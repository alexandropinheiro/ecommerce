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
	}
}

 ?>