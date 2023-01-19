<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Security\Crypt;
use \Hcode\Mailer;

class User extends Model
{	
	const SESSION = "User";
	const SESSION_ERRO = "UserErro";
	const SESSION_REGISTER = "UserRegister";
	const SESSION_REGISTER_ERRO = "UserRegisterErrors";

	public static function getFromSession()
	{
		$user = new User();

		if (isset($_SESSION[User::SESSION]) && $_SESSION[User::SESSION]['iduser'] > 0)
		{			
			$user->setData($_SESSION[User::SESSION]);
		}

		return $user;
	}

	public static function checkLogin($inadmin=true)
	{
		if (!isset($_SESSION[User::SESSION]) 
			|| !$_SESSION[User::SESSION]
			|| !(int)$_SESSION[User::SESSION]["iduser"] > 0)
		{
			return false;
		}
		else
		{
			if ($inadmin && (bool)$_SESSION[User::SESSION]["inadmin"])
			{
				return true;
			}
			else if (!$inadmin)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

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

		$userPassword = Crypt::decryptPassword($data["despassword"]);

		if (strcmp($password, $userPassword) === 0)
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

	public static function verifyLogin($inadmin = true)
	{
		if (!User::checkLogin($inadmin))
		{
			$route = ($inadmin) ? "/admin/login" : "/login";
			
			header("Location: $route");
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
				":pdesperson"=>utf8_decode($this->getdesperson()),
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

		if (count($res) > 0){
			$data = $res[0];

			$data['desperson'] = utf8_encode($data['desperson']);

			$this->setData($res[0]);	
		}
		
	}

	public function update()
	{
		$sql = new Sql();
		
		$results = $sql->select("CALL sp_usersupdate_save(:piduser, :pdesperson, :pdeslogin, :pdespassword, :pdesemail, :pnrphone, :pinadmin)",
			array(
				":piduser"=>$this->getiduser(),
				":pdesperson"=>utf8_decode($this->getdesperson()),
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

	public static function getForgot($email, $inadmin = true)
	{
		$sql = new Sql();

		$results = $sql->select("
			SELECT *
			  FROM tb_persons a
			 INNER JOIN tb_users b USING(idperson)
			 WHERE a.desemail = :email;
			", array(":email"=>$email));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");			
		}

		$data = $results[0];

		$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
			":iduser"=>$data['iduser'],
			":desip"=>$_SERVER["REMOTE_ADDR"]
		));

		if (count($results2) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}

		$dataRecovery = $results2[0];

		$code = base64_encode(Crypt::encryptForgot($dataRecovery['idrecovery']));

		$middleRoute = $inadmin ? "/admin" : "";

		$link = "http://www.apecommerce.com.br$middleRoute/forgot/reset?code=$code";

		$mailer = new Mailer(
			$data["desemail"],
			$data["desperson"],
			"Redefinir senha AP Store",
			"forgot",
			array(
				"name"=>$data['desperson'],
				"link"=>$link
			)
		);

		$mailer->send();

		return $data;
	}

	public static function validForgotDecrypt($code)
	{
		$decode = base64_decode($code);

		$idrecovery = Crypt::decryptForgot($decode);

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			  FROM tb_userspasswordsrecoveries r 
			 INNER JOIN tb_users u USING (iduser)
			 INNER JOIN tb_persons p USING(idperson)
			 WHERE r.idrecovery = :idrecovery
			   AND r.dtrecovery IS NULL
			   AND DATE_ADD(r.dtregister, INTERVAL 1 HOUR) >= NOW()", array(
				":idrecovery"=>$idrecovery
			));

		if (count($results) === 0)
		{
			return NULL;
		}

		return $results[0];
	}

	public static function setForgotUser($idrecovery)
	{
		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));
	}

	public function setPassword($password)
	{
		$encryptPassword = Crypt::encryptPassword($password);

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password"=>$encryptPassword,
			":iduser"=>$this->getiduser()
		));
	}

	public static function setError($msg)
	{
		$_SESSION[User::SESSION_ERRO] = $msg;
	}

	public static function getError()
	{
		$msg = isset($_SESSION[User::SESSION_ERRO]) ? $_SESSION[User::SESSION_ERRO] : '';
		User::clearMsgError();
		return $msg;
	}

	public static function clearMsgError()
	{
		$_SESSION[User::SESSION_ERRO] = NULL;
	}

	public static function setRegisterError($errors)
	{
		$_SESSION[User::SESSION_REGISTER_ERRO] = $errors;
	}

	public static function getRegisterError()
	{
		$errors = isset($_SESSION[User::SESSION_REGISTER_ERRO]) ? $_SESSION[User::SESSION_REGISTER_ERRO] : [];
		User::clearRegisterError();
		return $errors;
	}

	public static function clearRegisterError()
	{
		$_SESSION[User::SESSION_REGISTER_ERRO] = NULL;
	}

	public static function setRegisterSession($user)
	{
		$_SESSION[User::SESSION_REGISTER] = $user;
	}

	public static function getRegisterSession()
	{
		$msg = isset($_SESSION[User::SESSION_REGISTER]) 
			? $_SESSION[User::SESSION_REGISTER] 
			: [
				'name'=>'',
				'email'=>'',
				'phone'=>''
			  ];
		User::clearRegisterSession();
		return $msg;
	}

	public static function clearRegisterSession()
	{
		$_SESSION[User::SESSION_REGISTER] = NULL;
	}

	public static function getByEmail($email)
	{
		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			  FROM tb_persons
			 WHERE desemail = :desemail", array(
				":desemail"=>$email
			));

		if (count($results) === 0)
		{
			return NULL;
		}

		return $results[0];
	}

	public static function hasRegisterErrors($register)
	{
		$errors = [];
		$return = false;

		if (!isset($register['name']) || $register['name'] === '') {
			array_push($errors, ['msg'=>'O campo NOME é obrigatório.']);
			$return = true;
		}

		if (!isset($register['email']) || $register['email'] === '') {
			array_push($errors, ['msg'=>'O campo E-MAIL é obrigatório.']);
			$return = true;
		}

		if (!isset($register['password']) || $register['password'] === '') {
			array_push($errors, ['msg'=>'O campo SENHA é obrigatório.']);
			$return = true;
		}

		User::setRegisterError($errors);

		return $return;
	}
}

 ?>