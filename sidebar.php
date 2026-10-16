<?php
/**
 * The sidebar containing the contextual widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Kilka
 */

$kilka_sidebar_id = kilka_get_contextual_sidebar_id();

if ( ! is_active_sidebar( $kilka_sidebar_id ) ) {
	return;
}
?>

<aside id="secondary" class="widget-area">
	<?php dynamic_sidebar( $kilka_sidebar_id ); ?>
</aside><!-- #secondary -->
