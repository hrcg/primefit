<?php
/**
 * Email Styles
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-styles.php.
 *
 * @package WooCommerce/Templates/Emails
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get site name for use in styles
$site_name = get_bloginfo( 'name' );
?>

<style type="text/css">
	/* Base Email Styles */
	body {
		background-color: #ffffff !important;
		font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif !important;
		font-size: 16px !important;
		line-height: 1.6 !important;
		color: #333333 !important;
		margin: 0 !important;
		padding: 0 !important;
	}

	/* Email Container */
	.email-container {
		max-width: 600px !important;
		margin: 0 auto !important;
		background-color: #ffffff !important;
	}

	/* Header Styles */
	.email-header {
		background-color: #ffffff !important;
		border-bottom: 1px solid #e5e5e5 !important;
		padding: 40px 20px 20px 20px !important;
		text-align: center !important;
	}

	.site-logo {
		display: block !important;
		margin: 0 auto 20px auto !important;
		max-width: 120px !important;
		height: auto !important;
	}

	.site-name {
		font-size: 24px !important;
		font-weight: 700 !important;
		color: #333333 !important;
		margin: 0 !important;
		letter-spacing: -0.5px !important;
	}

	.email-heading {
		font-size: 28px !important;
		font-weight: 700 !important;
		color: #333333 !important;
		margin: 40px 0 0 0 !important;
		letter-spacing: -0.5px !important;
		text-align: center !important;
	}

	/* Content Styles */
	.email-content {
		padding: 40px 0 0 0 !important;
	}

	/* Introduction Section */
	.email-introduction {
		padding: 0 0 30px 0 !important;
		margin: 0 0 30px 0 !important;
		border-bottom: 1px solid #f0f0f0 !important;
	}

	/* Typography */
	h1, h2, h3, h4, h5, h6 {
		font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif !important;
		font-weight: 700 !important;
		color: #333333 !important;
		margin: 0 0 20px 0 !important;
		line-height: 1.3 !important;
	}

	h2 {
		font-size: 24px !important;
	}

	h3 {
		font-size: 20px !important;
	}

	p {
		font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif !important;
		font-size: 16px !important;
		line-height: 1.6 !important;
		color: #333333 !important;
		margin: 0 0 20px 0 !important;
		padding: 0 !important;
	}

	/* Order Details Table */
	.order-details {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 30px 0 !important;
		background-color: #ffffff !important;
		border: 1px solid #e5e5e5 !important;
	}

	.order-details th {
		background-color: #f8f8f8 !important;
		font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif !important;
		font-size: 14px !important;
		font-weight: 700 !important;
		color: #333333 !important;
		padding: 15px !important;
		text-align: left !important;
		border-bottom: 1px solid #e5e5e5 !important;
	}

	.order-details td {
		font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif !important;
		font-size: 14px !important;
		color: #666666 !important;
		padding: 15px !important;
		border-bottom: 1px solid #f0f0f0 !important;
	}

	.order-details .product-name {
		color: #333333 !important;
		font-weight: 500 !important;
	}

	.order-details .product-quantity {
		color: #666666 !important;
	}

	.order-details .product-total {
		color: #333333 !important;
		font-weight: 700 !important;
	}

	/* Order Totals */
	.order-totals {
		width: 100% !important;
		margin: 30px 0 !important;
		padding: 20px !important;
		background-color: #f8f8f8 !important;
		border: 1px solid #e5e5e5 !important;
		border-radius: 4px !important;
	}

	.order-totals th {
		font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif !important;
		font-size: 14px !important;
		font-weight: 700 !important;
		color: #333333 !important;
		padding: 8px 0 !important;
		text-align: left !important;
		border: none !important;
		background: none !important;
	}

	.order-totals td {
		font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif !important;
		font-size: 14px !important;
		color: #333333 !important;
		padding: 8px 0 !important;
		text-align: right !important;
		border: none !important;
		background: none !important;
	}

	.order-totals .order-total {
		font-size: 18px !important;
		font-weight: 700 !important;
		color: #333333 !important;
	}

	/* Customer Details */
	.customer-details {
		margin: 30px 0 !important;
		padding: 20px !important;
		background-color: #f8f8f8 !important;
		border: 1px solid #e5e5e5 !important;
		border-radius: 4px !important;
	}

	.customer-details h3 {
		font-size: 16px !important;
		margin: 0 0 15px 0 !important;
		color: #333333 !important;
	}

	.customer-details p {
		font-size: 14px !important;
		margin: 0 0 8px 0 !important;
		color: #666666 !important;
	}

	/* Buttons */
	.button {
		display: inline-block !important;
		padding: 12px 24px !important;
		background-color: #333333 !important;
		color: #ffffff !important;
		text-decoration: none !important;
		font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif !important;
		font-size: 14px !important;
		font-weight: 600 !important;
		border-radius: 4px !important;
		margin: 10px 5px !important;
		text-align: center !important;
		border: none !important;
		cursor: pointer !important;
	}

	.button:hover {
		background-color: #222222 !important;
		color: #ffffff !important;
	}

	.button-secondary {
		background-color: transparent !important;
		color: #333333 !important;
		border: 1px solid #e5e5e5 !important;
	}

	.button-secondary:hover {
		background-color: #f8f8f8 !important;
		color: #333333 !important;
	}

	/* Footer Styles */
	.email-footer {
		padding: 60px 20px 40px 20px !important;
		text-align: center !important;
		border-top: 1px solid #e5e5e5 !important;
	}

	.footer-text {
		font-size: 14px !important;
		color: #666666 !important;
		margin: 0 0 30px 0 !important;
		line-height: 1.5 !important;
	}

	.footer-disclaimer {
		font-size: 12px !important;
		color: #999999 !important;
		margin: 0 0 20px 0 !important;
		line-height: 1.4 !important;
	}

	.footer-links {
		margin: 20px 0 0 0 !important;
	}

	/* Additional Content */
	.email-additional-content {
		padding: 20px 0 !important;
		margin: 30px 0 !important;
		border-top: 1px solid #f0f0f0 !important;
		border-bottom: 1px solid #f0f0f0 !important;
	}

	.email-additional-content p {
		margin: 0 !important;
	}

	/* Responsive Styles */
	@media only screen and (max-width: 600px) {
		.email-header,
		.email-content,
		.email-footer {
			padding-left: 15px !important;
			padding-right: 15px !important;
		}

		.site-name {
			font-size: 20px !important;
		}

		.email-heading {
			font-size: 24px !important;
		}

		.order-details {
			font-size: 12px !important;
		}

		.order-details th,
		.order-details td {
			padding: 10px !important;
		}

		.button {
			display: block !important;
			width: 100% !important;
			margin: 10px 0 !important;
		}
	}

	/* High Priority Email Styles */
	@media only screen and (max-width: 480px) {
		body {
			font-size: 14px !important;
		}

		.email-header {
			padding: 30px 15px 15px 15px !important;
		}

		.site-logo {
			max-width: 100px !important;
		}

		.site-name {
			font-size: 18px !important;
		}

		.email-heading {
			font-size: 22px !important;
		}
	}
</style>

<?php
/**
 * Output the email styles.
 *
 * This template outputs the CSS styles for WooCommerce emails.
 * It's automatically loaded when the 'woocommerce_email_styles' action is triggered.
 */
