<?php 

class ZasielkovnaAdapterClass extends ParentAdapterClass
{
	public function getCarrierOrderByIdOrder($id_order = false)
	{
		if(!$id_order)
			return false;

		$sql = 'SELECT pb.place as name,
					   pb.street,
					   pb.city,
					   pb.zip,
					   pb.country,
					   pb.currency
				FROM '._DB_PREFIX_.'packetery_order po
				LEFT JOIN '._DB_PREFIX_.'packetery_branch pb
					ON (po.id_branch = pb.id_branch)
				WHERE po.id_order = '.$id_order.'
					AND po.is_carrier = 0';
		return Db::getInstance()->getRow($sql);
	}

	public function getCarrierOrderByIdOrder16($id_order = false)
	{
		if(!$id_order)
			return false;

		$sql = 'SELECT po.name_branch as name,
					   po.name_branch as street,
					   po.currency_branch as currency
				FROM '._DB_PREFIX_.'packetery_order po
				WHERE po.id_order = '.$id_order.'
					AND po.is_carrier = 0';
		$row =  Db::getInstance()->getRow($sql);

		$city = substr($row, 0, strpos($row, ','));
		$street = substr($row,strpos($row, ','));
		$street = substr($street, 2);

		$result = array(
			'name'	=>	$row['name'],
			'city'	=>	$city,
			'street'	=>	$street,
			'currency'	=>	$row['currency']
		);

		return $result;
	}
}
