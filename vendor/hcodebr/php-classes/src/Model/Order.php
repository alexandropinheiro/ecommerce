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
		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();
		
		$selectCommand =
		    "SELECT SQL_CALC_FOUND_ROWS *
			   FROM tb_orders o
			  INNER JOIN tb_ordersstatus os USING (idstatus)
			  INNER JOIN tb_carts c USING (idcart)
			  INNER JOIN tb_users u ON u.iduser = o.iduser
			  INNER JOIN tb_addresses a USING (idaddress)
			  INNER JOIN tb_persons p ON p.idperson = u.idperson
			  WHERE o.idorder = :id
			     OR p.desperson LIKE :search
			  ORDER BY o.dtregister DESC
			  LIMIT $start, $itemsPerPage";
 		$totalCommand = "SELECT FOUND_ROWS() as nrtotal";

		$results = $sql->select($selectCommand, array(
			':search'=>'%'.$search.'%',
			':id'=>$search
		));

		$resultTotal = $sql->select($totalCommand);

		$totalItems = (int)$resultTotal[0]['nrtotal'];

		return [
			'data'=>$results,
			'total'=>(int)$totalItems,
			'pages'=>ceil($totalItems / $itemsPerPage)
		];
	}

	public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_orders WHERE idorder=:idorder", [
			':idorder'=>$this->getidorder()
		]);
	}
}

 ?>