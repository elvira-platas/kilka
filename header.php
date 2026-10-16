<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Kilka
 */

?>
<!doctype html>
<html <?php language_attributes(); ?><?php if ( function_exists( 'kilka_is_exhibition_context' ) && kilka_is_exhibition_context() ) : ?> data-color-scheme-context="exhibition"<?php endif; ?>>
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php
	if ( function_exists( 'wp_body_open' ) ) {
	    wp_body_open();
	}
	else { do_action( 'wp_body_open' ); 
	}
?>
<div id="page" class="site">
	<section class="header-top">
		<div class="container">
			<div class="row">
				
			</div>
		</div>
	</section>
	<?php do_action( 'kilka_header_style' ); ?>
