<?php
/**
 * My Account View Order Template
 *
 * @package PrimeFit
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="account-container">
    <div class="account-layout">
        <div class="account-content-section">
            <div class="account-content">
                <div class="dashboard-header">
                    <div class="dashboard-navigation">
                        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="back-to-dashboard">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 12H5"></path>
                                <polyline points="12,19 5,12 12,5"></polyline>
                            </svg>
                            <?php esc_html_e( 'Back to Orders', 'primefit' ); ?>
                        </a>
                    </div>
                </div>
                
                <div class="dashboard-cards">
                    <!-- Order Status Card -->
                    <div class="dashboard-card order-status-card">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 11V7a4 4 0 0 0-8 0v4"></path>
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            </svg>
                        </div>
                        <h3 class="card-title"><?php esc_html_e( 'Order Status', 'primefit' ); ?></h3>
                        <div class="order-status-info">
                    <?php
                    printf(
                        /* translators: 1: order number 2: order date 3: order status */
                                esc_html__( 'ORDER #%1$s WAS PLACED ON %2$s AND IS CURRENTLY %3$s.', 'primefit' ),
                                '<span class="order-number">' . $order->get_order_number() . '</span>',
                                '<span class="order-date">' . strtoupper( wc_format_datetime( $order->get_date_created() ) ) . '</span>',
                                '<span class="order-status">' . strtoupper( wc_get_order_status_name( $order->get_status() ) ) . '</span>'
                            );
                            ?>
                        </div>
                    </div>
                    
                    <!-- Order Details Card -->
                    <div class="dashboard-card order-details-card">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10,9 9,9 8,9"></polyline>
                            </svg>
                        </div>
                        <h3 class="card-title"><?php esc_html_e( 'Order Details', 'primefit' ); ?></h3>
                        
                        <div class="order-items">
                            <?php
                            foreach ( $order->get_items() as $item_id => $item ) :
                                $product = $item->get_product();
                                ?>
                                <div class="order-item">
                                    <div class="item-details">
                                        <?php if ( $product ) : ?>
                                            <a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="item-name">
                                                <?php echo esc_html( $item->get_name() ); ?>
                                            </a>
                                        <?php else : ?>
                                            <span class="item-name"><?php echo esc_html( $item->get_name() ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-total">
                                        <?php echo $order->get_formatted_line_subtotal( $item ); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="order-totals">
                                <div class="total-line">
                                    <span class="total-label"><?php esc_html_e( 'SUBTOTAL:', 'primefit' ); ?></span>
                                    <span class="total-value"><?php echo $order->get_subtotal_to_display(); ?></span>
                                </div>
                                
                                <?php if ( $order->get_total_discount() > 0 ) : ?>
                                    <div class="total-line">
                                        <span class="total-label"><?php esc_html_e( 'DISCOUNT:', 'primefit' ); ?></span>
                                        <span class="total-value">-<?php echo wc_price( $order->get_total_discount() ); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $order->get_shipping_total() > 0 ) : ?>
                                    <div class="total-line">
                                        <span class="total-label"><?php esc_html_e( 'SHIPPING:', 'primefit' ); ?></span>
                                        <span class="total-value"><?php echo $order->get_shipping_to_display(); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $order->get_total_tax() > 0 ) : ?>
                                    <div class="total-line">
                                        <span class="total-label"><?php esc_html_e( 'TAX:', 'primefit' ); ?></span>
                                        <span class="total-value"><?php echo wc_price( $order->get_total_tax() ); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="total-line total-final">
                                    <span class="total-label"><?php esc_html_e( 'TOTAL:', 'primefit' ); ?></span>
                                    <span class="total-value"><?php echo $order->get_formatted_order_total(); ?></span>
                                </div>
                                
                                <div class="total-line">
                                    <span class="total-label"><?php esc_html_e( 'PAYMENT METHOD:', 'primefit' ); ?></span>
                                    <span class="total-value"><?php echo $order->get_payment_method_title(); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing Address Card -->
                    <?php if ( $order->get_formatted_billing_address() ) : ?>
                        <div class="dashboard-card billing-address-card">
                            <div class="card-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </div>
                            <h3 class="card-title"><?php esc_html_e( 'Billing Address', 'primefit' ); ?></h3>
                            <div class="address-details">
                                <?php echo wp_kses_post( $order->get_formatted_billing_address() ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Shipping Address Card -->
                    <?php if ( $order->get_formatted_shipping_address() ) : ?>
                        <div class="dashboard-card shipping-address-card">
                            <div class="card-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </div>
                            <h3 class="card-title"><?php esc_html_e( 'Shipping Address', 'primefit' ); ?></h3>
                            <div class="address-details">
                                <?php echo wp_kses_post( $order->get_formatted_shipping_address() ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Customer Note Card -->
                    <?php if ( $order->get_customer_note() ) : ?>
                        <div class="dashboard-card customer-note-card">
                            <div class="card-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14,2 14,8 20,8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10,9 9,9 8,9"></polyline>
                                </svg>
                            </div>
                            <h3 class="card-title"><?php esc_html_e( 'Order Note', 'primefit' ); ?></h3>
                            <div class="note-content">
                                <?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?>
                            </div>
                    </div>
                <?php endif; ?>
                </div>
                
                <?php do_action( 'woocommerce_view_order', $order->get_id() ); ?>
            </div>
        </div>
    </div>
</div>
