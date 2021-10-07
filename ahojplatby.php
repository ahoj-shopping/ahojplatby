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

	CONST PAYMENT_NAME_PREFIX = 'Ahoj - ';

	public function __construct()
	{
		$this->name = 'ahojplatby';
		$this->tab = 'payments_gateways';
		$this->version = '1.3.1';
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
		// $this->context->controller->addJS($this->_path.'views/js/ahojplatby.js');
        // $this->context->controller->addJS($this->_path.'js/nivo-slider/jquery.nivo.slider.js');
	}

	public function hookPaymentOptions()
	{
		if (!$this->active) {
			return;
		}
		
		$this->api->init();
		$total = (float)$this->context->cart->getOrderTotal(true);
		$payment_methods = $this->api->getPaymentMethods($total);

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
		$total = (float)$this->context->cart->getOrderTotal(true);
		$payment_methods = $this->api->getPaymentMethods($total);
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
		// if (!$this->active || !Configuration::get(self::FLAG_DISPLAY_PAYMENT_INVITE)) {
		// 	return;
		// }

		// $state = $params['order']->getCurrentState();
		// if (
		// 	in_array(
		// 		$state,
		// 		array(
		// 			Configuration::get('PS_OS_BANKWIRE'),
		// 			Configuration::get('PS_OS_OUTOFSTOCK'),
		// 			Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'),
		// 		)
		// 	)) {
		// 	$bankwireOwner = $this->owner;
		// 	if (!$bankwireOwner) {
		// 		$bankwireOwner = '___________';
		// 	}

		// 	$bankwireDetails = Tools::nl2br($this->details);
		// 	if (!$bankwireDetails) {
		// 		$bankwireDetails = '___________';
		// 	}

		// 	$bankwireAddress = Tools::nl2br($this->address);
		// 	if (!$bankwireAddress) {
		// 		$bankwireAddress = '___________';
		// 	}

		// 	$totalToPaid = $params['order']->getOrdersTotalPaid() - $params['order']->getTotalPaid();
		// 	$this->smarty->assign(array(
		// 		'shop_name' => $this->context->shop->name,
		// 		'total' => Tools::displayPrice(
		// 			$totalToPaid,
		// 			new Currency($params['order']->id_currency),
		// 			false
		// 		),
		// 		'bankwireDetails' => $bankwireDetails,
		// 		'bankwireAddress' => $bankwireAddress,
		// 		'bankwireOwner' => $bankwireOwner,
		// 		'status' => 'ok',
		// 		'reference' => $params['order']->reference,
		// 		'contact_url' => $this->context->link->getPageLink('contact', true)
		// 	));
		// } else {
		// 	$this->smarty->assign(
		// 		array(
		// 			'status' => 'failed',
		// 			'contact_url' => $this->context->link->getPageLink('contact', true),
		// 		)
		// 	);
		// }

		$this->smarty->assign(
			array(
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

	public function hookDisplayProductExtraContent($params)
	{
		$array = array();
		$array[] = (new PrestaShop\PrestaShop\Core\Product\ProductExtraContent())
			->setTitle('tittle')
			->setContent('content');
		return $array;

		return 'ahojplatby hook product extra content';
	}

	public function hookDisplayProductAdditionalInfo($params)
	{
		if($this->is17)
		{
			$price = $params['product']->rounded_display_price;
			// Task 4343
			if(!$price)
				$price = $params['product']->price_amount;
		}
		else
		{	
			$product = new Product(Tools::getValue('id_product'));
			$price = AhojApi::formatPrice($product->getPrice());
		}

		$this->api->init();
		$is_available = $this->api->isAvailableForItemPrice($price);
		if(!$is_available)
		{
			return false;
		}

		$banner_data = $this->api->getProductData($price);

		$this->smarty->assign(array(
			'banner_ajax_url'	=>	$this->context->link->getModuleLink('ahojplatby', 'banner'),
			'banner_data' => $banner_data
		));

		return $this->render('hook', 'product.tpl');
	}


	public function hookDisplayRightColumnProduct($params)
	{
		return $this->hookDisplayProductAdditionalInfo($params);
	}

	public function render($type = 'front', $template = 'file.tpl', $same_file = false)
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
			return $this->display(__FILE__, 'views/templates/'.$type.$ver.$template);
		}
		else
		{
			return $this->display(__FILE__, 'views/templates/'.$type.$ver.$template);
		}
	}
	
}
