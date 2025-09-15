<?php
/**
 * Mini Cart Template
 *
 * @package PrimeFit
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' ); ?>

<?php if ( ! WC()->cart->is_empty() ) : ?>

	<ul class="woocommerce-mini-cart__items">
		<?php
		do_action( 'woocommerce_before_mini_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
				$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
				$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				?>
				<li class="woocommerce-mini-cart__item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
					<?php if ( empty( $product_permalink ) ) : ?>
						<?php echo $thumbnail; ?>
					<?php else : ?>
						<a href="<?php echo esc_url( $product_permalink ); ?>">
							<?php echo $thumbnail; ?>
						</a>
					<?php endif; ?>
					
					<div class="woocommerce-mini-cart__item-details">
						<?php if ( empty( $product_permalink ) ) : ?>
							<?php echo wp_kses_post( $product_name ); ?>
						<?php else : ?>
							<a href="<?php echo esc_url( $product_permalink ); ?>" class="woocommerce-mini-cart__item-name">
								<?php echo wp_kses_post( $product_name ); ?>
							</a>
						<?php endif; ?>
						
						<?php if ( ! empty( $cart_item['variation'] ) ) : ?>
							<div class="woocommerce-mini-cart__item-variation">
								<?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
							</div>
						<?php endif; ?>
						
						<div class="woocommerce-mini-cart__item-price">
							<?php echo $product_price; ?>
						</div>
						
						<div class="woocommerce-mini-cart__item-quantity">
							<div class="quantity">
								<button type="button" class="minus" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">-</button>
								<input type="number" value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" min="1" max="<?php echo esc_attr( $_product->get_max_purchase_quantity() ); ?>" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
								<button type="button" class="plus" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">+</button>
							</div>
							<a href="#" class="woocommerce-mini-cart__item-remove remove_from_cart_button" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" data-product_id="<?php echo esc_attr( $product_id ); ?>" aria-label="<?php esc_attr_e( 'Remove this item', 'woocommerce' ); ?>">
								<?php esc_html_e( 'Remove', 'woocommerce' ); ?>
							</a>
						</div>
					</div>
				</li>
				<?php
			}
		}

		do_action( 'woocommerce_mini_cart_contents' );
		?>
	</ul>

	<div class="woocommerce-mini-cart__total">
		<strong><?php esc_html_e( 'Subtotal:', 'woocommerce' ); ?></strong>
		<?php echo WC()->cart->get_cart_subtotal(); ?>
	</div>

	<div class="woocommerce-mini-cart__buttons">
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="button wc-backward">
			<?php esc_html_e( 'View cart', 'woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button checkout">
			<?php esc_html_e( 'Checkout', 'woocommerce' ); ?>
		</a>
	</div>

	<?php
	// You May Also Like Section
	$cross_sells = WC()->cart->get_cross_sells();
	if ( ! empty( $cross_sells ) ) :
		$cross_sell_products = wc_get_products( array(
			'include' => $cross_sells,
			'limit'   => 2,
		) );
		
		if ( ! empty( $cross_sell_products ) ) :
		?>
		<div class="cart-recommendations">
			<h3><?php esc_html_e( 'You may also like', 'primefit' ); ?></h3>
			<ul class="products">
				<?php foreach ( $cross_sell_products as $product ) : ?>
					<li class="product">
						<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
							<?php echo $product->get_image( 'thumbnail' ); ?>
							<span class="product-title"><?php echo esc_html( $product->get_name() ); ?></span>
							<span class="price"><?php echo $product->get_price_html(); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		endif;
	endif;
	?>

	<div class="cart-checkout-summary">
		<div class="shipping-info">
			<?php esc_html_e( 'Shipping & taxes calculated at checkout', 'primefit' ); ?>
		</div>
	</div>

<?php else : ?>

	<p class="woocommerce-mini-cart__empty-message">
		<?php esc_html_e( 'No products in the cart.', 'woocommerce' ); ?>
	</p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
