<?php
/**
 * Information panel for a Kilka Exhibition.
 *
 * Portable data is supplied by the Kilka Exhibitions plugin. The theme owns
 * this interactive presentation and its relationship to the shared site frame.
 *
 * @package Kilka
 */

$kilka_exhibition_id = get_the_ID();

if (
	! function_exists( 'kilka_exhibitions_get_information_context' ) ||
	! function_exists( 'kilka_exhibitions_get_information_items' ) ||
	! function_exists( 'kilka_exhibitions_information_panel_enabled' ) ||
	! kilka_exhibitions_information_panel_enabled( $kilka_exhibition_id )
) {
	return;
}

$kilka_information_context = kilka_exhibitions_get_information_context( $kilka_exhibition_id );
$kilka_information_items   = kilka_exhibitions_get_information_items( $kilka_exhibition_id );
$kilka_heading             = isset( $kilka_information_context['heading'] ) ? $kilka_information_context['heading'] : '';
$kilka_description         = isset( $kilka_information_context['description'] ) ? $kilka_information_context['description'] : '';
$kilka_creator             = isset( $kilka_information_context['creator'] ) ? $kilka_information_context['creator'] : '';
$kilka_copyright           = isset( $kilka_information_context['copyright'] ) ? $kilka_information_context['copyright'] : '';

if ( ! $kilka_information_items && ! $kilka_heading && ! $kilka_description && ! $kilka_creator && ! $kilka_copyright ) {
	return;
}

$kilka_panel_id       = 'kilka-exhibition-information-' . $kilka_exhibition_id;
$kilka_panel_title_id = $kilka_panel_id . '-title';
?>

<a class="kilka-exhibition__information-toggle" href="#<?php echo esc_attr( $kilka_panel_id ); ?>" aria-label="<?php esc_attr_e( 'About this exhibition', 'kilka' ); ?>" aria-controls="<?php echo esc_attr( $kilka_panel_id ); ?>">i</a>

<aside id="<?php echo esc_attr( $kilka_panel_id ); ?>" class="kilka-exhibition__information" aria-labelledby="<?php echo esc_attr( $kilka_panel_title_id ); ?>">
	<div class="kilka-exhibition__information-panel">
		<button class="kilka-exhibition__information-close" type="button" hidden aria-label="<?php esc_attr_e( 'Close information', 'kilka' ); ?>">&times;</button>
		<header class="kilka-exhibition__information-section kilka-exhibition__information-intro">
			<?php if ( $kilka_heading ) : ?>
				<h2 id="<?php echo esc_attr( $kilka_panel_title_id ); ?>" class="kilka-exhibition__information-title"><?php echo esc_html( $kilka_heading ); ?></h2>
			<?php else : ?>
				<h2 id="<?php echo esc_attr( $kilka_panel_title_id ); ?>" class="screen-reader-text"><?php esc_html_e( 'Exhibition information', 'kilka' ); ?></h2>
			<?php endif; ?>

			<?php if ( $kilka_description ) : ?>
				<div class="kilka-exhibition__information-note"><?php echo wp_kses_post( wpautop( $kilka_description ) ); ?></div>
			<?php endif; ?>
		</header>

		<?php if ( $kilka_creator || $kilka_copyright ) : ?>
			<div class="kilka-exhibition__information-section">
				<dl class="kilka-exhibition__information-details">
					<?php if ( $kilka_creator ) : ?>
						<div>
							<dt class="screen-reader-text"><?php esc_html_e( 'Creator', 'kilka' ); ?></dt>
							<dd><?php echo esc_html( $kilka_creator ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( $kilka_copyright ) : ?>
						<div>
							<dt class="screen-reader-text"><?php esc_html_e( 'Rights', 'kilka' ); ?></dt>
							<dd><?php echo esc_html( $kilka_copyright ); ?></dd>
						</div>
					<?php endif; ?>
				</dl>
			</div>
		<?php endif; ?>

		<?php if ( $kilka_information_items ) : ?>
			<section class="kilka-exhibition__information-section">
				<h3 class="screen-reader-text"><?php esc_html_e( 'Works', 'kilka' ); ?></h3>
				<ol class="kilka-exhibition__works">
					<?php foreach ( $kilka_information_items as $kilka_item ) : ?>
						<?php
						$kilka_item_rights = array();

						if ( $kilka_item['creator'] && $kilka_item['creator'] !== $kilka_creator ) {
							$kilka_item_rights[] = $kilka_item['creator'];
						}

						if ( $kilka_item['copyright'] && $kilka_item['copyright'] !== $kilka_copyright ) {
							$kilka_item_rights[] = $kilka_item['copyright'];
						}
						?>
						<li value="<?php echo esc_attr( $kilka_item['position'] ); ?>">
							<span class="kilka-exhibition__work-caption"><?php echo esc_html( $kilka_item['caption'] ); ?></span>
							<?php if ( $kilka_item_rights ) : ?>
								<span class="kilka-exhibition__work-rights">
									<?php echo esc_html( implode( ' · ', $kilka_item_rights ) ); ?>
								</span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>
	</div>
</aside>
