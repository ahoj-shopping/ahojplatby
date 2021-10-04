<?php

namespace Ahoj;

class AhojApiRepository
{
    const CREATE_APPLICATION_URL = '/eshop/application';
    const GET_APPLICATION_URL_URL = '/eshop/application/{contractNumber}/application-url';
    const GET_APPLICATION_INFO_URL = '/eshop/application/{contractNumber}';
    const GET_PROMOTIONS_URL = '/eshop/{businessPlace}/calculation/promotions';
    const POST_CALCULATION_URL = '/eshop/{businessPlace}/calculation/';

    private $baseApiUrlMap = array(
        'dev' => 'https://api.test.psws.xyz',
        'test' => 'https://api.pilot.ahojsplatky.sk',
        'prod' => 'https://api.ahojsplatky.sk',
    );

    function __construct($eshopKey, $mode = 'prod')
    {
        $this->mode = $mode;
        $this->eshopKey = $eshopKey;
    }

    function httpPostApplication($applicationRequest)
    {
        $url = $this->getBaseUrl() . self::CREATE_APPLICATION_URL;
        $postDataEncoded = json_encode($applicationRequest);

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataEncoded);
        $responseBody = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseDecoded = json_decode($responseBody, true);
        curl_close($ch);

        return array(
            'body' => $responseDecoded,
            'code' => $responseCode,
        );
    }

    function httpPostCalculation($applicationRequest, $businessPlace)
    {
        $url = $this->getBaseUrl() . str_replace('{businessPlace}', $businessPlace, self::POST_CALCULATION_URL);
        $postDataEncoded = json_encode($applicationRequest);

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataEncoded);
        $responseBody = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseDecoded = json_decode($responseBody, true);
        curl_close($ch);

        return array(
            'body' => $responseDecoded,
            'code' => $responseCode,
        );
    }

    function httpGetApplicationInfo($contractNumber)
    {
        $getApplicationUrl = str_replace('{contractNumber}', $contractNumber, self::GET_APPLICATION_INFO_URL);
        $url = $this->getBaseUrl() . $getApplicationUrl;

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $url);
        $responseBody = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseDecoded = json_decode($responseBody, true);
        curl_close($ch);

        return array(
            'body' => $responseDecoded,
            'code' => $responseCode,
        );
    }

    function httpGetApplicationUrl($contractNumber, $queryParamsArr)
    {
        $getApplicationUrl = str_replace('{contractNumber}', $contractNumber, self::GET_APPLICATION_URL_URL);
        $url = $this->getBaseUrl() . $getApplicationUrl;
        $url .= $this->prepareQueryParamString($queryParamsArr);

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $url);
        $responseBody = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array(
            'body' => is_string($responseBody) ? $responseBody : json_decode($responseBody, true),
            'code' => $responseCode,
        );
    }

    function httpGetPromotions($businessPlace)
    {
        $promotionUrl = str_replace('{businessPlace}', $businessPlace, self::GET_PROMOTIONS_URL);
        $url = $this->getBaseUrl() . $promotionUrl;

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $url);
        $responseBody = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseDecoded = json_decode($responseBody, true);
        curl_close($ch);

        return array(
            'body' => $responseDecoded,
            'code' => $responseCode,
        );
    }

    /**
     * private
     */
    private function prepareQueryParamString($queryParamArray)
    {
        $filtered = array_filter($queryParamArray, function ($var) {
            return !is_null($var);
        });
        $filtered = array_map(function ($var) {
            return urlencode($var);
        }, $filtered);
        $queryStr = '';
        if (count($filtered) > 0) {
            $queryStr .=
                '?' .
                implode(
                    '&',
                    array_map(
                        function ($v, $k) {
                            return sprintf('%s=%s', $k, $v);
                        },
                        $filtered,
                        array_keys($filtered)
                    )
                );
        }
        return $queryStr;
    }

    private function getBaseUrl()
    {
        if (array_key_exists($this->mode, $this->baseApiUrlMap)) {
            return $this->baseApiUrlMap[$this->mode];
        }
        return $this->baseApiUrlMap['prod'];
    }

    private function initCurl()
    {
        $apiKey = $this->eshopKey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "API_KEY: $apiKey",
        ));
        return $ch;
    }
}
