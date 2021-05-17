<?php

include_once(dirname(__FILE__) . '/../../autoload.php');

class AhojplatbyValidationModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		parent::initContent();
		
		$this->context->smarty->assign(array(
		    // 'PAY24_ACTION' => $action_url,
		    // 'PAY24_MID' => $order->mid,
		    // 'PAY24_ESHOPID' => $order->eshopId,
		    // 'PAY24_MSTXNID' => $order->msTxnId,
		    // 'PAY24_AMOUNT' => $order->amount,
		    // 'PAY24_CURRALPHACODE' => $order->currAlphaCode,
		    // 'PAY24_LANGUAGE' => $order->language,
		    // 'PAY24_CLIENTID' => $order->clientId,
		    // 'PAY24_FIRSTNAME' => $order->firstName,
		    // 'PAY24_FAMILYNAME' => $order->familyName,
		    // 'PAY24_EMAIL' => $order->email,
		    // 'PAY24_COUNTRY' => $order->country,
		    // 'PAY24_NURL' => $order->nurl,
		    // 'PAY24_RURL' => $order->rurl,
		    // 'PAY24_TIMESTAMP' => $order->timestamp,
		    // 'PAY24_SIGN' => $order->sign,
		    'AHOJPLATBY_MODULE_DEBUG' => Configuration::get('AHOJPLATBY_MODULE_DEBUG'),
		));

        $this->setTemplate('module:ahojplatby/views/templates/front/1_7/validation.tpl');

	}

	
}
