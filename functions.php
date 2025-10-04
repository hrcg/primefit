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
 * Optimize font loading with preload hints for better performance
 */
add_action('wp_head', 'primefit_optimize_font_loading', 1);
function primefit_optimize_font_loading() {
	// Preload critical font files with high priority - using Google Fonts API
	// Let Google Fonts handle the specific font file URLs dynamically
	echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" as="style" fetchpriority="high">';
	
	// Add font-display: swap for better loading experience
	echo '<style>
		@font-face {
			font-family: "Figtree";
			font-display: swap;
			font-style: normal;
			font-weight: 400;
		}
		@font-face {
			font-family: "Figtree";
			font-display: swap;
			font-style: normal;
			font-weight: 600;
		}
		@font-face {
			font-family: "Figtree";
			font-display: swap;
			font-style: normal;
			font-weight: 700;
		}
	</style>';
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
 * Enhanced Database Query Optimization with Product Data Caching
 * Advanced caching system for WooCommerce products and queries
 */
add_action( 'init', 'primefit_init_advanced_caching', 1 );
function primefit_init_advanced_caching() {
	// Initialize object cache groups
	if ( ! wp_cache_get( 'primefit_cache_init', 'primefit_cache' ) ) {
		wp_cache_set( 'primefit_cache_init', true, 'primefit_cache', 0 );
		
		// Add advanced product caching hooks
		add_action( 'pre_get_posts', 'primefit_advanced_product_query_optimization', 5 );
		add_filter( 'posts_results', 'primefit_cache_product_meta_bulk', 10, 2 );
		add_action( 'wp', 'primefit_cache_query_results_advanced', 15 );
		
		// Enhanced cache invalidation
		add_action( 'save_post', 'primefit_invalidate_product_cache_advanced', 10, 2 );
		add_action( 'delete_post', 'primefit_invalidate_product_cache_advanced', 10, 2 );
		add_action( 'woocommerce_product_set_stock_status', 'primefit_invalidate_product_cache_advanced' );
		add_action( 'woocommerce_variation_set_stock_status', 'primefit_invalidate_product_cache_advanced' );
		add_action( 'woocommerce_product_object_updated_props', 'primefit_invalidate_product_cache_advanced' );
	}
}

/**
 * Advanced product query optimization with intelligent caching
 */
function primefit_advanced_product_query_optimization( $query ) {
	// Only optimize main queries on frontend
	if ( is_admin() || ! $query->is_main_query() || wp_doing_ajax() ) {
		return;
	}
	
	// Check if this is a WooCommerce product query
	$is_product_query = $query->is_post_type_archive( 'product' ) || 
						$query->is_tax( get_object_taxonomies( 'product' ) ) ||
						( $query->get( 'post_type' ) === 'product' );
	
	if ( ! $is_product_query ) {
		return;
	}
	
	// Generate comprehensive cache key
	$query_vars = $query->query_vars;
	$cache_key = 'primefit_advanced_query_' . md5( serialize( $query_vars ) );
	
	// Try to get cached results
	$cached_results = wp_cache_get( $cache_key, 'primefit_queries' );
	
	if ( false !== $cached_results ) {
		// Apply cached results
		$query->posts = $cached_results['posts'];
		$query->post_count = $cached_results['post_count'];
		$query->found_posts = $cached_results['found_posts'];
		$query->max_num_pages = $cached_results['max_num_pages'];
		$query->is_cached = true;
		return;
	}
	
	// Optimize query parameters
	$query->set( 'posts_per_page', 12 ); // Increased for better UX
	$query->set( 'update_post_meta_cache', true );
	$query->set( 'update_post_term_cache', true );
	$query->set( 'no_found_rows', false );
	
	// Store cache key for later use
	$query->primefit_cache_key = $cache_key;
}

/**
 * Bulk cache product meta data to prevent N+1 queries
 */
function primefit_cache_product_meta_bulk( $posts, $query ) {
	if ( empty( $posts ) || ! $query->is_main_query() ) {
		return $posts;
	}
	
	// Only cache for product queries
	if ( $query->get( 'post_type' ) !== 'product' && 
		 ! $query->is_post_type_archive( 'product' ) && 
		 ! $query->is_tax( get_object_taxonomies( 'product' ) ) ) {
		return $posts;
	}
	
	$post_ids = wp_list_pluck( $posts, 'ID' );
	
	// Bulk cache post meta
	update_meta_cache( 'post', $post_ids );
	
	// Bulk cache product-specific meta
	primefit_cache_product_meta_bulk_advanced( $post_ids );
	
	return $posts;
}

/**
 * Advanced bulk caching for product-specific meta data
 */
function primefit_cache_product_meta_bulk_advanced( $post_ids ) {
	global $wpdb;
	
	if ( empty( $post_ids ) ) {
		return;
	}
	
	$post_ids_str = implode( ',', array_map( 'intval', $post_ids ) );
	
	// Cache all product meta in one query
	$meta_query = "
		SELECT post_id, meta_key, meta_value 
		FROM {$wpdb->postmeta} 
		WHERE post_id IN ({$post_ids_str}) 
		AND meta_key IN (
			'_sku', '_price', '_sale_price', '_regular_price', 
			'_stock_status', '_stock', '_manage_stock', '_product_type',
			'_product_attributes', '_default_attributes', '_product_image_gallery',
			'_thumbnail_id', '_weight', '_length', '_width', '_height',
			'_virtual', '_downloadable', '_sold_individually', '_backorders',
			'_featured', '_visibility', '_purchase_note', '_menu_order'
		)
	";
	
	$meta_results = $wpdb->get_results( $meta_query );
	
	// Organize meta by post ID
	$meta_by_post = array();
	foreach ( $meta_results as $meta ) {
		$meta_by_post[ $meta->post_id ][ $meta->meta_key ] = $meta->meta_value;
	}
	
	// Cache organized meta data
	foreach ( $post_ids as $post_id ) {
		if ( isset( $meta_by_post[ $post_id ] ) ) {
			wp_cache_set( $post_id, $meta_by_post[ $post_id ], 'primefit_product_meta', 3600 );
		}
	}
}

/**
 * Cache query results with advanced metadata
 */
function primefit_cache_query_results_advanced() {
	global $wp_query;
	
	if ( ! $wp_query->is_main_query() || empty( $wp_query->posts ) ) {
		return;
	}
	
	// Check if we have a cache key from optimization
	if ( ! isset( $wp_query->primefit_cache_key ) ) {
		return;
	}
	
	$cache_key = $wp_query->primefit_cache_key;
	
	// Prepare comprehensive cache data
	$cache_data = array(
		'posts' => $wp_query->posts,
		'post_count' => $wp_query->post_count,
		'found_posts' => $wp_query->found_posts,
		'max_num_pages' => $wp_query->max_num_pages,
		'cached_at' => time(),
		'query_vars' => $wp_query->query_vars
	);
	
	// Cache for 30 minutes
	wp_cache_set( $cache_key, $cache_data, 'primefit_queries', 1800 );
	
	// Also cache individual product data
	foreach ( $wp_query->posts as $post ) {
		if ( $post->post_type === 'product' ) {
			primefit_cache_individual_product_data( $post->ID );
		}
	}
}

/**
 * Cache individual product data comprehensively
 */
function primefit_cache_individual_product_data( $product_id ) {
	$cache_key = "product_comprehensive_{$product_id}";
	$cached_data = wp_cache_get( $cache_key, 'primefit_products' );
	
	if ( false !== $cached_data ) {
		return $cached_data;
	}
	
	global $wpdb;
	
	// Single optimized query for all product data
	$query = $wpdb->prepare( "
		SELECT
			p.ID, p.post_title, p.post_content, p.post_excerpt, p.post_status,
			p.post_date, p.post_modified, p.menu_order,
			pm_sku.meta_value as sku,
			pm_price.meta_value as price,
			pm_sale_price.meta_value as sale_price,
			pm_regular_price.meta_value as regular_price,
			pm_stock_status.meta_value as stock_status,
			pm_stock.meta_value as stock_quantity,
			pm_manage_stock.meta_value as manage_stock,
			pm_product_type.meta_value as product_type,
			pm_weight.meta_value as weight,
			pm_dimensions.meta_value as dimensions,
			pm_virtual.meta_value as `virtual`,
			pm_downloadable.meta_value as downloadable,
			pm_featured.meta_value as featured,
			pm_visibility.meta_value as visibility,
			pm_gallery.meta_value as gallery_images,
			pm_thumbnail.meta_value as thumbnail_id,
			pm_attributes.meta_value as attributes,
			pm_default_attributes.meta_value as default_attributes
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm_sku ON (p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku')
		LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price')
		LEFT JOIN {$wpdb->postmeta} pm_sale_price ON (p.ID = pm_sale_price.post_id AND pm_sale_price.meta_key = '_sale_price')
		LEFT JOIN {$wpdb->postmeta} pm_regular_price ON (p.ID = pm_regular_price.post_id AND pm_regular_price.meta_key = '_regular_price')
		LEFT JOIN {$wpdb->postmeta} pm_stock_status ON (p.ID = pm_stock_status.post_id AND pm_stock_status.meta_key = '_stock_status')
		LEFT JOIN {$wpdb->postmeta} pm_stock ON (p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock')
		LEFT JOIN {$wpdb->postmeta} pm_manage_stock ON (p.ID = pm_manage_stock.post_id AND pm_manage_stock.meta_key = '_manage_stock')
		LEFT JOIN {$wpdb->postmeta} pm_product_type ON (p.ID = pm_product_type.post_id AND pm_product_type.meta_key = '_product_type')
		LEFT JOIN {$wpdb->postmeta} pm_weight ON (p.ID = pm_weight.post_id AND pm_weight.meta_key = '_weight')
		LEFT JOIN {$wpdb->postmeta} pm_dimensions ON (p.ID = pm_dimensions.post_id AND pm_dimensions.meta_key = '_dimensions')
		LEFT JOIN {$wpdb->postmeta} pm_virtual ON (p.ID = pm_virtual.post_id AND pm_virtual.meta_key = '_virtual')
		LEFT JOIN {$wpdb->postmeta} pm_downloadable ON (p.ID = pm_downloadable.post_id AND pm_downloadable.meta_key = '_downloadable')
		LEFT JOIN {$wpdb->postmeta} pm_featured ON (p.ID = pm_featured.post_id AND pm_featured.meta_key = '_featured')
		LEFT JOIN {$wpdb->postmeta} pm_visibility ON (p.ID = pm_visibility.post_id AND pm_visibility.meta_key = '_visibility')
		LEFT JOIN {$wpdb->postmeta} pm_gallery ON (p.ID = pm_gallery.post_id AND pm_gallery.meta_key = '_product_image_gallery')
		LEFT JOIN {$wpdb->postmeta} pm_thumbnail ON (p.ID = pm_thumbnail.post_id AND pm_thumbnail.meta_key = '_thumbnail_id')
		LEFT JOIN {$wpdb->postmeta} pm_attributes ON (p.ID = pm_attributes.post_id AND pm_attributes.meta_key = '_product_attributes')
		LEFT JOIN {$wpdb->postmeta} pm_default_attributes ON (p.ID = pm_default_attributes.post_id AND pm_default_attributes.meta_key = '_default_attributes')
		WHERE p.ID = %d AND p.post_type = 'product' AND p.post_status = 'publish'
	", $product_id );
	
	$result = $wpdb->get_row( $query );
	
	if ( ! $result ) {
		return false;
	}
	
	// Process and structure comprehensive product data
	$product_data = array(
		'id' => $result->ID,
		'title' => $result->post_title,
		'content' => $result->post_content,
		'excerpt' => $result->post_excerpt,
		'status' => $result->post_status,
		'date' => $result->post_date,
		'modified' => $result->post_modified,
		'menu_order' => $result->menu_order,
		'sku' => $result->sku ?: '',
		'price' => $result->price ?: '',
		'sale_price' => $result->sale_price ?: '',
		'regular_price' => $result->regular_price ?: '',
		'stock_status' => $result->stock_status ?: 'instock',
		'stock_quantity' => $result->stock_quantity ?: '',
		'manage_stock' => $result->manage_stock ?: 'no',
		'product_type' => $result->product_type ?: 'simple',
		'weight' => $result->weight ?: '',
		'dimensions' => $result->dimensions ? maybe_unserialize( $result->dimensions ) : array(),
		'virtual' => $result->virtual === 'yes',
		'downloadable' => $result->downloadable === 'yes',
		'featured' => $result->featured === 'yes',
		'visibility' => $result->visibility ?: 'visible',
		'gallery_images' => $result->gallery_images ? array_filter( explode( ',', $result->gallery_images ) ) : array(),
		'thumbnail_id' => $result->thumbnail_id ?: 0,
		'attributes' => $result->attributes ? maybe_unserialize( $result->attributes ) : array(),
		'default_attributes' => $result->default_attributes ? maybe_unserialize( $result->default_attributes ) : array(),
		'is_variable' => $result->product_type === 'variable',
		'is_in_stock' => $result->stock_status === 'instock',
		'is_featured' => $result->featured === 'yes',
		'is_virtual' => $result->virtual === 'yes',
		'is_downloadable' => $result->downloadable === 'yes',
		'cached_at' => time()
	);
	
	// Cache for 1 hour
	wp_cache_set( $cache_key, $product_data, 'primefit_products', 3600 );
	
	return $product_data;
}

/**
 * Advanced cache invalidation system
 */
function primefit_invalidate_product_cache_advanced( $post_id, $post = null ) {
	if ( ! $post_id ) {
		return;
	}
	
	// Clear individual product cache
	wp_cache_delete( "product_comprehensive_{$post_id}", 'primefit_products' );
	wp_cache_delete( "product_variations_optimized_{$post_id}", 'primefit_variations' );
	wp_cache_delete( $post_id, 'primefit_product_meta' );
	
	// Clear query caches
	primefit_clear_all_query_caches();
	
	// Clear transient caches
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		'_transient_primefit_%'
	) );
}

/**
 * Clear all query caches
 */
function primefit_clear_all_query_caches() {
	// Clear object cache for queries
	if ( function_exists( 'wp_cache_flush_group' ) ) {
		wp_cache_flush_group( 'primefit_queries' );
		wp_cache_flush_group( 'primefit_products' );
		wp_cache_flush_group( 'primefit_variations' );
		wp_cache_flush_group( 'primefit_product_meta' );
	} else {
		// Fallback: only flush entire cache in controlled contexts to avoid site-wide impact
		if ( ( function_exists( 'is_admin' ) && is_admin() ) || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}
		}
	}
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
	'inc/widgets.php',         // Custom widgets
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

// Deprecated: session-based initialization removed in favor of short-lived cookie approach

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

	// Don't process on admin or AJAX
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	// Store coupon in a short-lived cookie (10 minutes)
	$expires = time() + 10 * MINUTE_IN_SECONDS;
	// Secure cookie flags where possible
	$secure   = is_ssl();
	$httponly = true;
	setcookie( 'primefit_pending_coupon', $coupon_code, [ 'expires' => $expires, 'path' => '/', 'secure' => $secure, 'httponly' => $httponly, 'samesite' => 'Lax' ] );

	// If on cart/checkout and cart exists and not empty, attempt immediate apply
	if ( ( is_cart() || is_checkout() ) && function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		primefit_try_apply_coupon_from_cookie();
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
add_action( 'wp_loaded', 'primefit_apply_pending_coupon_from_cookie', 20 );
function primefit_apply_pending_coupon_from_cookie() {
	// Only run on frontend and when not admin/ajax
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	primefit_try_apply_coupon_from_cookie();
}

function primefit_try_apply_coupon_from_cookie() {
	if ( empty( $_COOKIE['primefit_pending_coupon'] ) ) {
		return;
	}

	$coupon_code = sanitize_text_field( wp_unslash( $_COOKIE['primefit_pending_coupon'] ) );

	// Only on cart/checkout to avoid cache-busting elsewhere
	if ( ! ( is_cart() || is_checkout() ) ) {
		return;
	}

	if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		$applied = primefit_apply_coupon_if_valid( $coupon_code );
		if ( $applied ) {
			// Clear cookie after successful application
			setcookie( 'primefit_pending_coupon', '', [ 'expires' => time() - HOUR_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ] );
		}
	}
}

/**
 * Add pending coupon data to cart fragments for JavaScript
 * Only add if we have a pending coupon
 */
// Session-based fragments removed; cookie is read server-side on cart/checkout

/**
 * Check for pending coupons when WooCommerce is ready
 * Moved to wp_loaded to avoid early cart access
 * FIXED: Added state management to prevent race conditions
 */
// Redundant session-based WC init hook removed

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