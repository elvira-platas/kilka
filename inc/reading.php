<?php
/**
 * Presentation helpers for the optional Reading page template.
 *
 * All document content stays in ordinary WordPress pages and core blocks.
 *
 * @package Kilka
 */

/**
 * Check whether the current page uses continuous reading presentation.
 *
 * @return bool
 */
function kilka_is_reading_context() {
	return is_page_template( 'page-templates/reading.php' );
}

/**
 * Load portable block styles wherever their editor options are available.
 * Keep the full reading layout limited to the Reading template.
 */
function kilka_reading_assets() {
	wp_enqueue_style(
		'kilka-reading-blocks',
		get_template_directory_uri() . '/assets/css/reading-blocks.css',
		array( 'kilka-color-schemes' ),
		filemtime( get_template_directory() . '/assets/css/reading-blocks.css' )
	);
	if ( ! kilka_is_reading_context() ) {
		return;
	}

	wp_enqueue_style(
		'kilka-reading',
		get_template_directory_uri() . '/assets/css/reading.css',
		array( 'kilka-reading-blocks' ),
		filemtime( get_template_directory() . '/assets/css/reading.css' )
	);

	// Reading needs neither emoji CDN fallbacks nor embedded-content messaging.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	wp_dequeue_script( 'wp-embed' );
}
add_action( 'wp_enqueue_scripts', 'kilka_reading_assets', 30 );

/**
 * Expose reusable document parts without custom blocks or stored metadata.
 */
function kilka_register_reading_block_styles() {
	register_block_style( 'core/paragraph', array(
		'name'  => 'kilka-reading-byline',
		'label' => __( 'Reading byline', 'kilka' ),
	) );
	register_block_style( 'core/group', array(
		'name'  => 'kilka-reading-text',
		'label' => __( 'Reading text', 'kilka' ),
	) );
	register_block_style( 'core/group', array(
		'name'  => 'kilka-reading-note',
		'label' => __( 'Reading note', 'kilka' ),
	) );
}
add_action( 'init', 'kilka_register_reading_block_styles' );

/** Keep the reader entry pattern easy to find in the inserter. */
function kilka_register_reader_pattern_category() {
	register_block_pattern_category( 'kilka-reader', array( 'label' => __( 'Reader', 'kilka' ) ) );
}
add_action( 'init', 'kilka_register_reader_pattern_category' );
