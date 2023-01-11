<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Category extends Model
{	
	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_categories order by descategory");
	}

	public function save()
	{
		$sql = new Sql();
		
		$results = $sql->select("CALL sp_categories_save(:pidcategory, :pdescategory)",
			array(
				":pidcategory"=>$this->getidcategory(),
				":pdescategory"=>$this->getdescategory()
			));

		$this->setData($results[0]);

		Category::updateFile();
	}

	public function get($idcategory)
	{				
		$sql = new Sql();

		$res = $sql->select("SELECT * FROM tb_categories WHERE idcategory=:idcategory", array(
			":idcategory"=>$idcategory
		));

		$this->setData($res[0]);
	}

	public function delete()
	{
		$sql = new Sql();
		
		$sql->query("DELETE FROM tb_categories WHERE idcategory=:idcategory", array(
			":idcategory"=>$this->getidcategory()
		));

		Category::updateFile();
	}

	public static function updateFile()
	{

		$categories = Category::listAll();

		$html = [];

		foreach ($categories as $row) 
		{
			array_push($html, '<li><a href="/category/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
		}

		file_put_contents($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'categories-menu.html', implode('', $html));
	}

	public function getProducts($related = true)
	{
		$sql = new Sql();
		
		$command = $related ? 
		    "select a.*
	 		   from tb_products a
			  INNER JOIN tb_productscategories b USING(idproduct)
			  WHERE b.idcategory=:idcategory" :
			"select a.*
			   from tb_products a  
			  WHERE NOT EXISTS 
			      (select 1 
			         from tb_productscategories b 
				    where a.idproduct = b.idproduct 
			          and b.idcategory=:idcategory)";		

		return $sql->select($command, array(':idcategory'=>$this->getidcategory()));
	}

	public function getProductsPaginated($page = 1, $itemsPerPage = 3)
	{
		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();
		
		$selectCommand =
		    "SELECT SQL_CALC_FOUND_ROWS *
			   FROM tb_products p
			  INNER JOIN tb_productscategories pc USING(idproduct)
			  INNER JOIN tb_categories c USING(idcategory)
			  WHERE c.idcategory=:idcategory
			  LIMIT $start, $itemsPerPage";
 		$totalCommand = "SELECT FOUND_ROWS() as nrtotal";

		$results = $sql->select($selectCommand, array(
			':idcategory'=>$this->getidcategory()
		));

		$resultTotal = $sql->select($totalCommand);

		$totalItems = (int)$resultTotal[0]['nrtotal'];

		return [
			'data'=>Product::checkList($results),
			'total'=>$totalItems,
			'pages'=>ceil($totalItems / $itemsPerPage)
		];
	}

	public function addProduct(Product $product)
	{
		$sql = new Sql();

		$sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES (:idcategory, :idproduct)", array(':idcategory'=>$this->getidcategory(), ':idproduct'=>$product->getidproduct()));
	}

	public function removeProduct(Product $product)
	{
		$sql = new Sql();

		$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", array(':idcategory'=>$this->getidcategory(), ':idproduct'=>$product->getidproduct()));
	}
}

 ?>