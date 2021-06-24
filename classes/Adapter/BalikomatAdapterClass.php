<?php 

class BalikomatAdapterClass extends ParentAdapterClass
{
	public function getCarrierOrderByIdOrder($id_order = false)
	{
		if(!$id_order)
			return false;

		$sql = 'SELECT b.name as name,
					   b.address as street,
					   b.location as city,
					   b.postal_code as zip
					   -- b.type as country
				FROM '._DB_PREFIX_.'balikomat_orders a
				LEFT JOIN '._DB_PREFIX_.'balikomat_shops b
					ON (a.balikomat_code = b.code)
				WHERE id_order = '.$id_order;
		$data = Db::getInstance()->getRow($sql);
		if($data)
		{
			$data['country'] = 'sk';
		}

		return $data;
	}
}
