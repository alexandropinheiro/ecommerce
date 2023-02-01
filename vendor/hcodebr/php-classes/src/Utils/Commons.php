<?php 

namespace Hcode\Utils;

use Spatie\ArrayToXml\ArrayToXml;

class Commons
{
	
	public static function ArrayToXml($array)
	{
		if (!is_array($array)) return '';

		$xml = "";
		$show_dump = false;

		foreach ($array as $key => $value) {

			if ($key === 'areaCode'){

				$show_dump = true;

			}

			if (is_array($value)){

				return $xml . Commons::ArrayToXml($value);

			}
			else
			{
				var_dump("<" . $key, $value, "<" . $key . ">" . $value . "</" . $key . ">");
				exit;

				$xml = $xml . "<" . $key . ">" . $value . "</" . $key . ">";

			}
		}

		if ($show_dump){

				var_dump($xml);
				exit;

			}

		return $xml;
	}
}

 ?>