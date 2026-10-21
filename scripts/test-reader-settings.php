<?php
/** Run on a disposable test site: wp eval-file scripts/test-reader-settings.php --user=admin (Reader active). */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'kilka_reader_intro_settings' ) || ! current_user_can( 'edit_pages' ) ) {
	throw new RuntimeException( 'Load WordPress with Reader active and a user who can edit pages.' );
}
$original_post = $_POST;
$original_request = $_REQUEST;
$document = wp_insert_post( array(
	'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Reader settings regression test',
	'meta_input' => array( '_wp_page_template' => KILKA_READER_TEMPLATE ),
), true );
if ( is_wp_error( $document ) ) { throw new RuntimeException( $document->get_error_message() ); }
$redirect_stop = new RuntimeException( 'Expected settings redirect.' );
$stop_redirect = function ( $url ) use ( $redirect_stop ) { throw $redirect_stop; };
add_filter( 'wp_redirect', $stop_redirect );
try {
	$text = 'Папка C:\\Books; literal \\n; "quoted"; apostrophe\'s; slash \\"';
	foreach ( array( $text, $text . ' — saved again' ) as $expected ) {
		$_POST = wp_slash( array(
			'document' => $document,
			'kilka_intro' => array( 'title' => $expected, 'author' => $expected, 'line' => $expected ),
			'_wpnonce' => wp_create_nonce( 'kilka_reader_settings_' . $document ),
		) );
		$_REQUEST = $_POST;
		try {
			do_action( 'admin_post_kilka_reader_settings' );
			throw new RuntimeException( 'Settings handler did not redirect.' );
		} catch ( RuntimeException $error ) {
			if ( $error !== $redirect_stop ) { throw $error; }
		}
		$stored = get_post_meta( $document, '_kilka_reader_intro', true );
		foreach ( array( 'title', 'author', 'line' ) as $key ) {
			if ( $stored[$key] !== $expected ) { throw new RuntimeException( 'Saved text changed: ' . $key ); }
		}
	}
	echo "PASS Reader settings preserve backslashes, quotes and Unicode on initial save and update.\n";
} finally {
	remove_filter( 'wp_redirect', $stop_redirect );
	$_POST = $original_post;
	$_REQUEST = $original_request;
	wp_delete_post( $document, true );
}
