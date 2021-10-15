<?php

namespace Ahoj;

require_once 'ahoj-api-repository.php';
require_once 'ahoj-exceptions.php';

class AhojPay
{
    const VERSION = '2.0.0';
    const PRODUCT_TYPE_CODE_ODLOZTO = 'GOODS_DEFERRED_PAYMENT';
    const PRODUCT_TYPE_CODE_ROZLOZTO = 'GOODS_SPLIT_PAYMENT';

    const PROMOTION_CODE_ODLOZTO = 'DP_DEFER_IT';

    /**
     * @deprecated Ponechana premenna pre potreby spatnej kompatibility s verziou 1.5.1. Pouzite AhojPay::PRODUCT_TYPE_CODE_ODLOZTO
     */
    const PRODUCT_TYPE_CODE = self::PRODUCT_TYPE_CODE_ODLOZTO;

    const PRODUCT_BANNER_CSS_CLASS = 'ahojpay-product-banner';
    const PAYMENT_METHOD_DESC_CSS_CLASS = 'ahojpay-payment-method-description';

    protected $promotionInfo = null;
    protected $repository;
    protected $config;

    private $jsPluginScriptSrcMap = array(
        'dev' => 'https://eshop.test.psws.xyz/merchant/plugin/ahojpay.js',
        'test' => 'https://eshop.pilot.ahojsplatky.sk/merchant/plugin/ahojpay.js',
        'prod' => 'https://eshop.ahojsplatky.sk/merchant/plugin/ahojpay.js',
    );

    /**
     * Priklad pouzitia:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(
     *          "mode" => "test",
     *          "businessPlace" => "TEST_ESHOP",
     *          "eshopKey" => "1111111111aaaaaaaaaa2222",
     *          "notificationCallbackUrl" => "https://eshop.com/notifications/ahojpay/",
     *      ));
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param array $config Pole s parametrami pre configuraciu sluzby AhojPay, ktore obsahuje:
     * - mode ("test"|"prod") - required: prevádzkový režim Pluginu, (nadobúda hodnotu „test“ alebo „prod“)
     * - businessPlace (string) - required: identifikátor E-shopu v rámci služby AhojPay pridelený v priebehu integrácie E-shopu
     * - eshopKey (string) - required: autorizačný kľúč predajného miesta
     * - notificationCallbackUrl (string) - required: notifikačný URL odkaz definovaný E-shopom, ktorý slúži informovanie E-shopu zo strany Pluginu o stave procesu spracovania žiadosti, napr.: https://E-shop.sk/ notificationCallback'
     *
     * @throws Ahoj\InvalidArgumentException V pripade, ze config nie je validny.
     * @throws Ahoj\ProductNotAvailableException V pripade, ze sluzba AhojPay nie je aktivna pre zadany businessPlace.
     * @throws Ahoj\ApiErrorException V pripade, ze niektore volanie na server neskonci uspesne. Napriklad pri InternalServerError
     */
    function __construct($config)
    {
        $this->validateConfig($config);
        $this->config = $config;
        $this->repository = new AhojApiRepository($config['eshopKey'], $config['mode']);
        try {
            $this->promotionInfo = $this->getPromotionInfo();
        } catch (ApiErrorException $apiException) {
        }
    }

    /**
     * API calls
     */

    /**
     * Funkcia pre zlozenie ziadosti v sluzbe AhojPay.
     *
     * Priklad pouzitia:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      $response = $ahojpay->createApplication(array(
     *          "orderNumber" => 1234,
     *          "completionUrl" => "https://example.com/complete/1234/whatever
     *          "terminationUrl" => "https://example.com/error/1234/whatever
     *          "eshopRegisteredCustomer" => false,
     *          "customer" => array(
     *              "firstName" => "Zákazník",
     *              "lastName" => "Nakupujúci",
     *              "contactInfo" => array(
     *                  "email" => "developer@ahoj.shopping",
     *                  "mobile" => "421944130665"
     *              ),
     *              "permanentAddress" => array(
     *                  "street" => "Ulicová",
     *                  "registerNumber" => "123",
     *                  "referenceNumber" => "456/A",
     *                  "city" => "Mestečko",
     *                  "zipCode" => "98765",
     *              )
     *          ),
     *          "product" => array(
     *              "goodsDeliveryTypeText" => "local_pickup",
     *              "goodsDeliveryAddress" => array(
     *                  "name" => "Domov",
     *                  "street" => "Hviezdolavova",
     *                  "registerNumber" => "3",
     *                  "referenceNumber" => "538/A",
     *                  "city" => "Mestečko",
     *                  "zipCode" => "98765",
     *                  "country" => "SK"
     *              ),
     *              "goods" => array(
     *                  array(
     *                      "name" => "Bicykel",
     *                      "price" => 199.9,
     *                      "id" => "1234567890",
     *                      "count" => 1,
     *                      "additionalServices" => array(
     *                          array(
     *                              "id" => "9876543210",
     *                              "name" => "Poistenie",
     *                              "price" => 9.99
     *                          )
     *                      ),
     *                      "typeText" => "goods",
     *                      "codeText" => array(
     *                          "8584027341404",
     *                          "444312312444",
     *                      ),
     *                      "nonMaterial" => false,
     *                      "commodityText" => array(
     *                          "bicykel",
     *                          "elektro bicykel"
     *                      ),
     *                  )
     *              ),
     *              "goodsDeliveryCosts" => 3.5
     *          )
     *      ), $productType);
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param array $applicationParameters - array typu Application popisany v integracnej prirucke
     * @param string $promotionCode - promotion code produktu, pre ktory je vytvarana ziadost
     * @return array Pole s hodnotami popisanymi nizsie:
     * Parametre v navratovej hodnote (pole):
     * - applicationUrl (string):URL, na ktorej zadava klient svoje udaje potrebne pre poskytnutie sluzby AhojPay. Zobrazuje sa v iframe za pomoci JS.
     * - contractNumber (string): Cislo novo vytvoreneho kontraktu
     *
     * @throws Ahoj\InvalidArgumentException V pripade, ze vstup $applicationParameters nie je validny.
     * @throws Ahoj\TotalPriceExceedsLimitsException V pripade, celkova suma objednavky prekracuje limity urcene pre sluzbu AhojPay (minGoodsPrice a maxGoodsPrice)
     * @throws Ahoj\ProductNotAvailableException V pripade, ze sluzba AhojPay nie je aktivna pre zadany businessPlace.
     * @throws Ahoj\ApiErrorException V pripade, ze niektore volanie na server neskonci uspesne. Napriklad InternalServerError
     */
    function createApplication($applicationParameters, $promotionCode = self::PROMOTION_CODE_ODLOZTO)
    {
        $this->checkAvailabilityAndThrow($promotionCode);
        $applicationParameters = $this->filterEmptyItems($applicationParameters);
        $this->validateApplicationParameters($applicationParameters);
        $applicationRequest = $this->prepareCreateApplicationParams($applicationParameters, $promotionCode);

        $totalOrderPrice = $this->calculateTotalOrderPrice($applicationRequest);
        if (!$this->isAvailableForTotalPrice($totalOrderPrice)) {
            throw new TotalPriceExceedsLimitsException();
        }

        $response = $this->repository->httpPostApplication($applicationRequest);
        $responseBody = $response['body'];
        $responseCode = $response['code'];

        if ($responseCode == 400) {
            throw new InvalidArgumentException($responseBody['message']);
        }

        if ($responseCode > 200) {
            throw new ApiErrorException($responseBody, $responseCode);
        }

        $contractNumber = $responseBody['contractNumber'];

        return array(
            'applicationUrl' => $this->getApplicationUrl(
                $contractNumber,
                isset($applicationRequest['completionUrl']) ? $applicationRequest['completionUrl'] : null,
                isset($applicationRequest['terminationUrl']) ? $applicationRequest['terminationUrl'] : null
            ),
            'contractNumber' => $contractNumber,
            'applicationInfo' => $responseBody,
        );
    }

    /**
     * Metoda na ziskavanie URL, na ktorej zadava klient svoje udaje potrebne pre poskytnutie sluzby AhojPay.
     *
     * @param string $contractNumber Cislo konktraktu, ktory je vrateny pri volani funkcie `createApplication()`
     * @param string|null $completionUrl
     * @param string|null $terminationUrl
     * @return string URL kde klient zadava udaje potrebne pre poskytnutie sluzby. null v pripade, ze nie je mozne ziskat URL zo servera.
     *
     * @throws Ahoj\ProductNotAvailableException V pripade, ze sluzba AhojPay nie je aktivna pre zadany businessPlace.
     * @throws Ahoj\ApiErrorException V pripade, ze niektore volanie na server neskonci uspesne. Napriklad InternalServerError
     */
    function getApplicationUrl($contractNumber, $completionUrl = null, $terminationUrl = null)
    {
        $response = $this->repository->httpGetApplicationUrl($contractNumber, array(
            'completionUrl' => $completionUrl,
            'earlyTerminationUrl' => $terminationUrl,
        ));
        $responseBody = $response['body'];
        $responseCode = $response['code'];

        if ($responseCode == 404) {
            throw new ContractNotExistException("Ziadost s cislom \"$contractNumber\" neexistuje");
        }

        if ($responseCode > 200) {
            throw new ApiErrorException($responseBody, $responseCode);
        }

        return $responseBody;
    }

    /**
     * Funkcia pre ziskavvanie stavu ziadosti (vsetky info o nej)
     *
     * Priklad:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      $applicationState = $ahojpay->createApplication("1231231231");
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param string $contractNumber Cislo ziadost
     * @return string Vrati stav ziadosti
     *
     * @throws Ahoj\ContractNotExistException V pripade, ze ziadost s cislom $contractNumber neexistuje
     * @throws Ahoj\ApiErrorException V pripade, ze niektore volanie na server neskonci uspesne. Napriklad InternalServerError
     */
    function getApplicationState($contractNumber)
    {
        $response = $this->repository->httpGetApplicationInfo($contractNumber);
        $responseBody = $response['body'];
        $responseCode = $response['code'];

        if ($responseCode == 404) {
            throw new ContractNotExistException("Ziadost s cislom \"$contractNumber\" neexistuje");
        }

        if ($responseCode > 200) {
            throw new ApiErrorException($responseBody, $responseCode);
        }

        return $responseBody['state'];
    }

    /**
     * Funkcia na ziskavanie nastavenia sluzby AhojPay. V pripade, ze je sluzba pre businessPlace (definovany v konstruktore).
     *
     * Priklad:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      $promotionInfo = $ahojpay->getPromotionInfo();
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param boolean $forceReload V pripade, ze je nastaveny parameter na `true` dopytuje sa metoda na server pre ziskanie cerstvych dat. Ak je parameter nastaveny na `false` vrati zapamatanu poslednu hodnotu ak existuje. Inak sa dopytuje na server. By default `false`
     * @return null|array Pole nastaveni produktov ahoj platieb. null v pripade, ze nie je ziadny produkt pre businessPlace (zadany v konstruktore) aktivny.
     *
     * @throws ApiErrorException V pripade, ze volanie na server neskonci uspesne. Napriklad pri InternalServerError alebo BadRequest.
     */
    function getPromotionInfo($forceReload = false)
    {
        if (!$forceReload && $this->promotionInfo) {
            return $this->promotionInfo;
        }

        $response = $this->repository->httpGetPromotions($this->config['businessPlace']);
        $responseBody = $response['body'];
        $responseCode = $response['code'];

        if ($responseCode > 200) {
            throw new ApiErrorException($responseBody, $responseCode);
        }

        $promotionInfo = array();

        foreach ($responseBody as $promotion) {
            if (
                $promotion['productType'] &&
                in_array($promotion['productType']['code'], array(
                    self::PRODUCT_TYPE_CODE_ODLOZTO,
                    self::PRODUCT_TYPE_CODE_ROZLOZTO,
                ))
            ) {
                $info = array(
                    'productType' => $promotion['productType']['code'],
                    'code' => $promotion['code'],
                    'name' => $promotion['name'],
                    'description' => $promotion['description'],
                    'minGoodsPrice' => $promotion['minGoodsPrice'],
                    'minGoodsItemPrice' => $promotion['minGoodsItemPrice'],
                    'maxGoodsPrice' => $promotion['maxGoodsPrice'],
                    'maxGoodsPriceProspect' => $promotion['maxGoodsPriceProspect'],
                    'instalmentIntervalDays' => $promotion['instalmentIntervalDays'],
                    'instalmentCount' => $promotion['instalmentCount'],
                );
                if ($promotion['productType']['code'] === self::PRODUCT_TYPE_CODE_ODLOZTO) {
                    $info['interest'] = array_key_exists('interest', $promotion) ? $promotion['interest'] : 0;
                }
                if ($promotion['productType']['code'] === self::PRODUCT_TYPE_CODE_ROZLOZTO) {
                    $info['instalmentDayOfMonth'] = $promotion['productType']['instalmentDayOfMonth'];
                }
                array_push($promotionInfo, $info);
            }
        }

        return count($promotionInfo) >= 1 ? $promotionInfo : null;
    }

    /**
     * Funkcia pre kalkulaciu produktu ahoj platieb
     *
     * Priklad pouzitia:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      $response = $ahojpay->getCalculation($totalPrice, $promotionCode);
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param array $totalPrice Cena, pre ktoru sa vytvara kalkulacia
     * @param string $promotionCode - promotion code produktu ahoj platieb, pre ktory je vytvarana kalkulacia
     * @return array Pole s hodnotami kalkulacie popisane v integracnej prirucke.
     *
     * @throws Ahoj\ProductNotAvailableException V pripade, ze AhojPay produkt nie je aktivny pre zadany businessPlace.
     * @throws Ahoj\ApiErrorException V pripade, ze niektore volanie na server neskonci uspesne. Napriklad InternalServerError
     */
    function getCalculation($totalPrice, $promotionCode)
    {
        $this->checkAvailabilityAndThrow($promotionCode);

        if ($promotionCode === self::PROMOTION_CODE_ODLOZTO) {
            return array();
        }

        $calculationParams = array();
        $calculationParams['goods'] = array(
            array(
                'price' => $totalPrice,
            ),
        );
        $productPromotionInfo = $this->getPromotionInfoForCode($promotionCode);
        if ($productPromotionInfo['productType'] === self::PRODUCT_TYPE_CODE_ROZLOZTO) {
            $calculationParams['instalmentCount'] = $productPromotionInfo['instalmentCount']['from'];
            $calculationParams['depositAmount'] = 0;
        }
        $calculationParams['promotion'] = array(
            'code' => $productPromotionInfo['code'],
        );

        $response = $this->repository->httpPostCalculation($calculationParams, $this->config['businessPlace']);
        $responseBody = $response['body'];
        $responseCode = $response['code'];

        if ($responseCode > 200) {
            throw new ApiErrorException($responseBody, $responseCode);
        }

        return array(
            'promotionCode' => $promotionCode,
            'productType' => $productPromotionInfo['productType'],
            'instalmentCount' => $responseBody['instalmentCount'],
            'instalment' => $responseBody['instalment'],
            'lastInstalment' => isset($responseBody['lastInstalment']) ? $responseBody['lastInstalment'] : null,
        );
    }

    /**
     * Funkcia pre vytvorenie kalkulacii pre vsetky aktivne produkty ahoj platieb
     *
     * Priklad pouzitia:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      $response = $ahojpay->getCalculations($totalPrice);
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param array $totalPrice Cena, pre ktoru sa vytvara kalkulacia
     * @return array Pole kalkulacii s hodnotami kalkulacie popisane v integracnej prirucke.
     *
     * @throws Ahoj\ApiErrorException V pripade, ze niektore volanie na server neskonci uspesne. Napriklad InternalServerError
     */
    function getCalculations($totalPrice)
    {
        $calculations = array();
        foreach ($this->promotionInfo as $index => $info) {
            array_push($calculations, $this->getCalculation($totalPrice, $info['code']));
        }

        return $calculations;
    }

    /**
     * Funkcia vracajuca vsetky platobne metody, ktore su dostupne v ahoj platbach
     *
     * Priklad pouzitia:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      $response = $ahojpay->getPaymentMethods($totalPrice);
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param array $totalPrice Cena, pre ktoru sa vytvara kalkulacia
     * @return array Pole platobnych metod popisane v integracnej prirucke.
     */
    function getPaymentMethods($totalPrice)
    {
        $paymentMethodArr = array();
        foreach ($this->promotionInfo as $index => $productTypePromotionInfo) {
            array_push($paymentMethodArr, array(
                'promotionCode' => $productTypePromotionInfo['code'],
                'productType' => $productTypePromotionInfo['productType'],
                'name' => $this->getPaymentMethodName($productTypePromotionInfo),
                'isAvailable' => $this->isAvailableForTotalPrice($totalPrice, $productTypePromotionInfo['code']),
            ));
        }
        return $paymentMethodArr;
    }

    /**
     * HTML generation
     */

    /**
     * Vygenerovanie HTML/JS kodu s inicializaciou JS pluginu za pomoci dat z PHP
     *
     * Priklad:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      echo $ahojpay->generateInitJavaScriptHtml();
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param string $scriptUrl Cesta k JS scriptu. Pre testovacie ucely
     */
    function generateInitJavaScriptHtml($scriptUrl = null)
    {
        $jsPromotionInfoStr = json_encode($this->promotionInfo);
        $scriptUrl = isset($scriptUrl) ? $scriptUrl : $this->getJsScriptUrl();

        $html = '';
        $html .= "<script type=\"text/javascript\" src=\"$scriptUrl\"></script>\n";
        $html .= "<script type=\"text/javascript\">\n";
        $html .= "(function() {\n";
        $html .= "    var promotionInfo = JSON.parse('$jsPromotionInfoStr');\n";
        $html .= "    ahojpay.init(promotionInfo);\n";
        $html .= "})();\n";
        $html .= "</script>\n";
        return $html;
    }

    /**
     * Vygenerovanie HTML/JS kodu, ktory pomocou JS vykresluje produktovy banner. Tato funkcia generuje aj div element s css class, do ktoreho bude produktovy banner vykresleny.
     * V pripade, ze pre zadanu cenu produktu nie je mozne vyuzit sluzbu AhojPay produktovy banner nebude zobrazeny.
     *
     * Priklad:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      echo $ahojpay->generateInitJavaScriptHtml();
     *      echo $ahojpay->generateProductBannerHtml(123.45);
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param string|number $goodsAndServicesPrice suma jednotkovej ceny za tovar a všetkých doplnkových služieb zvolených Zákazníkom. Suma je uvádzaná v EUR s DPH.
     * @param string|null $cssClass Css trieda používaná pre div element, do ktorého má byť vykreslený produktový mini banner. Inicializačná hodnota je `ahojpay-product-banner`
     *
     * @return string vygenerovaný HTML kód s obsahom mini banera
     */
    function generateProductBannerHtml($goodsAndServicesPrice, $cssClass = self::PRODUCT_BANNER_CSS_CLASS)
    {
        $calculations = $this->getCalculations($goodsAndServicesPrice);
        $calculations = json_encode($calculations);

        $html = "<div class=\"$cssClass\"></div>\n";
        $html .= "<script type=\"text/javascript\">\n";
        $html .= "(function() {\n";
        $html .= "    var calculations = JSON.parse('$calculations');\n";
        $html .= "    ahojpay.productBanner(\"$goodsAndServicesPrice\", \".$cssClass\", calculations)\n";
        $html .= "})();\n";
        $html .= "</script>\n";
        return $html;
    }

    /**
     * Vygenerovanie HTML/JS kodu, ktory pomocou JS vykresluje popis k platobnej metode.
     *
     * Priklad:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      echo $ahojpay->generateInitJavaScriptHtml();
     *      echo $ahojpay->generatePaymentMethodDescriptionHtml(1230.45);
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param string|number $price celková suma objednaných tovarov vrátane doplnkových služieb bez ceny nákladov na prepravu tovaru v EUR s DPH
     * @param string|null $cssClass Css trieda používaná pre div element, do ktorého má byť vykreslený popis k platbe prostredníctvom služby KTZo30d. Inicializačná hodnota je `ahojpay-payment-method-description`.
     * @param string $promotionCode - promotion code produktu ahoj platieb, pre ktory je zobrazovany payment method description
     *
     * @return string vygenerovaný HTML kód s obsahom popisu platobnej metódy
     */
    function generatePaymentMethodDescriptionHtml($price, $cssClass, $promotionCode = self::PROMOTION_CODE_ODLOZTO)
    {
        $calculation = $this->getCalculation($price, $promotionCode);
        $calculation = json_encode($calculation);

        $html = "<div class=\"$cssClass\"></div>\n";
        $html .= "<script type=\"text/javascript\">\n";
        $html .= "(function() {\n";
        $html .= "    var calculation = JSON.parse('$calculation');\n";
        $html .= "    ahojpay.paymentMethodDescription(\"$price\", \".$cssClass\", \"$promotionCode\", calculation)\n";
        $html .= "})();\n";
        $html .= "</script>\n";
        return $html;
    }

    /**
     * Vygenerovanie HTML/JS kodu, ktory pomocou JS otvori iframe so ziadostou hned po nacitani stranky.
     *
     * Priklad:
     * <?php
     * try {
     *      $ahojpay = new Ahoj\AhojPay(array(...));
     *      echo $ahojpay->generateInitJavaScriptHtml();
     *      echo $ahojpay->generateApplicationIframeOnLoadHtml(array(...), 2000);
     * } catch (Exception $e) {
     *    // Error handling
     * }
     * ?>
     *
     * @param array $applicationParameters Array s parametrami na vytvorenie ziadosti. Rovnake ako pri funkcii createApplication
     * @param number|null $delay Cas v ms, ktory sa bude cakat po loade stranky nez sa otvori iframe so ziadostou.
     *
     * @throws Ahoj\InvalidArgumenContractNotExistExceptiontException V pripade, ze $applicationParameters nie je validny.
     * @throws Ahoj\ProductNotAvailableException V pripade, ze sluzba AhojPay nie je aktivna pre zadany businessPlace.
     * @throws Ahoj\ApiErrorException V pripade, ze niektore volanie na server neskonci uspesne. Napriklad InternalServerError
     */
    function generateApplicationIframeOnLoadHtml($applicationParameters, $delay = 0)
    {
        $delay = is_numeric($delay) ? $delay : 0;
        $applicationResult = $this->createApplication($applicationParameters);

        if (empty($applicationResult)) {
            return '';
        }
        $applicationUrl = $applicationResult['applicationUrl'];

        $html = "<script type=\"text/javascript\">\n";
        $html .= "(function() {\n";
        $html .= "    var delay = Number($delay);\n";
        $html .= "    setTimeout(() => {\n";
        $html .= "        ahojpay.openApplication(\"$applicationUrl\");\n";
        $html .= "    }, delay);\n";
        $html .= "})();\n";
        $html .= "</script>\n";
        return $html;
    }

    /**
     * public helper methods
     */

    /**
     * Vrati src JS scriptu nutneho pre vykreslovanie product bannera + payment method descr
     */
    function getJsScriptUrl()
    {
        $mode = $this->config['mode'];
        if (array_key_exists($mode, $this->jsPluginScriptSrcMap)) {
            return $this->jsPluginScriptSrcMap[$mode];
        }
        return $this->jsPluginScriptSrcMap['prod'];
    }

    /**
     * Vrati true ak je sluzba AhojPay dostupna
     *
     * @param string $promotionCode - promotion code produktu ahoj platieb, pre ktory je zistovana dostupnost
     */
    function isAvailable($promotionInfo = self::PROMOTION_CODE_ODLOZTO)
    {
        $productPromotionInfo = $this->getPromotionInfoForCode($promotionInfo);
        return $productPromotionInfo != null;
    }

    /**
     * @param number $totalPrice celková suma objednaných tovarov vrátane doplnkových služieb bez ceny nákladov na prepravu tovaru v EUR s DPH
     * @param string $promotionCode - promotion code produktu ahoj platieb, pre ktory je zistovana dostupnost
     *
     * @return boolean parameter definuje, či suma objednávky (spolu s doplnkovými službami k tovaru - poistenie, predĺžená záruka a pod.) vyhovuje intervalu medzi minimálnou a maximálnou sumou objednávky, pričom do sumy objednávky sa nezapočítavajú náklady spojené s prepravou tovaru (poštovné, balné a pod.) a služba AhojPay je pre daný E-shop dostupná.
     */
    function isAvailableForTotalPrice($totalPrice, $promotionInfo = self::PROMOTION_CODE_ODLOZTO)
    {
        $productPromotionInfo = $this->getPromotionInfoForCode($promotionInfo);
        return $this->isAvailable($promotionInfo) &&
            $productPromotionInfo['minGoodsPrice'] <= $totalPrice &&
            $productPromotionInfo['maxGoodsPrice'] >= $totalPrice;
    }

    /**
     * Vrati true ak je AhojPay sluzba aktivna a ak pre sumu $productPrice je mozne tuto sluzbu pouzit.
     *
     * @param number $productPrice
     * @param string $promotionCode - promotion code produktu ahoj platieb, pre ktory je zistovana dostupnost
     *
     * @param number $productPrice Cena produktu
     */
    function isAvailableForItemPrice($productPrice, $promotionInfo = self::PROMOTION_CODE_ODLOZTO)
    {
        $productPromotionInfo = $this->getPromotionInfoForCode($promotionInfo);
        return $this->isAvailable($promotionInfo) &&
            $productPromotionInfo['minGoodsItemPrice'] <= $productPrice &&
            $productPromotionInfo['maxGoodsPrice'] >= $productPrice;
    }

    /**
     * protected
     */

    /**
     * private
     */
    private function getPaymentMethodName($promotionInfo)
    {
        switch ($promotionInfo['productType']) {
            case AhojPay::PRODUCT_TYPE_CODE_ODLOZTO:
                return 'o ' . $promotionInfo['instalmentIntervalDays'] . ' dní bez navýšenia';
                break;
            case AhojPay::PRODUCT_TYPE_CODE_ROZLOZTO:
                return 'v ' . $promotionInfo['instalmentCount']['from'] . ' platbách bez navýšenia';
                break;
            default:
                return '';
        }
    }

    private function getPromotionInfoForCode($promotionCode)
    {
        if ($this->promotionInfo) {
            $key = array_search(
                $promotionCode,
                array_map(function ($info) {
                    return $info['code'];
                }, $this->promotionInfo)
            );
            return $key >= 0 ? $this->promotionInfo[$key] : null;
        }
        return null;
    }

    private function filterEmptyItems($var)
    {
        if (is_array($var) || is_object($var)) {
            foreach ($var as $key => $value) {
                if (is_array($value)) {
                    if (count($value) <= 0) {
                        unset($var[$key]);
                    } else {
                        $var[$key] = $this->filterEmptyItems($var[$key]);
                    }
                } else {
                    if (is_null($value) || $value === '') {
                        unset($var[$key]);
                    }
                }
            }
        }

        return $var;
    }

    private function prepareCreateApplicationParams(
        $applicationParameters,
        $promotionCode = self::PROMOTION_CODE_ODLOZTO
    ) {
        $applicationRequest = $applicationParameters;
        $productPromotionInfo = $this->getPromotionInfoForCode($promotionCode);

        if (!empty($this->config['notificationCallbackUrl'])) {
            $applicationRequest['notificationCallbackUrl'] = $this->config['notificationCallbackUrl'];
        }
        $applicationRequest['product']['promotion'] = array(
            'code' => $productPromotionInfo['code'],
        );
        $applicationRequest['businessPlace'] = $this->config['businessPlace'];
        // By default is this ESHOP. Should be configurable in future versions od AhojPay service
        $applicationRequest['salesChannel'] = 'ESHOP';
        $applicationRequest['state'] = 'DRAFT';
        $applicationRequest['eshopWeb'] = $_SERVER['SERVER_NAME'];

        // always SK - integration manual
        $applicationRequest['customer']['permanentAddress']['country'] = array(
            'code' => 'SK',
        );

        if (array_key_exists('phone', $applicationRequest['customer']['contactInfo'])) {
            $applicationRequest['customer']['contactInfo']['phone'] = $this->normalizePhoneNumber(
                $applicationRequest['customer']['contactInfo']['phone']
            );
        }

        // delivery address
        $permanentAddress = $applicationRequest['customer']['permanentAddress'];
        if (array_key_exists('goodsDeliveryAddress', $applicationRequest['product'])) {
            if (array_key_exists('country', $applicationRequest['product']['goodsDeliveryAddress'])) {
                $applicationRequest['product']['goodsDeliveryAddress']['country'] = array(
                    'code' => strtoupper($applicationRequest['product']['goodsDeliveryAddress']['country']),
                );
            }
        } else {
            $applicationRequest['product']['goodsDeliveryAddress'] = $permanentAddress;
        }

        // convert goods -> codeText and commodityText to string
        foreach ($applicationRequest['product']['goods'] as &$good) {
            if (array_key_exists('codeText', $good) && is_array($good['codeText'])) {
                $good['codeText'] = implode(';', $good['codeText']);
            }
            if (array_key_exists('commodityText', $good) && is_array($good['commodityText'])) {
                $good['commodityText'] = implode(';', $good['commodityText']);
            }
        }

        // rozlozto product
        if ($productPromotionInfo['productType'] === self::PRODUCT_TYPE_CODE_ROZLOZTO) {
            $applicationRequest['product']['instalmentCount'] = $productPromotionInfo['instalmentCount']['from'];
            $applicationRequest['product']['depositAmount'] = 0;
        }
        return $applicationRequest;
    }

    private function validateConfig($config)
    {
        $requiredConfigKeys = array('mode', 'businessPlace', 'eshopKey', 'notificationCallbackUrl');
        $possibleModes = array('prod', 'test', 'dev');

        foreach ($requiredConfigKeys as $configKey) {
            if (empty($config[$configKey]) || !is_string($config[$configKey])) {
                throw new InvalidArgumentException("$configKey je povinny udaj");
            }
        }

        if (!in_array($config['mode'], $possibleModes)) {
            throw new InvalidArgumentException('Povolene hodnoty pre config mode su: ' . implode(',', $possibleModes));
        }
    }

    private function getValueByKey($key, $data, $default = null)
    {
        if (!is_string($key) || empty($key) || !count($data)) {
            return $default;
        }

        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            foreach ($keys as $innerKey) {
                if (!array_key_exists($innerKey, $data)) {
                    return $default;
                }
                $data = $data[$innerKey];
            }
            return $data;
        }
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    private function calculateTotalOrderPrice($applicationParameters)
    {
        $totalPrice = 0.0;

        foreach ($applicationParameters['product']['goods'] as $good) {
            $amountOfGood = $good['count'] >= 1 ? $good['count'] : 1;
            $totalPrice += $amountOfGood * $good['price'];

            if (isset($good['additionalServices'])) {
                foreach ($good['additionalServices'] as $additionalService) {
                    $totalPrice += $additionalService['price'];
                }
            }
        }

        // add delivery costs
        if (isset($applicationParameters['product']['goodsDeliveryCosts'])) {
            $totalPrice += $applicationParameters['product']['goodsDeliveryCosts'];
        }

        return $totalPrice;
    }

    private function validateApplicationParameters($applicationParameters)
    {
        if (!is_array($applicationParameters)) {
            throw new InvalidArgumentException('vstupny parameter musi byt array');
        }
        if (
            !array_key_exists('orderNumber', $applicationParameters) ||
            !(is_string($applicationParameters['orderNumber']) || is_numeric($applicationParameters['orderNumber'])) ||
            $applicationParameters['orderNumber'] === ''
        ) {
            throw new InvalidArgumentException('orderNumber je povinny udaj');
        }
        if (
            !array_key_exists('eshopRegisteredCustomer', $applicationParameters) ||
            !is_bool($applicationParameters['eshopRegisteredCustomer'])
        ) {
            throw new InvalidArgumentException('eshopRegisteredCustomer je povinny boolean udaj');
        }
        // customer
        if (!array_key_exists('customer', $applicationParameters)) {
            throw new InvalidArgumentException('customer je povinny udaj');
        }
        if (!array_key_exists('contactInfo', $applicationParameters['customer'])) {
            throw new InvalidArgumentException('customer.contactInfo je povinny udaj');
        }
        // customer required fields
        $customerRequiredStringFields = array(
            'firstName',
            'lastName',
            'contactInfo.email',
            'permanentAddress.street',
            'permanentAddress.city',
            'permanentAddress.zipCode',
        );
        foreach ($customerRequiredStringFields as $customerKey) {
            $value = $this->getValueByKey($customerKey, $applicationParameters['customer'], null);
            if (!is_string($value) || $value === '') {
                throw new InvalidArgumentException("customer.$customerKey je povinny textovy udaj");
            }
        }
        // product
        if (!array_key_exists('product', $applicationParameters)) {
            throw new InvalidArgumentException('product je povinny udaj');
        }
        // product.goodsDeliveryAddress.country
        if (
            array_key_exists('goodsDeliveryAddress', $applicationParameters['product']) &&
            array_key_exists('country', $applicationParameters['product']['goodsDeliveryAddress'])
        ) {
            if (
                !is_string($applicationParameters['product']['goodsDeliveryAddress']['country']) ||
                strlen($applicationParameters['product']['goodsDeliveryAddress']['country']) !== 2
            ) {
                throw new InvalidArgumentException(
                    'product.goodsDeliveryAddress.country musi byt vo formate ISO 3166-1 alpha-2'
                );
            }
        }
        // product.goodsDeliveryCosts
        if (
            !array_key_exists('goodsDeliveryCosts', $applicationParameters['product']) ||
            !is_numeric($applicationParameters['product']['goodsDeliveryCosts'])
        ) {
            throw new InvalidArgumentException('product.goodsDeliveryCosts je povinny numericky udaj');
        }
        // product.goods
        if (
            !array_key_exists('goods', $applicationParameters['product']) ||
            !is_array($applicationParameters['product']['goods']) ||
            count($applicationParameters['product']['goods']) <= 0
        ) {
            throw new InvalidArgumentException('product.goods je povinny udaj a musi obsahovat aspon jednu hodnotu');
        }
        foreach ($applicationParameters['product']['goods'] as $goodItemKey => $goodItem) {
            if (!array_key_exists('name', $goodItem) || !is_string($goodItem['name']) || $goodItem['name'] === '') {
                throw new InvalidArgumentException("product.goods[$goodItemKey].name je povinny textovy udaj");
            }
            if (!array_key_exists('price', $goodItem) || !is_numeric($goodItem['price'])) {
                throw new InvalidArgumentException("product.goods[$goodItemKey].price je povinny numericky udaj");
            }
            if (!array_key_exists('id', $goodItem) || !is_string($goodItem['id']) || $goodItem['id'] === '') {
                throw new InvalidArgumentException("product.goods[$goodItemKey].id je povinny textovy udaj");
            }
            if (!array_key_exists('count', $goodItem) || !is_numeric($goodItem['count'])) {
                throw new InvalidArgumentException("product.goods[$goodItemKey].count je povinny numericky udaj");
            }

            if (array_key_exists('additionalServices', $goodItem)) {
                if (!is_array($goodItem['additionalServices'])) {
                    throw new InvalidArgumentException(
                        "product.goods[$goodItemKey].additionalServices je povinny udaj a musi obsahovat aspon jednu hodnotu"
                    );
                }
                foreach ($goodItem['additionalServices'] as $additionalServicesItemKey => $additionalServicesItem) {
                    if (
                        !array_key_exists('id', $additionalServicesItem) ||
                        !is_string($additionalServicesItem['id']) ||
                        $additionalServicesItem['id'] === ''
                    ) {
                        throw new InvalidArgumentException(
                            "product.goods[$goodItemKey].additionalServices[$additionalServicesItemKey].id je povinny textovy udaj"
                        );
                    }
                    if (
                        !array_key_exists('name', $additionalServicesItem) ||
                        !is_string($additionalServicesItem['name']) ||
                        $additionalServicesItem['name'] === ''
                    ) {
                        throw new InvalidArgumentException(
                            "product.goods[$goodItemKey].additionalServices[$additionalServicesItemKey].name je povinny textovy udaj"
                        );
                    }
                    if (
                        !array_key_exists('price', $additionalServicesItem) ||
                        !is_numeric($additionalServicesItem['price'])
                    ) {
                        throw new InvalidArgumentException(
                            "product.goods[$goodItemKey].additionalServices[$additionalServicesItemKey].price je povinny numericky udaj"
                        );
                    }
                }
            }
        }
    }

    private function normalizePhoneNumber($phoneNumber)
    {
        $normalizedPhoneNumber = str_replace(' ', '', $phoneNumber);
        $normalizedPhoneNumber = str_replace('+', '', $normalizedPhoneNumber);
        if (strpos($normalizedPhoneNumber, '0') === 0) {
            $normalizedPhoneNumber = substr($normalizedPhoneNumber, 1);
            $normalizedPhoneNumber = '421' . $normalizedPhoneNumber;
        }
        return $normalizedPhoneNumber;
    }

    private function checkAvailabilityAndThrow($promotionCode)
    {
        if (!$this->isAvailable($promotionCode)) {
            throw new ProductNotAvailableException(
                'Sluzba ' .
                    $promotionCode .
                    ' nie je pre zadany businessPlace dostupna. Skontrolujte prosim konfiguracne parametre alebo kontaktujte support.'
            );
        }
    }
}
