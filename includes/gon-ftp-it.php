<?php

/**
 * Create temporary file in system temporary directory.
 *
 * @author Nabil Kadimi - https://kadimi.com
 *
 * @param  string $name    File name.
 * @param  string $content File contents.
 * @return string File path.
 */
function gon_k_tempnam( $name, $content ) {
    $sep = DIRECTORY_SEPARATOR;
    $file = $sep . trim( sys_get_temp_dir(), $sep ) . $sep . ltrim( $name, $sep );
    file_put_contents( $file, $content );
    register_shutdown_function( function() use( $file ) {
        @unlink( $file );
    } );
    return $file;
}


function gon_ftp( $order_id ){

    
    /* set the FTP hostname */
    $usr = "snobojohan";
    // TODO: Hide
    $pwd = "ywVdRxYqUKM#ms4";
    $host = "ftp.drivehq.com";
    
    // Create temporary file
    $filename = 'hello-' . $order_id . '.txt';
    $content = 'Hello order $order_id';
    $local_file = gon_k_tempnam( $filename, $content );

    $ftp_path = $filename; 
    $conn_id = ftp_connect($host, 21) or die ("Cannot connect to host");      

    ftp_pasv($conn_id, true); 
    ftp_login($conn_id, $usr, $pwd) or die("Cannot login"); 

    
    // perform file upload 
    ftp_chdir($conn_id, '/wwwhome');
    error_log("LOG 4 : $conn_id | $local_file");
    $upload = ftp_put($conn_id, $ftp_path, $local_file, FTP_ASCII); 
    if($upload) { $ftpsucc=1; } else { $ftpsucc=0; } 
    
    // TODO: Only update order status onn success
    $gon_msg = (!$upload) ? 'Cannot upload' : 'Upload complete'; // TODO: Make it visible
    error_log(" Upload? : $gon_msg | $ftp_path");

    // close the FTP stream 
    ftp_close($conn_id); 
   
}

// THIS
add_action( 'woocommerce_order_status_sent-to-crimson', 'gon_ftp', 10, 1 );