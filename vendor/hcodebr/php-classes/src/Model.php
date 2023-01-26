<?php 

namespace Hcode;

use \Hcode\DB\Sql;

class Model
{
	
	private $values = [];

	public function __call($name, $args)
	{
		$method = substr($name, 0, 3);
		$fieldName = substr($name, 3, strlen($name));

		switch ($method) {
			case "get":
				return isset($this->values[$fieldName]) ? $this->values[$fieldName] : '';
			break;
			
			case "set":
				$this->values[$fieldName] = $args[0];
			break;
		}
	}

	public function setData($data = array())
	{
		foreach ($data as $key => $value) {
			
			$this->{"set".$key}($value);

		}
	}

	public function getValues()
	{		
		return $this->values;
	}

	public static function getPaginated($selectCommand, $search, $page = 1, $hasExactValue = false)
	{
		$itemsPerPage = 10;

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$selectCommand .= " LIMIT $start, $itemsPerPage";
				
 		$totalCommand = "SELECT FOUND_ROWS() as nrtotal";

 		if ($hasExactValue){
 			$params = array(':search'=>'%'.$search.'%', ':exactValue'=>$search);
 		}else{
 			$params = array(':search'=>'%'.$search.'%');
 		}
 		
 		$results = $sql->select($selectCommand, $params);

		$resultTotal = $sql->select($totalCommand);

		$totalItems = (int)$resultTotal[0]['nrtotal'];

		return [
			'data'=>$results,
			'total'=>(int)$totalItems,
			'pages'=>ceil($totalItems / $itemsPerPage)
		];
	}
}

 ?>