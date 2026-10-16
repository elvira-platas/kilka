<?php
/** Localized reader UI without changing the locale of the rest of the site. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {
	load_plugin_textdomain( 'kilka-reader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

/** Supported document language, then site language, then English. */
function kilka_reader_ui_language( $document_language = null, $site_locale = null ) {
	if ( null === $document_language ) {
		$document_language = get_post_meta( get_queried_object_id(), '_kilka_reader_language', true );
	}
	if ( null === $site_locale ) { $site_locale = get_locale(); }
	foreach ( array( $document_language, $site_locale ) as $candidate ) {
		$language = strtolower( strtok( str_replace( '_', '-', (string) $candidate ), '-' ) );
		if ( in_array( $language, array( 'ru', 'en', 'de' ), true ) ) { return $language; }
	}
	return 'en';
}

add_filter( 'gettext_kilka-reader', function ( $translation, $text ) {
	if ( is_admin() || ! did_action( 'wp' ) || ! kilka_reader_is_reading() ) { return $translation; }
	$language = kilka_reader_ui_language();
	if ( 'en' === $language ) { return $text; }
	static $catalogs = array();
	if ( ! isset( $catalogs[ $language ] ) ) {
		require_once ABSPATH . WPINC . '/pomo/mo.php';
		$locales = array( 'ru' => 'ru_RU', 'de' => 'de_DE' );
		$catalogs[ $language ] = new MO();
		$catalogs[ $language ]->import_from_file( __DIR__ . '/languages/kilka-reader-' . $locales[ $language ] . '.mo' );
	}
	return $catalogs[ $language ]->translate( $text );
}, 10, 2 );
