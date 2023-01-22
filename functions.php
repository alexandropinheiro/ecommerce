<?php 

use \Hcode\Model\User;
use \Hcode\Model\Cart;

function formatPrice($vlprice)
{
	if (!$vlprice > 0) $vlprice = 0;

	return "R$".number_format($vlprice, 2, ",", ".");
}

function formatValue($vlprice)
{
	if (!$vlprice > 0) $vlprice = 0;

	return number_format($vlprice, 2, ",", ".");
}

function checkLogin($inadmin = true){

	return User::checkLogin($inadmin);

}

function getUserName() {

	$user = User::getFromSession();

	$user->get((int)$user->getiduser());

	return $user->getdesperson();
}

function getCartVlSubTotal()
{
	$cart = Cart::getFromSession();

	$totals = $cart->getProductTotals();

	return formatPrice($totals['vlprice']);
}

function getCartNrTotal()
{
	$cart = Cart::getFromSession();

	$totals = $cart->getProductTotals();

	return $totals['nrqtd'];
}

 ?>