<?php 

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Security\Crypt;

$app->get('/', function() {
    
    $products = Product::listAll();

	$page = new Page();

	$page->setTpl("index", [
		'products'=>Product::checkList($products)
	]);

});

$app->get("/category/:idcategory", function($idcategory) {

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPaginated($page);

	$pages = [];

	for ($i=1; $i <= $pagination['pages']; $i++) 
	{ 
		array_push($pages, [
			'link'=>"/category/".$category->getidcategory()."?page=$i",
			'page'=>$i
		]);
	}

	$page = new Page();

	$page->setTpl("category", array(
		'category'=>$category->getValues(),
		'products'=>$pagination['data'],
		'pages'=>$pages
	));
});

$app->get("/products/:desurl", function($desurl) {

	$product = new Product();

	$product->getFromUrl($desurl);

	$page = new Page();

	$page->setTpl("product-detail", array(
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	));
});

$app->get("/cart", function() {

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);
});

$app->get("/cart/:idproduct/add", function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['quantity'])) ? (int)$_GET['quantity'] : 1;

	for ($i=0; $i < $qtd ; $i++) 
	{ 
		$cart->addProduct($product);
	}
	
	header("Location: /cart");
	exit;
});

$app->get("/cart/:idproduct/minus", function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);

	header("Location: /cart");
	exit;
});

$app->get("/cart/:idproduct/remove", function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;
});

$app->post("/cart/freight", function() {

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;
});

$app->get("/checkout", function () {
	
	User::verifyLogin(false);

	$cart = Cart::getFromSession();

	$address = new Address();

	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'error'=>''
	]);

});

$app->get("/login", function () {
	
	$page = new Page();

	$registerErrors = User::getRegisterError();

	$page->setTpl("login", [
		'error'=>User::getError(),
		'hasListErrors'=>count($registerErrors) > 0 ? 1 : 0,
		'registerErrors' => $registerErrors,
		'registerValues'=>User::getRegisterSession()
	]);

});

$app->post("/login", function () {
	
	try {

		User::login($_POST['login'], $_POST['password']);
		$route = "/checkout";
		
	} catch (Exception $e) {

		User::setError($e->getMessage());
		$route = "/login";

	}	

	header("Location: $route");
	exit;
});

$app->get("/logout", function () {
	
	User::logout();

	header("Location: /login");
	exit;
	
});

$app->post("/register", function(){

	User::setRegisterSession($_POST);
	User::clearRegisterError();

	$hasRegisterError = User::hasRegisterErrors($_POST);
	
	if ($hasRegisterError){

		header("Location: /login");
		exit;

	}

	$existsUser = User::getByEmail($_POST['email']);

	if ($existsUser != NULL){

		User::setError("Já existe um usuário registrado com esse e-mail. Clique em 'Esqueceu a Senha?' para recuperar o acesso.");
		header("Location: /login");
		exit;

	}

	$user = new User();

	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desemail'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'despassword'=>Crypt::encryptPassword($_POST['password']),
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	User::clearRegisterSession();

	header("Location: /login");
	exit;
});

 ?>