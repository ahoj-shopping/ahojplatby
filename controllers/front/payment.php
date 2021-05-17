<?php

include_once(dirname(__FILE__) . '/../../autoload.php');

class AhojplatbyPaymentModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		parent::initContent();

		$cart = $this->context->cart;

		// add order
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$mailVars = array(
			'{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
			'{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
			'{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
		);

		// $this->module->validateOrder(
		// 	$cart->id, 
		// 	Configuration::get('AHOJPLATBY_ORDER_STATE_AWAITING'), 
		// 	$total, $this->module->displayName, 
		// 	NULL, 
		// 	$mailVars, 
		// 	(int)$currency->id, 
		// 	false, 
		// 	$customer->secure_key
		// );
		// Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

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

		// redirect to payment
		try {
		    $ahojPay = new Ahoj\AhojPay(array(
		        "mode" => "test",
		        "businessPlace" => "TEST_ESHOP",
		        "eshopKey" => "1111111111aaaaaaaaaa2222",
		        "notificationCallbackUrl" => $this->module->callback_url,
		    ));
		} catch (Exception $e) {
		    // proper error handling
		    dd(array(
		    	$cart->id,
		    	$cart->getOrderTotal(),
		    	$e->getMessage()
		    ), true);
		}

		dd(array(
			$ahojpay
		), true);

        $this->setRenderTemplate('front', 'payment.tpl');

	}

	public function setRenderTemplate($type = 'front', $template = 'file.tpl')
	{
		if($this->is17)
		{
        	$this->setRenderTemplate('module:ahojplatby/views/templates/'.$type.'/1_7/'.$template);
		}
		else
		{
			return $this->setTemplate('views/templates/'.$front.'/1_6/'.$template);
		}
	}

	
}
