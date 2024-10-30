<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');

require_once('../lib/woocommerce-api.php');
$options = array(
    'ssl_verify' => false
);

$Cs_URL             = isset($_POST['Cs_URL']) ? filter_var($_POST['Cs_URL'], FILTER_SANITIZE_URL) : "";
$Title             = isset($_POST["Title"]) ? filter_var($_POST["Title"], FILTER_SANITIZE_STRING) : "";
$webshopConsumerKey = isset($_POST['webshopConsumerKey']) ? filter_var($_POST['webshopConsumerKey'], FILTER_SANITIZE_STRING) : "";
$webshopSecretKey   = isset($_POST['webshopSecretKey']) ? filter_var($_POST['webshopSecretKey'], FILTER_SANITIZE_STRING) : "";
$Type               = isset($_POST['Type']) ? filter_var($_POST['Type'], FILTER_SANITIZE_STRING) : "";
$Image_url          = isset($_POST['Image_url']) ? filter_var($_POST['Image_url'], FILTER_SANITIZE_URL) : "";
$Regular_price      = isset($_POST['Regular_price']) ? filter_var($_POST['Regular_price'], FILTER_SANITIZE_NUMBER_FLOAT) : "";
$Description        = isset($_POST['Description']) ? filter_var($_POST['Description'], FILTER_SANITIZE_STRING) : "";
$Short_description  = isset($_POST['Short_description']) ? filter_var($_POST['Short_description'], FILTER_SANITIZE_STRING) : "";
$CategoryName       = isset($_POST['CategoryName']) ? filter_var($_POST['CategoryName'], FILTER_SANITIZE_STRING) : "";
$Visibility         = isset($_POST['Visibility']) ? filter_var($_POST['Visibility'], FILTER_SANITIZE_STRING) : "";
$sku                = isset($_POST['sku']) ? filter_var($_POST['sku'], FILTER_SANITIZE_STRING) : "";

try {
	$client = new WC_API_Client($Cs_URL, $webshopConsumerKey, $webshopSecretKey, $options);

	if ($Type == "LIST") {
	    $or = $client->products->get();
	    print_r(json_encode($or));
	    die();
	}
	if ($Type == "CREATE") {
	    $img_url = $Image_url;
	    
	    $img_url_add;
	    $headers = @get_headers($img_url);
	    if (strpos($headers[0], '200') === false) {
	        $img_url_add = '';
	    } else {
	        $img_url_add = $Image_url;
	    }
	    
	    $data = array(
	        'product' => array(
	            'title' => $Title,
	            'type' => 'simple',
	            'regular_price' => $Regular_price,
	            'description' => $Description,
	            'short_description' => $Short_description,
	            'categories' => array(
	                $CategoryName
	            ),
	            'catalog_visibility' => $Visibility,
	            'sku' => $sku,
	            'images' => array(
	                array(
	                    'src' => $img_url_add,
	                    'position' => 0
	                ),
	                array(
	                    'src' => $img_url_add,
	                    'position' => 1
	                )
	            )
	        )
	    );
	    $res  = $client->products->create($data);
	    print_r(json_encode(array(
	        "MESSAGE" => "SUCCESS",
	        "DATA" => $res
	    )));
	    die();
	}


	if ($Type == "UPDATE") {
	    $img_url = $Image_url;
	    
	    $img_url_add;
	    $headers = @get_headers($img_url);
	    if (strpos($headers[0], '200') === false) {
	        $img_url_add = '';
	    } else {
	        $img_url_add = $Image_url;
	    }
	    
	    $data = array(
	        'product' => array(
	            'title' => $Title,
	            'type' => 'simple',
	            'regular_price' => $Regular_price,
	            'description' => $Description,
	            'short_description' => $Short_description,
	            'categories' => array(
	                $CategoryName
	            ),
	            'catalog_visibility' => $Visibility,
	            'sku' => $sku,
	            'images' => array(
	                array(
	                    'src' => $img_url_add,
	                    'position' => 0
	                ),
	                array(
	                    'src' => $img_url_add,
	                    'position' => 1
	                )
	            )
	        )
	    );
	    $res  = $client->products->update($_POST["Id"], $data);
	    print_r(json_encode(array(
	        "MESSAGE" => "SUCCESS",
	        "DATA" => $res
	    )));
	    die();
	}
} catch (Exception $e) {
	print_r(json_encode(array(
	        "MESSAGE" => "ERROR",
	        "DATA" => null
	    )));
	    die();
}

?>
