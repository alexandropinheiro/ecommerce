<?php 

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Security\Crypt;
use \Hcode\Utils\Session;

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

	Cart::clearMsgError();

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;
});

$app->get("/checkout", function () {
	
	User::verifyLogin(false);

	$address = new Address();
	$cart = Cart::getFromSession();

	$erroMsg = Session::getError();

	if ($erroMsg !== ''){
		$loadAddress = Session::getAddressSession();
	}
	else
	{
		if (!isset($_GET['zipcode'])){
			$_GET['zipcode'] = $cart->getdeszipcode();
		}

		if (isset($_GET['zipcode']) && $_GET['zipcode'] === ''){
			if (!$address->getdesaddress()) $address->setdesaddress('');
			if (!$address->getdescomplement()) $address->setdescomplement('');
			if (!$address->getdesdistrict()) $address->setdesdistrict('');
			if (!$address->getdescity()) $address->setdescity('');
			if (!$address->getdesstate()) $address->setdesstate('');
			if (!$address->getdescountry()) $address->setdescountry('');
			if (!$address->getdeszipcode()) $address->setdeszipcode('');
		}

		if (isset($_GET['zipcode'])){
			$address->loadFromCep($_GET['zipcode']);

			$cart->setdeszipcode($_GET['zipcode']);

			$cart->save();

			$cart->getCalculateTotal();
		}

		$loadAddress = $address->getValues();
	}	

	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$loadAddress,
		'products'=>$cart->getProducts(),
		'error'=>$erroMsg
	]);

});

$app->post("/checkout", function() {

	User::verifyLogin(false);

	Session::setAddressSession($_POST);

	$verify = true;

	if (isset($_POST['zipcode']) && $_POST['zipcode'] === ''){
		Session::setError("Informe o CEP!");
		header("Location: /checkout");
		exit;
	}

	if (isset($_POST['desaddress']) && $_POST['desaddress'] === ''){
		Session::setError("Informe o endereço!");
		header("Location: /checkout");
		exit;
	}

	if (isset($_POST['descomplement']) && $_POST['descomplement'] === ''){
		Session::setError("Informe o complemento!");
		header("Location: /checkout");
		exit;
	}

	if (isset($_POST['desdistrict']) && $_POST['desdistrict'] === ''){
		Session::setError("Informe o bairro!");
		header("Location: /checkout");
		exit;
	}

	if (isset($_POST['descity']) && $_POST['descity'] === ''){
		Session::setError("Informe a cidade!");
		header("Location: /checkout");
		exit;
	}

	if (isset($_POST['desstate']) && $_POST['desstate'] === ''){
		Session::setError("Informe o estado!");
		header("Location: /checkout");
		exit;
	}

	if (isset($_POST['descountry']) && $_POST['descountry'] === ''){
		Session::setError("Informe o país!");
		header("Location: /checkout");
		exit;
	}

	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	header("Location: /order");
	exit;

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

$app->get('/forgot', function() {

	$page = new Page();

	$page->setTpl("forgot");

});

$app->post("/forgot", function() {

	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit;

});

$app->get("/forgot/sent", function() {

	$page = new Page();

	$page->setTpl("forgot-sent");
});

$app->get('/forgot/reset', function() {

	$code = $_GET['code'];

	$user = User::validForgotDecrypt($code);

	if ($user === NULL) 
	{
		header("Location: /forgot/expirated");
		exit;
	}

	$page = new Page();

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$code
	));

});

$app->post('/forgot/reset', function() {

	$code = $_POST['code'];

	$forgot = User::validForgotDecrypt($code);

	User::setForgotUser($forgot['idrecovery']);

	$user = new User();

	$user->get((int)$forgot['iduser']);

	$user->setPassword($_POST['password']);

	$page = new Page();

	$page->setTpl("forgot-reset-success");

});

$app->get("/forgot/expirated", function() {

	$page = new Page();

	$page->setTpl("forgot-expirated");
});

$app->get("/profile", function() {

	User::verifyLogin(false);

	$registerErrors = User::getRegisterError();

	$userErrorMsg = User::getError();

	$profileMessage = Session::getSuccessMessage();

	if (count($registerErrors) > 0 || $userErrorMsg != '') {

		$formData = User::getProfileSession();

	} else {

		$user = User::getFromSession();
		$formData = $user->getValues();

	}

	$page = new Page();

	$page->setTpl("profile", [
		'user'=>$formData,
		'profileMsg'=>$profileMessage,
		'hasListErrors'=>count($registerErrors) > 0 ? 1 : 0,
		'registerErrors' => $registerErrors,
		'profileError'=>$userErrorMsg
	]);
});

$app->post("/profile", function() {

	User::verifyLogin(false);

	User::setProfileSession($_POST);
	User::clearRegisterError();

	$user = User::getFromSession();

	$hasProfileErrors = User::hasProfileErrors($_POST, $user);

	if ($hasProfileErrors){
		header("Location: /profile");
		exit;
	}

	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();

	$user->setData($_POST);

	$user->update();

	Session::setSuccessMessage('Dados alterados com sucesso!');

	header("Location: /profile");
	exit;
});



 ?>