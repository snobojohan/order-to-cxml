<?php
/**
* Plugin Name: Order to cxml
* Plugin URI: https://bandalsim.org/
* Description: This is the very first version.
* Version: 1.0
* Author: Johan Hallberg
* Author URI: http://bandalism.org/
**/

// Order completed
function gon_woocommerce_order_status_completed( $order_id ) {
   
    error_log( "Order complete for order $order_id trying to ftp", 0 );
    

    /* set the FTP hostname */
    $usr = "snobojohan";
    $pwd = "ywVdRxYqUKM#ms4";
    $host = "ftp.drivehq.com";
    
    // Create temporary file
    // Basic
    $filename = 'hello.txt';
    $content = 'Hello World!';
    $local_file = wporg_k_tempnam( $filename, $content );

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
    print (!$upload) ? 'Cannot upload' : 'Upload complete'; 
    print "\n"; 
    // close the FTP stream 
    ftp_close($conn_id); 

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

// only proceed if we are in admin mode!
if ( ! is_admin() ) {
	return;
}