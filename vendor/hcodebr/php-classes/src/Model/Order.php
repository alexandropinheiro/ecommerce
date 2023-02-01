<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;


class Order extends Model
{
	public function save()
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_orders_save(:pidorder, :pidcart, :piduser, :pidstatus, :pidaddress, :pvltotal)",
			array(
				":pidorder"=>$this->getidorder(),
				":pidcart"=>$this->getidcart(),
				":piduser"=>$this->getiduser(),
				":pidstatus"=>$this->getidstatus(),
				":pidaddress"=>$this->getidaddress(),
				":pvltotal"=>$this->getvltotal()
			));

		if (count($results) > 0) {
			$this->setData($results[0]);
		}			
	}

	public function get($idorder)
	{
		$sql = new Sql();
		
		$results = $sql->select("
			SELECT *
			  FROM tb_orders o
			 INNER JOIN tb_ordersstatus os USING (idstatus)
			 INNER JOIN tb_carts c USING (idcart)
			 INNER JOIN tb_users u ON u.iduser = o.iduser
			 INNER JOIN tb_addresses a USING (idaddress)
			 INNER JOIN tb_persons p ON p.idperson = u.idperson
			 WHERE o.idorder = :idorder",
			array(":idorder"=>$idorder));

		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}

	public static function listAll()
	{
		
		$sql = new Sql();
		
		$results = $sql->select("
			SELECT *
			  FROM tb_orders o
			 INNER JOIN tb_ordersstatus os USING (idstatus)
			 INNER JOIN tb_carts c USING (idcart)
			 INNER JOIN tb_users u ON u.iduser = o.iduser
			 INNER JOIN tb_addresses a USING (idaddress)
			 INNER JOIN tb_persons p ON p.idperson = u.idperson
			 ORDER BY o.dtregister desc");

		return $results;
	}

	public static function getPage($search, $page = 1, $itemsPerPage = 3)
	{
		$selectCommand =
		    "SELECT SQL_CALC_FOUND_ROWS *
			   FROM tb_orders o
			  INNER JOIN tb_ordersstatus os USING (idstatus)
			  INNER JOIN tb_carts c USING (idcart)
			  INNER JOIN tb_users u ON u.iduser = o.iduser
			  INNER JOIN tb_addresses a USING (idaddress)
			  INNER JOIN tb_persons p ON p.idperson = u.idperson
			  WHERE o.idorder = :idorder
			     OR p.desperson LIKE :search
			  ORDER BY o.dtregister DESC";

		return parent::getPaginated($selectCommand, [':search'=>'%'.$search.'%', ':idorder'=>$search], $page);
	}

	public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_orders WHERE idorder=:idorder", [
			':idorder'=>$this->getidorder()
		]);
	}

	public function getArrayForPagSeguro($cart)
	{
		$nrphone = $this->getnrphone();

		$documents = array('document'=>[
			'type'=>'CPF',
			'value'=>'79726566061'
		]);

		$products = $cart->getProducts();

		$items = array();

		foreach ($products as $row) {
			array_push($items, [
				'item'=>[
					'id'=>$row['idproduct'],
					'description'=>$row['desproduct'],
					'amount'=>$row['vltotal'],
					'quantity'=>$row['nrqtd'],
					'weight'=>$row['vlweight']*1000,
					'shippingCost'=>'1.00'
				]
			]);
		}

		$arrayBody = [
			'checkout'=>[
				'sender'=>[
					'name'=>$this->getdesperson(),
					'email'=>$this->getdesemail(),
					'phone'=>[
						'areaCode'=>substr($nrphone, 0, 2),
						'number'=>substr($nrphone, 2, strlen($nrphone))
					],
					'documents'=>$documents
				],
				'currency'=>'BRL',
				'items'=>$items,
				'extraAmount'=>'0.00',
				'reference'=>$this->getidorder(),
				'shipping'=>[
					'address'=>[
						'street'=>$this->getdesaddress(),
						'number'=>$this->getdesnumber(),
						'complement'=>$this->getdescomplement(),
						'district'=>$this->getdesdistrict(),
						'city'=>$this->getdescity(),
						'state'=>$this->getdesstate(),
						'country'=>$this->getdescountry(),
						'postalCode'=>$this->getdeszipcode()
					],
					'type'=>1,
					'cost'=>$cart->getvlfreight(),
					'addressRequired'=>"true",

				],
				'maxAge'=>1000,
				'maxUse'=>1000,
				'receiver'=>[
					'email'=>'alexandro.analista@gmail.com'
				],
				'enableRecover'=>"false"
			]
		];

		return $arrayBody;
	}
}

 ?>