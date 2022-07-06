<?php
// The file has JSON type.
header( 'Content-Type: application/json' );

// Prepare the file name from the query string.
// Don't use session_start here. Otherwise this file will be only executed after the process.php execution is done.

$progressfile = rawurldecode( $_GET['progressfile'] );
sleep( 1 );
// Make sure the file is exist.
if ( file_exists( $progressfile ) ) {
	// Get the content and echo it.
	$text = file_get_contents( $progressfile );
	echo( $text );
}
exit;
