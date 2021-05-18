<?php

class AhojplatbyValidationModuleFrontController extends ParentController
{
	public function initContent()
	{
		parent::initContent();
		$debug = Configuration::get('AHOJPLATBY_MODULE_DEBUG');

		$id_order = Tools::getValue('id_order');
		$action = Tools::getValue('action');
		
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
				3,
				null,
				$this->module->name,
				0,
				true
			);
			return false;
		}

		switch ($action) {
			case AhojApi::SUCCESS:
				$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_OK');
				break;
			case AhojApi::FAIL:
				$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_FAIL');
				break;
			
			default:
				$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_ERROR');
				break;
		}

		$order = new Order($id_order);
		$extra_vars = array();
		// Set the order status
		$new_history = new OrderHistory();
		$new_history->id_order = (int) $order->id;
		$new_history->changeIdOrderState((int) $id_order_state, $order, true);
		$new_history->addWithemail(true, $extra_vars);

		PrestaShopLogger::addLog(
			'Validation: add new state: '.$id_order_state,
			1,
			null,
			$this->module->name,
			$id_order,
			true
		);
	}

}
