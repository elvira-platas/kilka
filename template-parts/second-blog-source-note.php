<?php
/**
 * Optional source note for the Second Blog introduction.
 *
 * @package Kilka
 */

$kilka_source_note  = trim( (string) get_theme_mod( 'kilka_second_blog_source_note', '' ) );
$kilka_source_title = trim( (string) get_theme_mod( 'kilka_second_blog_source_title', '' ) );
$kilka_source_url   = trim( (string) get_theme_mod( 'kilka_second_blog_source_url', '' ) );

if ( '' === $kilka_source_note ) {
	return;
}
?>
<aside id="second-blog-source-note" class="second-blog-source-note" aria-label="<?php esc_attr_e( 'Source note', 'kilka' ); ?>">
	<p>
		<sup><a href="#second-blog-source-marker" aria-label="<?php esc_attr_e( 'Back to source marker', 'kilka' ); ?>">1</a></sup>
		<?php echo esc_html( $kilka_source_note ); ?>
		<?php if ( $kilka_source_title ) : ?>
			<?php if ( $kilka_source_url ) : ?>
				<a href="<?php echo esc_url( $kilka_source_url ); ?>"><?php echo esc_html( $kilka_source_title ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $kilka_source_title ); ?>
			<?php endif; ?>
		<?php endif; ?>
	</p>
</aside>
