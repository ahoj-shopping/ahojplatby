<?php

class AhojplatbyPaymentModuleFrontController extends ParentController
{
	/** @var bool If false, does not build left page column content and hides it. */
	public $display_column_left = false;

	/** @var bool If false, does not build right page column content and hides it. */
	public $display_column_right = false;

	public function initContent()
	{
		$debug = Configuration::get('AHOJPLATBY_MODULE_DEBUG');
		$auto_redirect = Configuration::get('AHOJPLATBY_AUTOMATICALLY_REDIRECT');

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
		$this->module->validateOrder(
			$cart->id, 
			Configuration::get('AHOJPLATBY_ORDER_STATE_AWAITING'), 
			$total, 
			$this->module->displayName, 
			NULL, 
			$mailVars, 
			(int)$this->context->currency->id, 
			false, 
			$customer->secure_key
		);

		PrestaShopLogger::addLog(
			'Payment: add order '.$this->module->currentOrder,
			1,
			null,
			$this->module->name,
			$this->module->currentOrder,
			true
		);

		// api 
		$this->module->api->init();
		$this->module->api->setOrder(new Order(8)); // test order
		$this->module->api->setOrder(new Order($this->module->currentOrder)); // test order
		$response = $this->module->api->createApplication();

		// smarty
		$this->context->smarty->assign(array(
			'js_ahojpay_init'	=> $this->module->api->getInitJavascriptHtml(),
		    'debug' => $debug,
		    'response'	=>	$response,
		    'data'	=>	$this->module->api->debug_data // debug_data
		));
		
		// js var defines
		Media::addJsDef(array(
			'applicationUrl'	=>	$response['applicationUrl'],
			'test'	=>	'test'
		));

		// render
		$this->setRenderTemplate('front', 'payment.tpl');

	}
	public function setMedia()
	{
		parent::setMedia();

		if($this->module->is17)
		{
			$this->registerJavascript(
				'module-'.$this->module->name.'-payment',
				'modules/'.$this->module->name.'/views/js/ahojplatby.js'
			);
		}
		else
		{
			$this->addJs(_PS_MODULE_DIR_.''.$this->module->name.'/views/js/ahojplatby.js');
		}
		
	}
	
}
