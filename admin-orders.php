<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;
use \Hcode\Model\Cart;
use \Hcode\Utils\Session;

$app->get("/admin/orders/:idorder/delete", function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$order->delete();

	header("Location: /admin/orders");
	exit;
	
});

$app->get("/admin/orders/:idorder/status", function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$page = new PageAdmin();

	$page->setTpl("order-status", [
		'order'=>$order->getValues(),
		'status'=>OrderStatus::listAll(),
		'msgError'=>Session::getError(),
		'msgSuccess'=>Session::getSuccessMessage()
	]);
	
});

$app->post("/admin/orders/:idorder/status", function($idorder){

	User::verifyLogin();

	if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0){
		Session::setError('Informe o status atual.');
		header("Location: /admin/orders/$idorder/status");
		exit;
	}

	$order = new Order();

	$order->get((int)$idorder);

	$order->setidstatus((int)$_POST['idstatus']);

	$order->save();

	Session::setSuccessMessage('Status alterado com sucesso!');
	header("Location: /admin/orders/$idorder/status");
	exit;
	
});

$app->get("/admin/orders/:idorder", function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$page = new PageAdmin();

	$page->setTpl("order", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);
	
});

$app->get("/admin/orders", function(){

	User::verifyLogin();

	$search = isset($_GET['search']) ? $_GET['search'] : '';
	$page = isset($_GET['page']) ? $_GET['page'] : 1;

	$pagination = Order::getPage($search, $page);

	$pages = [];

	for ($i=1; $i <= $pagination['pages'] ; $i++) { 
		array_push($pages, [
			'href'=>'/admin/orders?'.http_build_query([
										'page'=>$i,
										'search'=>$search
									]),
			'text'=>$i
		]);
	}

	$page = new PageAdmin();

	$page->setTpl("orders", [
		'orders'=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	]);

});

 ?>