<?php
/** Optional, portable full-screen introduction for a reading document. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function kilka_reader_intro_defaults() {
	return array( 'enabled' => false, 'image' => 0, 'mobile_image' => 0, 'title' => '', 'author' => '', 'line' => '', 'hide_line' => false, 'title_font' => 'serif', 'text_font' => 'sans', 'title_size' => 64, 'text_size' => 22, 'align' => 'left', 'position' => 'center', 'mobile_position' => 'top', 'ink' => '#ffffff', 'background' => '#17313b', 'shade' => 30, 'desktop_x' => 50, 'desktop_y' => 50, 'tablet_x' => 50, 'tablet_y' => 50, 'mobile_x' => 50, 'mobile_y' => 50 );
}
function kilka_reader_intro_sanitize( $input ) {
	$out = kilka_reader_intro_defaults();
	if ( ! is_array( $input ) ) { return $out; }
	foreach ( $out as $key => $default ) {
		if ( ! isset( $input[$key] ) || ! is_scalar( $input[$key] ) ) { continue; }
		$v = $input[$key];
		if ( in_array( $key, array( 'enabled', 'hide_line' ), true ) ) { $out[$key] = ! empty( $v ); }
		elseif ( in_array( $key, array( 'image', 'mobile_image' ), true ) ) { $out[$key] = wp_attachment_is_image( absint( $v ) ) ? absint( $v ) : 0; }
		elseif ( 'title' === $key ) { $out[$key] = sanitize_textarea_field( $v ); }
		elseif ( in_array( $key, array( 'author', 'line' ), true ) ) { $out[$key] = sanitize_text_field( $v ); }
		elseif ( in_array( $key, array( 'title_font', 'text_font' ), true ) ) { $out[$key] = in_array( $v, array( 'serif', 'sans' ), true ) ? $v : $default; }
		elseif ( in_array( $key, array( 'position', 'mobile_position' ), true ) ) { $out[$key] = in_array( $v, array( 'top', 'center', 'bottom' ), true ) ? $v : $default; }
		elseif ( 'align' === $key ) { $out[$key] = in_array( $v, array( 'left', 'center', 'right' ), true ) ? $v : $default; }
		elseif ( in_array( $key, array( 'ink', 'background' ), true ) ) { $out[$key] = sanitize_hex_color( $v ) ?: $default; }
		else {
			$min = 'title_size' === $key ? 32 : ( 'text_size' === $key ? 16 : 0 );
			$max = 'title_size' === $key ? 100 : ( 'text_size' === $key ? 36 : ( 'shade' === $key ? 80 : 100 ) );
			$out[$key] = max( $min, min( $max, (int) $v ) );
		}
	}
	return $out;
}
function kilka_reader_intro_settings( $id ) {
	return kilka_reader_intro_sanitize( get_post_meta( $id, '_kilka_reader_intro', true ) );
}
function kilka_reader_intro_fonts() {
	return array( 'serif' => 'Georgia, "Times New Roman", serif', 'sans' => 'system-ui, -apple-system, "Segoe UI", sans-serif' );
}
function kilka_reader_intro_style( $s ) {
	$fonts = kilka_reader_intro_fonts();
	$style = '--intro-ink:' . $s['ink'] . ';--intro-bg:' . $s['background'] . ';--intro-shade:' . ( $s['shade'] / 100 ) . ';--intro-title-font:' . $fonts[$s['title_font']] . ';--intro-text-font:' . $fonts[$s['text_font']] . ';--intro-title-size:' . $s['title_size'] . 'px;--intro-text-size:' . $s['text_size'] . 'px;--intro-align:' . $s['align'] . ';';
	foreach ( array( 'desktop', 'tablet', 'mobile' ) as $screen ) {
		$style .= '--intro-' . $screen . '-x:' . $s[$screen . '_x'] . '%;--intro-' . $screen . '-y:' . $s[$screen . '_y'] . '%;';
	}
	return $style;
}
function kilka_reader_intro_markup( $id ) {
	$s = kilka_reader_intro_settings( $id );
	if ( ! $s['enabled'] ) { return ''; }
	$title = '' !== $s['title'] ? $s['title'] : get_the_title( $id );
	$language = kilka_reader_language( get_post_meta( $id, '_kilka_reader_language', true ) );
	$html = '<section' . ( $language ? ' lang="' . esc_attr( $language ) . '"' : '' ) . ' class="kilka-reader-intro" aria-label="' . esc_attr__( 'Story introduction', 'kilka-reader' ) . '" data-position="' . esc_attr( $s['position'] ) . '" data-mobile-position="' . esc_attr( $s['mobile_position'] ) . '" data-align="' . esc_attr( $s['align'] ) . '" style="' . esc_attr( kilka_reader_intro_style( $s ) ) . '">';
	if ( $s['image'] ) {
		$html .= '<picture class="kilka-reader-intro__picture">';
		if ( $s['mobile_image'] ) {
			$html .= '<source media="(max-width: 767px)" srcset="' . esc_url( wp_get_attachment_image_url( $s['mobile_image'], 'full' ) ) . '">';
		}
		$html .= wp_get_attachment_image( $s['image'], 'full', false, array( 'class' => 'kilka-reader-intro__image', 'alt' => '', 'loading' => 'eager', 'sizes' => '100vw' ) ) . '</picture>';
	}
	$html .= '<div class="kilka-reader-intro__copy"><h2 class="kilka-reader-intro__title">' . esc_html( $title ) . '</h2>';
	if ( $s['author'] ) { $html .= '<p class="kilka-reader-intro__author">' . esc_html( $s['author'] ) . '</p>'; }
	if ( $s['line'] ) { $html .= '<p class="kilka-reader-intro__line' . ( $s['hide_line'] ? ' hide-on-small' : '' ) . '">' . esc_html( $s['line'] ) . '</p>'; }
	return $html . '</div></section>';
}
add_action( 'init', function () {
	register_post_meta( 'page', '_kilka_reader_intro', array( 'type' => 'object', 'single' => true, 'default' => kilka_reader_intro_defaults(), 'show_in_rest' => false, 'sanitize_callback' => 'kilka_reader_intro_sanitize', 'auth_callback' => function ( $allowed, $key, $id ) { return current_user_can( 'edit_post', $id ); } ) );
} );
function kilka_reader_intro_editor( $post ) {
	$s = kilka_reader_intro_settings( $post->ID );
	echo '<div class="kilka-intro-editor" data-page-title="' . esc_attr( get_the_title( $post ) ) . '"><p>' . esc_html__( 'Optional introduction for the Reading template. The next page opens the story. Image cropping never changes the original file.', 'kilka-reader' ) . '</p>';
	foreach ( array( 'enabled' => __( 'Enable introduction', 'kilka-reader' ), 'hide_line' => __( 'Hide the optional line on phones', 'kilka-reader' ) ) as $key => $label ) {
		echo '<p><label><input type="checkbox" name="kilka_intro[' . esc_attr( $key ) . ']" value="1" ' . checked( $s[$key], true, false ) . '> ' . esc_html( $label ) . '</label></p>';
	}
	foreach ( array( 'image' => __( 'Background image', 'kilka-reader' ), 'mobile_image' => __( 'Optional phone image', 'kilka-reader' ) ) as $key => $label ) {
		echo '<p>' . esc_html( $label ) . ' <input type="hidden" name="kilka_intro[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[$key] ) . '" data-url="' . esc_url( wp_get_attachment_image_url( $s[$key], 'full' ) ?: '' ) . '"><button type="button" class="button" data-intro-media="' . esc_attr( $key ) . '">' . esc_html__( 'Choose image', 'kilka-reader' ) . '</button> <button type="button" class="button" data-intro-remove="' . esc_attr( $key ) . '">' . esc_html__( 'Remove image', 'kilka-reader' ) . '</button></p>';
	}
	echo '<div class="kilka-intro-fields">';
	echo '<label>' . esc_html__( 'Title (empty uses page title)', 'kilka-reader' ) . '<textarea name="kilka_intro[title]" rows="3" aria-describedby="kilka-intro-title-help">' . esc_textarea( $s['title'] ) . '</textarea><span class="description" id="kilka-intro-title-help">' . esc_html__( 'Press Enter to start a new line. Narrow screens may wrap each line further.', 'kilka-reader' ) . '</span></label>';
	foreach ( array( 'author' => __( 'Author (optional)', 'kilka-reader' ), 'line' => __( 'Short line (optional)', 'kilka-reader' ), 'ink' => __( 'Text color', 'kilka-reader' ), 'background' => __( 'Background color', 'kilka-reader' ) ) as $key => $label ) {
		$type = in_array( $key, array( 'ink', 'background' ), true ) ? 'color' : 'text';
		echo '<label>' . esc_html( $label ) . '<input type="' . esc_attr( $type ) . '" name="kilka_intro[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[$key] ) . '"></label>';
	}
	$options = array( 'title_font' => array( __( 'Title font', 'kilka-reader' ), array( 'serif' => __( 'Serif (Georgia)', 'kilka-reader' ), 'sans' => __( 'System sans serif', 'kilka-reader' ) ) ), 'text_font' => array( __( 'Other text font', 'kilka-reader' ), array( 'serif' => __( 'Serif (Georgia)', 'kilka-reader' ), 'sans' => __( 'System sans serif', 'kilka-reader' ) ) ), 'align' => array( __( 'Text alignment', 'kilka-reader' ), array( 'left' => __( 'Left', 'kilka-reader' ), 'center' => __( 'Center', 'kilka-reader' ), 'right' => __( 'Right', 'kilka-reader' ) ) ) );
	foreach ( array( 'position' => __( 'Text position', 'kilka-reader' ), 'mobile_position' => __( 'Phone text position', 'kilka-reader' ) ) as $key => $label ) { $options[$key] = array( $label, array( 'top' => __( 'Top', 'kilka-reader' ), 'center' => __( 'Center', 'kilka-reader' ), 'bottom' => __( 'Bottom', 'kilka-reader' ) ) ); }
	foreach ( $options as $key => $option ) {
		echo '<label>' . esc_html( $option[0] ) . '<select name="kilka_intro[' . esc_attr( $key ) . ']">';
		foreach ( $option[1] as $value => $label ) { echo '<option value="' . esc_attr( $value ) . '" ' . selected( $s[$key], $value, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></label>';
	}
	$ranges = array( 'title_size' => array( __( 'Title size', 'kilka-reader' ), 32, 100 ), 'text_size' => array( __( 'Other text size', 'kilka-reader' ), 16, 36 ), 'shade' => array( __( 'Image darkening (%)', 'kilka-reader' ), 0, 80 ) );
	foreach ( array( 'desktop' => __( 'Desktop', 'kilka-reader' ), 'tablet' => __( 'Tablet', 'kilka-reader' ), 'mobile' => __( 'Phone', 'kilka-reader' ) ) as $screen => $label ) {
		$ranges[$screen . '_x'] = array( $label . ' — ' . __( 'Image horizontal position (%)', 'kilka-reader' ), 0, 100 );
		$ranges[$screen . '_y'] = array( $label . ' — ' . __( 'Image vertical position (%)', 'kilka-reader' ), 0, 100 );
	}
	foreach ( $ranges as $key => $range ) { echo '<label>' . esc_html( $range[0] ) . '<input type="range" name="kilka_intro[' . esc_attr( $key ) . ']" min="' . esc_attr( $range[1] ) . '" max="' . esc_attr( $range[2] ) . '" value="' . esc_attr( $s[$key] ) . '"><output>' . esc_html( $s[$key] ) . '</output></label>'; }
	echo '</div><p><label>' . esc_html__( 'Preview size', 'kilka-reader' ) . ' <select data-intro-preview-size><option value="desktop">' . esc_html__( 'Desktop', 'kilka-reader' ) . '</option><option value="tablet">' . esc_html__( 'Tablet', 'kilka-reader' ) . '</option><option value="phone">' . esc_html__( 'Phone', 'kilka-reader' ) . '</option></select></label></p><div class="kilka-intro-preview"><iframe title="' . esc_attr__( 'Introduction preview', 'kilka-reader' ) . '" sandbox="allow-same-origin"></iframe></div><p>' . esc_html__( 'Preview shows layout, not reader controls. Check readability on a real phone before publishing. Reading colors and text size are independent of this introduction.', 'kilka-reader' ) . '</p></div>';
}
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_kilka-reader' !== $hook ) { return; }
	wp_enqueue_media();
	wp_enqueue_style( 'kilka-intro-editor', plugins_url( 'assets/intro-editor.css', dirname( __DIR__ ) . '/kilka-reader.php' ), array(), filemtime( __DIR__ . '/../assets/intro-editor.css' ) );
	wp_enqueue_script( 'kilka-intro-editor', plugins_url( 'assets/intro-editor.js', dirname( __DIR__ ) . '/kilka-reader.php' ), array( 'media-editor', 'wp-data' ), filemtime( __DIR__ . '/../assets/intro-editor.js' ), true );
	wp_localize_script( 'kilka-intro-editor', 'kilkaIntroPreview', array( 'css' => add_query_arg( 'ver', filemtime( __DIR__ . '/../assets/intro.css' ), plugins_url( 'assets/intro.css', dirname( __DIR__ ) . '/kilka-reader.php' ) ) ) );
} );
