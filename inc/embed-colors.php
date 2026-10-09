<?php
/**
 * Color scheme support for this site's WordPress embed cards.
 *
 * @package Kilka
 */

/** Register the small bridge independently of the embed's sandboxed storage. */
function kilka_register_embed_color_script( $in_footer = true ) {
	wp_register_script(
		'kilka-embed-colors',
		get_template_directory_uri() . '/assets/js/embed-colors.js',
		array(),
		filemtime( get_template_directory() . '/assets/js/embed-colors.js' ),
		$in_footer
	);
}

/** Send the host page's resolved scheme to its own embedded posts. */
function kilka_enqueue_embed_color_bridge() {
	kilka_register_embed_color_script();
	wp_enqueue_script( 'kilka-embed-colors' );
}
add_action( 'wp_enqueue_scripts', 'kilka_enqueue_embed_color_bridge' );

/** Embed templates use embed_head instead of the theme's usual wp_head. */
function kilka_print_embed_colors() {
	wp_register_style(
		'kilka-embed-colors',
		get_template_directory_uri() . '/assets/css/embed-colors.css',
		array(),
		filemtime( get_template_directory() . '/assets/css/embed-colors.css' )
	);
	wp_print_styles( 'kilka-embed-colors' );
	kilka_register_embed_color_script( false );
	wp_print_scripts( 'kilka-embed-colors' );
}
add_action( 'embed_head', 'kilka_print_embed_colors', 20 );
