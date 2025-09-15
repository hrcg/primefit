<?php
/**
 * PrimeFit Theme WooCommerce Integration
 *
 * WooCommerce-specific functionality and customizations
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only load if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Disable default WooCommerce stylesheets
 * We use our own custom styles instead
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Admin: Legacy product custom fields notice
 * Note: These fields are now handled by ACF. This function is kept for reference.
 */
// Commented out - replaced by ACF fields
// add_action( 'woocommerce_product_options_general_product_data', 'primefit_add_product_custom_fields' );

/**
 * Admin: Legacy Product Features Fields notice
 * Note: These fields are now handled by ACF. This function is kept for reference.
 */
// Commented out - replaced by ACF fields
// add_action( 'woocommerce_product_options_general_product_data', 'primefit_add_product_features_fields' );

/**
 * Admin: Legacy Product Information Fields notice
 * Note: These fields are now handled by ACF. This function is kept for reference.
 */
// Commented out - replaced by ACF fields
// add_action( 'woocommerce_product_options_general_product_data', 'primefit_add_product_info_fields' );

/**
 * Legacy: Save product custom fields
 * Note: ACF handles field saving automatically. This is kept for legacy data migration.
 */
// Commented out - ACF handles field saving
// add_action( 'woocommerce_process_product_meta', 'primefit_save_product_custom_fields' );

/**
 * Add tabs for Highlights and Details on product page
 */
add_filter( 'woocommerce_product_tabs', 'primefit_add_product_tabs' );
function primefit_add_product_tabs( $tabs ) {
	// Get legacy highlights and details with ACF fallback
	$highlights = primefit_get_product_field( 'highlights', get_the_ID(), 'primefit_highlights' );
	$details    = primefit_get_product_field( 'details', get_the_ID(), 'primefit_details' );
	
	if ( ! empty( $highlights ) ) {
		$tabs['primefit_highlights'] = [
			'title'    => __( 'Highlights', 'primefit' ),
			'priority' => 15,
			'callback' => function() use ( $highlights ) {
				$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $highlights ) ) );
				echo '<ul class="pf-highlights">';
				foreach ( $lines as $line ) {
					echo '<li>' . wp_kses_post( $line ) . '</li>';
				}
				echo '</ul>';
			},
		];
	}
	
	if ( ! empty( $details ) ) {
		$tabs['additional_information']['callback'] = function() use ( $details ) {
			echo wp_kses_post( wpautop( $details ) );
		};
	}
	
	return $tabs;
}

/**
 * Legacy: Meta box for Additional Sections
 * Note: This functionality is now handled by ACF. Meta box removed.
 */
// Commented out - replaced by ACF fields
// add_action( 'add_meta_boxes', 'primefit_add_product_meta_box' );
// add_action( 'save_post_product', 'primefit_save_product_additional_html' );

/**
 * Display additional sections on product page
 */
add_action( 'woocommerce_after_single_product_summary', 'primefit_display_additional_sections', 12 );
function primefit_display_additional_sections() {
	// Get additional HTML using ACF with fallback to legacy meta
	$additional = primefit_get_product_field( 'additional_html', get_the_ID(), 'primefit_additional_html' );
	if ( ! empty( $additional ) ) {
		echo '<section class="pf-additional container">' . wp_kses_post( $additional ) . '</section>';
	}
}

/**
 * Header cart fragments (update cart count asynchronously)
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'primefit_header_cart_fragment' );
function primefit_header_cart_fragment( $fragments ) {
	ob_start();
	?>
	<span class="cart-count" data-cart-count>
		<?php echo WC()->cart ? intval( WC()->cart->get_cart_contents_count() ) : 0; ?>
	</span>
	<?php
	$fragments['span[data-cart-count]'] = ob_get_clean();
	return $fragments;
}

/**
 * AJAX handler for notify availability
 */
add_action( 'wp_ajax_primefit_notify_availability', 'primefit_handle_notify_availability' );
add_action( 'wp_ajax_nopriv_primefit_notify_availability', 'primefit_handle_notify_availability' );
function primefit_handle_notify_availability() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'primefit_nonce' ) ) {
		wp_die( 'Security check failed' );
	}
	
	$product_id = intval( $_POST['product_id'] );
	$email = sanitize_email( $_POST['email'] );
	
	if ( ! $product_id || ! $email ) {
		wp_send_json_error( 'Invalid data provided' );
	}
	
	// Store notification request (you might want to use a custom table or post meta)
	$notifications = get_post_meta( $product_id, 'primefit_notify_requests', true );
	if ( ! is_array( $notifications ) ) {
		$notifications = array();
	}
	
	// Check if email already exists
	$email_exists = false;
	foreach ( $notifications as $notification ) {
		if ( $notification['email'] === $email ) {
			$email_exists = true;
			break;
		}
	}
	
	if ( ! $email_exists ) {
		$notifications[] = array(
			'email' => $email,
			'date' => current_time( 'mysql' ),
			'status' => 'pending'
		);
		
		update_post_meta( $product_id, 'primefit_notify_requests', $notifications );
		
		// Send confirmation email (optional)
		$subject = sprintf( __( 'You\'ll be notified when %s is back in stock', 'primefit' ), get_the_title( $product_id ) );
		$message = sprintf( __( 'Thank you for your interest in %s. We\'ll notify you as soon as it\'s back in stock.', 'primefit' ), get_the_title( $product_id ) );
		
		wp_mail( $email, $subject, $message );
		
		wp_send_json_success( 'You\'ll be notified when this product is back in stock!' );
	} else {
		wp_send_json_error( 'You\'re already on the notification list for this product.' );
	}
}

/**
 * Override WooCommerce single product templates
 */
add_action( 'wp', 'primefit_override_woocommerce_templates' );
function primefit_override_woocommerce_templates() {
	// Only run on single product pages
	if ( ! is_product() ) {
		return;
	}
	
	// Remove default WooCommerce single product actions
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
	
	// Remove default product images
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
	
	// Remove default tabs
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	
	// Add our custom templates
	add_action( 'woocommerce_before_single_product_summary', 'primefit_show_product_images', 20 );
	add_action( 'woocommerce_single_product_summary', 'primefit_template_single_product_summary', 5 );
	add_action( 'woocommerce_after_single_product_summary', 'primefit_output_product_data_tabs', 10 );
	add_action( 'woocommerce_after_single_product_summary', 'primefit_show_product_features', 12 );
}

/**
 * Custom product images display
 */
function primefit_show_product_images() {
	get_template_part( 'woocommerce/single-product/product-image' );
}

/**
 * Custom product summary
 */
function primefit_template_single_product_summary() {
	get_template_part( 'woocommerce/single-product/product-summary' );
}

/**
 * Custom product tabs
 */
function primefit_output_product_data_tabs() {
	get_template_part( 'woocommerce/single-product/tabs/tabs' );
}

/**
 * Show product features and technical highlights
 */
function primefit_show_product_features() {
	get_template_part( 'woocommerce/single-product/after-single-product-summary' );
}