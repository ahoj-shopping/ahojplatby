<?php

class AhojplatbyPaymentModuleFrontController extends ParentController
{
	public function initContent()
	{
		$debug = Configuration::get('AHOJPLATBY_MODULE_DEBUG');

		parent::initContent();

		$cart = $this->context->cart;
		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		// add order
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$mailVars = array(
			// '{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
		);
		
		// $this->module->validateOrder(
		// 	$cart->id, 
		// 	Configuration::get('AHOJPLATBY_ORDER_STATE_AWAITING'), 
		// 	$total, $this->module->displayName, 
		// 	NULL, 
		// 	$mailVars, 
		// 	(int)$this->context->currency->id, 
		// 	false, 
		// 	$customer->secure_key
		// );

		PrestaShopLogger::addLog(
			'Payment: add order '.$this->module->currentOrder,
			1,
			null,
			$this->module->name,
			$this->module->currentOrder,
			true
		);

		$this->module->api->init();
		$this->module->api->setOrder(new Order(6)); // test order
		// $this->module->api->setOrder(new Order($this->module->currentOrder)); // test order
		$response = $this->module->api->createApplication();

		if(!$debug)
		{
			Tools::redirect($response['applicationUrl']);
			return;
		}

		$this->context->smarty->assign(array(
		    'debug' => $debug,
		    'response'	=>	$response,
		    'data'	=>	$this->module->api->debug_data // debug_data
		));

		$this->setRenderTemplate('front', 'payment.tpl');

	}
	
}
