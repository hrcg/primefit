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

	// Cart-specific styles - load for all devices
	wp_enqueue_style(
		'primefit-cart',
		PRIMEFIT_THEME_URI . '/assets/css/cart.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/cart.css' ),
		'all'
	);

	// Footer-specific styles - load for all devices
	wp_enqueue_style(
		'primefit-footer',
		PRIMEFIT_THEME_URI . '/assets/css/footer.css',
		[ 'primefit-app' ],
		primefit_get_file_version( '/assets/css/footer.css' ),
		'all'
	);
	// WooCommerce styles - load for all devices
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'primefit-woocommerce',
			PRIMEFIT_THEME_URI . '/assets/css/woocommerce.css',
			[ 'primefit-app' ],
			primefit_get_file_version( '/assets/css/woocommerce.css' ),
			'all'
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
		
		// Checkout page styles - Load ONLY on checkout pages
		if ( $page_type === 'checkout' ) {
			// Additional verification to ensure we're really on a checkout page
			$is_checkout_page = is_checkout();
			$is_custom_checkout = (function_exists('wc_get_page_id') && is_page(wc_get_page_id('checkout')));

			if ( $is_checkout_page || $is_custom_checkout ) {
				wp_enqueue_style(
					'primefit-checkout',
					PRIMEFIT_THEME_URI . '/assets/css/checkout.css',
					[ 'primefit-woocommerce' ],
					primefit_get_file_version( '/assets/css/checkout.css' )
				);
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
	echo '<noscript><link rel="stylesheet" href="' . esc_url($font_url) . '"></noscript>';
}

/**
 * Inline critical CSS for faster first paint
 */
function primefit_inline_critical_css() {
	$critical_css = "
	<style>
	/* Critical CSS for immediate rendering */
	* { font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
	body { font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; }
	h1, h2, h3, h4, h5, h6 { font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
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

	// Define cache durations (in seconds)
	$cache_durations = array(
		'.css' => 31536000, // 1 year for CSS files
		'.js' => 31536000,  // 1 year for JS files
		'.woff2' => 31536000, // 1 year for fonts
		'.woff' => 31536000,  // 1 year for fonts
		'.jpg' => 86400,     // 1 day for images
		'.jpeg' => 86400,    // 1 day for images
		'.png' => 86400,     // 1 day for images
		'.gif' => 86400,     // 1 day for images
		'.webp' => 86400,    // 1 day for images
		'.svg' => 86400,     // 1 day for images
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

	// Handle HTML pages with shorter cache duration
	if ( is_front_page() || is_home() || is_page() || is_single() ) {
		// Cache HTML pages for 15 minutes (900 seconds) for better performance
		// This works well with our product loop caching (15 minutes)
		$cache_duration = 900;

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

	// Preload critical CSS and JS files specifically for product pages
	echo '<link rel="preload" href="' . PRIMEFIT_THEME_URI . '/assets/css/single-product.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
	echo '<link rel="preload" href="' . PRIMEFIT_THEME_URI . '/assets/js/single-product.js" as="script">';

	// Add resource hints for external dependencies
	echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
	echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">';
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

	// Preconnect to critical external domains
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
						console.warn('Browser cache not available');
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