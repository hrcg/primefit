<?php
/**
 * PrimeFit Theme Asset Enqueuing
 *
 * Scripts and styles enqueuing functions
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue theme assets
 *
 * @since 1.0.0
 */
add_action( 'wp_enqueue_scripts', 'primefit_enqueue_assets' );
function primefit_enqueue_assets() {
	// Google Fonts - Figtree with optimized loading and font-display: swap
	wp_enqueue_style(
		'primefit-fonts',
		'https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap',
		[],
		null,
		'all'
	);

	// Font preload hints are handled by primefit_prioritize_critical_resources()
	// Removed duplicate preload to prevent "not used" warnings
	
	// Critical CSS - inline for immediate rendering
	primefit_inline_critical_css();

	// Theme styles with critical CSS loading optimization
	wp_enqueue_style(
		'primefit-style',
		get_stylesheet_uri(),
		[ 'primefit-fonts' ],
		PRIMEFIT_VERSION
	);

	// Load main CSS with media attribute for better performance
	wp_enqueue_style(
		'primefit-app',
		PRIMEFIT_THEME_URI . '/assets/css/app.css',
		[ 'primefit-fonts' ],
		primefit_get_file_version( '/assets/css/app.css' ),
		'all'
	);
	
	// Mobile-first CSS loading strategy
	primefit_optimize_css_delivery();

	// Header-specific styles - Load early for above-the-fold content
	// Mobile-first: Load immediately on mobile, defer on desktop
	$header_media = wp_is_mobile() ? 'all' : 'print';
	wp_enqueue_style(
		'primefit-header',
		PRIMEFIT_THEME_URI . '/assets/css/header.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/header.css' ),
		$header_media
	);
	
	// Load header CSS for desktop after page load
	if ( ! wp_is_mobile() ) {
		add_action( 'wp_footer', function() {
			echo '<link rel="stylesheet" href="' . PRIMEFIT_THEME_URI . '/assets/css/header.css?v=' . primefit_get_file_version( '/assets/css/header.css' ) . '" media="all">';
		}, 1 );
	}

	// Footer-specific styles - load for all devices
	// Mobile-first: Load immediately on mobile, defer on desktop
	$footer_media = wp_is_mobile() ? 'all' : 'print';
	wp_enqueue_style(
		'primefit-footer',
		PRIMEFIT_THEME_URI . '/assets/css/footer.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/footer.css' ),
		$footer_media
	);
	
	// Load footer CSS for desktop after page load
	if ( ! wp_is_mobile() ) {
		add_action( 'wp_footer', function() {
			echo '<link rel="stylesheet" href="' . PRIMEFIT_THEME_URI . '/assets/css/footer.css?v=' . primefit_get_file_version( '/assets/css/footer.css' ) . '" media="all">';
		}, 1 );
	}

	// Cart-specific styles - load conditionally to reduce critical path
	$page_type = primefit_get_page_type();
	if ( in_array( $page_type, [ 'product', 'shop', 'category', 'tag', 'cart', 'checkout', 'front_page' ] ) ) {
		// Mobile-first: Load immediately on mobile, defer on desktop
		$cart_media = wp_is_mobile() ? 'all' : 'print';
		wp_enqueue_style(
			'primefit-cart',
			PRIMEFIT_THEME_URI . '/assets/css/cart.css',
			[ 'primefit-app' ],
			primefit_get_file_version( '/assets/css/cart.css' ),
			$cart_media
		);
		
		// Load cart CSS for desktop after page load
		if ( ! wp_is_mobile() ) {
			add_action( 'wp_footer', function() {
				echo '<link rel="stylesheet" href="' . PRIMEFIT_THEME_URI . '/assets/css/cart.css?v=' . primefit_get_file_version( '/assets/css/cart.css' ) . '" media="all">';
			}, 1 );
		}
	}
	// WooCommerce styles - load for all devices
	if ( class_exists( 'WooCommerce' ) ) {
		// Mobile-first: Load immediately on mobile, defer on desktop
		$woocommerce_media = wp_is_mobile() ? 'all' : 'print';
		wp_enqueue_style(
			'primefit-woocommerce',
			PRIMEFIT_THEME_URI . '/assets/css/woocommerce.css',
			[ 'primefit-app' ],
			primefit_get_file_version( '/assets/css/woocommerce.css' ),
			$woocommerce_media
		);
		
		// Load WooCommerce CSS for desktop after page load
		if ( ! wp_is_mobile() ) {
			add_action( 'wp_footer', function() {
				echo '<link rel="stylesheet" href="' . PRIMEFIT_THEME_URI . '/assets/css/woocommerce.css?v=' . primefit_get_file_version( '/assets/css/woocommerce.css' ) . '" media="all">';
			}, 1 );
		}
		
		// Cache page type for optimized CSS loading
		$page_type = primefit_get_page_type();
		
		// Single product page styles with critical CSS optimization
		if ( $page_type === 'product' ) {
			$single_product_css_url = PRIMEFIT_THEME_URI . '/assets/css/single-product.css';
			
			// Mobile-first: Load immediately on mobile, defer on desktop
			$single_product_media = wp_is_mobile() ? 'all' : 'print';
			wp_enqueue_style(
				'primefit-single-product',
				$single_product_css_url,
				[ 'primefit-woocommerce' ],
				primefit_get_file_version( '/assets/css/single-product.css' ),
				$single_product_media
			);
			
			// Load single product CSS for desktop after page load
			if ( ! wp_is_mobile() ) {
				add_action( 'wp_footer', function() use ( $single_product_css_url ) {
					echo '<link rel="stylesheet" href="' . esc_url( $single_product_css_url ) . '?v=' . primefit_get_file_version( '/assets/css/single-product.css' ) . '" media="all">';
				}, 1 );
			}
		}
		
		// Checkout page styles - Load ONLY on checkout pages
		if ( $page_type === 'checkout' ) {
			// Additional verification to ensure we're really on a checkout page
			$is_checkout_page = is_checkout();
			$is_custom_checkout = (function_exists('wc_get_page_id') && is_page(wc_get_page_id('checkout')));

			if ( $is_checkout_page || $is_custom_checkout ) {
				// Mobile-first: Load immediately on mobile, defer on desktop
				$checkout_media = wp_is_mobile() ? 'all' : 'print';
				wp_enqueue_style(
					'primefit-checkout',
					PRIMEFIT_THEME_URI . '/assets/css/checkout.css',
					[ 'primefit-woocommerce' ],
					primefit_get_file_version( '/assets/css/checkout.css' ),
					$checkout_media
				);
				
				// Load checkout CSS for desktop after page load
				if ( ! wp_is_mobile() ) {
					add_action( 'wp_footer', function() {
						echo '<link rel="stylesheet" href="' . PRIMEFIT_THEME_URI . '/assets/css/checkout.css?v=' . primefit_get_file_version( '/assets/css/checkout.css' ) . '" media="all">';
					}, 1 );
				}
			}
		}
		
		// Account page styles - Load ONLY on my-account pages
		$is_account_page = is_account_page();
		$is_custom_account = (function_exists('wc_get_page_id') && is_page(wc_get_page_id('myaccount')));
		$is_account_endpoint = function_exists('is_wc_endpoint_url') && (
			is_wc_endpoint_url('orders') ||
			is_wc_endpoint_url('downloads') ||
			is_wc_endpoint_url('edit-account') ||
			is_wc_endpoint_url('edit-address') ||
			is_wc_endpoint_url('payment-methods') ||
			is_wc_endpoint_url('customer-logout') ||
			is_wc_endpoint_url('dashboard')
		);

		// Load account styles if we're on any type of account page
		if ( $page_type === 'account' || $is_account_page || $is_custom_account || $is_account_endpoint ) {
			// Mobile-first: Load immediately on mobile, defer on desktop
			$account_media = wp_is_mobile() ? 'all' : 'print';
			wp_enqueue_style(
				'primefit-account',
				PRIMEFIT_THEME_URI . '/assets/css/account.css',
				[ 'primefit-woocommerce' ],
				primefit_get_file_version( '/assets/css/account.css' ),
				$account_media
			);
			
			// Load account CSS for desktop after page load
			if ( ! wp_is_mobile() ) {
				add_action( 'wp_footer', function() {
					echo '<link rel="stylesheet" href="' . PRIMEFIT_THEME_URI . '/assets/css/account.css?v=' . primefit_get_file_version( '/assets/css/account.css' ) . '" media="all">';
				}, 1 );
			}

			wp_enqueue_script(
				'primefit-account',
				PRIMEFIT_THEME_URI . '/assets/js/account.js',
				[ 'jquery' ],
				primefit_get_file_version( '/assets/js/account.js' ),
				true
			);
		}
		
		// Payment summary styles - Load ONLY on order received pages
		$load_payment_summary = false;

		// Only load on order received page - be very specific
		// Check if we're on the order received endpoint with valid order parameters
		if ( is_wc_endpoint_url( 'order-received' ) && isset( $_GET['key'] ) && isset( $_GET['order'] ) ) {
			$load_payment_summary = true;
		}

		// Alternative check: if we have order and key parameters and we're on checkout thank you page
		if ( isset( $_GET['key'] ) && isset( $_GET['order'] ) && is_checkout() && ! is_account_page() ) {
			$load_payment_summary = true;
		}

		if ( $load_payment_summary ) {
			wp_enqueue_style(
				'primefit-payment-summary',
				PRIMEFIT_THEME_URI . '/assets/css/payment-summary.css',
				[ 'primefit-woocommerce' ],
				primefit_get_file_version( '/assets/css/payment-summary.css' )
			);

			wp_enqueue_script(
				'primefit-payment-summary',
				PRIMEFIT_THEME_URI . '/assets/js/payment-summary.js',
				[ 'jquery' ],
				primefit_get_file_version( '/assets/js/payment-summary.js' ),
				true
			);
		}
	}
	
	// Load core functionality first (required on all pages) - high priority
	wp_enqueue_script(
		'primefit-core',
		PRIMEFIT_THEME_URI . '/assets/js/core.js',
		[ 'jquery' ],
		primefit_get_file_version( '/assets/js/core.js' ),
		false // Load in head for immediate scroll listener setup
	);

	// Add inline script to ensure scroll listener is initialized after scripts are loaded
	add_action('wp_footer', function() {
		?>
		<script>
		(function($) {
			$(document).ready(function() {
				// Ensure header scroll is initialized
				if (typeof window.initHeaderScroll === 'function') {
					window.initHeaderScroll();
				} else if (typeof window.initHeaderScrollVanilla === 'function') {
					window.initHeaderScrollVanilla();
				}

				// Debug: Check if header element exists
				const $header = $('.site-header');
			});
		})(jQuery);
		</script>
		<?php
	}, 999);

	// Load main app (minimal initialization) - high priority
	wp_enqueue_script(
		'primefit-app',
		PRIMEFIT_THEME_URI . '/assets/js/app.js',
		[ 'primefit-core' ],
		primefit_get_file_version( '/assets/js/app.js' ),
		false // Load in head for immediate availability
	);
	
	// Load page-specific modules in order of importance
	$page_type = primefit_get_page_type();

	// Critical functionality - load first
	if ( in_array( $page_type, [ 'product', 'shop', 'category', 'tag', 'front_page', 'cart', 'checkout' ] ) ) {
		wp_enqueue_script(
			'primefit-cart',
			PRIMEFIT_THEME_URI . '/assets/js/cart.js',
			[ 'primefit-app' ],
			primefit_get_file_version( '/assets/js/cart.js' ),
			true // Defer for better performance
		);
	}
	
	// Shop functionality - load on shop pages
	// On mobile, this will be lazy loaded instead
	if ( in_array( $page_type, [ 'shop', 'category', 'tag', 'front_page' ] ) && ! wp_is_mobile() ) {
		wp_enqueue_script( 
			'primefit-shop', 
			PRIMEFIT_THEME_URI . '/assets/js/shop.js', 
			[ 'jquery' ], // Remove dependency on core.js to break the chain
			primefit_get_file_version( '/assets/js/shop.js' ), 
			true 
		);
	}
	
	// Mega menu - load on all pages (header navigation) - defer for better performance
	wp_enqueue_script( 
		'primefit-mega-menu', 
		PRIMEFIT_THEME_URI . '/assets/js/mega-menu.js', 
		[ 'jquery' ], 
		primefit_get_file_version( '/assets/js/mega-menu.js' ), 
		true 
	);
	
	// Hero video - load on front page and pages with hero sections - defer for better performance
	// On mobile, this will be lazy loaded instead
	if ( ( $page_type === 'front_page' || is_page_template( 'page-hero.php' ) ) && ! wp_is_mobile() ) {
		wp_enqueue_script( 
			'primefit-hero-video', 
			PRIMEFIT_THEME_URI . '/assets/js/hero-video.js', 
			[ 'jquery' ], 
			primefit_get_file_version( '/assets/js/hero-video.js' ), 
			true 
		);
	}
	
	// Lazy load non-critical JavaScript modules for mobile performance
	primefit_lazy_load_js_modules();
	
	// Pass data to JavaScript
	wp_localize_script( 'primefit-core', 'primefitData', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'primefit_nonce' ),
		'isMobile' => wp_is_mobile(),
		'breakpoints' => [
			'mobile' => 768,
			'tablet' => 1024,
			'desktop' => 1200
		]
	] );
	
	// WooCommerce scripts and cart nonces - conditionally load based on page type
	if ( class_exists( 'WooCommerce' ) ) {
		// Cache page type for performance
		$page_type = primefit_get_page_type();

		// Only load cart fragments on pages that need it - defer to reduce critical path
		if ( in_array( $page_type, [ 'product', 'shop', 'category', 'tag', 'cart', 'checkout', 'account', 'front_page' ] ) ) {
			wp_enqueue_script( 'wc-cart-fragments' );
			// Defer cart fragments to reduce critical path latency
			wp_script_add_data( 'wc-cart-fragments', 'defer', true );
		}

		// Only load add to cart scripts on product-related pages
		if ( in_array( $page_type, [ 'product', 'shop', 'category', 'tag' ] ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}
		
		// Localize WooCommerce add to cart parameters
		wp_localize_script( 'wc-add-to-cart', 'wc_add_to_cart_params', [
			'ajax_url' => WC_AJAX::get_endpoint( 'add_to_cart' ),
			'wc_ajax_add_to_cart_nonce' => wp_create_nonce( 'wc_ajax_add_to_cart' ),
			'i18n_view_cart' => esc_attr__( 'View cart', 'woocommerce' ),
			'cart_url' => apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url() ),
			'is_cart' => is_cart(),
			'cart_redirect_after_add' => get_option( 'woocommerce_cart_redirect_after_add' ),
		] );
		
		wp_localize_script( 'primefit-app', 'primefit_cart_params', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'update_cart_nonce' => wp_create_nonce( 'woocommerce_update_cart_nonce' ),
			'remove_cart_nonce' => wp_create_nonce( 'woocommerce_remove_cart_nonce' ),
			'add_to_cart_nonce' => wp_create_nonce( 'woocommerce_add_to_cart_nonce' ),
			'apply_coupon_nonce' => wp_create_nonce( 'apply_coupon' ),
			'remove_coupon_nonce' => wp_create_nonce( 'remove_coupon' ),
		] );
	}
}

/**
 * Enqueue product-specific scripts
 */
/**
 * Get cached page type for optimized conditional loading
 */
function primefit_get_page_type() {
	static $page_type = null;
	if ( $page_type === null ) {
		if ( is_product() ) $page_type = 'product';
		elseif ( is_shop() ) $page_type = 'shop';
		elseif ( is_product_category() ) $page_type = 'category';
		elseif ( is_product_tag() ) $page_type = 'tag';
		elseif ( is_checkout() ) $page_type = 'checkout';
		elseif ( is_cart() ) $page_type = 'cart';
		elseif ( is_account_page() ) $page_type = 'account';
		elseif ( is_front_page() ) $page_type = 'front_page';
		elseif ( function_exists('wc_get_page_id') && is_page(wc_get_page_id('shop')) ) $page_type = 'shop';
		elseif ( function_exists('wc_get_page_id') && is_page(wc_get_page_id('cart')) ) $page_type = 'cart';
		elseif ( function_exists('wc_get_page_id') && is_page(wc_get_page_id('checkout')) ) $page_type = 'checkout';
		elseif ( function_exists('wc_get_page_id') && is_page(wc_get_page_id('myaccount')) ) $page_type = 'account';
		else $page_type = 'other';
	}
	return $page_type;
}

add_action( 'wp_enqueue_scripts', 'primefit_enqueue_product_scripts' );
function primefit_enqueue_product_scripts() {
	// Cache page type detection for better performance
	$page_type = primefit_get_page_type();
	
	// Simplified conditional loading based on cached page type
	if ( in_array( $page_type, [ 'product', 'shop', 'category', 'tag', 'front_page', 'cart', 'checkout' ] ) ) {
		wp_enqueue_script( 
			'primefit-product', 
			PRIMEFIT_THEME_URI . '/assets/js/product.js', 
			[ 'jquery' ], 
			primefit_get_file_version( '/assets/js/product.js' ), 
			true 
		);
		
		// Single product page specific scripts with optimization
		if ( $page_type === 'product' ) {
			// Preload critical product scripts for better performance
			wp_enqueue_script(
				'primefit-single-product',
				PRIMEFIT_THEME_URI . '/assets/js/single-product.js',
				[ 'jquery', 'primefit-product' ],
				primefit_get_file_version( '/assets/js/single-product.js' ),
				false // Load in footer for better performance
			);

			// Add preload for critical above-the-fold content
			// Note: This is handled by primefit_add_critical_preloads() function
		}
		
		// Checkout page specific scripts - Only load on actual checkout pages
		if ( $page_type === 'checkout' ) {
			// Additional verification to ensure we're really on a checkout page
			$is_checkout_page = is_checkout();
			$is_custom_checkout = (function_exists('wc_get_page_id') && is_page(wc_get_page_id('checkout')));

			if ( $is_checkout_page || $is_custom_checkout ) {
				// Ensure WooCommerce scripts are loaded
				wp_enqueue_script( 'woocommerce' );
				wp_enqueue_script( 'wc-checkout' );

				wp_enqueue_script(
					'primefit-checkout',
					PRIMEFIT_THEME_URI . '/assets/js/checkout.js',
					[ 'jquery', 'woocommerce', 'wc-checkout' ],
					primefit_get_file_version( '/assets/js/checkout.js' ),
					true
				);
			
			
				// Localize checkout script with shop URL and redirect flag
				$should_redirect = get_transient( 'primefit_checkout_redirect_to_shop' );
				if ( $should_redirect ) {
					delete_transient( 'primefit_checkout_redirect_to_shop' );
				}

				// Don't override WooCommerce's checkout params - let WC handle it
				// Only add our custom params that don't conflict
				wp_localize_script( 'primefit-checkout', 'primefit_checkout_params', array(
					'shop_url' => wc_get_page_permalink( 'shop' ),
					'should_redirect' => $should_redirect,
				) );
			}
		}
	}
}

/**
 * Prioritize critical resources for better first load performance
 */
add_action('wp_head', 'primefit_prioritize_critical_resources', 1);
function primefit_prioritize_critical_resources() {
	// Preload critical fonts with higher priority
	echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" as="style" fetchpriority="high">';

	// Preload above-the-fold images
	if (is_front_page()) {
		$hero_config = primefit_get_hero_config();
		if (!empty($hero_config['image_desktop'])) {
			$desktop_url = $hero_config['image_desktop'];
			$mobile_url = $hero_config['image_mobile'] ?? $desktop_url;

			// Preload hero images with high priority - desktop
			echo '<link rel="preload" href="' . esc_url($desktop_url) . '" as="image" media="(min-width: 769px)" fetchpriority="high">';

			// Preload hero images with high priority - mobile
			echo '<link rel="preload" href="' . esc_url($mobile_url) . '" as="image" media="(max-width: 768px)" fetchpriority="high">';

			// Preload WebP versions if available
			$desktop_webp = primefit_get_optimized_image_url($desktop_url, 'webp');
			if ($desktop_webp !== $desktop_url) {
				echo '<link rel="preload" href="' . esc_url($desktop_webp) . '" as="image" media="(min-width: 769px)" fetchpriority="high">';
			}

			$mobile_webp = primefit_get_optimized_image_url($mobile_url, 'webp');
			if ($mobile_webp !== $mobile_url) {
				echo '<link rel="preload" href="' . esc_url($mobile_webp) . '" as="image" media="(max-width: 768px)" fetchpriority="high">';
			}
		}
	}
	
    // Preload critical CSS with version so browser reuses the preload
    echo '<link rel="preload" href="' . PRIMEFIT_THEME_URI . '/assets/css/app.css?ver=' . primefit_get_file_version( '/assets/css/app.css' ) . '" as="style" fetchpriority="high">';
    echo '<link rel="preload" href="' . PRIMEFIT_THEME_URI . '/assets/css/header.css?ver=' . primefit_get_file_version( '/assets/css/header.css' ) . '" as="style" fetchpriority="high">';
	
	// Only preload JavaScript that loads in head and is used immediately
	// Remove preloads for deferred scripts to prevent "not used" warnings
}

/**
 * Inline critical CSS for faster first paint
 */
function primefit_inline_critical_css() {
	$critical_css = "
	<style>
	/* Critical CSS for immediate rendering - optimized for LCP */
	* {
		font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
		font-display: swap;
	}
	body {
		font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
		margin: 0;
		background: #0d0d0d;
		color: #fff;
	}
	h1, h2, h3, h4, h5, h6 {
		font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
		font-display: swap;
	}
	
	/* Navigation critical hides to prevent menu flash before header.css loads */
	.menu--primary .sub-menu,
	.menu--secondary .sub-menu { display: none; }
	/* Mega menu should be hidden by default */
	.mega-menu { opacity: 0; visibility: hidden; transform: translateY(-10px); }
	/* Mobile nav elements hidden by default */
	.mobile-nav-wrap { visibility: hidden; opacity: 0; }
	.mobile-nav-overlay { opacity: 0; visibility: hidden; }
	.mobile-nav-panel { transform: translateX(-100%); }
	/* Ensure open state works even if header.css hasn't applied yet */
	.mobile-open .mobile-nav-wrap { visibility: visible; opacity: 1; }
	.mobile-open .mobile-nav-panel { transform: translateX(0); }
	
	/* Header critical styles */
	.header { position: relative; z-index: 100; background: #0d0d0d; }
	.header__container { max-width: 1850px; margin: 0 auto; padding: 0 1rem; }
	
	/* Hero critical styles for LCP */
	.hero { position: relative; min-height: 70vh; display: flex; align-items: center; background: #0d0d0d; }
	.hero-image { width: 100%; height: auto; object-fit: cover; }
	.hero-content { position: absolute; z-index: 2; width: 100%; }
	.hero-heading { font-size: 3rem; font-weight: 700; margin: 0 0 1rem; color: white; }
	.hero-subheading { font-size: 1.2rem; margin: 0 0 2rem; color: white; opacity: 0.9; }
	
	/* Layout critical styles */
	.section-header { text-align: center; margin-bottom: 3rem; }
	.container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
	.button { display: inline-block; padding: 0.75rem 1.5rem; border: none; text-decoration: none; cursor: pointer; font-weight: 600; }
	.button--primary { background: #000; color: white; }
	
	/* Product grid critical styles */
	.product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; }
	.product-card { background: #0d0d0d; border: 1px solid #7c7c7c; }
	</style>
	";

	echo $critical_css;
}

/**
 * Add resource hints for performance optimization
 */
add_action( 'wp_head', 'primefit_add_resource_hints', 1 );
function primefit_add_resource_hints() {
	// DNS prefetch for external resources
	echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
	echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">';
	echo '<link rel="dns-prefetch" href="//newprime.swissdigital.io">';

	// Preconnect to critical external resources for faster loading
	echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
	echo '<link rel="preconnect" href="https://newprime.swissdigital.io" crossorigin>';

	// Remove duplicate preloads - these are handled by primefit_prioritize_critical_resources()
	// This prevents "preloaded but not used" warnings
}

/**
 * Add HTTP cache headers for static assets and HTML pages
 */
add_action( 'send_headers', 'primefit_add_cache_headers', 999 );
function primefit_add_cache_headers() {
	if ( is_admin() || is_user_logged_in() ) {
		// Don't cache for admin or logged-in users
		return;
	}

	// Get the request URI
	$request_uri = $_SERVER['REQUEST_URI'];

	// Define cache durations (in seconds) - Optimized for better performance
	$cache_durations = array(
		'.css' => 31536000, // 1 year for CSS files
		'.js' => 31536000,  // 1 year for JS files
		'.woff2' => 31536000, // 1 year for fonts
		'.woff' => 31536000,  // 1 year for fonts
		'.jpg' => 2592000,     // 30 days for images (increased from 1 day)
		'.jpeg' => 2592000,    // 30 days for images (increased from 1 day)
		'.png' => 2592000,     // 30 days for images (increased from 1 day)
		'.gif' => 2592000,     // 30 days for images (increased from 1 day)
		'.webp' => 2592000,    // 30 days for images (increased from 1 day)
		'.svg' => 2592000,     // 30 days for images (increased from 1 day)
	);

	// Check if the request is for a static asset
	foreach ( $cache_durations as $extension => $duration ) {
		if ( substr( $request_uri, -strlen( $extension ) ) === $extension ) {
			// Set cache headers for static assets
			header( 'Cache-Control: public, max-age=' . $duration . ', immutable' );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $duration ) . ' GMT' );
			header( 'Pragma: cache' );

			// Set ETag for better caching
			$etag = '"' . md5( $request_uri . filemtime( ABSPATH . $request_uri ) ) . '"';
			header( 'ETag: ' . $etag );

			// Set Last-Modified header
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', filemtime( ABSPATH . $request_uri ) ) . ' GMT' );

			// Handle conditional requests
			if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
				$if_modified_since = strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
				$file_modified_time = filemtime( ABSPATH . $request_uri );
				if ( $if_modified_since >= $file_modified_time ) {
					header( 'HTTP/1.1 304 Not Modified' );
					exit;
				}
			}

			return;
		}
	}

	// Handle HTML pages with optimized cache duration
	if ( is_front_page() || is_home() || is_page() || is_single() ) {
		// Increased cache duration for better performance (30 minutes instead of 15)
		// This works well with our product loop caching (15 minutes)
		$cache_duration = 1800;

		header( 'Cache-Control: public, max-age=' . $cache_duration );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $cache_duration ) . ' GMT' );
		header( 'Pragma: cache' );

		// Add Vary header to ensure proper caching for different user agents
		header( 'Vary: Accept-Encoding, User-Agent' );
	}
}

/**
 * Add cache headers for WooCommerce product images
 */
add_filter( 'wp_get_attachment_image_attributes', 'primefit_optimize_product_image_attributes', 10, 3 );
function primefit_optimize_product_image_attributes( $attr, $attachment, $size ) {
	// Add cache headers for product images
	if ( ! is_admin() && ! is_user_logged_in() ) {
		// Only for product pages to avoid affecting other images
		if ( is_product() ) {
			$attr['loading'] = 'lazy';
			$attr['decoding'] = 'async';
			$attr['fetchpriority'] = 'high'; // Prioritize product images

			// Add cache control for images served through WordPress
			add_filter( 'wp_headers', function( $headers, $wp_query ) use ( $attr ) {
				if ( ! empty( $headers['Content-Type'] ) && strpos( $headers['Content-Type'], 'image/' ) === 0 ) {
					$headers['Cache-Control'] = 'public, max-age=86400, immutable';
					$headers['Expires'] = gmdate( 'D, d M Y H:i:s', time() + 86400 ) . ' GMT';
				}
				return $headers;
			}, 10, 2 );
		}
	}

	return $attr;
}

/**
 * Preload critical resources for single product pages
 */
add_action( 'wp_head', 'primefit_preload_product_resources', 1 );
function primefit_preload_product_resources() {
	if ( ! is_product() || is_admin() ) {
		return;
	}

	global $product;

	// If global product is not set or invalid, try to get it properly
	if ( ! $product || ! is_object( $product ) ) {
		$product_id = get_the_ID();
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
		}
	}

	// Final check to ensure we have a valid product object
	if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
		return;
	}

	$product_id = $product->get_id();
	$gallery_ids = $product->get_gallery_image_ids();

	// Preload the main product image
	if ( has_post_thumbnail( $product_id ) ) {
		$main_image_id = get_post_thumbnail_id( $product_id );
		$main_image_src = wp_get_attachment_image_url( $main_image_id, 'woocommerce_single' );

		if ( $main_image_src ) {
			echo '<link rel="preload" href="' . esc_url( $main_image_src ) . '" as="image" fetchpriority="high">';
		}
	}

	// Preload first few gallery images
	$preload_count = 0;
	foreach ( $gallery_ids as $gallery_id ) {
		if ( $preload_count >= 3 ) break; // Limit to 3 gallery images

		$gallery_src = wp_get_attachment_image_url( $gallery_id, 'woocommerce_single' );
		if ( $gallery_src ) {
			echo '<link rel="preload" href="' . esc_url( $gallery_src ) . '" as="image">';
			$preload_count++;
		}
	}

	// Preload variation images if available
	if ( $product->is_type( 'variable' ) ) {
		$variations = $product->get_available_variations();

		foreach ( $variations as $variation ) {
			if ( isset( $variation['image_id'] ) && $variation['image_id'] ) {
				$variation_src = wp_get_attachment_image_url( $variation['image_id'], 'woocommerce_single' );
				if ( $variation_src ) {
					echo '<link rel="preload" href="' . esc_url( $variation_src ) . '" as="image">';
				}
				break; // Only preload first variation image
			}
		}
	}

    // Preload critical CSS files specifically for product pages (include version)
    echo '<link rel="preload" href="' . PRIMEFIT_THEME_URI . '/assets/css/single-product.css?ver=' . primefit_get_file_version( '/assets/css/single-product.css' ) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
	// Note: JS preload is handled by primefit_add_critical_preloads() function

	// Add resource hints for external dependencies
	echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
	echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">';
	echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';

	// Preconnect for Figtree font
	echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
}

/**
 * Add preconnect hints for external resources used on product pages
 */
add_action( 'wp_head', 'primefit_add_product_preconnect_hints', 1 );
function primefit_add_product_preconnect_hints() {
	if ( ! is_product() ) {
		return;
	}

	// Preconnect to critical external domains for Figtree font
	$preconnect_domains = [
		'https://fonts.googleapis.com',
		'https://fonts.gstatic.com',
	];

	foreach ( $preconnect_domains as $domain ) {
		echo '<link rel="preconnect" href="' . esc_url( $domain ) . '" crossorigin>';
	}
}

/**
 * Defer non-critical JavaScript files for better performance
 */
add_action( 'wp_enqueue_scripts', 'primefit_defer_non_critical_scripts', 999 );
function primefit_defer_non_critical_scripts() {
	// Get all registered scripts
	global $wp_scripts;

	$scripts_to_defer = array(
		'primefit-product',
		'primefit-single-product',
		'primefit-checkout',
		'primefit-account',
		'primefit-payment-summary',
		'primefit-cart',
		'primefit-shop',
		'primefit-mega-menu',
		'primefit-hero-video'
	);

	foreach ( $scripts_to_defer as $script_handle ) {
		if ( isset( $wp_scripts->registered[ $script_handle ] ) ) {
			$script = $wp_scripts->registered[ $script_handle ];
			// Add defer attribute for better performance
			$script->extra['defer'] = true;
		}
	}
}

/**
 * Optimize script loading order
 */
add_action( 'wp_enqueue_scripts', 'primefit_optimize_script_loading', 998 );
function primefit_optimize_script_loading() {
	// Ensure critical scripts load first in correct order
	wp_enqueue_script( 'jquery-core' );
	wp_enqueue_script( 'jquery-migrate' );

	// Load our critical scripts first (core then app)
	if ( wp_script_is( 'primefit-core', 'registered' ) ) {
		wp_enqueue_script( 'primefit-core' );
	}
	if ( wp_script_is( 'primefit-app', 'registered' ) ) {
		wp_enqueue_script( 'primefit-app' );
	}

	// Load WooCommerce scripts in optimal order
	if ( class_exists( 'WooCommerce' ) ) {
		// Load cart fragments early for better UX
		wp_enqueue_script( 'wc-cart-fragments' );

		// Load add to cart scripts only when needed using cached page type
		$page_type = primefit_get_page_type();
		if ( in_array( $page_type, [ 'product', 'shop', 'category', 'tag' ] ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}
	}
}

/**
 * Cache product data in browser localStorage to reduce AJAX calls
 */
add_action( 'wp_footer', 'primefit_add_browser_cache_script', 999 );
function primefit_add_browser_cache_script() {
	if ( is_product() && class_exists( 'WooCommerce' ) ) {
		global $product;

		// If global product is not set or invalid, try to get it properly
		if ( ! $product || ! is_object( $product ) ) {
			$product_id = get_the_ID();
			if ( $product_id ) {
				$product = wc_get_product( $product_id );
			}
		}

		// Final check to ensure we have a valid product object
		if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return;
		}

		if ( $product->is_type( 'variable' ) ) {
			$product_id = $product->get_id();
			$variations = $product->get_available_variations();

			// Cache variations data in browser localStorage
			$cache_data = wp_json_encode( array(
				'product_id' => $product_id,
				'variations' => $variations,
				'timestamp' => time(),
				'expires' => time() + 3600 // 1 hour
			) );
			?>
			<script>
			(function() {
				'use strict';

				// Browser-side caching for product variations
				const cacheKey = 'primefit_product_<?php echo $product_id; ?>';
				const cacheData = <?php echo $cache_data; ?>;

				// Store in localStorage if supported
				if (typeof(Storage) !== 'undefined') {
					try {
						localStorage.setItem(cacheKey, JSON.stringify(cacheData));

						// Set up cleanup for expired cache
						window.addEventListener('beforeunload', function() {
							const cached = JSON.parse(localStorage.getItem(cacheKey) || '{}');
							if (cached.expires && cached.expires < Date.now() / 1000) {
								localStorage.removeItem(cacheKey);
							}
						});
					} catch (e) {
						// localStorage might be full or disabled
					}
				}

				// Make cached data available to JavaScript
				window.primefitBrowserCache = window.primefitBrowserCache || {};
				window.primefitBrowserCache[cacheKey] = cacheData;
			})();
			</script>
			<?php
		}
	}
}

/**
 * Add cache-busting parameters to static assets
 */
add_filter( 'style_loader_src', 'primefit_cache_busting_styles', 10, 2 );
add_filter( 'script_loader_src', 'primefit_cache_busting_scripts', 10, 2 );

function primefit_cache_busting_styles( $src, $handle ) {
	return primefit_add_cache_busting( $src, $handle, 'css' );
}

function primefit_cache_busting_scripts( $src, $handle ) {
	return primefit_add_cache_busting( $src, $handle, 'js' );
}

function primefit_add_cache_busting( $src, $handle, $type ) {
	// Only add cache busting for our theme assets
	if ( strpos( $src, PRIMEFIT_THEME_URI ) === false ) {
		return $src;
	}

	// Generate cache-busting parameter based on file modification time
	$file_path = str_replace( PRIMEFIT_THEME_URI, PRIMEFIT_THEME_DIR, $src );
	$file_path = str_replace( '?' . parse_url( $src, PHP_URL_QUERY ), '', $file_path );

	if ( file_exists( $file_path ) ) {
		$version = filemtime( $file_path );
		$src = add_query_arg( 'ver', $version, $src );
	}

	return $src;
}

/**
 * Load non-critical CSS asynchronously to reduce critical path
 * This function is now handled by primefit_optimize_css_loading()
 */

/**
 * Optimize WooCommerce cart fragments loading
 */
add_action( 'wp_enqueue_scripts', 'primefit_optimize_cart_fragments', 999 );
function primefit_optimize_cart_fragments() {
	if ( class_exists( 'WooCommerce' ) ) {
		$page_type = primefit_get_page_type();
		
		// Only load cart fragments on pages that actually need them
		if ( in_array( $page_type, [ 'product', 'shop', 'category', 'tag', 'cart', 'checkout', 'front_page' ] ) ) {
			// Defer cart fragments to reduce critical path
			wp_script_add_data( 'wc-cart-fragments', 'defer', true );
			
			// Add preload for cart fragments to improve perceived performance
			add_action( 'wp_head', function() {
				echo '<link rel="preload" href="' . admin_url( 'admin-ajax.php' ) . '?action=woocommerce_get_refreshed_fragments" as="fetch" crossorigin>';
			}, 1 );
		}
	}
}

/**
 * Optimize CSS loading to reduce critical path
 */
add_action( 'wp_enqueue_scripts', 'primefit_optimize_css_loading', 999 );
function primefit_optimize_css_loading() {
	global $wp_styles;
	
	// Critical CSS files that should load immediately
	$critical_css = [
		'primefit-style',
		'primefit-app',
		'primefit-header'
	];
	
	// Non-critical CSS files that can be loaded asynchronously
	$non_critical_css = [
		'primefit-cart',
		'primefit-footer',
		'primefit-woocommerce',
		'primefit-single-product',
		'primefit-checkout',
		'primefit-account',
		'primefit-payment-summary'
	];
	
	// Make non-critical CSS load asynchronously
	foreach ( $non_critical_css as $style_handle ) {
		if ( isset( $wp_styles->registered[ $style_handle ] ) ) {
			$style = $wp_styles->registered[ $style_handle ];
			// Add async loading for non-critical CSS
			$style->extra['onload'] = "this.onload=null;this.rel='stylesheet'";
			$style->extra['rel'] = 'preload';
			$style->extra['as'] = 'style';
		}
	}
}

/**
 * Add preload hints for critical resources
 */
add_action( 'wp_head', 'primefit_add_critical_preloads', 1 );
function primefit_add_critical_preloads() {
	$page_type = primefit_get_page_type();
	
	// Only preload scripts that are actually executed immediately
	// Remove preloads for deferred scripts to prevent "not used" warnings
	
	// Page-specific preloads - only for scripts that are used immediately
	if ( $page_type === 'product' ) {
		// Only preload if these scripts are loaded in head (not deferred)
		if ( ! wp_script_is( 'primefit-single-product', 'enqueued' ) || 
			 ! wp_script_is( 'primefit-product', 'enqueued' ) ) {
			echo '<link rel="preload" href="' . PRIMEFIT_THEME_URI . '/assets/js/single-product.js" as="script">';
			echo '<link rel="preload" href="' . PRIMEFIT_THEME_URI . '/assets/js/product.js" as="script">';
		}
	}
	
	// Don't preload deferred scripts - let them load naturally
	// This prevents the "preloaded but not used" warnings
}

/**
 * Optimize script loading order to reduce dependency chains
 */
add_action( 'wp_enqueue_scripts', 'primefit_optimize_script_dependencies', 998 );
function primefit_optimize_script_dependencies() {
	global $wp_scripts;
	
	// Scripts that should load independently to break chains
	$independent_scripts = [
		'primefit-app' => [ 'jquery' ],
		'primefit-cart' => [ 'jquery' ],
		'primefit-shop' => [ 'jquery' ],
		'primefit-mega-menu' => [ 'jquery' ],
		'primefit-hero-video' => [ 'jquery' ]
	];
	
	// Update dependencies to break chains
	foreach ( $independent_scripts as $handle => $dependencies ) {
		if ( isset( $wp_scripts->registered[ $handle ] ) ) {
			$wp_scripts->registered[ $handle ]->deps = $dependencies;
		}
	}
}

/**
 * Add module loading for modern browsers
 */
add_action( 'wp_enqueue_scripts', 'primefit_add_module_loading', 999 );
function primefit_add_module_loading() {
	// Add type="module" to modern scripts for better performance
	$modern_scripts = [
		'primefit-app',
		'primefit-core',
		'primefit-cart',
		'primefit-shop'
	];
	
	foreach ( $modern_scripts as $script_handle ) {
		if ( wp_script_is( $script_handle, 'enqueued' ) ) {
			wp_script_add_data( $script_handle, 'type', 'module' );
		}
	}
}

/**
 * Register Service Worker for mobile caching
 */
add_action( 'wp_footer', 'primefit_register_service_worker', 999 );
function primefit_register_service_worker() {
	// Only register on frontend and for mobile devices
	if ( is_admin() || ! wp_is_mobile() ) {
		return;
	}
	
	$sw_url = PRIMEFIT_THEME_URI . '/sw.js';
	?>
	<script>
	(function() {
		'use strict';
		
		// Check if service workers are supported
		if ('serviceWorker' in navigator) {
			// Register service worker
			navigator.serviceWorker.register('<?php echo esc_url( $sw_url ); ?>', {
				scope: '/'
			}).then(function(registration) {

				// Handle updates
				registration.addEventListener('updatefound', function() {
					const newWorker = registration.installing;
					newWorker.addEventListener('statechange', function() {
						if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
							// New content is available, notify user
							if (confirm('New version available! Reload to update?')) {
								window.location.reload();
							}
						}
					});
				});

			}).catch(function(error) {
			});

			// Handle service worker messages
			navigator.serviceWorker.addEventListener('message', function(event) {
				if (event.data && event.data.type === 'CACHE_UPDATED') {
				}
			});

			// Handle offline/online events
			window.addEventListener('online', function() {
				// Notify service worker that we're back online
				if (navigator.serviceWorker.controller) {
					navigator.serviceWorker.controller.postMessage({
						type: 'ONLINE'
					});
				}
			});

			window.addEventListener('offline', function() {
			});
		}
	})();
	</script>
	<?php
}

/**
 * Lazy load non-critical JavaScript modules for mobile performance
 * Ensures critical functionality (cart, mobile menu) remains intact
 */
function primefit_lazy_load_js_modules() {
	// Only apply lazy loading on mobile devices for performance
	if ( ! wp_is_mobile() ) {
		return;
	}
	
	$page_type = primefit_get_page_type();
	
	// Define modules that can be safely lazy loaded
	$lazy_modules = [
		'primefit-hero-video' => [
			'condition' => $page_type === 'front_page' || is_page_template( 'page-hero.php' ),
			'load_on' => 'scroll', // Load when user scrolls
			'priority' => 'low'
		],
		'primefit-shop' => [
			'condition' => in_array( $page_type, [ 'shop', 'category', 'tag', 'front_page' ] ),
			'load_on' => 'interaction', // Load on first user interaction
			'priority' => 'medium'
		]
	];
	
	// Critical modules that must NOT be lazy loaded
	$critical_modules = [
		'primefit-core',      // Cart management, scroll utilities
		'primefit-app',       // Essential initialization
		'primefit-cart',      // Cart functionality
		'wc-cart-fragments',  // WooCommerce cart
		'wc-add-to-cart',     // Add to cart functionality
		'wc-add-to-cart-variation' // Product variations
	];
	
	// Add lazy loading script to footer
	add_action( 'wp_footer', function() use ( $lazy_modules, $critical_modules ) {
		?>
		<script>
		(function() {
			'use strict';
			
			// Lazy loading configuration
			const lazyConfig = {
				modules: <?php echo json_encode( $lazy_modules ); ?>,
				critical: <?php echo json_encode( $critical_modules ); ?>,
				loaded: new Set(),
				loading: new Set()
			};
			
			// Load script dynamically
			function loadScript(src, callback) {
				if (lazyConfig.loaded.has(src) || lazyConfig.loading.has(src)) {
					if (callback) callback();
					return;
				}
				
				lazyConfig.loading.add(src);
				const script = document.createElement('script');
				script.src = src;
				script.async = true;
				
				script.onload = function() {
					lazyConfig.loaded.add(src);
					lazyConfig.loading.delete(src);
					if (callback) callback();
				};
				
				script.onerror = function() {
					lazyConfig.loading.delete(src);
				};
				
				document.head.appendChild(script);
			}
			
			// Load module based on trigger
			function loadModule(moduleName, config) {
				// Get the correct script URL
				const baseUrl = '<?php echo PRIMEFIT_THEME_URI; ?>';
				const scriptSrc = baseUrl + '/assets/js/' + moduleName.replace('primefit-', '') + '.js';
				
				loadScript(scriptSrc, function() {
				});
			}
			
			// Scroll-based loading
			let scrollLoaded = false;
			function handleScroll() {
				if (scrollLoaded) return;
				
				const scrollY = window.scrollY || document.documentElement.scrollTop;
				if (scrollY > 100) { // Load after 100px scroll
					scrollLoaded = true;
					
					// Load scroll-triggered modules
					Object.entries(lazyConfig.modules).forEach(([moduleName, config]) => {
						if (config.load_on === 'scroll' && config.condition) {
							loadModule(moduleName, config);
						}
					});
					
					window.removeEventListener('scroll', handleScroll);
				}
			}
			
			// Hover-based loading
			function handleHover() {
				Object.entries(lazyConfig.modules).forEach(([moduleName, config]) => {
					if (config.load_on === 'hover' && config.condition) {
						const triggerElement = document.querySelector('.menu--primary, .hamburger, .mega-menu');
						if (triggerElement) {
							triggerElement.addEventListener('mouseenter', function() {
								loadModule(moduleName, config);
							}, { once: true });
						}
					}
				});
			}
			
			// Interaction-based loading
			function handleInteraction() {
				let interactionLoaded = false;
				
				function loadOnInteraction() {
					if (interactionLoaded) return;
					interactionLoaded = true;
					
					Object.entries(lazyConfig.modules).forEach(([moduleName, config]) => {
						if (config.load_on === 'interaction' && config.condition) {
							loadModule(moduleName, config);
						}
					});
				}
				
				// Load on first user interaction
				['click', 'touchstart', 'keydown'].forEach(eventType => {
					document.addEventListener(eventType, loadOnInteraction, { once: true, passive: true });
				});
			}
			
			// Initialize lazy loading
			function initLazyLoading() {
				// Set up scroll-based loading
				window.addEventListener('scroll', handleScroll, { passive: true });
				
				// Set up hover-based loading
				handleHover();
				
				// Set up interaction-based loading
				handleInteraction();
				
				// Load high-priority modules after a short delay
				setTimeout(function() {
					Object.entries(lazyConfig.modules).forEach(([moduleName, config]) => {
						if (config.priority === 'high' && config.condition) {
							loadModule(moduleName, config);
						}
					});
				}, 1000);
			}
			
			// Start lazy loading when DOM is ready
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', initLazyLoading);
			} else {
				initLazyLoading();
			}
			
			// Expose lazy loading API for debugging
			window.primefitLazyLoader = {
				loadModule: loadModule,
				loaded: lazyConfig.loaded,
				loading: lazyConfig.loading
			};
			
		})();
		</script>
		<?php
	}, 999 );
}

/**
 * Optimize CSS delivery with media queries for mobile performance
 * Implements mobile-first loading strategy
 */
function primefit_optimize_css_delivery() {
	// Only apply on frontend
	if ( is_admin() ) {
		return;
	}
	
	// Add CSS loading optimization script
	add_action( 'wp_footer', function() {
		?>
		<script>
		(function() {
			'use strict';
			
			// CSS loading optimization for mobile performance
			function optimizeCSSLoading() {
				// Get all stylesheets with media="print"
				const printStyles = document.querySelectorAll('link[rel="stylesheet"][media="print"]');
				
				// Convert print styles to all media after page load
				printStyles.forEach(function(link) {
					// Create new link element with media="all"
					const newLink = document.createElement('link');
					newLink.rel = 'stylesheet';
					newLink.href = link.href;
					newLink.media = 'all';
					
					// Insert after the print link
					link.parentNode.insertBefore(newLink, link.nextSibling);
					
					// Remove the print link
					link.remove();
				});
			}
			
			// Optimize CSS loading when DOM is ready
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', optimizeCSSLoading);
			} else {
				optimizeCSSLoading();
			}
			
			// Additional optimization: Load non-critical CSS asynchronously
			function loadNonCriticalCSS() {
				const nonCriticalCSS = [
					'<?php echo PRIMEFIT_THEME_URI; ?>/assets/css/payment-summary.css'
				];
				
				nonCriticalCSS.forEach(function(cssUrl) {
					const link = document.createElement('link');
					link.rel = 'preload';
					link.href = cssUrl;
					link.as = 'style';
					link.onload = function() {
						this.onload = null;
						this.rel = 'stylesheet';
					};
					document.head.appendChild(link);
				});
			}
			
			// Load non-critical CSS after page load
			window.addEventListener('load', loadNonCriticalCSS);
			
		})();
		</script>
		<?php
	}, 998 );
}
