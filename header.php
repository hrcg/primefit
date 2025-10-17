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
	<meta name="verify-paysera" content="27896fd15f547b01780669a2c65f1189">
	
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-SQ3XS6BVYY"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', 'G-SQ3XS6BVYY');
	</script>
	
	<!-- Microsoft Clarity -->
	<script type="text/javascript">
		(function(c,l,a,r,i,t,y){
			c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
			t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
			y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
		})(window, document, "clarity", "script", "t8m0awb6pd");
	</script>
	
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
				
				<!-- Search Icon for Mobile -->
				<button class="search-toggle search-toggle--mobile" aria-label="Search" aria-controls="search-overlay" aria-expanded="false">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="11" cy="11" r="8"></circle>
						<path d="m21 21-4.35-4.35"></path>
					</svg>
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

			<!-- Search Overlay -->
			<div class="search-overlay-wrap" id="search-overlay">
				<div class="search-overlay" aria-hidden="true"></div>
				<div class="search-panel" role="dialog" aria-modal="true" aria-label="Search" hidden>
					<div class="search-panel-header">
						<div class="search-input-container">
							<svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="11" cy="11" r="8"></circle>
								<path d="m21 21-4.35-4.35"></path>
							</svg>
							<input type="text" class="search-input" placeholder="Search products..." autocomplete="off" aria-label="Search products">
							<button class="search-close" aria-label="Close search">&times;</button>
						</div>
                        <div class="search-results-header">
                            <span class="search-results-count">0 results</span>
                        </div>
					</div>
					<div class="search-panel-content">
						<div class="search-results">
							<!-- Search results will be populated here -->
						</div>
						<div class="search-loading" style="display: none;">
							<div class="search-spinner"></div>
							<span>Searching...</span>
						</div>
						<div class="search-no-results" style="display: none;">
							<p>No products found. Try a different search term.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</header>

<main id="content" class="site-content" role="main">

