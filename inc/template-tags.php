<?php
/**
 * Custom template tags for this theme
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Kilka
 */

if ( ! function_exists( 'kilka_posted_on' ) ) :
	/**
	 * Prints HTML with meta information for the current post-date/time.
	 */
	function kilka_posted_on() {
		$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
		if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
			$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
		}

		$time_string = sprintf(
			$time_string,
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( get_the_date() ),
			esc_attr( get_the_modified_date( DATE_W3C ) ),
			esc_html( get_the_modified_date() )
		);

		$day_archive_link = get_day_link(
			get_the_date( 'Y' ),
			get_the_date( 'm' ),
			get_the_date( 'd' )
		);
		if ( 'world_note' === get_post_type() ) {
			$day_archive_link = add_query_arg( 'post_type', 'world_note', $day_archive_link );
		}

		$posted_on = sprintf(
			/* translators: %s: post date. */
			esc_html_x( '%s', 'post date', 'kilka' ),
			'<a href="' . esc_url( $day_archive_link ) . '">' . $time_string . '</a>'
		);

		echo '<span class="posted-on">' . $posted_on . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}
endif;

if ( ! function_exists( 'kilka_posted_by' ) ) :
	/**
	 * Prints HTML with meta information for the current author.
	 */
	function kilka_posted_by() {
		$author_url = get_author_posts_url( get_the_author_meta( 'ID' ) );
		if ( 'world_note' === get_post_type() ) {
			$author_url = add_query_arg( 'post_type', 'world_note', $author_url );
		}

		$byline = sprintf(
			/* translators: %s: post author. */
			esc_html_x( '%s', 'post author', 'kilka' ),
			'<span class="author vcard"><a class="url fn n" href="' . esc_url( $author_url ) . '">' . esc_html( get_the_author() ) . '</a></span>'
		);

		echo '<span class="byline"> ' . $byline . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}
endif;

if ( ! function_exists( 'kilka_entry_footer' ) ) :
	/**
	 * Prints HTML with meta information for tags and comments.
	 */
	function kilka_entry_footer() {
		$tags_list      = '';
		$is_main_single = 'post' === get_post_type() && is_single();

		if ( 'post' === get_post_type() ) {
			/* translators: used between list items, there is a space after the comma */
			$tags_list = get_the_tag_list( '', esc_html_x( ', ', 'list item separator', 'kilka' ) );
		} elseif ( 'world_note' === get_post_type() ) {
			/* translators: used between list items, there is a space after the comma */
			$tags_list = function_exists( 'kilka_get_world_note_term_links' )
				? kilka_get_world_note_term_links( get_the_ID(), 'world_note_tag', esc_html_x( ', ', 'list item separator', 'kilka' ) )
				: get_the_term_list( get_the_ID(), 'world_note_tag', '', esc_html_x( ', ', 'list item separator', 'kilka' ) );
		}

		if ( $is_main_single ) {
			echo '<div class="main-post-meta-footer">';
			kilka_posted_on();
			if ( $tags_list ) {
				printf( '<span class="post-tags"><span class="tags-links">%s</span></span>', $tags_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
		} elseif ( $tags_list ) {
			printf( '<div class="post-tags text-right"><span class="tags-links">%s</span></div>', $tags_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			echo '<span class="comments-link">';
			comments_popup_link(
				sprintf(
					wp_kses(
						/* translators: %s: post title */
						__( 'Leave a Comment<span class="screen-reader-text"> on %s</span>', 'kilka' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					wp_kses_post( get_the_title() )
				)
			);
			echo '</span>';
		}

		edit_post_link(
			sprintf(
				wp_kses(
					/* translators: %s: Name of current post. Only visible to screen readers */
					__( 'Edit <span class="screen-reader-text">%s</span>', 'kilka' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				wp_kses_post( get_the_title() )
			),
			'<span class="edit-link">',
			'</span>'
		);
	}
endif;

if ( ! function_exists( 'kilka_post_thumbnail' ) ) :
	/**
	 * Displays an optional post thumbnail.
	 *
	 * Wraps the post thumbnail in an anchor element on index views, or a div
	 * element when on single views.
	 */
	function kilka_post_thumbnail() {
		if ( post_password_required() || is_attachment() || ! has_post_thumbnail() ) {
			return;
		}

		$thumbnail_attributes = array();
		if ( 'post' === get_post_type() ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'post-thumbnail' );
			if ( $image && $image[1] > 0 && $image[2] > 0 ) {
				// Match the 860px cover width, 600px height and outer grid gutter.
				$width_limit = min( 860, $image[1], (int) ceil( 600 * $image[1] / $image[2] ) );
				$desktop = kilka_has_contextual_sidebar()
					? 'min(calc(66.667vw - 30px), 823.34px, %1$dpx)'
					: 'min(calc(100vw - 30px), 860px, %1$dpx)';
				$thumbnail_attributes['sizes'] = sprintf(
					'(max-width: 575px) min(calc(100vw - 30px), %1$dpx), (max-width: 767px) %2$dpx, (max-width: 991px) %3$dpx, ' . $desktop,
					$width_limit,
					min( 510, $width_limit ),
					min( 690, $width_limit )
				);
			}

			// Keep the leading cover eligible for WordPress's priority heuristics.
			global $wp_query;
			if ( ! is_singular() && in_the_loop() && $wp_query->current_post > 0 ) {
				$thumbnail_attributes['loading'] = 'lazy';
			}
		}

		if ( is_singular() ) :
			?>

			<div class="post-thumbnail">
				<?php the_post_thumbnail( 'post-thumbnail', $thumbnail_attributes ); ?>
			</div><!-- .post-thumbnail -->

		<?php else : ?>

			<a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<?php
					$thumbnail_attributes['alt'] = the_title_attribute( array( 'echo' => false ) );
					the_post_thumbnail( 'post-thumbnail', $thumbnail_attributes );
				?>
			</a>

			<?php
		endif; // End is_singular().
	}
endif;

if ( ! function_exists( 'kilka_get_button_arrow' ) ) :
	/**
	 * Return the shared forward arrow used by reading links.
	 *
	 * @return string Arrow markup.
	 */
	function kilka_get_button_arrow() {
		return '<span class="kilka-button-arrow" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" focusable="false"><path d="M5 12h14M12 5l7 7-7 7"></path></svg></span>';
	}
endif;

if ( ! function_exists( 'kilka_get_continue_reading_screen_reader_text' ) ) :
	/**
	 * Return an accessible label for an arrow-only reading link.
	 *
	 * @param int $post_id Post ID.
	 * @return string Screen-reader label markup.
	 */
	function kilka_get_continue_reading_screen_reader_text( $post_id ) {
		$label = sprintf(
			/* translators: %s: post title. */
			__( 'Continue reading: %s', 'kilka' ),
			wp_strip_all_tags( get_the_title( $post_id ) )
		);

		return '<span class="screen-reader-text">' . esc_html( $label ) . '</span>';
	}
endif;
