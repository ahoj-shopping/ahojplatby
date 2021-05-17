<?php

class CustomStock extends ObjectModel
{

	public $id_product;
	public $id_product_attribute;
	public $quantity;

	public static $definition = array(
		'table' => 'custom_stock',
		'primary' => 'id_custom_stock',

		'fields' => array(

			'id_product' => 				array('type' => self::TYPE_INT),
			'id_product_attribute' =>		array('type' => self::TYPE_INT),
			'quantity'	=>					array('type' => self::TYPE_INT),

			// 'date_add' =>               array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
			// 'date_upd' =>               array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
		)
	);


	public static function updateValue($id_product, $quantity = 0)
	{
		$sql = 'SELECT id_custom_stock 
				FROM '._DB_PREFIX_.'custom_stock
				WHERE id_product = '.$id_product;
		$id_custom_stock = Db::getInstance()->getValue($sql);

		if($id_custom_stock)
		{
			return Db::getInstance()->update('custom_stock', array(
				'quantity'	=>	$quantity
			), 'id_custom_stock = '.$id_custom_stock);

		} else {

			return Db::getInstance()->insert('custom_stock', array(
				'id_product'	=>	$id_product,
				'id_product_attribute'	=>	0,
				'quantity'	=>	(int)$quantity
			));
		}
	}
}
