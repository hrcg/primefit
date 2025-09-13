<?php
/**
 * ASRV Main Navigation Template Part
 *
 * Displays the main site navigation with logo and cart
 *
 * @package ASRV_Theme
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$header_config = ASRV_Theme_Config::get_header_config();
?>

<nav class="asrv-main-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Main Navigation', ASRV_THEME_TEXTDOMAIN ); ?>">
	<div class="asrv-container">
		<div class="asrv-nav-wrapper">
			
			<!-- Mobile Menu Toggle -->
			<button class="asrv-mobile-menu-toggle" aria-label="<?php esc_attr_e( 'Toggle Mobile Menu', ASRV_THEME_TEXTDOMAIN ); ?>">
				<span class="asrv-hamburger">
					<span class="asrv-hamburger-line"></span>
					<span class="asrv-hamburger-line"></span>
					<span class="asrv-hamburger-line"></span>
				</span>
			</button>
			
			<!-- Site Logo -->
			<div class="asrv-site-branding">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="asrv-site-title" rel="home">
						<?php bloginfo( 'name' ); ?>
					</a>
				<?php endif; ?>
			</div>
			
			<!-- Primary Menu -->
			<div class="asrv-primary-menu">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'menu_class'     => 'asrv-nav-menu',
					'container'      => false,
					'fallback_cb'    => false,
					'depth'          => 2,
				) );
				?>
			</div>
			
			<!-- Header Actions (Search, Account, Cart) -->
			<div class="asrv-header-actions">
				
				<!-- Search Toggle -->
				<button class="asrv-search-toggle" aria-label="<?php esc_attr_e( 'Toggle Search', ASRV_THEME_TEXTDOMAIN ); ?>">
					<svg class="asrv-icon asrv-icon--search" width="20" height="20" viewBox="0 0 20 20" fill="none">
						<path d="M19 19L13 13M15 8C15 11.866 11.866 15 8 15C4.134 15 1 11.866 1 8C1 4.134 4.134 1 8 1C11.866 1 15 4.134 15 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
				
				<!-- Account Link -->
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="asrv-account-link" aria-label="<?php esc_attr_e( 'My Account', ASRV_THEME_TEXTDOMAIN ); ?>">
						<svg class="asrv-icon asrv-icon--account" width="20" height="20" viewBox="0 0 20 20" fill="none">
							<path d="M16 19V17C16 15.9391 15.5786 14.9217 14.8284 14.1716C14.0783 13.4214 13.0609 13 12 13H8C6.93913 13 5.92172 13.4214 5.17157 14.1716C4.42143 14.9217 4 15.9391 4 17V19M12 9C12 11.2091 10.2091 13 8 13C5.79086 13 4 11.2091 4 9C4 6.79086 5.79086 5 8 5C10.2091 5 12 6.79086 12 9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</a>
				<?php endif; ?>
				
				<!-- Cart Link -->
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="asrv-cart-link" aria-label="<?php esc_attr_e( 'Shopping Cart', ASRV_THEME_TEXTDOMAIN ); ?>">
						<svg class="asrv-icon asrv-icon--cart" width="20" height="20" viewBox="0 0 20 20" fill="none">
							<path d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.7 15.3C4.3 15.7 4.6 16.5 5.1 16.5H17M17 13V17C17 17.6 16.6 18 16 18H8C7.4 18 7 17.6 7 17V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						<span class="asrv-cart-count"><?php echo esc_html( $header_config['cart_count'] ); ?></span>
					</a>
				<?php endif; ?>
				
			</div>
			
		</div>
	</div>
</nav>

<!-- Mobile Menu Overlay -->
<div class="asrv-mobile-menu-overlay">
	<div class="asrv-mobile-menu-content">
		<?php
		wp_nav_menu( array(
			'theme_location' => 'mobile',
			'menu_class'     => 'asrv-mobile-nav-menu',
			'container'      => false,
			'fallback_cb'    => 'asrv_fallback_mobile_menu',
			'depth'          => 2,
		) );
		?>
	</div>
</div>

<?php
/**
 * Fallback mobile menu if no menu is assigned
 */
function asrv_fallback_mobile_menu() {
	wp_nav_menu( array(
		'theme_location' => 'primary',
		'menu_class'     => 'asrv-mobile-nav-menu',
		'container'      => false,
		'fallback_cb'    => false,
		'depth'          => 2,
	) );
}
?>