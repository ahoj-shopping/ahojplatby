<?php

class AhojplatbyBannerModuleFrontController extends ParentController
{
	public function initContent()
	{	
		$debug = Configuration::get('AHOJPLATBY_MODULE_DEBUG');
		$auto_redirect = Configuration::get('AHOJPLATBY_AUTOMATICALLY_REDIRECT');

		parent::initContent();

		$price = Tools::getValue('price');
		$price = round($price, 6);

		// init api 
		$this->module->api->init();
		$calculations = $this->module->api->getCalculations((float)$price);
		
		if(isset($calculations) && $calculations)
		{
			die(Tools::jsonEncode(array(
				'calculations'	=>	$calculations,
				'error'		=>	false
			)));
		}
		else
		{
			die(Tools::jsonEncode(array(
				'calculations'	=>	array(),
				'error'		=>	true
			)));
		}
	}
}
