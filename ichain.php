<?php

/**
 * @package BoxRunner
 */
/*
  Plugin Name: BoxRunner
  Plugin URI: http://boxrunner.net/
  Description: BoxRunner is a plugin to integrate your woocommerce web shop with BoxRunner warehouse management system (http://boxrunner.net).Through this plugin you can sync your products to the BoxRunner system and manage your warehouse. It manages products, categorys, picking, packing and shipping your products .Also users can make sales order in the webshop, we process the order in BoxRunner. Furthermore if user adds a new product in warehouse side, it automatically sync to the web shop after the configuration is successfully done. This plugin is really helpful for the user to manage their web shop and warehouse.
  Version: 1.0.1
  Author: Crowderia (pvt) Ltd
  Author URI: http://crowderia.com/
  License: GPLv2 or later
  Text Domain: BoxRunner
 */

const BOXRUNNER_API_URL = "http://app.boxrunner.net:8080/ws/CrowdChain.svc";
const RABBIT_API_URL = "http://messagapi.boxrunner.net/send.php";
const SHIPPING_LABEL_PATH = "http://app.boxrunner.net:8080/boxrunner";

function wp_bxr_my_theme_enqueue_styles() {
    
    $parent_style = 'parent-style';

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        plugins_url() . '/boxrunner/public/css/boxrunner-public.css',
        array( $parent_style )
    );
}

add_action( 'wp_enqueue_scripts', 'wp_bxr_my_theme_enqueue_styles' );
add_action('init','ava_test_init');
function ava_test_init() {
    wp_enqueue_script( 'boxrunner-public', plugins_url( '/public/js/boxrunner-public.js', FILE ));
}
function wc_bxr_get_shipping_methods(){
    $wc_bxr_token=wc_bxr_getiChain_token_auth();
    $result = wc_bxr_get_boxrunner_meta();
    $wc_bxr_party_id = $result->party_id;
    $wc_bxr_base_url = $result->url;

    $wc_bxr_url = $wc_bxr_base_url . '/shipment.companyshipmentservices?' . http_build_query(array('partyid' => $wc_bxr_party_id, 'token' => wc_bxr_getiChain_token_auth()), '', '&');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $wc_bxr_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $bxr_shipping_data = curl_exec($ch);
    curl_close($ch);
    $bxr_shipping_model = json_decode($bxr_shipping_data);

    return $bxr_shipping_model->DataList;
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    /* BoxRunner ecornomy shipping method */
    function crowd_shipping_method_init() {
        if (!class_exists('WC_Crowderia_Shipping_Method')) {
            class WC_Crowderia_Shipping_Method extends WC_Shipping_Method {
                public function __construct() {
                    $this->id = 'boxrunner_cheapest ';
                    $this->method_title = __('Crowd Shipping Method');
                    $this->method_description = __('This is the crowderia own shipping method for the WooCommerce');

                    $this->enabled = "yes";
                    $this->title = "BoxRunner Ecornomy";

                    $this->init();
                }

                function init() {
                    $this->init_form_fields();
                    $this->init_settings();
                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                }

                public function calculate_shipping($package) {
                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title."(".wc_bxr_get_shipping_methods()[0]->Duration."days)",
                        'cost' =>wc_bxr_get_shipping_methods()[0]->Cost,
                        'calc_tax' => 'per_item'
                    );
                    $this->add_rate($rate);
                }
            }
        }
    }

    /* BoxRunner ecornomy shipping method */

    function crowd_fast_shipping_method_init() {
        if (!class_exists('WC_Crowderia_Shipping_Method_Fastest')) {
            class WC_Crowderia_Shipping_Method_Fastest extends WC_Shipping_Method {
                public function __construct() {
                    $this->id = 'boxruner_fastest';
                    $this->method_title = __('Crowd Shipping Method');
                    $this->method_description = __('This is the crowderia own shipping method for the WooCommerce');

                    $this->enabled = "yes";
                    $this->title = "BoxRunner Express";

                    $this->init();
                }
                function init() {
                    $this->init_form_fields();
                    $this->init_settings();
                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                }
                public function calculate_shipping($package) {
                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title."(".wc_bxr_get_shipping_methods()[1]->Duration."days)",
                        'cost' => wc_bxr_get_shipping_methods()[1]->Cost,
                        'calc_tax' => 'per_item'
                    );
                    $this->add_rate($rate);
                }
            }
        }
    }

    /* BoxRunner Express shipping method */
    /* Actions & filters*/
    add_action('woocommerce_shipping_init', 'crowd_shipping_method_init');
    function add_your_shipping_method($methods) {
        $methods[] = 'WC_Crowderia_Shipping_Method';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'add_your_shipping_method');

    add_action('woocommerce_shipping_init', 'crowd_fast_shipping_method_init');
    function add_your_shipping_method_fast($methods) {
        $methods[] = 'WC_Crowderia_Shipping_Method_Fastest';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'add_your_shipping_method_fast');
    /* Actions */
}

function wc_bxr_get_boxrunner_meta(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'ichain_settings';
    $result = $wpdb->get_results("SELECT * FROM $table_name")[0];
    $password =filter_var($result->password, FILTER_SANITIZE_STRING);
    $username=filter_var($result->username, FILTER_SANITIZE_STRING);

    if (isset($password)&& isset($username)) {
        return $result;
    }else{
        return null;
    }
    
}
function wc_bxr_get_boxrunner_api_meta(){
    global $wpdb;
    $rest_api=$wpdb->get_results("SELECT option_value FROM wp_options WHERE option_name='woocommerce_api_enabled'");
    if (isset($rest_api)) {
        return $rest_api;
    }else{
        return null;
    }
}

function wc_bxr_getiChain_token() {
    $result = wc_bxr_get_boxrunner_meta();
    if ($result!=null) {
        $params = array(
            "AccessPassWord" => $result->password,
            "AccessUser" => $result->username,
        );
        $data_string = json_encode($params);
        $ch = curl_init($result->url . "/token");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );

        $res = curl_exec($ch);
        $token_info = json_decode($res)->Data;

        if (!empty($token_info->Token)){
            return $token_info->Token;
        }
        else{
            return null;
        }
    }
    
}

function wc_bxr_getiChain_token_auth() {
    $result = wc_bxr_get_boxrunner_meta();
    if ($result!=null) {
        $params = array(
            "AccessPassWord" => $result->password,
            "AccessUser" => $result->username,
        );
        $data_string = json_encode($params);
        $ch = curl_init($result->url . "/token.auth");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );

        $res = curl_exec($ch);
        $token_info = json_decode($res)->Data;

        if (!empty($token_info->Token)){
            return $token_info->Token;
        }
        else{
            return null;
        }
    }
}

add_action('admin_notices', 'wc_bxr_my_admin_notice');

function wc_bxr_my_admin_notice() {
    global $pagenow;
    ?>
    <script type="text/javascript">
        var divsToHide = document.getElementsByClassName("update-nag");
        for (var i = 0; i < divsToHide.length; i++)
        {
            divsToHide[i].style.display = "none";
        }
    </script>
    <?php
}

add_action('wp_footer', 'wc_bxr_themeslug_enqueue_style');

function wc_bxr_themeslug_enqueue_style() {
    ?>
    <script type="text/javascript">
    </script>
    <?php
}

//Crowd chane category updater function 
/*add_action('init', 'wc_bxr_create_wc_category');

function wc_bxr_create_wc_category() {
    $user_admin=current_user_can('administrator');
    if($user_admin){
        $tag_name=isset($_POST["tag-name"]) ? filter_var($_POST["tag-name"], FILTER_SANITIZE_STRING):'';
        $description=isset($_POST["description"]) ? filter_var($_POST["description"], FILTER_SANITIZE_STRING):'';

        if (isset($tag_name) && isset($description) ) {

            wc_bxr_add_product_cat_api_call($tag_name,$description);
        }
    }
}*/

function wc_bxr_boxrunner_admin_notice__error() {
    $class = 'notice notice-error';
    $message = __( 'Keys not submited.', 'sample-text-domain' );

    printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
}

function wc_bxr_boxrunner_admin_notice__success() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Keys submited!', 'sample-text-domain' ); ?></p>
    </div>
    <?php
}

function wc_bxr_boxrunner_admin_notice_connect__error() {
    $class = 'notice notice-error';
    $message = __( 'Sorry, unable to connect to BoxRunner. Please try again.', 'sample-text-domain' );

    printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
}

function wc_bxr_boxrunner_admin_notice_connect__success() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Successfully connected to BoxRunner.', 'sample-text-domain' ); ?></p>
    </div>
    <?php
}

function wc_bxr_boxrunner_admin_notice_info__success() {
    ?>
    <div class="notice notice-info is-dismissible">
        <p><?php _e( 'Please submit your keys using <b>submit keys</b> button.', 'sample-text-domain' ); ?></p>
    </div>
    <?php
}

/* Added function */
add_action('init', 'wc_bxr_product_sink_all');

function wc_bxr_product_sink_all() {
    $user_admin=current_user_can('administrator');
    $retrieved_nonce = $_REQUEST['_wpnonce'];
    if($user_admin){
        if (isset($_POST["sinkall"]) && $_POST["sinkall"] == "Sync All Products"  && wp_verify_nonce($retrieved_nonce, 'wc_bxr_base_nonce')) {
            $result = wc_bxr_get_boxrunner_meta();
            $party_id = $result->party_id;

            $full_product_list = array();
            $loop = new WP_Query(array('post_type' => array('product', 'product_variation'), 'posts_per_page' => -1));

            while ($loop->have_posts()) : $loop->the_post();
                $theid = get_the_ID();
                $product = new WC_Product($theid);
                if (get_post_type() == 'product_variation') {
                    $parent_id = wp_get_post_parent_id($theid);
                    $sku = get_post_meta($theid, '_sku', true);
                    $selling_price = get_post_meta($theid, '_stock_status', true);
                    $thetitle = get_the_title($parent_id);
                    // ****** Some error checking for product database *******
                    if ($sku == '') {
                        if ($parent_id == 0) {
                            // Remove unexpected orphaned variations.. set to auto-draft
                            $false_post = array();
                            $false_post['ID'] = $theid;
                            $false_post['post_status'] = 'auto-draft';
                            wp_update_post($false_post);
                            if (function_exists(add_to_debug))
                                add_to_debug('false post_type set to auto-draft. id=' . $theid);
                        } else {
                            // there's no sku for this variation > copy parent sku to variation sku
                            // & remove the parent sku so the parent check below triggers
                            $sku = get_post_meta($parent_id, '_sku', true);
                            if (function_exists(add_to_debug))
                                add_to_debug('empty sku id=' . $theid . 'parent=' . $parent_id . 'setting sku to ' . $sku);
                            update_post_meta($theid, '_sku', $sku);
                            update_post_meta($parent_id, '_sku', '');
                        }
                    }
                    // ****************** end error checking *****************
                } else {
                    $sku = get_post_meta($theid, '_sku', true);
                    $selling_price = get_post_meta($theid, '_sale_price', true);
                    $regular_price = get_post_meta($theid, '_regular_price', true);
                    if ($selling_price == null) {
                        $selling_price = $regular_price;
                    }
                    $description = get_the_content();
                    $thetitle = get_the_title();

                    $term_list = wp_get_post_terms($theid, 'product_cat', array('fields' => 'ids'));
                    $cat_id = (int) $term_list[0];
                    $cat_link = get_term_link($cat_id, 'product_cat');

                    $pieces = explode('/', $cat_link);

                    $length = (int) count($pieces);
                    $cat_name = $pieces[$length - 2];

                    $thumb_id = get_post_thumbnail_id($theid);
                    $url = wp_get_attachment_thumb_url($thumb_id);
                }
                // add product to array but don't add the parent of product variations
                if (!empty($sku))
                    $full_product_list[] = array("PartyID" => (int) $party_id, "Description" => $description,
                        "ExternalNumber" => $theid, "ProductName" => $thetitle, "sku" => $sku,
                        "RegularPrice" => $regular_price, "SellingPrice" => $selling_price,
                        "ExternalProductCategoryId" => $cat_id, "ExternalProductCategoryName" => $cat_name, "img" => $url);
            endwhile;
            wp_reset_query();
            $full_product_list_with_token = array("Token" => wc_bxr_getiChain_token_auth(), "ProductList" => $full_product_list);
            sort($full_product_list);
            $data_string = json_encode($full_product_list_with_token);
            $ch = curl_init($result->url . "/product.syncallproducts");
            //$ch = curl_init(RABBIT_API_URL);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
            );
            $res = curl_exec($ch);

            $info = json_decode($res);
        }
    }   
}

/* Added function */
/* PDF */
add_action('init', 'wc_bxr_download_pdf_gen');
function wc_bxr_download_pdf_gen() {
    $user_awc_bxr_dmin=current_user_can('administrator');
     $action=filter_var($_GET['action'],FILTER_SANITIZE_STRING);
    if($user_admin && isset($action)){
        if ($action == "generate_wpo_wcpdf") {
            $result = wc_bxr_get_boxrunner_meta();

            $base_url = $result->url;
            $party = $result->party_id;
            if (isset($base_url) && isset($party) && isset($result) && isset($_GET['template_type'])) {
                if (isset($_GET['template_type'])? $_GET['template_type'] :''== "invoice") {
                    $url = $base_url . '/salesorder.getorderdocument?' . http_build_query(array('orderNumber' => $_GET['order_ids'], 'partyid' => $party, 'token' => wc_bxr_getiChain_token_auth()), '', '&');
                } else if ($_GET['template_type'] == "packing-slip") {
                    $url = null;
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $pdf_url = curl_exec($ch);
                curl_close($ch);
                $url_prefix = json_decode($pdf_url);
                $exat_url = SHIPPING_LABEL_PATH . $url_prefix->Data;
                ?>
                <script type="text/javascript">
                    window.location.assign("<?php echo $exat_url; ?>");
                </script>    
                <?php
                exit();
            }
            
        }
    }
}

/* PDF */
add_action('post_updated', 'check_updates', 10, 3);

function check_updates($post_ID, $post_after, $post_before) {
    $user_admin=current_user_can('administrator');
    if($user_admin){

        if (in_array($post_after->post_type, array('product'))) {
            $result = wc_bxr_get_boxrunner_meta();
            $party_id = $result->party_id;

            $thumb_id = get_post_thumbnail_id($post_ID);
            $url = wp_get_attachment_thumb_url($thumb_id);
            $temp = $_POST["tax_input"]["product_cat"][1];
            $term = get_term_by('id', $temp, 'product_cat');
            $ProductCategoryName = $term->name;

           $product_info = array(
                "AgreementID"=>0,
                "BasicPrice"=>  $_POST["_regular_price"],
                "BuyingPrice"=> (float)"",
                "Description"=> $_POST["post_content"],
                "ExternalNumber"=> (int)$_POST["ID"],
                "ExternalProductCategoryId"=>$_POST["tax_input"]["product_cat"][1],
                "ExternalProductCategoryName"=>$ProductCategoryName,
                "InternalNumber"=>"",
                "PartyID"=> (int)$party_id,
                "ProductID"=> 0, 
                "ProductName"=> $_POST["post_title"],
                "ProductImage" => $url,
                "SellingPrice"=> (float)$_POST["_sale_price"],
                "Token"=>wc_bxr_getiChain_token_auth(),
                "Weight" => floatval($_POST["_weight"])
            );

            $data_string = json_encode($product_info);

            $ch = curl_init($result->url . "/product.create");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
            );

            $res = curl_exec($ch);
            $info = json_decode($res);
            if ($info->Success) {
                global $status;
                $status["MSG"] = "Prduct updated in wharehouse";
            }
        }
    }
}

add_action('template_redirect', 'wc_bxr_custom_redirect_after_purchase_order');

function wc_bxr_custom_redirect_after_purchase_order() {
    global $wp;
    $result = wc_bxr_get_boxrunner_meta();
    $party_id = $result->party_id;
    $user_admin=current_user_can('administrator');
    //if($user_admin){

        if (is_checkout() && !empty($wp->query_vars['order-received'])) {
            $order_id = absint($wp->query_vars['order-received']);
            $order_key = wc_clean($_GET['key']);

            $order = new WC_Order($order_id);
            $shipping_address = preg_split('/<br[^>]*>/i', $order->get_shipping_address());

            $ComapnyServiceId=0;
            if ($ComapnyService=='BoxRunner Ecornomy(1days)') {
                $ComapnyServiceId=0;
            }else if($ComapnyService=='BoxRunner Express(1days)'){
                $ComapnyServiceId=1;
            }

            $item_id = 0;
            foreach ($order->get_items() as $item) {
                $order_item_arr = wc_bxr_orderIetmCreate($order, $item, $result, $info, $item_id);
                $order_item[] = $order_item_arr;
                $item_id++;
            }
            
            $user_billing_detail=get_post_meta($order->get_order_number());

            $order_info = array(
                    "BuyerID"=> (int)$order->user_id,
                       "DeliveryAddress"=> array(
                            "AddressLine1"=> isset($order->billing_address_1) ? $order->billing_address_1 : $user_billing_detail['_billing_address_1'][0],
                            "AddressLine2"=> isset($order->billing_address_2) ? $order->billing_address_2 : $user_billing_detail['_billing_address_2'][0],
                            "City"=> isset($order->billing_city) ? $order->billing_city : $user_billing_detail['_billing_city'][0],
                            "PostalCode"=> isset($order->billing_postcode) ? $order->billing_postcode : $user_billing_detail['_billing_postcode'][0],
                            "Country"=> WC()->countries->countries[$order->shipping_country],
                            "Name"=> isset($shipping_address[0]) ? $shipping_address[0] : $user_billing_detail['_billing_first_name'][0],
                            "Email" => isset($order->billing_email) ? $order->billing_email : $user_billing_detail['_billing_email'][0],
                            "PhoneNumber" => isset($order->billing_phone) ? $order->billing_phone : $user_billing_detail['_billing_phone'][0],
                        ),
                    "CollectionName"=>isset($shipping_address[0]) ? $shipping_address[0] : $user_billing_detail['_billing_first_name'][0],
                    "CreatedBy"=> $order->billing_email,
                    "Description"=> "",
                    "OrderID" => (int)"",
                    "OrderNumber"=> $order->get_order_number(),
                    "PartyID"=> (int)$party_id,
                    "SellerID"=> 0,
                    "Token"=> wc_bxr_getiChain_token_auth(),
                    "OrderItems"=>$order_item,
                    "ComapnyServiceId"=>$ComapnyServiceId
                );

            $data_string = json_encode($order_info);

            $ch = curl_init($result->url . "/salesOrder.createorderwithorderitems");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
            );

            $res = curl_exec($ch);
            $info = json_decode($res);
        }
    //}
}

add_action('init', 'wc_bxr_update_party');

function wc_bxr_update_party() {
    global $wpdb;   
    $table_name = $wpdb->prefix . 'ichain_settings';
    $user_admin=current_user_can('administrator');
    $retrieved_nonce = $_REQUEST['_wpnonce'];
    if($user_admin && wp_verify_nonce($retrieved_nonce, 'wc_bxr_base_nonce')){
        if (wc_bxr_get_boxrunner_meta()!=null && isset($_POST["updateparty"])) {
            if (isset($_POST["updateparty"])? $_POST["updateparty"]:''== "Connect to BoxRunner") {
                $result = wc_bxr_get_boxrunner_meta();

                $user_info = array(
                    "UserName" => isset($_POST["api_username"])? $_POST["api_username"] :'',
                    "UserPassWord" => isset($_POST["api_password"])? $_POST["api_password"] :''
                );

                $data_string = json_encode($user_info);

                $ch = curl_init($result->url . "/get.org");
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
                );

                $res = curl_exec($ch);
                $info = json_decode($res);
                
                if ($info->Data != null) {
                    $party_id = $info->Data->PartyID;
                    $wpdb->update(
                            $table_name, array(
                        'party_id' => $party_id,
                        'flag' => "1"
                            ), array('ID' => 1), array(
                        '%s',
                        '%s'
                            ), array('%d')
                    );
                } else {
                    echo $info->Data;
                }
                if ($info->Success==true) {
                    wc_bxr_boxrunner_admin_notice_connect__success();
                }else{
                    wc_bxr_boxrunner_admin_notice_connect__error();
                }
            }
        }
    }
}

add_action('init', 'wc_bxr_update_keys');

function wc_bxr_update_keys() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ichain_settings';
    $user_admin=current_user_can('administrator');
    $retrieved_nonce = $_REQUEST['_wpnonce'];
    if($user_admin && wp_verify_nonce($retrieved_nonce, 'wc_bxr_key_nonce')){
        $submit_key=isset($_POST["submit-key"]) ? filter_var($_POST["submit-key"],FILTER_SANITIZE_STRING): '';
        if ($submit_key== "Submit Keys" && $submit_key!='') {
            $result = wc_bxr_get_boxrunner_meta();

            $key_info = array(
                "CrowdShopURL" => get_site_url(),
                "PartyID" => $result->party_id,
                "SubscriptionID" => 1,
                "Token" => wc_bxr_getiChain_token_auth(),
                "ConsumerKey" => isset($_POST["consumer-key"])? filter_var($_POST["consumer-key"],FILTER_SANITIZE_STRING) :'',
                "ConsumerSecret" => isset($_POST["consumer-secret"])? filter_var($_POST["consumer-secret"],FILTER_SANITIZE_STRING) :'' 
            );
            $data_string = json_encode($key_info);

            $ch = curl_init($result->url . "/subscription.meta");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
            );

            $res = curl_exec($ch);
            $info = json_decode($res);

            if ($info->Success==false) {
                boxrunner_admin_notice__error();
            }else{
                $wpdb->update(
                        $table_name, array(
                    'flag' => "2"
                        ), array('ID' => 1), array(
                    '%s'
                        ), array('%d')
                );
                wc_bxr_boxrunner_admin_notice__success();
            }
        }
    }
}

function wc_bxr_orderIetmCreate($order, $item, $result, $info, $item_id) {
    $order_info = array(
        "OrderItemID" => 0,
        "OrderID" => 0,
        "ProductID" => (int) "",
        "Quantity" => (int) $item['qty'],
        "CreatedBy" => $order->billing_email,
        "PartyID" => (int) $result->party_id,
        "OrderNumber" => $order->get_order_number(),
        "ExternalProductID" => (int) $item["product_id"],
        //"Token" => wc_bxr_getiChain_token_auth()
    );

    $data_string = json_encode($order_info);
    return $order_info;
}

add_action('admin_menu', 'wc_bxr_my_plugin_menu');

function wc_bxr_my_plugin_menu() {
    add_options_page('iChain Options', 'BoxRunner', 'manage_options', 'ichain-plugin.php', 'wc_bxr_my_plugin_page');
}

function wc_bxr_my_plugin_page() {

    global $wpdb;
    /* Get corrent user id's */
    global $current_user;
    get_currentuserinfo();
    $user_id = (int) $current_user->ID;
    $consumer_key = get_user_meta($user_id, 'woocommerce_api_consumer_key', true);
    $consumer_secret = get_user_meta($user_id, 'woocommerce_api_consumer_secret', true);

    $bxr_key = $wpdb->get_row( $wpdb->prepare("
    SELECT consumer_key, consumer_secret, permissions
    FROM {$wpdb->prefix}woocommerce_api_keys
    WHERE user_id = $user_id", $user_id), ARRAY_A);

    if ($bxr_key !=null) {
        $consumer_key=$bxr_key[consumer_key];
        $consumer_secret=$bxr_key[consumer_secret];
    }
    /* Get corrent user id's */

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'ichain_settings';

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      url varchar(255) DEFAULT '' NOT NULL,
      username varchar(255)  NOT NULL,
      password varchar(255)  NOT NULL,
      party_id varchar(255)  NOT NULL,
      flag varchar(255)  NOT NULL,
      UNIQUE KEY id (id)
    )";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
    $retrieved_nonce = $_REQUEST['_wpnonce'];
    $result = $wpdb->get_results("SELECT * FROM $table_name")[0];
    if (isset($_POST["ichain_page"]) && wp_verify_nonce($retrieved_nonce, 'wc_bxr_base_nonce')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ichain_settings';
        $result = $wpdb->get_results("SELECT * FROM $table_name")[0];
        if ($result == null) {
            $wpdb->insert($table_name, array(
                'url' => BOXRUNNER_API_URL,
            ));
        }
        $wpdb->update(
                $table_name, array(
            'url' => BOXRUNNER_API_URL,
            'username' => isset($_POST['api_username'])? filter_var($_POST['api_username'],FILTER_SANITIZE_STRING) :'',
            'password' => isset($_POST['api_password'])? filter_var($_POST['api_password'],FILTER_SANITIZE_STRING):'',
                ), array('ID' => 1), array(
            '%s',
            '%s',
            '%s',
                ), array('%d')
        );
        $result = $wpdb->get_results("SELECT * FROM $table_name")[0];
    }
    ?>
    <?php 
    $user_admin=current_user_can('administrator');
    if($user_admin){
    ?>
    <div class="wrap">
        <h2>BoxRunner API Settings (Enter your BoxRunner user credentials)</h2>
        <form method="post" action="#" novalidate="novalidate" autocomplete="false">
            <input type="hidden" name="ichain_page" value="general">
            <input type="hidden" name="action" value="update">
            <?php wp_nonce_field('wc_bxr_base_nonce'); ?>
            <input type="hidden" name="_wp_http_referer" value="">
    <?php if ($result->party_id == null) { ?>
                <h2 style="border-left: 5px solid #F72B2B;padding: 10px;font-size: 17px;font-weight: 600;" class="notice"> You are not connected to BoxRunner</br><span style="font-size: 14px !important;">Please enter correct Boxrunner username and password and click "Connect to BoxRunner" button.</br>If you don`t have a Boxrunner account please sign up. <a>http://boxrunner.net</a></span></h2>
    <?php } ?>  
            <table class="form-table">
                <tbody>
                    <tr style="display:none;">
                        <th scope="row" >
                            <label for="api_url">API URL</label>
                        </th>
                        <td>
                            <input name="api_url" type="text" id="api_url" value="<?php echo $result->url; ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="api_username">Username</label>
                        </th>
                        <td>
                            <input name="api_username" type="text" id="api_username" aria-describedby="tagline-description" autocomplete="nope" value="<?php echo $result->username; ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="api_password">Password</label>
                        </th>
                        <td>
                            <input name="api_password" type="password" autocomplete="nope" id="api_password" value="<?php echo $result->password; ?>" class="regular-text code">
                        </td>
                    </tr>
                </tbody>
            </table>
            <!-- <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p> -->
            <!-- /*Added function*/ -->
            <table>
                <tbody>
                    <tr>
                        <th scope="row">
                            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
                        </th>
                        <?php if ($result->party_id != null) { ?>
                            <td>
                                <p class="submit"><input type="submit" name="sinkall" id="sinkall" class="button button-primary" value="Sync All Products"></p>
                            <td>
                        <?php } ?>
                        <?php if ($result->party_id == null) { ?>
                            <td>
                                <p class="submit"><input type="submit" name="updateparty" id="updateparty" class="button button-primary" value="Connect to BoxRunner"></p>
                            <td>
                        <?php } ?>
                    </tr>
                </tbody>
            </table>
            <!-- /*Added function*/ -->
        </form>
        <!-- New form for submit keys to the boxrunner -->
        <h2 style="">Submit the Woocommerce API keys</h2>
        <?php if (wc_bxr_get_boxrunner_api_meta()[0]->option_value!="yes") { ?>
            <div class="updated" style="padding: 10px;font-size: 13px;font-weight: 700;">You have not enabled rest api. Please enable it<span style="font-size: 12px;margin-top: 20px !important;"> ( Woocommerce -> Settings -> Enable Rest API )</span></br></div>
        <?php } ?>

        <?php if ($consumer_key== null ||$consumer_secret==null) { ?>
            <div class="updated" style="padding: 10px;font-size: 13px;font-weight: 700;">You have not generated API keys. Please generate it.<span style="font-size: 12px;">  (  User -> User name -> Edit user -> Enable generate API keys )</span></div>
        <?php } ?>

        <?php if ($consumer_key!= null && $consumer_secret!=null && wc_bxr_get_boxrunner_api_meta()[0]->option_value=="yes" && $result->flag=="1") { ?>
            <div class="updated" style="border-left: 5px solid #008ec2; padding: 10px;font-size: 13px;font-weight: 700;">Please submit your keys by clicking the "Submit Keys" button.</div>
        <?php } ?>

        <form method="post" action="#" novalidate="novalidate">
        <?php wp_nonce_field('wc_bxr_key_nonce'); ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="consumer-key">Consumer Key</label>
                        </th>
                        <td>
                            <input name="consumer-key" type="text" id="consumer-key" onblur="verify()" aria-describedby="tagline-description" value="" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="consumer-key">Consumer Secret</label>
                        </th>
                        <td>
                            <input  name="consumer-secret" type="text" id="consumer-secret" onblur="verify()" aria-describedby="tagline-description" value="" class="regular-text">
                        </td>
                    </tr>
                </tbody>
            </table>
            <table>
                <tbody>
                    <tr>
    <?php if ($result->party_id != null) { ?>
                            <th scope="row" style="">
                                <p class="submit"><input disabled type="submit" name="submit-key" id="submit-key" class="button button-primary" value="Submit Keys"></p>
                            </th>
    <?php } ?>
                    </tr>
                </tbody>
            </table>
        </form>
        <!-- New form for submit keys to the boxrunner -->
    </div>
    <?php
    }
}
?>
