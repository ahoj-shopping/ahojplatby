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
			$mode = 'test';
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

	public function createApplication()
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
		return array(
			// 'goodsDeliveryTypeText' => 'local_pickup',
			// 'goodsDeliveryAddress' => externa balikova sluzba, dodacia adresa,
			'goods'	=>	$this->getList(),
			'goodsDeliveryCosts' => AhojApi::formatPrice($this->order->total_shipping_tax_incl)
		);
	}

	public function getList()
	{
		$list = $this->order->getOrderDetailList();
		$data = array();

		if(count($list) > 0)
		{
			foreach ($list as $key => $value) {
				$data[] = array(
					'name' => $value['product_name'],
					'price' => AhojApi::formatPrice($value['unit_price_tax_incl']),
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
				
				$order_cart_rules = AhojApi::getCartRules($this->order->id);
				$cart_rules_codes = AhojApi::formatOrderCartRulesCodes($order_cart_rules);

				$data[] = array(
					'name' => $this->module->l('Zľavový kupón'),
					'price' => -1 * AhojApi::formatPrice($this->order->total_discounts),
					'id' => 'discount',
					'count' => 1,
					'typeText'	=> 'discount',
					'codeText'	=> $cart_rules_codes,
					'nonMaterial' => true
				);
			}
		}
		
		return $data;
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
			    WHERE ocr.`deleted` = 0 
			    	AND ocr.`id_order` = ' . (int) $id_order;
	    return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
	}

	public static function formatOrderCartRulesCodes($order_cart_rules)
	{
		$result = array();
		if($order_cart_rules)
		{
			foreach ($order_cart_rules as $key => $value) {
				$result[] = $value['code'];
			}
		}

		return array_filter($result);
	}

	public static function formatPrice($price)
	{
		$price = round($price, 2);
		return number_format((float)$price, 2, '.', '');  // Outputs -> 105.00
	}
}
