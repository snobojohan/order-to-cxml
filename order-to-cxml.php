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

/*
    *   Some status hooks
// Order completed
function gon_woocommerce_order_status_completed( $order_id ) {
    error_log( "Order complete for order $order_id", 0 );
}
add_action( 'woocommerce_order_status_completed', 'gon_woocommerce_order_status_completed', 10, 1 );

// Payment completed
function gon_woocommerce_payment_complete( $order_id ) {
    error_log( "Payment has been received for order $order_id", 0 );
}
add_action( 'woocommerce_payment_complete', 'gon_woocommerce_payment_complete', 10, 1 );
*/


// https://stackoverflow.com/questions/42530626/getting-order-data-after-successful-checkout-hook
/*
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
*/

// only proceed if we are in admin mode!
if ( ! is_admin() ) {
	return;
}

function gon_debug($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}
require plugin_dir_path( __FILE__ ) . 'includes/gon-bulk-action.php';
require plugin_dir_path( __FILE__ ) . 'includes/gon-ftp-it.php';
