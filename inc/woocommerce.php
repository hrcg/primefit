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
	// Log that WooCommerce isn't available yet
	error_log('CART DEBUG: WooCommerce class not available when woocommerce.php is loaded');
	return;
}

// Log that WooCommerce is available
error_log('CART DEBUG: WooCommerce class is available - setting up cart functions');

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
 * Replace default WooCommerce quantity input with custom one
 */
add_filter( 'woocommerce_quantity_input_args', 'primefit_override_quantity_input', 10, 2 );
function primefit_override_quantity_input( $args, $product ) {
	// This filter allows us to modify the args, but we'll handle the replacement in the template
	return $args;
}

/**
 * Header cart fragments (update cart count and mini cart content)
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'primefit_header_cart_fragment' );
function primefit_header_cart_fragment( $fragments ) {
	// Add cart count fragment for header
	ob_start();
	?>
	<span class="cart-count" data-cart-count>
		<?php echo WC()->cart ? intval( WC()->cart->get_cart_contents_count() ) : 0; ?>
	</span>
	<?php
	$fragments['span[data-cart-count]'] = ob_get_clean();
	
	// Add mini cart content fragment using WooCommerce standard structure
	ob_start();
	?>
	<div class="widget_shopping_cart_content">
		<?php if ( function_exists( 'woocommerce_mini_cart' ) ) { woocommerce_mini_cart(); } ?>
	</div>
	<?php
	$fragments['div.widget_shopping_cart_content'] = ob_get_clean();
	
	return $fragments;
}

/**
 * Add AJAX endpoint for refreshing cart fragments
 * This ensures WooCommerce core cart functions work properly with our custom structure
 */
add_action( 'wp_ajax_woocommerce_get_refreshed_fragments', 'primefit_woocommerce_get_refreshed_fragments' );
add_action( 'wp_ajax_nopriv_woocommerce_get_refreshed_fragments', 'primefit_woocommerce_get_refreshed_fragments' );

function primefit_woocommerce_get_refreshed_fragments() {
	WC_AJAX::get_refreshed_fragments();
}

/**
 * Custom empty cart message - Multiple approaches for better compatibility
 * Based on WordPress support thread: https://wordpress.org/support/topic/empty-cart-no-message-help/
 */

// Approach 1: Filter the empty cart message text
add_filter( 'wc_empty_cart_message', 'primefit_custom_empty_cart_message', 10 );

// Approach 2: Action hook for cart page (more reliable for dynamic emptying)
remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
add_action( 'woocommerce_cart_is_empty', 'primefit_custom_empty_cart_action', 10 );

function primefit_get_custom_empty_cart_html() {
	$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$mens_url   = $shop_url;
	$womens_url = $shop_url;

	// Try common category slugs for Mens/Womens; fall back to shop if missing
	$mens_term = get_term_by( 'slug', 'mens', 'product_cat' );
	if ( ! $mens_term ) {
		$mens_term = get_term_by( 'slug', 'men', 'product_cat' );
	}
	if ( $mens_term && ! is_wp_error( $mens_term ) ) {
		$mens_url = get_term_link( $mens_term );
	}

	$womens_term = get_term_by( 'slug', 'womens', 'product_cat' );
	if ( ! $womens_term ) {
		$womens_term = get_term_by( 'slug', 'women', 'product_cat' );
	}
	if ( $womens_term && ! is_wp_error( $womens_term ) ) {
		$womens_url = get_term_link( $womens_term );
	}

	$html = '<div class="pf-mini-cart-empty" aria-live="polite">';
	$html .= '<div class="pf-mini-cart-empty__graphic" aria-hidden="true"></div>';
	$html .= '<h3 class="pf-mini-cart-empty__title">' . esc_html__( 'YOUR BAG IS EMPTY', 'primefit' ) . '</h3>';
	$html .= '<p class="pf-mini-cart-empty__text">' . esc_html__( 'There are no products in your bag', 'primefit' ) . '</p>';
	$html .= '<div class="pf-mini-cart-empty__actions">';
	$html .= '<a class="button pf-mini-cart-empty__btn" href="' . esc_url( $mens_url ) . '">' . esc_html__( 'SHOP MENS', 'primefit' ) . '</a>';
	$html .= '<a class="button pf-mini-cart-empty__btn" href="' . esc_url( $womens_url ) . '">' . esc_html__( 'SHOP WOMENS', 'primefit' ) . '</a>';
	$html .= '</div>';
	$html .= '</div>';
	
	return $html;
}

function primefit_custom_empty_cart_message( $message ) {
	return primefit_get_custom_empty_cart_html();
}

function primefit_custom_empty_cart_action() {
	// Only show if cart is actually empty (fix from WordPress support thread)
	if ( WC()->cart->get_cart_contents_count() == 0 ) {
		echo primefit_get_custom_empty_cart_html();
	}
}

/**
 * AJAX handler for notify availability
 */
add_action( 'wp_ajax_primefit_notify_availability', 'primefit_handle_notify_availability' );
add_action( 'wp_ajax_nopriv_primefit_notify_availability', 'primefit_handle_notify_availability' );

/**
 * AJAX: Update cart item quantity (used by mini-cart controls)
 * Frontend action: wc_ajax_update_cart_item_quantity
 */
add_action( 'wp_ajax_wc_ajax_update_cart_item_quantity', 'primefit_wc_update_cart_item_quantity' );
add_action( 'wp_ajax_nopriv_wc_ajax_update_cart_item_quantity', 'primefit_wc_update_cart_item_quantity' );
function primefit_wc_update_cart_item_quantity() {
    // Basic validation
    if ( ! isset( $_POST['cart_item_key'], $_POST['quantity'], $_POST['security'] ) ) {
        wp_send_json_error( __( 'Invalid request', 'primefit' ), 400 );
    }

    // Verify nonce matches the one localized in JS
    if ( ! wp_verify_nonce( $_POST['security'], 'woocommerce_update_cart_nonce' ) ) {
        wp_send_json_error( __( 'Security check failed', 'primefit' ), 403 );
    }

    $cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) );
    $quantity      = (int) $_POST['quantity'];

    if ( $quantity < 1 ) {
        $quantity = 1;
    }

    // Ensure cart exists
    if ( ! WC()->cart ) {
        wp_send_json_error( __( 'Cart not available', 'primefit' ), 500 );
    }

    // Update quantity; set_quantity returns WC_Cart_Item or false
    $updated = WC()->cart->set_quantity( $cart_item_key, $quantity, true );

    if ( false === $updated ) {
        wp_send_json_error( __( 'Failed to update quantity', 'primefit' ), 400 );
    }

    // Recalculate totals and refresh fragments
    WC()->cart->calculate_totals();

    $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );

    wp_send_json_success( array(
        'fragments' => $fragments,
        'cart_hash' => WC()->cart->get_cart_hash(),
    ) );
}

/**
 * AJAX: Remove cart item (used by mini-cart remove button fallback)
 * Frontend action: wc_ajax_remove_cart_item
 */
add_action( 'wp_ajax_wc_ajax_remove_cart_item', 'primefit_wc_remove_cart_item' );
add_action( 'wp_ajax_nopriv_wc_ajax_remove_cart_item', 'primefit_wc_remove_cart_item' );
function primefit_wc_remove_cart_item() {
    if ( ! isset( $_POST['cart_item_key'], $_POST['security'] ) ) {
        wp_send_json_error( __( 'Invalid request', 'primefit' ), 400 );
    }

    if ( ! wp_verify_nonce( $_POST['security'], 'woocommerce_remove_cart_nonce' ) ) {
        wp_send_json_error( __( 'Security check failed', 'primefit' ), 403 );
    }

    $cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) );

    if ( ! WC()->cart ) {
        wp_send_json_error( __( 'Cart not available', 'primefit' ), 500 );
    }

    $removed = WC()->cart->remove_cart_item( $cart_item_key );

    if ( ! $removed ) {
        wp_send_json_error( __( 'Failed to remove item', 'primefit' ), 400 );
    }

    WC()->cart->calculate_totals();

    $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );

    wp_send_json_success( array(
        'fragments' => $fragments,
        'cart_hash' => WC()->cart->get_cart_hash(),
        'cart_contents_count' => WC()->cart->get_cart_contents_count(),
        'cart_is_empty' => WC()->cart->is_empty(),
    ) );
}

/**
 * Register AJAX handlers at proper time
 */
// Removed custom AJAX handlers in favor of WooCommerce core endpoints
/* add_action( 'init', 'primefit_register_cart_ajax_handlers', 20 );
function primefit_register_cart_ajax_handlers() {
	// Double-check WooCommerce is available
	if ( ! class_exists( 'WooCommerce' ) ) {
		error_log('CART DEBUG: WooCommerce still not available at init');
		return;
	}
	
	error_log('CART DEBUG: Registering AJAX handlers at init');
	
	// AJAX handlers for cart updates
	add_action( 'wp_ajax_woocommerce_update_cart_item_quantity', 'primefit_update_cart_item_quantity' );
	add_action( 'wp_ajax_nopriv_woocommerce_update_cart_item_quantity', 'primefit_update_cart_item_quantity' );

	add_action( 'wp_ajax_woocommerce_remove_cart_item', 'primefit_remove_cart_item' );
	add_action( 'wp_ajax_nopriv_woocommerce_remove_cart_item', 'primefit_remove_cart_item' );

	// Debug: Log when actions are registered
	error_log('CART DEBUG: AJAX actions registered for woocommerce_remove_cart_item at init');
} */

/**
 * Update cart item quantity
 */
/* function primefit_update_cart_item_quantity() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['security'], 'woocommerce_update_cart_nonce' ) ) {
		wp_die( 'Security check failed' );
	}
	
	$cart_item_key = sanitize_text_field( $_POST['cart_item_key'] );
	$quantity = intval( $_POST['quantity'] );
	
	if ( $cart_item_key && $quantity > 0 ) {
		WC()->cart->set_quantity( $cart_item_key, $quantity );
		
		// Return updated cart fragments
		wp_send_json_success( array(
			'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
			'cart_hash' => WC()->cart->get_cart_hash(),
		) );
	} else {
		wp_send_json_error( 'Invalid data' );
	}
} */

/**
 * Remove cart item
 */
/* function primefit_remove_cart_item() {
	// Log that function was called
	error_log('CART DEBUG: primefit_remove_cart_item() called');
	error_log('CART DEBUG: POST data: ' . print_r($_POST, true));
	
	// Check if WooCommerce is available
	if ( ! class_exists( 'WooCommerce' ) || ! WC() || ! WC()->cart ) {
		error_log('CART DEBUG: WooCommerce not available');
		wp_send_json_error( 'WooCommerce not available' );
		return;
	}
	
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['security'], 'woocommerce_remove_cart_nonce' ) ) {
		error_log('CART DEBUG: Nonce verification failed');
		wp_send_json_error( 'Security check failed' );
		return;
	}
	
	$cart_item_key = sanitize_text_field( $_POST['cart_item_key'] );
	error_log('CART DEBUG: Cart item key: ' . $cart_item_key);
	
	if ( empty( $cart_item_key ) ) {
		error_log('CART DEBUG: Empty cart item key');
		wp_send_json_error( 'Invalid cart item key' );
		return;
	}
	
	// Get current cart contents
	$cart_contents = WC()->cart->get_cart();
	error_log('CART DEBUG: Current cart contents: ' . print_r(array_keys($cart_contents), true));
	
	// Check if item exists in cart before attempting removal
	if ( ! isset( $cart_contents[ $cart_item_key ] ) ) {
		error_log('CART DEBUG: Item not found in cart');
		wp_send_json_error( 'Item not found in cart' );
		return;
	}
	
	error_log('CART DEBUG: Attempting to remove item');
	
	// Remove the item
	$removed = WC()->cart->remove_cart_item( $cart_item_key );
	
	error_log('CART DEBUG: Remove result: ' . ($removed ? 'SUCCESS' : 'FAILED'));
	
	if ( $removed ) {
		// Multiple approaches to ensure cart persistence
		
		// 1. Calculate totals first
		WC()->cart->calculate_totals();
		// Ensure cart cookies reflect the new state (especially when cart becomes empty)
		if ( function_exists( 'wc_setcookie' ) && method_exists( WC()->cart, 'maybe_set_cart_cookies' ) ) {
			WC()->cart->maybe_set_cart_cookies();
		}
		
		// 2. Force session update
		if ( WC()->session ) {
			WC()->session->set( 'cart', WC()->cart->get_cart_for_session() );
			WC()->session->save_data();
			error_log('CART DEBUG: Session data saved');
		}
		
		// 3. Update persistent cart
		WC()->cart->persistent_cart_update();
		// Re-set cookies after persistent update just in case
		if ( method_exists( WC()->cart, 'maybe_set_cart_cookies' ) ) {
			WC()->cart->maybe_set_cart_cookies();
		}
		
		// 4. Clear cart cache if exists
		if ( function_exists( 'wc_clear_cart_cache' ) ) {
			wc_clear_cart_cache();
		}
		
		// Log final cart state
		$final_cart_contents = WC()->cart->get_cart();
		error_log('CART DEBUG: Final cart contents: ' . print_r(array_keys($final_cart_contents), true));
		error_log('CART DEBUG: Cart is empty: ' . (WC()->cart->is_empty() ? 'YES' : 'NO'));
		error_log('CART DEBUG: Cart hash: ' . WC()->cart->get_cart_hash());
		
		// 5. Double-check by reloading cart from session
		if ( WC()->session ) {
			$session_cart = WC()->session->get( 'cart', array() );
			error_log('CART DEBUG: Session cart after save: ' . print_r(array_keys($session_cart), true));
		}
		
		// Get updated fragments
		$fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );
		
		// Return success with updated cart data
		wp_send_json_success( array(
			'fragments' => $fragments,
			'cart_hash' => WC()->cart->get_cart_hash(),
			'cart_contents_count' => WC()->cart->get_cart_contents_count(),
			'cart_is_empty' => WC()->cart->is_empty(),
			'message' => 'Item successfully removed and cart persisted',
		) );
	} else {
		error_log('CART DEBUG: Failed to remove - WC remove_cart_item returned false');
		wp_send_json_error( 'Failed to remove item from cart' );
	}
} */

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

/**
 * Add shipping progress bar to mini cart
 */
add_action( 'woocommerce_before_mini_cart', 'primefit_mini_cart_shipping_progress' );
function primefit_mini_cart_shipping_progress() {
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	// Get free shipping methods
	$free_shipping_methods = primefit_get_free_shipping_methods();
	if ( empty( $free_shipping_methods ) ) {
		return;
	}
	
	// Get the minimum amount for free shipping
	$free_shipping_min_amount = primefit_get_free_shipping_minimum();
	if ( ! $free_shipping_min_amount ) {
		return;
	}
	
	$cart_total = WC()->cart->get_displayed_subtotal();
	$remaining = $free_shipping_min_amount - $cart_total;
	$progress_percentage = min( ( $cart_total / $free_shipping_min_amount ) * 100, 100 );
	
	?>
	<div class="mini-cart-shipping-progress">
		<?php if ( $remaining > 0 ) : ?>
			<p class="shipping-progress-text">
				<?php printf( 
					__( 'You\'re %s away from Free Standard Shipping', 'primefit' ),
					'<strong>' . wc_price( $remaining ) . '</strong>'
				); ?>
				<?php if ( function_exists( 'wc_help_tip' ) ) : ?>
					<?php echo wc_help_tip( __( 'Add more items to qualify for free shipping', 'primefit' ) ); ?>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<p class="shipping-progress-text shipping-qualified">
				<?php _e( '🎉 You qualify for Free Standard Shipping!', 'primefit' ); ?>
			</p>
		<?php endif; ?>
		
		<div class="shipping-progress-bar">
			<div class="shipping-progress-track">
				<div class="shipping-progress-fill" style="width: <?php echo esc_attr( $progress_percentage ); ?>%"></div>
			</div>
			<div class="shipping-progress-labels">
				<span class="shipping-start">$0</span>
				<span class="shipping-end"><?php echo wc_price( $free_shipping_min_amount ); ?></span>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Get free shipping methods
 */
function primefit_get_free_shipping_methods() {
	$free_shipping_methods = [];
	$shipping_zones = WC_Shipping_Zones::get_zones();
	
	// Check regular zones
	foreach ( $shipping_zones as $zone ) {
		$zone_obj = WC_Shipping_Zones::get_zone( $zone['zone_id'] );
		$shipping_methods = $zone_obj->get_shipping_methods( true );
		
		foreach ( $shipping_methods as $method ) {
			if ( $method->id === 'free_shipping' && $method->is_enabled() ) {
				$free_shipping_methods[] = $method;
			}
		}
	}
	
	// Check worldwide zone (zone 0)
	$worldwide_zone = new WC_Shipping_Zone( 0 );
	$shipping_methods = $worldwide_zone->get_shipping_methods( true );
	
	foreach ( $shipping_methods as $method ) {
		if ( $method->id === 'free_shipping' && $method->is_enabled() ) {
			$free_shipping_methods[] = $method;
		}
	}
	
	return $free_shipping_methods;
}

/**
 * Get the minimum amount required for free shipping
 */
function primefit_get_free_shipping_minimum() {
	$free_shipping_methods = primefit_get_free_shipping_methods();
	$min_amount = 0;
	
	foreach ( $free_shipping_methods as $method ) {
		$method_min_amount = $method->get_option( 'min_amount' );
		if ( $method_min_amount && ( ! $min_amount || $method_min_amount < $min_amount ) ) {
			$min_amount = floatval( $method_min_amount );
		}
	}
	
	return $min_amount;
}

/**
 * Add recommended items section after mini cart items
 */
add_action( 'woocommerce_mini_cart_contents', 'primefit_mini_cart_recommended_items', 25 );
function primefit_mini_cart_recommended_items() {
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	// Get recommended products (you can customize this logic)
	$recommended_products = primefit_get_mini_cart_recommended_products();
	
	if ( empty( $recommended_products ) ) {
		return;
	}
	
	?>
	</ul> <!-- Close the mini cart items list -->
	<div class="mini-cart-recommendations">
		<h3 class="recommendations-title"><?php _e( 'ADD A LITTLE EXTRA', 'primefit' ); ?></h3>
		<p class="recommendations-subtitle"><?php _e( 'Add one or more of these items to get free delivery', 'primefit' ); ?></p>
		
		<div class="recommendations-grid">
			<?php foreach ( array_slice( $recommended_products, 0, 2 ) as $product ) : ?>
				<div class="recommendation-item" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
					<div class="recommendation-image">
						<?php echo $product->get_image( 'thumbnail' ); ?>
					</div>
					<div class="recommendation-details">
						<h4 class="recommendation-name"><?php echo esc_html( $product->get_name() ); ?></h4>
						<span class="recommendation-price"><?php echo $product->get_price_html(); ?></span>
					</div>
					<button type="button" class="recommendation-add-btn" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
						+ <?php _e( 'ADD', 'primefit' ); ?>
					</button>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<ul class="woocommerce-mini-cart <?php echo esc_attr( $args['list_class'] ); ?> hidden-list-start"> <!-- Reopen the list for WooCommerce -->
	<?php
}

/**
 * Note: Mini cart quantity controls are now handled by the custom template override
 * at woocommerce/cart/mini-cart.php instead of using filters
 */

/**
 * Get recommended products for mini cart
 */
function primefit_get_mini_cart_recommended_products() {
	// Get products from specific categories or use cross-sells/up-sells
	$recommended_products = [];
	
	// Option 1: Get cross-sells from cart items
	$cross_sells = array();
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = $cart_item['data'];
		$cross_sells = array_merge( $cross_sells, $product->get_cross_sell_ids() );
	}
	
	if ( ! empty( $cross_sells ) ) {
		$cross_sells = array_unique( $cross_sells );
		foreach ( $cross_sells as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_in_stock() && $product->is_purchasable() ) {
				$recommended_products[] = $product;
			}
		}
	}
	
	// Option 2: Fallback to popular products if no cross-sells
	if ( empty( $recommended_products ) ) {
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 4,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '='
				)
			),
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'total_sales',
			'order'          => 'DESC'
		);
		
		$products = get_posts( $args );
		foreach ( $products as $product_post ) {
			$product = wc_get_product( $product_post->ID );
			if ( $product ) {
				$recommended_products[] = $product;
			}
		}
	}
	
	return $recommended_products;
}

/**
 * Add discount code section to mini cart
 */
add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'primefit_mini_cart_discount_section' );
function primefit_mini_cart_discount_section() {
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	?>
	<div class="mini-cart-discount-section">
		<h3 class="discount-title"><?php _e( 'DISCOUNT CODE', 'primefit' ); ?></h3>
		<form class="mini-cart-coupon-form" method="post">
			<div class="coupon-input-group">
				<input type="text" 
					   name="coupon_code" 
					   class="coupon-code-input" 
					   placeholder="<?php esc_attr_e( 'Enter code', 'primefit' ); ?>" 
					   value=""
					   autocomplete="off">
				<button type="submit" 
						class="apply-coupon-btn" 
						name="apply_coupon">
					<?php _e( 'APPLY', 'primefit' ); ?>
				</button>
			</div>
			<?php wp_nonce_field( 'apply_coupon', 'coupon_nonce' ); ?>
		</form>
		
		<?php if ( function_exists( 'wc_help_tip' ) ) : ?>
			<p class="coupon-help-text">
				<?php echo wc_help_tip( __( 'Gift Card codes can be applied at checkout.', 'primefit' ) ); ?>
				<small><?php _e( 'Gift Card codes can be applied at checkout.', 'primefit' ); ?></small>
			</p>
		<?php else : ?>
			<p class="coupon-help-text">
				<small><?php _e( 'Gift Card codes can be applied at checkout.', 'primefit' ); ?></small>
			</p>
		<?php endif; ?>
		
		<?php
		// Display applied coupons
		$coupons = WC()->cart->get_coupons();
		if ( ! empty( $coupons ) ) : ?>
			<div class="applied-coupons">
				<?php foreach ( $coupons as $code => $coupon ) : ?>
					<div class="applied-coupon">
						<span class="coupon-code"><?php echo esc_html( $code ); ?></span>
						<span class="coupon-discount">-<?php echo wc_price( WC()->cart->get_coupon_discount_amount( $code ) ); ?></span>
						<button type="button" class="remove-coupon" data-coupon="<?php echo esc_attr( $code ); ?>" title="<?php _e( 'Remove coupon', 'primefit' ); ?>">×</button>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Add order summary section to mini cart with payment icons
 */
add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'primefit_mini_cart_order_summary', 15 );
function primefit_mini_cart_order_summary() {
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	?>
	<div class="mini-cart-order-summary">
		<h3 class="order-summary-title"><?php _e( 'ORDER SUMMARY', 'primefit' ); ?></h3>
		
		<div class="order-summary-line">
			<span class="line-label"><?php _e( 'Sub Total', 'primefit' ); ?></span>
			<span class="line-value"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
		</div>
		
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php $packages = WC()->shipping->get_packages(); ?>
			<?php if ( ! empty( $packages ) ) : ?>
				<div class="order-summary-line">
					<span class="line-label"><?php _e( 'Estimated Shipping', 'primefit' ); ?></span>
					<span class="line-value">
						<?php
						$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
						$shipping_total = 0;
						foreach ( $packages as $package ) {
							if ( isset( $package['rates'] ) ) {
								foreach ( $package['rates'] as $method ) {
									if ( in_array( $method->id, $chosen_methods ) ) {
										$shipping_total += $method->cost;
										break;
									}
								}
							}
						}
						if ( $shipping_total > 0 ) {
							echo wc_price( $shipping_total );
						} else {
							echo wc_price( 5 ); // Default shipping estimate
						}
						?>
					</span>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		
		<div class="order-summary-line total-line">
			<span class="line-label"><?php _e( 'Total', 'primefit' ); ?></span>
			<span class="line-value total-value"><?php echo WC()->cart->get_total(); ?></span>
		</div>
	</div>
	<?php
}

/**
 * Add payment icons below checkout button
 */
add_action( 'woocommerce_widget_shopping_cart_after_buttons', 'primefit_mini_cart_payment_icons' );
function primefit_mini_cart_payment_icons() {
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	?>
	<div class="payment-icons">
		<div class="payment-icon visa">VISA</div>
		<div class="payment-icon mastercard">MC</div>
		<div class="payment-icon paypal">PP</div>
		<div class="payment-icon apple-pay">AP</div>
		<div class="payment-icon klarna">K</div>
		<div class="payment-icon amex">AE</div>
		<div class="payment-icon afterpay">A</div>
	</div>
	<?php
}

/**
 * Handle coupon application via AJAX
 */
add_action( 'wp_ajax_apply_coupon', 'primefit_handle_apply_coupon' );
add_action( 'wp_ajax_nopriv_apply_coupon', 'primefit_handle_apply_coupon' );

function primefit_handle_apply_coupon() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['security'], 'apply_coupon' ) ) {
		wp_send_json_error( __( 'Security check failed', 'primefit' ) );
	}
	
	$coupon_code = sanitize_text_field( $_POST['coupon_code'] );
	
	if ( empty( $coupon_code ) ) {
		wp_send_json_error( __( 'Please enter a coupon code', 'primefit' ) );
	}
	
	// Apply the coupon
	$result = WC()->cart->apply_coupon( $coupon_code );
	
	if ( $result ) {
		WC()->cart->calculate_totals();
		wp_send_json_success( __( 'Coupon applied successfully!', 'primefit' ) );
	} else {
		$error_messages = wc_get_notices( 'error' );
		$error_message = ! empty( $error_messages ) ? $error_messages[0]['notice'] : __( 'Invalid coupon code', 'primefit' );
		wc_clear_notices(); // Clear notices to prevent showing them elsewhere
		wp_send_json_error( $error_message );
	}
}

/**
 * Handle coupon removal via AJAX
 */
add_action( 'wp_ajax_remove_coupon', 'primefit_handle_remove_coupon' );
add_action( 'wp_ajax_nopriv_remove_coupon', 'primefit_handle_remove_coupon' );

function primefit_handle_remove_coupon() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['security'], 'remove_coupon' ) ) {
		wp_send_json_error( __( 'Security check failed', 'primefit' ) );
	}
	
	$coupon_code = sanitize_text_field( $_POST['coupon'] );
	
	if ( empty( $coupon_code ) ) {
		wp_send_json_error( __( 'Invalid coupon code', 'primefit' ) );
	}
	
	// Remove the coupon
	$result = WC()->cart->remove_coupon( $coupon_code );
	
	if ( $result ) {
		WC()->cart->calculate_totals();
		wp_send_json_success( __( 'Coupon removed successfully!', 'primefit' ) );
	} else {
		wp_send_json_error( __( 'Failed to remove coupon', 'primefit' ) );
	}
}