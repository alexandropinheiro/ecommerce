<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\User;

class Cart extends Model
{	
	const SESSION = "cart";
	const SESSION_ERRO = "CartError";

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

		$this->getCalculateTotal();
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

		$sql->query($command, [
					 	':idcart'=>$this->getidcart(),
					 	':idproduct'=>$product->getidproduct()
					 ]);

		$this->getCalculateTotal();
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

	public function getProductTotals()
	{
		$sql = new Sql();

		$results = $sql->select("
			SELECT SUM(a.vlprice) AS vlprice, SUM(a.vlwidth) AS vlwidth, SUM(a.vlheight) AS vlheight, 
			       SUM(a.vllength) AS vllength, SUM(a.vlweight) AS vlweight, COUNT(1) AS nrqtd
			  FROM tb_products a
			 INNER JOIN tb_cartsproducts b using (idproduct)
			 WHERE b.idcart=:idcart
			   AND b.dtremoved IS NULL", [
			   	":idcart"=>$this->getidcart()
			   ]);

		return ($results[0] === 0) ? [] : $results[0];
	}

	public function setFreight($nrzipcode)
	{
		
		$nrzipcode = str_replace('-', '', $nrzipcode);

		$totals = $this->getProductTotals();

		if ($totals['nrqtd'] > 0)
		{
			$qs = http_build_query([
				'nCdEmpresa'=>'',
				'sDsSenha'=>'',
				'nCdServico'=>'40010',
				'sCepOrigem'=>'28980116',
				'sCepDestino'=>$nrzipcode,
				'nVlPeso'=>$totals['vlweight'],
				'nCdFormato'=>'1',
				'nVlComprimento'=>$totals['vllength'],
				'nVlAltura'=>$totals['vlheight'],
				'nVlLargura'=>$totals['vlwidth'],
				'nVlDiametro'=>'0',
				'sCdMaoPropria'=>'S',
				'nVlValorDeclarado'=>$totals['vlprice'],
				'sCdAvisoRecebimento'=>'S'
			]);

			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);
			
			$result = $xml->Servicos->cServico;

			$arrayMsgErro = (array)$result->MsgErro;

			//var_dump($arrayMsgErro != [] && $arrayMsgErro != '');
			//exit;

			if ($arrayMsgErro != [] && $arrayMsgErro != '')
			{
				Cart::setMsgError(((array)$result->MsgErro)[0]);
			}
			else
			{
				Cart::clearMsgError();
			}

			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->save();

			return $result;
		}
		else
		{

		}

	}

	public static function formatValueToDecimal($value):float
	{

		$value = str_replace(".", "", $value);
		return str_replace(",", ".", $value);
		
	}

	public static function setMsgError($msg)
	{
		$_SESSION[Cart::SESSION_ERRO] = $msg;
	}

	public static function getMsgError()
	{
		$msg = isset($_SESSION[Cart::SESSION_ERRO]) ? $_SESSION[Cart::SESSION_ERRO] : '';
		Cart::clearMsgError();
		return $msg;
	}

	public static function clearMsgError()
	{
		$_SESSION[Cart::SESSION_ERRO] = NULL;
	}

	public function updateFreight()
	{
		if($this->getdeszipcode() != '')
		{
			$this->setFreight($this->getdeszipcode());
		}
	}

	public function getValues()
	{
		$this->getCalculateTotal();

		return parent::getValues();
	}

	public function getCalculateTotal()
	{
		$this->updateFreight();
		
		$totals = $this->getProductTotals();

		$this->setvlsubtotal($totals['vlprice']);
		$this->setvltotal($totals['vlprice'] + $this->getvlfreight());
	}
}

 ?>