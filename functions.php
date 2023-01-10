<?php 

function formatPrice(float $vlprice)
{
	return "R$".number_format($vlprice, 2, ",", ".");
}

 ?>