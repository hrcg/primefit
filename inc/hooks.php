<?php
/**
 * PrimeFit Theme Hooks
 *
 * Actions and filters for theme functionality
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce: wrap product thumbnail and add cart count fragment
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'primefit_cart_count_fragment' );
function primefit_cart_count_fragment( $fragments ) {
	ob_start();
	?>
	<span class="count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span>
	<?php
	$fragments['span.count'] = ob_get_clean();
	return $fragments;
}

/**
 * WooCommerce: Custom product thumbnail with hover effect for product loops
 */
add_action( 'init', 'primefit_customize_woocommerce_hooks' );
function primefit_customize_woocommerce_hooks() {
	// Remove default hooks
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	
	// Keep default product link wrapper but modify it
	add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	
	// Add our custom hooks
	add_action( 'woocommerce_before_shop_loop_item_title', 'primefit_loop_product_thumbnail', 10 );
	add_action( 'woocommerce_after_shop_loop_item_title', 'primefit_loop_product_price', 10 );
}

/**
 * Custom product thumbnail with hover effect, status badges, and color swatches
 */
function primefit_loop_product_thumbnail() {
	global $product;
	
	if ( ! $product ) {
		return;
	}
	
	// Get product images
	$attachment_ids = $product->get_gallery_image_ids();
	$main_image_id = $product->get_image_id();
	$second_image_id = !empty($attachment_ids) ? $attachment_ids[0] : null;
	
	echo '<div class="product-image-container">';
	
	if ( $main_image_id ) {
		// Use optimized image size for product loops
		$image_size = wp_is_mobile() ? 'primefit-product-loop-small' : 'primefit-product-loop';
		
		echo wp_get_attachment_image( $main_image_id, $image_size, false, [
			'class' => 'attachment-woocommerce_thumbnail',
			'alt' => esc_attr( $product->get_name() ),
			'loading' => 'lazy',
			'decoding' => 'async'
		] );
	}
	
	if ( $second_image_id ) {
		// Use optimized image size for hover image
		$image_size = wp_is_mobile() ? 'primefit-product-loop-small' : 'primefit-product-loop';
		
		echo wp_get_attachment_image( $second_image_id, $image_size, false, [
			'class' => 'product-second-image',
			'alt' => esc_attr( $product->get_name() ),
			'loading' => 'lazy',
			'decoding' => 'async'
		] );
	}
	
	// Add status badge
	get_template_part( 'woocommerce/global/product-status-badge' );
	
	// Add color swatches for variable products
	if ( $product->is_type( 'variable' ) ) {
		primefit_render_product_loop_color_swatches( $product );
	}
	
	echo '</div>';
}

/**
 * Custom product price display
 */
function primefit_loop_product_price() {
	global $product;
	
	if ( ! $product ) {
		return;
	}
	
	// For variable products, show size options instead of price on hover
	if ( $product->is_type( 'variable' ) ) {
		echo '<div class="product-price-container">';
		
		// Show price by default
		get_template_part( 'woocommerce/single-product/parts/product-price' );
		
		// Show size options on hover
		primefit_render_size_selection_overlay( $product );
		
		echo '</div>';
	} else {
		// For simple products, just show the price
		get_template_part( 'woocommerce/single-product/parts/product-price' );
	}
}

/**
 * Add product status tags to WooCommerce products
 */
add_action( 'woocommerce_before_shop_loop_item_title', 'primefit_add_product_status_tag', 5 );
function primefit_add_product_status_tag() {
	global $product;
	
	$has_sale_label = false;
	
	// Check if product is on sale
	if ( $product->is_on_sale() ) {
		$sale_percentage = 'SALE';
		$tag_class = 'sale';
		$percentage = 0;
		
		if ( $product->get_regular_price() && $product->get_sale_price() ) {
			$percentage = round( ( ( $product->get_regular_price() - $product->get_sale_price() ) / $product->get_regular_price() ) * 100 );
			if ( $percentage >= 50 ) {
				$sale_percentage = 'FLASH SALE';
				$tag_class = 'flash-sale';
			}
		}
		
		echo '<span class="product-status-tag ' . esc_attr( $tag_class ) . '">' . esc_html( $sale_percentage ) . '</span>';
		$has_sale_label = true;
	}
	
	// Check if product is out of stock
	if ( ! $product->is_in_stock() ) {
		echo '<span class="product-status-tag sold-out">SOLD OUT</span>';
	}
	
	// Check for LIMITED STOCK on variable products with mixed stock availability
	if ( $product->is_type( 'variable' ) && $product->is_in_stock() ) {
		$variations = $product->get_available_variations();
		$has_out_of_stock_variations = false;
		$has_in_stock_variations = false;
		
		foreach ( $variations as $variation_data ) {
			$variation = wc_get_product( $variation_data['variation_id'] );
			if ( $variation ) {
				if ( $variation->is_in_stock() ) {
					$has_in_stock_variations = true;
				} else {
					$has_out_of_stock_variations = true;
				}
			}
		}
		
		// Show LIMITED STOCK if some variations are out of stock but others are in stock
		if ( $has_out_of_stock_variations && $has_in_stock_variations ) {
			$limited_stock_class = 'limited-stock';
			if ( $has_sale_label ) {
				$limited_stock_class .= ' has-sale-label';
			}
			echo '<span class="product-status-tag ' . esc_attr( $limited_stock_class ) . '">LIMITED STOCK</span>';
		}
	}
}

/**
 * Add sold out text for out of stock products
 */
add_action( 'woocommerce_after_shop_loop_item_title', 'primefit_add_sold_out_text', 15 );
function primefit_add_sold_out_text() {
	global $product;
	
	if ( ! $product->is_in_stock() ) {
		echo '<p class="sold-out-text">SOLD OUT</p>';
	}
}

/**
 * Remove WooCommerce shop sidebar and filters
 */
function primefit_remove_shop_sidebar() {
	// Remove sidebar
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	
	// Remove layered nav widgets
	remove_action( 'woocommerce_sidebar', 'woocommerce_output_content_wrapper_end', 20 );
}

/**
 * Disable WooCommerce shop filters and sorting on archive pages
 */
add_action( 'init', 'primefit_disable_shop_filters' );
function primefit_disable_shop_filters() {
	if ( is_admin() ) {
		return;
	}
	
	// Remove default ordering dropdown (we have our own in the filter bar)
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	
	// Remove default result count (we have our own in the filter bar)
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	
	// Remove breadcrumbs on shop page
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
}

/**
 * Ensure WooCommerce sorting still works with our custom filter bar
 */
add_action( 'pre_get_posts', 'primefit_handle_custom_sorting' );
function primefit_handle_custom_sorting( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	
	// Only apply to WooCommerce shop/category/tag pages
	if ( ! ( $query->is_post_type_archive( 'product' ) || $query->is_tax( get_object_taxonomies( 'product' ) ) ) ) {
		return;
	}
	
	// Handle sorting
	if ( isset( $_GET['orderby'] ) ) {
		$orderby = wc_clean( $_GET['orderby'] );
		
		switch ( $orderby ) {
			case 'menu_order':
				$query->set( 'orderby', 'menu_order title' );
				$query->set( 'order', 'ASC' );
				break;
			case 'date':
				$query->set( 'orderby', 'date ID' );
				$query->set( 'order', 'DESC' );
				break;
			case 'price':
				$query->set( 'meta_key', '_price' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'ASC' );
				break;
			case 'price-desc':
				$query->set( 'meta_key', '_price' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;
			case 'popularity':
				$query->set( 'meta_key', 'total_sales' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;
			case 'rating':
				$query->set( 'meta_key', '_wc_average_rating' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;
		}
	}
}

/**
 * Add data-mega-menu attribute to specific menu items for mega menu functionality
 */
add_filter( 'nav_menu_link_attributes', 'primefit_add_mega_menu_attribute', 10, 3 );
function primefit_add_mega_menu_attribute( $atts, $item, $args ) {
	// Only apply to primary menu
	if ( $args->theme_location !== 'primary' ) {
		return $atts;
	}
	
	// Get the mega menu configuration from customizer
	$mega_menu_config = primefit_get_mega_menu_config();
	$trigger_item_id = $mega_menu_config['trigger_item'];
	
	// Check if this menu item should trigger the mega menu
	if ( ! empty( $trigger_item_id ) && $item->ID == $trigger_item_id ) {
		$atts['data-mega-menu'] = 'true';
	}
	
	return $atts;
}

/**
 * Add NEW badge to specific menu items
 */
add_filter( 'nav_menu_item_title', 'primefit_add_new_badge_to_menu_item', 10, 4 );
function primefit_add_new_badge_to_menu_item( $title, $item, $args, $depth ) {
	// Only apply to primary menu and top-level items
	if ( $args->theme_location !== 'primary' || $depth !== 0 ) {
		return $title;
	}
	
	// Get the navigation badge configuration from customizer
	$badge_config = primefit_get_navigation_badge_config();
	
	// Check if badge is enabled and this is the correct menu item
	if ( $badge_config['enabled'] && ! empty( $badge_config['menu_item_id'] ) && $item->ID == $badge_config['menu_item_id'] ) {
		$badge_html = sprintf(
			'<span class="menu-badge menu-badge--new" style="background-color: %s; color: %s;">%s</span>',
			esc_attr( $badge_config['bg_color'] ),
			esc_attr( $badge_config['text_color'] ),
			esc_html( $badge_config['text'] )
		);
		
		$title = $title . $badge_html;
	}
	
	return $title;
}

/**
 * Clear product caches when product is updated
 */
add_action( 'save_post', 'primefit_clear_product_cache_on_save', 10, 2 );
function primefit_clear_product_cache_on_save( $post_id, $post ) {
	// Only clear cache for products
	if ( $post->post_type === 'product' ) {
		primefit_clear_product_performance_cache( $post_id );
	}
}

/**
 * Clear product caches when product meta is updated
 */
add_action( 'updated_post_meta', 'primefit_clear_product_cache_on_meta_update', 10, 4 );
function primefit_clear_product_cache_on_meta_update( $meta_id, $post_id, $meta_key, $meta_value ) {
	// Only clear cache for products
	if ( get_post_type( $post_id ) === 'product' ) {
		primefit_clear_product_performance_cache( $post_id );
	}
}

/**
 * Clear product caches when ACF fields are updated
 */
add_action( 'acf/save_post', 'primefit_clear_product_cache_on_acf_save', 20 );
function primefit_clear_product_cache_on_acf_save( $post_id ) {
	// Only clear cache for products
	if ( get_post_type( $post_id ) === 'product' ) {
		primefit_clear_product_performance_cache( $post_id );
	}
}