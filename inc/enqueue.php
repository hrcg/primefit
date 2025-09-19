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
	// Google Fonts - Figtree
	wp_enqueue_style( 
		'primefit-fonts', 
		'https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800;900&display=swap', 
		[], 
		null 
	);
	
	// Theme styles
	wp_enqueue_style( 
		'primefit-style', 
		get_stylesheet_uri(), 
		[ 'primefit-fonts' ], 
		PRIMEFIT_VERSION 
	);
	
	wp_enqueue_style( 
		'primefit-app', 
		PRIMEFIT_THEME_URI . '/assets/css/app.css', 
		[ 'primefit-fonts' ], 
		primefit_get_file_version( '/assets/css/app.css' )
	);

	// Header-specific styles
	wp_enqueue_style(
		'primefit-header',
		PRIMEFIT_THEME_URI . '/assets/css/header.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/header.css' )
	);

	// Cart-specific styles
	wp_enqueue_style(
		'primefit-cart',
		PRIMEFIT_THEME_URI . '/assets/css/cart.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/cart.css' )
	);
	
	// Footer-specific styles
	wp_enqueue_style(
		'primefit-footer',
		PRIMEFIT_THEME_URI . '/assets/css/footer.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/footer.css' )
	);
	// WooCommerce styles
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style( 
			'primefit-woocommerce', 
			PRIMEFIT_THEME_URI . '/assets/css/woocommerce.css', 
			[ 'primefit-app' ], 
			primefit_get_file_version( '/assets/css/woocommerce.css' )
		);
		
		// Single product page styles
		if ( is_product() ) {
			wp_enqueue_style( 
				'primefit-single-product', 
				PRIMEFIT_THEME_URI . '/assets/css/single-product.css', 
				[ 'primefit-woocommerce' ], 
				primefit_get_file_version( '/assets/css/single-product.css' )
			);
		}
		
		// Checkout page styles
		if ( is_checkout() ) {
			wp_enqueue_style( 
				'primefit-checkout', 
				PRIMEFIT_THEME_URI . '/assets/css/checkout.css', 
				[ 'primefit-woocommerce' ], 
				primefit_get_file_version( '/assets/css/checkout.css' )
			);
		}
		
		// Account page styles
		if ( is_account_page() ) {
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
	
	// WooCommerce scripts and cart nonces
	if ( class_exists( 'WooCommerce' ) ) {
		// Ensure WooCommerce cart fragments script is loaded
		wp_enqueue_script( 'wc-cart-fragments' );
		
		// Ensure WooCommerce add to cart script is loaded for AJAX functionality
		wp_enqueue_script( 'wc-add-to-cart' );
		
		// Ensure WooCommerce add to cart variation script is loaded for variable products
		wp_enqueue_script( 'wc-add-to-cart-variation' );
		
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
add_action( 'wp_enqueue_scripts', 'primefit_enqueue_product_scripts' );
function primefit_enqueue_product_scripts() {
	// Load on single product pages, shop pages, and pages with WooCommerce content
	if ( is_product() || is_shop() || is_product_category() || is_product_tag() || is_front_page() || (function_exists('wc_get_page_id') && (is_page(wc_get_page_id('shop')) || is_page(wc_get_page_id('cart')) || is_page(wc_get_page_id('checkout')))) ) {
		wp_enqueue_script( 
			'primefit-product', 
			PRIMEFIT_THEME_URI . '/assets/js/product.js', 
			[ 'jquery' ], 
			primefit_get_file_version( '/assets/js/product.js' ), 
			true 
		);
		
		// Single product page specific scripts
		if ( is_product() ) {
			wp_enqueue_script( 
				'primefit-single-product', 
				PRIMEFIT_THEME_URI . '/assets/js/single-product.js', 
				[ 'jquery', 'primefit-product' ], 
				primefit_get_file_version( '/assets/js/single-product.js' ), 
				true 
			);
		}
		
		// Checkout page specific scripts
		if ( is_checkout() ) {
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
	// Preload hero image on homepage
	if ( is_front_page() ) {
		$hero_config = primefit_get_hero_config();
		if ( ! empty( $hero_config['image'][0] ) ) {
			echo '<link rel="preload" href="' . esc_url( $hero_config['image'][0] ) . '" as="image">';
		}
	}
}

/**
 * Add resource hints for performance optimization
 */
add_action( 'wp_head', 'primefit_add_resource_hints', 1 );
function primefit_add_resource_hints() {
	// DNS prefetch for external resources
	echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
	echo '<link rel="dns-prefetch" href="//www.google-analytics.com">';
	
	// Preconnect to critical external resources for faster font loading
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
}
