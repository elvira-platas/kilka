<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package Kilka
 */

/**
 * Get the announcement eligible for the first page of the Main Blog.
 *
 * @return array|false Plain text and optional URL, or false when inactive.
 */
function kilka_get_active_announcement() {
	if ( ! is_home() || is_paged() || ! get_theme_mod( 'kilka_announcement_enabled', false ) ) {
		return false;
	}

	$text = sanitize_text_field( get_theme_mod( 'kilka_announcement_text', '' ) );
	if ( '' === $text ) {
		return false;
	}

	$from  = kilka_sanitize_announcement_date( get_theme_mod( 'kilka_announcement_from', '' ) );
	$until = kilka_sanitize_announcement_date( get_theme_mod( 'kilka_announcement_until', '' ) );
	$today = current_datetime()->format( 'Y-m-d' );

	// ISO calendar dates sort chronologically without UTC conversion.
	if ( ( $from && $until && $from > $until ) || ( $from && $today < $from ) || ( $until && $today > $until ) ) {
		return false;
	}

	return array(
		'text' => $text,
		'url'  => esc_url_raw( get_theme_mod( 'kilka_announcement_url', '' ) ),
	);
}

/**
 * Get the widget area for the current blog context.
 *
 * @return string Sidebar ID.
 */
function kilka_get_contextual_sidebar_id() {
	if ( function_exists( 'kilka_is_second_blog_context' ) && kilka_is_second_blog_context() ) {
		return 'sidebar-second-blog';
	}

	return 'sidebar-1';
}

/**
 * Check whether the current request uses an authored Exhibition canvas.
 *
 * Exhibition colors belong to the presentation itself rather than to the
 * visitor's saved reading preference.
 *
 * @return bool
 */
function kilka_is_exhibition_context() {
	return is_singular( 'kilka_exhibition' );
}

/**
 * Check whether the current blog context has an active widget area.
 *
 * @return bool
 */
function kilka_has_contextual_sidebar() {
	// Exhibitions and reading documents never inherit blog widgets.
	if ( is_singular( 'kilka_exhibition' ) || kilka_is_reading_context() ) {
		return false;
	}

	$sidebar_id = kilka_get_contextual_sidebar_id();

	if ( 'sidebar-1' === $sidebar_id && ! get_theme_mod( 'kilka_show_main_blog_sidebar', false ) ) {
		return false;
	}

	return is_active_sidebar( $sidebar_id );
}

/**
 * Check whether the current page belongs to either blog search context.
 *
 * @return bool
 */
function kilka_is_blog_search_context() {
	if ( function_exists( 'kilka_is_second_blog_context' ) && kilka_is_second_blog_context() ) {
		return true;
	}

	if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_author() || is_date() ) {
		return true;
	}

	if ( is_search() ) {
		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) ) {
			return in_array( 'post', $post_type, true );
		}

		return empty( $post_type ) || 'post' === $post_type;
	}

	return false;
}

/**
 * Add contextual search to the primary menu for both blogs.
 *
 * Search remains a theme presentation concern. The companion plugin only
 * supplies the post type context used by the shared search form.
 *
 * @param string   $items Menu items HTML.
 * @param stdClass $args  Menu arguments.
 * @return string
 */
function kilka_append_contextual_search_menu_item( $items, $args ) {
	if ( ! isset( $args->theme_location ) || 'menu-1' !== $args->theme_location || ! kilka_is_blog_search_context() || false !== strpos( $items, 'kilka-menu-search' ) ) {
		return $items;
	}

	return $items . '<li class="menu-item menu-item-search kilka-menu-search">' . get_search_form( false ) . '</li>';
}
add_filter( 'wp_nav_menu_items', 'kilka_append_contextual_search_menu_item', 11, 2 );

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function kilka_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	if ( is_singular( 'kilka_exhibition' ) ) {
		$classes[] = has_nav_menu( 'menu-1' ) ? 'kilka-exhibition-has-menu' : 'kilka-exhibition-no-menu';
	}

	$is_second_blog_context = function_exists( 'kilka_is_second_blog_context' ) && kilka_is_second_blog_context();
	if ( $is_second_blog_context ) {
		$classes[] = 'kilka-second-blog-context';
	} elseif ( kilka_is_blog_search_context() ) {
		$classes[] = 'kilka-main-blog-context';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! kilka_has_contextual_sidebar() ) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'kilka_body_classes' );

/**
 * Register the optional floating presentation for standalone image blocks.
 */
function kilka_register_floating_image_block_style() {
	register_block_style(
		'core/image',
		array(
			'name'  => 'kilka-floating',
			'label' => __( 'Floating image', 'kilka' ),
		)
	);
}
add_action( 'init', 'kilka_register_floating_image_block_style' );

/**
 * Disable the XML-RPC methods used by pingbacks.
 *
 * @param array $methods XML-RPC methods exposed by WordPress.
 * @return array
 */
function kilka_disable_pingbacks( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

	return $methods;
}
add_filter( 'xmlrpc_methods', 'kilka_disable_pingbacks' );

/**
 * Keep public emoji as Unicode instead of loading fallback images from a CDN.
 * Cover ordinary pages, WordPress embed cards and RSS output.
 */
function kilka_use_native_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'embed_head', 'print_emoji_detection_script' );
	remove_action( 'wp_print_footer_scripts', '_print_emoji_detection_script' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'enqueue_embed_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
}
add_action( 'init', 'kilka_use_native_emoji' );

/**
 * Describe the Main Blog content column when WordPress builds responsive images.
 * Explicit sizes in saved markup remain under the author's control.
 *
 * @param string       $sizes Default source sizes.
 * @param string|int[] $size  Requested image size.
 * @return string Source sizes for this layout.
 */
function kilka_main_content_image_sizes( $sizes, $size ) {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop()
		|| ! is_main_query() || ! doing_filter( 'the_content' )
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ! is_array( $size ) || empty( $size[0] ) ) {
		return $sizes;
	}

	// Match article padding, grid breakpoints and the 1000px content limit.
	$width_limit = min( 1000, absint( $size[0] ) );
	$desktop     = kilka_has_contextual_sidebar()
		? 'min(calc(66.667vw - 90px), 763.34px, %1$dpx)'
		: 'min(calc(100vw - 90px), %1$dpx)';

	return sprintf(
		'(max-width: 575px) min(calc(100vw - 90px), %1$dpx), (max-width: 767px) %2$dpx, (max-width: 991px) %3$dpx, ' . $desktop,
		$width_limit,
		min( 450, $width_limit ),
		min( 630, $width_limit )
	);
}
add_filter( 'wp_calculate_image_sizes', 'kilka_main_content_image_sizes', 10, 2 );
