<?php 

namespace Hcode\Utils;

class Session
{	
	const SESSION_SUCCESS = "successMessage";

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
}

 ?>