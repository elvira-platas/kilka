<?php
/** Neutral fallback for themes without a Reading template. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?><title><?php echo esc_html( wp_get_document_title() ); ?></title><?php endif; ?>
<?php wp_head(); ?>
</head>
<body <?php body_class( 'kilka-reader-fallback' ); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'kilka-reader' ); ?></a>
<main id="content" class="kilka-reading" tabindex="-1">
<?php while ( have_posts() ) : the_post(); ?>
<article class="kilka-reading__document">
<h1><?php the_title(); ?></h1>
<div class="kilka-reading__content"><?php the_content(); ?></div>
<?php wp_link_pages( array( 'before' => '<nav aria-label="' . esc_attr__( 'Document pages', 'kilka-reader' ) . '">', 'after' => '</nav>' ) ); ?>
</article>
<?php endwhile; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
