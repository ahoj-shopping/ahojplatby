<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
	exit;
}

include_once(dirname(__FILE__) . '/autoload.php');

class ahojplatby extends PaymentModule
{

	use AhojPlatbyConfigModuleTrait;
	use AhojPlatbyBaseModuleTrait;

	public $api;
	public $callback_url;
	public $is17 = false;

	CONST PAYMENT_NAME_PREFIX = '';

	public function __construct()
	{
		$this->name = 'ahojplatby';
		$this->tab = 'payments_gateways';
		$this->version = '1.4.3';
		$this->author = 'Ahoj, a.s.';
		$this->need_instance = 1;

		/**
		 * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
		 */
		$this->bootstrap = true;

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		parent::__construct();

		$this->displayName = $this->l('Ahoj platby');
		$this->description = $this->l('');

		$this->confirmUninstall = $this->l('');

		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

		if(version_compare(_PS_VERSION_, '1.7', '>='))
		    $this->is17 = true;

		// $this->callback_url = $this->context->link->getModuleLink('ahojplatby', 'validation');

		$this->api = new AhojApi($this);
	}

	/**
	* Add the CSS & JavaScript files you want to be loaded in the BO.
	*/
	public function hookBackOfficeHeader()
	{
		// $this->context->controller->addJS($this->_path.'views/js/back.js');
		// $this->context->controller->addJS($this->_path.'views/js/download.js');

		if (Tools::getValue('module_name') == $this->name) {
			$this->context->controller->addCSS($this->_path.'views/css/back.css');
		}
	}

	public function hookDisplayHeader()
	{ 
		$this->context->controller->addCSS($this->_path.'views/css/ahojplatby.css');
		if(!$this->is17)
		{
			if ( in_array($this->context->controller->php_self, array('product')) ) 
			{
				$this->context->controller->addJS($this->_path.'views/js/ahojplatby_1_6.js');
			}

			if ( in_array($this->context->controller->php_self, array('order-opc')) ) 
			{
				$this->api->init();
				$this->context->controller->addJS($this->api->getJsScriptUrl(), false);
			}
		}
        // $this->context->controller->addJS($this->_path.'js/nivo-slider/jquery.nivo.slider.js');
	}

	public function hookPaymentOptions()
	{
		if (!$this->active) {
			return;
		}
		
		$payment_options = array();
		$this->api->init();
		$total_products = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
		$total = (float)$this->context->cart->getOrderTotal(true);
		$payment_methods = $this->api->getPaymentMethods($total_products);

		if(count($payment_methods) > 0)
		{
			foreach ($payment_methods as $key => $payment_method) {

				// pokial nie je dostupna platba preskocit
				if(!$payment_method['isAvailable']) continue;

				$description = $this->api->generatePaymentMethodDescriptionHtml(
					$total, 
					$payment_method['promotionCode'], 
					$payment_method['promotionCode']
				);
				
				$this->smarty->assign(array(
					'description' => $description,
					'add_class'	=>	$payment_method['promotionCode'],
					'ahoj_logo_url'	=>	Tools::getHttpHost(true).__PS_BASE_URI__.'modules/ahojplatby/img/ahoj-logo-1x.png'
				));

				$payment_option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
				$payment_option->setModuleName($this->name)
					->setCallToActionText(Ahojplatby::PAYMENT_NAME_PREFIX.$payment_method['name'])
					->setAction(
						$this->context->link->getModuleLink($this->name, 'payment', 
							array(
								'promotioncode' => $payment_method['promotionCode']
							)
						, true))
					->setAdditionalInformation($this->render('hook', 'payment.tpl'));
				$payment_options[] = $payment_option;
			}
		}
		
		return $payment_options;
	}

	public function hookPayment($params) {

		if (!$this->active)
			return;

		$this->api->init();
		$total_products = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
		$total = (float)$this->context->cart->getOrderTotal(true);
		$payment_methods = $this->api->getPaymentMethods($total_products);
		$available_payment_methods = array();

		if(count($payment_methods) > 0)
		{
			foreach ($payment_methods as $key => $payment_method) {

				// pokial nie je dostupna platba preskocit
				if(!$payment_method['isAvailable']) continue;

				$description = $this->api->generatePaymentMethodDescriptionHtml(
					$total, 
					$payment_method['promotionCode'], 
					$payment_method['promotionCode']
				);

				$available_payment_methods[] = array(
					'name'			=>	Ahojplatby::PAYMENT_NAME_PREFIX.$payment_method['name'],
					'description'	=>	$description,
					'promotionCode'	=>	$payment_method['promotionCode'],
					'action'		=>	$this->context->link->getModuleLink($this->name, 'payment', 
											array(
												'promotioncode' => $payment_method['promotionCode']
											)
										, true)
				);
			}
		}

		$this->smarty->assign(array(
			'available_payment_methods' => $available_payment_methods,
			'ahoj_logo_url'	=>	Tools::getHttpHost(true).__PS_BASE_URI__.'modules/ahojplatby/img/ahoj-logo-1x.png',
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));

		return $this->render('hook', 'payment.tpl');
	}

	public function hookDisplayPaymentReturn($params)
	{
		if($this->is17)
		{
			$payment = $params['order']->payment;
		}
		else
		{
			$payment = $params['objOrder']->payment;
		}

		$this->smarty->assign(
			array(
				'payment'	=>	$payment,
				'status' => Tools::getValue('status'),
				'contact_url' => $this->context->link->getPageLink('contact', true),
			)
		);

		return $this->render('hook', 'payment_return.tpl');
	}

	public function hookDisplayOrderConfirmation($params)
	{
		// return 'test hook orderConfirmation';
	}

	public function hookDisplayProductAdditionalInfo($params)
	{
		if($this->is17)
		{
			$id_product = $params['product']->id;
			$price = $params['product']->rounded_display_price;
			// Task #4343
			if(!$price)
				$price = $params['product']->price_amount;
		}
		else
		{	
			$product = new Product(Tools::getValue('id_product'));
			$id_product = $product->id;
			$price = AhojApi::formatPrice($product->getPrice());
		}

		$banner_data = false;

		$cacheId = $this->getCacheIdCustom($id_product, $price);
		if (!$this->isCached($this->getTemplateString('hook', 'product.tpl'), $cacheId)) {

			$this->api->init();
			$is_available = $this->api->isAvailableForItemPrice($price);
			if($is_available)
			{
				$banner_data = $this->api->getProductData($price, true);
			}

			$this->smarty->assign(array(
				'banner_ajax_url'	=>	$this->context->link->getModuleLink('ahojplatby', 'banner'),
				'js'	=>	$this->api->getInitJavascriptHtml(),
				'banner_data' => $banner_data
			));
		}
		
		return $this->render('hook', 'product.tpl', false, $cacheId);
	}

	public function hookDisplayRightColumnProduct($params)
	{
		return $this->hookDisplayProductAdditionalInfo($params);
	}

	public function getTemplateString($type = 'front', $template = 'file.tpl')
	{
		if($this->is17)
			$ver = '/1_7/';
		else
			$ver = '/1_6/';

		return 'views/templates/'.$type.$ver.$template;
	}

	public function render($type = 'front', $template = 'file.tpl', $same_file = false, $cacheId = false)
	{
		if($same_file)
		{
			$ver = '/';
		}
		else
		{
			if($this->is17)
				$ver = '/1_7/';
			else
				$ver = '/1_6/';
		}

		if($this->is17)
		{
			// return $this->fetch($this->getTemplateString($type, $template), $cacheId);
			if($cacheId)
				return $this->display(__FILE__, 'views/templates/'.$type.$ver.$template, $cacheId);
			else
				return $this->display(__FILE__, 'views/templates/'.$type.$ver.$template);

		}
		else
		{
			if($cacheId)
				return $this->display(__FILE__, 'views/templates/'.$type.$ver.$template, $cacheId);
			else
				return $this->display(__FILE__, 'views/templates/'.$type.$ver.$template);

		}
	}
	
	public function getCacheIdCustom($id_product, $price)
	{
		$price = md5($price);
	    return parent::getCacheId() . '|' . (string) $id_product . '|' . (string)$price;
	}
}
