<?php
/**
 * Title: Reading document
 * Slug: kilka/reading-document
 * Categories: text
 * Post Types: page
 * Description: A byline, continuous reading text, and an optional source or rights note. Use with the Reading page template.
 *
 * @package Kilka
 */
?>
<!-- wp:paragraph {"className":"is-style-kilka-reading-byline"} -->
<p class="is-style-kilka-reading-byline"><?php esc_html_e( 'Author name', 'kilka-reader' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"is-style-kilka-reading-text"} -->
<div class="wp-block-group is-style-kilka-reading-text"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Add the text here. Use paragraphs, headings, images, and separators in their reading order.', 'kilka-reader' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-kilka-reading-note"} -->
<div class="wp-block-group is-style-kilka-reading-note"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Optional source or rights note. Remove this group if it is not needed.', 'kilka-reader' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
