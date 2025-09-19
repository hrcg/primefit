<?php
/**
 * Payment Summary Template
 * 
 * Custom payment summary displayed after order completion
 * Follows PrimeFit theme design patterns and dark/light mode compatibility
 *
 * @package PrimeFit
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Get the order object
$order = wc_get_order( get_query_var( 'view-order' ) );

if ( ! $order ) {
    // If no order found, try to get from global
    global $wp_query;
    if ( isset( $wp_query->query_vars['view-order'] ) ) {
        $order = wc_get_order( $wp_query->query_vars['view-order'] );
    }
}

if ( ! $order ) {
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

// Get shipping method
$shipping_methods = $order->get_shipping_methods();
$shipping_method = '';
if ( ! empty( $shipping_methods ) ) {
    $shipping_method = reset( $shipping_methods )->get_method_title();
}

// Get order notes (if any)
$order_notes = $order->get_customer_order_notes();
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
        <div class="payment-summary-card order-status-card">
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
        <div class="payment-summary-card order-items-card">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e( 'Order Items', 'primefit' ); ?></h3>
                <span class="item-count"><?php printf( esc_html( _n( '%d item', '%d items', $item_count, 'primefit' ) ), $item_count ); ?></span>
            </div>
            <div class="card-content">
                <div class="order-items-list">
                    <?php foreach ( $order_items as $item_id => $item ) : ?>
                        <?php
                        $product = $item->get_product();
                        $product_name = $item->get_name();
                        $quantity = $item->get_quantity();
                        $item_total = $item->get_total();
                        $product_image = $product ? $product->get_image( 'thumbnail' ) : '';
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
                                                if ( strpos( $meta->key, 'attribute_' ) === 0 ) {
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
                                <?php echo wc_price( $item_total ); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Order Summary Card -->
        <div class="payment-summary-card order-summary-card">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e( 'Order Summary', 'primefit' ); ?></h3>
            </div>
            <div class="card-content">
                <div class="summary-line">
                    <span class="summary-label"><?php esc_html_e( 'Subtotal', 'primefit' ); ?></span>
                    <span class="summary-value"><?php echo wc_price( $order->get_subtotal() ); ?></span>
                </div>

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
        <div class="payment-summary-card payment-info-card">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e( 'Payment Information', 'primefit' ); ?></h3>
            </div>
            <div class="card-content">
                <div class="payment-method">
                    <div class="payment-method-icon">
                        <?php
                        // Display payment method icon based on method
                        $payment_method_lower = strtolower( $payment_method );
                        if ( strpos( $payment_method_lower, 'card' ) !== false || strpos( $payment_method_lower, 'credit' ) !== false ) {
                            echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>';
                        } elseif ( strpos( $payment_method_lower, 'paypal' ) !== false ) {
                            echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.5 8h9l-1 8H8.5l1-8z"></path><path d="M6.5 8H5.5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h1"></path></svg>';
                        } else {
                            echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>';
                        }
                        ?>
                    </div>
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
        <div class="payment-summary-card shipping-info-card">
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
        <div class="payment-summary-card order-notes-card">
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
    </div>

    <!-- Order Tracking Information -->
    <?php if ( $order_status === 'processing' || $order_status === 'shipped' ) : ?>
    <div class="payment-summary-tracking">
        <div class="tracking-info">
            <h4><?php esc_html_e( 'Track Your Order', 'primefit' ); ?></h4>
            <p><?php esc_html_e( 'You will receive tracking information via email once your order ships.', 'primefit' ); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>
