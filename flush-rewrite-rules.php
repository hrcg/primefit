<?php
/**
 * Flush Rewrite Rules
 * Run this once to register the payment-summary endpoint
 */

// Include WordPress
require_once( dirname( __FILE__ ) . '/wp-config.php' );

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    die( 'Access denied. Please log in as an administrator.' );
}

echo '<h1>Flushing Rewrite Rules</h1>';

// Flush rewrite rules
flush_rewrite_rules();

echo '<p>✅ Rewrite rules flushed successfully!</p>';

// Test the endpoint
$payment_summary_url = wc_get_account_endpoint_url( 'payment-summary' );
echo '<p><strong>Payment Summary URL:</strong> <a href="' . esc_url( $payment_summary_url ) . '">' . esc_url( $payment_summary_url ) . '</a></p>';

// Check if endpoint is registered
global $wp_rewrite;
echo '<h2>Registered Endpoints</h2>';
echo '<pre>';
print_r( $wp_rewrite->endpoints );
echo '</pre>';

echo '<p><strong>Next steps:</strong></p>';
echo '<ol>';
echo '<li>Go to <a href="' . admin_url( 'options-permalink.php' ) . '">Settings > Permalinks</a> and click "Save Changes" to flush rewrite rules via admin</li>';
echo '<li>Test placing an order to see if it redirects to the payment summary</li>';
echo '<li>Check the <a href="' . esc_url( $payment_summary_url ) . '">Payment Summary page</a> in My Account</li>';
echo '</ol>';

?>