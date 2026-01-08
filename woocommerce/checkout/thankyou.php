<?php
/**
 * Thankyou page - Custom PrimeFit styling
 *
 * This template uses the same styling as the payment summary page
 * for a consistent order confirmation experience.
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 8.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! $order ) {
    // If no order found, show error message
    ?>
    <div class="payment-summary-container">
        <div class="payment-summary-header">
            <div class="payment-summary-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <h1 class="payment-summary-title"><?php esc_html_e( 'Order Not Found', 'primefit' ); ?></h1>
            <p class="payment-summary-subtitle">
                <?php esc_html_e( 'We could not find the order you are looking for.', 'primefit' ); ?>
            </p>
        </div>
        <div class="payment-summary-actions">
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button button--primary">
                <?php esc_html_e( 'Continue Shopping', 'primefit' ); ?>
            </a>
        </div>
    </div>
    <?php
    return;
}

// Get order data
$order_id = $order->get_id();
$order_number = $order->get_order_number();
$order_date = $order->get_date_created();
$order_status = $order->get_status();
$order_total = $order->get_total();
$payment_method = $order->get_payment_method_title();
$billing_address = $order->get_formatted_billing_address();
$shipping_address = $order->get_formatted_shipping_address();

// Get order items
$order_items = $order->get_items();
$item_count = $order->get_item_count();

// Bundle grouping (if this order contains PrimeFit bundle items).
$bundle_groups = array(); // gid => array( name, items[], base_total, charged_total )
$non_bundle_items = array(); // item_id => item
$display_items_total = 0.0; // What we display as "items total" (bundle items use base/original).
foreach ( $order_items as $item_id => $item ) {
	$gid = (string) $item->get_meta( '_primefit_bundle_group_id', true );
	if ( $gid === '' ) {
		$non_bundle_items[ $item_id ] = $item;
		$display_items_total += (float) $item->get_subtotal();
		continue;
	}

	$bundle_name = (string) $item->get_meta( '_primefit_bundle_product_name', true );
	if ( ! isset( $bundle_groups[ $gid ] ) ) {
		$bundle_groups[ $gid ] = array(
			'name' => $bundle_name,
			'items' => array(),
			'base_total' => 0.0,
			'charged_total' => 0.0,
		);
	}

	$qty = (int) $item->get_quantity();
	$base_unit = (float) $item->get_meta( '_primefit_bundle_child_base_price', true );
	$base_line_total = $base_unit > 0 ? ( $base_unit * max( 1, $qty ) ) : 0.0;

	$bundle_groups[ $gid ]['items'][ $item_id ] = $item;
	$bundle_groups[ $gid ]['base_total'] += $base_line_total > 0 ? $base_line_total : (float) $item->get_subtotal();
	$bundle_groups[ $gid ]['charged_total'] += (float) $item->get_subtotal();
	$display_items_total += $base_line_total > 0 ? $base_line_total : (float) $item->get_subtotal();
}

// Calculate total bundle savings for this order.
$bundle_savings_total = 0.0;
foreach ( $bundle_groups as $g ) {
	$s = (float) $g['base_total'] - (float) $g['charged_total'];
	if ( $s > 0 ) {
		$bundle_savings_total += $s;
	}
}

// Get shipping method
$shipping_methods = $order->get_shipping_methods();
$shipping_method = '';
if ( ! empty( $shipping_methods ) ) {
    $shipping_method = reset( $shipping_methods )->get_method_title();
}

// Get order notes (if any)
$order_notes = $order->get_customer_order_notes();

// Check if order failed
if ( $order->has_status( 'failed' ) ) :
    ?>
    <div class="payment-summary-container">
        <div class="payment-summary-header">
            <div class="payment-summary-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <h1 class="payment-summary-title"><?php esc_html_e( 'Payment Failed', 'primefit' ); ?></h1>
            <p class="payment-summary-subtitle">
                <?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'primefit' ); ?>
            </p>
        </div>
        <div class="payment-summary-actions">
            <a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button button--primary">
                <?php esc_html_e( 'Try Again', 'primefit' ); ?>
            </a>
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button button--secondary">
                    <?php esc_html_e( 'My Account', 'primefit' ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return;
endif;
?>

<div class="payment-summary-container">
    <div class="payment-summary-header">
        <div class="payment-summary-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22,4 12,14.01 9,11.01"></polyline>
            </svg>
        </div>
        <h1 class="payment-summary-title"><?php esc_html_e( 'Order Confirmed', 'primefit' ); ?></h1>
        <p class="payment-summary-subtitle">
            <?php
            printf(
                /* translators: 1: order number 2: order date */
                esc_html__( 'Thank you for your order #%1$s placed on %2$s', 'primefit' ),
                '<strong>' . esc_html( $order_number ) . '</strong>',
                '<strong>' . esc_html( wc_format_datetime( $order_date ) ) . '</strong>'
            );
            ?>
        </p>
    </div>

    <div class="payment-summary-content">
        <!-- Order Status Card -->
        <div class="payment-summary-card order-status-card animate-in">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e( 'Order Status', 'primefit' ); ?></h3>
            </div>
            <div class="card-content">
                <div class="status-badge status-<?php echo esc_attr( $order_status ); ?>">
                    <?php echo esc_html( wc_get_order_status_name( $order_status ) ); ?>
                </div>
                <p class="status-description">
                    <?php
                    switch ( $order_status ) {
                        case 'completed':
                            esc_html_e( 'Your order has been completed and delivered.', 'primefit' );
                            break;
                        case 'processing':
                            esc_html_e( 'Your order is being processed and will be shipped soon.', 'primefit' );
                            break;
                        case 'on-hold':
                            esc_html_e( 'Your order is on hold pending payment confirmation.', 'primefit' );
                            break;
                        case 'pending':
                            esc_html_e( 'Your order is pending payment confirmation.', 'primefit' );
                            break;
                        default:
                            esc_html_e( 'Your order status will be updated as it progresses.', 'primefit' );
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- Order Items Card -->
        <div class="payment-summary-card order-items-card animate-in">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e( 'Order Items', 'primefit' ); ?></h3>
                <span class="item-count"><?php printf( esc_html( _n( '%d item', '%d items', $item_count, 'primefit' ) ), $item_count ); ?></span>
            </div>
            <div class="card-content">
                <div class="order-items-list">
					<?php
					$print_order_item = function( WC_Order_Item_Product $item ) {
						$product = $item->get_product();
						$product_name = $item->get_name();
						$quantity = (int) $item->get_quantity();
						$product_image = $product ? $product->get_image( 'thumbnail' ) : '';

						// For bundle items, show the original/base (non-discounted) price.
						$base_unit = (float) $item->get_meta( '_primefit_bundle_child_base_price', true );
						$item_total = (float) $item->get_total();
						$display_total = $base_unit > 0 ? ( $base_unit * max( 1, $quantity ) ) : $item_total;
						?>
						<div class="order-item">
							<div class="item-image">
								<?php echo $product_image; ?>
							</div>
							<div class="item-details">
								<h4 class="item-name"><?php echo esc_html( $product_name ); ?></h4>
								<div class="item-meta">
									<span class="item-quantity"><?php printf( esc_html__( 'Qty: %d', 'primefit' ), $quantity ); ?></span>
									<?php if ( $item->get_variation_id() ) : ?>
										<div class="item-variation">
											<?php
											$variation_data = $item->get_meta_data();
											$variation_attributes = array();

											foreach ( $variation_data as $meta ) {
												if ( $meta->key && strpos( $meta->key, 'attribute_' ) === 0 ) {
													$attribute_name = str_replace( 'attribute_', '', $meta->key );
													$attribute_name = wc_attribute_label( $attribute_name );
													$variation_attributes[] = $attribute_name . ': ' . $meta->value;
												}
											}

											if ( ! empty( $variation_attributes ) ) {
												echo wp_kses_post( implode( ', ', $variation_attributes ) );
											}
											?>
										</div>
									<?php endif; ?>
								</div>
							</div>
							<div class="item-total">
								<?php echo wc_price( $display_total ); ?>
							</div>
						</div>
						<?php
					};
					?>

					<?php foreach ( $bundle_groups as $gid => $group ) : ?>
						<div class="order-items-bundle-header">
							<?php
							printf(
								/* translators: 1: bundle name */
								esc_html__( 'YOU GOT BUNDLE "%s"', 'primefit' ),
								esc_html( (string) $group['name'] )
							);
							?>
						</div>
						<?php foreach ( $group['items'] as $item ) : ?>
							<?php $print_order_item( $item ); ?>
						<?php endforeach; ?>
					<?php endforeach; ?>

					<?php foreach ( $non_bundle_items as $item ) : ?>
						<?php $print_order_item( $item ); ?>
					<?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Order Summary Card -->
        <div class="payment-summary-card order-summary-card animate-in">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e( 'Order Summary', 'primefit' ); ?></h3>
            </div>
            <div class="card-content">
                <div class="summary-line">
                    <span class="summary-label"><?php esc_html_e( 'Items total', 'primefit' ); ?></span>
                    <span class="summary-value"><?php echo wc_price( $display_items_total ); ?></span>
                </div>

				<?php if ( $bundle_savings_total > 0 ) : ?>
					<div class="summary-line discount">
						<span class="summary-label"><?php esc_html_e( 'Bundle discount', 'primefit' ); ?></span>
						<span class="summary-value">-<?php echo wc_price( $bundle_savings_total ); ?></span>
					</div>
					<div class="summary-line">
						<span class="summary-label"><?php esc_html_e( 'Subtotal', 'primefit' ); ?></span>
						<span class="summary-value"><?php echo wc_price( $order->get_subtotal() ); ?></span>
					</div>
					<div class="summary-line">
						<span class="summary-label"><?php esc_html_e( 'Bundle savings', 'primefit' ); ?></span>
						<span class="summary-value"><?php echo esc_html( sprintf( __( 'You saved %s by getting this bundle', 'primefit' ), strip_tags( wc_price( $bundle_savings_total ) ) ) ); ?></span>
					</div>
				<?php endif; ?>

                <?php if ( $order->get_total_discount() > 0 ) : ?>
                    <div class="summary-line discount">
                        <span class="summary-label"><?php esc_html_e( 'Discount', 'primefit' ); ?></span>
                        <span class="summary-value">-<?php echo wc_price( $order->get_total_discount() ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( $order->get_total_tax() > 0 ) : ?>
                    <div class="summary-line">
                        <span class="summary-label"><?php esc_html_e( 'Tax', 'primefit' ); ?></span>
                        <span class="summary-value"><?php echo wc_price( $order->get_total_tax() ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( $order->get_total_shipping() > 0 ) : ?>
                    <div class="summary-line">
                        <span class="summary-label"><?php esc_html_e( 'Shipping', 'primefit' ); ?></span>
                        <span class="summary-value"><?php echo wc_price( $order->get_total_shipping() ); ?></span>
                    </div>
                <?php endif; ?>

                <div class="summary-line total">
                    <span class="summary-label"><?php esc_html_e( 'Total', 'primefit' ); ?></span>
                    <span class="summary-value"><?php echo wc_price( $order_total ); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Information Card -->
        <div class="payment-summary-card payment-info-card animate-in">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e( 'Payment Information', 'primefit' ); ?></h3>
            </div>
            <div class="card-content">
                <div class="payment-method">
                    <div class="payment-method-details">
                        <h4 class="payment-method-name"><?php echo esc_html( $payment_method ); ?></h4>
                        <p class="payment-method-description">
                            <?php esc_html_e( 'Payment processed successfully', 'primefit' ); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Information Card -->
        <?php if ( $shipping_address ) : ?>
        <div class="payment-summary-card shipping-info-card animate-in">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e( 'Shipping Information', 'primefit' ); ?></h3>
            </div>
            <div class="card-content">
                <div class="shipping-address">
                    <div class="address-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="address-details">
                        <h4 class="address-title"><?php esc_html_e( 'Delivery Address', 'primefit' ); ?></h4>
                        <div class="address-content">
                            <?php echo wp_kses_post( $shipping_address ); ?>
                        </div>
                        <?php if ( $shipping_method ) : ?>
                            <div class="shipping-method">
                                <strong><?php esc_html_e( 'Shipping Method:', 'primefit' ); ?></strong>
                                <?php echo esc_html( $shipping_method ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Notes Card -->
        <?php if ( $order->get_customer_note() ) : ?>
        <div class="payment-summary-card order-notes-card animate-in">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e( 'Order Notes', 'primefit' ); ?></h3>
            </div>
            <div class="card-content">
                <div class="order-note">
                    <p><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="payment-summary-actions">
        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="button button--secondary">
            <?php esc_html_e( 'View All Orders', 'primefit' ); ?>
        </a>
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button button--primary">
            <?php esc_html_e( 'Continue Shopping', 'primefit' ); ?>
        </a>
                    <!--
        <button class="print-order-btn button button--secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6,9 6,2 18,2 18,9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <?php esc_html_e( 'Print Order', 'primefit' ); ?>
        </button>
        -->
    </div>

    <!-- Order Tracking Information
    <?php if ( $order_status === 'processing' || $order_status === 'shipped' ) : ?>
    <div class="payment-summary-tracking">
        <div class="tracking-info">
            <h4><?php esc_html_e( 'Track Your Order', 'primefit' ); ?></h4>
            <p><?php esc_html_e( 'You will receive tracking information via email once your order ships.', 'primefit' ); ?></p>
        </div>
    </div>
    <?php endif; ?>
    -->
</div>