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

	public function __construct()
	{
		$this->name = 'ahojplatby';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.1';
		$this->author = 'bestuniverse.sk';
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

		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

		if(version_compare(_PS_VERSION_, '1.7', '>='))
		    $this->is17 = true;

		// $this->callback_url = $this->context->link->getModuleLink('ahojplatby', 'validation');

		$this->api = new AhojApi();
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
		// $this->context->controller->addCSS($this->_path.'views/css/buconnector.css');
		// $this->context->controller->addJS($this->_path.'views/js/buconnector.js');
        // $this->context->controller->addJS($this->_path.'js/nivo-slider/jquery.nivo.slider.js');
	}

	public function hookPaymentOptions()
	{
		if (!$this->active) {
			return;
		}

		// $this->smarty->assign(array(
		// ));

		$newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
		$newOption->setModuleName($this->name)
			->setCallToActionText($this->l('Ahoj platby'))
			->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
			->setAdditionalInformation($this->l('Ahoj platby addintional information'));
			// ->setAdditionalInformation($this->fetch('module:ps_wirepayment/views/templates/hook/ps_wirepayment_intro.tpl'));

		$payment_options = [
			$newOption,
		];

		return $payment_options;
	}

	public function hookPayment($params) {

		if (!$this->active)
			return;

		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_cheque' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));

		return $this->render('hook', 'payment.tpl');
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
		}
		else
		{
			$price = $params['product']->price;
		}

		$this->api->init();
		$banner_data = $this->api->getProductData($price);

		$this->smarty->assign(array(
			'banner_data' => $banner_data
		));

		return $this->render('hook', 'product.tpl', true);
	}

	public function render($type = 'front', $template = 'file.tpl', $same_file = false)
	{
		if($same_file)
		{
			$ver = '/';
		}
		else
		{
			$ver = '/1_7/';
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
