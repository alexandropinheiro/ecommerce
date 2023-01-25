<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Utils\Session;

$app->get('/admin', function() {
    
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index");

});

$app->get('/admin/login', function() {
    
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("login");

});

$app->post('/admin/login', function() {
    
	User::login($_POST['login'], $_POST['password']);

	header("Location: /admin");
	exit;
});

$app->get('/admin/logout', function() {
    
	User::logout();

	header("Location: /admin/login");
	exit;
});

$app->get('/admin/users/:iduser/password', function($iduser){

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-password", [
		'user'=>$user->getValues(),
		'msgError'=>Session::getError(),
		'msgSuccess'=>Session::getSuccessMessage()
	]);

});

$app->post('/admin/users/:iduser/password', function($iduser){

	User::verifyLogin();

	if (!isset($_POST['despassword']) || $_POST['despassword'] === ''){
		Session::setError('Preencha a nova senha!');
		header("Location: /admin/users/$iduser/password");
		exit;
	}

	if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === ''){
		Session::setError('Preencha confirmação da nova senha!');
		header("Location: /admin/users/$iduser/password");
		exit;
	}

	if ($_POST['despassword'] !== $_POST['despassword-confirm']){
		Session::setError('Confirme corretamente as senhas!');
		header("Location: /admin/users/$iduser/password");
		exit;
	}

	$user = new User();

	$user->get((int)$iduser);

	$user->setPassword($_POST['despassword']);

	Session::setSuccessMessage('Senha alterada com sucesso!');

	header("Location: /admin/users/$iduser/password");
	exit;

});

$app->get('/admin/users', function() {
    
	User::verifyLogin();

	$search = isset($_GET['search']) ? $_GET['search'] : '';
	$page = isset($_GET['page']) ? $_GET['page'] : 1;

	$pagination = User::getPage($search, $page);

	$pages = [];

	for ($i=1; $i <= $pagination['pages'] ; $i++) { 
		array_push($pages, [
			'href'=>'/admin/users?'.http_build_query([
										'page'=>$i,
										'search'=>$search
									]),
			'text'=>$i
		]);
	}

	$page = new PageAdmin();

	$page->setTpl("users", array(
		"users"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	));
});

$app->get('/admin/users/create', function() {
    
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("users-create");
});

$app->get('/admin/users/:iduser/delete', function($iduser) {
    
	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$user->delete();

	header("Location: /admin/users");	
	exit;
});

$app->get('/admin/users/:iduser', function($iduser) {
    
	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array(
		"user"=>$user->getValues()
	));
});

$app->post('/admin/users/create', function() {
    
	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->setData($_POST);

	$user->save();

	header("Location: /admin/users");	
	exit;

});

$app->post('/admin/users/:iduser', function($iduser) {
    
	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->get((int)$iduser);

	$user->setData($_POST);

	$user->update();

	header("Location: /admin/users");	
	exit;
});

$app->get('/admin/forgot', function() {

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot");

});

$app->post("/admin/forgot", function() {

	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
	exit;

});

$app->get("/admin/forgot/sent", function() {

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-sent");
});

$app->get('/admin/forgot/reset', function() {

	$code = $_GET['code'];

	$user = User::validForgotDecrypt($code);

	if ($user === NULL) 
	{
		header("Location: /admin/forgot/expirated");
		exit;
	}

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$code
	));

});

$app->post('/admin/forgot/reset', function() {

	$code = $_POST['code'];

	$forgot = User::validForgotDecrypt($code);

	User::setForgotUser($forgot['idrecovery']);

	$user = new User();

	$user->get((int)$forgot['iduser']);

	$user->setPassword($_POST['password']);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset-success");

});

$app->get("/admin/forgot/expirated", function() {

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-expirated");
});

 ?>