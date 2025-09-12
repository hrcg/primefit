<?php
/**
 * Product Price Template
 * 
 * Displays product pricing with special handling for sold out products
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product ) {
	return;
}

$is_sold_out = !$product->is_in_stock();

if ( $is_sold_out ) : ?>
	<span class="sold-out-text">SOLD OUT</span>
<?php else : ?>
	<span class="price">
		<?php echo $product->get_price_html(); ?>
	</span>
<?php endif; ?>
