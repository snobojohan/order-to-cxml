<?php
/**
* Plugin Name: Order to cxml
* Plugin URI: https://bandalism.org/
* Description: This is the very first version.
* Version: 1.0
* Author: Johan Hallberg
* Author URI: http://bandalism.org/
* License: GNU General Public License V3
**/

/*  @ Copyright 2021  Gon Gallery

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Some security
if ( ! defined( 'ABSPATH' ) ) { 
    die;
}

class OrderToCxml
{
    function __construct(){
        add_action( 'init', array( $this, 'register_sent_to_crimson') );
        add_action( 'wc_order_statuses', array( $this, 'add_sent_to_crimson_to_order_statuses') );
    }

    function activate() {
       // Generate Custom Order Type
       error_log("Activated plugin");
       $this->register_sent_to_crimson();
       flush_rewrite_rules();
    }

    function deactivate() {
        
    }

    function uninstall() {
        
    }

    function register_sent_to_crimson() {
        register_post_status( 'wc-sent-to-crimson', array(
            'label'                     => 'Sent to Crimson',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Sent to Crimson (%s)', 'Sent to Crimson (%s)' )
        ) );
    }

    // Add to list of WC Order statuses
    function add_sent_to_crimson_to_order_statuses( $order_statuses ) {
    
        $new_order_statuses = array();
    
        // add new order status after processing
        foreach ( $order_statuses as $key => $status ) {
    
            $new_order_statuses[ $key ] = $status;
    
            if ( 'wc-processing' === $key ) {
                $new_order_statuses['wc-sent-to-crimson'] = 'Sent to crimson';
            }
        }
    
        return $new_order_statuses;
    }
}

if ( class_exists( 'OrderToCxml') ) {
    $orderToCxml = new OrderToCxml();
}

// ACTIVATION
register_activation_hook( __FILE__, array( $orderToCxml, 'activate') );

// DEACTIVATION
register_deactivation_hook( __FILE__, array( $orderToCxml, 'deactivate') );

// UNINSTALL


// Order completed
function gon_woocommerce_order_status_completed( $order_id ) {
   
    error_log( "Order complete for order $order_id", 0 );
    
}
add_action( 'woocommerce_order_status_completed', 'gon_woocommerce_order_status_completed', 10, 1 );

/**
 * Create temporary file in system temporary directory.
 *
 * @author Nabil Kadimi - https://kadimi.com
 *
 * @param  string $name    File name.
 * @param  string $content File contents.
 * @return string File path.
 */
function wporg_k_tempnam( $name, $content ) {
    $sep = DIRECTORY_SEPARATOR;
    $file = $sep . trim( sys_get_temp_dir(), $sep ) . $sep . ltrim( $name, $sep );
    file_put_contents( $file, $content );
    register_shutdown_function( function() use( $file ) {
        @unlink( $file );
    } );
    return $file;
}

// Payment completed
function gon_woocommerce_payment_complete( $order_id ) {
    error_log( "Payment has been received for order $order_id", 0 );
}
add_action( 'woocommerce_payment_complete', 'gon_woocommerce_payment_complete', 10, 1 );

// https://stackoverflow.com/questions/42530626/getting-order-data-after-successful-checkout-hook
add_action('woocommerce_thankyou', 'bake_xml', 10, 1);
function bake_xml( $order_id ) {
    if ( ! $order_id )
        return;

    // Allow code execution only once 
    if( true || ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {

        $aStr = "";

        // Get an instance of the WC_Order object
        $order = wc_get_order( $order_id );

        // Get the order key
        $order_key = $order->get_order_key();

        // Get the order number
        $order_key = $order->get_order_number();

        if($order->is_paid())
            $paid = __('yes');
        else
            $paid = __('no');

        // Loop through order items
        foreach ( $order->get_items() as $item_id => $item ) {

            // Get the product object
            $product = $item->get_product();

            // Get the product Id
            $product_id = $product->get_id();

            // Get the product name
            $product_name = $item->get_name();

            // The quantity
            $quantity = $item->get_quantity(); 

            $aStr .= $product_name ." ( $product_id ) qty: $quantity<br>";
        }

        // Output some data
        echo '<p class="jh-debug">Order ID: '. $order_id . ' — Order Status: ' . $order->get_status() . ' — Order is paid: ' . $paid . ' ||| ' . $aStr . '</p>';

        // Flag the action as done (to avoid repetitions on reload for example)
        $order->update_meta_data( '_thankyou_action_done', true );
        $order->save();
    }
}




////////////////

/*
 * Add your custom bulk action in dropdown
 * @since 3.5.0
 */
add_filter( 'bulk_actions-edit-shop_order', 'misha_register_bulk_action' ); // edit-shop_order is the screen ID of the orders page
 
function misha_register_bulk_action( $bulk_actions ) {
 
	$bulk_actions['mark_sent_to_crimson'] = 'Send to Crimson'; // <option value="mark_sent_to_crimson">Mark sent to Crimson</option>
	return $bulk_actions;
 
}
 
/*
 * Bulk action handler
 * Make sure that "action name" in the hook is the same like the option value from the above function
 */
add_action( 'admin_action_mark_sent_to_crimson', 'misha_bulk_process_custom_status' ); // admin_action_{action name}
 
function misha_bulk_process_custom_status() {
 
	// if an array with order IDs is not presented, exit the function
	if( !isset( $_REQUEST['post'] ) && !is_array( $_REQUEST['post'] ) )
		return;
 
	foreach( $_REQUEST['post'] as $order_id ) {
 
		$order = new WC_Order( $order_id );
		$order_note = 'That\'s what happened by bulk edit:';
		$order->update_status( 'sent-to-crimson', $order_note, true ); // "misha-shipment" is the order status name (do not use wc-misha-shipment)
 
	}
 
	// of course using add_query_arg() is not required, you can build your URL inline
	$location = add_query_arg( array(
    	'post_type' => 'shop_order',
		'marked_sent-to-crimson' => 1, // just the $_GET variable for notices
		'changed' => count( $_REQUEST['post'] ), // number of changed orders
		'ids' => join( $_REQUEST['post'], ',' ),
		'post_status' => 'all'
	), 'edit.php' );
 
	wp_redirect( admin_url( $location ) );
	exit;
 
}
 
/*
 * Notices
 */
add_action('admin_notices', 'misha_custom_order_status_notices');
 
function misha_custom_order_status_notices() {
 
	global $pagenow, $typenow;
 
	if( $typenow == 'shop_order' 
	 && $pagenow == 'edit.php'
	 && isset( $_REQUEST['marked_sent-to-crimson'] )
	 && $_REQUEST['marked_sent-to-crimson'] == 1
	 && isset( $_REQUEST['changed'] ) ) {
 
		$message = sprintf( _n( 'Order status changed.', '%s order statuses changed.', $_REQUEST['changed'] ), number_format_i18n( $_REQUEST['changed'] ) );
		echo "<div class=\"updated\"><p>{$message}</p></div>";
 
	}
 
}
///////////////

// only proceed if we are in admin mode!
if ( ! is_admin() ) {
	return;
}

function gon_foo( $order_id ){
    echo "<div class=\"updated\"><p>$order_id skickad till print</p></div>";
    error_log("---->>>>>> $order_id skickad till print", 0);

    // TODO: Put the ftping here
    /* set the FTP hostname */
    $usr = "snobojohan";
    $pwd = "ywVdRxYqUKM#ms4";
    $host = "ftp.drivehq.com";
    
    // Create temporary file
    // Basic
    /*
    $filename = 'hello.txt';
    $content = 'Hello World!';
    $local_file = wporg_k_tempnam( $filename, $content );
    */
    $filename = 'hello-' . wp_generate_password( 5, false ) . '.txt';
    $content = 'Hello World!';
    $file = wporg_k_tempnam( $filename, $content );

    error_log("Local file: $local_file", 0);

    $ftp_path = 'test.txt'; 
    $conn_id = ftp_connect($host, 21) or die ("Cannot connect to host");      
    ftp_pasv($conn_id, true); 
    ftp_login($conn_id, $usr, $pwd) or die("Cannot login"); 
    
    // perform file upload 
    ftp_chdir($conn_id, '/wwwhome'); 
    $upload = ftp_put($conn_id, $ftp_path, $local_file, FTP_ASCII); 
    if($upload) { $ftpsucc=1; } else { $ftpsucc=0; } 
    
    // check upload status: 
    print (!$upload) ? 'Cannot upload' : 'Upload complete'; // TODO: Make it visible
    print "\n"; 

    // close the FTP stream 
    ftp_close($conn_id); 
   
}

add_action( 'woocommerce_order_status_sent-to-crimson', 'gon_foo', 10, 1 );