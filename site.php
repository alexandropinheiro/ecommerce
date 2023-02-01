<?php 

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;
use \Hcode\Security\Crypt;
use \Hcode\Utils\Session;
use \Hcode\Infrastructure\Client;
use \Spatie\ArrayToXml\ArrayToXml;

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
		$loadAddress = Session::getRegister(NULL);
	}
	else
	{
		if (!isset($_GET['zipcode'])){
			$_GET['zipcode'] = $cart->getdeszipcode();
		}

		if (isset($_GET['zipcode']) && $_GET['zipcode'] === ''){
			if (!$address->getdesaddress()) $address->setdesaddress('');
			if (!$address->getdesnumber()) $address->setdesnumber('');
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

	Session::setRegister($_POST);

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

	if (isset($_POST['desnumber']) && $_POST['desnumber'] === ''){
		Session::setError("Informe o número!");
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

	$cart = Cart::getFromSession();

	$cart->getCalculateTotal();

	$order = new Order();

	$order->setData([
		'idorder'=>0,
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);

	$order->save();

	switch ((int)$_POST['payment-method']) {
		case 1:
			header("Location: order/".$order->getidorder()."/pagseguro");
			break;
		
		case 2:
			header("Location: order/".$order->getidorder()."/paypal");
			break;
	}
	
	exit;

});

$app->get("/order/:idorder/pagseguro", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$nrphone = $order->getnrphone();

	$arrayBody = $order->getArrayForPagSeguro($cart);

	$xmlBody = ArrayToXml::convert($arrayBody);

	$indice = strpos($xmlBody, "<root>");

	$xmlBody = substr($xmlBody, $indice+6, strlen($xmlBody));

	$xmlBody = str_replace("</root>", "", $xmlBody);
	$xmlBody = str_replace("</items><items>", "", $xmlBody);

	$queryString = [
		'email'=>'alexandro.analista@gmail.com',
		'token'=>'EE0D8C1C1ED8400C90755651EAD6698E'
	];

	$client = new Client();

	$url = "https://ws.sandbox.pagseguro.uol.com.br/v2/checkout?" . http_build_query($queryString);
	$header = array("Content-Type: application/xml");

	$result = $client->post($url, $header, $xmlBody);

	var_dump($result);
	exit;

	$page = new Page([
		'header'=>false,
		'footer'=>false
	]);

	$page->setTpl("payment-pagseguro", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'phone'=>[
			'areaCode'=>substr($nrphone, 0, 2),
			'number'=>substr($nrphone, 0, strlen($nrphone))
		]
	]);

});

$app->get("/order/:idorder/paypal", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$page = new Page([
		'header'=>false,
		'footer'=>false
	]);

	$page->setTpl("payment-paypal", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);

});

$app->get("/login", function () {
	
	$page = new Page();

	$registerErrors = Session::getListError();

	$page->setTpl("login", [
		'error'=>Session::getError(),
		'hasListErrors'=>count($registerErrors) > 0 ? 1 : 0,
		'registerErrors' => $registerErrors,
		'registerValues'=>Session::getRegister([
				'name'=>'',
				'email'=>'',
				'phone'=>''
			  ])
	]);

});

$app->post("/login", function () {
	
	try {

		User::login($_POST['login'], $_POST['password']);
		$route = "/checkout";
		
	} catch (Exception $e) {

		Session::setError($e->getMessage());
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

	Session::setRegister($_POST);

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

	$registerErrors = Session::getListError();

	$userErrorMsg = Session::getError();

	$profileMessage = Session::getSuccess();

	if (count($registerErrors) > 0 || $userErrorMsg != '') {

		$formData = Session::getRegister([
				'desperson'=>'',
				'desemail'=>'',
				'nrphone'=>''
			  ]);

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

	Session::setRegister($_POST);

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

	Session::setSuccess('Dados alterados com sucesso!');

	header("Location: /profile");
	exit;
});

$app->get("/order/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get($idorder);

	$page = new Page();

	$page->setTpl("payment", [
		'order'=>$order->getValues()
	]);

});

$app->get("/boleto/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
	$valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal

	$valor_cobrado = str_replace(",", ".",$valor_cobrado);

	$valor_boleto=$valor_cobrado+$taxa_boleto;

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)

	$dadosboleto["valor_boleto"] = formatValue($valor_boleto); 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " " . $order->getdesstate() . " " . $order->getdescountry() . " - " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

	// NÃO ALTERAR!
	include($path . "funcoes_itau.php"); 
	include($path . "layout_itau.php");	

});

$app->get("/profile/orders/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$page = new Page();

	$page->setTpl("profile-orders-detail", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);
});

$app->get("/profile/orders", function(){

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile-orders", [
		'orders'=>$user->getOrders()
	]);
});

$app->get("/profile/change-password", function(){

	User::verifyLogin(false);

	$page = new Page();

	$page->setTpl("profile-change-password", [
		'changePassError'=>Session::getError(),
		'changePassSuccess'=>Session::getSuccess()
	]);

});

$app->post("/profile/change-password", function(){

	User::verifyLogin(false);

	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {
		Session::setError('Digite a senha atual.');
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === '') {
		Session::setError('Digite a nova senha.');
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') {
		Session::setError('Digite a confirmação da nova senha.');
		header("Location: /profile/change-password");
		exit;
	}

	if ($_POST['current_pass'] === $_POST['new_pass']) {
		Session::setError('A sua nova senha deve ser diferente da atual.');
		header("Location: /profile/change-password");
		exit;
	}

	if ($_POST['new_pass'] !== $_POST['new_pass_confirm']) {
		Session::setError('A nova senha não confirma.');
		header("Location: /profile/change-password");
		exit;
	}

	$user = User::getFromSession();

	if (Crypt::encryptPassword($_POST['current_pass']) !== $user->getdespassword()){

		Session::setError('A senha está inválida.');
		header("Location: /profile/change-password");
		exit;

	}

	if (Crypt::encryptPassword($_POST['new_pass']) === $user->getdespassword()){
		Session::setError('A senha está inválida.');
		header("Location: /profile/change-password");
		exit;
	}

	$user->setdespassword(Crypt::encryptPassword($_POST['new_pass']));

	$user->update();

	Session::setSuccess('Senha alterada com sucesso!');

	header("Location: /profile/change-password");
	exit;

});

 ?>