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

		// init api 
		$this->module->api->init();
		$payment_methods = $this->module->api->getPaymentMethods($total);
		$promotionCode = Tools::getValue('promotioncode');
		$payment_method_name = 'AhojPlatby undefined payment method';
		$validate_promotion_code = false;
		if(count($payment_methods) && $promotionCode)
		{
			foreach ($payment_methods as $payment_method) {
				if($payment_method['promotionCode'] == $promotionCode)
				{
					$payment_method_name = Ahojplatby::PAYMENT_NAME_PREFIX.$payment_method['name'];
					$validate_promotion_code = true;
				}
			}
		}

		if(!$validate_promotion_code)
		{
			PrestaShopLogger::addLog(
				'Payment: validate promotionCode failed id_cart: '.$cart->id,
				4,
				null,
				$this->module->name,
				$cart->id,
				true
			);
			die($this->l('promotion kod nie je validny'));
		}

		$this->module->validateOrder(
			$cart->id, 
			Configuration::get('AHOJPLATBY_ORDER_STATE_AWAITING'), 
			$total, 
			$payment_method_name, 
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
		
		// $this->module->api->setOrder(new Order(16)); // test order
		$this->module->api->setOrder(new Order($this->module->currentOrder));
		$response = $this->module->api->createApplication($promotionCode);

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
