<?php
/**
 * Product Status Badge Template
 * 
 * Displays product status badges (Flash Sale, Sale, Sold Out)
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

// Determine status
$status_tag = '';
$status_class = '';

if ($flash_sale) {
	$status_tag = 'FLASH SALE';
	$status_class = 'flash-sale';
} elseif ($is_sold_out) {
	$status_tag = 'SOLD OUT';
	$status_class = 'sold-out';
} elseif ($is_on_sale) {
	$status_tag = 'SALE';
	$status_class = 'sale';
}

if ( $status_tag ) : ?>
	<span class="product-status-tag <?php echo esc_attr( $status_class ); ?>">
		<?php echo esc_html( $status_tag ); ?>
	</span>
<?php endif; ?>
