<?php
/**
 * My Account Orders Template
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

// Use WooCommerce's default approach
$current_page = absint( get_query_var( 'orders' ) );
if ( $current_page < 1 ) {
    $current_page = 1;
}

// Get customer orders using WooCommerce's standard method
$customer_orders = wc_get_orders( array(
    'customer' => get_current_user_id(),
    'status'   => array_keys( wc_get_order_statuses() ),
    'limit'    => 10,
    'page'     => $current_page,
    'orderby'  => 'date',
    'order'    => 'DESC',
) );

$has_orders = ! empty( $customer_orders );
?>

<div class="account-container">
    <div class="account-layout">
        <div class="account-content-section">
            <div class="account-content">
                <div class="dashboard-header">
                    <div class="dashboard-navigation">
                        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>" class="back-to-dashboard">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 12H5"></path>
                                <polyline points="12,19 5,12 12,5"></polyline>
                            </svg>
                            <?php esc_html_e( 'Back to Dashboard', 'primefit' ); ?>
                        </a>
                    </div>
                </div>
                
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 11V7a4 4 0 0 0-8 0v4"></path>
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            </svg>
                        </div>
                        <h3 class="card-title"><?php esc_html_e( 'My Orders', 'primefit' ); ?></h3>
                        <p class="card-description"><?php esc_html_e( 'View and manage your orders, see the latest delivery information and track packages in your account.', 'primefit' ); ?></p>
                        
                        <?php if ( $has_orders ) : ?>
                            <div class="woocommerce-orders-table-wrapper">
                                <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
                                    <thead>
                                        <tr>
                                            <?php foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) : ?>
                                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr( $column_id ); ?>">
                                                    <span class="nobr"><?php echo esc_html( $column_name ); ?></span>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    
                                    <tbody>
                                        <?php
                                        foreach ( $customer_orders as $order ) :
                                            $item_count = $order->get_item_count() - $order->get_item_count_refunded();
                                            ?>
                                            <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $order->get_status() ); ?> order">
                                                <?php foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) : ?>
                                                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
                                                        <?php if ( has_action( 'woocommerce_my_account_my_orders_column_' . $column_id ) ) : ?>
                                                            <?php do_action( 'woocommerce_my_account_my_orders_column_' . $column_id, $order ); ?>
                                                        <?php elseif ( 'order-number' === $column_id ) : ?>
                                                            <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                                                                <?php echo esc_html( _x( '#', 'hash before order number', 'primefit' ) . $order->get_order_number() ); ?>
                                                            </a>
                                                        <?php elseif ( 'order-date' === $column_id ) : ?>
                                                            <time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></time>
                                                        <?php elseif ( 'order-status' === $column_id ) : ?>
                                                            <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                                                        <?php elseif ( 'order-total' === $column_id ) : ?>
                                                            <?php
                                                            /* translators: 1: formatted order total 2: total order items */
                                                            echo wp_kses_post( sprintf( _n( '%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'primefit' ), $order->get_formatted_order_total(), $item_count ) );
                                                            ?>
                                                        <?php elseif ( 'order-actions' === $column_id ) : ?>
                                                            <?php
                                                            $actions = wc_get_account_orders_actions( $order );
                                                            
                                                            if ( ! empty( $actions ) ) {
                                                                foreach ( $actions as $key => $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                                                                    echo '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a>';
                                                                }
                                                            }
                                                            ?>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>
                            
                            <?php 
                            // Simple pagination - if we have 10 orders, there might be more
                            if ( count( $customer_orders ) >= 10 ) : ?>
                                <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
                                    <?php if ( 1 !== $current_page ) : ?>
                                        <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'primefit' ); ?></a>
                                    <?php endif; ?>
                                    
                                    <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'primefit' ); ?></a>
                                </div>
                            <?php endif; ?>
                            
                        <?php else : ?>
                            <div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
                                <a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                                    <?php esc_html_e( 'Browse products', 'primefit' ); ?>
                                </a>
                                <?php esc_html_e( 'No order has been made yet.', 'primefit' ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
