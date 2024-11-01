<?php


/*
* @package Boldwallet Woocommerce Plugin
*/

/*
Plugin Name: WooCommerce Boldwallet (Boldwallet)
Plugin URI: http://bwalletpay.com
Description: Boldwallet Suports online card processing and internet banking transactions
Version: 1.4
Author: Premium ESOWP Limited
Author URI: http://premiumesowp.com
Licence: GPLv2 or Later
License URI:  http://www.gnu.org/licenses/gpl-2.0.tx
*/

 


//check access from wordpress
if(!function_exists('add_action')){

	die;
}

defined('ABSPATH') or die('hey , what are you doind here? you silly human');

class Boldwallet_api{

 function __construct(){
add_action('init',array($this,'custom_post_type'));
add_action('admin_menu',array($this,'awesome_page_create'));
}
function register(){
add_action('admin_enqueue_scripts',array($this,'enqueue'));

}
function activate(){
	$this->custom_post_type();
	//add_action('admin_menu',array($this,'awesome_page_create'));
flush_rewrite_rules();
}	

function deactivate(){}

static function uninstall(){}

function custom_post_type(){

	register_post_type('book',['public' => true,'label'=>'books']);
}


function awesome_page_create() {
    $page_title = 'My Awesome Admin Page';
    $menu_title = 'Boldwallet';
    $capability = 'edit_posts';
    $menu_slug = 'awesome_page';
    $function = 'my_awesome_page_display';
    $icon_url = '';
    $position = 24;

   // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, array($this,$function), $icon_url, $position );
}

function my_awesome_page_display() {
    renderpage();
}
function enqueue(){

//enqueue all our css file
	wp_enqueue_style('mypluginstyle',plugins_url('/assets/mystyle.css',__FILE__));
    wp_enqueue_script('mypluginstyle',plugins_url('/assets/mystyle.js',__FILE__));
}
}

if(class_exists('Boldwallet_api')){

	$boldwalletobject = new Boldwallet_api();
	$boldwalletobject->register();

}
//activation
register_activation_hook(__FILE__,array($boldwalletobject,"activate"));

//deactivation

register_deactivation_hook(__FILE__,array($boldwalletobject,"deactivate"));

register_uninstall_hook(__FILE__,array($boldwalletobject,"uninstall"));




// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;
//woocommerce payment gateway begin

add_filter( 'woocommerce_payment_gateways', 'boldwallet_gateway_class' );



add_action( 'plugins_loaded', 'bolwallet_init_gateway_class' );

define('boldlogo', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/logo.png');
function bolwallet_init_gateway_class() {
 
	class WC_Boldwallet_Gateway extends WC_Payment_Gateway {

public function __construct(){


	 // global ID
    $this->id = "bwalletpay1";
    // Show Title
    $this->method_title = __( "BoldWallet Gateway", 'bwalletpay1' );
    // Show Description
    $this->method_description = __( "BoldWallet Payment Gateway Plug-in for WooCommerce", 'bwalletpay1' );
    // vertical tab title

    $this->init_form_fields();
    // load time variable setting
    $this->init_settings();
    $this->msg['message']	= '';
    $this->msg['class'] 	= '';
    $this->title = __( "BoldWallet Gateway", 'bwalletpay1' );
    $this->icon = boldlogo;
    $this->has_fields = true;
    $this->liveurl = "https://bwalletpay.com/wooapi";
    $this->masterkey = $this->settings['master_key']; // Define the Redirect Page.
    $this->servicekey = $this->settings['service_key']; // Define the Redirect Page.
    //$this->redirect_page	= $this->settings['redirect_page']; // Define the Redirect Page.
    $this->description =  $this->settings['description'];
    if ( $this->settings['ref_id'] == "yes" ) { 

    	$this->referal = $this->settings['referral']; 

     }else{

 		$this->referal =  "";

     }
    
    
     
    foreach ( $this->settings as $setting_key => $value ) {
      $this->$setting_key = $value;
    }

 

// Save settings
    if ( is_admin() ) {
     add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
  }  

if ( version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); //update for woocommerce >2.0
                 } else {
                    add_action( 'woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options' ) ); // WC-1.6.6
                }

add_action('init', array(&$this, 'check_bwallet_response'));
  add_action('woocommerce_api_' . strtolower(get_class($this)), array(&$this, 'check_bwallet_response'));

 add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
 


 add_action('woocommerce_receipt_bwalletpay1', array(&$this, 'receipt_page'));	
  

}



function bol_buildpayment($order_id){

            global $woocommerce;
			$order = new WC_Order( $order_id );

			 $redirect_url = get_site_url() . "/";
			 
			// Redirect URL : For WooCoomerce 2.0
			if ( version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				$redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
			}

           $productinfo = "Order $order_id";

			$txfid = $order_id.'_'.uniqid();
			$order_total        = $order->get_total();

			$order_currency     = method_exists( $order, 'get_currency' ) ? $order->get_currency() : $order->get_order_currency();

			$currency_symbol	= get_woocommerce_currency_symbol( $order_currency );

			$defrg = $currency_symbol." + ".$order_currency." ".$order_total;

           $bwallet_args = array(
				
				'txnid' 		=> $txnid,
				'total' 		=> $order->order_total,
				'customerFirstName'		=> $order->billing_first_name,
				'customerEmail' 		=> $order->billing_email,
				'customerPhoneNumber' 	=> substr( $order->billing_phone, -10 ),
				'description'	=> $productinfo,
				'rurl' 			=> $redirect_url,
				'bwalletrefid' 	=> $this->referal,
				'customerLastName' => $order->billing_last_name,
				'customeraddress' 		=> $order->billing_address_1,
				'currency' 		=> $order_currency,
				'customercity' 			=> $order->billing_city,
				'customerstate' 		=> $order->billing_state,
				'customercountry' 		=> $order->billing_country,
				'customerzipcode' 		=> $order->billing_postcode,
				'masterkey'		=> $this->masterkey,
				'servicekey'    => $this->servicekey,
				'txfid' 			=> $txfid,
				'orderid' 			=> $order_id,
				'service_provider'	=> $service_provider
			);

  //check_bwallet_responsetrue
	
	$bwallet_args_array = array();
			foreach($bwallet_args as $key => $value){
				$bwallet_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
			}
			
			return '	<form action="'.$this->liveurl.'" method="post" id="payboldwallet_payment_form">
  				' . implode('', $bwallet_args_array) . '
				<input type="submit" class="button-alt" id="submit_payboldwallet_payment_form" value="'.__('Pay via Boldwallet', 'bwalletpay1').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'bwalletpay1').'</a>
					<script type="text/javascript">
					jQuery(function(){
					jQuery("body").block({
						message: "'.__('Thank you for your order. We are now redirecting you to Boldwallet Payment Gateway to make payment.', 'bwalletpay1').'",
						overlayCSS: {
							background		: "#fff",
							opacity			: 0.6
						},
						css: {
							padding			: 20,
							textAlign		: "center",
							color			: "#555",
							border			: "3px solid #aaa",
							backgroundColor	: "#fff",
							cursor			: "wait",
							lineHeight		: "32px"
						}
					});
					jQuery("#submit_payboldwallet_payment_form").click();});
					</script>
				</form>';		
			
			

    }




	 
	public function admin_notices() {

		if ( $this->enabled == 'no' ) {
			return;
		}

		// Check required fields
		if (  $this->masterkey == "" &&  $this->secretkey == "" ) {
			echo '<div class="error"><p>' . sprintf( 'Please enter your Boldwallet merchant details <a href="%s">here</a> to be able to use the Boldwallet WooCommerce plugin.', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bwalletpay1' ) ) . '</p></div>';
			return;
		}

	}


	/**
	 * Check if this gateway is enabled
	 */
	public function is_available() {

		if ( $this->enabled == "yes" ) {

			if ( !$this->masterkey  && !$this->secretkey  ) {

				return false;

			}

			if ( $this->settings['ref_id'] == "yes" &&  !$this->referal) { 
 

				return false;
			}

			return true;

		}

		return false;

	}


    /**
     * Admin Panel Options
    */
    public function admin_options() {

    	?>

    	<h2>Boldwallet
		<?php
			if ( function_exists( 'wc_back_link' ) ) {
				wc_back_link( 'Return to payments', admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
			}
		?>
		</h2>

         

        <?php

		if ( $this->is_valid_for_use() ){

            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';

        }
		else {	 ?>
			<div class="inline error"><p><strong>Boldwallet Payment Gateway is Disabled</strong>: <?php echo $this->msg ?></p></div>

		<?php }

    }

	public function is_valid_for_use() {

		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_bwalletpay1_supported_currencies', array( 'NGN', 'USD', 'GHS' ,'EUR') ) ) ) {

			$this->msg = 'Boldwallet does not support your store currency. Kindly set it to either NGN (&#8358), GHS (&#x20b5;), USD (&#36;) or EUR(&#8364;) <a href="' . admin_url( 'admin.php?page=wc-settings&tab=general' ) . '">here</a>';

			return false;

		}

		return true;

	}
 
		
function enqueue(){

//enqueue all our css file
	wp_enqueue_script( 'jquery');
    wp_enqueue_script('mypluginscript',plugins_url('/assets/myscript.js',__FILE__));
 
}
function check_bwallet_response(){


global $woocommerce;

if(isset($_REQUEST['orderid']) && isset($_REQUEST['refno']) ){

$order_id = sanitize_text_field($_REQUEST['orderid']);
$refid = sanitize_text_field($_REQUEST['refno']);
if(isset($_REQUEST['gftrh'])){

$retd = (int)sanitize_text_field($_REQUEST['gftrh']);

}else{

$retd  = 0;

}
if($order_id != ''){
 
    try{  
		if("1" == $retd ){

		$confirmUrl = "https://bwalletpay.com/processor/sandboxverify";

			}else{

		$confirmUrl = "https://bwalletpay.com/processor/verify";
			}

			$order = new WC_Order( $order_id );

   				 $masterkey = $this->masterkey ; // Define the Redirect Page.
   			  $servicekey =  $this->servicekey;; 

    			$refdy = $confirmUrl."?masterkey=".$masterkey."&refid=".$refid;
      
    			$url = $json."".$refdy;
   			
   			 $request = wp_remote_get($url);
   			 
   			if ( is_wp_error( $request) ) {
              return;

 					} 	

                $jsonfile = wp_remote_retrieve_body( $request );
   			  $var = json_decode($jsonfile);


     			if($var->status_request == "1"){
			 
       				if($var->status == "1"){
       
         			//echo "Payment was successfull";
       				$order->payment_complete();
       				$woocommerce->cart->empty_cart();
       				$order->add_order_note('');
       				
       				 
						$redirect_url = $this->get_return_url( $order );
					 
				 wc_print_notices();
				
				 wp_redirect( $redirect_url );
                 exit;
                     

        
         			 }else if($var->status == "2"){

         			 	//cancelled

         			$this->msg['class'] = 'error';
					$this->msg['message'] = "Thank you for the order. However, the transaction has been declined.";
					$order->add_order_note('');
					$order->update_status('on-hold');
					$redirect_url = $this->get_return_url( $order );
					wc_print_notices();
				
					 wp_redirect( $redirect_url );
                 	exit;
                     

         			 }else if($var->status == "3"){
                     
                    //decline
         			$this->msg['class'] = 'error';
					$this->msg['message'] = "Thank you for the order. However, the transaction has been declined.";
					$order->add_order_note('');
					$order->update_status('failed');
					wc_print_notices();
					$redirect_url = wc_get_checkout_url();
				 	wp_redirect( $redirect_url );
                 	exit;

         			 }else{
                      //pending
         			 $this->msg['message'] = "Thank you for the order. Right now your payment status is pending. We will keep you posted regarding the status of your order through eMail";
					$this->msg['class'] = 'notice';
 					$order->update_status('on-hold');
					$redirect_url = $this->get_return_url( $order );
					wc_print_notices();
				 	wp_redirect( $redirect_url );
                 	exit;


         			 }


     				}else{

$this->msg['message'] = "Sorry, we were not able to verify your transaction, due to low internet connectivity";
									$this->msg['class'] = 'notice';

					wc_print_notices();
				 

					 $redirect_url = wc_get_checkout_url();
				 	wp_redirect( $redirect_url );
                 	exit;
                 	exit;
     				}	

 		}catch(Exception $e){
                        // $errorOccurred = true;
                        $msg = "Error";
                        $redirect_url = wc_get_checkout_url();
      	  wp_redirect($redirect_url);

					}



          if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( $msg['message'], $msg['class'] );

				} else {
					if( 'success' == $msg['class'] ) {
						$woocommerce->add_message( $msg['message']);
					}else{
						$woocommerce->add_error( $msg['message'] );

					}
					$woocommerce->set_messages();
				}	
				
			 


    }else{
 	 $redirect_url = wc_get_checkout_url();
    wp_redirect( $redirect_url );
    }

      }else{

        $redirect_url = wc_get_checkout_url();
       wp_redirect( $redirect_url );
      }


   }
function init_form_fields(){

$this->form_fields = array(
		'enabled' => array(
			'title'       => 'Enable/Disable',
			'label'       => 'Enable Boldwallet',
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'yes'
		),
		'title' => array(
			'title'       => 'Title',
			'type'        => 'text',
			'description' => 'This controls shows the title which the user sees during checkout.',
			'default'     => 'Credit Card',
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => 'Description',
			'type'        => 'textarea',
			'description' => 'This controls shows the description which the user sees during checkout.',
			'default'     => 'Pay with your credit card via our super-cool payment gateway.',

		),
		
		'ref_id' => array(
			'title'       => 'Referal Attribute',
			'label'       => 'Enable Referral Attribute',
			'type'        => 'checkbox',
			'description' => 'This Activate Referal Process to get bonus on every Transaction',
			'default'     => 'no',
			'desc_tip'    => true,
		),
		'referral' => array(
			'title'       => 'Referral Username',
			'type'        => 'text',
			'class'       => 'woocommerce_bwalletpay1_referral',
		) ,
		'master_key' => array(
			'title'       => 'Master Key',
			'type'        => 'text'
		),
		'service_key' => array(
			'title'       => 'Service Key',
			'type'        => 'text',
		)
);



	
}


function process_payment($order_id){
			global $woocommerce;
            $order = new WC_Order($order_id);
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) { // For WC 2.1.0
			  	$checkout_payment_url = $order->get_checkout_payment_url( true );
			} else {
				$checkout_payment_url = get_permalink( get_option ( 'woocommerce_pay_page_id' ) );
			}

			return array(
				'result' => 'success', 
				'redirect' => add_query_arg(
					'order', 
					$order->id, 
					add_query_arg(
						'key', 
						$order->order_key, 
						$checkout_payment_url						
					)
				)
			);
		} //END-process_payment

function payment_fields(){
			 
		} //END-payment_fields
function receipt_page($order){
			echo '<p><strong>' . __('Thank you for your order.', 'bwalletpay1').'</strong><br/>' . __('The payment page will open soon.', 'bwalletpay1').'</p>';
			echo $this->bol_buildpayment($order);
		} //END-receipt_page

/**
         * Get Page list from WordPress
         **/
		function boldwallet_get_pages($title = false, $indent = true) {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			if ($title) $page_list[] = $title;
			foreach ($wp_pages as $page) {
				$prefix = '';
				// show indented child pages?
				if ($indent) {
                	$has_parent = $page->post_parent;
                	while($has_parent) {
                    	$prefix .=  ' - ';
                    	$next_page = get_post($has_parent);
                    	$has_parent = $next_page->post_parent;
                	}
            	}
            	// add to page list array array
            	$page_list[$page->ID] = $prefix . $page->post_title;
        	}
        	return $page_list;
		} //END-boldwallet_get_pages

	 
}}

function boldwallet_gateway_class( $gateways ) {
	$gateways[] = 'WC_Boldwallet_Gateway'; // your class name is here
	return $gateways;
}

add_filter( 'plugin_action_links', 'boldwallet_add_action_plugin', 10, 5 );
function boldwallet_add_action_plugin( $actions, $plugin_file ) {
	static $plugin;

	if (!isset($plugin))
		$plugin = plugin_basename(__FILE__);
	if ($plugin == $plugin_file) {

			$settings = array('settings' => '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_boldwallet_gateway">' . __('Settings') . '</a>');
		
    			$actions = array_merge($settings, $actions);
			
		}
		
		return $actions;
}//END-settings_add_action_link

