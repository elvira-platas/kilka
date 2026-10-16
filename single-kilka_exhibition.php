<?php
/**
 * The template for displaying a single Exhibition.
 *
 * Exhibition content remains portable plugin-owned block markup. The theme
 * supplies the shared site frame and the presentation of that sequence.
 *
 * @package Kilka
 */

get_header();
?>

<main id="content" class="kilka-exhibition">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'kilka-exhibition__document' ); ?>>
			<h1 class="screen-reader-text"><?php the_title(); ?></h1>

			<?php get_template_part( 'template-parts/exhibition-information' ); ?>

			<div class="kilka-exhibition__content">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer();
