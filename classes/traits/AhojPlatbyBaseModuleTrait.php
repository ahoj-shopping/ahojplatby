<?php

/**
 * Base prestashop module functions
 */
trait AhojPlatbyBaseModuleTrait
{

	// /**
	// * Add the CSS & JavaScript files you want to be loaded in the BO.
	// */
	// public function hookBackOfficeHeader()
	// {
	// 	if (Tools::getValue('module_name') == $this->name || Tools::getValue('controller') == 'AdminOrders') {
	// 		$this->context->controller->addJS($this->_path.'views/js/back.js');
	// 		$this->context->controller->addCSS($this->_path.'views/css/back.css');
	// 	}
	// }

	// /**
	//  * Add the CSS & JavaScript files you want to be added on the FO.
	//  */
	// public function hookHeader($params)
	// {
	// 	$this->context->controller->addJS(($this->_path).'views/js/front/mallpartner.js');
	// 	$this->context->controller->addCSS(($this->_path).'views/css/front/mallpartner.css', 'all');
	// }

	/**
	 * Don't forget to create update methods if needed:
	 * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
	 */
	public function install()
	{

		if (extension_loaded('curl') == false)
		{
			$this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
			return false;
		}

		Configuration::updateValue('BUCONNECTOR_LIVE_MODE', false);
		Configuration::updateValue('BUCONNECTOR_RETURN_DAY_OFFSET', 14);

		// Configuration::updateValue('BUCONNECTOR_TOKEN', "");

		require(dirname(__FILE__).'/../../sql/install.php');

		return parent::install() 
		&& $this->installHook()
		// && $this->installTab()
		/*&& AsdataGeisTabs::installTab($this)*/;
	}

	public function uninstall()
	{
		Configuration::deleteByName('BUCONNECTOR_LIVE_MODE');
		// Configuration::deleteByName('BUCONNECTOR_TOKEN');

		include(dirname(__FILE__).'/../../sql/uninstall.php');

		return parent::uninstall();
	}

	public function installHook()
	{
		// $this->registerHook('moduleRoutes');
		$this->registerHook('payment');
		$this->registerHook('paymentReturn');
		$this->registerHook('paymentOptions');
		// $this->registerHook('displayProductExtraContent');
		$this->registerHook('displayProductAdditionalInfo');

		return true;
	}

	public function installTab()
	{
		return;
	    $id_parent = 0; // main tab
	    // $id_parent = $this->getTabParent();
	    $id_parent = $this->installSubTab($id_parent, $this->name, 'PickupinstoreOrder', $this->displayName);
	    //subitems install
	    $this->installSubTab($id_parent, $this->name, 'PickupinstoreOrders', 'Pick-up in store orders');
	    $this->installSubTab($id_parent, $this->name, 'PickupinstoreApi', 'Pick-up in store api');
	    // $this->installSubTab($id_parent, $this->name, 'AdminPickupinstoreReplaces', 'Replaces');
	    // $this->installSubTab($id_parent, $this->name, 'AdminPickupinstoreClaims', 'Claims');
	    // $this->installSubTab($id_parent, 'AdminMallpartnerOrders', 'Orders');

	    return true;
	}

	public function getTabParent()
	{
		$sql = 'SELECT id_tab FROM '._DB_PREFIX_.'tab WHERE class_name = "AdminParentOrders"';
		$id_parent = Db::getInstance()->getValue($sql);
		return $id_parent;
	}

	public function getTabPosition($id_parent)
	{
		$sql = 'SELECT MAX(position) as position FROM '._DB_PREFIX_.'tab WHERE id_parent = '.(int)$id_parent;
		$position = Db::getInstance()->getValue($sql);
		return $position + 1;
	}

	public function installSubTab($id_parent, $module, $class_name, $tab_name)
	{
		$tab = new Tab();
		$tab->class_name = $class_name;
		$tab->module = $module;
		$tab->id_parent = $id_parent;
		foreach (Language::getLanguages(false) as $lang) {
			$tab->name[(int) $lang['id_lang']] = $tab_name;
		}
		if (!$tab->save()) {
			dd(array(
				'unable save'
			), false);
			return $this->_abortInstall($this->trans('Unable to create the "'.$class_name.'" tab', array(), 'Modules.Autoupgrade.Admin'));
		}

		return $tab->id;
	}

	
	/**
	 * Set installation errors and return false.
	 *
	 * @param string $error Installation abortion reason
	 *
	 * @return bool Always false
	 */
	protected function _abortInstall($error)
	{
		$this->_errors[] = $error;

		return false;
	}


}
