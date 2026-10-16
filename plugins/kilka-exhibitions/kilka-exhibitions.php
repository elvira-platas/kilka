<?php
/**
 * Plugin Name: Kilka Exhibitions
 * Plugin URI:  https://github.com/elvira-platas/kilka
 * Description: Provides portable exhibition content and editor foundations for the Kilka ecosystem.
 * Version:     0.2.0
 * Author:      Elvira
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kilka-exhibitions
 *
 * Development of this plugin was carried out with substantial assistance from
 * OpenAI Codex and Google Gemini under Elvira's direction.
 *
 * @package Kilka_Exhibitions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'KILKA_EXHIBITIONS_VERSION' ) ) {
	return;
}

define( 'KILKA_EXHIBITIONS_VERSION', '0.2.0' );
define( 'KILKA_EXHIBITIONS_PLUGIN_FILE', __FILE__ );

if ( ! function_exists( 'kilka_exhibitions_load_textdomain' ) ) :
	/**
	 * Load plugin translations.
	 */
	function kilka_exhibitions_load_textdomain() {
		load_plugin_textdomain( 'kilka-exhibitions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
endif;
add_action( 'plugins_loaded', 'kilka_exhibitions_load_textdomain' );

if ( ! function_exists( 'kilka_exhibitions_get_rewrite_slug' ) ) :
	/**
	 * Get the public rewrite slug for individual exhibitions.
	 *
	 * @return string
	 */
	function kilka_exhibitions_get_rewrite_slug() {
		/**
		 * Filter the public exhibition rewrite slug.
		 *
		 * @param string $slug Default rewrite slug.
		 */
		$slug = apply_filters( 'kilka_exhibitions_rewrite_slug', 'exhibitions' );
		$slug = sanitize_title( $slug );

		return '' === $slug ? 'exhibitions' : $slug;
	}
endif;

if ( ! function_exists( 'kilka_exhibitions_register_post_type' ) ) :
	/**
	 * Register the Exhibition post type.
	 */
	function kilka_exhibitions_register_post_type() {
		$labels = array(
			'name'                  => _x( 'Exhibitions', 'Post type general name', 'kilka-exhibitions' ),
			'singular_name'         => _x( 'Exhibition', 'Post type singular name', 'kilka-exhibitions' ),
			'menu_name'             => __( 'Exhibitions', 'kilka-exhibitions' ),
			'name_admin_bar'        => __( 'Exhibition', 'kilka-exhibitions' ),
			'add_new'               => __( 'Add New', 'kilka-exhibitions' ),
			'add_new_item'          => __( 'Add New Exhibition', 'kilka-exhibitions' ),
			'new_item'              => __( 'New Exhibition', 'kilka-exhibitions' ),
			'edit_item'             => __( 'Edit Exhibition', 'kilka-exhibitions' ),
			'view_item'             => __( 'View Exhibition', 'kilka-exhibitions' ),
			'all_items'             => __( 'All Exhibitions', 'kilka-exhibitions' ),
			'search_items'          => __( 'Search Exhibitions', 'kilka-exhibitions' ),
			'parent_item_colon'     => __( 'Parent Exhibitions:', 'kilka-exhibitions' ),
			'not_found'             => __( 'No exhibitions found.', 'kilka-exhibitions' ),
			'not_found_in_trash'    => __( 'No exhibitions found in Trash.', 'kilka-exhibitions' ),
			'archives'              => __( 'Exhibition Archives', 'kilka-exhibitions' ),
			'attributes'            => __( 'Exhibition Attributes', 'kilka-exhibitions' ),
			'insert_into_item'      => __( 'Insert into exhibition', 'kilka-exhibitions' ),
			'uploaded_to_this_item' => __( 'Uploaded to this exhibition', 'kilka-exhibitions' ),
			'featured_image'        => __( 'Exhibition Preview Image', 'kilka-exhibitions' ),
			'set_featured_image'    => __( 'Set exhibition preview image', 'kilka-exhibitions' ),
			'remove_featured_image' => __( 'Remove exhibition preview image', 'kilka-exhibitions' ),
			'use_featured_image'    => __( 'Use as exhibition preview image', 'kilka-exhibitions' ),
		);

		register_post_type(
			'kilka_exhibition',
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'rest_base'           => 'exhibitions',
				'query_var'           => true,
				'rewrite'             => array(
					'slug'       => kilka_exhibitions_get_rewrite_slug(),
					'with_front' => false,
				),
				'has_archive'         => false,
				'hierarchical'        => false,
				'exclude_from_search' => false,
				'menu_position'       => 6,
				'menu_icon'           => 'dashicons-format-gallery',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
				'template'            => array(
					array( 'kilka-exhibitions/sequence' ),
				),
				'template_lock'       => 'all',
			)
		);
	}
endif;
add_action( 'init', 'kilka_exhibitions_register_post_type' );

if ( ! function_exists( 'kilka_exhibitions_register_block_category' ) ) :
	/**
	 * Add a dedicated editor category for exhibition blocks.
	 *
	 * @param array $categories Existing block categories.
	 * @return array
	 */
	function kilka_exhibitions_register_block_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'kilka-exhibitions' === $category['slug'] ) {
				return $categories;
			}
		}

		$categories[] = array(
			'slug'  => 'kilka-exhibitions',
			'title' => __( 'Exhibition Spaces', 'kilka-exhibitions' ),
		);

		return $categories;
	}
endif;
add_filter( 'block_categories_all', 'kilka_exhibitions_register_block_category' );
add_filter( 'block_categories', 'kilka_exhibitions_register_block_category' );

if ( ! function_exists( 'kilka_exhibitions_sanitize_boolean' ) ) :
	/**
	 * Sanitize a REST or metadata boolean value.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	function kilka_exhibitions_sanitize_boolean( $value ) {
		if ( is_string( $value ) ) {
			return ! in_array( strtolower( $value ), array( '', '0', 'false', 'no', 'off' ), true );
		}

		return (bool) $value;
	}
endif;

if ( ! function_exists( 'kilka_exhibitions_sanitize_wall_tone' ) ) :
	/**
	 * Restrict wall tone values to supported presentation presets.
	 *
	 * @param mixed $value Raw wall tone.
	 * @return string
	 */
	function kilka_exhibitions_sanitize_wall_tone( $value ) {
		$value   = sanitize_key( $value );
		$allowed = array( 'inherit', 'warm', 'light', 'dark' );

		return in_array( $value, $allowed, true ) ? $value : 'inherit';
	}
endif;

if ( ! function_exists( 'kilka_exhibitions_register_post_meta' ) ) :
	/**
	 * Register portable exhibition-level metadata.
	 */
	function kilka_exhibitions_register_post_meta() {
		$string_schema = array(
			'type'    => 'string',
			'context' => array( 'view', 'edit' ),
		);

		register_post_meta(
			'kilka_exhibition',
			'kilka_exhibition_information_heading',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => array( 'schema' => $string_schema ),
			)
		);

		register_post_meta(
			'kilka_exhibition',
			'kilka_exhibition_creator',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => array( 'schema' => $string_schema ),
			)
		);

		register_post_meta(
			'kilka_exhibition',
			'kilka_exhibition_copyright_notice',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => array( 'schema' => $string_schema ),
			)
		);

		register_post_meta(
			'kilka_exhibition',
			'kilka_exhibition_information_panel',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'sanitize_callback' => 'kilka_exhibitions_sanitize_boolean',
				'show_in_rest'      => array(
					'schema' => array(
						'type'    => 'boolean',
						'context' => array( 'view', 'edit' ),
					),
				),
			)
		);

		register_post_meta(
			'kilka_exhibition',
			'kilka_exhibition_wall_tone',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'inherit',
				'sanitize_callback' => 'kilka_exhibitions_sanitize_wall_tone',
				'show_in_rest'      => array(
					'schema' => array(
						'type'    => 'string',
						'enum'    => array( 'inherit', 'warm', 'light', 'dark' ),
						'context' => array( 'view', 'edit' ),
					),
				),
			)
		);

		register_post_meta(
			'kilka_exhibition',
			'kilka_exhibition_search_summary',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => array( 'schema' => $string_schema ),
			)
		);
	}
endif;
add_action( 'init', 'kilka_exhibitions_register_post_meta', 11 );

if ( ! function_exists( 'kilka_exhibitions_get_information_context' ) ) :
	/**
	 * Get portable exhibition-level information for a presentation layer.
	 *
	 * @param int $post_id Exhibition post ID.
	 * @return array
	 */
	function kilka_exhibitions_get_information_context( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || 'kilka_exhibition' !== get_post_type( $post_id ) ) {
			return array();
		}

		return array(
			'heading'     => sanitize_text_field( get_post_meta( $post_id, 'kilka_exhibition_information_heading', true ) ),
			'description' => sanitize_textarea_field( get_post_field( 'post_excerpt', $post_id ) ),
			'creator'     => sanitize_text_field( get_post_meta( $post_id, 'kilka_exhibition_creator', true ) ),
			'copyright'   => sanitize_text_field( get_post_meta( $post_id, 'kilka_exhibition_copyright_notice', true ) ),
		);
	}
endif;

if ( ! function_exists( 'kilka_exhibitions_collect_information_items' ) ) :
	/**
	 * Collect ordered Image Space notes assigned to the information panel.
	 *
	 * @param array $blocks         Parsed blocks.
	 * @param int   $post_id        Exhibition post ID.
	 * @param int   $image_position Current image position in the sequence.
	 * @return array
	 */
	function kilka_exhibitions_collect_information_items( $blocks, $post_id, &$image_position = 0 ) {
		$items             = array();
		$default_creator   = sanitize_text_field( get_post_meta( $post_id, 'kilka_exhibition_creator', true ) );
		$default_copyright = sanitize_text_field( get_post_meta( $post_id, 'kilka_exhibition_copyright_notice', true ) );

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( isset( $block['blockName'] ) && 'kilka-exhibitions/image-space' === $block['blockName'] ) {
				++$image_position;

				$attributes         = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
				$caption_mode       = isset( $attributes['captionMode'] ) && is_scalar( $attributes['captionMode'] ) ? sanitize_key( $attributes['captionMode'] ) : 'information';
				$caption            = isset( $attributes['caption'] ) && is_scalar( $attributes['caption'] ) ? sanitize_textarea_field( $attributes['caption'] ) : '';
				$creator_override   = isset( $attributes['creatorOverride'] ) && is_scalar( $attributes['creatorOverride'] ) ? sanitize_text_field( $attributes['creatorOverride'] ) : '';
				$copyright_override = isset( $attributes['copyrightOverride'] ) && is_scalar( $attributes['copyrightOverride'] ) ? sanitize_text_field( $attributes['copyrightOverride'] ) : '';

				if ( 'information' === $caption_mode && '' !== $caption ) {
					$creator = '' !== trim( $creator_override )
						? $creator_override
						: $default_creator;
					$copyright = '' !== trim( $copyright_override )
						? $copyright_override
						: $default_copyright;

					$items[] = array(
						'position'  => $image_position,
						'media_id'  => isset( $attributes['mediaId'] ) ? absint( $attributes['mediaId'] ) : 0,
						'caption'   => $caption,
						'creator'   => $creator,
						'copyright' => $copyright,
					);
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$items = array_merge(
					$items,
					kilka_exhibitions_collect_information_items( $block['innerBlocks'], $post_id, $image_position )
				);
			}
		}

		return $items;
	}
endif;

if ( ! function_exists( 'kilka_exhibitions_get_information_items' ) ) :
	/**
	 * Get the ordered information-panel items for an exhibition.
	 *
	 * @param int $post_id Exhibition post ID.
	 * @return array
	 */
	function kilka_exhibitions_get_information_items( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || 'kilka_exhibition' !== get_post_type( $post_id ) ) {
			return array();
		}

		$image_position = 0;

		return kilka_exhibitions_collect_information_items(
			parse_blocks( (string) get_post_field( 'post_content', $post_id ) ),
			$post_id,
			$image_position
		);
	}
endif;

if ( ! function_exists( 'kilka_exhibitions_information_panel_enabled' ) ) :
	/**
	 * Check whether an exhibition allows its information panel to be shown.
	 *
	 * @param int $post_id Exhibition post ID.
	 * @return bool
	 */
	function kilka_exhibitions_information_panel_enabled( $post_id ) {
		$value = get_post_meta( absint( $post_id ), 'kilka_exhibition_information_panel', true );

		return '' === $value ? true : kilka_exhibitions_sanitize_boolean( $value );
	}
endif;

if ( ! function_exists( 'kilka_exhibitions_asset_version' ) ) :
	/**
	 * Return a cache-safe version for a plugin asset.
	 *
	 * @param string $relative_path Asset path relative to the plugin directory.
	 * @return string
	 */
	function kilka_exhibitions_asset_version( $relative_path ) {
		$path = plugin_dir_path( KILKA_EXHIBITIONS_PLUGIN_FILE ) . ltrim( $relative_path, '/' );

		return file_exists( $path ) ? (string) filemtime( $path ) : KILKA_EXHIBITIONS_VERSION;
	}
endif;

if ( ! function_exists( 'kilka_exhibitions_render_image_space' ) ) :
	/**
	 * Add aspect-ratio-aware display limits to a rendered Image Space.
	 *
	 * The saved markup remains portable and valid. Runtime custom properties
	 * keep a visible caption aligned with an image constrained by viewport height.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Saved block markup.
	 * @return string
	 */
	function kilka_exhibitions_render_image_space( $attributes, $content ) {
		$width  = isset( $attributes['mediaWidth'] ) ? absint( $attributes['mediaWidth'] ) : 0;
		$height = isset( $attributes['mediaHeight'] ) ? absint( $attributes['mediaHeight'] ) : 0;

		if ( ! $width || ! $height || false === strpos( $content, '<figure' ) ) {
			return $content;
		}

		$legacy_presence = array(
			'small'     => 40,
			'half'      => 50,
			'medium'    => 60,
			'large'     => 80,
			'immersive' => 100,
		);
		$height_limits = array(
			35  => 48,
			40  => 52,
			45  => 56,
			50  => 60,
			55  => 64,
			60  => 68,
			65  => 72,
			70  => 76,
			75  => 80,
			80  => 84,
			85  => 87,
			90  => 90,
			95  => 92,
			100 => 94,
		);
		$scale = isset( $attributes['scale'] ) ? sanitize_key( $attributes['scale'] ) : 'medium';

		if ( isset( $legacy_presence[ $scale ] ) ) {
			$presence = $legacy_presence[ $scale ];
		} elseif ( 1 === preg_match( '/^p(35|40|45|50|55|60|65|70|75|80|85|90|95|100)$/', $scale, $matches ) ) {
			$presence = (int) $matches[1];
		} else {
			$presence = 60;
		}

		$ratio               = $width / $height;
		$height_limit        = $height_limits[ $presence ];
		$mobile_height_limit = min( $height_limit, 82 );
		$style                = sprintf(
			'--kilka-exhibition-image-height-width:%.2fvh;--kilka-exhibition-image-mobile-height-width:%.2fvh;',
			$height_limit * $ratio,
			$mobile_height_limit * $ratio
		);

		return preg_replace( '/<figure(\s)/', '<figure style="' . esc_attr( $style ) . '"$1', $content, 1 );
	}
endif;

if ( ! function_exists( 'kilka_exhibitions_register_blocks' ) ) :
	/**
	 * Register the constrained Sequence and Space editor blocks.
	 */
	function kilka_exhibitions_register_blocks() {
		$plugin_url = plugin_dir_url( KILKA_EXHIBITIONS_PLUGIN_FILE );

		wp_register_script(
			'kilka-exhibitions-editor-blocks',
			$plugin_url . 'assets/js/editor-blocks.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n' ),
			kilka_exhibitions_asset_version( 'assets/js/editor-blocks.js' ),
			true
		);

		wp_register_style(
			'kilka-exhibitions-editor-blocks',
			$plugin_url . 'assets/css/editor-blocks.css',
			array( 'wp-edit-blocks' ),
			kilka_exhibitions_asset_version( 'assets/css/editor-blocks.css' )
		);

		wp_register_style(
			'kilka-exhibitions-blocks',
			$plugin_url . 'assets/css/blocks.css',
			array(),
			kilka_exhibitions_asset_version( 'assets/css/blocks.css' )
		);

		$block_names = array( 'sequence', 'image-space', 'text-space', 'pause-space' );

		foreach ( $block_names as $block_name ) {
			$block_args = array(
				'editor_script' => 'kilka-exhibitions-editor-blocks',
				'editor_style'  => 'kilka-exhibitions-editor-blocks',
				'style'         => 'kilka-exhibitions-blocks',
			);

			if ( 'image-space' === $block_name ) {
				$block_args['render_callback'] = 'kilka_exhibitions_render_image_space';
			}

			register_block_type_from_metadata(
				plugin_dir_path( KILKA_EXHIBITIONS_PLUGIN_FILE ) . 'blocks/' . $block_name,
				$block_args
			);
		}

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'kilka-exhibitions-editor-blocks', 'kilka-exhibitions' );
		}
	}
endif;
add_action( 'init', 'kilka_exhibitions_register_blocks', 12 );

if ( ! function_exists( 'kilka_exhibitions_enqueue_editor_settings' ) ) :
	/**
	 * Load exhibition-level controls in the block editor sidebar.
	 */
	function kilka_exhibitions_enqueue_editor_settings() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'kilka_exhibition' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'kilka-exhibitions-editor-settings',
			plugin_dir_url( KILKA_EXHIBITIONS_PLUGIN_FILE ) . 'assets/js/editor-settings.js',
			array( 'wp-components', 'wp-data', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-plugins' ),
			kilka_exhibitions_asset_version( 'assets/js/editor-settings.js' ),
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'kilka-exhibitions-editor-settings', 'kilka-exhibitions' );
		}
	}
endif;
add_action( 'enqueue_block_editor_assets', 'kilka_exhibitions_enqueue_editor_settings' );

if ( ! function_exists( 'kilka_exhibitions_activate' ) ) :
	/**
	 * Register exhibition rewrites when the plugin is activated.
	 */
	function kilka_exhibitions_activate() {
		kilka_exhibitions_register_post_type();
		flush_rewrite_rules();
	}
endif;
register_activation_hook( __FILE__, 'kilka_exhibitions_activate' );

if ( ! function_exists( 'kilka_exhibitions_deactivate' ) ) :
	/**
	 * Flush exhibition rewrites when the plugin is deactivated.
	 */
	function kilka_exhibitions_deactivate() {
		flush_rewrite_rules();
	}
endif;
register_deactivation_hook( __FILE__, 'kilka_exhibitions_deactivate' );
