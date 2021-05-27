<?php

class AhojplatbyFailModuleFrontController extends ParentController
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

		$id_order = Tools::getValue('id_order');
		$order = new Order($id_order);
		$cart = new Cart($order->id_cart);
		$customer = new Customer($cart->id_customer);

		PrestaShopLogger::addLog(
			'Fail: ahoj form fail '.$order->id,
			1,
			null,
			$this->module->name,
			$order->id,
			true
		);

		$this->context->smarty->assign(array(
		    'debug' => $debug,
		    'order'	=>	$order,
		    'cart'	=>	$cart,
		    'customer'	=>	$customer
		));
		
		$this->setRenderTemplate('front', 'fail.tpl');

	}
	
}
