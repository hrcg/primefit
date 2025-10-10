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

<?php get_template_part( 'templates/header/promo-bar' ); ?>

<header class="site-header" role="banner">
	<div class="container">
		<div class="header-inner">
			<nav class="header-left" aria-label="Primary Menu">
				<!-- Back Button (only visible on single product pages) -->
				<button class="header-back-button" aria-label="<?php esc_attr_e( 'Go back', 'primefit' ); ?>" data-home-url="<?php echo esc_url( home_url( '/' ) ); ?>" style="display: none;">
					<img src="<?php echo esc_url( PRIMEFIT_THEME_URI . '/assets/images/back-button.svg' ); ?>" alt="<?php esc_attr_e( 'Go back', 'primefit' ); ?>" class="back-button-icon">
				</button>
				<!-- Hamburger Menu -->
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

			<?php 
			$mega_menu_config = primefit_get_mega_menu_config();
			if ( $mega_menu_config['enabled'] ) : 
			?>
			<!-- Mega Menu -->
			<div class="mega-menu" id="mega-menu" aria-hidden="true">
				<div class="container">
					<div class="mega-menu-content">
						<div class="mega-menu-column">
							<h3 class="mega-menu-heading"><?php echo esc_html( $mega_menu_config['column_1_heading'] ); ?></h3>
							<ul class="mega-menu-links">
								<?php
								$links_1 = primefit_get_category_links( $mega_menu_config['column_1_links'] );
								foreach ( $links_1 as $link ) {
									echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['name'] ) . '</a></li>';
								}
								?>
							</ul>
						</div>
						<div class="mega-menu-column">
							<h3 class="mega-menu-heading"><?php echo esc_html( $mega_menu_config['column_2_heading'] ); ?></h3>
							<ul class="mega-menu-links">
								<?php
								$links_2 = primefit_get_category_links( $mega_menu_config['column_2_links'] );
								foreach ( $links_2 as $link ) {
									echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['name'] ) . '</a></li>';
								}
								?>
							</ul>
						</div>
						<div class="mega-menu-column">
							<h3 class="mega-menu-heading"><?php echo esc_html( $mega_menu_config['column_3_heading'] ); ?></h3>
							<ul class="mega-menu-links">
								<?php
								$links_3 = primefit_get_category_links( $mega_menu_config['column_3_links'] );
								foreach ( $links_3 as $link ) {
									echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['name'] ) . '</a></li>';
								}
								?>
							</ul>
						</div>
						<div class="mega-menu-column">
							<h3 class="mega-menu-heading"><?php echo esc_html( $mega_menu_config['column_4_heading'] ); ?></h3>
							<ul class="mega-menu-links">
								<?php
								$links_4 = primefit_get_category_links( $mega_menu_config['column_4_links'] );
								foreach ( $links_4 as $link ) {
									echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['name'] ) . '</a></li>';
								}
								?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<div class="header-center">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-brand" aria-label="<?php echo esc_attr( get_bloginfo('name') ); ?>">
					<img class="brand-logo" src="<?php echo esc_url( PRIMEFIT_THEME_URI . '/assets/images/logo-white.webp' ); ?>" alt="<?php echo esc_attr( get_bloginfo('name') ); ?>">
					<img class="brand-symbol" src="<?php echo esc_url( PRIMEFIT_THEME_URI . '/assets/images/symbol.webp' ); ?>" alt="<?php echo esc_attr( get_bloginfo('name') ); ?>" >
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
						<a class="header-cart cart-toggle" href="#" aria-label="View cart" aria-expanded="false" aria-controls="mini-cart-panel">
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
								<div class="widget_shopping_cart_content">
									<?php if ( function_exists( 'woocommerce_mini_cart' ) ) { woocommerce_mini_cart(); } ?>
								</div>
							</div>
						</aside>
					</div>
				<?php } ?>
			</div>

			<div class="mobile-nav-wrap" id="mobile-nav">
				<div class="mobile-nav-overlay" aria-hidden="true"></div>
				<aside class="mobile-nav-panel" role="dialog" aria-modal="true" aria-label="Menu">
					<div class="mobile-nav-header">
						<a href="product-category/sale" style="text-decoration: none; color: inherit;">
						<span class="mobile-nav-title">END OF SEASON SALE</span>
				</a>
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
					
					<nav class="mobile-nav mobile-nav--tertiary" aria-label="Mobile Tertiary Menu">
						<?php
							wp_nav_menu([
								'theme_location' => 'secondary',
								'container'      => false,
								'menu_class'      => 'mobile-menu mobile-menu--tertiary',
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

