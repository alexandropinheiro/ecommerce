<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model
{	
	public static function getCep($nrcep)
	{
		$nrcep = str_replace("-", "", $nrcep);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$nrcep/json/");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$data = json_decode(curl_exec($ch), true);

		curl_close($ch);

		return $data;
	}

	public function loadFromCep($nrcep)
	{
		$data = Address::getCep($nrcep);

		if (isset($data['logradouro']) && $data['logradouro']){
			$this->setdesaddress($data['logradouro']);
			$this->setdesnumber('');
			$this->setdescomplement($data['complemento']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry('Brasil');
			$this->setdeszipcode($nrcep);
			$this->setdesdistrict($data['bairro']);
		}
	}

	public function save()
	{
		$sql = new Sql();
		
		$results = $sql->select("CALL sp_addresses_save(:pidaddress, :pidperson, :pdesaddress, :pdesnumber, :pdescomplement, :pdescity, :pdesstate, :pdescountry, :pdeszipcode, :pdesdistrict)",
			array(
				":pidaddress"=>$this->getidaddress(),
				":pidperson"=>$this->getidperson(),
				":pdesaddress"=>utf8_decode($this->getdesaddress()),
				":pdesnumber"=>$this->getdesnumber(),
				":pdescomplement"=>utf8_decode($this->getdescomplement()),
				":pdescity"=>utf8_decode($this->getdescity()),
				":pdesstate"=>utf8_decode($this->getdesstate()),
				":pdescountry"=>utf8_decode($this->getdescountry()),
				":pdeszipcode"=>$this->getdeszipcode(),
				":pdesdistrict"=>utf8_decode($this->getdesdistrict())
			));

		if (count($results) > 0) {
			$this->setData($results[0]);
		}			
	}
}

 ?>