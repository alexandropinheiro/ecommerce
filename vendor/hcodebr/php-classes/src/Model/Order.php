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
}

 ?>