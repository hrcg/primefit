<?php
/**
 * PrimeFit Theme Functions
 *
 * Core theme functionality, hooks, and WordPress customizations
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme constants
 */
define( 'PRIMEFIT_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'PRIMEFIT_THEME_DIR', get_template_directory() );
define( 'PRIMEFIT_THEME_URI', get_template_directory_uri() );

/**
 * Completely remove dashicons from WordPress
 * This prevents dashicons from loading on any page
 */
add_action( 'wp_enqueue_scripts', 'primefit_remove_dashicons', 1 );
function primefit_remove_dashicons() {
	wp_deregister_style( 'dashicons' );
}

/**
 * Completely remove WordPress block library CSS
 * This prevents Gutenberg block styles from loading on the frontend
 */
add_action( 'wp_enqueue_scripts', 'primefit_remove_block_library_css', 1 );
function primefit_remove_block_library_css() {
	// Remove WordPress block library CSS
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' ); // WooCommerce blocks CSS
	wp_dequeue_style( 'wc-blocks-vendors-style' ); // WooCommerce blocks vendor CSS
	
	// Also deregister to prevent any plugins from re-enqueuing
	wp_deregister_style( 'wp-block-library' );
	wp_deregister_style( 'wp-block-library-theme' );
	wp_deregister_style( 'wc-blocks-style' );
	wp_deregister_style( 'wc-blocks-vendors-style' );
}

/**
 * Completely remove brands.css from WooCommerce
 * This prevents WooCommerce brands stylesheet from loading
 */
add_action( 'wp_enqueue_scripts', 'primefit_remove_brands_css', 2 );
function primefit_remove_brands_css() {
	// Comprehensive list of potential WooCommerce brand-related style handles
	$brand_styles = [
		'brands-styles', // Specific handle from the URL
		'wc-brands',
		'woocommerce-brands',
		'brands',
		'wc_brands',
		'brands-admin',
		'brands-rtl',
		'brands-admin-rtl',
		'woocommerce-brands-admin',
		'woocommerce-brands-rtl'
	];

	// Deregister all brand-related styles
	foreach ( $brand_styles as $style ) {
		if ( wp_style_is( $style, 'registered' ) ) {
			wp_deregister_style( $style );
		}
	}
}

// Additional filter to prevent block library CSS from being loaded by any plugin
add_filter( 'style_loader_tag', 'primefit_remove_block_library_css_tag', 10, 4 );
function primefit_remove_block_library_css_tag( $html, $handle, $href, $media ) {
	// Check if this is a block library CSS file from any location
	if ( strpos( $href, 'wp-block-library' ) !== false ||
		 strpos( $href, 'blocks.css' ) !== false ||
		 strpos( $handle, 'wp-block' ) !== false ||
		 strpos( $handle, 'wc-blocks' ) !== false ||
		 strpos( $href, '/blocks' ) !== false ) {
		return ''; // Return empty string to remove the tag completely
	}
	return $html;
}

// Additional filter to prevent brands.css from being loaded by any plugin
add_filter( 'style_loader_tag', 'primefit_remove_brands_css_tag', 10, 4 );
function primefit_remove_brands_css_tag( $html, $handle, $href, $media ) {
	// Check if this is a brands.css file from any location
	if ( strpos( $href, 'brands.css' ) !== false ||
		 strpos( $handle, 'brands' ) !== false ||
		 strpos( $href, '/brands' ) !== false ) {
		return ''; // Return empty string to remove the tag completely
	}
	return $html;
}

/**
 * Remove screen reader text from HTML output
 * This removes screen-reader-text classes and content from various sources
 */
add_action( 'wp_enqueue_scripts', 'primefit_remove_screen_reader_text_css', 20 );
function primefit_remove_screen_reader_text_css() {
	// Add CSS to hide screen reader text
	wp_add_inline_style( 'primefit-app', '
		.screen-reader-text,
		.sr-only,
		.visually-hidden,
		.visuallyhidden {
			display: none !important;
			visibility: hidden !important;
			position: absolute !important;
			left: -9999px !important;
			width: 1px !important;
			height: 1px !important;
			overflow: hidden !important;
			clip: rect(1px, 1px, 1px, 1px) !important;
		}
	' );
}

/**
 * Remove screen reader text from WooCommerce price HTML
 */
add_filter( 'woocommerce_price_html', 'primefit_remove_screen_reader_from_price', 10, 2 );
function primefit_remove_screen_reader_from_price( $price_html, $product ) {
	// Remove screen reader text spans from price HTML
	$price_html = preg_replace( '/<span[^>]*class="[^"]*screen-reader-text[^"]*"[^>]*>.*?<\/span>/i', '', $price_html );
	$price_html = preg_replace( '/<span[^>]*class="[^"]*sr-only[^"]*"[^>]*>.*?<\/span>/i', '', $price_html );
	$price_html = preg_replace( '/<span[^>]*class="[^"]*visually-hidden[^"]*"[^>]*>.*?<\/span>/i', '', $price_html );
	
	return $price_html;
}

/**
 * Remove screen reader text from general HTML content
 */
add_filter( 'the_content', 'primefit_remove_screen_reader_from_content', 20 );
function primefit_remove_screen_reader_from_content( $content ) {
	// Remove screen reader text spans from content
	$content = preg_replace( '/<span[^>]*class="[^"]*screen-reader-text[^"]*"[^>]*>.*?<\/span>/i', '', $content );
	$content = preg_replace( '/<span[^>]*class="[^"]*sr-only[^"]*"[^>]*>.*?<\/span>/i', '', $content );
	$content = preg_replace( '/<span[^>]*class="[^"]*visually-hidden[^"]*"[^>]*>.*?<\/span>/i', '', $content );
	
	return $content;
}

/**
 * Remove screen reader text from WooCommerce cart item data
 */
add_filter( 'woocommerce_cart_item_data', 'primefit_remove_screen_reader_from_cart_data', 10, 3 );
function primefit_remove_screen_reader_from_cart_data( $item_data, $cart_item, $cart_item_key ) {
	foreach ( $item_data as $key => $data ) {
		if ( isset( $data['value'] ) ) {
			$item_data[$key]['value'] = preg_replace( '/<span[^>]*class="[^"]*screen-reader-text[^"]*"[^>]*>.*?<\/span>/i', '', $data['value'] );
			$item_data[$key]['value'] = preg_replace( '/<span[^>]*class="[^"]*sr-only[^"]*"[^>]*>.*?<\/span>/i', '', $data['value'] );
			$item_data[$key]['value'] = preg_replace( '/<span[^>]*class="[^"]*visually-hidden[^"]*"[^>]*>.*?<\/span>/i', '', $data['value'] );
		}
	}
	
	return $item_data;
}

/**
 * Remove screen reader text from WooCommerce formatted cart item data
 */
add_filter( 'woocommerce_get_formatted_cart_item_data', 'primefit_remove_screen_reader_from_formatted_cart_data', 10, 2 );
function primefit_remove_screen_reader_from_formatted_cart_data( $formatted_data, $cart_item ) {
	foreach ( $formatted_data as $key => $data ) {
		if ( isset( $data['value'] ) ) {
			$formatted_data[$key]['value'] = preg_replace( '/<span[^>]*class="[^"]*screen-reader-text[^"]*"[^>]*>.*?<\/span>/i', '', $data['value'] );
			$formatted_data[$key]['value'] = preg_replace( '/<span[^>]*class="[^"]*sr-only[^"]*"[^>]*>.*?<\/span>/i', '', $data['value'] );
			$formatted_data[$key]['value'] = preg_replace( '/<span[^>]*class="[^"]*visually-hidden[^"]*"[^>]*>.*?<\/span>/i', '', $data['value'] );
		}
	}
	
	return $formatted_data;
}

/**
 * Load theme includes
 */
$includes = [
	'inc/setup.php',           // Theme setup and configuration
	'inc/enqueue.php',         // Scripts and styles enqueuing
	'inc/hooks.php',           // Actions and filters
	'inc/helpers.php',         // Utility and helper functions
	'inc/customizer.php',      // Theme customizer settings
	'inc/acf-fields.php',      // ACF field groups and helpers
	'inc/woocommerce.php',     // WooCommerce integration
	'inc/discount-system.php', // Discount code tracking system
	'inc/discount-system-styles.php', // Discount system admin styles
	'inc/image-optimization.php', // Image optimization and lazy loading
];

foreach ( $includes as $file ) {
	$file_path = get_template_directory() . '/' . $file;
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}

// Debug include removed - checkout is now working

// Emergency shortcode fix removed - using custom checkout template

/**
 * Force classic checkout instead of WooCommerce Blocks
 * This resolves the Store API conflicts
 */
add_action( 'init', function() {
	// Disable WooCommerce Blocks checkout feature
	add_filter( 'woocommerce_blocks_is_feature_enabled', function( $enabled, $feature ) {
		if ( $feature === 'checkout' ) {
			return false;
		}
		return $enabled;
	}, 10, 2 );
	
	// Ensure WooCommerce shortcodes are enabled
	add_action( 'wp', function() {
		if ( is_checkout() ) {
			// Force shortcode processing
			add_filter( 'the_content', 'do_shortcode', 11 );
			
			// Ensure WooCommerce shortcodes are registered
			if ( class_exists( 'WC_Shortcodes' ) ) {
				WC_Shortcodes::init();
			}
		}
	});
	
	// Remove blocks checkout from available blocks
	add_filter( 'allowed_block_types_all', function( $allowed_blocks, $editor_context ) {
		if ( isset( $editor_context->post ) && $editor_context->post->post_type === 'page' ) {
			if ( is_array( $allowed_blocks ) ) {
				$blocks_to_remove = [
					'woocommerce/checkout',
					'woocommerce/cart'
				];
				$allowed_blocks = array_diff( $allowed_blocks, $blocks_to_remove );
			}
		}
		return $allowed_blocks;
	}, 10, 2 );
}, 5 );

/**
 * Initialize session early for coupon handling
 */
add_action( 'init', 'primefit_init_session_early', 1 );
function primefit_init_session_early() {
	// Start session early if we have a coupon parameter and not in admin
	if ( isset( $_GET['coupon'] ) && ! is_admin() && ! session_id() && ! wp_doing_ajax() ) {
		// Check if headers have been sent before starting session
		if ( ! headers_sent() ) {
			session_start();
		}
	}
}

/**
 * Handle URL coupon application on page load
 * Automatically applies coupon codes from URL parameters
 * Works on base URL and all pages, with session fallback
 */
add_action( 'wp_loaded', 'primefit_handle_url_coupon', 10 );
function primefit_handle_url_coupon() {
	// Only process if we have a coupon parameter and WooCommerce is active
	if ( ! isset( $_GET['coupon'] ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$coupon_code = sanitize_text_field( $_GET['coupon'] );

	if ( empty( $coupon_code ) ) {
		return;
	}

	// Don't apply on admin pages
	if ( is_admin() ) {
		return;
	}

	// Store coupon in session for later application
	if ( ! session_id() ) {
		session_start();
	}
	$_SESSION['primefit_pending_coupon'] = $coupon_code;

	// Try to apply coupon if cart is available and not empty
	if ( WC()->cart && ! WC()->cart->is_empty() ) {
		$applied = primefit_apply_coupon_if_valid( $coupon_code );
		if ( $applied ) {
			// Remove from session since it's applied
			unset( $_SESSION['primefit_pending_coupon'] );
		}
	}

	// Redirect to clean URL (but don't exit to avoid headers already sent error)
	primefit_redirect_without_coupon_param();
}

/**
 * Apply coupon if valid and not already applied
 * Added safety checks to prevent early cart access
 */
function primefit_apply_coupon_if_valid( $coupon_code ) {
	// Safety check - ensure we're not in admin and WC is loaded
	if ( is_admin() || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	// Check if cart is empty before accessing it
	try {
		// Only proceed if cart is not empty to avoid early cart access
		if ( WC()->cart->is_empty() ) {
			return false;
		}

		$applied_coupons = WC()->cart->get_applied_coupons();

		// Check if coupon is already applied (case-insensitive)
		foreach ( $applied_coupons as $applied_coupon ) {
			if ( strtoupper( $applied_coupon ) === strtoupper( $coupon_code ) ) {
				return true; // Already applied
			}
		}

		// Validate coupon exists and is valid
		$coupon = new WC_Coupon( $coupon_code );
		if ( ! $coupon || ! $coupon->get_id() ) {
			return false;
		}

		// Try to apply the coupon
		return WC()->cart->apply_coupon( $coupon_code );

	} catch ( Exception $e ) {
		// If there's any error accessing cart, return false
		return false;
	}
}

/**
 * Apply pending coupon from session when cart is loaded
 * Moved to wp_loaded to avoid early cart access
 * FIXED: Added state management to prevent race conditions
 */
add_action( 'wp_loaded', 'primefit_apply_pending_coupon_from_session', 20 );
function primefit_apply_pending_coupon_from_session() {
	// Only run on frontend
	if ( is_admin() ) {
		return;
	}

	if ( ! session_id() ) {
		session_start();
	}

	if ( isset( $_SESSION['primefit_pending_coupon'] ) ) {
		$coupon_code = $_SESSION['primefit_pending_coupon'];

		// CRITICAL: Check if coupon is already being processed to prevent race conditions
		if ( isset( $_SESSION['primefit_coupon_processing'] ) && $_SESSION['primefit_coupon_processing'] === $coupon_code ) {
			return;
		}

		// Only try to apply if cart exists and is not empty
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			// Mark as processing to prevent race conditions
			$_SESSION['primefit_coupon_processing'] = $coupon_code;

			$applied = primefit_apply_coupon_if_valid( $coupon_code );

			if ( $applied ) {
				// Remove from session since it's now applied
				unset( $_SESSION['primefit_pending_coupon'] );
			}

			// Remove processing flag
			unset( $_SESSION['primefit_coupon_processing'] );
		}
	}
}

/**
 * Add pending coupon data to cart fragments for JavaScript
 * Only add if we have a pending coupon
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'primefit_add_coupon_data_to_fragments' );
function primefit_add_coupon_data_to_fragments( $fragments ) {
	// Only add if we have a pending coupon and not in admin
	if ( is_admin() || ! isset( $_SESSION['primefit_pending_coupon'] ) ) {
		return $fragments;
	}

	if ( ! session_id() ) {
		session_start();
	}

	if ( isset( $_SESSION['primefit_pending_coupon'] ) ) {
		$pending_coupon = $_SESSION['primefit_pending_coupon'];
		// Add coupon data to a hidden element for JavaScript
		$fragments['.primefit-coupon-data'] = '<div class="primefit-coupon-data" data-pending-coupon="' . esc_attr( $pending_coupon ) . '" style="display:none;"></div>';
	}

	return $fragments;
}

/**
 * Check for pending coupons when WooCommerce is ready
 * Moved to wp_loaded to avoid early cart access
 * FIXED: Added state management to prevent race conditions
 */
add_action( 'wp_loaded', 'primefit_check_pending_coupon_on_wc_init', 30 );
function primefit_check_pending_coupon_on_wc_init() {
	// Only run on frontend
	if ( is_admin() ) {
		return;
	}

	// Only run if we have a pending coupon
	if ( ! session_id() ) {
		session_start();
	}

	if ( ! isset( $_SESSION['primefit_pending_coupon'] ) ) {
		return;
	}

	$coupon_code = $_SESSION['primefit_pending_coupon'];

	// CRITICAL: Check if coupon is already being processed to prevent race conditions
	if ( isset( $_SESSION['primefit_coupon_processing'] ) && $_SESSION['primefit_coupon_processing'] === $coupon_code ) {
		return;
	}

	// Try to apply the coupon now that WC is fully loaded
	if ( WC()->cart && ! WC()->cart->is_empty() ) {
		$applied = primefit_apply_coupon_if_valid( $coupon_code );

		if ( $applied ) {
			// Remove from session since it's now applied
			unset( $_SESSION['primefit_pending_coupon'] );
		}
	}
}

/**
 * Redirect to the same page without the coupon parameter
 */
function primefit_redirect_without_coupon_param() {
	// Only redirect if we're not in an AJAX request and not in admin
	if ( wp_doing_ajax() || is_admin() ) {
		return;
	}

	// Build clean URL
	$url = remove_query_arg( 'coupon' );

	// Only redirect if the URL actually changed
	if ( $url !== $_SERVER['REQUEST_URI'] ) {
		wp_redirect( $url );
		// Don't use exit() to avoid headers already sent errors
	}
}