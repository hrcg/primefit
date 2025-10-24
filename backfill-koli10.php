<?php
/**
 * Backfill script to find and track missing "koli10" coupon uses
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h2>Backfilling missing 'koli10' coupon tracking</h2>\n";

global $wpdb;
$table_name = $wpdb->prefix . 'discount_code_tracking';

// Check if HPOS is enabled
$use_hpos = false;
try {
    $wpdb->get_var("SELECT 1 FROM {$wpdb->prefix}wc_orders LIMIT 1");
    $use_hpos = true;
    echo "Using HPOS (High-Performance Order Storage)\n";
} catch (Exception $e) {
    echo "Using traditional order storage\n";
}

// Find orders with "koli10" coupon by checking coupon items
if ($use_hpos) {
    // HPOS query - look for coupon items with koli10 code
    $orders_query = "
        SELECT DISTINCT o.id as order_id, o.date_created_gmt
        FROM {$wpdb->prefix}wc_orders o
        INNER JOIN {$wpdb->prefix}wc_orders_meta om ON o.id = om.order_id 
        WHERE o.status IN ('wc-completed', 'wc-processing')
        AND om.meta_key = '_coupon_codes'
        AND om.meta_value LIKE '%koli10%'
        ORDER BY o.date_created_gmt DESC
    ";
} else {
    // Traditional query - look for coupon items with koli10 code
    $orders_query = "
        SELECT DISTINCT p.ID as order_id, p.post_date as date_created_gmt
        FROM {$wpdb->prefix}posts p
        INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id 
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-processing')
        AND pm.meta_key = '_coupon_codes'
        AND pm.meta_value LIKE '%koli10%'
        ORDER BY p.post_date DESC
    ";
}

$orders = $wpdb->get_results($orders_query);

echo "Found " . count($orders) . " orders with 'koli10' coupon\n\n";

$tracked_count = 0;
$skipped_count = 0;

foreach ($orders as $order_data) {
    $order_id = $order_data->order_id;
    
    // Get order object to check coupon codes properly
    $order = wc_get_order($order_id);
    if (!$order) {
        echo "Order #$order_id: Could not load order object\n";
        continue;
    }
    
    // Get coupon codes using WooCommerce method
    $coupon_codes = $order->get_coupon_codes();
    
    // Check if koli10 is in this order's coupons
    $has_koli10 = false;
    foreach ($coupon_codes as $code) {
        if (strtolower(trim($code)) === 'koli10') {
            $has_koli10 = true;
            break;
        }
    }
    
    if (!$has_koli10) {
        echo "Order #$order_id: No koli10 coupon found (codes: " . implode(', ', $coupon_codes) . ")\n";
        continue;
    }
    
    // Check if already tracked
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE order_id = %d AND LOWER(coupon_code) = %s LIMIT 1",
        $order_id,
        'koli10'
    ));
    
    if ($existing) {
        echo "Order #$order_id: Already tracked\n";
        $skipped_count++;
        continue;
    }
    
    // Get customer email and validate - allow tracking even without valid email
    $email = $order->get_billing_email();
    if (empty($email) || !is_email($email)) {
        // Try to get email from user account if order has a customer
        $user_id = $order->get_user_id();
        if ($user_id > 0) {
            $user = get_user_by('id', $user_id);
            if ($user && is_email($user->user_email)) {
                $email = $user->user_email;
            }
        }
        
        // If still no valid email, use a placeholder for tracking purposes
        if (empty($email) || !is_email($email)) {
            $email = 'no-email@tracked-order-' . $order_id . '.local';
        }
    }
    
    $user_id = $order->get_user_id();
    
    // Calculate savings
    $coupon = new WC_Coupon('koli10');
    $savings_amount = primefit_calculate_coupon_savings($coupon, $order);
    
    // Insert tracking record
    $result = $wpdb->insert(
        $table_name,
        array(
            'coupon_code' => 'koli10',
            'email' => sanitize_email($email),
            'user_id' => intval($user_id),
            'order_id' => intval($order_id),
            'savings_amount' => floatval($savings_amount),
            'ip_address' => '',
            'user_agent' => ''
        ),
        array('%s', '%s', '%d', '%d', '%f', '%s', '%s')
    );
    
    if ($result !== false) {
        echo "Order #$order_id: Tracked successfully (€" . number_format($savings_amount, 2) . " savings)\n";
        $tracked_count++;
    } else {
        echo "Order #$order_id: Failed to track - " . $wpdb->last_error . "\n";
    }
}

echo "\n<h3>Summary:</h3>\n";
echo "New records added: $tracked_count\n";
echo "Already tracked: $skipped_count\n";

// Clear cache
if (function_exists('primefit_clear_discount_stats_cache')) {
    primefit_clear_discount_stats_cache();
    echo "Cache cleared\n";
}

echo "\nDone! Check your coupon analytics now.\n";
?>
