<?php

/*
 * Add your custom bulk action in dropdown
 * @since 3.5.0
 */
add_filter( 'bulk_actions-edit-shop_order', 'gon_register_bulk_action' ); // edit-shop_order is the screen ID of the orders page
 
function gon_register_bulk_action( $bulk_actions ) {
 
	$bulk_actions['mark_sent_to_crimson'] = 'Send to Crimson'; // <option value="mark_sent_to_crimson">Mark sent to Crimson</option>
	return $bulk_actions;
 
}
 
/*
 * Bulk action handler
 * Make sure that "action name" in the hook is the same like the option value from the above function
 */
add_action( 'admin_action_mark_sent_to_crimson', 'gon_bulk_process_custom_status' ); // admin_action_{action name}
 
function gon_bulk_process_custom_status() {
 
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
		'ids' => join( ',', $_REQUEST['post'] ), 
		'post_status' => 'all'
	), 'edit.php' );
 
	wp_redirect( admin_url( $location ) );
	exit;
 
}
 
/*
 * Notices
 */
add_action('admin_notices', 'gon_custom_order_status_notices');
 
function gon_custom_order_status_notices() {
 
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