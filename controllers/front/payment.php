<?php

class AhojplatbyPaymentModuleFrontController extends ParentController
{
	public function initContent()
	{
		$debug = Configuration::get('AHOJPLATBY_MODULE_DEBUG');

		parent::initContent();

		$cart = $this->context->cart;
		// add order
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$mailVars = array(
			'{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
			'{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
			'{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
		);
		// $this->module->validateOrder(
		// 	$cart->id, 
		// 	Configuration::get('AHOJPLATBY_ORDER_STATE_AWAITING'), 
		// 	$total, $this->module->displayName, 
		// 	NULL, 
		// 	$mailVars, 
		// 	(int)$currency->id, 
		// 	false, 
		// 	$customer->secure_key
		// );
		// Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

		PrestaShopLogger::addLog(
			'Payment: add order: '.$this->module->currentOrder,
			1,
			null,
			$this->module->name,
			$this->module->currentOrder,
			true
		);

		$this->module->api->init();
		$this->module->api->setOrder(new Order(5)); // test order
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
