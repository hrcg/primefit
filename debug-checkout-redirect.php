<?php
/**
 * Debug Checkout Redirect
 * Temporary file to debug checkout redirect issues
 */

// Only run if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) ) {
    die( 'WooCommerce is not active' );
}

echo '<h1>Checkout Redirect Debug</h1>';

// Check WooCommerce pages
$checkout_page_id = wc_get_page_id( 'checkout' );
$thankyou_page_id = wc_get_page_id( 'thankyou' );
$myaccount_page_id = wc_get_page_id( 'myaccount' );

echo '<h2>WooCommerce Page Settings</h2>';
echo '<p><strong>Checkout Page ID:</strong> ' . $checkout_page_id . '</p>';
echo '<p><strong>Thank You Page ID:</strong> ' . $thankyou_page_id . '</p>';
echo '<p><strong>My Account Page ID:</strong> ' . $myaccount_page_id . '</p>';

// Check if thank you page exists
if ( $thankyou_page_id > 0 ) {
    $thankyou_page = get_post( $thankyou_page_id );
    echo '<p><strong>Thank You Page Status:</strong> ' . ( $thankyou_page ? $thankyou_page->post_status : 'Not found' ) . '</p>';
    echo '<p><strong>Thank You Page URL:</strong> ' . get_permalink( $thankyou_page_id ) . '</p>';
} else {
    echo '<p><strong>Thank You Page:</strong> Not configured</p>';
}

// Check checkout URL
echo '<h2>Checkout URLs</h2>';
echo '<p><strong>Checkout URL:</strong> ' . wc_get_checkout_url() . '</p>';

// Test order received URL
if ( function_exists( 'wc_get_endpoint_url' ) ) {
    $order_received_url = wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() );
    echo '<p><strong>Order Received URL Pattern:</strong> ' . $order_received_url . '</p>';
}

// Check recent orders
echo '<h2>Recent Orders (for testing)</h2>';
$recent_orders = wc_get_orders( array(
    'limit' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
) );

if ( $recent_orders ) {
    foreach ( $recent_orders as $order ) {
        $order_id = $order->get_id();
        $order_key = $order->get_order_key();
        $order_received_url = $order->get_checkout_order_received_url();
        
        echo '<div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">';
        echo '<p><strong>Order #' . $order_id . '</strong></p>';
        echo '<p><strong>Status:</strong> ' . $order->get_status() . '</p>';
        echo '<p><strong>Order Key:</strong> ' . $order_key . '</p>';
        echo '<p><strong>Order Received URL:</strong> <a href="' . $order_received_url . '" target="_blank">' . $order_received_url . '</a></p>';
        echo '</div>';
    }
} else {
    echo '<p>No recent orders found.</p>';
}

// Check if custom templates exist
echo '<h2>Template Files</h2>';
$theme_dir = get_template_directory();
$templates_to_check = [
    'woocommerce/checkout/thankyou.php',
    'woocommerce/checkout/order-received.php',
    'woocommerce/myaccount/payment-summary.php',
];

foreach ( $templates_to_check as $template ) {
    $file_path = $theme_dir . '/' . $template;
    $exists = file_exists( $file_path );
    echo '<p><strong>' . $template . ':</strong> ' . ( $exists ? '✅ Exists' : '❌ Missing' ) . '</p>';
}

// Check WooCommerce settings
echo '<h2>WooCommerce Settings</h2>';
$checkout_settings = get_option( 'woocommerce_checkout_settings', array() );
echo '<p><strong>Checkout Settings:</strong></p>';
echo '<pre>' . print_r( $checkout_settings, true ) . '</pre>';

?>