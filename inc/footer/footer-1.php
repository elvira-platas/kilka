<?php
/**
 * Footer action
 * @package Kilka
 */

function kilka_footer_style_1(){ ?>
<footer class="footer-area">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="copyright">
					<?php
					$footer_copyright_owner = trim( (string) get_theme_mod( 'kilka_footer_copyright_owner', get_bloginfo( 'name' ) ) );

					if ( '' !== $footer_copyright_owner ) :
						?>
						&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $footer_copyright_owner ); ?>
						<span class="sep"> | </span>
					<?php endif; ?>
					<a href="<?php echo esc_url( 'https://wordpress.org/' ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Powered by WordPress', 'kilka' ); ?></a>
					<?php
					$footer_link_text = get_theme_mod( 'kilka_footer_link_text' );
					$footer_link_url  = get_theme_mod( 'kilka_footer_link_url' );
					
					if ( ! empty( $footer_link_text ) && ! empty( $footer_link_url ) ) {
						echo '<span class="sep"> | </span>';
						echo '<a href="' . esc_url( $footer_link_url ) . '" target="_blank" rel="noopener">' . esc_html( $footer_link_text ) . '</a>';
					}

					$footer_link_text_2 = get_theme_mod( 'kilka_footer_link_text_2' );
					$footer_link_url_2  = get_theme_mod( 'kilka_footer_link_url_2' );
					
					if ( ! empty( $footer_link_text_2 ) && ! empty( $footer_link_url_2 ) ) {
						echo '<span class="sep"> | </span>';
						echo '<a href="' . esc_url( $footer_link_url_2 ) . '" target="_blank" rel="noopener">' . esc_html( $footer_link_text_2 ) . '</a>';
					}
					?>
				</div>
			</div>
		</div>
	</div>
</footer>
<?php }
add_action('kilka_footer_style','kilka_footer_style_1');
