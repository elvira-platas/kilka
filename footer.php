<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Kilka
 */

?>
<?php do_action( 'kilka_footer_style' ); ?>
</div><!-- #page -->
<button class="back-to-top" type="button" aria-label="<?php esc_attr_e( 'Back to top', 'kilka' ); ?>">
	<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">
		<path d="M12 19V5M5 12l7-7 7 7"></path>
	</svg>
</button>
<?php wp_footer(); ?>
</body>
</html>
