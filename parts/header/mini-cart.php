<?php
/**
 * Mini Cart Component
 * 
 * Usage: get_template_part('parts/header/mini-cart');
 */

if ( ! function_exists( 'woocommerce_mini_cart' ) ) {
	return;
}
?>

<div data-behavior="click">
	<button class="cart-toggle" type="button" aria-expanded="false" aria-controls="mini-cart-panel" aria-label="Open cart">
		CART (<span class="count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span>)
	</button>
	<div class="cart-overlay"></div>
	<div id="mini-cart-panel" class="mini-cart-panel" hidden>
		<div class="cart-header">
			<h3>Cart</h3>
			<button class="cart-close" type="button" aria-label="Close cart">×</button>
		</div>
		<div class="cart-content">
			<?php woocommerce_mini_cart(); ?>
		</div>
	</div>
</div>
