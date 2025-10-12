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
	// Calculate sale percentage for display
	$percentage = 0;

	// Handle different product types for percentage calculation
	if ( $product->is_type( 'simple' ) ) {
		// For simple products, use direct price comparison
		$regular_price = $product->get_regular_price();
		$sale_price = $product->get_sale_price();

		if ( $regular_price && $sale_price ) {
			$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
		}
	} elseif ( $product->is_type( 'variable' ) ) {
		// For variable products, find the highest percentage discount across variations
		$variations = $product->get_available_variations();
		$max_percentage = 0;

		foreach ( $variations as $variation_data ) {
			$variation = wc_get_product( $variation_data['variation_id'] );
			if ( $variation && $variation->is_on_sale() ) {
				$var_regular_price = $variation->get_regular_price();
				$var_sale_price = $variation->get_sale_price();

				if ( $var_regular_price && $var_sale_price ) {
					$var_percentage = round( ( ( $var_regular_price - $var_sale_price ) / $var_regular_price ) * 100 );
					if ( $var_percentage > $max_percentage ) {
						$max_percentage = $var_percentage;
					}
				}
			}
		}

		$percentage = $max_percentage;
	}

	$sale_text = 'SALE';
	if ( $percentage > 0 ) {
		$sale_text .= ' - ' . $percentage . '% OFF';
	}
	echo '<span class="product-status-tag sale">' . esc_html( $sale_text ) . '</span>';
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
