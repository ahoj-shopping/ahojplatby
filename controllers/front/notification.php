<?php

include_once(dirname(__FILE__) . '/../../autoload.php');

class AhojplatbyNotificationModuleFrontController extends ParentController
{

	// 1. REJECTED - ziadost zamietnuta - FAIL
	// 2. SIGNED - ziadost podpisana klientom - OK
	// 3. CANCELED - ziadost bola zrusena - FAIL
	// 4. DELETED - ziadost bola zmazana na zakalade internych procesov - FAIL
	// not used
	// 5. CREATED
	// 6. DRAFT
	// 7. APPROVED
	// 7. ACTIVE


	public function initContent()
	{
		$debug = Configuration::get('AHOJPLATBY_MODULE_DEBUG');
		if($debug)
		{
			PrestaShopLogger::addLog(
				'Notification: init',
				1,
				null,
				$this->module->name,
				0,
				true
			);
		}
		
		// get http body
		$data = (array) json_decode(file_get_contents('php://input'), true);

		if(isset($data) && $data['orderNumber'] && $data['state'])
		{

			PrestaShopLogger::addLog(
				'Notification: start - orderNumber: '.$data['orderNumber'].' | state: '.$data['state'],
				1,
				null,
				$this->module->name,
				$data['orderNumber'],
				true
			);

			// get id_order_state by state 
			switch ($data['state']) {
				case 'CREATED':
				case 'APPROVED':
				case 'ACTIVE':
				case 'DRAFT':
				case 'SENT':
					$id_order_state = 0;
					break;
				case 'REJECTED':
				case 'CANCELED':
				case 'DELETED':
					$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_FAIL');
					break;

				case 'SIGNED':
					$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_OK');
					break;
				
				default:
					$id_order_state = Configuration::get('AHOJPLATBY_ORDER_STATE_ERROR');
					break;
			}

			if($id_order_state == 0)
			{
				PrestaShopLogger::addLog(
					'Notification: skip order_state - state: '.$data['state'],
					1,
					null,
					$this->module->name,
					$data['orderNumber'],
					true
				);

				die(
					Tools::jsonEncode(array(
						'status'	=>	false,
						'error'		=>	'Skip add order_state'
					);)
				);
			}

			// orderNumber is id_order
			$order = new Order($data['orderNumber']);
			if(Validate::isLoadedObject($order) && $order)
			{
				// add new state to order with email

				$extra_vars = array();
				// Set the order status
				$new_history = new OrderHistory();
				$new_history->id_order = (int) $order->id;
				$new_history->changeIdOrderState((int) $id_order_state, $order, true);
				$new_history->addWithemail(true, $extra_vars);

				PrestaShopLogger::addLog(
					'Notification: add order state - id_order_state: '.$id_order_state.' | id_order: '.$order->id,
					1,
					null,
					$this->module->name,
					$data['orderNumber'],
					true
				);

				$result = array(
					'status'	=>	true,
					'error'		=>	''
				);
				
			} 
			else
			{
				PrestaShopLogger::addLog(
					'Notification: bad orderNumber '.$data['orderNumber'],
					3,
					null,
					$this->module->name,
					$data['orderNumber'],
					true
				);

				$result = array(
					'status'	=>	false,
					'error'	=>	$this->l('Bad orderNumber')
				);
			}

		}

		die(
			Tools::jsonEncode($result)
		);

		dd(array(
			$id_order_state,
			$data['orderNumber'],
			$data['state'],
			$data,
		), true);
	}


	
}
