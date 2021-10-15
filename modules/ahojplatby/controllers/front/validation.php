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
				// success order
				$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_OK');
				$order_confirmation_url = $this->context->link->getPageLink('order-confirmation', null, null, array(
					'id_cart'	=> $cart->id,
					'id_module'	=> $this->module->id,
					'id_order'	=> $id_order,
					'key'	=>	$customer->secure_key,
					'status'	=>	'ok'
				));

				// $redirect_url_17 = $this->context->link->getPageLink('order-confirmation', null, null, array(
				// 	'id_cart'	=> $cart->id,
				// 	'id_module'	=> $this->module->id,
				// 	'id_order'	=> $id_order,
				// 	'key'	=>	$customer->secure_key
				// ));
				// $redirect_url_16 = '';


			break;
			
			case AhojApi::FAIL:
				// fail order
				$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_FAIL');
				$order_confirmation_url = $this->context->link->getPageLink('order-confirmation', null, null, array(
					'id_cart'	=> $cart->id,
					'id_module'	=> $this->module->id,
					'id_order'	=> $id_order,
					'key'	=>	$customer->secure_key,
					'status'	=>	'fail'
				));
				// $redirect_url_17 = $this->context->link->getModuleLink('ahojplatby', 'fail', array(
				// 	'id_order'	=>	$id_order
				// )); 
				// $redirect_url_16 = '';

			break;

			default:
				// fail order
				$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_ERROR');
				$order_confirmation_url = $this->context->link->getPageLink('order-confirmation', null, null, array(
					'id_cart'	=> $cart->id,
					'id_module'	=> $this->module->id,
					'id_order'	=> $id_order,
					'key'	=>	$customer->secure_key,
					'status'	=>	'ok'
				));
				// $redirect_url_17 = $this->context->link->getModuleLink('ahojplatby', 'fail', array(
				// 	'id_order'	=>	$id_order
				// ));  
				// $redirect_url_16 = '';
			break;
		}

		// TODO 
		// ak uz je stav nastaveny, tak nemenit

		// Novy status sa tu nastavovat nebude.
		// bude sa to nastavovat cisto len async
		
		// $extra_vars = array();
		// // Set the order status
		// $new_history = new OrderHistory();
		// $new_history->id_order = (int) $order->id;
		// $new_history->changeIdOrderState((int) $id_order_state, $order, true);
		// $new_history->addWithemail(true, $extra_vars);

		// PrestaShopLogger::addLog(
		// 	'Validation: add new state '.$id_order_state,
		// 	1,
		// 	null,
		// 	$this->module->name,
		// 	$id_order,
		// 	true
		// );

		PrestaShopLogger::addLog(
			'Validation: redirect to '.$action,
			2,
			null,
			$this->module->name,
			$id_order,
			true
		);

		Tools::redirect($order_confirmation_url);

		// // redirect to order-confirmation 1.7
		// if($this->module->is17)
		// 	Tools::redirect($redirect_url_17);

		// // redirect to order-confirmation 1.6
		// if(!$this->module->is17)
		// 	Tools::redirect($redirect_url_16);

	}

}
