<?php
/**
 * Template Name: Reading
 * Template Post Type: page
 *
 * Continuous reading of a portable, core-block document.
 *
 * @package Kilka
 */

$reader_active = function_exists( 'kilka_reader_is_reading' );
if ( ! $reader_active ) {
	get_header();
} else {
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<?php
}
?>
<main id="content" class="kilka-reading" tabindex="-1">
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'kilka-reading__document' ); ?> aria-labelledby="kilka-reading-title">
			<header class="kilka-reading__header">
				<h1 id="kilka-reading-title" class="kilka-reading__title"><?php the_title(); ?></h1>
			</header>
			<div class="kilka-reading__content">
				<?php the_content(); ?>
				<?php
				// Preserve existing manually paginated content without adding pagination.
				wp_link_pages( array(
					'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Document pages', 'kilka' ) . '">',
					'after'  => '</nav>',
				) );
				?>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php if ( ! $reader_active ) { get_footer(); } else { ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
<?php } ?>
