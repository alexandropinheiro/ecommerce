<?php 

namespace Hcode\Security;

class Crypt
{	
	const SECRET_PASS_IV = '<secretIvOfPass>';
	const SECRET_PASS = '<?secretOfPass?>';
	const SECRET_FORGOT_IV = 'secretIvOfForgot';
	const SECRET_FORGOT = '<secretOfForgot>';
	const ENCRYPT_METHOD = 'AES-128-CBC';

	public static function encryptPassword($data)
	{		
		$openssl = openssl_encrypt(
			$data,
			Crypt::ENCRYPT_METHOD, 
			Crypt::SECRET_PASS,
			0,
			Crypt::SECRET_PASS_IV
		);

		return $openssl;
	}

	/*public static function encryptPassword($data)
	{	
		$pass = password_hash($data, PASSWORD_DEFAULT, [
		    'cost' => 12,
		]);

		return $pass;
	}*/

	public static function decryptPassword($code)
	{
		$openssl = openssl_decrypt(
			$code,
			Crypt::ENCRYPT_METHOD, 
			Crypt::SECRET_PASS,
			0,
			Crypt::SECRET_PASS_IV
		);

		return $openssl;
	}

	public static function encryptForgot($data)
	{
		$openssl = openssl_encrypt(
			$data,
			Crypt::ENCRYPT_METHOD, 
			Crypt::SECRET_FORGOT,
			0,
			Crypt::SECRET_FORGOT_IV
		);

		return $openssl;
	}

	public static function decryptForgot($code)
	{
		$openssl = openssl_decrypt(
			$code,
			Crypt::ENCRYPT_METHOD, 
			Crypt::SECRET_FORGOT,
			0,
			Crypt::SECRET_FORGOT_IV
		);

		return $openssl;
	}
}

 ?>