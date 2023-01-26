<?php 

namespace Hcode\Utils;

class Session
{	
	const SESSION_SUCCESS = "successMessage";
	const SESSION_ERROR = "errorMessage";
	const SESSION_ADDRESS = "address";
	const SESSION_REGISTER = "registers";
	const SESSION_LIST_ERRO = "listErrors";

	public static function setSuccess($msg)
	{
		$_SESSION[Session::SESSION_SUCCESS] = $msg;
	}

	public static function getSuccess()
	{
		$msg = isset($_SESSION[Session::SESSION_SUCCESS]) 
			? $_SESSION[Session::SESSION_SUCCESS] 
			: '';

		$_SESSION[Session::SESSION_SUCCESS] = NULL;

		return $msg;
	}

	public static function setError($msg)
	{
		$_SESSION[Session::SESSION_ERROR] = $msg;
	}

	public static function getError()
	{
		$msg = isset($_SESSION[Session::SESSION_ERROR]) 
			? $_SESSION[Session::SESSION_ERROR] 
			: '';

		$_SESSION[Session::SESSION_ERROR] = NULL;
		
		return $msg;
	}

	public static function setListError($errors)
	{
		$_SESSION[Session::SESSION_LIST_ERRO] = $errors;
	}

	public static function getListError()
	{
		$errors = isset($_SESSION[Session::SESSION_LIST_ERRO]) ? $_SESSION[Session::SESSION_LIST_ERRO] : [];
		$_SESSION[Session::SESSION_LIST_ERRO] = NULL;
		return $errors;
	}

	public static function setRegister($user)
	{
		$_SESSION[Session::SESSION_REGISTER] = $user;
	}

	public static function getRegister($defaultData = [])
	{
		$msg = isset($_SESSION[Session::SESSION_REGISTER]) 
			? $_SESSION[Session::SESSION_REGISTER] 
			: $defaultData;
		$_SESSION[Session::SESSION_REGISTER] = NULL;
		return $msg;
	}
}

 ?>