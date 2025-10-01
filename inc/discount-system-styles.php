<?php
/**
 * PrimeFit Theme Discount System Styles
 *
 * Enqueue styles for discount system admin interface
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue admin styles for discount system
 */
add_action( 'admin_enqueue_scripts', 'primefit_enqueue_discount_system_styles' );
function primefit_enqueue_discount_system_styles( $hook ) {
	// Only load on coupon-related pages
	if ( ! in_array( $hook, array( 'edit.php', 'post.php', 'admin.php' ) ) ) {
		return;
	}

	// Check if we're on a coupon-related page
	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'shop_coupon' ) {
		return;
	}

	wp_add_inline_style( 'wp-admin', '
		/* Discount System Admin Styles */

		/* Coupon List Enhancements */
		.column-usage_count,
		.column-total_savings,
		.column-unique_users,
		.column-avg_savings,
		.column-last_reset {
			width: 120px;
			text-align: center;
		}

		.column-has_restrictions {
			width: 100px;
			text-align: center;
		}

		/* Analytics Page Styles */
		.discount-analytics-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
		}

		.discount-analytics-box {
			background: #fff;
			border: 1px solid #e1e1e1;
			border-radius: 8px;
			padding: 20px;
			text-align: center;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}

		.discount-analytics-number {
			font-size: 2.5em;
			font-weight: bold;
			color: #0073aa;
			margin-bottom: 5px;
		}

		.discount-analytics-label {
			color: #666;
			font-size: 0.9em;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.discount-analytics-chart {
			background: #fff;
			border: 1px solid #e1e1e1;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 20px;
		}

		/* Coupon Form Enhancements */
		.acf-field-true-false .acf-input {
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.acf-field-email .acf-input input,
		.acf-field-text .acf-input input,
		.acf-field-textarea .acf-input textarea {
			width: 100%;
		}

		/* Notification badges */
		.coupon-usage-badge {
			display: inline-block;
			padding: 4px 8px;
			border-radius: 12px;
			font-size: 11px;
			font-weight: bold;
			text-transform: uppercase;
		}

		.coupon-usage-badge.high {
			background: #d4edda;
			color: #155724;
		}

		.coupon-usage-badge.medium {
			background: #fff3cd;
			color: #856404;
		}

		.coupon-usage-badge.low {
			background: #f8d7da;
			color: #721c24;
		}

		/* Enhanced table styling */
		.widefat .column-usage_count {
			font-weight: bold;
		}

		.widefat .column-total_savings {
			color: #28a745;
			font-weight: bold;
		}

		/* Loading states */
		.discount-loading {
			opacity: 0.6;
			pointer-events: none;
		}

		/* Responsive adjustments */
		@media screen and (max-width: 782px) {
			.discount-analytics-grid {
				grid-template-columns: 1fr;
			}

			.column-usage_count,
			.column-total_savings,
			.column-unique_users,
			.column-avg_savings,
			.column-last_reset,
			.column-has_restrictions {
				width: auto;
			}
		}
	');
}
