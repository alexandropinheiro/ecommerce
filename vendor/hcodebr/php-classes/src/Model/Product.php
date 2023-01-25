<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Product extends Model
{
	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("SELECT *
			   FROM tb_products
			  ORDER BY desproduct");
	}
	public static function getPage($search, $page = 1)
	{
		$selectCommand =
		    "SELECT SQL_CALC_FOUND_ROWS *
			   FROM tb_products
			  WHERE desproduct LIKE :search
			  ORDER BY desproduct";

		return parent::getPaginated($selectCommand, $search, $page);
	}

	public static function checkList($list)
	{
		foreach ($list as &$row) {
			
			$product = new Product();
			$product->setData($row);
			$row = $product->getValues();
		}

		return $list;
	}

	public function save()
	{
		$sql = new Sql();
		
		$results = $sql->select("CALL sp_products_save(
				:pidproduct, 
				:pdesproduct, 
				:pvlprice,
				:pvlwidth,
				:pvlheight,
				:pvllength,
				:pvlweight,
				:pdesurl)",
			array(
				":pidproduct"=>$this->getidproduct(),
				":pdesproduct"=>$this->getdesproduct(),
				":pvlprice"=>$this->getvlprice(),
				":pvlwidth"=>$this->getvlwidth(),
				":pvlheight"=>$this->getvlheight(),
				":pvllength"=>$this->getvllength(),
				":pvlweight"=>$this->getvlweight(),
				":pdesurl"=>$this->getdesurl(),
			));

		$this->setData($results[0]);
	}

	public function get($idproduct)
	{				
		$sql = new Sql();

		$res = $sql->select("SELECT * FROM tb_products WHERE idproduct=:idproduct", array(
			":idproduct"=>$idproduct
		));

		$this->setData($res[0]);
	}

	public function delete()
	{
		$sql = new Sql();
		
		$sql->query("DELETE FROM tb_products WHERE idproduct=:idproduct", array(
			":idproduct"=>$this->getidproduct()
		));
	}

	public function checkPhoto()
	{

		if (file_exists(
			$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg"))
		{
			$url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";
		}
		else
		{
			$url = "/res/site/img/products/semimagem.png";
		}

		return $this->setdesphoto($url);
	}

	public function getValues()
	{
		$this->checkPhoto();

		$values = parent::getValues();

		return $values;
	}

	public function setPhoto($file)
	{

		$extension = explode('.', $file['name']);
		$extension = end($extension);

		switch ($extension) {
			case 'jpg':
			case 'jpeg':
				$image = imagecreatefromjpeg($file['tmp_name']);
				break;
			
			case 'gif':
				$image = imagecreatefromgif($file['tmp_name']);
				break;

			case 'png':
				$image = imagecreatefrompng($file['tmp_name']);
				break;
		}

		$dest = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg";

		imagejpeg($image, $dest);

		imagedestroy($image);

		$this->checkPhoto();
	}

	public function getFromUrl($desurl)
	{
		$sql = new Sql();

		$res = $sql->select("SELECT * from tb_products where desurl = :desurl", array(
			":desurl"=>$desurl
		));
		
		$this->setData($res[0]);
	}

	public function getCategories()
	{
		$sql = new Sql();

		return $sql->select("
				SELECT * 
				  from tb_categories a 
				 INNER JOIN tb_productscategories b USING(idcategory)
				 WHERE b.idproduct = :idproduct", array(
			":idproduct"=>$this->getidproduct()
		));
	}
}

 ?>