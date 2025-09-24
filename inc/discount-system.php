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
 * Create custom database table for discount code tracking
 * Note: Using theme activation instead of plugin activation
 */
add_action( 'after_switch_theme', 'primefit_create_discount_tracking_table_on_theme_activation' );
function primefit_create_discount_tracking_table_on_theme_activation() {
	primefit_create_discount_tracking_table();
}
function primefit_create_discount_tracking_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'discount_code_tracking';

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
	dbDelta( $sql );

	// Store version for future updates
	add_option( 'primefit_discount_tracking_version', '1.0' );
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
		// Add new columns for version 1.1
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN ip_address varchar(45) DEFAULT NULL AFTER usage_date" );
		$wpdb->query( "ALTER TABLE $table_name ADD COLUMN user_agent text DEFAULT NULL AFTER ip_address" );

		update_option( 'primefit_discount_tracking_version', '1.1' );
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
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_primefit_usage_by_date_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_primefit_usage_by_date_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_primefit_top_users_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_primefit_top_users_%'" );
}

/**
 * Calculate the actual savings amount for a coupon
 */
function primefit_calculate_coupon_savings( $coupon, $order ) {
	$savings = 0;

	// Get discount amount based on coupon type
	switch ( $coupon->get_discount_type() ) {
		case 'percent':
			$discount_percent = $coupon->get_amount();
			$savings = $order->get_subtotal() * ( $discount_percent / 100 );
			break;

		case 'fixed_cart':
			$savings = $coupon->get_amount();
			break;

		case 'fixed_product':
			// Calculate based on items that used this coupon
			$savings = $order->get_discount_total();
			break;

		default:
			$savings = $order->get_discount_total();
			break;
	}

	return max( 0, (float) $savings );
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
		$cached = $wpdb->get_results( $wpdb->prepare( $usage_by_date_query, $params ) );
		
		// Cache for 1 hour
		set_transient( $cache_key, $cached, HOUR_IN_SECONDS );
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
		$cached = $wpdb->get_results( $wpdb->prepare( $top_users_query, $params ) );
		
		// Cache for 1 hour
		set_transient( $cache_key, $cached, HOUR_IN_SECONDS );
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
	
	$stats = $wpdb->get_row( $wpdb->prepare( $stats_query, $params ) );
	
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
 * Schedule weekly coupon report email
 */
add_action( 'init', 'primefit_schedule_weekly_coupon_reports' );
function primefit_schedule_weekly_coupon_reports() {
	if ( ! wp_next_scheduled( 'primefit_weekly_coupon_report' ) ) {
		wp_schedule_event( time(), 'weekly', 'primefit_weekly_coupon_report' );
	}
}

/**
 * Send weekly coupon usage report
 */
add_action( 'primefit_weekly_coupon_report', 'primefit_send_weekly_coupon_report' );
function primefit_send_weekly_coupon_report() {
	// Ensure WooCommerce is loaded and available
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_coupons' ) ) {
		return;
	}

	// Get last week's date range
	$end_date = current_time( 'Y-m-d' );
	$start_date = date( 'Y-m-d', strtotime( '-7 days', strtotime( $end_date ) ) );

	// Get all coupon usage stats for the past week
	$weekly_stats = primefit_get_discount_stats( null, null, $start_date, $end_date );

	// Get coupon-specific stats
	$coupons = wc_get_coupons( array( 'posts_per_page' => -1 ) );
	$coupon_stats = array();

	foreach ( $coupons as $coupon ) {
		$coupon_stats[] = array(
			'code' => $coupon->get_code(),
			'stats' => primefit_get_discount_stats( $coupon->get_code(), null, $start_date, $end_date )
		);
	}

	// Get top performing coupons
	usort( $coupon_stats, function( $a, $b ) {
		return $b['stats']['total_savings'] <=> $a['stats']['total_savings'];
	} );
	$top_coupons = array_slice( $coupon_stats, 0, 10 );

	// Get email recipients for weekly reports
	$recipients = primefit_get_weekly_report_recipients();

	if ( empty( $recipients ) ) {
		return; // No recipients configured
	}

	$subject = sprintf( __( 'Weekly Coupon Usage Report - %s', 'primefit' ), $start_date . ' to ' . $end_date );

	$message = primefit_generate_weekly_report_html( $weekly_stats, $coupon_stats, $top_coupons, $start_date, $end_date );

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>'
	);

	foreach ( $recipients as $recipient ) {
		wp_mail( $recipient, $subject, $message, $headers );
	}
}

/**
 * Get recipients for weekly coupon reports
 */
function primefit_get_weekly_report_recipients() {
	// Ensure WooCommerce is loaded
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_coupons' ) ) {
		return array( get_option( 'admin_email' ) );
	}

	$recipients = array();

	// Get admin email
	$admin_email = get_option( 'admin_email' );
	if ( $admin_email ) {
		$recipients[] = $admin_email;
	}

	// Get recipients from coupon notification settings
	$coupons = wc_get_coupons( array( 'posts_per_page' => -1 ) );

	foreach ( $coupons as $coupon ) {
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
}
