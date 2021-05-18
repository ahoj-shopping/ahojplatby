<?php

include_once(dirname(__FILE__) . '/../../autoload.php');

class AhojplatbyNotificationModuleFrontController extends ParentController
{
	public function initContent()
	{
		parent::initContent();
		
		$this->context->smarty->assign(array(
		    'AHOJPLATBY_MODULE_DEBUG' => Configuration::get('AHOJPLATBY_MODULE_DEBUG'),
		));

        $this->setRenderTemplate('front', 'notification.tpl');
	}

	
}
