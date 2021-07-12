<?php 

class ZasielkovnaAdapterClass extends ParentAdapterClass
{
	public static function getCarrierOrderByIdOrder($id_order = false)
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

		$data = Db::getInstance()->getRow($sql);
		if(!isset($data['name']) || !$data['name'])
		{
			$sql = 'SELECT po.name_branch as name,
						   po.name_branch as street,
						   po.currency_branch as currency
					FROM '._DB_PREFIX_.'packetery_order po
					WHERE po.id_order = '.$id_order.'
						AND po.is_carrier = 0';
			$row =  Db::getInstance()->getRow($sql);
			
			$city = substr($row['street'], 0, strpos($row['street'], ','));
			$street = substr($row['street'], strpos($row['street'], ','));
			$street = substr($street, 2);

			$data = array(
				'name'	=>	$row['name'],
				'city'	=>	$city,
				'street'	=>	$street,
				'currency'	=>	$row['currency']
			);
		}

		return $data;
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

		$city = substr($row['street'], 0, strpos($row['street'], ','));
		$street = substr($row['street'],strpos($row['street'], ','));
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
