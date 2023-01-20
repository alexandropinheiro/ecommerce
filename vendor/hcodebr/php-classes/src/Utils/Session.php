<?php 

namespace Hcode\Utils;

class Session
{	
	const SESSION_SUCCESS = "successMessage";
	const SESSION_ERROR = "errorMessage";
	const SESSION_ADDRESS = "address";

	public static function setSuccessMessage($msg)
	{
		$_SESSION[Session::SESSION_SUCCESS] = $msg;
	}

	public static function getSuccessMessage()
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

	public static function setAddressSession($address)
	{
		$_SESSION[Session::SESSION_ADDRESS] = $address;
	}

	public static function getAddressSession()
	{
		$address = isset($_SESSION[Session::SESSION_ADDRESS]) 
			? $_SESSION[Session::SESSION_ADDRESS] 
			: NULL;

		$_SESSION[Session::SESSION_ADDRESS] = NULL;
		
		return $address;
	}
}

 ?>