<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/mini-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' ); ?>

<?php if ( ! WC()->cart->is_empty() ) : ?>

	<ul class="woocommerce-mini-cart cart_list product_list_widget <?php echo esc_attr( $args['list_class'] ); ?>">
		<?php
		do_action( 'woocommerce_before_mini_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
			$is_bundle_child = ! empty( $cart_item['primefit_bundle_group_id'] );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				/**
				 * This filter is documented in woocommerce/templates/cart/cart.php.
				 *
				 * @since 2.1.0
				 */
				$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
				$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
				$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				?>
				<li class="woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?><?php echo $is_bundle_child ? ' primefit-bundle-child' : ''; ?>">
					
					<?php echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf( '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">REMOVE</a>', esc_url( wc_get_cart_remove_url( $cart_item_key ) ), esc_attr__( 'Remove this item', 'woocommerce' ), esc_attr( $product_id ), esc_attr( $cart_item_key ), esc_attr( $_product->get_sku() ) ), $cart_item_key ); ?>
					
					<div class="woocommerce-mini-cart__item-content">
						<?php if ( empty( $product_permalink ) ) : ?>
							<?php echo $thumbnail; ?>
						<?php else : ?>
							<a href="<?php echo esc_url( $product_permalink ); ?>">
								<?php echo $thumbnail; ?>
							</a>
						<?php endif; ?>

						<div class="woocommerce-mini-cart__item-details">
							<?php if ( empty( $product_permalink ) ) : ?>
								<span class="woocommerce-mini-cart__item-name"><?php echo wp_kses_post( $product_name ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( $product_permalink ); ?>" class="woocommerce-mini-cart__item-name"><?php echo wp_kses_post( $product_name ); ?></a>
							<?php endif; ?>

							<?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>

							<div class="woocommerce-mini-cart__item-quantity">
								<?php if ( $is_bundle_child ) : ?>
									<div class="quantity quantity--bundle" aria-label="<?php esc_attr_e( 'Bundle quantity is fixed per item', 'primefit' ); ?>">
										<span class="qty-text">×<?php echo esc_html( (int) $cart_item['quantity'] ); ?></span>
									</div>
								<?php else : ?>
									<div class="quantity">
										<button type="button" class="minus" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Decrease quantity', 'primefit' ); ?>"></button>
										<input
											type="number"
											class="qty"
											value="<?php echo esc_attr( $cart_item['quantity'] ); ?>"
											min="1"
											max="<?php echo esc_attr( $_product->get_max_purchase_quantity() > 0 ? $_product->get_max_purchase_quantity() : 999 ); ?>"
											data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
											data-original-value="<?php echo esc_attr( $cart_item['quantity'] ); ?>"
											step="1"
											inputmode="numeric"
											readonly
										>
										<button type="button" class="plus" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Increase quantity', 'primefit' ); ?>"></button>
									</div>
								<?php endif; ?>
								<span class="quantity-price"><?php echo $product_price; ?></span>
							</div>
						</div>
					</div>
				</li>
				<?php
			}
		}

		do_action( 'woocommerce_mini_cart_contents' );
		?>
	</ul>

	<?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

	<p class="woocommerce-mini-cart__buttons buttons">
		<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button checkout wc-forward">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px; margin-bottom: -3px;">
				<path d="M18 8H17V6C17 3.24 14.76 1 12 1S7 3.24 7 6V8H6C4.9 8 4 8.9 4 10V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V10C20 8.9 19.1 8 18 8ZM12 17C10.9 17 10 16.1 10 15S10.9 13 12 13S14 13.9 14 15S13.1 17 12 17ZM15.1 8H8.9V6C8.9 4.29 10.29 2.9 12 2.9S15.1 4.29 15.1 6V8Z" fill="currentColor"/>
			</svg>
			Checkout Securely
		</a>
	</p>

<?php else : ?>

	<p class="woocommerce-mini-cart__empty-message"><?php esc_html_e( 'No products in the cart.', 'woocommerce' ); ?></p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
