<?php

include_once(dirname(__FILE__) . '/../autoload.php');

class AhojApi
{
	public $callback_url;
	public $success_url;
	public $fail_url;

	public $module;
	public $context;
	public $order;
	public $customer;
	public $ahojpay;

	public $debug;
	public $debug_data;

	public $cheapest_product_used = false;
	public $order_cart_for_discounts = array();
	public $cumulative_discount = 0;


	const SUCCESS = 'success';
	const FAIL = 'fail';

	function __construct($module)
	{
		$this->module = $module;
		$this->context = Context::getContext();
		
		$this->callback_url = $this->context->link->getModuleLink('ahojplatby', 'notification');
		// $this->success_url = $this->context->link->getModuleLink('ahojplatby', 'validation', array(
		// 	'action'	=>	self::SUCCESS,
		// 	'id_order'	=>	'id_order',
		// ));
		// $this->fail_url = $this->context->link->getModuleLink('ahojplatby', 'validation', array(
		// 	'action'	=>	self::FAIL,
		// 	'id_order'	=>	'id_order',
		// ));

		$this->setDebug(Configuration::get('AHOJPLATBY_MODULE_DEBUG'));
	}

	public function setDebug($debug = false)
	{
		$this->debug = $debug;
	}

	public function init()
	{	

		$test = Configuration::get('AHOJPLATBY_TEST_ENVIROMENT');
		$business_place = false;
		$eshop_key = false;
		if($test)
		{
			// $mode = 'test';
			$mode = 'dev';
			$business_place = 'TEST_ESHOP';
			$eshop_key = '1111111111aaaaaaaaaa2222';
		}
		else
		{
			$mode = 'prod';
			$business_place = Configuration::get('AHOJPLATBY_BUSINESS_PLACE');
			$eshop_key = Configuration::get('AHOJPLATBY_API_KEY');
		}

		if(!$business_place || !$eshop_key)
		{
			return false;
		}
		try {
			$this->ahojpay = new Ahoj\AhojPay(array(
				"mode" => $mode,
				"businessPlace" => $business_place,
				"eshopKey" => $eshop_key,
				"notificationCallbackUrl" => $this->callback_url,
			));
		} catch (PrestaShopException $e) {
	  		// Error handling
			Tools::displayError($e->getMessage());
		}

	}

	public function getProductData($price = 0)
	{	
		if(!isset($this->ahojpay) || !$this->ahojpay)
			return array();

		return array(
			'js' => $this->ahojpay->generateInitJavaScriptHtml(),
			'html_banner' => $this->ahojpay->generateProductBannerHtml($price)
		);
	}

	public function getInitJavascriptHtml()
	{
		return $this->ahojpay->generateInitJavaScriptHtml();
	}

	public function setOrder($order)
	{
		$this->order = $order;
		$this->customer = new Customer($order->id_customer);
	}

	public function createApplication($dev = false)
	{
		if(!$this->order)
			Tools::displayError('Order not set');

		$response = array();
		$customer = new Customer($this->order->id_customer);

		$data = array(
			'orderNumber' => $this->order->id,
			'completionUrl' => $this->success_url = $this->context->link->getModuleLink('ahojplatby', 'validation', array(
				'action'	=>	self::SUCCESS,
				'id_order'	=>	$this->order->id,
				'token'		=>	$this->getSecurityToken($this->order->id, self::SUCCESS)
			)),
			'terminationUrl' => $this->fail_url = $this->context->link->getModuleLink('ahojplatby', 'validation', array(
				'action'	=>	self::FAIL,
				'id_order'	=>	$this->order->id,
				'token'		=>	$this->getSecurityToken($this->order->id, self::FAIL)
			)),
			'eshopRegisteredCustomer' => $this->customer->isGuest(),
			'customer'	=>	$this->getCustomerData(),
			'product'	=>	$this->getOrderListData()
		);	

		if($dev)
		{
			dd(array(
				$data
			), true);
		}

		try {
			$response = $this->ahojpay->createApplication($data);
		} catch (PrestaShopException $e) {
			// Error handling
			// Tools::displayError($e->getMessage());
		}

		if($this->debug)
			$this->debug_data = $data;

		
		return $response;
	}

	public function getCustomerData()
	{
		$address = new Address($this->order->id_address_invoice);

		return array(
			'firstName'	=>	$this->customer->firstname,
			'lastName'	=>	$this->customer->lastname,
			'contactInfo'	=> array(
				'email'	=>	$this->customer->email,
				'mobile'=>	($address->phone ? $address->phone : $address->phone_mobile)
			),
			'permanentAddress'	=>	array(
				'street' => $address->address1,
				'registerNumber' => $address->address2,
				'referenceNumber' => '',
				'city' => $address->city,
				'zipCode' => $address->postcode,
			),
		);
	}

	public function getOrderListData()
	{
		$data = array();
		
		$res = $this->getExtCarrier();
		$data = array_merge($data, $res);

		$carrier = new Carrier($this->order->id_carrier);

		$res =  array(
			'goods'	=>	$this->getList(),
			'goodsDeliveryTypeText' => $carrier->name,
			'goodsDeliveryCosts' => AhojApi::formatPrice($this->order->total_shipping_tax_incl)
		);
		$data = array_merge($data, $res);

		return $data;
	}

	public function getList()
	{
		$list = $this->order->getOrderDetailList();
		$discounts = $this->getNonGiftDiscounts($this->order->id);
		$data = array();

		if(count($list) > 0)
		{
			foreach ($list as $key => $value) {
				$data[] = array(
					'name' => $value['product_name'],
					// 'price' => AhojApi::formatPrice($value['unit_price_tax_incl']),
					// TODO upravit kalkulacie cien podla kuponov
					'price' => $this->calculateItemPrice($value, $list, $discounts),
					'id' => $value['product_id'].'_'.$value['product_attribute_id'],
					'count' => $value['product_quantity'],
					'typeText'	=> 'goods',
					'codeText'	=> array_filter(array(
						$value['product_ean13'],
						$value['product_reference'],
						$value['product_isbn'],
						$value['product_upc']
					)),
					'nonMaterial' => AhojApi::isVritualProduct($value['product_id']),
					'commodityText' => AhojApi::getProductCategories($value['product_id'])
					// 'additionalServices' => array(
					// 	array(
					// 		'id' => '9876543210',
					// 		'name' => 'Poistenie',
					// 		'price' => 9.99
					// 	)
					// )
				);
			}

			// add discount as item row
			if($this->order->total_discounts > 0)
			{	
				$cart_rules_codes = AhojApi::formatOrderCartRulesCodes($this->order_cart_for_discounts);

				$data[] = array(
					'name' => $this->module->l('Zľava z objednávky'),
					'price' => -1 * AhojApi::formatPrice($this->getOrderCumulativeDiscount()),
					'id' => 'ABATEMENT',
					'count' => 1,
					'typeText'	=> 'ABATEMENT',
					'codeText'	=> $cart_rules_codes,
					'nonMaterial' => true
				);
			}
		}
		
		return $data;
	}

	public function getExtCarrier()
	{
		// zasielkovna v2.1.6 ps1.7
		// zasielkovna v2.0.5 ps1.6
		// shaim dpdparcelshop
		// easybalikomat v1.10

		$data = false;
		$result = array();

		/* zasielkovna */
		if(Module::isInstalled('packetery'))
		{
			if($this->module->is17)
				$data = ZasielkovnaAdapterClass::getCarrierOrderByIdOrder($this->order->id);
			else
				$data = ZasielkovnaAdapterClass::getCarrierOrderByIdOrder16($this->order->id);

			if($data)
			{
				$result = $this->formatExtCarrier($data);
			}			
		}
		/* zasielkovna end */

		/* dpd */
		if(Module::isInstalled('shaim_dpdparcelshop'))
		{
			$data = DpdAdapterClass::getCarrierOrderByIdCart($this->order->id_cart);
			if($data)
			{
				$result = $this->formatExtCarrier($data);
			}
		}
		/* dpd end */


		/* balikomat */
		if(Module::isInstalled('easybalikomat'))
		{
			$data = BalikomatAdapterClass::getCarrierOrderByIdOrder($this->order->id);
			if($data)
			{
				$result = $this->formatExtCarrier($data);
			}
		}
		/* balikomat end */

		return $result;
	}

	public function formatExtCarrier($data)
	{	
		if($data)
		{
			return array(
				'goodsDeliveryAddress' => array(
					'name' => (isset($data['name']) ? $data['name'] : ''),
					'street' => (isset($data['street']) ? $data['street'] : ''),
					'registerNumber' => '',
					'referenceNumber' => '',
					'city' => (isset($data['city']) ? $data['city'] : ''),
					'zipCode' => (isset($data['zip']) ? $data['zip'] : ''),
					'country' => (isset($data['country']) ? $data['country'] : 'SK'),
				),
			);
		}
		else
		{
			return array();
		}
		

	}

	// public function getRedirectUrl()
	// {
	// 	try {
	// 	     $response = $this->ahojpay->createApplication(array(
	// 	         "orderNumber" => 1234,
	// 	         "completionUrl" => "https://example.com/complete/1234/whatever",
	// 	         "terminationUrl" => "https://example.com/error/1234/whatever",
	// 	         "eshopRegisteredCustomer" => false,
	// 	         "customer" => array(
	// 	             "firstName" => "Zákazník",
	// 	             "lastName" => "Nakupujúci",
	// 	             "contactInfo" => array(
	// 	                 "email" => "developer@ahoj.shopping",
	// 	                 "mobile" => "421944130665"
	// 	             ),
	// 	             "permanentAddress" => array(
	// 	                 "street" => "Ulicová",
	// 	                 "registerNumber" => "123",
	// 	                 "referenceNumber" => "456/A",
	// 	                 "city" => "Mestečko",
	// 	                 "zipCode" => "98765",
	// 	             )
	// 	         ),
	// 	         "product" => array(
	// 	             "goods" => array(
	// 	                 array(
	// 	                     "name" => "Bicykel",
	// 	                     "price" => 199.9,
	// 	                     "id" => "1234567890",
	// 	                     "count" => 1,
	// 	                     "additionalServices" => array(
	// 	                         array(
	// 	                             "id" => "9876543210",
	// 	                             "name" => "Poistenie",
	// 	                             "price" => 9.99
	// 	                         )
	// 	                     )
	// 	                 )
	// 	             ),
	// 	             "goodsDeliveryCosts" => 3.5
	// 	         )
	// 	     ));
	// 	} catch (Exception $e) {
	// 	   // Error handling
	// 		Tools::displayError($e->getMessage());
	// 	}
	// }

	public function getPromotionInfo()
	{
		try {
			$response = $this->ahojpay->getPromotionInfo();
		} catch (PrestaShopException $e) {
			// Error handling
			// Tools::displayError($e->getMessage());
		}

		return $response;
	}

	public function isAvailableForTotalPrice($total = 0)
	{
		try {
			$response = $this->ahojpay->isAvailableForTotalPrice($total);
		} catch (PrestaShopException $e) {
			// Error handling
			// Tools::displayError($e->getMessage());
		}

		return $response;
	}

	public function isAvailableForItemPrice($itemPrice = 0)
	{
		try {
			$response = $this->ahojpay->isAvailableForItemPrice($itemPrice);
		} catch (PrestaShopException $e) {
			// Error handling
			// Tools::displayError($e->getMessage());
		}

		return $response;
	}

	public function generatePaymentMethodDescriptionHtml($total = 0)
	{
		return array(
			'js' => $this->ahojpay->generateInitJavaScriptHtml(),
			'html_description' => $this->ahojpay->generatePaymentMethodDescriptionHtml($total)
		);
	}

	public function getSecurityToken($id_order, $action)
	{
		return Tools::encrypt($action.'_'.$id_order);
	}

	public static function isVritualProduct($id_product = false)
	{
		if(!$id_product)
			return false;

		$sql = 'SELECT is_virtual FROM '._DB_PREFIX_.'product WHERE id_product = '.$id_product;
		$is_virtual =  Db::getInstance()->getValue($sql);
		if($is_virtual)
			return true;
		else
			return false;
	}

	public static function getProductCategories($id_product = false)
	{
		if(!$id_product)
			return array();
		$categories = Product::getProductCategoriesFull($id_product);
		$result = array();
		foreach ($categories as $key => $value) {
			$result[] = $value['name'];
		}

		return $result;
	}

	public static function getCartRules($id_order)
	{
		$sql = 'SELECT ocr.*, cr.code
			    FROM ' . _DB_PREFIX_ . 'order_cart_rule ocr
			    LEFT JOIN '._DB_PREFIX_.'cart_rule cr
			    	ON (cr.id_cart_rule = ocr.id_cart_rule)
			    WHERE ocr.`id_order` = ' . (int) $id_order;
	    return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
	}

	public static function formatOrderCartRulesCodes($order_cart_rules)
	{
		$result = array();
		if($order_cart_rules && count($order_cart_rules) > 0)
		{
			foreach ($order_cart_rules as $key => $value) {
				$result[] = $value['code'];
			}
		}

		return array_filter($result);
	}

	public function	calculateItemPrice($order_detail, $list, $discounts = array())
	{
		$price = $order_detail['unit_price_tax_incl'];

		if($discounts && count($discounts) > 0)
		{
			foreach ($discounts as $key => $value) {

				$reduc = 0;

				if($value['reduction_product'] == -1 && !$this->cheapest_product_used) {

					$cheapest_product = $this->isCheapestProduct($order_detail, $list);
					if(isset($cheapest_product) && $cheapest_product['id_order_detail'] == $order_detail['id_order_detail']) {

						// spracovat zlavu pre tento produkt
						if($value['reduction_percent'] > 0) 
						{
							$reduc =  (( $order_detail['unit_price_tax_incl'] / 100) * $value['reduction_percent'] );
							if($reduc > 0)
								$price -= $reduc;
						}

						if($value['reduction_amount'] > 0) {
							$reduc =  $value['reduction_amount'] / 1;
							if($reduc > 0)
								$price -= $reduc;
						}

						$this->cheapest_product_used = true;
					}
				} else if($value['reduction_product'] == -2) {

					$specified_product = $this->getSpecifiedDiscount($value, $this->order->id_cart, $list);
					if(in_array($order_detail['id_order_detail'], $specified_product)) {

						if($value['reduction_percent'] > 0) {
							
						
							$specified_product_total = $this->getSpecifiedProductsTotalPrice($list, $specified_product);
							$percentage = ($value['value'] / $specified_product_total) * 100;

							$reduc = (( $order_detail['unit_price_tax_incl'] / 100) * $percentage );
							$price -= $reduc;
							
						} else {

							$nbr_products = $this->getSpecifiedProductsNbr($list, $specified_product);
							$reduc = $value['value'] / $nbr_products;
							$price -= $reduc;
						}
					}
				
				} else {

					// obycajna zlava
					$this->order_cart_for_discounts[$value['id_order_cart_rule']] = $value;
				}

				// if($value['id_cart_rule'] == 3 && $order_detail['id_order_detail'] == 27)
				// dd(array(
				// 	$value,
				// 	$reduc
				// ), true);
			}
		}	
		// dd(array(
		// 	$order_detail['unit_price_tax_incl'],
		// 	$price,
		// 	round($order_detail['unit_price_tax_incl'] - $price, 2),
		// ), false);
		return AhojApi::formatPrice($price);
	}

	public function	getOrderCumulativeDiscount()
	{
		$result = 0;
		if($this->order_cart_for_discounts && count($this->order_cart_for_discounts) > 0)
		{
			foreach ($this->order_cart_for_discounts as $key => $value) {
				$result += round($value['value'], 2);
			}
		}

		return $result;
	}

	public function getNonGiftDiscounts($id_order)
	{
		$sql = '
		SELECT ocr.*, cr.reduction_percent, cr.reduction_amount, cr.priority, cr.reduction_product, crprg.quantity AS restritction_quantity, cr.code
		FROM `'._DB_PREFIX_.'order_cart_rule` ocr
		LEFT JOIN '._DB_PREFIX_.'cart_rule cr ON (ocr.id_cart_rule = cr.id_cart_rule)
		LEFT JOIN '._DB_PREFIX_.'cart_rule_product_rule_group crprg ON (crprg.id_cart_rule = cr.id_cart_rule)
		WHERE ocr.`id_order` = '.(int)$id_order.'
		AND cr.gift_product = 0
		ORDER BY ocr.id_order_cart_rule ASC';

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
	}

	public function getSpecifiedDiscount($value, $id_cart, $list)
	{
		// Discount (%) on the selection of products
	    if ($value['reduction_percent'] && $value['reduction_product'] == -2) {

	        $selected_products_reduction = 0;
	        $return = array();

	        $cart_rule = new CartRuleOverride($value['id_cart_rule']);
	        $context = Context::getContext();
	        $context->cart = new Cart($id_cart);

	        $selected_products = $cart_rule->checkProductRestrictionsOverride($context, true);

	        if (is_array($selected_products)) {
	            foreach ($list as $product) {
	                if (in_array($product['product_id'].'-'.$product['product_attribute_id'], $selected_products)
	                    || in_array($product['product_id'].'-0', $selected_products)) {
						
						$return[] = $product['id_order_detail'];	                   
	                }
	            }
	        }

	        return $return;
	    }
	}

	public function getSpecifiedProductsNbr($list, $specified_products)
	{
		$nbr = 0;
		foreach ($list as $key => $value) {
			if(isset($specified_products) && count($specified_products) > 0) {

				if(in_array($value['id_order_detail'], $specified_products)) {
					$nbr = $nbr + $value['product_quantity'];

				}
			} else {
				$nbr = $nbr + $value['product_quantity'];
			}
		}

		return $nbr;
	}

	public function isCheapestProduct($order_detail, $list)
	{
		$cheapest_product = false;
		$min_price = false;
		foreach ($list as $key => $value) {
			if(!$min_price || $value['unit_price_tax_incl'] <= $min_price)
			{
				$min_price = $value['unit_price_tax_incl'];
				$cheapest_product = $value; 
			}
		}

		return $cheapest_product;
	}

	public function getSpecifiedProductsTotalPrice($list, $specified_products)
	{
		$total = 0;
		foreach ($list as $key => $value) {
			if(isset($specified_products) && count($specified_products) > 0) {

				if(in_array($value['id_order_detail'], $specified_products)) {
					$total = $total + $value['total_price_tax_incl'];

				}
			} else {
				$total = $total + $value['total_price_tax_incl'];
			}
		}

		return $total;
	}

	public function getNbrOrderedProducts($list)
	{
		$nbr = 0;
		foreach ($list as $key => $value) {
			$nbr = $nbr + $value['product_quantity'];
		}

		return $nbr;
	}

	public static function formatPrice($price)
	{
		$price = round($price, 2);
		return number_format((float)$price, 2, '.', '');  // Outputs -> 105.00
	}
}
