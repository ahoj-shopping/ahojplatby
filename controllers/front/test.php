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

		// api 
		$this->module->api->init();
		$response = $this->module->api->generatePaymentMethodDescriptionHtml(35);

		// smarty
		$this->context->smarty->assign(array(
		    'debug' => $debug,
		    'response'	=>	$response,
		    'data'	=>	$this->module->api->debug_data // debug_data
		));
		

		// render
		$this->setRenderTemplate('front', 'test.tpl', true);

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
