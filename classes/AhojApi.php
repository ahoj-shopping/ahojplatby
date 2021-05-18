<?php

include_once(dirname(__FILE__) . '/../autoload.php');

class AhojApi
{
	public $callback_url;
	public $success_url;
	public $fail_url;

	public $context;
	public $order;
	public $customer;
	public $ahojpay;

	public $debug;
	public $debug_data;

	const SUCCESS = 'success';
	const FAIL = 'fail';

	function __construct()
	{
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
		$mode = 'prod';
		if($this->debug)
		{
			$mode = 'test';
		}

		try {
		     $this->ahojpay = new Ahoj\AhojPay(array(
		         "mode" => $mode,
		         "businessPlace" => Configuration::get('AHOJPLATBY_BUSINESS_PLACE'),
		         "eshopKey" => Configuration::get('AHOJPLATBY_API_KEY'),
		         "notificationCallbackUrl" => $this->callback_url,
		     ));
		} catch (PrestaShopException $e) {
	  		// Error handling
			$this->displayError($e->getMessage());
		}
	
	}

	public function getProductData($price = 0)
	{
		return array(
			$this->ahojpay->generateInitJavaScriptHtml(),
			$this->ahojpay->generateProductBannerHtml($price)
		);
	}

	public function setOrder($order)
	{
		$this->order = $order;
		$this->customer = new Customer($order->id_customer);
	}

	public function createApplication()
	{
		if(!$this->order)
			$this->displayError('Order not set');

		$response = array();

		$customer = new Customer($this->order->id_customer);

		$data = array(
			'orderNumber' => $this->order->id,
			'completionUrl' => $this->success_url = $this->context->link->getModuleLink('ahojplatby', 'validation', array(
				'action'	=>	self::SUCCESS,
				'id_order'	=>	$this->order->id,
			)),
			'terminationUrl' => $this->fail_url = $this->context->link->getModuleLink('ahojplatby', 'validation', array(
				'action'	=>	self::FAIL,
				'id_order'	=>	$this->order->id,
			)),
			'eshopRegisteredCustomer' => $this->customer->isGuest(),
			'customer'	=>	$this->getCustomerData(),
			'product'	=>	$this->getProductsData()
		);



		try {
		     $response = $this->ahojpay->createApplication($data);
		} catch (PrestaShopException $e) {
			// Error handling
			// $this->displayError($e->getMessage());
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

	public function getProductsData()
	{
		
		return array(
			'goods'	=>	$this->getList(),
			'goodsDeliveryCosts' => round($this->order->total_shipping_tax_incl, 2)
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
					'price' => round($value['product_price'], 2),
					'id' => $value['product_id'].'_'.$value['product_attribute_id'],
					'count' => $value['product_quantity'],
					// 'additionalServices' => array(
					// 	array(
					// 		'id' => '9876543210',
					// 		'name' => 'Poistenie',
					// 		'price' => 9.99
					// 	)
					// )
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
	// 		$this->displayError($e->getMessage());
	// 	}
	// }

	public function displayError($error_msg = '')
	{
		return;
		throw new PrestaShopException($error_msg);
	}
}
