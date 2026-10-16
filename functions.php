<?php
/**
 * Kilka functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Kilka
 */

if ( ! defined( 'KILKA_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( 'KILKA_VERSION', '1.3.0' );
}

if ( ! function_exists( 'kilka_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function kilka_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on kilka, use a find and replace
		 * to change 'kilka' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'kilka', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus(
			array(
				'menu-1' => esc_html__( 'Primary', 'kilka' ),
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);

		// Set up the WordPress core custom background feature.
		add_theme_support(
			'custom-background',
			apply_filters(
				'kilka_custom_background_args',
				array(
					'default-color' => 'ffffff',
					'default-image' => '',
				)
			)
		);

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		// Keep the block editor typography aligned with published posts.
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/default-block.css' );
		add_editor_style( 'assets/css/reading-blocks.css' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 250,
				'width'       => 250,
				'flex-width'  => true,
				'flex-height' => true,
			)
		);
	}
endif;
add_action( 'after_setup_theme', 'kilka_setup' );

/**
 * Start new Main Blog posts with an unlocked Classic block.
 *
 * The block editor remains available, so authors can add More, Image, and
 * other blocks when a post needs more structure.
 */
function kilka_set_main_blog_block_template() {
	$post_type_object = get_post_type_object( 'post' );

	if ( ! $post_type_object ) {
		return;
	}

	$post_type_object->template = array(
		array( 'core/freeform', array() ),
	);
	$post_type_object->template_lock = false;
}
add_action( 'init', 'kilka_set_main_blog_block_template', 20 );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function kilka_content_width() {
	// This variable is intended to be overruled from themes.
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'kilka_content_width', 640 );
}
add_action( 'after_setup_theme', 'kilka_content_width', 0 );

/**
 * Resolve the saved color scheme before styles are painted.
 *
 * The preference is intentionally browser-local. An absent value means
 * "auto", which follows the operating system color scheme.
 */
function kilka_print_color_scheme_bootstrap() {
	?>
	<script id="kilka-color-scheme-bootstrap">
		(function () {
			var preference = 'auto';
			var root = document.documentElement;
			var isExhibitionContext = root.getAttribute('data-color-scheme-context') === 'exhibition';

			try {
				preference = window.localStorage.getItem('kilka-color-scheme') || 'auto';
			} catch (error) {
				preference = 'auto';
			}

			if (preference !== 'light' && preference !== 'dark') {
				preference = 'auto';
			}

			var isSystemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
			var resolvedScheme = isExhibitionContext ? 'light' : (preference === 'auto' ? (isSystemDark ? 'dark' : 'light') : preference);

			root.setAttribute('data-color-scheme-preference', preference);
			root.setAttribute('data-color-scheme', resolvedScheme);
			root.style.colorScheme = resolvedScheme;
		}());
	</script>
	<?php
}
add_action( 'wp_head', 'kilka_print_color_scheme_bootstrap', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function kilka_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Main Blog Sidebar', 'kilka' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Widgets shown on the main blog.', 'kilka' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);

	if ( function_exists( 'kilka_is_second_blog_context' ) ) {
		register_sidebar(
			array(
				'name'          => esc_html__( 'Second Blog Sidebar', 'kilka' ),
				'id'            => 'sidebar-second-blog',
				'description'   => esc_html__( 'Widgets shown on Second Blog pages. Leave empty for a full-width layout.', 'kilka' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}
}
add_action( 'widgets_init', 'kilka_widgets_init' );

/**
 * Register custom fonts.
 */

/**
 * Enqueue scripts and styles.
 */
function kilka_scripts() {
	wp_enqueue_style( 'bootstrap', get_template_directory_uri() . '/assets/css/bootstrap.min.css', array(), '4.6.2', 'all');
	wp_enqueue_style( 'slicknav', get_template_directory_uri() . '/assets/css/slicknav.min.css', array(), '1.0.10', 'all');
	$kilka_default_block_version = filemtime( get_template_directory() . '/assets/css/default-block.css' );
	$kilka_style_version = filemtime( get_template_directory() . '/assets/css/kilka-style.css' );
	$kilka_script_version = filemtime( get_template_directory() . '/assets/js/kilka-script.js' );
	wp_enqueue_style( 'kilka-default-block', get_template_directory_uri() . '/assets/css/default-block.css', array(), $kilka_default_block_version, 'all');
	wp_enqueue_style( 'kilka-style', get_template_directory_uri() . '/assets/css/kilka-style.css', array(), $kilka_style_version, 'all');

	wp_enqueue_script( 'slicknav', get_template_directory_uri() . '/assets/js/jquery.slicknav.min.js', array('jquery'), '1.0.10', true );
	wp_enqueue_script( 'kilka-script', get_template_directory_uri() . '/assets/js/kilka-script.js', array('jquery'), $kilka_script_version, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'kilka_scripts' );

/**
 * Load the scheme layer after both the static and Customizer-generated CSS.
 */
function kilka_color_scheme_styles() {
	$kilka_color_scheme_version = filemtime( get_template_directory() . '/assets/css/color-schemes.css' );

	wp_enqueue_style(
		'kilka-color-schemes',
		get_template_directory_uri() . '/assets/css/color-schemes.css',
		array( 'kilka-custom' ),
		$kilka_color_scheme_version,
		'all'
	);
}
add_action( 'wp_enqueue_scripts', 'kilka_color_scheme_styles', 20 );

/*
 * This theme styles the visual editor to resemble the theme style,
 * specifically font, colors, and column width.
*/

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/reading.php';
require get_template_directory() . '/inc/embed-colors.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/custom-style.php';

/**
 * Load kilka Header.
 */
require get_template_directory() . '/inc/header/header-1.php';

/**
 * Load kilka Footer.
 */
require get_template_directory() . '/inc/footer/footer-1.php';
