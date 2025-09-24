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
	// Google Fonts - Figtree with optimized loading
	wp_enqueue_style(
		'primefit-fonts',
		'https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700&display=swap',
		[],
		null,
		'all'
	);
	
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

	// Header-specific styles - Load early for above-the-fold content
	wp_enqueue_style(
		'primefit-header',
		PRIMEFIT_THEME_URI . '/assets/css/header.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/header.css' )
	);

	// Cart-specific styles - defer loading
	wp_enqueue_style(
		'primefit-cart',
		PRIMEFIT_THEME_URI . '/assets/css/cart.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/cart.css' ),
		'screen and (min-width: 769px)'
	);

	// Footer-specific styles - defer loading
	wp_enqueue_style(
		'primefit-footer',
		PRIMEFIT_THEME_URI . '/assets/css/footer.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/footer.css' ),
		'screen and (min-width: 769px)'
	);
	// WooCommerce styles - defer non-critical styles
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'primefit-woocommerce',
			PRIMEFIT_THEME_URI . '/assets/css/woocommerce.css',
			[ 'primefit-app' ],
			primefit_get_file_version( '/assets/css/woocommerce.css' ),
			'screen and (min-width: 769px)'
		);
		
		// Cache page type for optimized CSS loading
		$page_type = primefit_get_page_type();
		
		// Single product page styles with critical CSS optimization
		if ( $page_type === 'product' ) {
			$single_product_css_url = PRIMEFIT_THEME_URI . '/assets/css/single-product.css';

			wp_enqueue_style(
				'primefit-single-product',
				$single_product_css_url,
				[ 'primefit-woocommerce' ],
				primefit_get_file_version( '/assets/css/single-product.css' )
			);

			// Preload critical single product CSS for faster rendering
			add_action( 'wp_head', function() use ( $single_product_css_url ) {
				echo '<link rel="preload" href="' . esc_url( $single_product_css_url ) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
				echo '<noscript><link rel="stylesheet" href="' . esc_url( $single_product_css_url ) . '"></noscript>';
			}, 1 );
		}
		
		// Checkout page styles
		if ( $page_type === 'checkout' ) {
			wp_enqueue_style( 
				'primefit-checkout', 
				PRIMEFIT_THEME_URI . '/assets/css/checkout.css', 
				[ 'primefit-woocommerce' ], 
				primefit_get_file_version( '/assets/css/checkout.css' )
			);
		}
		
		// Account page styles
		if ( $page_type === 'account' || is_account_page() ) {
			wp_enqueue_style( 
				'primefit-account', 
				PRIMEFIT_THEME_URI . '/assets/css/account.css', 
				[ 'primefit-woocommerce' ], 
				primefit_get_file_version( '/assets/css/account.css' )
			);
			
			wp_enqueue_script( 
				'primefit-account', 
				PRIMEFIT_THEME_URI . '/assets/js/account.js', 
				[ 'jquery' ], 
				primefit_get_file_version( '/assets/js/account.js' ), 
				true 
			);
		}
		
		// Payment summary styles - Load on account pages, checkout pages, and order received pages
		$load_payment_summary = false;
		
		// Check for account pages
		if ( is_account_page() ) {
			$load_payment_summary = true;
		}
		
		// Check for checkout pages (including order received)
		if ( is_checkout() ) {
			$load_payment_summary = true;
		}
		
		// Check for specific WooCommerce endpoints
		if ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'payment-summary' ) ) {
			$load_payment_summary = true;
		}
		
		// Check if we're on the order received page by checking for order key parameter
		if ( isset( $_GET['key'] ) && isset( $_GET['order'] ) ) {
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
	
	// Theme scripts
	wp_enqueue_script( 
		'primefit-app', 
		PRIMEFIT_THEME_URI . '/assets/js/app.js', 
		[ 'jquery' ], 
		primefit_get_file_version( '/assets/js/app.js' ), 
		true 
	);
	
	// Pass data to JavaScript
	wp_localize_script( 'primefit-app', 'primefitData', [
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

		// Only load cart fragments on pages that need it
		if ( in_array( $page_type, [ 'product', 'shop', 'category', 'tag', 'cart', 'checkout', 'account', 'front_page' ] ) ) {
			wp_enqueue_script( 'wc-cart-fragments' );
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
	
	// Dashicons for admin functionality
	wp_enqueue_style( 'dashicons' );
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
			add_action( 'wp_head', function() {
				echo '<link rel="preload" href="' . PRIMEFIT_THEME_URI . '/assets/js/single-product.js' . '" as="script">';
			}, 1 );
		}
		
		// Checkout page specific scripts
		if ( $page_type === 'checkout' ) {
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

/**
 * Preload important assets for performance
 */
add_action( 'wp_head', 'primefit_preload_assets', 1 );
function primefit_preload_assets() {
	// Inline critical CSS for immediate rendering
	if ( is_front_page() ) {
		primefit_inline_critical_css();
	}

	// Preload hero image on homepage with modern formats
	if ( is_front_page() ) {
		$hero_config = primefit_get_hero_config();
		if ( ! empty( $hero_config['image_desktop'] ) ) {
			$desktop_url = $hero_config['image_desktop'];
			$mobile_url = $hero_config['image_mobile'] ?? $desktop_url;

			// Fallback preload for WebP
			$webp_desktop = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $desktop_url);
			$webp_mobile = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $mobile_url);

			echo '<link rel="preload" href="' . esc_url( $webp_desktop ) . '" as="image" media="(min-width: 769px)" fetchpriority="high">';
			echo '<link rel="preload" href="' . esc_url( $webp_mobile ) . '" as="image" media="(max-width: 768px)" fetchpriority="high">';
		}
	}

	// Preload critical fonts with optimized display=swap
	$font_url = 'https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700&display=swap';
	echo '<link rel="preload" href="' . esc_url($font_url) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
	echo '<link rel="preload" href="https://fonts.gstatic.com/s/figtree/v5/wf.woff2" as="font" type="font/woff2" crossorigin>';
	echo '<noscript><link rel="stylesheet" href="' . esc_url($font_url) . '"></noscript>';
}

/**
 * Inline critical CSS for faster first paint
 */
function primefit_inline_critical_css() {
	$critical_css = "
	<style>
	/* Critical CSS for immediate rendering */
	body { font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; }
	.hero { position: relative; min-height: 70vh; display: flex; align-items: center; }
	.hero-image { width: 100%; height: auto; object-fit: cover; }
	.hero-content { position: absolute; z-index: 2; width: 100%; }
	.hero-heading { font-size: 3rem; font-weight: 700; margin: 0 0 1rem; color: white; }
	.hero-subheading { font-size: 1.2rem; margin: 0 0 2rem; color: white; opacity: 0.9; }
	.product-loop { margin: 4rem 0; }
	.section-header { text-align: center; margin-bottom: 3rem; }
	.container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
	.button { display: inline-block; padding: 0.75rem 1.5rem; border: none; text-decoration: none; cursor: pointer; font-weight: 600; }
	.button--primary { background: #000; color: white; }
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

	// Preconnect to critical external resources for faster font loading
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';

	// Preload critical CSS files
	if ( is_front_page() ) {
		echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/css/app.css" as="style">';
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
		'primefit-payment-summary'
	);

	foreach ( $scripts_to_defer as $script_handle ) {
		if ( isset( $wp_scripts->registered[ $script_handle ] ) ) {
			$script = $wp_scripts->registered[ $script_handle ];
			// Only defer if not already loaded in head
			if ( ! in_array( $script_handle, array( 'primefit-app' ) ) ) {
				$script->extra['defer'] = true;
			}
		}
	}
}

/**
 * Optimize script loading order
 */
add_action( 'wp_enqueue_scripts', 'primefit_optimize_script_loading', 998 );
function primefit_optimize_script_loading() {
	// Ensure critical scripts load first
	wp_enqueue_script( 'jquery-core' );
	wp_enqueue_script( 'jquery-migrate' );

	// Load our critical app script first
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