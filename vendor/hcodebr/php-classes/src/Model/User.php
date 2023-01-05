<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class User extends Model
{	
	const SESSION = "User";

	public static function login($login, $password)
	{
		$errorMessage = "Usuário inexistente ou senha inválida.";

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count($results) === 0)
		{
			throw new \Exception($errorMessage);
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"]) === true)
		{
			$user = new User();

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;
		}
		else
		{
			throw new \Exception($errorMessage);			
		}
	}

	public static function verifyLogin()
	{
		if (!isset($_SESSION[User::SESSION]) 
			|| !$_SESSION[User::SESSION]
			|| (int)$_SESSION[User::SESSION]["iduser"] === 0
			|| !((bool)$_SESSION[User::SESSION]["inadmin"]))
		{
			header("Location: /admin/login");
			exit;
		}
	}

	public static function logout()
	{
		$_SESSION[User::SESSION] = NULL;
	}

	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users u INNER JOIN tb_persons p USING(idperson) order by  p.desperson");
	}

	public function save()
	{
		$sql = new Sql();
		
		$results = $sql->select("CALL sp_users_save(:pdesperson, :pdeslogin, :pdespassword, :pdesemail, :pnrphone, :pinadmin)",
			array(
				":pdesperson"=>$this->getdesperson(),
				":pdeslogin"=>$this->getdeslogin(),
				":pdespassword"=>$this->getdespassword(),
				":pdesemail"=>$this->getdesemail(),
				":pnrphone"=>$this->getnrphone(),
				":pinadmin"=>$this->getinadmin()
			));

		$this->setData($results[0]);
	}

	public function get($iduser)
	{				
		$sql = new Sql();

		$res = $sql->select("SELECT * FROM tb_users u INNER JOIN tb_persons p USING(idperson) WHERE u.iduser=:iduser", array(
			":iduser"=>$iduser
		));

		$this->setData($res[0]);
	}

	public function update()
	{
		$sql = new Sql();
		
		$results = $sql->select("CALL sp_usersupdate_save(:piduser, :pdesperson, :pdeslogin, :pdespassword, :pdesemail, :pnrphone, :pinadmin)",
			array(
				":piduser"=>$this->getiduser(),
				":pdesperson"=>$this->getdesperson(),
				":pdeslogin"=>$this->getdeslogin(),
				":pdespassword"=>$this->getdespassword(),
				":pdesemail"=>$this->getdesemail(),
				":pnrphone"=>$this->getnrphone(),
				":pinadmin"=>$this->getinadmin()
			));

		$this->setData($results[0]);
	}

	public function delete()
	{
		$sql = new Sql();
		
		$sql->query("CALL sp_users_delete(:piduser)",array
			(
				":piduser"=>$this->getiduser()
			));
	}
}

 ?>