<?php
/** Portable typography for Second Blog entry text. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kilka_note_fonts() {
	return array(
		'literata'      => 'Literata',
		'plex'          => 'IBM Plex Sans',
		'kelly'         => 'Kelly Slab',
		'bad-script'    => 'Bad Script',
		'hachi'         => 'Hachi Maru Pop',
	);
}

function kilka_note_sanitize_font( $value ) {
	return is_string( $value ) && array_key_exists( $value, kilka_note_fonts() ) ? $value : 'plex';
}

function kilka_note_sanitize_title_font( $value ) {
	$fonts = kilka_note_fonts();
	return is_string( $value ) && isset( $fonts[ $value ] ) ? $value : '';
}

function kilka_note_title_font( $post_id ) {
	$font = kilka_note_sanitize_title_font( get_post_meta( $post_id, '_kilka_note_title_font', true ) );
	return '' !== $font ? $font : kilka_note_sanitize_font( get_post_meta( $post_id, '_kilka_note_font', true ) );
}

function kilka_note_font_meta() {
	// REST meta saving requires custom-fields support; the raw meta box is hidden below.
	add_post_type_support( 'world_note', 'custom-fields' );
	register_post_meta( 'world_note', '_kilka_note_font', array(
		'type'              => 'string',
		'single'            => true,
		'default'           => 'plex',
		'sanitize_callback' => 'kilka_note_sanitize_font',
		'auth_callback'     => 'kilka_note_font_auth',
		'show_in_rest'      => array( 'schema' => array( 'type' => 'string', 'enum' => array_keys( kilka_note_fonts() ) ) ),
		'revisions_enabled' => true,
	) );
	register_post_meta( 'world_note', '_kilka_note_title_font', array(
		'type'              => 'string',
		'single'            => true,
		'default'           => '',
		'sanitize_callback' => 'kilka_note_sanitize_title_font',
		'auth_callback'     => 'kilka_note_font_auth',
		'show_in_rest'      => array( 'schema' => array( 'type' => 'string', 'enum' => array_merge( array( '' ), array_keys( kilka_note_fonts() ) ) ) ),
		'revisions_enabled' => true,
	) );
}
add_action( 'init', 'kilka_note_font_meta', 12 );

function kilka_note_font_auth( $allowed, $key, $post_id ) {
	return 'world_note' === get_post_type( $post_id ) && current_user_can( 'edit_post', $post_id );
}

function kilka_note_editor_context() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $screen && 'world_note' === $screen->post_type;
}

function kilka_note_asset_url( $file ) {
	return plugins_url( 'assets/' . $file, dirname( __FILE__ ) );
}

function kilka_note_styles() {
	foreach ( array( 'fonts', 'typography' ) as $name ) {
		wp_enqueue_style( 'kilka-note-' . $name, kilka_note_asset_url( $name . '.css' ), array(), filemtime( dirname( __DIR__ ) . '/assets/' . $name . '.css' ) );
	}
}

function kilka_note_public_styles() {
	// Covers mixed query results without making the main blog load these fonts.
	global $wp_query;
	foreach ( (array) $wp_query->posts as $post ) {
		if ( $post instanceof WP_Post && 'world_note' === $post->post_type ) {
			kilka_note_styles();
			break;
		}
	}
}
add_action( 'wp_enqueue_scripts', 'kilka_note_public_styles', 40 );

function kilka_note_wrap_text( $text ) {
	if ( 'world_note' !== get_post_type() || is_feed() || is_admin() || '' === trim( $text ) ) {
		return $text;
	}
	$font = kilka_note_sanitize_font( get_post_meta( get_the_ID(), '_kilka_note_font', true ) );
	return '<div class="kilka-note-prose kilka-note-entry-font-' . esc_attr( $font ) . '">' . $text . '</div>';
}
add_filter( 'the_content', 'kilka_note_wrap_text', 20 );
add_filter( 'the_excerpt', 'kilka_note_wrap_text', 20 );

function kilka_note_title_class( $classes, $class, $post_id ) {
	if ( 'world_note' === get_post_type( $post_id ) ) {
		$classes[] = 'kilka-note-title-' . kilka_note_title_font( $post_id );
	}
	return $classes;
}
add_filter( 'post_class', 'kilka_note_title_class', 10, 3 );

function kilka_note_editor_styles() {
	if ( is_admin() && kilka_note_editor_context() ) {
		kilka_note_styles();
	}
}
add_action( 'enqueue_block_assets', 'kilka_note_editor_styles' );

function kilka_note_classic_styles() {
	if ( kilka_note_editor_context() && ! get_current_screen()->is_block_editor() ) {
		kilka_note_styles();
	}
}
add_action( 'admin_enqueue_scripts', 'kilka_note_classic_styles' );

function kilka_note_editor_config() {
	global $post;
	return array(
		'fonts'     => kilka_note_fonts(),
		'entryFont' => $post ? kilka_note_sanitize_font( get_post_meta( $post->ID, '_kilka_note_font', true ) ) : 'plex',
		'titleFont' => $post ? kilka_note_sanitize_title_font( get_post_meta( $post->ID, '_kilka_note_title_font', true ) ) : '',
		'labels'    => array(
			'panel'     => __( 'Second Blog typography', 'kilka-second-blog' ),
			'entry'     => __( 'Entry text font', 'kilka-second-blog' ),
			'title'     => __( 'Entry title font', 'kilka-second-blog' ),
			'paragraph' => __( 'Paragraph font', 'kilka-second-blog' ),
			'heading'   => __( 'Heading font', 'kilka-second-blog' ),
			'inherit'   => __( 'Use entry font', 'kilka-second-blog' ),
			'help'      => __( 'Choose any font for this block, or inherit the entry font.', 'kilka-second-blog' ),
		),
	);
}

function kilka_note_editor_script() {
	if ( ! kilka_note_editor_context() ) {
		return;
	}
	wp_enqueue_script( 'kilka-note-editor', kilka_note_asset_url( 'editor.js' ), array( 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-data', 'wp-edit-post', 'wp-element', 'wp-hooks', 'wp-plugins' ), filemtime( dirname( __DIR__ ) . '/assets/editor.js' ), true );
	wp_localize_script( 'kilka-note-editor', 'kilkaNoteTypography', kilka_note_editor_config() );
}
add_action( 'enqueue_block_editor_assets', 'kilka_note_editor_script' );

function kilka_note_meta_box( $post ) {
	remove_meta_box( 'postcustom', 'world_note', 'normal' );
	if ( ! use_block_editor_for_post( $post ) ) {
		add_meta_box( 'kilka-note-typography', __( 'Second Blog typography', 'kilka-second-blog' ), 'kilka_note_meta_box_html', 'world_note', 'side' );
	}
}
add_action( 'add_meta_boxes_world_note', 'kilka_note_meta_box' );

function kilka_note_meta_box_html( $post ) {
	wp_nonce_field( 'kilka_note_font', 'kilka_note_font_nonce' );
	$value = kilka_note_sanitize_font( get_post_meta( $post->ID, '_kilka_note_font', true ) );
	echo '<label for="kilka-note-entry-font">' . esc_html__( 'Entry text font', 'kilka-second-blog' ) . '</label><select class="widefat" id="kilka-note-entry-font" name="kilka_note_font">';
	foreach ( kilka_note_fonts() as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
	$title_font = kilka_note_sanitize_title_font( get_post_meta( $post->ID, '_kilka_note_title_font', true ) );
	echo '<p><label for="kilka-note-title-font">' . esc_html__( 'Entry title font', 'kilka-second-blog' ) . '</label><select class="widefat" id="kilka-note-title-font" name="kilka_note_title_font">';
	foreach ( array_merge( array( '' => __( 'Use entry font', 'kilka-second-blog' ) ), kilka_note_fonts() ) as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $title_font, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></p>';
}

function kilka_note_save_classic_font( $post_id ) {
	if ( ! isset( $_POST['kilka_note_font_nonce'], $_POST['kilka_note_font'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kilka_note_font_nonce'] ) ), 'kilka_note_font' ) || ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	update_post_meta( $post_id, '_kilka_note_font', kilka_note_sanitize_font( wp_unslash( $_POST['kilka_note_font'] ) ) );
	if ( isset( $_POST['kilka_note_title_font'] ) ) {
		update_post_meta( $post_id, '_kilka_note_title_font', kilka_note_sanitize_title_font( wp_unslash( $_POST['kilka_note_title_font'] ) ) );
	}
}
add_action( 'save_post_world_note', 'kilka_note_save_classic_font' );

function kilka_note_mce_plugins( $plugins ) {
	if ( kilka_note_editor_context() ) {
		$plugins['kilka_note_font'] = kilka_note_asset_url( 'classic.js' ) . '?ver=' . filemtime( dirname( __DIR__ ) . '/assets/classic.js' );
	}
	return $plugins;
}
add_filter( 'mce_external_plugins', 'kilka_note_mce_plugins' );

function kilka_note_mce_buttons( $buttons ) {
	if ( kilka_note_editor_context() ) {
		$buttons[] = 'kilka_note_font';
	}
	return $buttons;
}
add_filter( 'mce_buttons', 'kilka_note_mce_buttons' );

function kilka_note_mce_settings( $settings ) {
	if ( ! kilka_note_editor_context() ) {
		return $settings;
	}
	$config = kilka_note_editor_config();
	$settings['kilka_note_config'] = wp_json_encode( $config );
	$settings['body_class'] = ( isset( $settings['body_class'] ) ? $settings['body_class'] : '' ) . ' kilka-note-prose kilka-note-entry-font-' . $config['entryFont'];
	foreach ( array( 'fonts', 'typography' ) as $name ) {
		$settings['content_css'] = ( empty( $settings['content_css'] ) ? '' : $settings['content_css'] . ',' ) . kilka_note_asset_url( $name . '.css' ) . '?ver=' . filemtime( dirname( __DIR__ ) . '/assets/' . $name . '.css' );
	}
	return $settings;
}
add_filter( 'tiny_mce_before_init', 'kilka_note_mce_settings' );
