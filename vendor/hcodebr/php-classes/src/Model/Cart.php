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
}

 ?>