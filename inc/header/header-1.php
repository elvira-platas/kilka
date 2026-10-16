<?php
/**
 * Header action
 * @package Kilka
 */

function kilka_header_style_1(){ ?>
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'kilka' ); ?></a>
	<header id="masthead" class="header-area <?php if(has_header_image() && is_front_page()): ?>kilka-header-img<?php endif; ?>">
		<?php if(has_header_image() && is_front_page()): ?>
	        <div class="header-img"> 
	        	<?php the_header_image_tag(); ?>
	        </div>
        <?php endif; ?>
		<div class="container">
			<div class="row align-items-center">
				<div class="col-lg-12">
					<div class="header-main-flex">
						<div class="site-branding text-center">
							<?php
							$kilka_second_blog_context = function_exists( 'kilka_is_second_blog_context' ) && kilka_is_second_blog_context();
							$kilka_exhibition_context  = function_exists( 'kilka_is_exhibition_context' ) && kilka_is_exhibition_context();
							$kilka_has_primary_menu   = has_nav_menu( 'menu-1' );
							$kilka_show_responsive_menu = $kilka_has_primary_menu || ! $kilka_exhibition_context;
							?>
							<div class="site-branding-main">
								<div class="site-branding-identity">
									<?php
									the_custom_logo();
									?>
									<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></a></p>
								</div>
								<?php if ( $kilka_show_responsive_menu ) : ?>
									<div class="kilka-responsive-menu" data-menu-label="<?php esc_attr_e( 'Menu', 'kilka' ); ?>">

										<?php if ( ! $kilka_exhibition_context ) : ?>
											<template class="kilka-color-scheme-template">
												<li class="kilka-color-scheme-item">
													<fieldset class="kilka-color-scheme-switcher">
														<legend class="screen-reader-text"><?php esc_html_e( 'Color scheme', 'kilka' ); ?></legend>
														<div class="kilka-color-scheme-options">
															<button type="button" data-color-scheme-option="auto" aria-pressed="true"><?php echo esc_html_x( 'Auto', 'color scheme option', 'kilka' ); ?></button>
															<button type="button" data-color-scheme-option="light" aria-pressed="false"><?php echo esc_html_x( 'Light', 'color scheme option', 'kilka' ); ?></button>
															<button type="button" data-color-scheme-option="dark" aria-pressed="false"><?php echo esc_html_x( 'Dark', 'color scheme option', 'kilka' ); ?></button>
														</div>
													</fieldset>
												</li>
											</template>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
							<?php
							$kilka_description = get_bloginfo( 'description', 'display' );
							if ( is_home() && ! $kilka_second_blog_context && ( $kilka_description || is_customize_preview() ) ) :
								?>
								<p class="site-description"><?php echo esc_html($kilka_description); ?></p>
							<?php endif; ?>
							<?php
							if ( $kilka_second_blog_context ) :
								$second_blog_heading     = trim( (string) get_theme_mod( 'kilka_second_blog_heading', '' ) );
								$second_blog_description = trim( (string) get_theme_mod( 'kilka_second_blog_description', '' ) );
								$second_blog_disclosure  = trim( (string) get_theme_mod( 'kilka_second_blog_disclosure', '' ) );
								$second_blog_source_note = trim( (string) get_theme_mod( 'kilka_second_blog_source_note', '' ) );
								$second_blog_archive_url = get_post_type_archive_link( 'world_note' );

								if ( ! $second_blog_archive_url && function_exists( 'kilka_get_world_note_slug' ) ) {
									$second_blog_archive_url = home_url( '/' . trim( (string) kilka_get_world_note_slug(), '/' ) . '/' );
								}

								$show_second_blog_intro      = is_post_type_archive( 'world_note' ) || is_customize_preview();
								$show_second_blog_disclosure = is_singular( 'world_note' ) && $second_blog_disclosure;

								if ( $show_second_blog_intro && ( $second_blog_heading || $second_blog_description || $second_blog_disclosure || is_customize_preview() ) ) :
									?>
									<div class="second-blog-intro">
										<?php if ( $second_blog_heading || is_customize_preview() ) : ?>
											<p class="second-blog-title">
												<?php if ( $second_blog_archive_url ) : ?>
													<a href="<?php echo esc_url( $second_blog_archive_url ); ?>"><?php echo esc_html( $second_blog_heading ); ?></a>
												<?php else : ?>
													<?php echo esc_html( $second_blog_heading ); ?>
												<?php endif; ?>
											</p>
										<?php endif; ?>
										<?php if ( $second_blog_description || is_customize_preview() ) : ?>
											<p class="second-blog-description">
												<?php echo wp_kses_post( nl2br( esc_html( $second_blog_description ) ) ); ?>
												<?php if ( $second_blog_source_note ) : ?>
													<sup><a id="second-blog-source-marker" class="second-blog-source-marker" href="#second-blog-source-note" aria-label="<?php esc_attr_e( 'Go to source note', 'kilka' ); ?>">1</a></sup>
												<?php endif; ?>
											</p>
										<?php endif; ?>
										<?php if ( $second_blog_disclosure || is_customize_preview() ) : ?>
											<p class="second-blog-disclosure">
												<?php if ( $second_blog_disclosure ) : ?>
													<?php echo esc_html( $second_blog_disclosure ); ?>
												<?php endif; ?>
											</p>
										<?php endif; ?>
									</div>
								<?php elseif ( $show_second_blog_disclosure ) : ?>
									<div class="second-blog-intro second-blog-intro--single">
										<p class="second-blog-disclosure"><?php echo esc_html( $second_blog_disclosure ); ?></p>
									</div>
								<?php endif; ?>
							<?php endif; ?>
						</div><!-- .site-branding -->
					</div>
				</div>
			</div>
		</div>
	</header><!-- #masthead -->
	<?php if ( $kilka_show_responsive_menu ) : ?>
	<nav class="mainmenu-area" aria-label="<?php esc_attr_e( 'Menu', 'kilka' ); ?>">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="mainmenu">
						<?php
							if ( $kilka_has_primary_menu ) {
								wp_nav_menu( array(
									'theme_location' => 'menu-1',
									'menu_id'        => 'primary-menu',
								) );
							} else {
								echo '<ul id="primary-menu" class="menu"><li class="kilka-menu-search">' . get_search_form( false ) . '</li></ul>';
							}
						?>
					</div>
				</div>
			</div>
		</div>
	</nav>
	<?php endif; ?>
<?php }
add_action('kilka_header_style','kilka_header_style_1');
