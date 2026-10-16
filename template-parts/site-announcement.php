<?php
/**
 * A manually curated announcement above the Main Blog loop.
 *
 * @package Kilka
 */

$kilka_announcement = kilka_get_active_announcement();
if ( false === $kilka_announcement ) {
	return;
}
?>
<aside class="kilka-site-announcement" aria-label="<?php esc_attr_e( 'Announcement', 'kilka' ); ?>">
	<p>
		<?php if ( '' !== $kilka_announcement['url'] ) : ?>
			<a href="<?php echo esc_url( $kilka_announcement['url'] ); ?>"><?php echo esc_html( $kilka_announcement['text'] ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $kilka_announcement['text'] ); ?>
		<?php endif; ?>
	</p>
</aside>
