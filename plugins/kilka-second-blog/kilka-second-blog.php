<?php
/**
 * Plugin Name: Kilka Second Blog
 * Plugin URI:  https://github.com/elvira-platas/my-website-theme
 * Description: Registers the Second Blog custom post type, taxonomies, and context helpers used by the Kilka theme.
 * Version:     1.0.0
 * Author:      Elvira
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kilka-second-blog
 *
 * Development of this plugin was carried out with substantial assistance from
 * OpenAI Codex and Google Gemini under Elvira's direction.
 *
 * Register custom post types for the Kilka ecosystem.
 *
 * @package Kilka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'kilka_register_world_note_post_type' ) ) {
	return;
}

if ( ! function_exists( 'kilka_get_world_note_slug' ) ) :
	/**
	 * Get the public slug for the Second Blog archive.
	 *
	 * @return string
	 */
	function kilka_get_world_note_slug() {
		$slug = sanitize_title( get_option( 'kilka_world_note_slug', 'second-blog' ) );

		if ( '' === $slug ) {
			return 'second-blog';
		}

		return $slug;
	}
endif;

if ( ! function_exists( 'kilka_sanitize_world_note_slug' ) ) :
	/**
	 * Sanitize the Second Blog slug from admin settings.
	 *
	 * @param string $value Raw slug value.
	 * @return string
	 */
	function kilka_sanitize_world_note_slug( $value ) {
		$slug = sanitize_title( $value );

		if ( '' === $slug ) {
			return 'second-blog';
		}

		return $slug;
	}
endif;

if ( ! function_exists( 'kilka_request_has_world_note_post_type' ) ) :
	/**
	 * Check whether current request explicitly targets Second Blog post type.
	 *
	 * @return bool
	 */
	function kilka_request_has_world_note_post_type() {
		$post_type = get_query_var( 'post_type' );
		if ( empty( $post_type ) && isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( is_array( $post_type ) ) {
			return in_array( 'world_note', $post_type, true );
		}

		return 'world_note' === $post_type;
	}
endif;

if ( ! function_exists( 'kilka_is_second_blog_context' ) ) :
	/**
	 * Check whether the current request belongs to the Second Blog context.
	 *
	 * @return bool
	 */
	function kilka_is_second_blog_context() {
		if ( is_post_type_archive( 'world_note' ) || is_singular( 'world_note' ) ) {
			return true;
		}

		if ( is_tax( array( 'world_note_category', 'world_note_type', 'world_note_tag' ) ) ) {
			return true;
		}

		if ( is_search() ) {
			return kilka_request_has_world_note_post_type();
		}

		// Keep context for author/date archives opened via ?post_type=world_note.
		return kilka_request_has_world_note_post_type();
	}
endif;

if ( ! function_exists( 'kilka_get_contextual_search_post_type' ) ) :
	/**
	 * Resolve the search post type based on current blog context.
	 *
	 * @return string
	 */
	function kilka_get_contextual_search_post_type() {
		return kilka_is_second_blog_context() ? 'world_note' : 'post';
	}
endif;

if ( ! function_exists( 'kilka_get_world_note_term_links' ) ) :
	/**
	 * Build linked term list for Second Blog taxonomies with explicit post_type context.
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $taxonomy   Taxonomy key.
	 * @param string $separator  Separator between links.
	 * @return string
	 */
	function kilka_get_world_note_term_links( $post_id, $taxonomy, $separator = ', ' ) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$links = array();
		foreach ( $terms as $term ) {
			$term_link = get_term_link( $term );
			if ( is_wp_error( $term_link ) ) {
				continue;
			}

			$term_link = add_query_arg( 'post_type', 'world_note', $term_link );
			$links[]   = '<a href="' . esc_url( $term_link ) . '">' . esc_html( $term->name ) . '</a>';
		}

		return implode( esc_html( $separator ), $links );
	}
endif;

if ( ! function_exists( 'kilka_filter_search_block_post_type' ) ) :
	/**
	 * Inject contextual post_type into the core Search block form.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @return string
	 */
	function kilka_filter_search_block_post_type( $block_content ) {
		if ( false === strpos( $block_content, '<form' ) ) {
			return $block_content;
		}

		$hidden_input = '<input type="hidden" name="post_type" value="' . esc_attr( kilka_get_contextual_search_post_type() ) . '" />';

		if ( false !== strpos( $block_content, 'name="post_type"' ) || false !== strpos( $block_content, "name='post_type'" ) ) {
			$updated_block_content = preg_replace( '/<input[^>]*name=["\']post_type["\'][^>]*>/', $hidden_input, $block_content, 1 );
			if ( is_string( $updated_block_content ) ) {
				return $updated_block_content;
			}
		}

		return str_replace( '</form>', $hidden_input . '</form>', $block_content );
	}
endif;
add_filter( 'render_block_core/search', 'kilka_filter_search_block_post_type' );

if ( ! function_exists( 'kilka_limit_second_blog_taxonomy_queries' ) ) :
	/**
	 * Force Second Blog taxonomy archives to query only Second Blog entries.
	 *
	 * @param WP_Query $query The main query object.
	 */
	function kilka_limit_second_blog_taxonomy_queries( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->is_tax( array( 'world_note_category', 'world_note_type', 'world_note_tag' ) ) ) {
			$query->set( 'post_type', 'world_note' );
		}
	}
endif;
add_action( 'pre_get_posts', 'kilka_limit_second_blog_taxonomy_queries' );

if ( ! function_exists( 'kilka_register_world_note_post_type' ) ) :
	/**
	 * Register the "Second Blog" post type.
	 */
	function kilka_register_world_note_post_type() {
		$labels = array(
			'name'                  => _x( 'Second Blog', 'Post type general name', 'kilka-second-blog' ),
			'singular_name'         => _x( 'Second Blog Entry', 'Post type singular name', 'kilka-second-blog' ),
			'menu_name'             => __( 'Second Blog', 'kilka-second-blog' ),
			'name_admin_bar'        => __( 'Second Blog', 'kilka-second-blog' ),
			'add_new'               => __( 'Add New', 'kilka-second-blog' ),
			'add_new_item'          => __( 'Add New Second Blog Entry', 'kilka-second-blog' ),
			'new_item'              => __( 'New Second Blog Entry', 'kilka-second-blog' ),
			'edit_item'             => __( 'Edit Second Blog Entry', 'kilka-second-blog' ),
			'view_item'             => __( 'View Second Blog Entry', 'kilka-second-blog' ),
			'all_items'             => __( 'All Second Blog Entries', 'kilka-second-blog' ),
			'search_items'          => __( 'Search Second Blog', 'kilka-second-blog' ),
			'parent_item_colon'     => __( 'Parent Second Blog Entries:', 'kilka-second-blog' ),
			'not_found'             => __( 'No second blog entries found.', 'kilka-second-blog' ),
			'not_found_in_trash'    => __( 'No second blog entries found in Trash.', 'kilka-second-blog' ),
			'archives'              => __( 'Second Blog Archives', 'kilka-second-blog' ),
			'attributes'            => __( 'Second Blog Attributes', 'kilka-second-blog' ),
			'insert_into_item'      => __( 'Insert into second blog entry', 'kilka-second-blog' ),
			'uploaded_to_this_item' => __( 'Uploaded to this second blog entry', 'kilka-second-blog' ),
		);

			$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => kilka_get_world_note_slug(),
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-book-alt',
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments', 'revisions' ),
				'show_in_rest'       => true,
				'show_in_nav_menus'  => true,
				'exclude_from_search' => true,
				'template'           => array(
					array( 'core/freeform', array() ),
				),
				'taxonomies'         => array( 'world_note_category', 'world_note_type', 'world_note_tag' ),
			);

			register_post_type( 'world_note', $args );
		}
endif;
add_action( 'init', 'kilka_register_world_note_post_type' );

if ( ! function_exists( 'kilka_register_world_note_taxonomies' ) ) :
	/**
	 * Register custom taxonomies for the Second Blog post type.
	 */
	function kilka_register_world_note_taxonomies() {
		$base_slug = kilka_get_world_note_slug();

		$category_labels = array(
			'name'              => _x( 'Second Blog Categories', 'taxonomy general name', 'kilka-second-blog' ),
			'singular_name'     => _x( 'Second Blog Category', 'taxonomy singular name', 'kilka-second-blog' ),
			'search_items'      => __( 'Search Second Blog Categories', 'kilka-second-blog' ),
			'all_items'         => __( 'All Second Blog Categories', 'kilka-second-blog' ),
			'parent_item'       => __( 'Parent Second Blog Category', 'kilka-second-blog' ),
			'parent_item_colon' => __( 'Parent Second Blog Category:', 'kilka-second-blog' ),
			'edit_item'         => __( 'Edit Second Blog Category', 'kilka-second-blog' ),
			'update_item'       => __( 'Update Second Blog Category', 'kilka-second-blog' ),
			'add_new_item'      => __( 'Add New Second Blog Category', 'kilka-second-blog' ),
			'new_item_name'     => __( 'New Second Blog Category', 'kilka-second-blog' ),
			'menu_name'         => __( 'Categories', 'kilka-second-blog' ),
		);

		register_taxonomy(
			'world_note_category',
			array( 'world_note' ),
			array(
				'labels'            => $category_labels,
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'         => $base_slug . '-category',
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);

		$type_labels = array(
			'name'              => _x( 'Second Blog Types', 'taxonomy general name', 'kilka-second-blog' ),
			'singular_name'     => _x( 'Second Blog Type', 'taxonomy singular name', 'kilka-second-blog' ),
			'search_items'      => __( 'Search Second Blog Types', 'kilka-second-blog' ),
			'all_items'         => __( 'All Second Blog Types', 'kilka-second-blog' ),
			'edit_item'         => __( 'Edit Second Blog Type', 'kilka-second-blog' ),
			'update_item'       => __( 'Update Second Blog Type', 'kilka-second-blog' ),
			'add_new_item'      => __( 'Add New Second Blog Type', 'kilka-second-blog' ),
			'new_item_name'     => __( 'New Second Blog Type', 'kilka-second-blog' ),
			'menu_name'         => __( 'Types', 'kilka-second-blog' ),
		);

		register_taxonomy(
			'world_note_type',
			array( 'world_note' ),
			array(
				'labels'            => $type_labels,
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'       => $base_slug . '-type',
					'with_front' => false,
				),
			)
		);

		$tag_labels = array(
			'name'                       => _x( 'Second Blog Tags', 'taxonomy general name', 'kilka-second-blog' ),
			'singular_name'              => _x( 'Second Blog Tag', 'taxonomy singular name', 'kilka-second-blog' ),
			'search_items'               => __( 'Search Second Blog Tags', 'kilka-second-blog' ),
			'popular_items'              => __( 'Popular Second Blog Tags', 'kilka-second-blog' ),
			'all_items'                  => __( 'All Second Blog Tags', 'kilka-second-blog' ),
			'edit_item'                  => __( 'Edit Second Blog Tag', 'kilka-second-blog' ),
			'update_item'                => __( 'Update Second Blog Tag', 'kilka-second-blog' ),
			'add_new_item'               => __( 'Add New Second Blog Tag', 'kilka-second-blog' ),
			'new_item_name'              => __( 'New Second Blog Tag', 'kilka-second-blog' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'kilka-second-blog' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'kilka-second-blog' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'kilka-second-blog' ),
			'menu_name'                  => __( 'Tags', 'kilka-second-blog' ),
		);

		register_taxonomy(
			'world_note_tag',
			array( 'world_note' ),
			array(
				'labels'            => $tag_labels,
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'       => $base_slug . '-tag',
					'with_front' => false,
				),
			)
		);
	}
endif;
add_action( 'init', 'kilka_register_world_note_taxonomies', 11 );

/**
 * Add per-entry controls for the More link presentation.
 */
function kilka_world_note_more_meta_box() {
	add_meta_box(
		'kilka-world-note-more',
		__( 'Continue Reading', 'kilka-second-blog' ),
		'kilka_render_world_note_more_meta_box',
		'world_note',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_world_note', 'kilka_world_note_more_meta_box' );

function kilka_render_world_note_more_meta_box( $post ) {
	wp_nonce_field( 'kilka_world_note_more', 'kilka_world_note_more_nonce' );
	$format = get_post_meta( $post->ID, '_kilka_world_note_more_format', true );
	if ( ! in_array( $format, array( 'text', 'arrow', 'text_arrow' ), true ) ) {
		$format = 'text';
	}
	?>
	<p><label for="kilka-world-note-more-format"><?php esc_html_e( 'Display format', 'kilka-second-blog' ); ?></label></p>
	<select id="kilka-world-note-more-format" name="kilka_world_note_more_format" class="widefat">
		<option value="text" <?php selected( $format, 'text' ); ?>><?php esc_html_e( 'Text', 'kilka-second-blog' ); ?></option>
		<option value="arrow" <?php selected( $format, 'arrow' ); ?>><?php esc_html_e( 'Arrow', 'kilka-second-blog' ); ?></option>
		<option value="text_arrow" <?php selected( $format, 'text_arrow' ); ?>><?php esc_html_e( 'Text + arrow', 'kilka-second-blog' ); ?></option>
	</select>
	<p class="description"><?php esc_html_e( 'Set any wording in the More block, or choose Arrow to hide the visible text.', 'kilka-second-blog' ); ?></p>
	<?php
}

function kilka_save_world_note_more_meta( $post_id ) {
	if ( ! isset( $_POST['kilka_world_note_more_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kilka_world_note_more_nonce'] ) ), 'kilka_world_note_more' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$format = isset( $_POST['kilka_world_note_more_format'] ) ? sanitize_key( wp_unslash( $_POST['kilka_world_note_more_format'] ) ) : 'text';
	if ( ! in_array( $format, array( 'text', 'arrow', 'text_arrow' ), true ) ) {
		$format = 'text';
	}
	update_post_meta( $post_id, '_kilka_world_note_more_format', $format );
}
add_action( 'save_post_world_note', 'kilka_save_world_note_more_meta' );

function kilka_filter_world_note_more_link( $link, $more_link_text ) {
	$post_id = get_the_ID();
	if ( 'world_note' !== get_post_type( $post_id ) ) {
		return $link;
	}

	$format = get_post_meta( $post_id, '_kilka_world_note_more_format', true );
	if ( ! in_array( $format, array( 'text', 'arrow', 'text_arrow' ), true ) ) {
		$format = 'text';
	}
	$link = preg_replace( '/class="([^"]*)"/', 'class="$1 button format-' . esc_attr( $format ) . '"', $link, 1 );

	if ( 'text' === $format ) {
		return $link;
	}

	$screen_reader = '';
	$visible_link  = $link;
	if ( preg_match( '/(<span class=["\']screen-reader-text["\']>.*?<\/span>)/s', $link, $match ) ) {
		$screen_reader = $match[1];
		$visible_link  = str_replace( $screen_reader, '', $link );
	}
	if ( '' === $screen_reader ) {
		$post_title = wp_strip_all_tags( get_the_title( $post_id ) );
		if ( 'arrow' === $format ) {
			$accessible_action  = trim( wp_strip_all_tags( $more_link_text ) );
			$accessible_action  = '' !== $accessible_action ? $accessible_action : __( 'Continue reading', 'kilka-second-blog' );
			$screen_reader_text = $accessible_action . ': ' . $post_title;
		} else {
			$screen_reader_text = ': ' . $post_title;
		}
		$screen_reader = '<span class="screen-reader-text">' . esc_html( $screen_reader_text ) . '</span>';
	}
	$visible_text = trim( wp_strip_all_tags( $visible_link ) );
	$visible_text = '' !== $visible_text ? $visible_text : $more_link_text;
	$arrow        = '<span class="kilka-button-arrow" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" focusable="false"><path d="M5 12h14M12 5l7 7-7 7"></path></svg></span>';
	$replacement  = 'arrow' === $format ? $arrow . $screen_reader : '<span class="kilka-more-text">' . esc_html( $visible_text ) . '</span>' . $arrow . $screen_reader;

	return preg_replace( '/(<a\b[^>]*>).*?(<\/a>)/s', '$1' . $replacement . '$2', $link, 1 );
}
add_filter( 'the_content_more_link', 'kilka_filter_world_note_more_link', 10, 2 );

if ( ! function_exists( 'kilka_activate_second_blog_plugin' ) ) :
	/**
	 * Register rewrite endpoints when the companion plugin is activated.
	 */
	function kilka_activate_second_blog_plugin() {
		kilka_register_world_note_post_type();
		kilka_register_world_note_taxonomies();
		flush_rewrite_rules();
	}
endif;
register_activation_hook( __FILE__, 'kilka_activate_second_blog_plugin' );

if ( ! function_exists( 'kilka_deactivate_second_blog_plugin' ) ) :
	/**
	 * Flush rewrite rules when the companion plugin is deactivated.
	 */
	function kilka_deactivate_second_blog_plugin() {
		flush_rewrite_rules();
	}
endif;
register_deactivation_hook( __FILE__, 'kilka_deactivate_second_blog_plugin' );

if ( ! function_exists( 'kilka_schedule_world_note_rewrite_flush' ) ) :
	/**
	 * Schedule a single rewrite flush after slug changes.
	 */
	function kilka_schedule_world_note_rewrite_flush() {
		update_option( 'kilka_world_note_needs_rewrite_flush', '1' );
	}
endif;

if ( ! function_exists( 'kilka_flush_rewrite_rules_on_world_note_slug_update' ) ) :
	/**
	 * Flush rewrite rules when the Second Blog slug changes.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $value     New option value.
	 */
	function kilka_flush_rewrite_rules_on_world_note_slug_update( $old_value, $value ) {
		if ( $old_value === $value ) {
			return;
		}

		kilka_schedule_world_note_rewrite_flush();
	}
endif;
add_action( 'update_option_kilka_world_note_slug', 'kilka_flush_rewrite_rules_on_world_note_slug_update', 10, 2 );

if ( ! function_exists( 'kilka_flush_rewrite_rules_on_world_note_slug_add' ) ) :
	/**
	 * Flush rewrite rules when the Second Blog slug option is added for the first time.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 */
	function kilka_flush_rewrite_rules_on_world_note_slug_add( $option, $value ) {
		if ( 'kilka_world_note_slug' !== $option ) {
			return;
		}

		kilka_schedule_world_note_rewrite_flush();
	}
endif;
add_action( 'added_option', 'kilka_flush_rewrite_rules_on_world_note_slug_add', 10, 2 );

if ( ! function_exists( 'kilka_maybe_flush_world_note_rewrite_rules' ) ) :
	/**
	 * Flush rewrite rules once when Second Blog slug was updated.
	 */
	function kilka_maybe_flush_world_note_rewrite_rules() {
		if ( '1' !== get_option( 'kilka_world_note_needs_rewrite_flush', '0' ) ) {
			return;
		}

		flush_rewrite_rules();
		delete_option( 'kilka_world_note_needs_rewrite_flush' );
	}
endif;
add_action( 'init', 'kilka_maybe_flush_world_note_rewrite_rules', 20 );

if ( ! function_exists( 'kilka_world_note_slug_field_markup' ) ) :
	/**
	 * Render Second Blog slug input in the Reading settings page.
	 */
	function kilka_world_note_slug_field_markup() {
		$current_slug = get_option( 'kilka_world_note_slug', 'second-blog' );
		?>
		<input
			name="kilka_world_note_slug"
			id="kilka_world_note_slug"
			type="text"
			class="regular-text"
			value="<?php echo esc_attr( $current_slug ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'Example: second-blog. Use lowercase letters, numbers, and hyphens only.', 'kilka-second-blog' ); ?>
		</p>
		<?php
	}
endif;

if ( ! function_exists( 'kilka_register_world_note_slug_setting' ) ) :
	/**
	 * Register the Second Blog slug setting in admin.
	 */
	function kilka_register_world_note_slug_setting() {
		register_setting(
			'reading',
			'kilka_world_note_slug',
				array(
					'type'              => 'string',
					'sanitize_callback' => 'kilka_sanitize_world_note_slug',
					'default'           => 'second-blog',
				)
			);

		add_settings_field(
			'kilka_world_note_slug',
			esc_html__( 'Second Blog URL Slug', 'kilka-second-blog' ),
			'kilka_world_note_slug_field_markup',
			'reading',
			'default'
		);
	}
endif;
add_action( 'admin_init', 'kilka_register_world_note_slug_setting' );

if ( ! function_exists( 'kilka_append_world_notes_menu_item' ) ) :
	/**
	 * Append a Second Blog link to the primary menu when missing.
	 *
	 * @param string   $items Menu items HTML.
	 * @param stdClass $args  Menu arguments.
	 * @return string
	 */
	function kilka_append_world_notes_menu_item( $items, $args ) {
		if ( ! isset( $args->theme_location ) || 'menu-1' !== $args->theme_location ) {
			return $items;
		}

		$archive_url = get_post_type_archive_link( 'world_note' );
		if ( ! $archive_url ) {
			return $items;
		}

		$archive_path = wp_parse_url( $archive_url, PHP_URL_PATH );

		// Prevent duplicates if the menu already contains a world notes link.
		if ( false !== strpos( $items, 'world_note' ) ) {
			return $items;
		}

		if ( is_string( $archive_path ) && '' !== $archive_path ) {
			$archive_path = trim( $archive_path, '/' );
			if ( '' !== $archive_path && false !== strpos( $items, $archive_path ) ) {
				return $items;
			}
		}

		if ( false !== strpos( $items, esc_url( $archive_url ) ) ) {
			return $items;
		}

		$item_classes = 'menu-item menu-item-type-custom menu-item-second-blog';
		if ( is_post_type_archive( 'world_note' ) || is_singular( 'world_note' ) ) {
			$item_classes .= ' current-menu-item';
		}

		$items .= sprintf(
			'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
			esc_attr( $item_classes ),
			esc_url( $archive_url ),
			esc_html__( 'Second Blog', 'kilka-second-blog' )
		);

		return $items;
	}
endif;
add_filter( 'wp_nav_menu_items', 'kilka_append_world_notes_menu_item', 10, 2 );

require_once plugin_dir_path( __FILE__ ) . 'includes/typography.php';
