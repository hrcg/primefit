<?php
/**
 * Minimal Header Scaffold (Header removed for reimplementation)
 *
 * @package PrimeFit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header" role="banner">
	<div class="container">
		<div class="header-inner">
			<nav class="header-left" aria-label="Primary Menu">
				<button class="hamburger" aria-label="Open menu" aria-controls="mobile-nav" aria-expanded="false">
					<span></span>
					<span></span>
					<span></span>
				</button>
				<?php
					wp_nav_menu([
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'      => 'menu menu--primary',
						'fallback_cb'     => false,
					]);
				?>
			</nav>

			<div class="header-center">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-brand" aria-label="<?php echo esc_attr( get_bloginfo('name') ); ?>">
					<img class="brand-logo" src="<?php echo esc_url( PRIMEFIT_THEME_URI . '/assets/images/logo-white.webp' ); ?>" alt="<?php echo esc_attr( get_bloginfo('name') ); ?>">
				</a>
			</div>

			<div class="header-right">
				<nav class="secondary-nav" aria-label="Secondary Menu">
					<?php
						wp_nav_menu([
							'theme_location' => 'secondary',
							'container'      => false,
							'menu_class'      => 'menu menu--secondary',
							'fallback_cb'     => false,
						]);
					?>
				</nav>
				<?php if ( class_exists('WooCommerce') ) { ?>
					<div class="cart-wrap" data-behavior="click">
						<a class="header-cart cart-toggle" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="View cart" aria-expanded="false" aria-controls="mini-cart-panel">
							<span class="cart-label">Cart</span>
							<span class="cart-corners" aria-hidden="true"><span class="cart-count" data-cart-count><?php echo WC()->cart ? intval( WC()->cart->get_cart_contents_count() ) : 0; ?></span></span>
						</a>
						<div class="cart-overlay"></div>
						<aside id="mini-cart-panel" class="cart-panel" role="dialog" aria-modal="true" aria-label="Cart" hidden>
							<div class="cart-panel-header">
								<span>Cart</span>
								<button type="button" class="cart-close" aria-label="Close">&times;</button>
							</div>
							<div class="cart-panel-content">
								<?php if ( function_exists( 'woocommerce_mini_cart' ) ) { woocommerce_mini_cart(); } ?>
							</div>
						</aside>
					</div>
				<?php } ?>
			</div>

			<div class="mobile-nav-wrap" id="mobile-nav">
				<div class="mobile-nav-overlay" aria-hidden="true"></div>
				<aside class="mobile-nav-panel" role="dialog" aria-modal="true" aria-label="Menu">
					<div class="mobile-nav-header">
						<span class="mobile-nav-title">END OF SEASON SALE</span>
						<button class="mobile-nav-close" aria-label="Close menu">&times;</button>
					</div>
					<nav class="mobile-nav" aria-label="Mobile Primary Menu">
						<?php
							wp_nav_menu([
								'theme_location' => 'primary',
								'container'      => false,
								'menu_class'      => 'mobile-menu',
								'fallback_cb'     => false,
							]);
						?>
					</nav>
				</aside>
			</div>
		</div>
	</div>
</header>

<main id="content" class="site-content" role="main">

