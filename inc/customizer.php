<?php
/**
 * Kilka Theme Customizer
 *
 * @package Kilka
 */

/**
 * Get the site-title fonts supported by the theme.
 *
 * @return array
 */
function kilka_get_site_title_font_choices() {
	$fonts = array(
		'system-ui' => __( 'System UI (Default)', 'kilka' ),
		'Shafarik'  => __( 'Shafarik', 'kilka' ),
	);
	// Keep an existing choice visible until the owner replaces it.
	$saved = get_theme_mod( 'kilka_site_title_font', '' );
	if ( in_array( $saved, array( 'Roboto', 'Montserrat', 'Oswald', 'Playfair Display', 'Merriweather' ), true ) ) {
		$fonts[ $saved ] = $saved;
	}
	return $fonts;
}

/**
 * Limit the site-title font to fonts supported by the theme.
 *
 * @param string $value Requested font name.
 * @return string
 */
function kilka_sanitize_site_title_font( $value ) {
	// Do not read theme mods here: Customizer preview calls this sanitizer.
	$fonts = array( 'system-ui', 'Shafarik', 'Roboto', 'Montserrat', 'Oswald', 'Playfair Display', 'Merriweather' );
	return is_string( $value ) && in_array( $value, $fonts, true ) ? $value : 'system-ui';
}

/** Restrict announcement typography without reading previewed settings. */
function kilka_sanitize_announcement_font( $value ) {
	return in_array( $value, array( 'system-ui', 'kelly-slab' ), true ) ? $value : 'system-ui';
}

/**
 * Limit the Continue Reading button format to supported values.
 *
 * @param string $value Requested button format.
 * @return string
 */
function kilka_sanitize_continue_reading_format( $value ) {
	$allowed_formats = array( 'text', 'arrow', 'text_arrow' );

	return in_array( $value, $allowed_formats, true ) ? $value : 'arrow';
}

/**
 * Limit the Continue Reading font weight to supported values.
 *
 * @param string $value Requested font weight.
 * @return string
 */
function kilka_sanitize_continue_reading_weight( $value ) {
	$allowed_weights = array( '300', '400', '500', '600', '700', '800' );

	return in_array( (string) $value, $allowed_weights, true ) ? (string) $value : '400';
}

/**
 * Accept only complete, real calendar dates; an empty date is unbounded.
 *
 * @param string $value Requested date in YYYY-MM-DD format.
 * @return string Valid date or an empty string.
 */
function kilka_sanitize_announcement_date( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $value, $parts ) ) {
		return '';
	}

	return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ? $value : '';
}

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function kilka_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'blogname',
			array(
				'selector'        => '.site-title a',
				'render_callback' => 'kilka_customize_partial_blogname',
			)
		);
		$wp_customize->selective_refresh->add_partial(
			'blogdescription',
			array(
				'selector'        => '.site-description',
				'render_callback' => 'kilka_customize_partial_blogdescription',
			)
		);
	}

	// Group theme-specific controls under one top-level Customizer item.
	$wp_customize->add_panel( 'kilka_theme_options_panel', array(
		'title'       => __( 'Kilka Theme Options', 'kilka' ),
		'description' => __( 'Customize site appearance, post listings, the footer, and Second Blog content.', 'kilka' ),
		'priority'    => 130,
	) );

	$wp_customize->add_section( 'kilka_sidebar_section', array(
		'title'       => __( 'Main Blog Layout', 'kilka' ),
		'description' => __( 'The Main Blog sidebar is hidden by default. Enable it here, then add widgets under Appearance → Widgets.', 'kilka' ),
		'panel'       => 'kilka_theme_options_panel',
		'priority'    => 25,
	) );

	$wp_customize->add_setting( 'kilka_show_main_blog_sidebar', array(
		'default'           => false,
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );
	$wp_customize->add_control( 'kilka_show_main_blog_sidebar', array(
		'label'   => __( 'Show Main Blog Sidebar', 'kilka' ),
		'section' => 'kilka_sidebar_section',
		'type'    => 'checkbox',
	) );

	$wp_customize->add_section( 'kilka_announcement_section', array(
		'title'       => __( 'Announcement', 'kilka' ),
		'panel'       => 'kilka_theme_options_panel',
		'priority'    => 35,
		'description' => __( 'One short announcement above the first page of the Main Blog. Dates include the whole day in the site timezone. Leave dates empty for no time limit. A start date after the end date hides the announcement.', 'kilka' ),
	) );

	$announcement_fields = array(
		'enabled' => array( __( 'Show announcement', 'kilka' ), 'checkbox', 'rest_sanitize_boolean', false ),
		'text'    => array( __( 'Announcement text', 'kilka' ), 'text', 'sanitize_text_field', '' ),
		'url'     => array( __( 'Announcement URL', 'kilka' ), 'url', 'esc_url_raw', '' ),
		'from'    => array( __( 'Show from', 'kilka' ), 'date', 'kilka_sanitize_announcement_date', '' ),
		'until'   => array( __( 'Show until', 'kilka' ), 'date', 'kilka_sanitize_announcement_date', '' ),
	);
	foreach ( $announcement_fields as $name => $field ) {
		$setting_id = 'kilka_announcement_' . $name;
		$wp_customize->add_setting( $setting_id, array(
			'type'              => 'theme_mod',
			'default'           => $field[3],
			'sanitize_callback' => $field[2],
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'   => $field[0],
			'section' => 'kilka_announcement_section',
			'type'    => $field[1],
		) );
	}

	$wp_customize->add_setting( 'kilka_announcement_font', array(
		'default' => 'system-ui',
		'sanitize_callback' => 'kilka_sanitize_announcement_font',
		'transport' => 'refresh',
	) );
	$wp_customize->add_control( 'kilka_announcement_font', array(
		'label' => __( 'Announcement font', 'kilka' ),
		'section' => 'kilka_announcement_section',
		'type' => 'select',
		'choices' => array( 'system-ui' => __( 'System UI (Default)', 'kilka' ), 'kelly-slab' => __( 'Kelly Slab', 'kilka' ) ),
	) );

	// Keep the header text color with the other site identity controls.
	$header_text_color_control = $wp_customize->get_control( 'header_textcolor' );
	if ( $header_text_color_control ) {
		$header_text_color_control->label    = __( 'Site Title and Tagline Color', 'kilka' );
		$header_text_color_control->section  = 'title_tagline';
		$header_text_color_control->priority = 60;
	}

	// Keep the optional homepage image with the theme appearance controls.
	$header_image_section = $wp_customize->get_section( 'header_image' );
	if ( $header_image_section ) {
		$header_image_section->title       = __( 'Homepage Header Image', 'kilka' );
		$header_image_section->description = __( 'Shown only at the top of the site homepage.', 'kilka' );
		$header_image_section->panel       = 'kilka_theme_options_panel';
		$header_image_section->priority    = 10;
	}

	// Combine the core background color and image controls into one section.
	$background_section       = $wp_customize->get_section( 'background_image' );
	$background_color_control = $wp_customize->get_control( 'background_color' );

	if ( $background_section && $background_color_control ) {
		$background_section->title            = __( 'General Background', 'kilka' );
		$background_section->description      = __( 'Used by the Main Blog and standard pages. Second Blog and Exhibitions use their own backgrounds.', 'kilka' );
		$background_section->panel            = 'kilka_theme_options_panel';
		$background_section->priority         = 20;
		$background_color_control->section     = 'background_image';
		$background_color_control->priority    = 5;
		$wp_customize->remove_section( 'colors' );
	}

	// Keep site-title typography with the related core identity controls.
	$wp_customize->add_setting( 'kilka_site_title_font', array(
		'default'           => 'system-ui',
		'sanitize_callback' => 'kilka_sanitize_site_title_font',
	) );
	$wp_customize->add_control( 'kilka_site_title_font', array(
		'label'    => __( 'Site Title Font Family', 'kilka' ),
		'section'  => 'title_tagline',
		'priority' => 50,
		'type'     => 'select',
		'choices'  => kilka_get_site_title_font_choices(),
	) );

	$wp_customize->add_setting( 'kilka_site_title_size', array(
		'default'           => 14,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'kilka_site_title_size', array(
		'label'       => __( 'Site Title Font Size (px)', 'kilka' ),
		'section'     => 'title_tagline',
		'priority'    => 55,
		'type'        => 'number',
		'input_attrs' => array(
			'min'  => 14,
			'max'  => 100,
			'step' => 1,
		),
	) );

	// Add Footer Settings Section
	$wp_customize->add_section( 'kilka_footer_section', array(
		'title'    => __( 'Footer', 'kilka' ),
		'panel'    => 'kilka_theme_options_panel',
		'priority' => 40,
	) );

	// Add Footer Copyright Owner Setting
	$wp_customize->add_setting( 'kilka_footer_copyright_owner', array(
		'default'           => get_bloginfo( 'name' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'kilka_footer_copyright_owner', array(
		'label'       => __( 'Copyright Name', 'kilka' ),
		'description' => __( 'Leave blank to hide the copyright line.', 'kilka' ),
		'section'     => 'kilka_footer_section',
		'type'        => 'text',
	) );

	// Add Footer Link Text Setting
	$wp_customize->add_setting( 'kilka_footer_link_text', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'kilka_footer_link_text', array(
		'label'    => __( 'First Link Text', 'kilka' ),
		'section'  => 'kilka_footer_section',
		'type'     => 'text',
	) );

	// Add Footer Link URL Setting
	$wp_customize->add_setting( 'kilka_footer_link_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'kilka_footer_link_url', array(
		'label'    => __( 'First Link URL', 'kilka' ),
		'section'  => 'kilka_footer_section',
		'type'     => 'url',
	) );

	// Add Footer Link Text 2 Setting
	$wp_customize->add_setting( 'kilka_footer_link_text_2', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'kilka_footer_link_text_2', array(
		'label'    => __( 'Second Link Text', 'kilka' ),
		'section'  => 'kilka_footer_section',
		'type'     => 'text',
	) );

	// Add Footer Link URL 2 Setting
	$wp_customize->add_setting( 'kilka_footer_link_url_2', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'kilka_footer_link_url_2', array(
		'label'    => __( 'Second Link URL', 'kilka' ),
		'section'  => 'kilka_footer_section',
		'type'     => 'url',
	) );

	// Add Post Listings Section
	$wp_customize->add_section( 'kilka_button_section', array(
		'title'       => __( 'Post Listings', 'kilka' ),
		'description' => __( 'Customize the Continue Reading link shown on post lists.', 'kilka' ),
		'panel'       => 'kilka_theme_options_panel',
		'priority'    => 30,
	) );

	// Continue Reading Text
	$wp_customize->add_setting( 'kilka_continue_reading_text', array(
		'default'           => __( 'Continue Reading', 'kilka' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'kilka_continue_reading_text', array(
		'label'    => __( 'Button Text', 'kilka' ),
		'section'  => 'kilka_button_section',
		'type'     => 'text',
	) );

	// Continue Reading Format
	$wp_customize->add_setting( 'kilka_continue_reading_format', array(
		'default'           => 'arrow',
		'sanitize_callback' => 'kilka_sanitize_continue_reading_format',
	) );
	$wp_customize->add_control( 'kilka_continue_reading_format', array(
		'label'    => __( 'Format', 'kilka' ),
		'section'  => 'kilka_button_section',
		'type'     => 'select',
		'choices'  => array(
			'text'       => __( 'Text Only', 'kilka' ),
			'arrow'      => __( 'Arrow Only (→)', 'kilka' ),
			'text_arrow' => __( 'Text + Arrow (→)', 'kilka' ),
		),
	) );

	// Continue Reading Color
	$wp_customize->add_setting( 'kilka_continue_reading_color', array(
		'default'           => '#000000',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'kilka_continue_reading_color', array(
		'label'    => __( 'Color', 'kilka' ),
		'section'  => 'kilka_button_section',
	) ) );

	// Continue Reading Font Weight
	$wp_customize->add_setting( 'kilka_continue_reading_weight', array(
		'default'           => '400',
		'sanitize_callback' => 'kilka_sanitize_continue_reading_weight',
	) );
	$wp_customize->add_control( 'kilka_continue_reading_weight', array(
		'label'    => __( 'Font Weight', 'kilka' ),
		'section'  => 'kilka_button_section',
		'type'     => 'select',
		'choices'  => array(
			'300' => __( 'Light (300)', 'kilka' ),
			'400' => __( 'Normal (400)', 'kilka' ),
			'500' => __( 'Medium (500)', 'kilka' ),
			'600' => __( 'Semi-Bold (600)', 'kilka' ),
			'700' => __( 'Bold (700)', 'kilka' ),
			'800' => __( 'Extra-Bold (800)', 'kilka' ),
		),
	) );

	if ( function_exists( 'kilka_get_world_note_slug' ) ) {
		// Add Second Blog Intro Section only when the companion plugin is active.
		$wp_customize->add_section(
			'kilka_second_blog_intro_section',
			array(
				'title'       => __( 'Second Blog', 'kilka' ),
				'panel'       => 'kilka_theme_options_panel',
				'priority'    => 50,
				'description' => __( 'Shown under the site title on Second Blog pages.', 'kilka' ),
			)
		);

		// Second Blog Heading
		$wp_customize->add_setting(
			'kilka_second_blog_heading',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			'kilka_second_blog_heading',
			array(
				'label'   => __( 'Second Blog Heading', 'kilka' ),
				'section' => 'kilka_second_blog_intro_section',
				'type'    => 'text',
			)
		);

		// Second Blog Description
		$wp_customize->add_setting(
			'kilka_second_blog_description',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		);
		$wp_customize->add_control(
			'kilka_second_blog_description',
			array(
				'label'   => __( 'Second Blog Description', 'kilka' ),
				'section' => 'kilka_second_blog_intro_section',
				'type'    => 'textarea',
			)
		);

		// Second Blog Disclosure
		$wp_customize->add_setting(
			'kilka_second_blog_disclosure',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			'kilka_second_blog_disclosure',
			array(
				'label'       => __( 'Second Blog Disclosure', 'kilka' ),
				'description' => __( 'Optional notice shown below the Second Blog description.', 'kilka' ),
				'section'     => 'kilka_second_blog_intro_section',
				'type'        => 'text',
			)
		);

		// Second Blog Source Note
		$wp_customize->add_setting(
			'kilka_second_blog_source_note',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		);
		$wp_customize->add_control(
			'kilka_second_blog_source_note',
			array(
				'label'       => __( 'Second Blog Source Note', 'kilka' ),
				'description' => __( 'Optional footnote shown at the bottom of Second Blog pages.', 'kilka' ),
				'section'     => 'kilka_second_blog_intro_section',
				'type'        => 'textarea',
			)
		);

		// Second Blog Source Title
		$wp_customize->add_setting(
			'kilka_second_blog_source_title',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			'kilka_second_blog_source_title',
			array(
				'label'   => __( 'Second Blog Source Title', 'kilka' ),
				'section' => 'kilka_second_blog_intro_section',
				'type'    => 'text',
			)
		);

		// Second Blog Source URL
		$wp_customize->add_setting(
			'kilka_second_blog_source_url',
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			'kilka_second_blog_source_url',
			array(
				'label'   => __( 'Second Blog Source URL', 'kilka' ),
				'section' => 'kilka_second_blog_intro_section',
				'type'    => 'url',
			)
		);
	}
}
add_action( 'customize_register', 'kilka_customize_register' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function kilka_customize_partial_blogname() {
	echo esc_html( get_bloginfo( 'name', 'display' ) );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function kilka_customize_partial_blogdescription() {
	echo esc_html( get_bloginfo( 'description', 'display' ) );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function kilka_customize_preview_js() {
	wp_enqueue_script( 'kilka-customizer', get_template_directory_uri() . '/assets/js/customizer.js', array( 'customize-preview' ), '20151215', true );
}
add_action( 'customize_preview_init', 'kilka_customize_preview_js' );

/**
 * Enqueue Customizer controls script for Second Blog intro helpers.
 *
 * @return void
 */
function kilka_customize_controls_js() {
	if ( ! function_exists( 'kilka_get_world_note_slug' ) ) {
		return;
	}

	$second_blog_url = '';

	if ( post_type_exists( 'world_note' ) ) {
		$second_blog_url = get_post_type_archive_link( 'world_note' );
	}

	if ( ! $second_blog_url && function_exists( 'kilka_get_world_note_slug' ) ) {
		$second_blog_url = home_url( '/' . trim( (string) kilka_get_world_note_slug(), '/' ) . '/' );
	}

	wp_enqueue_script(
		'kilka-customizer-controls',
		get_template_directory_uri() . '/assets/js/customizer-controls.js',
		array( 'customize-controls' ),
		KILKA_VERSION,
		true
	);

	wp_localize_script(
		'kilka-customizer-controls',
		'kilkaCustomizerControls',
		array(
			'secondBlogUrl' => esc_url_raw( (string) $second_blog_url ),
		)
	);
}
add_action( 'customize_controls_enqueue_scripts', 'kilka_customize_controls_js' );
