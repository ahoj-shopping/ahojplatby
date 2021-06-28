<?php

class AhojplatbyTestModuleFrontController extends ParentController
{
	/** @var bool If false, does not build left page column content and hides it. */
	public $display_column_left = false;

	/** @var bool If false, does not build right page column content and hides it. */
	public $display_column_right = false;

	public function initContent()
	{
		$debug = Configuration::get('AHOJPLATBY_MODULE_DEBUG');
		$auto_redirect = Configuration::get('AHOJPLATBY_AUTOMATICALLY_REDIRECT');
		$id_order = Tools::getValue('id_order');
		$order = New Order($id_order);
		$cart = new Cart($order->id_cart);

		parent::initContent();

		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		// add order
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

		// api 
		$this->module->api->init();
		$this->module->api->setOrder(new Order($id_order)); // test order
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
