<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Kilka
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php kilka_post_thumbnail(); ?>

	<?php if ( 'world_note' === get_post_type() ) : ?>
		<?php
		$world_note_type_links = function_exists( 'kilka_get_world_note_term_links' )
			? kilka_get_world_note_term_links( get_the_ID(), 'world_note_type', esc_html_x( ', ', 'list item separator', 'kilka' ) )
			: get_the_term_list( get_the_ID(), 'world_note_type', '', esc_html_x( ', ', 'list item separator', 'kilka' ) );
		$world_note_place_links = function_exists( 'kilka_get_world_note_term_links' )
			? kilka_get_world_note_term_links( get_the_ID(), 'world_note_category', esc_html_x( ', ', 'list item separator', 'kilka' ) )
			: get_the_term_list( get_the_ID(), 'world_note_category', '', esc_html_x( ', ', 'list item separator', 'kilka' ) );
		?>
		<?php if ( $world_note_type_links || $world_note_place_links ) : ?>
			<div class="entry-meta world-note-meta">
				<?php
				if ( $world_note_type_links ) {
					echo '<span class="world-note-type">' . $world_note_type_links . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				if ( $world_note_type_links && $world_note_place_links ) {
					echo '<span class="sep">|</span>';
				}
				if ( $world_note_place_links ) {
					echo '<span class="world-note-place">' . $world_note_place_links . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div><!-- .entry-meta -->
		<?php endif; ?>
	<?php elseif ( 'post' === get_post_type() ) : ?>
		<?php
		$category_links = get_the_category_list( esc_html_x( ', ', 'list item separator', 'kilka' ) );
		if ( $category_links ) :
			?>
			<div class="entry-meta">
				<span class="cat-links"><?php echo $category_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</div><!-- .entry-meta -->
		<?php endif; ?>
	<?php endif; ?>

	<header class="entry-header">
		<?php
		if ( is_singular() ) :
			the_title( '<h1 class="entry-title">', '</h1>' );
		else :
			the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		endif;
		?>
	</header><!-- .entry-header -->

	

	<div class="entry-content">
		<?php

		if ( is_single() || ( ! is_singular() && 'world_note' === get_post_type() ) ) {
			the_content(
				sprintf(
					wp_kses(
						/* translators: %s: Name of current post. Only visible to screen readers */
						__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'kilka' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					wp_kses_post( get_the_title() )
				)
			);
		}else{
			the_excerpt();
			$continue_reading_text   = get_theme_mod( 'kilka_continue_reading_text', esc_html__('Continue Reading','kilka') );
			$continue_reading_format = get_theme_mod( 'kilka_continue_reading_format', 'arrow' );
			
			$button_content     = '';
			$arrow_html        = kilka_get_button_arrow();
			$screen_reader_text = kilka_get_continue_reading_screen_reader_text( $post->ID );
			
			if ( 'arrow' === $continue_reading_format ) {
				$button_content = $arrow_html . $screen_reader_text;
			} elseif ( 'text_arrow' === $continue_reading_format ) {
				$button_content = '<span class="button-text">' . esc_html( $continue_reading_text ) . '</span>' . $arrow_html;
			} else {
				$button_content = esc_html( $continue_reading_text );
			}

			echo'<a href="'.esc_url ( get_the_permalink( $post->ID ) ).'" class="button format-'.esc_attr($continue_reading_format).'">'. $button_content .'</a>';
		}

			if ( ! is_singular() && in_array( get_post_type(), array( 'post', 'world_note' ), true ) ) {
				if ( 'post' === get_post_type() ) {
					$tags_list = get_the_tag_list( '', esc_html_x( ', ', 'list item separator', 'kilka' ) );
				} else {
					$tags_list = function_exists( 'kilka_get_world_note_term_links' )
						? kilka_get_world_note_term_links( get_the_ID(), 'world_note_tag', esc_html_x( ', ', 'list item separator', 'kilka' ) )
						: get_the_term_list( get_the_ID(), 'world_note_tag', '', esc_html_x( ', ', 'list item separator', 'kilka' ) );
				}

				if ( $tags_list ) {
					printf( '<div class="entry-footer text-right"><span class="tags-links">' . esc_html__( '%1$s', 'kilka' ) . '</span></div>', $tags_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'kilka' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->

	<?php if ( is_singular() ) : ?>
		<footer class="entry-footer">
			<?php kilka_entry_footer(); ?>
		</footer><!-- .entry-footer -->
	<?php endif; ?>
</article><!-- #post-<?php the_ID(); ?> -->
