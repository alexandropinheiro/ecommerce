<?php

namespace Hcode\Infrastructure;

class Client
{
	public function get($url)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$result = curl_exec($ch);

		curl_close($ch);

		return $result;
	}

	public function post($url, $header = NULL, $body = NULL)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		if ($header !== NULL)
		{
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}

		if ($body !== NULL)
		{
			//var_dump($body);
			//exit;

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		$result = curl_exec($ch);

		curl_close($ch);

		return $result;	
	}
}

 ?>