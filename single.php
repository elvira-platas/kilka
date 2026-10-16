<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Kilka
 */
$kilka_has_sidebar = kilka_has_contextual_sidebar();

if ( $kilka_has_sidebar ) {
	$kilka_column = 8;
} else {
	$kilka_column = 12;
}
get_header();
?>

<section class="single-area <?php if ( ! $kilka_has_sidebar ) : ?>block-content-css<?php endif; ?>" id="content">
	<div class="container">
		<div class="row">
			<div class="col-lg-<?php echo esc_attr( $kilka_column ); ?>">
				<?php
					while ( have_posts() ) :
						the_post();

						get_template_part( 'template-parts/content', get_post_type() );
						the_post_navigation(
							array(
								'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous post:', 'kilka' ) . ' </span><span class="nav-title">%title</span>',
								'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next post:', 'kilka' ) . ' </span><span class="nav-title">%title</span>',
							)
						);
						// If comments are open or we have at least one comment, load up the comment template.
						if ( comments_open() || get_comments_number() ) :
							comments_template();
						endif;

					endwhile; // End of the loop.
				?>
			</div>
			<?php if ( $kilka_has_sidebar ) : ?>
			<div class="col-lg-4">
				<?php get_sidebar(); ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
