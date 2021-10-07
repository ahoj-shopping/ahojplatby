<?php 

class DpdAdapterClass extends ParentAdapterClass
{
	public static function getCarrierOrderByIdCart($id_cart = false)
	{
		if(!$id_cart)
			return false;

		$sql = 'SELECT b.naz_prov as name,
					   b.adresa as street,
					   b.okres as city,
					   b.psc as zip,
					   b.type as country
				FROM '._DB_PREFIX_.'shaim_dpdparcelshop_data a
				LEFT JOIN '._DB_PREFIX_.'shaim_dpdparcelshop b
					ON (a.id = b.id)
				WHERE id_cart = '.$id_cart;
		return Db::getInstance()->getRow($sql);
	}
}
