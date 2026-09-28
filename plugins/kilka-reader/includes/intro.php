<?php
/** Optional, portable full-screen introduction for a reading document. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function kilka_reader_intro_defaults() {
	return array( 'enabled' => false, 'image' => 0, 'mobile_image' => 0, 'title' => '', 'phone_breaks_enabled' => false, 'phone_breaks' => '', 'author' => '', 'line' => '', 'hide_line' => false, 'title_font' => 'serif', 'title_weight' => 400, 'title_italic' => false, 'text_font' => 'sans', 'text_weight' => 400, 'text_italic' => false, 'title_size' => 64, 'text_size' => 22, 'line_size' => 22, 'align' => 'left', 'position' => 'center', 'mobile_position' => 'top', 'ink' => '#ffffff', 'background' => '#17313b', 'shade' => 30, 'desktop_x' => 50, 'desktop_y' => 50, 'tablet_x' => 50, 'tablet_y' => 50, 'mobile_x' => 50, 'mobile_y' => 50 );
}
function kilka_reader_intro_sanitize( $input ) {
	$out = kilka_reader_intro_defaults();
	if ( ! is_array( $input ) ) { return $out; }
	foreach ( $out as $key => $default ) {
		if ( ! isset( $input[$key] ) || ! is_scalar( $input[$key] ) ) { continue; }
		$v = $input[$key];
		if ( in_array( $key, array( 'enabled', 'hide_line', 'title_italic', 'text_italic', 'phone_breaks_enabled' ), true ) ) { $out[$key] = ! empty( $v ); }
		elseif ( in_array( $key, array( 'image', 'mobile_image' ), true ) ) { $out[$key] = wp_attachment_is_image( absint( $v ) ) ? absint( $v ) : 0; }
		elseif ( 'phone_breaks' === $key ) {
			$breaks = array_filter( array_map( 'intval', explode( ',', substr( (string) $v, 0, 2000 ) ) ), function ( $n ) { return $n > 0 && $n <= 500; } );
			$breaks = array_unique( $breaks ); sort( $breaks, SORT_NUMERIC ); $out[$key] = implode( ',', $breaks );
		}
		elseif ( 'title' === $key ) { $out[$key] = sanitize_textarea_field( $v ); }
		elseif ( in_array( $key, array( 'author', 'line' ), true ) ) { $out[$key] = sanitize_text_field( $v ); }
		elseif ( in_array( $key, array( 'title_font', 'text_font' ), true ) ) { $out[$key] = is_string( $v ) && isset( kilka_reader_intro_font_catalog()[$v] ) ? $v : $default; }
		elseif ( in_array( $key, array( 'title_weight', 'text_weight' ), true ) ) { $out[$key] = (int) $v; }
		elseif ( in_array( $key, array( 'position', 'mobile_position' ), true ) ) { $out[$key] = in_array( $v, array( 'top', 'center', 'bottom' ), true ) ? $v : $default; }
		elseif ( 'align' === $key ) { $out[$key] = in_array( $v, array( 'left', 'center', 'right' ), true ) ? $v : $default; }
		elseif ( in_array( $key, array( 'ink', 'background' ), true ) ) { $out[$key] = sanitize_hex_color( $v ) ?: $default; }
		else {
			$min = 'title_size' === $key ? 32 : ( in_array( $key, array( 'text_size', 'line_size' ), true ) ? 16 : 0 );
			$max = 'title_size' === $key ? 100 : ( 'text_size' === $key ? 48 : ( 'line_size' === $key ? 36 : ( 'shade' === $key ? 80 : 100 ) ) );
			$out[$key] = max( $min, min( $max, (int) $v ) );
		}
	}
	// Preserve the previous shared size until this document saves an independent line size.
	if ( ! isset( $input['line_size'] ) || ! is_scalar( $input['line_size'] ) ) { $out['line_size'] = $out['text_size']; }
	foreach ( array( 'title', 'text' ) as $group ) {
		$font = kilka_reader_intro_font_catalog()[$out[$group . '_font']];
		if ( isset( $font['italic'] ) && ! $font['italic'] ) { $out[$group . '_italic'] = false; }
		$out[$group . '_weight'] = max( $font['min'], min( $font['max'], $font['min'] + (int) round( ( $out[$group . '_weight'] - $font['min'] ) / $font['step'] ) * $font['step'] ) );
	}
	return $out;
}
function kilka_reader_intro_settings( $id ) {
	return kilka_reader_intro_sanitize( get_post_meta( $id, '_kilka_reader_intro', true ) );
}
/** One catalog for saved settings and the live preview. Font files belong to this plugin. */
function kilka_reader_intro_font_catalog() {
	return array(
		'serif' => array( 'label' => __( 'Serif (Georgia)', 'kilka-reader' ), 'stack' => 'Georgia, "Times New Roman", serif', 'min' => 400, 'max' => 700, 'step' => 300 ),
		'sans' => array( 'label' => __( 'System sans serif', 'kilka-reader' ), 'stack' => 'system-ui, -apple-system, "Segoe UI", sans-serif', 'min' => 100, 'max' => 900, 'step' => 100 ),
		'playfair' => array( 'label' => 'Playfair Display', 'stack' => '"Kilka Intro Playfair Display", Georgia, serif', 'min' => 400, 'max' => 900, 'step' => 1 ),
		'cormorant' => array( 'label' => 'Cormorant Garamond', 'stack' => '"Kilka Intro Cormorant Garamond", Georgia, serif', 'min' => 300, 'max' => 700, 'step' => 1 ),
		'ruslan' => array( 'label' => 'Ruslan Display', 'stack' => '"Kilka Intro Ruslan Display", Georgia, serif', 'min' => 400, 'max' => 400, 'step' => 1, 'italic' => false ),
		'montserrat' => array( 'label' => 'Montserrat', 'stack' => '"Kilka Intro Montserrat", system-ui, sans-serif', 'min' => 100, 'max' => 900, 'step' => 1 ),
	);
}
function kilka_reader_intro_fonts() {
	return array_map( function ( $font ) { return $font['stack']; }, kilka_reader_intro_font_catalog() );
}
function kilka_reader_intro_style( $s ) {
	$fonts = kilka_reader_intro_fonts();
	$style = '--intro-text-weight:' . $s['text_weight'] . ';--intro-text-style:' . ( $s['text_italic'] ? 'italic' : 'normal' ) . ';--intro-title-weight:' . $s['title_weight'] . ';--intro-title-style:' . ( $s['title_italic'] ? 'italic' : 'normal' ) . ';--intro-ink:' . $s['ink'] . ';--intro-bg:' . $s['background'] . ';--intro-shade:' . ( $s['shade'] / 100 ) . ';--intro-title-font:' . $fonts[$s['title_font']] . ';--intro-text-font:' . $fonts[$s['text_font']] . ';--intro-title-size:' . $s['title_size'] . 'px;--intro-text-size:' . $s['text_size'] . 'px;--intro-line-size:' . $s['line_size'] . 'px;--intro-align:' . $s['align'] . ';';
	foreach ( array( 'title', 'text' ) as $group ) {
		$style .= '--intro-' . $group . '-synthesis:' . ( 'ruslan' === $s[$group . '_font'] ? 'none' : 'auto' ) . ';';
	}
	foreach ( array( 'desktop', 'tablet', 'mobile' ) as $screen ) {
		$style .= '--intro-' . $screen . '-x:' . $s[$screen . '_x'] . '%;--intro-' . $screen . '-y:' . $s[$screen . '_y'] . '%;';
	}
	return $style;
}
/** A single title supplies both layouts; only whitespace differs. */
function kilka_reader_intro_title_markup( $title, $s ) {
	if ( ! $s['phone_breaks_enabled'] ) { return esc_html( $title ); }
	$words = preg_split( '/[ \t\r\n\f]+/u', trim( $title ), -1, PREG_SPLIT_NO_EMPTY );
	$breaks = array_map( 'intval', explode( ',', $s['phone_breaks'] ) );
	$phone = '';
	foreach ( $words as $index => $word ) {
		if ( $index ) { $phone .= in_array( $index, $breaks, true ) ? "\n" : ' '; }
		$phone .= $word;
	}
	return '<span class="kilka-intro-title-wide">' . esc_html( $title ) . '</span><span class="kilka-intro-title-phone">' . esc_html( $phone ) . '</span>';
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
	$html .= '<div class="kilka-reader-intro__copy"><h2 class="kilka-reader-intro__title">' . kilka_reader_intro_title_markup( $title, $s ) . '</h2>';
	if ( $s['author'] ) { $html .= '<p class="kilka-reader-intro__author">' . esc_html( $s['author'] ) . '</p>'; }
	if ( $s['line'] ) { $html .= '<p class="kilka-reader-intro__line' . ( $s['hide_line'] ? ' hide-on-small' : '' ) . '">' . esc_html( $s['line'] ) . '</p>'; }
	return $html . '</div></section>';
}
add_action( 'init', function () {
	register_post_meta( 'page', '_kilka_reader_intro', array( 'type' => 'object', 'single' => true, 'default' => kilka_reader_intro_defaults(), 'show_in_rest' => false, 'sanitize_callback' => 'kilka_reader_intro_sanitize', 'auth_callback' => function ( $allowed, $key, $id ) { return current_user_can( 'edit_post', $id ); } ) );
} );
function kilka_reader_intro_editor( $post ) {
	$s = kilka_reader_intro_settings( $post->ID );
	$select = function ( $key, $label, $options ) use ( $s ) {
		echo '<label>' . esc_html( $label ) . '<select name="kilka_intro[' . esc_attr( $key ) . ']">';
		foreach ( $options as $value => $text ) { echo '<option value="' . esc_attr( $value ) . '" ' . selected( $s[$key], $value, false ) . '>' . esc_html( $text ) . '</option>'; }
		echo '</select></label>';
	};
	$range = function ( $key, $label, $min, $max, $step = 1, $disabled = false ) use ( $s ) {
		echo '<label>' . esc_html( $label ) . '<input type="range" name="kilka_intro[' . esc_attr( $key ) . ']" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" step="' . esc_attr( $step ) . '" value="' . esc_attr( $s[$key] ) . '" ' . disabled( $disabled, true, false ) . '><output>' . esc_html( $s[$key] ) . '</output></label>';
	};
	$checkbox = function ( $key, $label, $disabled = false ) use ( $s ) {
		echo '<label class="kilka-intro-check"><input type="checkbox" name="kilka_intro[' . esc_attr( $key ) . ']" value="1" ' . checked( $s[$key], true, false ) . ' ' . disabled( $disabled, true, false ) . '> ' . esc_html( $label ) . '</label>';
	};
	echo '<div class="kilka-intro-editor" data-page-title="' . esc_attr( get_the_title( $post ) ) . '"><p>' . esc_html__( 'Optional introduction for the Reading template. The next page opens the story. Image cropping never changes the original file.', 'kilka-reader' ) . '</p>';
	$checkbox( 'enabled', __( 'Enable introduction', 'kilka-reader' ) );
	echo '<div class="kilka-intro-workspace"><div class="kilka-intro-sections"><details open><summary>' . esc_html__( 'Text', 'kilka-reader' ) . '</summary><div class="kilka-intro-fields">';
	echo '<label>' . esc_html__( 'Title (empty uses page title)', 'kilka-reader' ) . '<textarea name="kilka_intro[title]" rows="3" aria-describedby="kilka-intro-title-help">' . esc_textarea( $s['title'] ) . '</textarea><span class="description" id="kilka-intro-title-help">' . esc_html__( 'Press Enter to start a new line. Narrow screens may wrap each line further.', 'kilka-reader' ) . '</span></label>';
	echo '<div class="kilka-intro-phone-breaks"><label><span>' . esc_html__( 'Separate title line breaks on phones', 'kilka-reader' ) . '</span><input type="checkbox" name="kilka_intro[phone_breaks_enabled]" value="1" ' . checked( $s['phone_breaks_enabled'], true, false ) . '></label><input type="hidden" name="kilka_intro[phone_breaks]" value="' . esc_attr( $s['phone_breaks'] ) . '"><div data-intro-break-panel hidden><p>' . esc_html__( 'Select where a new line starts. With none selected, phones wrap automatically. Editing the title words clears these choices.', 'kilka-reader' ) . '</p><div data-intro-break-buttons></div></div></div>';

	foreach ( array( 'author' => __( 'Author (optional)', 'kilka-reader' ), 'line' => __( 'Short line (optional)', 'kilka-reader' ) ) as $key => $label ) {
		echo '<label>' . esc_html( $label ) . '<input type="text" name="kilka_intro[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[$key] ) . '"></label>';
	}
	$checkbox( 'hide_line', __( 'Hide the optional line on phones', 'kilka-reader' ) );
	echo '</div></details><details><summary>' . esc_html__( 'Fonts', 'kilka-reader' ) . '</summary><div class="kilka-intro-fields">';
	$fonts = kilka_reader_intro_font_catalog();
	$font_labels = array_map( function ( $font ) { return $font['label']; }, $fonts );
	foreach ( array( 'title' => __( 'Title', 'kilka-reader' ), 'text' => __( 'Author and short line', 'kilka-reader' ) ) as $group => $heading ) {
		echo '<fieldset><legend>' . esc_html( $heading ) . '</legend><div class="kilka-intro-fields">';
		$select( $group . '_font', __( 'Font', 'kilka-reader' ), $font_labels );
		$font = $fonts[$s[$group . '_font']];
		$range( $group . '_weight', __( 'Weight', 'kilka-reader' ), $font['min'], $font['max'], $font['step'], $font['min'] === $font['max'] );
		$checkbox( $group . '_italic', __( 'Italic', 'kilka-reader' ), isset( $font['italic'] ) && ! $font['italic'] );
		echo '<p class="description" data-intro-font-note="' . esc_attr( $group ) . '"' . ( $font['min'] === $font['max'] ? '' : ' hidden' ) . '>' . esc_html__( 'This font has one original style: Regular 400, without italic.', 'kilka-reader' ) . '</p>';
		if ( 'title' === $group ) { $range( 'title_size', __( 'Title size', 'kilka-reader' ), 32, 100 ); }
		else {
			$range( 'text_size', __( 'Author size', 'kilka-reader' ), 16, 48 );
			$range( 'line_size', __( 'Short line size', 'kilka-reader' ), 16, 36 );
		}
		echo '</div></fieldset>';
	}
	echo '</div></details><details><summary>' . esc_html__( 'Image', 'kilka-reader' ) . '</summary><div class="kilka-intro-fields">';
	foreach ( array( 'image' => __( 'Background image', 'kilka-reader' ), 'mobile_image' => __( 'Optional phone image', 'kilka-reader' ) ) as $key => $label ) {
		echo '<div><p>' . esc_html( $label ) . '</p><input type="hidden" name="kilka_intro[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[$key] ) . '" data-url="' . esc_url( wp_get_attachment_image_url( $s[$key], 'full' ) ?: '' ) . '"><button type="button" class="button" data-intro-media="' . esc_attr( $key ) . '">' . esc_html__( 'Choose image', 'kilka-reader' ) . '</button> <button type="button" class="button" data-intro-remove="' . esc_attr( $key ) . '">' . esc_html__( 'Remove image', 'kilka-reader' ) . '</button></div>';
	}
	$range( 'shade', __( 'Image darkening (%)', 'kilka-reader' ), 0, 80 );
	foreach ( array( 'desktop' => __( 'Desktop', 'kilka-reader' ), 'tablet' => __( 'Tablet', 'kilka-reader' ), 'mobile' => __( 'Phone', 'kilka-reader' ) ) as $screen => $label ) {
		echo '<fieldset><legend>' . esc_html( $label ) . '</legend><div class="kilka-intro-fields">';
		$range( $screen . '_x', __( 'Image horizontal position (%)', 'kilka-reader' ), 0, 100 );
		$range( $screen . '_y', __( 'Image vertical position (%)', 'kilka-reader' ), 0, 100 );
		echo '</div></fieldset>';
	}
	echo '</div></details><details><summary>' . esc_html__( 'Composition', 'kilka-reader' ) . '</summary><div class="kilka-intro-fields">';
	$select( 'align', __( 'Text alignment', 'kilka-reader' ), array( 'left' => __( 'Left', 'kilka-reader' ), 'center' => __( 'Center', 'kilka-reader' ), 'right' => __( 'Right', 'kilka-reader' ) ) );
	foreach ( array( 'position' => __( 'Text position', 'kilka-reader' ), 'mobile_position' => __( 'Phone text position', 'kilka-reader' ) ) as $key => $label ) {
		$select( $key, $label, array( 'top' => __( 'Top', 'kilka-reader' ), 'center' => __( 'Center', 'kilka-reader' ), 'bottom' => __( 'Bottom', 'kilka-reader' ) ) );
	}
	foreach ( array( 'ink' => __( 'Text color', 'kilka-reader' ), 'background' => __( 'Background color', 'kilka-reader' ) ) as $key => $label ) {
		echo '<label>' . esc_html( $label ) . '<input type="color" name="kilka_intro[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[$key] ) . '"></label>';
	}
	echo '</div></details></div>';
	echo '<div class="kilka-intro-preview-panel"><p><label>' . esc_html__( 'Preview size', 'kilka-reader' ) . ' <select data-intro-preview-size><option value="desktop">' . esc_html__( 'Desktop', 'kilka-reader' ) . '</option><option value="tablet">' . esc_html__( 'Tablet', 'kilka-reader' ) . '</option><option value="phone">' . esc_html__( 'Phone', 'kilka-reader' ) . '</option></select></label></p><div class="kilka-intro-preview"><iframe title="' . esc_attr__( 'Introduction preview', 'kilka-reader' ) . '" sandbox="allow-same-origin"></iframe></div><p>' . esc_html__( 'Preview shows layout, not reader controls. Check readability on a real phone before publishing. Reading colors and text size are independent of this introduction.', 'kilka-reader' ) . '</p></div></div></div>';
}
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_kilka-reader' !== $hook ) { return; }
	wp_enqueue_media();
	wp_enqueue_style( 'kilka-intro-editor', plugins_url( 'assets/intro-editor.css', dirname( __DIR__ ) . '/kilka-reader.php' ), array(), filemtime( __DIR__ . '/../assets/intro-editor.css' ) );
	wp_enqueue_script( 'kilka-intro-editor', plugins_url( 'assets/intro-editor.js', dirname( __DIR__ ) . '/kilka-reader.php' ), array( 'media-editor', 'wp-data' ), filemtime( __DIR__ . '/../assets/intro-editor.js' ), true );
	wp_localize_script( 'kilka-intro-editor', 'kilkaIntroPreview', array( 'breakLabel' => __( 'Line break after %s', 'kilka-reader' ), 'fonts' => kilka_reader_intro_font_catalog(), 'css' => add_query_arg( 'ver', filemtime( __DIR__ . '/../assets/intro.css' ), plugins_url( 'assets/intro.css', dirname( __DIR__ ) . '/kilka-reader.php' ) ) ) );
} );
