<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\User;

class Cart extends Model
{	
	const SESSION = "cart";

	public static function getFromSession()
	{
		$cart = new Cart();

		if (isset($_SESSION[Cart::SESSION]) && $_SESSION[Cart::SESSION]['idcart'] > 0)
		{
			$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
		}
		else
		{
			$cart->getFromSessionId();

			if (!(int)$cart->getidcart() > 0)
			{
				//Criar carrinho de compras
				$data = [
					'dessessionid'=>session_id()
				];

				if (User::checkLogin(false))
				{
					$user = User::getFromSession();
					
					$data['iduser'] = $user->getiduser();
				}

				$cart->setData($data);

				$cart->save();

				$cart->setToSession();
			}
		}

		return $cart;
	}

	public function get(int $idcart)
	{				
		$sql = new Sql();

		$res = $sql->select("SELECT * FROM tb_carts WHERE idcart=:idcart", array(
			":idcart"=>$idcart
		));

		if (count($res) > 0)
		{
			$this->setData($res[0]);
		}		
	}

	public function getFromSessionId()
	{				
		$sql = new Sql();

		$res = $sql->select("SELECT * FROM tb_carts WHERE dessessionid=:dessessionid", array(
			":dessessionid"=>session_id()
		));

		if (count($res) > 0)
		{
			$this->setData($res[0]);
		}		
	}

	public function save()
	{
		$sql = new Sql();
		
		$results = $sql->select("CALL sp_carts_save(
				:pidcart,
				:pdessessionid,
				:piduser,
				:pdeszipcode,
				:pvlfreight,
				:pnrdays)",
			array(
				":pidcart"=>$this->getidcart(),
				":pdessessionid"=>$this->getdessessionid(),
				":piduser"=>$this->getiduser(),
				":pdeszipcode"=>$this->getdeszipcode(),
				":pvlfreight"=>$this->getvlfreight(),
				":pnrdays"=>$this->getnrdays()
			));

		$this->setData($results[0]);
	}

	public function setToSession()
	{
		$_SESSION[Cart::SESSION] = $this->getValues();
	}

	public function addProduct(Product $product)
	{
		$sql = new Sql();

		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)", [
			':idcart'=>$this->getidcart(),
			'idproduct'=>$product->getidproduct()
		]);
	}

	public function removeProduct(Product $product, bool $all = false)
	{
		$sql = new Sql();

		$command = "UPDATE tb_cartsproducts 
			           SET dtremoved = NOW() 
			         WHERE idcart = :idcart 
			           AND idproduct = :idproduct
			           AND dtremoved IS NULL";
			             
		if (!(bool)$all)
		{
			$command = $command . " LIMIT 1";
		}

	    //var_dump($command, $this->getidcart(), $product->getidproduct());
	    //exit;
 	
		$sql->query($command, [
					 	':idcart'=>$this->getidcart(),
					 	':idproduct'=>$product->getidproduct()
					 ]);
	}

	public function getProducts()
	{
		$sql = new Sql();

		$rows = $sql->select("
			SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, count(1) as nrqtd, SUM(vlprice) as vltotal
			  FROM tb_cartsproducts a 
			 INNER JOIN tb_products b USING(idproduct)
			 WHERE a.idcart = :idcart
			   AND a.dtRemoved IS NULL
			 GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
			 ORDER BY a.dtRegister", [":idcart"=>$this->getidcart()]);

		return Product::checkList($rows);
	}
}

 ?>