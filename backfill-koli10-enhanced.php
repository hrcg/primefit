<?php
/**
 * Enhanced backfill script to find and track missing "koli10" coupon uses
 * Uses multiple methods to find orders with koli10 coupon
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h2>Enhanced Backfilling missing 'koli10' coupon tracking</h2>\n";

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

// Method 1: Get all completed/processing orders and check them individually
echo "\n=== Method 1: Checking all orders individually ===\n";

if ($use_hpos) {
    $orders_query = "
        SELECT o.id as order_id, o.date_created_gmt
        FROM {$wpdb->prefix}wc_orders o
        WHERE o.status IN ('wc-completed', 'wc-processing')
        ORDER BY o.date_created_gmt DESC
    ";
} else {
    $orders_query = "
        SELECT p.ID as order_id, p.post_date as date_created_gmt
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
    
    if ($checked_count % 50 == 0) {
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
                'date' => $order_data->date_created_gmt
            );
            break;
        }
    }
}

echo "Found " . count($koli10_orders) . " orders with 'koli10' coupon\n\n";

// Method 2: Check meta fields for coupon codes
echo "=== Method 2: Checking meta fields ===\n";

if ($use_hpos) {
    $meta_query = "
        SELECT DISTINCT o.id as order_id, o.date_created_gmt, om.meta_value
        FROM {$wpdb->prefix}wc_orders o
        INNER JOIN {$wpdb->prefix}wc_orders_meta om ON o.id = om.order_id 
        WHERE o.status IN ('wc-completed', 'wc-processing')
        AND om.meta_key = '_coupon_codes'
        AND om.meta_value LIKE '%koli10%'
        ORDER BY o.date_created_gmt DESC
    ";
} else {
    $meta_query = "
        SELECT DISTINCT p.ID as order_id, p.post_date as date_created_gmt, pm.meta_value
        FROM {$wpdb->prefix}posts p
        INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id 
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-processing')
        AND pm.meta_key = '_coupon_codes'
        AND pm.meta_value LIKE '%koli10%'
        ORDER BY p.post_date DESC
    ";
}

$meta_orders = $wpdb->get_results($meta_query);
echo "Found " . count($meta_orders) . " orders with koli10 in meta fields\n";

// Combine results and remove duplicates
$all_koli10_orders = array();
$order_ids_found = array();

// Add orders from method 1
foreach ($koli10_orders as $order_data) {
    $order_id = $order_data['order_id'];
    if (!in_array($order_id, $order_ids_found)) {
        $all_koli10_orders[] = $order_data;
        $order_ids_found[] = $order_id;
    }
}

// Add orders from method 2 that weren't found in method 1
foreach ($meta_orders as $order_data) {
    $order_id = $order_data->order_id;
    if (!in_array($order_id, $order_ids_found)) {
        $order = wc_get_order($order_id);
        if ($order) {
            $all_koli10_orders[] = array(
                'order_id' => $order_id,
                'order' => $order,
                'date' => $order_data->date_created_gmt
            );
            $order_ids_found[] = $order_id;
        }
    }
}

echo "\n=== Processing " . count($all_koli10_orders) . " unique orders with koli10 coupon ===\n";

$tracked_count = 0;
$skipped_count = 0;

foreach ($all_koli10_orders as $order_data) {
    $order_id = $order_data['order_id'];
    $order = $order_data['order'];
    
    echo "Processing Order #$order_id (" . $order_data['date'] . ")\n";
    
    // Check if already tracked
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE order_id = %d AND LOWER(coupon_code) = %s LIMIT 1",
        $order_id,
        'koli10'
    ));
    
    if ($existing) {
        echo "  Already tracked\n";
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
        echo "  Tracked successfully (€" . number_format($savings_amount, 2) . " savings)\n";
        $tracked_count++;
    } else {
        echo "  Failed to track - " . $wpdb->last_error . "\n";
    }
}

echo "\n<h3>Summary:</h3>\n";
echo "New records added: $tracked_count\n";
echo "Already tracked: $skipped_count\n";
echo "Total orders checked: $checked_count\n";
echo "Orders with koli10 found: " . count($all_koli10_orders) . "\n";

// Clear cache
if (function_exists('primefit_clear_discount_stats_cache')) {
    primefit_clear_discount_stats_cache();
    echo "Cache cleared\n";
}

echo "\nDone! Check your coupon analytics now.\n";
?>
