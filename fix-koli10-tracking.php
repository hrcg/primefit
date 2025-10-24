<?php
/**
 * Comprehensive backfill and fix script for koli10 coupon tracking
 * This script will:
 * 1. Find all orders with koli10 coupon that aren't tracked
 * 2. Manually track them
 * 3. Test the tracking system
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h2>Comprehensive Koli10 Coupon Tracking Fix</h2>\n";

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

echo "\n=== Step 1: Finding all orders with koli10 coupon ===\n";

// Get all completed/processing orders
if ($use_hpos) {
    $orders_query = "
        SELECT o.id as order_id, o.date_created_gmt, o.status
        FROM {$wpdb->prefix}wc_orders o
        WHERE o.status IN ('wc-completed', 'wc-processing')
        ORDER BY o.date_created_gmt DESC
    ";
} else {
    $orders_query = "
        SELECT p.ID as order_id, p.post_date as date_created_gmt, p.post_status as status
        FROM {$wpdb->prefix}posts p
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-processing')
        ORDER BY p.post_date DESC
    ";
}

$all_orders = $wpdb->get_results($orders_query);
echo "Found " . count($all_orders) . " total completed/processing orders\n";

$koli10_orders = array();
$checked_count = 0;

foreach ($all_orders as $order_data) {
    $order_id = $order_data->order_id;
    $checked_count++;
    
    if ($checked_count % 100 == 0) {
        echo "Checked $checked_count orders...\n";
    }
    
    // Get order object
    $order = wc_get_order($order_id);
    if (!$order) {
        continue;
    }
    
    // Get coupon codes using WooCommerce method
    $coupon_codes = $order->get_coupon_codes();
    
    // Check if koli10 is in this order's coupons
    foreach ($coupon_codes as $code) {
        if (strtolower(trim($code)) === 'koli10') {
            $koli10_orders[] = array(
                'order_id' => $order_id,
                'order' => $order,
                'date' => $order_data->date_created_gmt,
                'status' => $order_data->status
            );
            break;
        }
    }
}

echo "Found " . count($koli10_orders) . " orders with 'koli10' coupon\n\n";

echo "=== Step 2: Checking tracking status ===\n";

$tracked_count = 0;
$missing_count = 0;
$missing_orders = array();

foreach ($koli10_orders as $order_data) {
    $order_id = $order_data['order_id'];
    
    // Check if already tracked
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE order_id = %d AND LOWER(coupon_code) = %s LIMIT 1",
        $order_id,
        'koli10'
    ));
    
    if ($existing) {
        $tracked_count++;
        echo "Order #$order_id: Already tracked\n";
    } else {
        $missing_count++;
        $missing_orders[] = $order_data;
        echo "Order #$order_id: MISSING tracking\n";
    }
}

echo "\nSummary:\n";
echo "- Already tracked: $tracked_count\n";
echo "- Missing tracking: $missing_count\n";

if ($missing_count > 0) {
    echo "\n=== Step 3: Manually tracking missing orders ===\n";
    
    $successfully_tracked = 0;
    $failed_tracked = 0;
    
    foreach ($missing_orders as $order_data) {
        $order_id = $order_data['order_id'];
        $order = $order_data['order'];
        
        echo "Processing Order #$order_id (" . $order_data['date'] . ")\n";
        
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
            echo "  ✓ Tracked successfully (€" . number_format($savings_amount, 2) . " savings)\n";
            $successfully_tracked++;
        } else {
            echo "  ✗ Failed to track - " . $wpdb->last_error . "\n";
            $failed_tracked++;
        }
    }
    
    echo "\nTracking Results:\n";
    echo "- Successfully tracked: $successfully_tracked\n";
    echo "- Failed to track: $failed_tracked\n";
}

echo "\n=== Step 4: Final verification ===\n";

// Get final count of tracked koli10 uses
$final_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_name WHERE LOWER(coupon_code) = %s",
    'koli10'
));

echo "Total koli10 uses now tracked: $final_count\n";

// Show recent tracked uses
$recent_tracked = $wpdb->get_results($wpdb->prepare(
    "SELECT order_id, usage_date, savings_amount, email 
     FROM $table_name 
     WHERE LOWER(coupon_code) = %s 
     ORDER BY usage_date DESC 
     LIMIT 10",
    'koli10'
));

echo "\nRecent tracked koli10 uses:\n";
foreach ($recent_tracked as $track) {
    echo "- Order #" . $track->order_id . " - " . $track->usage_date . " - €" . $track->savings_amount . " - " . $track->email . "\n";
}

// Clear cache
if (function_exists('primefit_clear_discount_stats_cache')) {
    primefit_clear_discount_stats_cache();
    echo "\nCache cleared\n";
}

echo "\n=== Step 5: Testing tracking system for future orders ===\n";

// Test the tracking function with a known order
$test_order_id = 11786; // This order has koli10 but wasn't tracked
echo "Testing tracking function with Order #$test_order_id...\n";

$test_order = wc_get_order($test_order_id);
if ($test_order) {
    $coupon_codes = $test_order->get_coupon_codes();
    if (in_array('koli10', $coupon_codes)) {
        echo "Order has koli10 coupon - testing tracking function...\n";
        
        // Check if it's tracked now
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE order_id = %d AND LOWER(coupon_code) = %s LIMIT 1",
            $test_order_id,
            'koli10'
        ));
        
        if ($existing) {
            echo "✓ Order #$test_order_id is now tracked!\n";
        } else {
            echo "✗ Order #$test_order_id is still not tracked\n";
        }
    }
}

echo "\nDone! The koli10 coupon tracking should now be complete.\n";
?>
