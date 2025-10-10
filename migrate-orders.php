<?php
/**
 * WooCommerce Order Migration Script
 * Migrates orders from Site A to Site B via REST API
 */

// Increase execution time for large migrations
set_time_limit(0);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 0);

// Prevent gateway timeout by sending periodic output
if (!function_exists('prevent_timeout')) {
    function prevent_timeout() {
        echo " ";
        flush();
        if (ob_get_level() > 0) {
            ob_flush();
        }
    }
}

// ==============================================
// CONFIGURATION
// ==============================================

// Site A (Source) Configuration
define('SITE_A_URL', 'https://prime-fit.eu');
define('SITE_A_KEY', 'ck_3a76197686e648b9875c3a5cb358d036abbc0579');
define('SITE_A_SECRET', 'cs_56314f698d9fb40900b7f6a351b62ccf2658553d');

// Site B (Destination) Configuration
define('SITE_B_URL', 'https://newprime.swissdigital.io');
define('SITE_B_KEY', 'ck_422248be674970cf472bc1d5bfb15152295dcb5c');
define('SITE_B_SECRET', 'cs_3b85fd5f997469ea6227436628e34f173a31aa2e');

// Site B Database Configuration (for direct date updates)
// To find your database name, check wp-config.php on your site
define('SITE_B_DB_HOST', 'localhost');
define('SITE_B_DB_NAME', 'wp_imuiu');  // Update this
define('SITE_B_DB_USER', 'wp_cnbna');   // Update this
define('SITE_B_DB_PASS', 'QAB^VafJ4*8Me84&');   // Update this
define('SITE_B_DB_PREFIX', '5VCpQ0fS5_');  // Update if different

// Migration Settings
define('TEST_MODE', false);  // Set to false for full migration
define('ORDERS_PER_PAGE', TEST_MODE ? 5 : 10); // Smaller batches to avoid timeout
define('START_PAGE', 1);
define('MAX_ORDERS_PER_RUN', TEST_MODE ? 5 : 50); // Reduced for browser execution
define('DELAY_BETWEEN_ORDERS', 500000); // Microseconds (0.5 seconds = faster)
define('DELAY_BETWEEN_BATCHES', 1000000); // 1 second between page fetches

// Product mapping settings
// If SKUs changed between sites, you can remap old SKUs to new SKUs here.
// Example: 'OLD-SKU-RED' => 'NEW-SKU-RED'
$SKU_REMAP = [
    // 'OLD-SKU' => 'NEW-SKU',
];

// Automatic color suffix mapping (applied if direct SKU match fails)
// Example: PF1001 (old var) + 'B' -> PF1001B
$COLOR_SUFFIX_MAP = [
    'black' => 'B',
    'white' => 'W',
    'red' => 'R',
    'blue' => 'U', // choose site-specific convention
    'green' => 'G',
    'yellow' => 'Y',
    'orange' => 'O',
    'purple' => 'P',
    'pink' => 'K',
    'grey' => 'E',
    'gray' => 'E',
];

// ==============================================
// HELPER FUNCTIONS
// ==============================================

/**
 * Make API request to WooCommerce
 */
function wc_api_request($site_url, $consumer_key, $consumer_secret, $endpoint, $method = 'GET', $data = []) {
    $url = rtrim($site_url, '/') . '/wp-json/wc/v3/' . ltrim($endpoint, '/');
    
    $ch = curl_init();
    
    // Set up authentication
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $consumer_key . ':' . $consumer_secret);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Set method and data
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    } elseif ($method === 'GET' && !empty($data)) {
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'data' => json_decode($response, true),
        'http_code' => $http_code
    ];
}

/**
 * Find customer by email on Site B
 */
function find_customer_by_email($email) {
    $response = wc_api_request(
        SITE_B_URL,
        SITE_B_KEY,
        SITE_B_SECRET,
        'customers',
        'GET',
        ['email' => $email]
    );
    
    if ($response['http_code'] === 200 && !empty($response['data'])) {
        return $response['data'][0]['id'];
    }
    
    return null;
}

/**
 * Find product by SKU on Site B
 */
function find_product_id_by_sku($sku) {
    if (empty($sku)) {
        return null;
    }
    $response = wc_api_request(
        SITE_B_URL,
        SITE_B_KEY,
        SITE_B_SECRET,
        'products',
        'GET',
        ['sku' => $sku]
    );
    if ($response['http_code'] === 200 && !empty($response['data'])) {
        // Use the first match
        return $response['data'][0]['id'] ?? null;
    }
    return null;
}

/**
 * Resolve Site B product_id for a line item using SKU (with optional remap)
 */
function resolve_product_id_for_item($item) {
    global $SKU_REMAP;
    global $COLOR_SUFFIX_MAP;
    $originalSku = $item['sku'] ?? '';
    $lookupSku = $originalSku;
    if (!empty($originalSku) && isset($SKU_REMAP[$originalSku])) {
        $lookupSku = $SKU_REMAP[$originalSku];
    }
    // Try direct (or remapped) SKU first
    if (!empty($lookupSku)) {
        $productId = find_product_id_by_sku($lookupSku);
        if ($productId) {
            return $productId;
        }
    }
    // If item came from a variation with a color attribute, try suffixing the base SKU
    $color = null;
    if (!empty($item['meta_data']) && is_array($item['meta_data'])) {
        foreach ($item['meta_data'] as $meta) {
            $key = isset($meta['key']) ? strtolower($meta['key']) : '';
            $value = isset($meta['value']) ? strtolower($meta['value']) : '';
            if ($key === 'pa_color' || $key === 'color' || $key === 'attribute_pa_color') {
                $color = $value;
                break;
            }
        }
    }
    if (!$color && !empty($item['name'])) {
        // crude fallback: try to detect color in name
        $lowerName = strtolower($item['name']);
        foreach ($COLOR_SUFFIX_MAP as $cName => $suffix) {
            if (strpos($lowerName, $cName) !== false) {
                $color = $cName;
                break;
            }
        }
    }
    if (!empty($originalSku) && !empty($color) && isset($COLOR_SUFFIX_MAP[$color])) {
        $candidate = $originalSku . $COLOR_SUFFIX_MAP[$color];
        $productId = find_product_id_by_sku($candidate);
        if ($productId) {
            return $productId;
        }
    }
    return null;
}

/**
 * Sanitize billing/shipping address data
 */
function sanitize_address($address) {
    if (empty($address)) {
        return [];
    }
    
    // Only include valid WooCommerce billing/shipping fields
    $valid_fields = [
        'first_name', 'last_name', 'company', 'address_1', 'address_2',
        'city', 'state', 'postcode', 'country', 'email', 'phone'
    ];
    
    $sanitized = [];
    foreach ($valid_fields as $field) {
        if (isset($address[$field]) && $address[$field] !== '') {
            $sanitized[$field] = $address[$field];
        }
    }
    
    return $sanitized;
}

/**
 * Prepare order data for migration
 */
function prepare_order_data($order) {
    $customer_id = find_customer_by_email($order['billing']['email'] ?? '');
    
    if (!$customer_id) {
        $email = $order['billing']['email'] ?? 'unknown';
        echo "   ⚠️  Customer not found: {$email} - Creating as guest\n";
    }
    
    // Prepare line items. Try to bind products by SKU. If not found, send name + totals.
    $line_items = [];
    foreach ($order['line_items'] as $item) {
        $line_item = [
            'quantity' => (int)$item['quantity']
        ];

        // 1) Try resolve using SKU mapping on Site B
        $resolvedProductId = resolve_product_id_for_item($item);
        if ($resolvedProductId) {
            $line_item['product_id'] = (int)$resolvedProductId;
        } elseif (!empty($item['product_id'])) {
            // 2) As a last resort, try the original product_id (may not match across sites)
            $line_item['product_id'] = (int)$item['product_id'];
        }

        // If product still not resolvable, create a custom line with name and totals
        if (empty($line_item['product_id'])) {
            if (!empty($item['name'])) {
                $line_item['name'] = $item['name'];
            }
            // Use subtotal (pre-discount) for both to avoid double-discounting
            if (isset($item['subtotal'])) {
                $line_item['subtotal'] = (string)$item['subtotal'];
                $line_item['total'] = (string)$item['subtotal'];
            }
        } else {
            // When product is resolvable, use subtotal (pre-discount) for both fields
            // Coupons will be applied via negative fee lines
            if (isset($item['subtotal'])) {
                $line_item['subtotal'] = (string)$item['subtotal'];
                $line_item['total'] = (string)$item['subtotal'];
            }
        }

        $line_items[] = $line_item;
    }
    
    // Sanitize shipping lines: remove order item IDs and send minimal fields only
    $shipping_lines = [];
    if (!empty($order['shipping_lines'])) {
        foreach ($order['shipping_lines'] as $s) {
            $shipping_lines[] = [
                'method_id' => $s['method_id'] ?? '',
                'method_title' => $s['method_title'] ?? '',
                'total' => isset($s['total']) ? (string)$s['total'] : '0'
            ];
        }
    }
    
    // Sanitize fee lines
    $fee_lines = [];
    if (!empty($order['fee_lines'])) {
        foreach ($order['fee_lines'] as $f) {
            $fee_lines[] = [
                'name' => $f['name'] ?? 'Fee',
                'total' => isset($f['total']) ? (string)$f['total'] : '0'
            ];
        }
    }
    
    // Convert coupons to negative fee lines to avoid applicability validation
    $coupon_lines = [];
    $migrated_coupon_codes = [];
    if (!empty($order['coupon_lines'])) {
        foreach ($order['coupon_lines'] as $c) {
            $code = $c['code'] ?? '';
            $discount = 0;
            if (isset($c['discount'])) {
                $discount = (float)$c['discount'];
            } elseif (isset($c['amount'])) {
                $discount = (float)$c['amount'];
            } elseif (isset($c['total'])) {
                $discount = (float)$c['total'];
            }
            if ($discount > 0) {
                $fee_lines[] = [
                    'name' => 'Coupon: ' . ($code ?: 'discount'),
                    'total' => '-' . (string)$discount,
                    'meta_data' => [
                        [ 'key' => '_migrated_coupon_code', 'value' => $code ]
                    ]
                ];
            }
            if (!empty($code)) {
                $migrated_coupon_codes[] = $code;
            }
        }
    }
    
    // Sanitize billing and shipping addresses
    $billing = sanitize_address($order['billing'] ?? []);
    $shipping = sanitize_address($order['shipping'] ?? []);
    
    // Ensure required billing fields
    if (empty($billing['email'])) {
        $billing['email'] = 'noemail@example.com';
    }
    if (empty($billing['first_name'])) {
        $billing['first_name'] = 'Guest';
    }
    if (empty($billing['last_name'])) {
        $billing['last_name'] = 'Customer';
    }
    
    // Build new order data
    $new_order = [
        'status' => $order['status'] ?? 'processing',
        'currency' => $order['currency'] ?? 'EUR',
        'customer_id' => $customer_id ?: 0,
        'billing' => $billing,
        'shipping' => $shipping,
        'customer_ip_address' => $order['customer_ip_address'] ?? '',
        'customer_user_agent' => $order['customer_user_agent'] ?? '',
        'line_items' => $line_items,
        'shipping_lines' => $shipping_lines,
        'fee_lines' => $fee_lines,
        'coupon_lines' => $coupon_lines, // intentionally empty; discounts captured as negative fees
        'customer_note' => $order['customer_note'] ?? '',
        // Attempt to preserve original creation date (may be ignored by API)
        'date_created' => $order['date_created'] ?? null,
        'date_created_gmt' => $order['date_created_gmt'] ?? null,
        'meta_data' => [
            [
                'key' => '_migrated_from_order_id',
                'value' => (string)$order['id']
            ],
            [
                'key' => '_original_order_date',
                'value' => $order['date_created'] ?? date('Y-m-d H:i:s')
            ],
            [
                'key' => '_created_via',
                'value' => 'rest-api'
            ],
            [
                'key' => '_migrated_coupon_codes',
                'value' => !empty($migrated_coupon_codes) ? implode(',', $migrated_coupon_codes) : ''
            ]
        ]
    ];
    
    return $new_order;
}

/**
 * Migrate a single order
 */
function migrate_order($order) {
    $order_id = $order['id'];
    
    try {
        // Prepare order data
        $new_order_data = prepare_order_data($order);
        
        // Create order on Site B
        $response = wc_api_request(
            SITE_B_URL,
            SITE_B_KEY,
            SITE_B_SECRET,
            'orders',
            'POST',
            $new_order_data
        );
        
        if ($response['http_code'] === 201) {
            $new_order = $response['data'];
            echo "   ✓ Order #{$order_id} → #{$new_order['id']} migrated successfully\n";
            
            // Attempt to correct the order created date if API ignored it during create
            if (!empty($order['date_created']) || !empty($order['date_created_gmt'])) {
                $datePayload = [];
                if (!empty($order['date_created'])) {
                    $datePayload['date_created'] = $order['date_created'];
                }
                if (!empty($order['date_created_gmt'])) {
                    $datePayload['date_created_gmt'] = $order['date_created_gmt'];
                }
                if (!empty($datePayload)) {
                    wc_api_request(
                        SITE_B_URL,
                        SITE_B_KEY,
                        SITE_B_SECRET,
                        "orders/{$new_order['id']}",
                        'PUT',
                        $datePayload
                    );
                }
            }

            // Migrate order notes
            if (!empty($order['customer_notes'])) {
                foreach ($order['customer_notes'] as $note) {
                    wc_api_request(
                        SITE_B_URL,
                        SITE_B_KEY,
                        SITE_B_SECRET,
                        "orders/{$new_order['id']}/notes",
                        'POST',
                        [
                            'note' => $note,
                            'customer_note' => true
                        ]
                    );
                }
            }
            
            return ['success' => true, 'new_id' => $new_order['id']];
        } else {
            $error = isset($response['data']['message']) ? $response['data']['message'] : 'Unknown error';
            echo "   ✗ Order #{$order_id} failed: {$response['http_code']} - {$error}\n";
            return ['success' => false, 'error' => $error];
        }
    } catch (Exception $e) {
        echo "   ✗ Order #{$order_id} exception: {$e->getMessage()}\n";
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Check if order already migrated (to allow resuming)
 * Uses direct database check for HPOS compatibility
 */
function is_order_migrated($source_order_id) {
    static $pdo = null;
    static $useHPOS = null;
    static $prefix = null;
    
    // Initialize database connection once
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . SITE_B_DB_HOST . ";dbname=" . SITE_B_DB_NAME,
                SITE_B_DB_USER,
                SITE_B_DB_PASS
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $prefix = SITE_B_DB_PREFIX;
            
            // Check if HPOS is enabled
            try {
                $pdo->query("SELECT 1 FROM {$prefix}wc_orders LIMIT 1");
                $useHPOS = true;
            } catch (PDOException $e) {
                $useHPOS = false;
            }
        } catch (PDOException $e) {
            // Fallback to API method if DB connection fails
            $response = wc_api_request(
                SITE_B_URL,
                SITE_B_KEY,
                SITE_B_SECRET,
                'orders',
                'GET',
                [
                    'per_page' => 1,
                    'meta_key' => '_migrated_from_order_id',
                    'meta_value' => (string)$source_order_id
                ]
            );
            return ($response['http_code'] === 200 && !empty($response['data']));
        }
    }
    
    try {
        if ($useHPOS) {
            // HPOS: Check wc_orders_meta table
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM {$prefix}wc_orders_meta
                WHERE meta_key = '_migrated_from_order_id'
                AND meta_value = :source_id
                LIMIT 1
            ");
            $stmt->execute([':source_id' => (string)$source_order_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['count'] > 0);
        } else {
            // Traditional: Check wp_postmeta table
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM {$prefix}postmeta pm
                INNER JOIN {$prefix}posts p ON pm.post_id = p.ID
                WHERE p.post_type = 'shop_order'
                AND pm.meta_key = '_migrated_from_order_id'
                AND pm.meta_value = :source_id
                LIMIT 1
            ");
            $stmt->execute([':source_id' => (string)$source_order_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['count'] > 0);
        }
    } catch (PDOException $e) {
        // If database query fails, assume not migrated to be safe
        return false;
    }
}

/**
 * Delete all migrated orders from Site B (for testing/cleanup)
 * WARNING: This will permanently delete orders!
 */
function delete_all_migrated_orders() {
    echo "\n==========================================\n";
    echo "DELETE ALL MIGRATED ORDERS\n";
    echo "==========================================\n\n";
    echo "⚠️  WARNING: This will permanently delete all migrated orders!\n\n";
    
    try {
        $pdo = new PDO(
            "mysql:host=" . SITE_B_DB_HOST . ";dbname=" . SITE_B_DB_NAME,
            SITE_B_DB_USER,
            SITE_B_DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $prefix = SITE_B_DB_PREFIX;
        
        // Check if HPOS is enabled
        $useHPOS = false;
        try {
            $pdo->query("SELECT 1 FROM {$prefix}wc_orders LIMIT 1");
            $useHPOS = true;
            echo "✓ Using HPOS storage\n\n";
        } catch (PDOException $e) {
            echo "✓ Using traditional storage\n\n";
        }
        
        if ($useHPOS) {
            // Get all migrated order IDs
            $stmt = $pdo->query("
                SELECT DISTINCT order_id
                FROM {$prefix}wc_orders_meta
                WHERE meta_key = '_migrated_from_order_id'
            ");
            $orderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($orderIds)) {
                echo "No migrated orders found.\n";
                return;
            }
            
            echo "Found " . count($orderIds) . " migrated orders to delete.\n\n";
            
            $deleted = 0;
            foreach ($orderIds as $orderId) {
                // Delete from wc_orders_meta
                $pdo->prepare("DELETE FROM {$prefix}wc_orders_meta WHERE order_id = ?")->execute([$orderId]);
                
                // Delete from wc_order_operational_data
                $pdo->prepare("DELETE FROM {$prefix}wc_order_operational_data WHERE order_id = ?")->execute([$orderId]);
                
                // Delete from wc_orders
                $pdo->prepare("DELETE FROM {$prefix}wc_orders WHERE id = ?")->execute([$orderId]);
                
                $deleted++;
                echo "✓ Deleted order #{$orderId}\n";
            }
            
            echo "\n✓ Deleted {$deleted} orders successfully.\n";
            
        } else {
            // Traditional: Delete from wp_posts and wp_postmeta
            $stmt = $pdo->query("
                SELECT DISTINCT pm.post_id
                FROM {$prefix}postmeta pm
                INNER JOIN {$prefix}posts p ON pm.post_id = p.ID
                WHERE p.post_type = 'shop_order'
                AND pm.meta_key = '_migrated_from_order_id'
            ");
            $orderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($orderIds)) {
                echo "No migrated orders found.\n";
                return;
            }
            
            echo "Found " . count($orderIds) . " migrated orders to delete.\n\n";
            
            $deleted = 0;
            foreach ($orderIds as $orderId) {
                // Delete postmeta
                $pdo->prepare("DELETE FROM {$prefix}postmeta WHERE post_id = ?")->execute([$orderId]);
                
                // Delete post
                $pdo->prepare("DELETE FROM {$prefix}posts WHERE ID = ? AND post_type = 'shop_order'")->execute([$orderId]);
                
                $deleted++;
                echo "✓ Deleted order #{$orderId}\n";
            }
            
            echo "\n✓ Deleted {$deleted} orders successfully.\n";
        }
        
    } catch (PDOException $e) {
        echo "✗ Database error: " . $e->getMessage() . "\n";
    }
}

/**
 * Update order dates via direct database connection
 * Run this AFTER the migration is complete to fix any date issues
 * Supports both traditional and HPOS (High-Performance Order Storage)
 */
function update_order_dates_via_database() {
    echo "\n==========================================\n";
    echo "Updating Order Dates via Database\n";
    echo "==========================================\n\n";
    
    try {
        $pdo = new PDO(
            "mysql:host=" . SITE_B_DB_HOST . ";dbname=" . SITE_B_DB_NAME,
            SITE_B_DB_USER,
            SITE_B_DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✓ Connected to database\n\n";
        
        $prefix = SITE_B_DB_PREFIX;
        
        // Check if HPOS is enabled
        $useHPOS = false;
        try {
            $hposCheck = $pdo->query("SELECT COUNT(*) as count FROM {$prefix}wc_orders LIMIT 1");
            $useHPOS = true;
            echo "✓ Detected HPOS (High-Performance Order Storage)\n\n";
        } catch (PDOException $e) {
            echo "✓ Using traditional order storage (wp_posts)\n\n";
        }
        
        $orders = [];
        
        if ($useHPOS) {
            // HPOS: Get migrated orders from wc_orders and wc_orders_meta
            $stmt = $pdo->prepare("
                SELECT o.id as ID, om.meta_value as original_date
                FROM {$prefix}wc_orders o
                INNER JOIN {$prefix}wc_orders_meta om ON o.id = om.order_id
                WHERE om.meta_key = '_original_order_date'
                AND om.meta_value IS NOT NULL
                AND om.meta_value != ''
            ");
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($orders)) {
                echo "⚠️  No orders found with '_original_order_date' meta.\n";
                echo "Checking for migrated orders using '_migrated_from_order_id'...\n\n";
                
                // Alternative: Get orders by migration meta
                $stmt = $pdo->prepare("
                    SELECT o.id as ID, om.meta_value as source_order_id
                    FROM {$prefix}wc_orders o
                    INNER JOIN {$prefix}wc_orders_meta om ON o.id = om.order_id
                    WHERE om.meta_key = '_migrated_from_order_id'
                    AND om.meta_value IS NOT NULL
                    AND om.meta_value != ''
                    ORDER BY o.id ASC
                ");
                $stmt->execute();
                $migrated_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($migrated_orders)) {
                    echo "No migrated orders found.\n";
                    return;
                }
                
                echo "Found " . count($migrated_orders) . " migrated orders.\n";
                echo "Fetching original dates from Site A...\n\n";
                
                foreach ($migrated_orders as $mo) {
                    $source_order_id = $mo['source_order_id'];
                    
                    // Fetch original order from Site A
                    $response = wc_api_request(
                        SITE_A_URL,
                        SITE_A_KEY,
                        SITE_A_SECRET,
                        "orders/{$source_order_id}",
                        'GET'
                    );
                    
                    if ($response['http_code'] === 200 && !empty($response['data']['date_created'])) {
                        $orders[] = [
                            'ID' => $mo['ID'],
                            'original_date' => $response['data']['date_created']
                        ];
                        echo "✓ Order #{$mo['ID']} (from source #{$source_order_id})\n";
                    } else {
                        echo "⚠️  Could not fetch source order #{$source_order_id}\n";
                    }
                    
                    usleep(200000); // 0.2s delay
                }
                
                echo "\n";
            }
        } else {
            // Traditional: Use wp_posts and wp_postmeta
            $stmt = $pdo->prepare("
                SELECT p.ID, pm.meta_value as original_date
                FROM {$prefix}posts p
                INNER JOIN {$prefix}postmeta pm ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_order'
                AND pm.meta_key = '_original_order_date'
                AND pm.meta_value IS NOT NULL
                AND pm.meta_value != ''
            ");
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (empty($orders)) {
            echo "No orders to update.\n";
            return;
        }
        
        echo "Found " . count($orders) . " orders to update\n\n";
        
        $updated = 0;
        $errors = 0;
        
        foreach ($orders as $order) {
            $orderId = $order['ID'];
            $originalDate = $order['original_date'];
            
            try {
                // Parse the original date (ISO 8601 format from WooCommerce API)
                $dateTime = new DateTime($originalDate);
                
                // Convert to MySQL datetime format (local time)
                $mysqlDate = $dateTime->format('Y-m-d H:i:s');
                
                // Convert to GMT
                $dateTimeGmt = clone $dateTime;
                $dateTimeGmt->setTimezone(new DateTimeZone('UTC'));
                $mysqlDateGmt = $dateTimeGmt->format('Y-m-d H:i:s');
                
                if ($useHPOS) {
                    // Update HPOS wc_orders table
                    $updateStmt = $pdo->prepare("
                        UPDATE {$prefix}wc_orders
                        SET date_created_gmt = :date_gmt
                        WHERE id = :id
                    ");
                    
                    $updateStmt->execute([
                        ':date_gmt' => $mysqlDateGmt,
                        ':id' => $orderId
                    ]);
                } else {
                    // Update traditional wp_posts table
                    $updateStmt = $pdo->prepare("
                        UPDATE {$prefix}posts
                        SET post_date = :date,
                            post_date_gmt = :date_gmt
                        WHERE ID = :id
                        AND post_type = 'shop_order'
                    ");
                    
                    $updateStmt->execute([
                        ':date' => $mysqlDate,
                        ':date_gmt' => $mysqlDateGmt,
                        ':id' => $orderId
                    ]);
                }
                
                if ($updateStmt->rowCount() > 0) {
                    $updated++;
                    echo "✓ Order #{$orderId}: {$mysqlDate}\n";
                } else {
                    echo "⚠️  Order #{$orderId}: No rows updated\n";
                }
                
            } catch (Exception $e) {
                $errors++;
                echo "✗ Order #{$orderId}: {$e->getMessage()}\n";
            }
        }
        
        echo "\n==========================================\n";
        echo "Date Update Complete!\n";
        echo "==========================================\n";
        echo "Successfully Updated: {$updated}\n";
        echo "Errors: {$errors}\n";
        
    } catch (PDOException $e) {
        echo "✗ Database connection error: " . $e->getMessage() . "\n";
        echo "\nPlease check your database configuration:\n";
        echo "  - SITE_B_DB_HOST: " . SITE_B_DB_HOST . "\n";
        echo "  - SITE_B_DB_NAME: " . SITE_B_DB_NAME . "\n";
        echo "  - SITE_B_DB_USER: " . SITE_B_DB_USER . "\n";
        echo "  - SITE_B_DB_PREFIX: " . SITE_B_DB_PREFIX . "\n";
    }
}

/**
 * Main migration function
 */
function migrate_orders() {
    echo "==========================================\n";
    echo "WooCommerce Order Migration\n";
    echo "==========================================\n\n";
    
    if (TEST_MODE) {
        echo "⚠️  TESTING MODE: Processing first " . MAX_ORDERS_PER_RUN . " orders\n";
        echo "Review results before running full migration!\n\n";
    } else {
        echo "📦 PRODUCTION MODE: Max " . MAX_ORDERS_PER_RUN . " orders per run\n";
        echo "Rate limiting: " . (DELAY_BETWEEN_ORDERS / 1000000) . "s between orders\n\n";
    }
    
    $page = START_PAGE;
    $total_migrated = 0;
    $total_skipped = 0;
    $total_errors = 0;
    $errors = [];
    $orders_processed_this_run = 0;
    
    while (true) {
        // Check if we've hit the max MIGRATED orders limit for this run (not including skipped)
        if ($total_migrated >= MAX_ORDERS_PER_RUN) {
            echo "\n⏸️  Reached max orders per run ({$total_migrated} migrated). Stopping.\n";
            echo "Run the script again to continue.\n";
            break;
        }
        
        echo "\n--- Fetching page {$page} (batch of " . ORDERS_PER_PAGE . ") ---\n";
        
        // Get orders from Site A
        $response = wc_api_request(
            SITE_A_URL,
            SITE_A_KEY,
            SITE_A_SECRET,
            'orders',
            'GET',
            [
                'per_page' => ORDERS_PER_PAGE,
                'page' => $page,
                'orderby' => 'id',
                'order' => 'asc'
            ]
        );
        
        if ($response['http_code'] !== 200) {
            echo "Error fetching orders: {$response['http_code']}\n";
            break;
        }
        
        $orders = $response['data'];
        
        if (empty($orders)) {
            echo "No more orders to migrate!\n";
            break;
        }
        
        echo "Found " . count($orders) . " orders\n\n";
        
        // Process each order
        foreach ($orders as $order) {
            // Check if we've hit the limit (only count actual migrations)
            if ($total_migrated >= MAX_ORDERS_PER_RUN) {
                break;
            }
            
            $order_id = $order['id'];
            echo "Processing Order #{$order_id}...\n";
            prevent_timeout(); // Keep connection alive
            
            // Check if already migrated (for resumption) - skip this check in test mode
            if (!TEST_MODE && is_order_migrated($order_id)) {
                echo "   ⏭️  Already migrated, skipping...\n";
                $total_skipped++;
                prevent_timeout();
                continue;
            }
            
            $result = migrate_order($order);
            
            if ($result['success']) {
                $total_migrated++;
                $orders_processed_this_run++;
            } else {
                $total_errors++;
                $errors[] = "Order #{$order_id}: {$result['error']}";
            }
            
            prevent_timeout(); // Keep connection alive
            
            // Rate limiting between orders
            usleep(DELAY_BETWEEN_ORDERS);
            
            // Clear memory periodically
            if ($total_migrated % 10 === 0 && $total_migrated > 0) {
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                prevent_timeout();
            }
        }
        
        // In test mode, only process first batch
        if (TEST_MODE) {
            break;
        }
        
        // Check again if we've hit limit before fetching next page
        if ($total_migrated >= MAX_ORDERS_PER_RUN) {
            break;
        }
        
        // Rate limiting between batches
        usleep(DELAY_BETWEEN_BATCHES);
        
        $page++;
    }
    
    // Summary
    echo "\n==========================================\n";
    echo "Migration Session Complete!\n";
    echo "==========================================\n";
    echo "Orders Migrated: {$total_migrated}\n";
    echo "Orders Skipped (already migrated): {$total_skipped}\n";
    echo "Errors: {$total_errors}\n";
    echo "Total Processed This Run: {$orders_processed_this_run}\n";
    
    if (!empty($errors)) {
        echo "\nErrors encountered:\n";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "  - {$error}\n";
        }
        if (count($errors) > 10) {
            $remaining = count($errors) - 10;
            echo "  ... and {$remaining} more\n";
        }
    }
    
    if (TEST_MODE) {
        echo "\n✓ Test complete! Review the orders on Site B.\n";
        echo "If everything looks good, set TEST_MODE to false and run again.\n";
    } else {
        if ($total_migrated >= MAX_ORDERS_PER_RUN) {
            echo "\n🔄 More orders to migrate. Reload the page to continue.\n";
            echo "(Script will automatically skip already-migrated orders)\n";
        } else {
            echo "\n✅ All orders migrated!\n";
        }
    }
}

// ==============================================
// RUN MIGRATION
// ==============================================

// Check if running from command line
if (php_sapi_name() === 'cli') {
    // CLI: Check for action parameter
    $action = $argv[1] ?? 'migrate';
    
    if ($action === 'update-dates') {
        update_order_dates_via_database();
    } elseif ($action === 'delete-migrated') {
        delete_all_migrated_orders();
    } else {
        migrate_orders();
    }
} else {
    // Running from browser - add basic security
    echo "<pre>";
    
    // Simple password protection (change this!)
    $password = 'blurider10';
    
    if (!isset($_GET['password']) || $_GET['password'] !== $password) {
        die("Access denied. Add ?password=your_password to URL");
    }
    
    // Check which action to run
    $action = $_GET['action'] ?? 'migrate';
    
    if ($action === 'update-dates') {
        echo "<h2>Order Date Update Tool</h2>";
        echo "<p><a href='?password={$password}&action=migrate'>← Back to Migration</a></p>";
        update_order_dates_via_database();
    } elseif ($action === 'delete-migrated') {
        echo "<h2>⚠️ DELETE Migrated Orders</h2>";
        echo "<p><a href='?password={$password}&action=migrate'>← Back to Migration</a></p>";
        delete_all_migrated_orders();
    } else {
        echo "<h2>Order Migration Tool</h2>";
        echo "<p><a href='?password={$password}&action=update-dates'>Update Order Dates</a> | ";
        echo "<a href='?password={$password}&action=delete-migrated' style='color:red;'>⚠️ Delete All Migrated Orders</a></p>";
        migrate_orders();
    }
    
    echo "</pre>";
}