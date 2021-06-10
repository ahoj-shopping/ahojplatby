<?php

/**
 * Base prestashop module functions
 */
trait AhojPlatbyConfigModuleTrait
{
	/**
	 * Load the configuration form
	 */
	public function getContent()
	{
		/**
		 * If values have been submitted in the form, process.
		 */
		if (((bool)Tools::isSubmit('submitAhojPlatbyModule')) == true) {
			$this->postProcess();
		}

		$this->context->smarty->assign('module_dir', $this->_path);
		$output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
		return $output.$this->renderForm();
	}

	/**
	 * Create the form that will be displayed in the configuration of your module.
	 */
	protected function renderForm()
	{
		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitAhojPlatbyModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
		.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
		);

		return $helper->generateForm(array(
			$this->getConfigForm(),
			$this->getConfigOrderStatesForm(),
		));
	}

	/**
	 * Create the structure of your form.
	 */
	protected function getConfigForm()
	{

		return array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Základne nastavenie'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Produkčné prostredie'),
						'name' => 'AHOJPLATBY_LIVE_MODE',
						'is_bool' => true,
						'desc' => $this->l('Use this module in live mode'),
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Testovacie prostredie'),
						'name' => 'AHOJPLATBY_MODULE_DEBUG',
						'is_bool' => true,
						'desc' => $this->l('Use this module in debug mode'),
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
							)
						),
					),

					array(
						'type' => 'switch',
						'label' => $this->l('Použiť test prihlasovacie údaje'),
						'name' => 'AHOJPLATBY_TEST_ENVIROMENT',
						'is_bool' => true,
						'desc' => $this->l('Replce credentials to test enviroment'),
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
							)
						),
					),

					// array(
					// 	'type' => 'switch',
					// 	'label' => $this->l('Automatically redirect to pay gate'),
					// 	'name' => 'AHOJPLATBY_AUTOMATICALLY_REDIRECT',
					// 	'is_bool' => true,
					// 	'desc' => $this->l('Automatically redirect without payment page'),
					// 	'values' => array(
					// 		array(
					// 			'id' => 'active_on',
					// 			'value' => true,
					// 			'label' => $this->l('Enabled')
					// 		),
					// 		array(
					// 			'id' => 'active_off',
					// 			'value' => false,
					// 			'label' => $this->l('Disabled')
					// 		)
					// 	),
					// ),

					array(
						'type' => 'text',
						'label' => $this->l('Číslo obchodného miesta'),
						'name' => 'AHOJPLATBY_BUSINESS_PLACE',
						'is_bool' => true,
						'desc' => $this->l('ahoj platby business place'),
					),
					array(
						'type' => 'text',
						'label' => $this->l('Bezpečnostný kľúč'),
						'name' => 'AHOJPLATBY_API_KEY',
						'is_bool' => true,
						'desc' => $this->l('Bude zaslaný emailom po úspešnom teste) (k testu viesť, získate po prihlásení do testovacieho Ahoj portálu)'),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				),
			),
		);
	}

	/**
	 * Create the structure of your form.
	 */
	protected function getConfigOrderStatesForm()
	{

		$order_states = OrderState::getOrderStates($this->context->language->id);
		$order_state_options = array();
		$order_state_options[] = array(
			'id' => null,
			'name'      => $this->l('Vyberte si stav objednávky'),
		);

		foreach ($order_states as $key => $value) {
			$order_state_options[] = array(
				'id'	=>	$value['id_order_state'],
				'name'	=>	$value['name']
			);
		}

		return array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Mapovanie stavov objednávky'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					
					array(
						'type' => 'alert.info',
						'col' => 12,
						'label' => false,
						'name' => 'AHOJPLATBY_ORDER_STATE_FREE_TXT',
						'value'	=> $this->l('Namapujte si stavy platby na stavy objednávok Vášho eshopu.')
					),

					array(
						'type' => 'select',
						'name' => 'AHOJPLATBY_ORDER_STATE_AWAITING',
						'label' => $this->l('Spracováva sa Ahoj platba'),
						'options' => array(
							'query' => $order_state_options,
							'id' => 'id',
							'name' => 'name'
						)
					),

					array(
						'type' => 'select',
						'name' => 'AHOJPLATBY_ORDER_STATE_OK',
						'label' => $this->l('Schválená Ahoj platba'),
						'options' => array(
							'query' => $order_state_options,
							'id' => 'id',
							'name' => 'name'
						)
					),

					array(
						'type' => 'select',
						'name' => 'AHOJPLATBY_ORDER_STATE_FAIL',
						'label' => $this->l('Zamietnutá Ahoj platba'),
						'options' => array(
							'query' => $order_state_options,
							'id' => 'id',
							'name' => 'name'
						)
					),

					array(
						'type' => 'select',
						'name' => 'AHOJPLATBY_ORDER_STATE_ERROR',
						'label' => $this->l('Chyba Ahoj platby'),
						'options' => array(
							'query' => $order_state_options,
							'id' => 'id',
							'name' => 'name'
						)
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				),
			),
		);
	}

	/**
	 * Set values for the inputs.
	 */
	protected function getConfigFormValues()
	{
		return array(
			'AHOJPLATBY_LIVE_MODE'    => Configuration::get('AHOJPLATBY_LIVE_MODE'),
			'AHOJPLATBY_MODULE_DEBUG'    => Configuration::get('AHOJPLATBY_MODULE_DEBUG'),
			'AHOJPLATBY_API_KEY'    => Configuration::get('AHOJPLATBY_API_KEY'),
			'AHOJPLATBY_BUSINESS_PLACE'    => Configuration::get('AHOJPLATBY_BUSINESS_PLACE'),
			// states
			'AHOJPLATBY_ORDER_STATE_AWAITING'    => Configuration::get('AHOJPLATBY_ORDER_STATE_AWAITING'),
			'AHOJPLATBY_ORDER_STATE_OK'    => Configuration::get('AHOJPLATBY_ORDER_STATE_OK'),
			'AHOJPLATBY_ORDER_STATE_FAIL'    => Configuration::get('AHOJPLATBY_ORDER_STATE_FAIL'),
			'AHOJPLATBY_ORDER_STATE_ERROR'    => Configuration::get('AHOJPLATBY_ORDER_STATE_ERROR'),

			'AHOJPLATBY_TEST_ENVIROMENT'    => Configuration::get('AHOJPLATBY_TEST_ENVIROMENT'),
			'AHOJPLATBY_AUTOMATICALLY_REDIRECT'    => Configuration::get('AHOJPLATBY_AUTOMATICALLY_REDIRECT'),
		);
	}

	/**
	 * Save form data.
	 */
	protected function postProcess()
	{

		$form_values = $this->getConfigFormValues();

		foreach (array_keys($form_values) as $key) {
			Configuration::updateValue($key, Tools::getValue($key));
		}
	}
}
