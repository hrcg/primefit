<?php
/**
 * Product Status Badge Template
 * 
 * Displays product status badges (Flash Sale, Sale, Sold Out, Limited Stock)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product ) {
	return;
}

$product_id = $product->get_id();
$is_on_sale = $product->is_on_sale();
$is_sold_out = !$product->is_in_stock();
$flash_sale = get_post_meta($product_id, '_flash_sale', true);

// Check for LIMITED STOCK on variable products with mixed stock availability
$has_limited_stock = false;
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
	$has_limited_stock = $has_out_of_stock_variations && $has_in_stock_variations;
}

// Show SALE label first (if applicable)
if ($flash_sale) {
	echo '<span class="product-status-tag flash-sale">FLASH SALE</span>';
} elseif ($is_sold_out) {
	echo '<span class="product-status-tag sold-out">SOLD OUT</span>';
} elseif ($is_on_sale) {
	echo '<span class="product-status-tag sale">SALE</span>';
}

// Show LIMITED STOCK label (if applicable and not already showing SOLD OUT)
if ( $has_limited_stock && !$is_sold_out ) {
	$limited_stock_class = 'limited-stock';
	if ( $is_on_sale || $flash_sale ) {
		$limited_stock_class .= ' has-sale-label';
	}
	echo '<span class="product-status-tag ' . esc_attr( $limited_stock_class ) . '">LIMITED STOCK</span>';
}
?>
