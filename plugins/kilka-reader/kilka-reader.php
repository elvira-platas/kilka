<?php
/**
 * Plugin Name: Kilka Reader
 * Description: Portable reading pages with a related publication and optional text size controls.
 * Version: 0.1.0
 * Author: Elvira
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kilka-reader
 * Domain Path: /languages
 * Requires at least: 6.6
 * Requires PHP: 7.4
 *
 * Developed with substantial assistance from OpenAI Codex under Elvira's direction.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KILKA_READER_VERSION', '0.1.0' );
define( 'KILKA_READER_TEMPLATE', 'page-templates/reading.php' );
require_once __DIR__ . '/languages.php';
require_once __DIR__ . '/includes/intro.php';
require_once __DIR__ . '/includes/admin.php';

/** Keep existing reading pages and URLs; no content migration is required. */
function kilka_reader_is_reading() {
	return is_page() && KILKA_READER_TEMPLATE === get_page_template_slug( get_queried_object_id() );
}

add_filter( 'body_class', function ( $classes ) {
	if ( kilka_reader_is_reading() ) { $classes[] = 'kilka-reader-active'; }
	return $classes;
} );

add_filter( 'theme_page_templates', function ( $templates ) {
	$templates[ KILKA_READER_TEMPLATE ] = __( 'Reading', 'kilka-reader' );
	return $templates;
} );

add_filter( 'template_include', function ( $template ) {
	if ( ! kilka_reader_is_reading() ) {
		return $template;
	}
	$theme_template = locate_template( KILKA_READER_TEMPLATE );
	return $theme_template ? $theme_template : __DIR__ . '/templates/reading.php';
}, 99 );

/** Only published, unprotected blog publications can be public return targets. */
function kilka_reader_publication( $id ) {
	$post = get_post( absint( $id ) );
	if ( ! $post || ! in_array( $post->post_type, array( 'post', 'world_note' ), true ) || ! is_post_publicly_viewable( $post ) || $post->post_password ) {
		return null;
	}
	return $post;
}

function kilka_reader_language( $value ) {
	return is_string( $value ) && strlen( $value ) <= 35 && preg_match( '/^[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8})*$/D', $value ) ? $value : '';
}

add_action( 'init', function () {
	register_post_meta( 'page', '_kilka_reader_language', array(
		'type' => 'string', 'single' => true, 'default' => '',
		'sanitize_callback' => 'kilka_reader_language', 'show_in_rest' => false,
		'auth_callback' => function ( $allowed, $key, $id ) { return current_user_can( 'edit_post', $id ); },
	) );
	register_post_meta( 'page', '_kilka_reader_publication', array(
		'type' => 'integer', 'single' => true, 'default' => 0,
		'sanitize_callback' => 'absint', 'show_in_rest' => false,
		'auth_callback' => function ( $allowed, $key, $id ) { return current_user_can( 'edit_post', $id ); },
	) );
} );

add_action( 'add_meta_boxes_page', function () {
	add_meta_box( 'kilka-reader', __( 'Reader', 'kilka-reader' ), 'kilka_reader_meta_box', 'page', 'side' );
} );

function kilka_reader_meta_box( $post, $settings = false ) {
	if ( ! $settings ) {
		$url = KILKA_READER_TEMPLATE === get_page_template_slug( $post ) ? kilka_reader_admin_url( $post->ID ) : kilka_reader_admin_url();
		echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Reader settings', 'kilka-reader' ) . '</a></p>';
		return;
	}
	$selected = (int) get_post_meta( $post->ID, '_kilka_reader_publication', true );
	$posts = get_posts( array( 'post_type' => array( 'post', 'world_note' ), 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	echo '<p>' . esc_html__( 'Use the Reading page template. Optionally choose the publication readers can return to.', 'kilka-reader' ) . '</p>';
	echo '<label for="kilka-reader-publication">' . esc_html__( 'Related publication', 'kilka-reader' ) . '</label>';
	echo '<select id="kilka-reader-publication" name="kilka_reader_publication" style="width:100%"><option value="0">' . esc_html__( 'None', 'kilka-reader' ) . '</option>';
	foreach ( $posts as $item ) {
		if ( ! kilka_reader_publication( $item->ID ) ) {
			continue;
		}
		echo '<option value="' . esc_attr( $item->ID ) . '" ' . selected( $selected, $item->ID, false ) . '>' . esc_html( get_the_title( $item ) ) . '</option>';
	}
	echo '</select>';
	echo '<p><label for="kilka-reader-language">' . esc_html__( 'Text language', 'kilka-reader' ) . '</label><input id="kilka-reader-language" name="kilka_reader_language" type="text" maxlength="35" placeholder="ru, en, de" value="' . esc_attr( get_post_meta( $post->ID, '_kilka_reader_language', true ) ) . '" class="widefat"></p>';
	echo '<p>' . esc_html__( 'Language tag for pronunciation and automatic hyphenation. Leave empty to use the site language.', 'kilka-reader' ) . '</p>';
}

/** Portable block styles and pattern; Kilka retains its no-plugin fallback. */
add_action( 'init', function () {
	foreach ( array( 'core/paragraph' => array( 'kilka-reading-byline' => __( 'Reading byline', 'kilka-reader' ) ), 'core/group' => array( 'kilka-reading-text' => __( 'Reading text', 'kilka-reader' ), 'kilka-reading-note' => __( 'Reading note', 'kilka-reader' ) ) ) as $block => $styles ) {
		foreach ( $styles as $name => $label ) {
			if ( ! WP_Block_Styles_Registry::get_instance()->is_registered( $block, $name ) ) {
				register_block_style( $block, array( 'name' => $name, 'label' => $label ) );
			}
		}
	}
	if ( ! WP_Block_Patterns_Registry::get_instance()->is_registered( 'kilka/reading-document' ) ) {
		ob_start();
		include __DIR__ . '/templates/document-pattern.php';
		register_block_pattern( 'kilka/reading-document', array( 'title' => __( 'Reading document', 'kilka-reader' ), 'categories' => array( 'text' ), 'postTypes' => array( 'page' ), 'content' => ob_get_clean() ) );
	}
}, 30 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! kilka_reader_is_reading() ) {
		return;
	}
	wp_enqueue_style( 'kilka-reader-intro', plugins_url( 'assets/intro.css', __FILE__ ), array(), filemtime( __DIR__ . '/assets/intro.css' ) );
	wp_enqueue_style( 'kilka-reader-controls', plugins_url( 'assets/controls.css', __FILE__ ), array(), filemtime( __DIR__ . '/assets/controls.css' ) );
	wp_enqueue_style( 'kilka-reader-colors', plugins_url( 'assets/colors.css', __FILE__ ), array( 'kilka-reader-controls' ), filemtime( __DIR__ . '/assets/colors.css' ) );
	wp_enqueue_script( 'kilka-reader-pagination', plugins_url( 'assets/pagination.js', __FILE__ ), array(), filemtime( __DIR__ . '/assets/pagination.js' ), true );
	wp_enqueue_style( 'kilka-reader-pagination', plugins_url( 'assets/pagination.css', __FILE__ ), array( 'kilka-reader-controls' ), filemtime( __DIR__ . '/assets/pagination.css' ) );
	wp_enqueue_script( 'kilka-reader', plugins_url( 'assets/reader.js', __FILE__ ), array( 'kilka-reader-pagination' ), filemtime( __DIR__ . '/assets/reader.js' ), true );
	if ( ! locate_template( KILKA_READER_TEMPLATE ) ) {
		wp_enqueue_style( 'kilka-reader-fallback', plugins_url( 'assets/fallback.css', __FILE__ ), array(), filemtime( __DIR__ . '/assets/fallback.css' ) );
	}
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	wp_dequeue_script( 'wp-embed' );
}, 40 );

function kilka_reader_return_link( $publication ) {
	$url = $publication ? get_permalink( $publication ) : home_url( '/' );
	return '<a class="kilka-reader-return" href="' . esc_url( $url ) . '" aria-label="' . esc_attr__( 'Close reader', 'kilka-reader' ) . '"><svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></a>';
}

/** Enhance rendered content only; never write controls into post_content. */
add_filter( 'the_content', function ( $content ) {
	if ( ! kilka_reader_is_reading() || ! in_the_loop() || ! is_main_query() || get_the_ID() !== get_queried_object_id() || is_feed() ) {
		return $content;
	}
	$publication = kilka_reader_publication( get_post_meta( get_the_ID(), '_kilka_reader_publication', true ) );
	$link = kilka_reader_return_link( $publication );
	$ui_language = kilka_reader_ui_language();
	$dock = '<div class="kilka-reader-dock" lang="' . esc_attr( $ui_language ) . '">' . $link;
	if ( post_password_required() ) { return $dock . '</div>' . $content; }
	$dock .= '<button type="button" class="kilka-reader-settings-toggle" aria-label="' . esc_attr__( 'Reading settings', 'kilka-reader' ) . '" aria-expanded="false" aria-controls="kilka-reader-settings" hidden><svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16M9 3v6M15 9v6M8 15v6"/></svg></button></div>';
	$toolbar = $dock . '<div id="kilka-reader-settings" lang="' . esc_attr( $ui_language ) . '" class="kilka-reader-toolbar" role="region" aria-label="' . esc_attr__( 'Reading settings', 'kilka-reader' ) . '" hidden><div class="kilka-reader-size" role="group" aria-label="' . esc_attr__( 'Text size', 'kilka-reader' ) . '" hidden>';
	foreach ( array( 'decrease' => array( 'A−', __( 'Decrease text size', 'kilka-reader' ) ), 'reset' => array( '100%', __( 'Reset text size', 'kilka-reader' ) ), 'increase' => array( 'A+', __( 'Increase text size', 'kilka-reader' ) ) ) as $action => $button ) {
		$toolbar .= '<button type="button" data-reader-size="' . esc_attr( $action ) . '" aria-label="' . esc_attr( $button[1] ) . '">' . esc_html( $button[0] ) . '</button>';
	}
	$toolbar .= '<span class="kilka-reader-status" role="status" aria-live="polite" data-label="' . esc_attr__( 'Text size', 'kilka-reader' ) . '"></span></div>';
	$toolbar .= '<div class="kilka-reader-alignment" role="group" aria-label="' . esc_attr__( 'Text alignment', 'kilka-reader' ) . '" hidden>';
	foreach ( array( 'left' => __( 'Align left', 'kilka-reader' ), 'justify' => __( 'Justify', 'kilka-reader' ) ) as $alignment => $label ) {
		$path = 'left' === $alignment ? 'M4 5h16M4 10h10M4 15h16M4 20h10' : 'M4 5h16M4 10h16M4 15h16M4 20h16';
		$toolbar .= '<button type="button" data-reader-alignment="' . esc_attr( $alignment ) . '" aria-label="' . esc_attr( $label ) . '" aria-pressed="' . ( 'left' === $alignment ? 'true' : 'false' ) . '"><svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="' . esc_attr( $path ) . '"/></svg></button>';
	}
	$toolbar .= '</div><div class="kilka-reader-colors" role="group" aria-label="' . esc_attr__( 'Page color', 'kilka-reader' ) . '" hidden>';
	foreach ( array( 'cream' => __( 'Cream', 'kilka-reader' ), 'neutral' => __( 'Neutral', 'kilka-reader' ), 'light' => __( 'Light', 'kilka-reader' ), 'graphite' => __( 'Graphite', 'kilka-reader' ) ) as $color => $label ) {
		$toolbar .= '<button type="button" data-reader-color-option="' . esc_attr( $color ) . '" aria-label="' . esc_attr( $label ) . '" aria-pressed="' . ( 'light' === $color ? 'true' : 'false' ) . '"><span class="kilka-reader-swatch" aria-hidden="true"></span></button>';
	}
	$toolbar .= '</div><div class="kilka-reader-mode" role="group" aria-label="' . esc_attr__( 'Reading mode', 'kilka-reader' ) . '" hidden>';
	foreach ( array( 'scroll' => __( 'Continuous scrolling', 'kilka-reader' ), 'pages' => __( 'Paginated reading', 'kilka-reader' ) ) as $mode => $label ) {
		$path = 'scroll' === $mode ? 'M7 4h10M7 8h10M7 12h10M7 16h10M7 20h10' : 'M4 4h16v16H4zM9 8l-3 4 3 4m6-8l3 4-3 4';
		$toolbar .= '<button type="button" data-reader-mode="' . esc_attr( $mode ) . '" aria-label="' . esc_attr( $label ) . '" aria-pressed="' . ( 'scroll' === $mode ? 'true' : 'false' ) . '"><svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . esc_attr( $path ) . '"/></svg></button>';
	}
	$toolbar .= '</div><button type="button" class="kilka-reader-fullscreen" aria-label="' . esc_attr__( 'Enter fullscreen', 'kilka-reader' ) . '" aria-pressed="false" data-enter-label="' . esc_attr__( 'Enter fullscreen', 'kilka-reader' ) . '" data-exit-label="' . esc_attr__( 'Exit fullscreen', 'kilka-reader' ) . '" data-error="' . esc_attr__( 'Fullscreen could not be changed. You can continue reading here.', 'kilka-reader' ) . '" hidden><svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4H4v5m11-5h5v5M4 15v5h5m11-5v5h-5"/></svg></button><p class="kilka-reader-fullscreen-status" role="status" aria-live="polite"></p></div>';
	$toolbar .= '<p class="kilka-reader-hint" lang="' . esc_attr( $ui_language ) . '" role="status" aria-live="polite" data-message="' . esc_attr__( 'Tap the text to show reading controls.', 'kilka-reader' ) . '"></p>';
	$toolbar .= '<nav class="kilka-reader-pages" lang="' . esc_attr( $ui_language ) . '" aria-label="' . esc_attr__( 'Reading pages', 'kilka-reader' ) . '" hidden><button type="button" data-reader-turn="previous" aria-label="' . esc_attr__( 'Previous page', 'kilka-reader' ) . '"><svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6-6l-6 6 6 6"/></svg></button><span class="kilka-reader-page-number" role="status" aria-live="polite" aria-atomic="true" data-label="' . esc_attr__( 'Page %1$s of %2$s', 'kilka-reader' ) . '"></span><button type="button" data-reader-turn="next" aria-label="' . esc_attr__( 'Next page', 'kilka-reader' ) . '"><svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6l6 6-6 6"/></svg></button></nav>';
	$language = kilka_reader_language( get_post_meta( get_the_ID(), '_kilka_reader_language', true ) );
	return '<div class="kilka-reader" data-kilka-reader data-reader-align="left">' . $toolbar . kilka_reader_intro_markup( get_the_ID() ) . '<div class="kilka-reader-body"' . ( $language ? ' lang="' . esc_attr( $language ) . '"' : '' ) . '>' . $content . '</div>' . '</div>';
}, 20 );
