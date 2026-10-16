<?php
/**
 * Search form template.
 *
 * @package Kilka
 */

$search_post_type       = function_exists( 'kilka_get_contextual_search_post_type' ) ? kilka_get_contextual_search_post_type() : 'post';
$is_world_note_search   = 'world_note' === $search_post_type;
$search_form_class_name = 'search-form kilka-icon-search-form';
if ( $is_world_note_search ) {
	$search_form_class_name .= ' kilka-world-note-search-form';
}
?>
<form role="search" method="get" class="<?php echo esc_attr( $search_form_class_name ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label>
		<span class="screen-reader-text"><?php echo esc_html_x( 'Search for:', 'label', 'kilka' ); ?></span>
		<input
			type="search"
			class="search-field"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			name="s"
		/>
	</label>
	<input type="hidden" name="post_type" value="<?php echo esc_attr( $search_post_type ); ?>" />
	<button type="submit" class="search-submit kilka-search-icon-button" aria-label="<?php echo esc_attr_x( 'Search', 'submit button', 'kilka' ); ?>">
		<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">
			<circle cx="10.5" cy="10.5" r="6.5"></circle>
			<path d="M15.5 15.5L20 20"></path>
		</svg>
	</button>
</form>
