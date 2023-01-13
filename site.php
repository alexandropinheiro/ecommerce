<?php 

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;

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
		'products'=>$cart->getProducts()
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

 ?>