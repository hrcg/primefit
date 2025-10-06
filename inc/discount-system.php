<?php
/**
 * PrimeFit Theme Discount Code System
 *
 * Advanced discount code tracking with email association
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: WooCommerce availability will be checked in individual functions
// to allow the discount system to load even if WooCommerce isn't ready yet

/**
 * Initialize discount system and create tables if needed
 */
add_action( 'after_setup_theme', 'primefit_initialize_discount_system' );
function primefit_initialize_discount_system() {
	// Create the discount tracking table if it doesn't exist
	primefit_create_discount_tracking_table();

	// Update table structure and create new tables if needed
	primefit_update_discount_tracking_table();

	// Schedule Action Scheduler cleanup to run after initialization
	add_action( 'init', 'primefit_schedule_action_scheduler_cleanup', 20 );
}

/**
 * Schedule Action Scheduler cleanup to run after WordPress is fully initialized
 */
function primefit_schedule_action_scheduler_cleanup() {
	// Only run this if we have admin capabilities or if it's been more than 24 hours since last cleanup
	$last_cleanup = get_option( 'primefit_last_action_scheduler_cleanup', 0 );
	$current_time = time();

	if ( $current_time - $last_cleanup > 24 * 60 * 60 ) { // 24 hours
		// Use a delayed execution to ensure Action Scheduler is ready
		wp_schedule_single_event( time() + 30, 'primefit_action_scheduler_cleanup' );
	}
}

/**
 * Execute Action Scheduler cleanup (scheduled as single event)
 */
add_action( 'primefit_action_scheduler_cleanup', 'primefit_execute_action_scheduler_cleanup' );
function primefit_execute_action_scheduler_cleanup() {
	$cleanup_count = primefit_cleanup_action_scheduler();
	update_option( 'primefit_last_action_scheduler_cleanup', time() );

	// Only log if we actually cleaned up actions or if there was an error
	if ( $cleanup_count > 0 ) {
		error_log( sprintf( '[PrimeFit] Action Scheduler cleanup completed, removed %d past-due actions', $cleanup_count ) );
	}
}

/**
 * Create custom database table for discount code tracking
 * Note: Tables are now created on init instead of theme activation
 */
function primefit_create_discount_tracking_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'discount_code_tracking';

	// Check if table already exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
		return; // Table already exists
	}

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		coupon_code varchar(50) NOT NULL,
		email varchar(100) NOT NULL,
		user_id bigint(20) unsigned DEFAULT NULL,
		order_id bigint(20) unsigned NOT NULL,
		savings_amount decimal(10,2) NOT NULL,
		usage_date datetime DEFAULT CURRENT_TIMESTAMP,
		ip_address varchar(45) DEFAULT NULL,
		user_agent text DEFAULT NULL,
		PRIMARY KEY (id),
		KEY coupon_code (coupon_code),
		KEY email (email),
		KEY order_id (order_id),
		KEY user_id (user_id),
		KEY usage_date (usage_date),
		KEY coupon_order (coupon_code, order_id),
		KEY email_usage_date (email, usage_date)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$result = dbDelta( $sql );

	// Store version for future updates (only if table was actually created)
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
		add_option( 'primefit_discount_tracking_version', '1.0' );
	}
}

/**
 * Update database table structure if needed
 */
add_action( 'plugins_loaded', 'primefit_update_discount_tracking_table' );
function primefit_update_discount_tracking_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'discount_code_tracking';
	$current_version = get_option( 'primefit_discount_tracking_version', '1.0' );

	if ( version_compare( $current_version, '1.1', '<' ) ) {
		// Add new columns for version 1.1 with safety checks
		$has_ip = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table_name LIKE %s", 'ip_address' ) );
		if ( ! $has_ip ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN ip_address varchar(45) DEFAULT NULL AFTER usage_date" );
		}
		$has_ua = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table_name LIKE %s", 'user_agent' ) );
		if ( ! $has_ua ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN user_agent text DEFAULT NULL AFTER ip_address" );
		}

		update_option( 'primefit_discount_tracking_version', '1.1' );
	}

	if ( version_compare( $current_version, '1.2', '<' ) ) {
		// Create coupon reset tracking table for version 1.2
		primefit_create_coupon_reset_tracking_table();

		update_option( 'primefit_discount_tracking_version', '1.2' );
	}
}

/**
 * Create coupon reset tracking table
 */
function primefit_create_coupon_reset_tracking_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'coupon_reset_tracking';

	// Check if table already exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
		return; // Table already exists
	}

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		coupon_code varchar(50) NOT NULL,
		reset_date datetime DEFAULT CURRENT_TIMESTAMP,
		reset_by_user_id bigint(20) unsigned DEFAULT NULL,
		previous_uses_count int(11) DEFAULT 0,
		previous_total_savings decimal(10,2) DEFAULT 0.00,
		PRIMARY KEY (id),
		KEY coupon_code (coupon_code),
		KEY reset_date (reset_date),
		KEY coupon_reset_date (coupon_code, reset_date)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	// Verify table was created
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
		// Log error if table creation failed
		error_log( "Failed to create coupon reset tracking table: $table_name" );
	}
}

/**
 * Track discount code usage when order is completed
 * FIXED: Added comprehensive error handling and validation
 */
add_action( 'woocommerce_order_status_completed', 'primefit_track_discount_usage', 10, 1 );
function primefit_track_discount_usage( $order_id ) {
	// Validate order ID
	if ( empty( $order_id ) || ! is_numeric( $order_id ) ) {
		return;
	}

	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	// Get applied coupons
	$coupons = $order->get_coupon_codes();

	if ( empty( $coupons ) ) {
		return; // No coupons to track
	}

	// Get customer email and validate
	$email = $order->get_billing_email();
	if ( empty( $email ) || ! is_email( $email ) ) {
		return;
	}

	$user_id = $order->get_user_id();

	// Track each coupon used with proper error handling
	global $wpdb;
	$table_name = $wpdb->prefix . 'discount_code_tracking';

	// Use transaction to prevent partial data corruption
	$wpdb->query( 'START TRANSACTION' );

	try {
		foreach ( $coupons as $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );

			if ( ! $coupon || ! $coupon->get_id() ) {
				continue;
			}

			// Calculate savings for this coupon
			$savings_amount = primefit_calculate_coupon_savings( $coupon, $order );

			// Get user IP and agent for tracking
			$ip_address = primefit_get_client_ip();
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

			// Insert tracking record with proper error handling
			$result = $wpdb->insert(
				$table_name,
				array(
					'coupon_code' => sanitize_text_field( $coupon_code ),
					'email' => sanitize_email( $email ),
					'user_id' => intval( $user_id ),
					'order_id' => intval( $order_id ),
					'savings_amount' => floatval( $savings_amount ),
					'ip_address' => sanitize_text_field( $ip_address ),
					'user_agent' => substr( sanitize_text_field( $user_agent ), 0, 500 ) // Limit length to prevent overflow
				),
				array(
					'%s', '%s', '%d', '%d', '%f', '%s', '%s'
				)
			);

			if ( $result === false ) {
				throw new Exception( "Database error inserting discount tracking record: " . $wpdb->last_error );
			}
		}

		// Commit transaction if all inserts succeeded
		$wpdb->query( 'COMMIT' );
		
		// Clear discount statistics cache after successful tracking
		primefit_clear_discount_stats_cache();

	} catch ( Exception $e ) {
		// Rollback transaction on error
		$wpdb->query( 'ROLLBACK' );
	}
}

/**
 * Clear discount statistics cache when new usage is tracked
 */
function primefit_clear_discount_stats_cache() {
	global $wpdb;

	// Clear all discount statistics related transients
    // Targeted invalidation via registries
    if ( function_exists( 'primefit_clear_registered_transients' ) ) {
        primefit_clear_registered_transients( 'primefit_usage_by_date_keys' );
        primefit_clear_registered_transients( 'primefit_top_users_keys' );
        primefit_clear_registered_transients( 'primefit_coupon_stats_keys' );
    }
}

/**
 * Reset coupon statistics
 */
function primefit_reset_coupon_stats( $coupon_code ) {
	global $wpdb;

	if ( empty( $coupon_code ) ) {
		return new WP_Error( 'invalid_coupon', 'Coupon code is required' );
	}

	// Get current stats before resetting
	$current_stats = primefit_get_coupon_stats( $coupon_code );

	if ( $current_stats['total_uses'] == 0 && $current_stats['total_savings'] == 0 ) {
		return new WP_Error( 'no_data', 'No usage data to reset for this coupon' );
	}

	// Start transaction
	$wpdb->query( 'START TRANSACTION' );

	try {
		// Get current user ID for tracking
		$current_user_id = get_current_user_id();

		// Insert reset record
		$reset_table = $wpdb->prefix . 'coupon_reset_tracking';

		// Ensure reset tracking table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$reset_table'" ) !== $reset_table ) {
			primefit_create_coupon_reset_tracking_table();
		}

		$reset_result = $wpdb->insert(
			$reset_table,
			array(
				'coupon_code' => sanitize_text_field( $coupon_code ),
				'reset_date' => current_time( 'mysql' ),
				'reset_by_user_id' => intval( $current_user_id ),
				'previous_uses_count' => intval( $current_stats['total_uses'] ),
				'previous_total_savings' => floatval( $current_stats['total_savings'] )
			),
			array( '%s', '%s', '%d', '%d', '%f' )
		);

		if ( $reset_result === false ) {
			throw new Exception( 'Failed to record reset action' );
		}

		// Delete all usage records for this coupon
		$tracking_table = $wpdb->prefix . 'discount_code_tracking';
		$delete_result = $wpdb->delete(
			$tracking_table,
			array( 'coupon_code' => sanitize_text_field( $coupon_code ) ),
			array( '%s' )
		);

		if ( $delete_result === false ) {
			throw new Exception( 'Failed to reset coupon statistics' );
		}

		// Commit transaction
		$wpdb->query( 'COMMIT' );

		// Clear cache
		primefit_clear_discount_stats_cache();

		return array(
			'success' => true,
			'reset_count' => $delete_result,
			'previous_uses' => $current_stats['total_uses'],
			'previous_savings' => $current_stats['total_savings']
		);

	} catch ( Exception $e ) {
		// Rollback on error
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'reset_failed', $e->getMessage() );
	}
}

/**
 * Get coupon reset history
 */
function primefit_get_coupon_reset_history( $coupon_code = null, $limit = 10 ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'coupon_reset_tracking';

	// Check if table exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
		return array(); // Return empty array if table doesn't exist
	}

	$where_clause = '';
	$params = array();

	if ( $coupon_code ) {
		$where_clause = 'WHERE coupon_code = %s';
		$params[] = $coupon_code;
	}

	if ( ! empty( $params ) ) {
		$query = $wpdb->prepare(
			"SELECT * FROM $table_name $where_clause ORDER BY reset_date DESC LIMIT %d",
			array_merge( $params, array( $limit ) )
		);
	} else {
		$query = $wpdb->prepare(
			"SELECT * FROM $table_name $where_clause ORDER BY reset_date DESC LIMIT %d",
			array( $limit )
		);
	}

	return $wpdb->get_results( $query );
}

/**
 * Get last reset date for a coupon
 */
function primefit_get_coupon_last_reset_date( $coupon_code ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'coupon_reset_tracking';

	// Check if table exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
		return false; // Return false if table doesn't exist
	}

	$last_reset = $wpdb->get_var( $wpdb->prepare(
		"SELECT reset_date FROM $table_name WHERE coupon_code = %s ORDER BY reset_date DESC LIMIT 1",
		$coupon_code
	) );

	return $last_reset ? $last_reset : false;
}

/**
 * Calculate the actual savings amount for a coupon
 */
function primefit_calculate_coupon_savings( $coupon, $order ) {
	// Prefer exact per-coupon discount as recorded on the order
	$target_code = is_string( $coupon ) ? $coupon : $coupon->get_code();
	$target_code = is_string( $target_code ) ? strtolower( trim( $target_code ) ) : '';
	if ( empty( $target_code ) ) {
		return 0.0;
	}

	$total_discount_for_coupon = 0.0;
	$coupon_items = $order->get_items( 'coupon' );
	if ( ! empty( $coupon_items ) ) {
		foreach ( $coupon_items as $coupon_item ) {
			$code = strtolower( trim( $coupon_item->get_code() ) );
			if ( $code === $target_code ) {
				// Include tax component to reflect total savings
				$total_discount_for_coupon += (float) $coupon_item->get_discount() + (float) $coupon_item->get_discount_tax();
			}
		}
	}

	// Fallback to order-level totals if no coupon item was found
	if ( $total_discount_for_coupon <= 0 ) {
		$total_discount_for_coupon = (float) $order->get_discount_total();
	}

	return max( 0.0, (float) $total_discount_for_coupon );
}

/**
 * Get client IP address
 * FIXED: Prevent IP spoofing by validating and sanitizing input
 */
function primefit_get_client_ip() {
	$ip_headers = array(
		'HTTP_CF_CONNECTING_IP',
		'HTTP_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'REMOTE_ADDR'
	);

	foreach ( $ip_headers as $header ) {
		if ( ! empty( $_SERVER[ $header ] ) ) {
			$ip = $_SERVER[ $header ];

			// SECURITY: Sanitize the input first
			$ip = trim( $ip );

			// SECURITY: Validate IP format before processing
			if ( ! preg_match( '/^[0-9a-fA-F:.]+$/', $ip ) ) {
				continue; // Skip invalid characters
			}

			// Handle comma-separated IPs (like X-Forwarded-For)
			if ( strpos( $ip, ',' ) !== false ) {
				$ip = trim( explode( ',', $ip )[0] );
			}

			// SECURITY: Validate IP format and prevent spoofing
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				// Additional validation to prevent header injection
				if ( ! preg_match( '/[\r\n\t]/', $ip ) ) {
					return $ip;
				}
			}
		}
	}

	// Fallback to REMOTE_ADDR with additional validation
	$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
	if ( ! empty( $remote_addr ) && filter_var( $remote_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
		return $remote_addr;
	}

	return '';
}

/**
 * Get cached usage by date statistics
 */
function primefit_get_cached_usage_by_date( $where_clause, $params ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'discount_code_tracking';
	
	// Create cache key based on query parameters
	$cache_key = 'primefit_usage_by_date_' . md5( $where_clause . serialize( $params ) );
	$cached = get_transient( $cache_key );
	
	if ( false === $cached ) {
		$usage_by_date_query = "
			SELECT
				DATE(usage_date) as usage_date,
				COUNT(*) as uses_count,
				SUM(savings_amount) as daily_savings
			FROM $table_name $where_clause
			GROUP BY DATE(usage_date)
			ORDER BY usage_date DESC
			LIMIT 30
		";
		if ( ! empty( $params ) ) {
			$cached = $wpdb->get_results( $wpdb->prepare( $usage_by_date_query, $params ) );
		} else {
			$cached = $wpdb->get_results( $usage_by_date_query );
		}
		
		// Cache for 1 hour
        set_transient( $cache_key, $cached, HOUR_IN_SECONDS );
        if ( function_exists( 'primefit_register_transient_key' ) ) {
            primefit_register_transient_key( 'primefit_usage_by_date_keys', $cache_key );
        }
	}
	
	return $cached;
}

/**
 * Get cached top users statistics
 */
function primefit_get_cached_top_users( $where_clause, $params ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'discount_code_tracking';
	
	// Create cache key based on query parameters
	$cache_key = 'primefit_top_users_' . md5( $where_clause . serialize( $params ) );
	$cached = get_transient( $cache_key );
	
	if ( false === $cached ) {
		$top_users_query = "
			SELECT
				email,
				COUNT(*) as uses_count,
				SUM(savings_amount) as total_savings
			FROM $table_name $where_clause
			GROUP BY email
			ORDER BY uses_count DESC, total_savings DESC
			LIMIT 10
		";
		if ( ! empty( $params ) ) {
			$cached = $wpdb->get_results( $wpdb->prepare( $top_users_query, $params ) );
		} else {
			$cached = $wpdb->get_results( $top_users_query );
		}
		
		// Cache for 1 hour
        set_transient( $cache_key, $cached, HOUR_IN_SECONDS );
        if ( function_exists( 'primefit_register_transient_key' ) ) {
            primefit_register_transient_key( 'primefit_top_users_keys', $cache_key );
        }
	}
	
	return $cached;
}

/**
 * Get discount usage statistics
 */
function primefit_get_discount_stats( $coupon_code = null, $email = null, $start_date = null, $end_date = null ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'discount_code_tracking';

	$where_conditions = array();
	$params = array();

	if ( $coupon_code ) {
		$where_conditions[] = 'coupon_code = %s';
		$params[] = $coupon_code;
	}

	if ( $email ) {
		$where_conditions[] = 'email = %s';
		$params[] = $email;
	}

	if ( $start_date ) {
		$where_conditions[] = 'usage_date >= %s';
		$params[] = $start_date;
	}

	if ( $end_date ) {
		$where_conditions[] = 'usage_date <= %s';
		$params[] = $end_date;
	}

	$where_clause = $where_conditions ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';

	// Single optimized query with all aggregations
	$stats_query = "
		SELECT 
			COUNT(*) as total_uses,
			SUM(savings_amount) as total_savings,
			COUNT(DISTINCT email) as unique_emails,
			AVG(savings_amount) as avg_savings_per_user
		FROM $table_name $where_clause
	";
	
	if ( ! empty( $params ) ) {
		$stats = $wpdb->get_row( $wpdb->prepare( $stats_query, $params ) );
	} else {
		$stats = $wpdb->get_row( $stats_query );
	}
	
	// Extract values with proper fallbacks
	$total_uses = (int) $stats->total_uses;
	$total_savings = (float) $stats->total_savings;
	$unique_emails = (int) $stats->unique_emails;
	$avg_savings_per_user = (float) $stats->avg_savings_per_user;

	// Get usage by date and top users with caching
	$usage_by_date = primefit_get_cached_usage_by_date( $where_clause, $params );
	$top_users = primefit_get_cached_top_users( $where_clause, $params );

	return array(
		'total_uses' => (int) $total_uses,
		'total_savings' => (float) $total_savings,
		'unique_emails' => (int) $unique_emails,
		'avg_savings_per_user' => (float) $avg_savings_per_user,
		'usage_by_date' => $usage_by_date,
		'top_users' => $top_users
	);
}

/**
 * Get discount statistics for a specific coupon
 */
function primefit_get_coupon_stats( $coupon_code ) {
	return primefit_get_discount_stats( $coupon_code );
}

/**
 * Get usage statistics for a specific email
 */
function primefit_get_email_discount_stats( $email ) {
	return primefit_get_discount_stats( null, $email );
}

/**
 * Clean up old tracking data (optional - for data retention)
 */
function primefit_cleanup_old_discount_data( $days_to_keep = 365 ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'discount_code_tracking';

	$wpdb->query( $wpdb->prepare(
		"DELETE FROM $table_name WHERE usage_date < DATE_SUB(NOW(), INTERVAL %d DAY)",
		$days_to_keep
	) );
}

/**
 * Clean up old Action Scheduler actions and reschedule properly
 */
function primefit_cleanup_action_scheduler() {
	// Only run this function if Action Scheduler is properly initialized
	if ( ! primefit_is_action_scheduler_ready() ) {
		error_log( '[PrimeFit] Action Scheduler not ready, skipping cleanup' );
		return 0;
	}

	try {
		// Get all past-due actions for our specific hook
		$past_due_actions = as_get_scheduled_actions( array(
			'hook' => 'primefit_weekly_coupon_report',
			'status' => 'pending',
			'date' => gmdate( 'Y-m-d H:i:s', time() - 24 * 60 * 60 ), // More than 1 day old
			'per_page' => -1
		) );

		$cleanup_count = 0;

		if ( ! empty( $past_due_actions ) ) {
			foreach ( $past_due_actions as $action_id => $action ) {
				// Cancel old past-due actions
				as_unschedule_action( $action_id );
				$cleanup_count++;
			}

			// Only log if we actually cleaned up actions
			if ( $cleanup_count > 0 ) {
				error_log( sprintf( '[PrimeFit] Cleaned up %d past-due coupon report actions', $cleanup_count ) );
			}
		}

		// Reschedule the event properly if it's missing or corrupted
		$next_scheduled = wp_next_scheduled( 'primefit_weekly_coupon_report' );

		if ( ! $next_scheduled || $next_scheduled < time() ) {
			$timestamp = primefit_get_next_monday_9am();
			wp_schedule_event( $timestamp, 'weekly', 'primefit_weekly_coupon_report' );
			error_log( sprintf( '[PrimeFit] Rescheduled weekly coupon report for %s', date( 'Y-m-d H:i:s', $timestamp ) ) );
		}

		return $cleanup_count;

	} catch ( Exception $e ) {
		error_log( '[PrimeFit] Error cleaning up Action Scheduler: ' . $e->getMessage() );
		return 0;
	}
}

/**
 * Check if Action Scheduler is properly initialized and ready
 */
function primefit_is_action_scheduler_ready() {
	// Check if the function exists
	if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
		return false;
	}

	// Check if the data store is initialized by trying to access it
	try {
		// Try to get the data store instance using the global function
		if ( function_exists( 'ActionScheduler_Store' ) ) {
			$data_store = ActionScheduler_Store::instance();
			return $data_store !== null;
		}

		// Fallback: check if we can call the function without errors
		$test_actions = as_get_scheduled_actions( array(
			'per_page' => 1,
			'status' => 'pending'
		) );

		// If we get here without exception, Action Scheduler is ready
		return true;

	} catch ( Exception $e ) {
		// If there's an exception, the data store isn't ready
		return false;
	}
}

/**
 * Admin function to manually trigger Action Scheduler cleanup
 */
function primefit_admin_cleanup_action_scheduler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'primefit' ) );
	}

	// Check if Action Scheduler is ready before running cleanup
	if ( ! primefit_is_action_scheduler_ready() ) {
		echo '<div class="wrap">';
		echo '<h1>' . __( 'Action Scheduler Cleanup', 'primefit' ) . '</h1>';
		echo '<div class="notice notice-warning"><p>' . __( 'Action Scheduler is not ready. Please try again in a few moments.', 'primefit' ) . '</p></div>';
		echo '<p><a href="' . admin_url( 'admin.php?page=tools.php' ) . '" class="button">' . __( 'Back to Tools', 'primefit' ) . '</a></p>';
		echo '</div>';
		return;
	}

	$cleanup_count = primefit_cleanup_action_scheduler();

	echo '<div class="wrap">';
	echo '<h1>' . __( 'Action Scheduler Cleanup', 'primefit' ) . '</h1>';
	echo '<p>' . sprintf( __( 'Cleaned up %d past-due actions.', 'primefit' ), $cleanup_count ) . '</p>';
	echo '<p><a href="' . admin_url( 'admin.php?page=tools.php' ) . '" class="button">' . __( 'Back to Tools', 'primefit' ) . '</a></p>';
	echo '</div>';
}

/**
 * Add cleanup option to admin menu
 */
add_action( 'admin_menu', 'primefit_add_cleanup_admin_menu' );
function primefit_add_cleanup_admin_menu() {
	add_submenu_page(
		'tools.php',
		__( 'Action Scheduler Cleanup', 'primefit' ),
		__( 'Action Scheduler Cleanup', 'primefit' ),
		'manage_options',
		'primefit-action-scheduler-cleanup',
		'primefit_admin_cleanup_action_scheduler'
	);
}

/**
 * Validate coupon usage based on email restrictions
 */
function primefit_validate_coupon_email( $coupon_code, $email ) {
	// Check if coupon exists
	$coupon = new WC_Coupon( $coupon_code );
	if ( ! $coupon ) {
		return false;
	}

	// Check if ACF is available
	if ( ! function_exists( 'get_field' ) ) {
		return true; // Allow if ACF not available
	}

	// Get email restrictions setting
	$email_restrictions = get_field( 'email_restrictions', $coupon->get_id() );

	if ( ! $email_restrictions || $email_restrictions === 'allow_all' ) {
		return true; // Allow all emails
	}

	// Get associated emails
	$associated_emails = get_field( 'associated_emails', $coupon->get_id() );

	if ( empty( $associated_emails ) ) {
		return $email_restrictions !== 'restrict_list';
	}

	// Check if email is in the associated list
	$allowed_emails = wp_list_pluck( $associated_emails, 'email' );

	if ( in_array( $email, $allowed_emails ) ) {
		return true; // Email is in the allowed list
	}

	// Handle different restriction types
	switch ( $email_restrictions ) {
		case 'restrict_list':
			return false; // Only allow emails in the list
		case 'require_verification':
			// Send verification email to admin
			primefit_send_coupon_verification_email( $coupon_code, $email );
			return false; // Block usage until verified
		default:
			return true;
	}
}

/**
 * Send verification email for new coupon usage
 */
function primefit_send_coupon_verification_email( $coupon_code, $email ) {
	$admin_email = get_option( 'admin_email' );
	$subject = sprintf( __( 'Coupon Verification Required: %s', 'primefit' ), $coupon_code );

	$message = sprintf( __(
		"A customer with email %s is trying to use coupon code '%s' but is not in the approved list.\n\n" .
		"Coupon Details:\n" .
		"- Code: %s\n" .
		"- Customer Email: %s\n" .
		"- Time: %s\n\n" .
		"To approve this usage, please add the email to the coupon's associated emails list in the WordPress admin.",
		'primefit'
	), $email, $coupon_code, $coupon_code, $email, current_time( 'mysql' ) );

	wp_mail( $admin_email, $subject, $message );
}

/**
 * Send usage notification emails
 */
function primefit_send_usage_notification( $coupon_code, $order_id, $savings_amount, $email ) {
	// Check if notifications are enabled for this coupon
	$coupon = new WC_Coupon( $coupon_code );
	if ( ! $coupon || ! function_exists( 'get_field' ) ) {
		return;
	}

	$usage_notifications = get_field( 'usage_notifications', $coupon->get_id() );
	if ( ! $usage_notifications ) {
		return;
	}

	// Get notification recipients
	$notification_emails = get_field( 'notification_emails', $coupon->get_id() );
	if ( empty( $notification_emails ) ) {
		return;
	}

	$notification_emails = array_map( 'trim', explode( "\n", $notification_emails ) );
	$notification_emails = array_filter( $notification_emails );
	$notification_emails = array_unique( array_filter( array_map( 'sanitize_email', $notification_emails ), 'is_email' ) );

	if ( empty( $notification_emails ) ) {
		return;
	}

	$order = wc_get_order( $order_id );
	$subject = sprintf( __( 'Coupon Used: %s - €%s saved', 'primefit' ), $coupon_code, number_format( $savings_amount, 2 ) );

	$message = sprintf( __(
		"Coupon code '%s' has been used successfully!\n\n" .
		"Usage Details:\n" .
		"- Code: %s\n" .
		"- Customer Email: %s\n" .
		"- Savings: €%s\n" .
		"- Order ID: %d\n" .
		"- Order Total: €%s\n" .
		"- Time: %s\n\n" .
		"Order Link: %s",
		'primefit'
	),
		$coupon_code,
		$coupon_code,
		$email,
		number_format( $savings_amount, 2 ),
		$order_id,
		$order ? number_format( $order->get_total(), 2 ) : 'N/A',
		current_time( 'mysql' ),
		admin_url( 'post.php?post=' . $order_id . '&action=edit' )
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	foreach ( $notification_emails as $recipient ) {
		wp_mail( $recipient, $subject, $message, $headers );
	}
}

/**
 * Enhanced coupon validation with email checking
 */
add_filter( 'woocommerce_coupon_is_valid', 'primefit_enhanced_coupon_validation', 10, 3 );
function primefit_enhanced_coupon_validation( $valid, $coupon, $discount ) {
	// Only validate if coupon is otherwise valid
	if ( ! $valid ) {
		return $valid;
	}

	// Ensure WooCommerce is loaded
	if ( ! class_exists( 'WooCommerce' ) ) {
		return $valid;
	}

	// Get customer email from session or current user
	$email = '';
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$email = $current_user->user_email;
	} elseif ( WC()->session ) {
		$email = WC()->session->get( 'billing_email' );
	}

	// If no email available yet, allow validation (will be checked on checkout)
	if ( empty( $email ) ) {
		return $valid;
	}

	// Validate email against coupon restrictions
	return primefit_validate_coupon_email( $coupon->get_code(), $email );
}

/**
 * Add email validation to checkout process
 */
add_action( 'woocommerce_after_checkout_validation', 'primefit_validate_checkout_coupons', 10, 2 );
function primefit_validate_checkout_coupons( $data, $errors ) {
	// Ensure WooCommerce is loaded
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
		return;
	}

	$coupon_codes = WC()->cart->get_applied_coupons();

	if ( empty( $coupon_codes ) ) {
		return;
	}

	$email = $data['billing_email'];

	foreach ( $coupon_codes as $coupon_code ) {
		if ( ! primefit_validate_coupon_email( $coupon_code, $email ) ) {
			$errors->add( 'invalid_coupon_email', sprintf(
				__( 'The coupon code "%s" cannot be used with this email address. Please contact support if you believe this is an error.', 'primefit' ),
				$coupon_code
			) );
		}
	}
}

/**
 * Send notification when coupon is used successfully
 */
add_action( 'woocommerce_checkout_order_processed', 'primefit_notify_coupon_usage', 10, 3 );
function primefit_notify_coupon_usage( $order_id, $posted_data, $order ) {
	// Ensure WooCommerce is loaded
	if ( ! class_exists( 'WooCommerce' ) || ! $order ) {
		return;
	}

	$coupons = $order->get_coupon_codes();

	if ( empty( $coupons ) ) {
		return;
	}

	$email = $order->get_billing_email();
	$savings_total = 0;

	foreach ( $coupons as $coupon_code ) {
		$coupon = new WC_Coupon( $coupon_code );
		$savings_amount = primefit_calculate_coupon_savings( $coupon, $order );

		// Send notification
		primefit_send_usage_notification( $coupon_code, $order_id, $savings_amount, $email );

		$savings_total += $savings_amount;
	}
}

/**
 * Get coupon field helper function
 */
function primefit_get_coupon_field( $field_name, $coupon_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	return get_field( $field_name, $coupon_id );
}

/**
 * Check if coupon has email restrictions
 */
function primefit_coupon_has_email_restrictions( $coupon_code ) {
	$coupon = new WC_Coupon( $coupon_code );
	if ( ! $coupon || ! function_exists( 'get_field' ) ) {
		return false;
	}

	$email_restrictions = get_field( 'email_restrictions', $coupon->get_id() );
	return $email_restrictions && $email_restrictions !== 'allow_all';
}

/**
 * Schedule weekly coupon report email with improved error handling
 * Only runs scheduling logic if needed, not on every page load
 */
add_action( 'init', 'primefit_schedule_weekly_coupon_reports' );
function primefit_schedule_weekly_coupon_reports() {
	// Only check scheduling once per day to avoid excessive logging
	$last_check = get_transient( 'primefit_schedule_check' );
	if ( $last_check ) {
		return;
	}

	// Clear any existing problematic transients on first run after update
	if ( ! get_transient( 'primefit_transients_cleared' ) ) {
		delete_transient( 'primefit_schedule_check' );
		set_transient( 'primefit_transients_cleared', true, DAY_IN_SECONDS );
	}

	try {
		// Prefer Action Scheduler for reliability
		$as_ready = primefit_is_action_scheduler_ready();
		if ( $as_ready && function_exists( 'as_next_scheduled_action' ) ) {
			// Clear legacy WP-Cron schedule if it exists
			if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_clear_scheduled_hook' ) ) {
				$legacy_next = wp_next_scheduled( 'primefit_weekly_coupon_report' );
				if ( $legacy_next ) {
					wp_clear_scheduled_hook( 'primefit_weekly_coupon_report' );
				}
			}

			$next_as = as_next_scheduled_action( 'primefit_weekly_coupon_report' );
			if ( ! $next_as ) {
				$timestamp = primefit_get_next_monday_9am();
				if ( function_exists( 'as_schedule_recurring_action' ) ) {
					as_schedule_recurring_action( $timestamp, WEEK_IN_SECONDS, 'primefit_weekly_coupon_report' );
					error_log( sprintf( '[PrimeFit] Weekly coupon report (AS) scheduled for %s', date( 'Y-m-d H:i:s', $timestamp ) ) );
				}
			} elseif ( $next_as < time() && function_exists( 'as_schedule_single_action' ) ) {
				// Watchdog: if scheduled time is in the past, trigger a one-off soon
				as_schedule_single_action( time() + 60, 'primefit_weekly_coupon_report' );
				error_log( '[PrimeFit] Watchdog scheduled immediate weekly coupon report (AS)' );
			}

			set_transient( 'primefit_schedule_check', time(), DAY_IN_SECONDS );
			return;
		}

		// Fallback to WP-Cron if Action Scheduler is not ready
		if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_event' ) ) {
			$next_scheduled = wp_next_scheduled( 'primefit_weekly_coupon_report' );
			if ( ! $next_scheduled ) {
				$timestamp = primefit_get_next_monday_9am();
				wp_schedule_event( $timestamp, 'weekly', 'primefit_weekly_coupon_report' );
				error_log( sprintf( '[PrimeFit] Weekly coupon report (WP-Cron) scheduled for %s', date( 'Y-m-d H:i:s', $timestamp ) ) );
			} elseif ( $next_scheduled < time() ) {
				wp_schedule_single_event( time() + 60, 'primefit_weekly_coupon_report' );
				error_log( '[PrimeFit] Watchdog scheduled immediate weekly coupon report (WP-Cron)' );
			}
		}

		set_transient( 'primefit_schedule_check', time(), DAY_IN_SECONDS );

	} catch ( Exception $e ) {
		error_log( '[PrimeFit] Error scheduling weekly coupon reports: ' . $e->getMessage() );
		set_transient( 'primefit_schedule_check', time(), DAY_IN_SECONDS );
	}
}

/**
 * Get the next Monday at 9 AM timestamp
 */
function primefit_get_next_monday_9am() {
	$now = current_time( 'timestamp' );
	$day_of_week = date( 'w', $now ); // 0 = Sunday, 1 = Monday, etc.

	// Calculate days until next Monday (0 = Sunday, so we want 1)
	$days_until_monday = (1 - $day_of_week + 7) % 7;

	// If today is Monday, schedule for next Monday
	if ( $days_until_monday === 0 ) {
		$days_until_monday = 7;
	}

	$next_monday = $now + ( $days_until_monday * 24 * 60 * 60 );

	// Set time to 9 AM
	$next_monday_9am = strtotime( date( 'Y-m-d', $next_monday ) . ' 09:00:00' );

	// Ensure we're not scheduling in the past
	if ( $next_monday_9am <= $now ) {
		$next_monday_9am += 7 * 24 * 60 * 60; // Add another week
	}

	return $next_monday_9am;
}

/**
 * Send weekly coupon usage report with improved error handling
 */
add_action( 'primefit_weekly_coupon_report', 'primefit_send_weekly_coupon_report' );
function primefit_send_weekly_coupon_report() {
	try {
		// Ensure WooCommerce is loaded and available
		if ( ! class_exists( 'WooCommerce' ) ) {
			error_log( '[PrimeFit] WooCommerce not available for weekly report' );
			return;
		}

		// Get last week's date range with validation
		$end_date = current_time( 'Y-m-d' );
		$start_date = date( 'Y-m-d', strtotime( '-7 days', strtotime( $end_date ) ) );

		if ( ! $start_date || ! $end_date ) {
			error_log( '[PrimeFit] Invalid date range for weekly report' );
			return;
		}

		// Get all coupon usage stats for the past week
		$weekly_stats = primefit_get_discount_stats( null, null, $start_date, $end_date );

		if ( $weekly_stats === false ) {
			error_log( '[PrimeFit] Failed to get weekly stats for coupon report' );
			return;
		}

		// Build coupon-specific stats using a single grouped query for efficiency
		global $wpdb;
		$table_name = $wpdb->prefix . 'discount_code_tracking';
		$grouped = $wpdb->get_results( $wpdb->prepare(
			"SELECT coupon_code,
				COUNT(*) as total_uses,
				SUM(savings_amount) as total_savings,
				COUNT(DISTINCT email) as unique_emails,
				AVG(savings_amount) as avg_savings_per_user
			 FROM $table_name
			 WHERE usage_date >= %s AND usage_date <= %s
			 GROUP BY coupon_code",
			$start_date, $end_date
		) );

		$stats_by_code = array();
		if ( $grouped ) {
			foreach ( $grouped as $row ) {
				$stats_by_code[ strtolower( $row->coupon_code ) ] = array(
					'total_uses' => (int) $row->total_uses,
					'total_savings' => (float) $row->total_savings,
					'unique_emails' => (int) $row->unique_emails,
					'avg_savings_per_user' => (float) $row->avg_savings_per_user,
					'usage_by_date' => array(), // not needed per coupon in weekly email
					'top_users' => array(),
				);
			}
		}

		// Load coupons to ensure we only include existing/published codes and proper casing
		$coupons = get_posts( array(
			'post_type' => 'shop_coupon',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		) );
		$coupon_stats = array();
		foreach ( $coupons as $coupon_post ) {
			$coupon = new WC_Coupon( $coupon_post->ID );
			$code = $coupon && $coupon->get_code() ? $coupon->get_code() : '';
			if ( ! $code ) { continue; }
			$key = strtolower( $code );
			$stats = isset( $stats_by_code[$key] ) ? $stats_by_code[$key] : array(
				'total_uses' => 0,
				'total_savings' => 0.0,
				'unique_emails' => 0,
				'avg_savings_per_user' => 0.0,
				'usage_by_date' => array(),
				'top_users' => array(),
			);
			$coupon_stats[] = array( 'code' => $code, 'stats' => $stats );
		}

		// Get top performing coupons
		usort( $coupon_stats, function( $a, $b ) {
			return $b['stats']['total_savings'] <=> $a['stats']['total_savings'];
		} );
		$top_coupons = array_slice( $coupon_stats, 0, 10 );

		// Get email recipients for weekly reports
		$recipients = primefit_get_weekly_report_recipients();

		if ( empty( $recipients ) ) {
			return; // No recipients configured - this is normal, don't log
		}

		$subject = sprintf( __( 'Weekly Coupon Usage Report - %s', 'primefit' ), $start_date . ' to ' . $end_date );

		$message = primefit_generate_weekly_report_html( $weekly_stats, $coupon_stats, $top_coupons, $start_date, $end_date );

		if ( empty( $message ) ) {
			error_log( '[PrimeFit] Failed to generate weekly report HTML' );
			return;
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>'
		);

		$sent_count = 0;
		$failed_count = 0;

		foreach ( $recipients as $recipient ) {
			$recipient = sanitize_email( $recipient );
			if ( ! is_email( $recipient ) ) {
				continue;
			}

			$mail_result = wp_mail( $recipient, $subject, $message, $headers );
			if ( $mail_result ) {
				$sent_count++;
			} else {
				$failed_count++;
			}
		}

		// Persist result for observability
		update_option( 'primefit_weekly_report_last_sent', array(
			'timestamp' => current_time( 'mysql' ),
			'sent' => $sent_count,
			'failed' => $failed_count,
			'period' => array( 'start' => $start_date, 'end' => $end_date )
		) );

		// Only log if there were issues or if none were sent
		if ( $failed_count > 0 || $sent_count === 0 ) {
			error_log( sprintf( '[PrimeFit] Weekly coupon report: sent to %d, failed %d recipients', $sent_count, $failed_count ) );
			// Admin notice via transient for next dashboard view
			set_transient( 'primefit_weekly_report_notice', array( 'sent' => $sent_count, 'failed' => $failed_count ), DAY_IN_SECONDS );
		}

	} catch ( Exception $e ) {
		error_log( '[PrimeFit] Critical error in weekly coupon report: ' . $e->getMessage() );
		// Don't re-throw to prevent Action Scheduler from retrying indefinitely
	}
}

/**
 * Get recipients for weekly coupon reports
 */
function primefit_get_weekly_report_recipients() {
	// Ensure WooCommerce is loaded
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array( get_option( 'admin_email' ) );
	}

	$recipients = array();

	// Get admin email
	$admin_email = get_option( 'admin_email' );
	if ( $admin_email ) {
		$recipients[] = $admin_email;
	}

	// Get recipients from coupon notification settings
	$coupons = get_posts( array(
		'post_type' => 'shop_coupon',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	) );

	foreach ( $coupons as $coupon_post ) {
		$coupon = new WC_Coupon( $coupon_post->ID );
		if ( ! function_exists( 'get_field' ) ) {
			continue;
		}

		$notification_emails = get_field( 'notification_emails', $coupon->get_id() );
		if ( $notification_emails ) {
			$emails = array_map( 'trim', explode( "\n", $notification_emails ) );
			$recipients = array_merge( $recipients, array_filter( $emails ) );
		}
	}

	// Remove duplicates and empty values
	$recipients = array_unique( array_filter( $recipients ) );

	return $recipients;
}

/**
 * Generate HTML content for weekly report
 */
function primefit_generate_weekly_report_html( $weekly_stats, $coupon_stats, $top_coupons, $start_date, $end_date ) {
	ob_start();
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="UTF-8">
		<title>Weekly Coupon Usage Report</title>
		<style>
			body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
			.container { max-width: 800px; margin: 0 auto; padding: 20px; }
			.header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px; }
			.section { margin-bottom: 40px; }
			.section h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
			.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
			.stat-box { background: #f8f9fa; padding: 20px; border-radius: 5px; text-align: center; }
			.stat-number { font-size: 2em; font-weight: bold; color: #3498db; }
			.stat-label { color: #666; font-size: 0.9em; }
			.coupon-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
			.coupon-table th, .coupon-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
			.coupon-table th { background: #f8f9fa; font-weight: bold; }
			.coupon-table tr:hover { background: #f5f5f5; }
			.footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 0.9em; }
		</style>
	</head>
	<body>
		<div class="container">
			<div class="header">
				<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?> - Weekly Coupon Usage Report</h1>
				<p><strong>Reporting Period:</strong> <?php echo esc_html( $start_date ); ?> to <?php echo esc_html( $end_date ); ?></p>
			</div>

			<div class="section">
				<h2>Overall Performance</h2>
				<div class="stats-grid">
					<div class="stat-box">
						<div class="stat-number"><?php echo number_format( $weekly_stats['total_uses'] ); ?></div>
						<div class="stat-label">Total Uses</div>
					</div>
					<div class="stat-box">
						<div class="stat-number">€<?php echo number_format( $weekly_stats['total_savings'], 2 ); ?></div>
						<div class="stat-label">Total Savings</div>
					</div>
					<div class="stat-box">
						<div class="stat-number"><?php echo number_format( $weekly_stats['unique_emails'] ); ?></div>
						<div class="stat-label">Unique Customers</div>
					</div>
					<div class="stat-box">
						<div class="stat-number">€<?php echo number_format( $weekly_stats['avg_savings_per_user'], 2 ); ?></div>
						<div class="stat-label">Avg. Savings per User</div>
					</div>
				</div>
			</div>

			<div class="section">
				<h2>Top Performing Coupons</h2>
				<table class="coupon-table">
					<thead>
						<tr>
							<th>Coupon Code</th>
							<th>Uses</th>
							<th>Total Savings</th>
							<th>Unique Users</th>
							<th>Avg. per User</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $top_coupons as $coupon ) : ?>
							<?php if ( $coupon['stats']['total_uses'] > 0 ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $coupon['code'] ); ?></strong></td>
									<td><?php echo number_format( $coupon['stats']['total_uses'] ); ?></td>
									<td>€<?php echo number_format( $coupon['stats']['total_savings'], 2 ); ?></td>
									<td><?php echo number_format( $coupon['stats']['unique_emails'] ); ?></td>
									<td>€<?php echo number_format( $coupon['stats']['avg_savings_per_user'], 2 ); ?></td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="section">
				<h2>Daily Usage Breakdown</h2>
				<table class="coupon-table">
					<thead>
						<tr>
							<th>Date</th>
							<th>Uses</th>
							<th>Daily Savings</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_reverse( $weekly_stats['usage_by_date'] ) as $day ) : ?>
							<tr>
								<td><?php echo esc_html( $day->usage_date ); ?></td>
								<td><?php echo number_format( $day->uses_count ); ?></td>
								<td>€<?php echo number_format( $day->daily_savings, 2 ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="section">
				<h2>Top Customers This Week</h2>
				<table class="coupon-table">
					<thead>
						<tr>
							<th>Email</th>
							<th>Uses</th>
							<th>Total Savings</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $weekly_stats['top_users'] as $user ) : ?>
							<tr>
								<td><?php echo esc_html( $user->email ); ?></td>
								<td><?php echo number_format( $user->uses_count ); ?></td>
								<td>€<?php echo number_format( $user->total_savings, 2 ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="footer">
				<p><strong>Report Generated:</strong> <?php echo current_time( 'Y-m-d H:i:s' ); ?></p>
				<p>This is an automated report from <?php echo esc_html( get_bloginfo( 'name' ) ); ?>.</p>
				<p><a href="<?php echo admin_url( 'edit.php?post_type=shop_coupon' ); ?>">Manage Coupons</a></p>
			</div>
		</div>
	</body>
	</html>
	<?php
	return ob_get_clean();
}




/**
 * Add quick actions to coupon list
 */
add_filter( 'post_row_actions', 'primefit_add_coupon_quick_actions', 10, 2 );
function primefit_add_coupon_quick_actions( $actions, $post ) {
	if ( $post->post_type === 'shop_coupon' ) {
		$coupon = new WC_Coupon( $post->ID );
		$stats = primefit_get_discount_stats( $coupon->get_code() );

		if ( $stats['total_uses'] > 0 ) {
		}
	}
	return $actions;
}

/**
 * Add custom columns to coupon list
 */
add_filter( 'manage_edit-shop_coupon_columns', 'primefit_add_coupon_columns' );
function primefit_add_coupon_columns( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $value ) {
		$new_columns[$key] = $value;

		// Add usage statistics columns after the coupon code column
		if ( $key === 'coupon_code' ) {
			$new_columns['usage_count'] = __( 'Uses', 'primefit' );
			$new_columns['total_savings'] = __( 'Total Savings', 'primefit' );
			$new_columns['unique_users'] = __( 'Unique Users', 'primefit' );
			$new_columns['last_reset'] = __( 'Last Reset', 'primefit' );
		}
	}

	return $new_columns;
}

/**
 * Display custom column content for coupons
 */
add_action( 'manage_shop_coupon_posts_custom_column', 'primefit_display_coupon_column_content', 10, 2 );
function primefit_display_coupon_column_content( $column, $post_id ) {
	$coupon = new WC_Coupon( $post_id );

	switch ( $column ) {
		case 'usage_count':
			$stats = primefit_get_coupon_stats( $coupon->get_code() );
			echo number_format( $stats['total_uses'] );
			break;

		case 'total_savings':
			$stats = primefit_get_coupon_stats( $coupon->get_code() );
			echo '€' . number_format( $stats['total_savings'], 2 );
			break;

		case 'unique_users':
			$stats = primefit_get_coupon_stats( $coupon->get_code() );
			echo number_format( $stats['unique_emails'] );
			break;

		case 'last_reset':
			$last_reset = primefit_get_coupon_last_reset_date( $coupon->get_code() );
			if ( $last_reset ) {
				echo '<span style="color: #d63638; font-size: 12px;">' .
				     date_i18n( get_option( 'date_format' ), strtotime( $last_reset ) ) .
				     '</span>';
			} else {
				echo '<span style="color: #999; font-size: 12px;">' . __( 'Never', 'primefit' ) . '</span>';
			}
			break;
	}
}

/**
 * Make coupon columns sortable
 */
add_filter( 'manage_edit-shop_coupon_sortable_columns', 'primefit_make_coupon_columns_sortable' );
function primefit_make_coupon_columns_sortable( $columns ) {
	$columns['usage_count'] = 'usage_count';
	$columns['total_savings'] = 'total_savings';
	$columns['unique_users'] = 'unique_users';
	$columns['last_reset'] = 'last_reset';
	return $columns;
}

/**
 * Handle coupon column sorting
 */
add_action( 'pre_get_posts', 'primefit_handle_coupon_column_sorting' );
function primefit_handle_coupon_column_sorting( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'shop_coupon' ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	switch ( $orderby ) {
		case 'usage_count':
			$query->set( 'meta_query', array(
				array(
					'key' => '_usage_count',
					'compare' => 'EXISTS',
				)
			));
			$query->set( 'orderby', 'meta_value_num' );
			break;

		case 'total_savings':
			$query->set( 'meta_query', array(
				array(
					'key' => '_total_savings',
					'compare' => 'EXISTS',
				)
			));
			$query->set( 'orderby', 'meta_value_num' );
			break;

		case 'unique_users':
			$query->set( 'meta_query', array(
				array(
					'key' => '_unique_users',
					'compare' => 'EXISTS',
				)
			));
			$query->set( 'orderby', 'meta_value_num' );
			break;

		case 'last_reset':
			$query->set( 'meta_query', array(
				array(
					'key' => '_last_reset',
					'compare' => 'EXISTS',
				)
			));
			$query->set( 'orderby', 'meta_value' );
			break;
	}
}

/**
 * Store coupon statistics as post meta for sorting
 */
add_action( 'woocommerce_order_status_completed', 'primefit_update_coupon_meta_for_sorting', 20, 1 );
function primefit_update_coupon_meta_for_sorting( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$coupons = $order->get_coupon_codes();
	if ( empty( $coupons ) ) {
		return;
	}

	foreach ( $coupons as $coupon_code ) {
		$coupon = new WC_Coupon( $coupon_code );
		if ( ! $coupon || ! $coupon->get_id() ) {
			continue;
		}

		$stats = primefit_get_coupon_stats( $coupon_code );

		// Update coupon meta for sorting
		update_post_meta( $coupon->get_id(), '_usage_count', $stats['total_uses'] );
		update_post_meta( $coupon->get_id(), '_total_savings', $stats['total_savings'] );
		update_post_meta( $coupon->get_id(), '_unique_users', $stats['unique_emails'] );

		if ( $last_reset = primefit_get_coupon_last_reset_date( $coupon_code ) ) {
			update_post_meta( $coupon->get_id(), '_last_reset', $last_reset );
		} else {
			delete_post_meta( $coupon->get_id(), '_last_reset' );
		}
	}
}

/**
 * Add bulk actions for coupons
 */
add_filter( 'bulk_actions-edit-shop_coupon', 'primefit_add_coupon_bulk_actions' );
function primefit_add_coupon_bulk_actions( $bulk_actions ) {
	$bulk_actions['export_usage_data'] = __( 'Export Usage Data', 'primefit' );
	$bulk_actions['send_usage_report'] = __( 'Send Usage Report', 'primefit' );
	return $bulk_actions;
}

/**
 * Handle coupon bulk actions
 */
add_filter( 'handle_bulk_actions-edit-shop_coupon', 'primefit_handle_coupon_bulk_actions', 10, 3 );
function primefit_handle_coupon_bulk_actions( $redirect_to, $action, $post_ids ) {
	if ( $action === 'export_usage_data' ) {
		// Export usage data for selected coupons
		primefit_export_coupon_usage_data( $post_ids );
		$redirect_to = add_query_arg( 'exported', count( $post_ids ), $redirect_to );
	} elseif ( $action === 'send_usage_report' ) {
		// Send usage reports for selected coupons
		primefit_send_bulk_usage_reports( $post_ids );
		$redirect_to = add_query_arg( 'reports_sent', count( $post_ids ), $redirect_to );
	}
	return $redirect_to;
}

/**
 * Export coupon usage data
 */
function primefit_export_coupon_usage_data( $coupon_ids ) {
	$filename = 'coupon-usage-data-' . date( 'Y-m-d-H-i-s' ) . '.csv';
	header( 'Content-Type: text/csv' );
	header( 'Content-Disposition: attachment; filename=' . $filename );

	$output = fopen( 'php://output', 'w' );

	// CSV headers
	fputcsv( $output, array(
		__( 'Coupon Code', 'primefit' ),
		__( 'Total Uses', 'primefit' ),
		__( 'Total Savings (€)', 'primefit' ),
		__( 'Unique Users', 'primefit' ),
		__( 'Avg. per User (€)', 'primefit' ),
		__( 'Has Restrictions', 'primefit' ),
		__( 'Last Used', 'primefit' )
	) );

	foreach ( $coupon_ids as $coupon_id ) {
		$coupon = new WC_Coupon( $coupon_id );
		$stats = primefit_get_discount_stats( $coupon->get_code() );

		// Get last used date
		global $wpdb;
		$table_name = $wpdb->prefix . 'discount_code_tracking';
		$last_used = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(usage_date) FROM $table_name WHERE coupon_code = %s",
			$coupon->get_code()
		) );

		fputcsv( $output, array(
			$coupon->get_code(),
			$stats['total_uses'],
			$stats['total_savings'],
			$stats['unique_emails'],
			$stats['avg_savings_per_user'],
			primefit_coupon_has_email_restrictions( $coupon->get_code() ) ? __( 'Yes', 'primefit' ) : __( 'No', 'primefit' ),
			$last_used ? $last_used : __( 'Never', 'primefit' )
		) );
	}

	fclose( $output );
	exit;
}

/**
 * Send bulk usage reports
 */
function primefit_send_bulk_usage_reports( $coupon_ids ) {
	$sent_count = 0;

	foreach ( $coupon_ids as $coupon_id ) {
		$coupon = new WC_Coupon( $coupon_id );
		$stats = primefit_get_discount_stats( $coupon->get_code() );

		if ( $stats['total_uses'] > 0 ) {
			// Send individual report for this coupon
			primefit_send_individual_coupon_report( $coupon->get_code(), $stats );
			$sent_count++;
		}
	}

	if ( $sent_count > 0 ) {
		// Send summary email
		$subject = sprintf( __( 'Bulk Coupon Usage Reports Sent - %d coupons', 'primefit' ), $sent_count );
		$message = sprintf( __(
			"Usage reports have been sent for %d coupons:\n\n",
			$sent_count
		) . implode( "\n", array_map( function( $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );
			return "- " . $coupon->get_code();
		}, $coupon_ids ) ) );

		wp_mail( get_option( 'admin_email' ), $subject, $message );
	}
}

/**
 * Send individual coupon report
 */
function primefit_send_individual_coupon_report( $coupon_code, $stats ) {
	$subject = sprintf( __( 'Coupon Usage Report: %s', 'primefit' ), $coupon_code );

	$message = sprintf( __(
		"Coupon Usage Report for: %s\n\n" .
		"Statistics:\n" .
		"- Total Uses: %d\n" .
		"- Total Savings: €%.2f\n" .
		"- Unique Users: %d\n" .
		"- Average per User: €%.2f\n\n" .
		"View detailed analytics: %s",
		$coupon_code,
		$stats['total_uses'],
		$stats['total_savings'],
		$stats['unique_emails'],
		$stats['avg_savings_per_user'],
		admin_url( 'edit.php?post_type=shop_coupon' )
	) );

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	// Get recipients for this coupon
	$coupon = new WC_Coupon( $coupon_code );
	if ( function_exists( 'get_field' ) ) {
		$notification_emails = get_field( 'notification_emails', $coupon->get_id() );
		if ( $notification_emails ) {
			$recipients = array_map( 'trim', explode( "\n", $notification_emails ) );
			foreach ( $recipients as $recipient ) {
				wp_mail( $recipient, $subject, $message, $headers );
			}
		}
	}

	// Always send to admin
	wp_mail( get_option( 'admin_email' ), $subject, $message, $headers );
}

/**
 * Display admin notices for bulk actions
 */
add_action( 'admin_notices', 'primefit_coupon_bulk_action_notices' );
function primefit_coupon_bulk_action_notices() {
	// Weekly report failure/success notice
	if ( current_user_can( 'manage_woocommerce' ) ) {
		$notice = get_transient( 'primefit_weekly_report_notice' );
		if ( $notice ) {
			$sent = intval( $notice['sent'] );
			$failed = intval( $notice['failed'] );
			$cls = $failed > 0 || $sent === 0 ? 'notice notice-error' : 'notice notice-success';
			echo '<div class="' . esc_attr( $cls ) . ' is-dismissible"><p>' .
				esc_html( sprintf( __( 'Weekly coupon report: sent %d, failed %d.', 'primefit' ), $sent, $failed ) ) .
				'</p></div>';
			delete_transient( 'primefit_weekly_report_notice' );
		}
	}
	if ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'shop_coupon' ) {
		return;
	}

	if ( isset( $_GET['exported'] ) ) {
		$count = intval( $_GET['exported'] );
		printf(
			'<div class="notice notice-success is-dismissible"><p>' .
			__( 'Usage data exported for %d coupon(s).', 'primefit' ) .
			'</p></div>',
			$count
		);
	}

	if ( isset( $_GET['reports_sent'] ) ) {
		$count = intval( $_GET['reports_sent'] );
		printf(
			'<div class="notice notice-success is-dismissible"><p>' .
			__( 'Usage reports sent for %d coupon(s).', 'primefit' ) .
			'</p></div>',
			$count
		);
	}

	if ( isset( $_GET['coupon_reset'] ) ) {
		$coupon_code = sanitize_text_field( $_GET['coupon_reset'] );
		printf(
			'<div class="notice notice-success is-dismissible"><p>' .
			__( 'Statistics reset for coupon "%s".', 'primefit' ) .
			'</p></div>',
			esc_html( $coupon_code )
		);
	}

	if ( isset( $_GET['reset_error'] ) ) {
		$error = sanitize_text_field( $_GET['reset_error'] );
		printf(
			'<div class="notice notice-error is-dismissible"><p>' .
			__( 'Error resetting coupon: %s', 'primefit' ) .
			'</p></div>',
			esc_html( $error )
		);
	}
}

/**
 * Add coupon analytics submenu page
 */
add_action( 'admin_menu', 'primefit_add_coupon_analytics_menu' );
function primefit_add_coupon_analytics_menu() {
	add_submenu_page(
		'woocommerce',
		__( 'Coupon Analytics', 'primefit' ),
		__( 'Coupon Analytics', 'primefit' ),
		'manage_woocommerce',
		'coupon-analytics',
		'primefit_coupon_analytics_page'
	);
}

/**
 * Display coupon analytics page
 */
function primefit_coupon_analytics_page() {
	// Handle coupon reset action
	if ( isset( $_POST['reset_coupon'] ) && isset( $_POST['coupon_code'] ) ) {
		check_admin_referer( 'reset_coupon_stats' );

		$coupon_code = sanitize_text_field( $_POST['coupon_code'] );
		$result = primefit_reset_coupon_stats( $coupon_code );

		if ( is_wp_error( $result ) ) {
			wp_redirect( add_query_arg( array( 'reset_error' => urlencode( $result->get_error_message() ) ) ) );
		} else {
			wp_redirect( add_query_arg( array( 'coupon_reset' => urlencode( $coupon_code ) ) ) );
		}
		exit;
	}

	?>
	<div class="wrap">
		<h1><?php _e( 'Coupon Analytics Dashboard', 'primefit' ); ?></h1>

		<?php primefit_display_coupon_analytics_overview(); ?>

		<?php primefit_display_coupon_analytics_table(); ?>

		<?php primefit_display_coupon_reset_history(); ?>
	</div>
	<?php
}

/**
 * Display coupon analytics overview
 */
function primefit_display_coupon_analytics_overview() {
	// Get overall statistics
	$overall_stats = primefit_get_discount_stats();

	// Get all coupons and their stats
	$coupons = get_posts( array(
		'post_type' => 'shop_coupon',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	) );
	$coupon_stats = array();

	foreach ( $coupons as $coupon_post ) {
		$coupon = new WC_Coupon( $coupon_post->ID );
		$stats = primefit_get_coupon_stats( $coupon->get_code() );
		if ( $stats['total_uses'] > 0 ) {
			$coupon_stats[] = array(
				'code' => $coupon->get_code(),
				'stats' => $stats,
				'last_reset' => primefit_get_coupon_last_reset_date( $coupon->get_code() )
			);
		}
	}

	// Sort by total savings
	usort( $coupon_stats, function( $a, $b ) {
		return $b['stats']['total_savings'] <=> $a['stats']['total_savings'];
	});

	$top_performing = array_slice( $coupon_stats, 0, 5 );
	?>
	<div class="coupon-analytics-overview">
		<div class="analytics-grid">
			<div class="analytics-card">
				<h3><?php _e( 'Total Coupons Used', 'primefit' ); ?></h3>
				<div class="stat-number"><?php echo number_format( $overall_stats['total_uses'] ); ?></div>
			</div>
			<div class="analytics-card">
				<h3><?php _e( 'Total Savings Given', 'primefit' ); ?></h3>
				<div class="stat-number">€<?php echo number_format( $overall_stats['total_savings'], 2 ); ?></div>
			</div>
			<div class="analytics-card">
				<h3><?php _e( 'Unique Customers', 'primefit' ); ?></h3>
				<div class="stat-number"><?php echo number_format( $overall_stats['unique_emails'] ); ?></div>
			</div>
			<div class="analytics-card">
				<h3><?php _e( 'Active Coupons', 'primefit' ); ?></h3>
				<div class="stat-number"><?php echo count( $coupon_stats ); ?></div>
			</div>
		</div>

		<?php if ( ! empty( $top_performing ) ) : ?>
		<div class="top-coupons-section">
			<h2><?php _e( 'Top Performing Coupons', 'primefit' ); ?></h2>
			<div class="coupon-grid">
				<?php foreach ( $top_performing as $coupon ) : ?>
				<div class="coupon-card">
					<h4><?php echo esc_html( $coupon['code'] ); ?></h4>
					<div class="coupon-stats">
						<div class="stat-item">
							<span class="stat-label"><?php _e( 'Uses:', 'primefit' ); ?></span>
							<span class="stat-value"><?php echo number_format( $coupon['stats']['total_uses'] ); ?></span>
						</div>
						<div class="stat-item">
							<span class="stat-label"><?php _e( 'Savings:', 'primefit' ); ?></span>
							<span class="stat-value">€<?php echo number_format( $coupon['stats']['total_savings'], 2 ); ?></span>
						</div>
						<div class="stat-item">
							<span class="stat-label"><?php _e( 'Avg/User:', 'primefit' ); ?></span>
							<span class="stat-value">€<?php echo number_format( $coupon['stats']['avg_savings_per_user'], 2 ); ?></span>
						</div>
						<?php if ( $coupon['last_reset'] ) : ?>
						<div class="stat-item reset-info">
							<span class="stat-label"><?php _e( 'Last Reset:', 'primefit' ); ?></span>
							<span class="stat-value"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $coupon['last_reset'] ) ); ?></span>
						</div>
						<?php endif; ?>
					</div>
					<div class="coupon-actions">
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'reset_coupon_stats' ); ?>
							<input type="hidden" name="coupon_code" value="<?php echo esc_attr( $coupon['code'] ); ?>">
							<button type="submit" name="reset_coupon" class="button button-secondary"
									onclick="return confirm('<?php printf( esc_js( __( 'Are you sure you want to reset statistics for coupon "%s"? This action cannot be undone.', 'primefit' ) ), esc_js( $coupon['code'] ) ); ?>');">
								<?php _e( 'Reset Stats', 'primefit' ); ?>
							</button>
						</form>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<style>
	.coupon-analytics-overview {
		margin-bottom: 40px;
	}

	.analytics-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 20px;
		margin-bottom: 30px;
	}

	.analytics-card {
		background: #fff;
		border: 1px solid #e1e1e1;
		border-radius: 8px;
		padding: 20px;
		text-align: center;
		box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	}

	.analytics-card h3 {
		margin: 0 0 10px 0;
		color: #666;
		font-size: 14px;
		text-transform: uppercase;
	}

	.stat-number {
		font-size: 2.5em;
		font-weight: bold;
		color: #0073aa;
		margin: 0;
	}

	.top-coupons-section {
		background: #fff;
		border: 1px solid #e1e1e1;
		border-radius: 8px;
		padding: 20px;
	}

	.top-coupons-section h2 {
		margin-top: 0;
		color: #333;
	}

	.coupon-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
		gap: 20px;
	}

	.coupon-card {
		border: 1px solid #e1e1e1;
		border-radius: 6px;
		padding: 15px;
		background: #f9f9f9;
	}

	.coupon-card h4 {
		margin: 0 0 15px 0;
		color: #333;
		font-size: 16px;
	}

	.coupon-stats {
		margin-bottom: 15px;
	}

	.stat-item {
		display: flex;
		justify-content: space-between;
		margin-bottom: 5px;
		font-size: 13px;
	}

	.stat-label {
		color: #666;
	}

	.stat-value {
		font-weight: bold;
		color: #333;
	}

	.reset-info .stat-value {
		color: #d63638;
	}

	.coupon-actions .button {
		font-size: 12px;
		padding: 6px 12px;
	}
	</style>
	<?php
}

/**
 * Display coupon analytics table
 */
function primefit_display_coupon_analytics_table() {
	// Get all coupons with usage data
	$coupons = get_posts( array(
		'post_type' => 'shop_coupon',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	) );
	$coupons_with_data = array();

	foreach ( $coupons as $coupon_post ) {
		$coupon = new WC_Coupon( $coupon_post->ID );
		$stats = primefit_get_coupon_stats( $coupon->get_code() );
		if ( $stats['total_uses'] > 0 ) {
			$coupons_with_data[] = array(
				'coupon' => $coupon,
				'stats' => $stats,
				'last_reset' => primefit_get_coupon_last_reset_date( $coupon->get_code() )
			);
		}
	}

	if ( empty( $coupons_with_data ) ) {
		echo '<p>' . __( 'No coupon usage data available yet.', 'primefit' ) . '</p>';
		return;
	}

	?>
	<div class="coupon-analytics-table-section">
		<h2><?php _e( 'All Coupon Statistics', 'primefit' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php _e( 'Coupon Code', 'primefit' ); ?></th>
					<th><?php _e( 'Usage Count', 'primefit' ); ?></th>
					<th><?php _e( 'Total Savings', 'primefit' ); ?></th>
					<th><?php _e( 'Unique Users', 'primefit' ); ?></th>
					<th><?php _e( 'Avg. per User', 'primefit' ); ?></th>
					<th><?php _e( 'Last Reset', 'primefit' ); ?></th>
					<th><?php _e( 'Actions', 'primefit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $coupons_with_data as $coupon_data ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $coupon_data['coupon']->get_code() ); ?></strong></td>
					<td><?php echo number_format( $coupon_data['stats']['total_uses'] ); ?></td>
					<td>€<?php echo number_format( $coupon_data['stats']['total_savings'], 2 ); ?></td>
					<td><?php echo number_format( $coupon_data['stats']['unique_emails'] ); ?></td>
					<td>€<?php echo number_format( $coupon_data['stats']['avg_savings_per_user'], 2 ); ?></td>
					<td>
						<?php if ( $coupon_data['last_reset'] ) : ?>
							<span style="color: #d63638; font-size: 12px;">
								<?php echo date_i18n( get_option( 'date_format' ), strtotime( $coupon_data['last_reset'] ) ); ?>
							</span>
						<?php else : ?>
							<span style="color: #999; font-size: 12px;"><?php _e( 'Never', 'primefit' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'reset_coupon_stats' ); ?>
							<input type="hidden" name="coupon_code" value="<?php echo esc_attr( $coupon_data['coupon']->get_code() ); ?>">
							<button type="submit" name="reset_coupon" class="button button-secondary button-small"
									onclick="return confirm('<?php printf( esc_js( __( 'Are you sure you want to reset statistics for coupon "%s"? This action cannot be undone.', 'primefit' ) ), esc_js( $coupon_data['coupon']->get_code() ) ); ?>');">
								<?php _e( 'Reset', 'primefit' ); ?>
							</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Display coupon reset history
 */
function primefit_display_coupon_reset_history() {
	$reset_history = primefit_get_coupon_reset_history( null, 20 );

	if ( empty( $reset_history ) ) {
		echo '<div class="coupon-reset-history-section">';
		echo '<h2>' . __( 'Recent Coupon Resets', 'primefit' ) . '</h2>';
		echo '<p>' . __( 'No coupon reset history available yet.', 'primefit' ) . '</p>';
		echo '</div>';
		return;
	}

	?>
	<div class="coupon-reset-history-section">
		<h2><?php _e( 'Recent Coupon Resets', 'primefit' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php _e( 'Coupon Code', 'primefit' ); ?></th>
					<th><?php _e( 'Reset Date', 'primefit' ); ?></th>
					<th><?php _e( 'Previous Uses', 'primefit' ); ?></th>
					<th><?php _e( 'Previous Savings', 'primefit' ); ?></th>
					<th><?php _e( 'Reset By', 'primefit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $reset_history as $reset ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $reset->coupon_code ); ?></strong></td>
					<td><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $reset->reset_date ) ); ?></td>
					<td><?php echo number_format( $reset->previous_uses_count ); ?></td>
					<td>€<?php echo number_format( $reset->previous_total_savings, 2 ); ?></td>
					<td>
						<?php if ( $reset->reset_by_user_id ) : ?>
							<?php $user = get_user_by( 'id', $reset->reset_by_user_id ); ?>
							<?php echo $user ? esc_html( $user->display_name ) : __( 'User ID:', 'primefit' ) . ' ' . $reset->reset_by_user_id; ?>
						<?php else : ?>
							<?php _e( 'System', 'primefit' ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
