<?php

class AhojplatbyValidationModuleFrontController extends ParentController
{
	public function initContent()
	{
		parent::initContent();
		$debug = Configuration::get('AHOJPLATBY_MODULE_DEBUG');

		$id_order = Tools::getValue('id_order');
		$action = Tools::getValue('action');
		$token = Tools::getValue('token');
		
		if($token != $this->module->api->getSecurityToken($id_order, $action))
		{
			PrestaShopLogger::addLog(
				'Validation: bad token '.$id_order.' | '.$action.' | '.$token,
				4,
				null,
				$this->module->name,
				$this->module->currentOrder,
				true
			);

			die($this->l('Bad security token'));
		}

		$this->updateOrderState($id_order, $action);

		die();
		// $this->context->smarty->assign(array(
		//     'AHOJPLATBY_MODULE_DEBUG' => $debug,
		// ));

		// $this->setRenderTemplate('front', 'validation.tpl');

	}

	public function updateOrderState($id_order, $action)
	{
		if(!$id_order)
		{
			PrestaShopLogger::addLog(
				'Validation: id_order not defined',
				4,
				null,
				$this->module->name,
				0,
				true
			);
			die($this->l('id order not defined'));

		}

		$order = new Order($id_order);
		$cart = new Cart($order->id_cart);
		$customer = new Customer($order->id_customer);

		switch ($action) {
			case AhojApi::SUCCESS:
				$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_OK');
				$redirect_url = 'index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$id_order.'&key='.$customer->secure_key;
				break;
			case AhojApi::FAIL:
				$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_FAIL');
				$redirect_url = ''; // fail order
				break;
			
			default:
				$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_ERROR');
				$redirect_url = ''; // fail order
				break;
		}

		$extra_vars = array();
		// Set the order status
		$new_history = new OrderHistory();
		$new_history->id_order = (int) $order->id;
		$new_history->changeIdOrderState((int) $id_order_state, $order, true);
		$new_history->addWithemail(true, $extra_vars);

		PrestaShopLogger::addLog(
			'Validation: add new state '.$id_order_state,
			1,
			null,
			$this->module->name,
			$id_order,
			true
		);

		// redirect to confirmation

		// redirect to order-confirmation 1.7
		Tools::redirect($redirect_url);
	}

}
