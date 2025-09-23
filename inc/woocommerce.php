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
	// WooCommerce not available yet
	return;
}

// WooCommerce is available - setting up cart functions

/**
 * Add Kosovo to WooCommerce countries list
 */
add_filter( 'woocommerce_countries', 'primefit_add_kosovo' );
function primefit_add_kosovo( $countries ) {
    $new_countries = array(
        'XK'  => __( 'Kosovo', 'woocommerce' ),
    );
    return array_merge( $countries, $new_countries );
}

/**
 * Add Kosovo to European continent
 */
add_filter( 'woocommerce_continents', 'primefit_add_kosovo_to_continents' );
function primefit_add_kosovo_to_continents( $continents ) {
    $continents['EU']['countries'][] = 'XK';
    return $continents;
}

/**
 * Disable default WooCommerce stylesheets
 * We use our own custom styles instead
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Add custom stock validation for add to cart
 * This ensures stock limits are enforced on the server side
 * Only run for direct add-to-cart attempts on product pages
 */
// Temporarily disabled to debug AJAX add to cart issues
// add_filter( 'woocommerce_add_to_cart_validation', 'primefit_validate_stock_on_add_to_cart', 10, 5 );
function primefit_validate_stock_on_add_to_cart( $valid, $product_id, $quantity, $variation_id = 0, $variation_data = array() ) {
	// Get the product
	$product = wc_get_product( $product_id );
	
	if ( ! $product ) {
		wc_add_notice( __( 'Product not found.', 'primefit' ), 'error' );
		return false;
	}
	
	// For variable products, ensure variation is selected and valid
	if ( $product->is_type( 'variable' ) ) {
		// Only validate on single product pages during direct add-to-cart attempts
		$is_product_page = is_product() && ! wp_doing_ajax() && ! is_admin() && ! (defined('REST_REQUEST') && REST_REQUEST);
		
		// Skip all validation except for direct product page interactions
		if ( ! $is_product_page ) {
			return $valid; // Let WooCommerce handle validation in other contexts
		}
		
		if ( ! $variation_id || $variation_id <= 0 ) {
			wc_add_notice( __( 'Please choose product options by visiting the product page.', 'primefit' ), 'error' );
			return false;
		}
		
		$variation = wc_get_product( $variation_id );
		
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			wc_add_notice( __( 'Selected variation is not valid.', 'primefit' ), 'error' );
			return false;
		}
		
		// Check if variation is in stock
		if ( ! $variation->is_in_stock() ) {
			wc_add_notice( __( 'This variation is currently out of stock.', 'primefit' ), 'error' );
			return false;
		}
		
		// Validate variation attributes
		if ( ! empty( $variation_data ) ) {
			$variation_attributes = $variation->get_variation_attributes();
			
			foreach ( $variation_data as $attribute_name => $attribute_value ) {
				// Skip empty values
				if ( empty( $attribute_value ) ) {
					continue;
				}
				
				// Check if this attribute is required for this variation
				if ( isset( $variation_attributes[ $attribute_name ] ) ) {
					$expected_value = $variation_attributes[ $attribute_name ];
					
					// If the variation has a specific value for this attribute, it must match
					if ( ! empty( $expected_value ) && $expected_value !== $attribute_value ) {
						wc_add_notice( 
							sprintf( 
								__( 'Selected %s is not available for this variation.', 'primefit' ), 
								wc_attribute_label( $attribute_name )
							), 
							'error' 
						);
						return false;
					}
				}
			}
		}
		
		// Check stock quantity limits
		if ( $variation->managing_stock() ) {
			$stock_quantity = $variation->get_stock_quantity();
			$max_purchase_quantity = $variation->get_max_purchase_quantity();
			
			// Use the more restrictive limit
			$max_allowed = $max_purchase_quantity > 0 ? min( $stock_quantity, $max_purchase_quantity ) : $stock_quantity;
			
			if ( $quantity > $max_allowed ) {
				wc_add_notice( 
					sprintf( 
						__( 'Only %d items available in stock. Please reduce your quantity.', 'primefit' ), 
						$max_allowed 
					), 
					'error' 
				);
				return false;
			}
		}
	} else {
		// For simple products, check stock
		if ( ! $product->is_in_stock() ) {
			wc_add_notice( __( 'This product is currently out of stock.', 'primefit' ), 'error' );
			return false;
		}
		
		// Check stock quantity limits for simple products
		if ( $product->managing_stock() ) {
			$stock_quantity = $product->get_stock_quantity();
			$max_purchase_quantity = $product->get_max_purchase_quantity();
			
			$max_allowed = $max_purchase_quantity > 0 ? min( $stock_quantity, $max_purchase_quantity ) : $stock_quantity;
			
			if ( $quantity > $max_allowed ) {
				wc_add_notice( 
					sprintf( 
						__( 'Only %d items available in stock. Please reduce your quantity.', 'primefit' ), 
						$max_allowed 
					), 
					'error' 
				);
				return false;
			}
		}
	}
	
	return $valid;
}

/**
 * Optimize database queries for better performance
 * Add early in the loading process to affect all queries
 */
add_action( 'init', 'primefit_optimize_database_queries', 1 );
function primefit_optimize_database_queries() {
	// Optimize term queries by adding cache
	if ( ! is_admin() && ! wp_doing_ajax() ) {
		// Cache term queries for 1 hour
		add_filter( 'get_terms_args', 'primefit_add_terms_cache', 10, 2 );
	}
}

/**
 * Add caching to term queries for better performance
 */
function primefit_add_terms_cache( $args, $taxonomies ) {
	if ( ! is_admin() && ! wp_doing_ajax() && isset( $args['taxonomy'] ) ) {
		// Add cache to product category queries
		if ( $args['taxonomy'] === 'product_cat' || $args['taxonomy'] === 'product_tag' ) {
			$args['cache_results'] = true;
			$args['update_term_meta_cache'] = true;
		}
	}
	return $args;
}

/**
 * Ensure WooCommerce AJAX add to cart handler is properly registered
 * WooCommerce should handle this automatically, but we'll ensure it's available
 */
// Re-enable custom AJAX handler to ensure proper processing
add_action( 'wp_ajax_wc_ajax_add_to_cart', 'primefit_ensure_wc_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_wc_ajax_add_to_cart', 'primefit_ensure_wc_ajax_add_to_cart' );

// Removed add to cart validation debugging
function primefit_ensure_wc_ajax_add_to_cart() {
	// Ensure variation_id is properly set
	if ( isset( $_POST['variation_id'] ) && ! empty( $_POST['variation_id'] ) ) {
		$_POST['variation_id'] = intval( $_POST['variation_id'] );
	}
	
	// Let WooCommerce handle the AJAX add to cart
	if ( class_exists( 'WC_AJAX' ) && method_exists( 'WC_AJAX', 'add_to_cart' ) ) {
		WC_AJAX::add_to_cart();
	} else {
		// Fallback: handle manually
		wp_send_json_error( __( 'WooCommerce AJAX handler not available', 'primefit' ) );
	}
}

/**
 * Ensure variation attributes are properly processed during add to cart
 */
add_action( 'woocommerce_add_to_cart', 'primefit_ensure_variation_attributes', 10, 6 );
function primefit_ensure_variation_attributes( $cart_item_key, $product_id, $quantity, $variation_id, $variation_data, $cart_item_data ) {
	// Only process for variable products
	if ( $variation_id && $variation_id > 0 ) {
		$variation = wc_get_product( $variation_id );
		
		if ( $variation && $variation->is_type( 'variation' ) ) {
			// Get the variation attributes
			$variation_attributes = $variation->get_variation_attributes();
			
			// Ensure all required attributes are set
			foreach ( $variation_attributes as $attribute_name => $attribute_value ) {
				if ( ! empty( $attribute_value ) && ! isset( $variation_data[ $attribute_name ] ) ) {
					// Set the attribute value from the variation
					$variation_data[ $attribute_name ] = $attribute_value;
				}
			}
			
			// Update the cart item data
			if ( ! empty( $variation_data ) ) {
				WC()->cart->cart_contents[ $cart_item_key ]['variation'] = $variation_data;
			}
		}
	}
}

/**
 * Enable AJAX add to cart functionality
 */
add_action( 'init', 'primefit_enable_ajax_add_to_cart' );
function primefit_enable_ajax_add_to_cart() {
	// Ensure AJAX add to cart is enabled
	if ( class_exists( 'WooCommerce' ) ) {
		// Enable AJAX add to cart for single products
		add_filter( 'woocommerce_product_single_add_to_cart_text', 'primefit_ajax_add_to_cart_text', 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_link', 'primefit_ajax_add_to_cart_link', 10, 2 );
	}
}

/**
 * Ensure add to cart buttons have proper AJAX classes
 */
function primefit_ajax_add_to_cart_text( $text, $product ) {
	return $text;
}

function primefit_ajax_add_to_cart_link( $link, $product ) {
	// Add AJAX classes to add to cart links
	if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
		$link = str_replace( 'add_to_cart_button', 'add_to_cart_button ajax_add_to_cart', $link );
		$link = str_replace( '<a ', '<a data-product_id="' . $product->get_id() . '" ', $link );
	}
	return $link;
}

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
 * Replace default WooCommerce quantity input with custom one
 * Optimized for better performance
 */
add_filter( 'woocommerce_quantity_input_args', 'primefit_override_quantity_input', 10, 2 );
function primefit_override_quantity_input( $args, $product ) {
	// Add performance optimizations to quantity input
	$args['inputmode'] = 'numeric'; // Mobile optimization
	$args['pattern'] = '[0-9]*'; // Prevent non-numeric input

	// Set reasonable defaults to prevent excessive queries
	if (!isset($args['min_value'])) {
		$args['min_value'] = 1;
	}
	if (!isset($args['max_value'])) {
		$args['max_value'] = 99; // Reasonable upper limit
	}

	return $args;
}

/**
 * Header cart fragments (update cart count and mini cart content)
 * Optimized for better performance
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'primefit_header_cart_fragment' );
function primefit_header_cart_fragment( $fragments ) {
	// Only update fragments if cart exists
	if ( ! WC()->cart ) {
		return $fragments;
	}

	// Add cart count fragment for header - use output buffering for cleaner code
	ob_start();
	?>
	<span class="cart-count" data-cart-count>
		<?php echo intval( WC()->cart->get_cart_contents_count() ); ?>
	</span>
	<?php
	$fragments['span[data-cart-count]'] = ob_get_clean();

	// Add mini cart content fragment - only if cart has items
	if ( ! WC()->cart->is_empty() ) {
		ob_start();
		?>
		<div class="widget_shopping_cart_content">
			<?php if ( function_exists( 'woocommerce_mini_cart' ) ) {
				woocommerce_mini_cart();
			} ?>
		</div>
		<?php
		$fragments['div.widget_shopping_cart_content'] = ob_get_clean();
	}

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
    // Ensure clean output buffer for JSON response
    if ( ob_get_level() ) {
        ob_clean();
    }
    
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

    // Update cart item quantity

    // Update quantity; set_quantity returns WC_Cart_Item or false
    $updated = WC()->cart->set_quantity( $cart_item_key, $quantity, true );

    if ( false === $updated ) {
        wp_send_json_error( __( 'Failed to update quantity', 'primefit' ), 400 );
    }

    // Recalculate totals and refresh fragments
    WC()->cart->calculate_totals();

    // Cart quantity updated successfully

    $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );

    wp_send_json_success( array(
        'fragments' => $fragments,
        'cart_hash' => WC()->cart->get_cart_hash(),
        'updated_quantity' => $quantity,
        'cart_item_key' => $cart_item_key,
    ) );
}

/**
 * AJAX: Remove cart item (used by mini-cart remove button fallback)
 * Frontend action: wc_ajax_remove_cart_item
 */
add_action( 'wp_ajax_wc_ajax_remove_cart_item', 'primefit_wc_remove_cart_item' );
add_action( 'wp_ajax_nopriv_wc_ajax_remove_cart_item', 'primefit_wc_remove_cart_item' );
function primefit_wc_remove_cart_item() {
    // Ensure clean output buffer for JSON response
    if ( ob_get_level() ) {
        ob_clean();
    }
    
    // Remove cart item AJAX handler called
    
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

    // Check if we're on checkout page and cart is now empty
    $is_checkout_page = is_checkout();
    $cart_is_empty = WC()->cart->is_empty();
    
    // Sending success response
    wp_send_json_success( array(
        'fragments' => $fragments,
        'cart_hash' => WC()->cart->get_cart_hash(),
        'cart_contents_count' => WC()->cart->get_cart_contents_count(),
        'cart_is_empty' => $cart_is_empty,
        'is_checkout_page' => $is_checkout_page,
        'redirect_to_shop' => $is_checkout_page && $cart_is_empty,
        'shop_url' => $is_checkout_page && $cart_is_empty ? wc_get_page_permalink( 'shop' ) : null,
    ) );
}

/**
 * Redirect cart page to front page and open mini cart
 */
add_action( 'template_redirect', 'primefit_redirect_cart_page' );
function primefit_redirect_cart_page() {
	// Check if we're on the cart page
	if ( is_cart() ) {
		// Get the front page URL
		$front_page_url = home_url( '/' );
		
		// Add a parameter to indicate we should open the mini cart
		$redirect_url = add_query_arg( 'open_cart', '1', $front_page_url );
		
		// Redirect to front page
		wp_redirect( $redirect_url );
		exit;
	}
}

/**
 * Redirect checkout page to shop if cart is empty
 * BUT NOT during order processing or on order-received page
 */
add_action( 'template_redirect', 'primefit_redirect_empty_checkout' );
function primefit_redirect_empty_checkout() {
	// Don't redirect if we're processing an order or on order-received page
	if ( is_wc_endpoint_url( 'order-received' ) || 
		 is_wc_endpoint_url( 'order-pay' ) || 
		 is_wc_endpoint_url( 'order-received' ) ||
		 isset( $_POST['woocommerce_checkout_place_order'] ) ||
		 wp_doing_ajax() ) {
		return;
	}
	
	// Check if we're on the checkout page and cart is empty
	if ( is_checkout() && WC()->cart && WC()->cart->is_empty() ) {
		// Get the shop page URL
		$shop_url = wc_get_page_permalink( 'shop' );
		
		// Redirect to shop page
		wp_redirect( $shop_url );
		exit;
	}
}

/**
 * Hook into WooCommerce cart item removal to handle checkout redirect
 */
add_action( 'woocommerce_cart_item_removed', 'primefit_handle_cart_item_removed', 10, 2 );
function primefit_handle_cart_item_removed( $cart_item_key, $cart ) {
	// Check if we're on checkout page and cart is now empty
	if ( is_checkout() && $cart->is_empty() ) {
		// Set a transient to indicate we should redirect
		set_transient( 'primefit_checkout_redirect_to_shop', true, 30 );
	}
}

/**
 * Auto-open mini cart on front page if redirected from cart
 */
add_action( 'wp_footer', 'primefit_auto_open_mini_cart' );
function primefit_auto_open_mini_cart() {
	// Only on front page and if open_cart parameter is present
	if ( is_front_page() && isset( $_GET['open_cart'] ) && $_GET['open_cart'] === '1' ) {
		?>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Small delay to ensure everything is loaded
			setTimeout(function() {
				// Find the cart toggle and trigger click
				const cartToggle = document.querySelector('.cart-toggle');
				if (cartToggle) {
					cartToggle.click();
				}
			}, 100);
		});
		</script>
		<?php
	}
}

/**
 * Remove custom checkout AJAX handler - let WooCommerce handle it natively
 * The custom handler was causing JSON parsing errors
 */
// Removed custom checkout AJAX handler to fix JavaScript syntax errors

/**
 * Remove custom apply coupon AJAX handler - let WooCommerce handle it natively
 * The custom handler was causing JSON parsing errors
 */
// Removed custom apply coupon AJAX handler to fix JavaScript syntax errors

/**
 * Remove custom get states AJAX handler - let WooCommerce handle it natively
 * The custom handler was causing JSON parsing errors
 */
// Removed custom get states AJAX handler to fix JavaScript syntax errors

/**
 * Removed general AJAX request debugging that was interfering with checkout
 * The excessive logging was causing output buffer issues
 */

/**
 * Register payment summary endpoint for My Account page
 */
add_action( 'init', 'primefit_add_payment_summary_endpoint' );
function primefit_add_payment_summary_endpoint() {
	add_rewrite_endpoint( 'payment-summary', EP_ROOT | EP_PAGES );
}

/**
 * Flush rewrite rules on theme activation to register new endpoints
 */
add_action( 'after_switch_theme', 'primefit_flush_rewrite_rules' );
function primefit_flush_rewrite_rules() {
	flush_rewrite_rules();
}

/**
 * Add payment summary to My Account menu
 */
add_filter( 'woocommerce_account_menu_items', 'primefit_add_payment_summary_menu_item' );
function primefit_add_payment_summary_menu_item( $items ) {
	// Insert payment summary after orders
	$new_items = array();
	foreach ( $items as $key => $item ) {
		$new_items[ $key ] = $item;
		if ( $key === 'orders' ) {
			$new_items['payment-summary'] = __( 'Payment Summary', 'primefit' );
		}
	}
	return $new_items;
}

/**
 * Handle payment summary endpoint content
 */
add_action( 'woocommerce_account_payment-summary_endpoint', 'primefit_payment_summary_endpoint_content' );
function primefit_payment_summary_endpoint_content() {
	// Get order ID from URL parameter
	$order_id = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
	
	if ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order && ( $order->get_user_id() === get_current_user_id() || current_user_can( 'manage_woocommerce' ) ) ) {
			// Load the payment summary template with the specific order
			wc_get_template( 'myaccount/payment-summary.php', array( 'order' => $order ) );
			return;
		}
	}
	
	// If no specific order, show general payment summary or recent orders
	wc_get_template( 'myaccount/payment-summary.php' );
}

// Redirect function removed - now using custom order-received template

/**
 * Removed WooCommerce fragments interceptor that was causing JSON parsing issues
 */

/**
 * Removed AJAX output catching that was interfering with WooCommerce responses
 */

/**
 * Register AJAX handlers at proper time
 */
// Removed custom AJAX handlers in favor of WooCommerce core endpoints
/* add_action( 'init', 'primefit_register_cart_ajax_handlers', 20 );
function primefit_register_cart_ajax_handlers() {
	// Double-check WooCommerce is available
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	
	
	// AJAX handlers for cart updates
	add_action( 'wp_ajax_woocommerce_update_cart_item_quantity', 'primefit_update_cart_item_quantity' );
	add_action( 'wp_ajax_nopriv_woocommerce_update_cart_item_quantity', 'primefit_update_cart_item_quantity' );

	add_action( 'wp_ajax_woocommerce_remove_cart_item', 'primefit_remove_cart_item' );
	add_action( 'wp_ajax_nopriv_woocommerce_remove_cart_item', 'primefit_remove_cart_item' );

	// Debug: Log when actions are registered
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
	
	// Check if WooCommerce is available
	if ( ! class_exists( 'WooCommerce' ) || ! WC() || ! WC()->cart ) {
		wp_send_json_error( 'WooCommerce not available' );
		return;
	}
	
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['security'], 'woocommerce_remove_cart_nonce' ) ) {
		wp_send_json_error( 'Security check failed' );
		return;
	}
	
	$cart_item_key = sanitize_text_field( $_POST['cart_item_key'] );
	
	if ( empty( $cart_item_key ) ) {
		wp_send_json_error( 'Invalid cart item key' );
		return;
	}
	
	// Get current cart contents
	$cart_contents = WC()->cart->get_cart();
	
	// Check if item exists in cart before attempting removal
	if ( ! isset( $cart_contents[ $cart_item_key ] ) ) {
		wp_send_json_error( 'Item not found in cart' );
		return;
	}
	
	
	// Remove the item
	$removed = WC()->cart->remove_cart_item( $cart_item_key );
	
	
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
		
		// 5. Double-check by reloading cart from session
		if ( WC()->session ) {
			$session_cart = WC()->session->get( 'cart', array() );
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
 * Add recommended items section after mini cart items - only from basics/accessories
 */
add_action( 'woocommerce_mini_cart_contents', 'primefit_mini_cart_recommended_items', 25 );
function primefit_mini_cart_recommended_items() {
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	// Get recommended products from basics/accessories categories only
	$recommended_products = primefit_get_mini_cart_recommended_products();
	
	// Only show section if we have products from the target categories
	if ( empty( $recommended_products ) ) {
		return;
	}
	
	?>
	</ul> <!-- Close the mini cart items list -->
	<div class="mini-cart-recommendations">
		<h3 class="recommendations-title"><?php _e( 'ADD A LITTLE EXTRA', 'primefit' ); ?></h3>
		<p class="recommendations-subtitle"><?php _e( 'Complete your look with these essentials', 'primefit' ); ?></p>
		
		<div class="recommendations-carousel">
			<div class="carousel-container">
				<?php foreach ( array_slice( $recommended_products, 0, 5 ) as $product ) : ?>
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="recommendation-item">
						<div class="recommendation-image">
							<?php echo $product->get_image( 'thumbnail' ); ?>
						</div>
						<div class="recommendation-details">
							<h4 class="recommendation-name"><?php echo esc_html( $product->get_name() ); ?></h4>
							<span class="recommendation-price"><?php echo $product->get_price_html(); ?></span>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<ul class="woocommerce-mini-cart hidden-list-start"> <!-- Reopen the list for WooCommerce -->
	<?php
}

/**
 * Note: Mini cart quantity controls are now handled by the custom template override
 * at woocommerce/cart/mini-cart.php instead of using filters
 */

/**
 * Get recommended products for mini cart - only from basics and accessories categories
 */
function primefit_get_mini_cart_recommended_products() {
	// Get products from specific categories: basics and accessories
	$recommended_products = [];
	
	// Get category terms for basics and accessories
	$basics_term = get_term_by( 'slug', 'basics', 'product_cat' );
	$accessories_term = get_term_by( 'slug', 'accessories', 'product_cat' );
	
	// If categories don't exist, try alternative slugs
	if ( ! $basics_term ) {
		$basics_term = get_term_by( 'slug', 'basic', 'product_cat' );
	}
	if ( ! $accessories_term ) {
		$accessories_term = get_term_by( 'slug', 'accessory', 'product_cat' );
	}
	
	// Debug: Log category search results (only for admins)
	if ( current_user_can( 'manage_options' ) && ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
	}
	
	// Collect category IDs
	$category_ids = array();
	if ( $basics_term && ! is_wp_error( $basics_term ) ) {
		$category_ids[] = $basics_term->term_id;
	}
	if ( $accessories_term && ! is_wp_error( $accessories_term ) ) {
		$category_ids[] = $accessories_term->term_id;
	}
	
	// If no categories found, return empty array
	if ( empty( $category_ids ) ) {
		return $recommended_products;
	}
	
	// Query products from these categories
	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => 5,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $category_ids,
				'operator' => 'IN'
			)
		),
		'meta_query'     => array(
			array(
				'key'     => '_stock_status',
				'value'   => 'instock',
				'compare' => '='
			)
		),
		'orderby'        => 'rand', // Random order for variety
		'order'          => 'ASC'
	);
	
	$products = get_posts( $args );

	// Bulk load all product objects to avoid N+1 queries
	$product_ids = wp_list_pluck( $products, 'ID' );
	$product_objects = array();

	if ( ! empty( $product_ids ) ) {
		$product_objects = array_filter( array_map( 'wc_get_product', $product_ids ) );
	}

	foreach ( $products as $product_post ) {
		$product = isset( $product_objects[ $product_post->ID ] ) ? $product_objects[ $product_post->ID ] : null;
		if ( $product && $product->is_purchasable() ) {
			$recommended_products[] = $product;
		}
	}
	
	// Debug: Log results (only for admins)
	if ( current_user_can( 'manage_options' ) && ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
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
	// Ensure clean output buffer for JSON response
	if ( ob_get_level() ) {
		ob_clean();
	}
	
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
	// Ensure clean output buffer for JSON response
	if ( ob_get_level() ) {
		ob_clean();
	}
	
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

/**
 * Customize account menu items - simplify to only show orders, payment summary, account details, and logout
 */
add_filter( 'woocommerce_account_menu_items', 'primefit_customize_account_menu_items' );
function primefit_customize_account_menu_items( $items ) {
	// Remove unwanted menu items
	unset( $items['dashboard'] );
	unset( $items['downloads'] );
	unset( $items['addresses'] );
	unset( $items['payment-methods'] );
	
	// Keep only orders, payment summary, account details, and logout
	$custom_items = array();
	
	// Add orders if it exists
	if ( isset( $items['orders'] ) ) {
		$custom_items['orders'] = $items['orders'];
	}
	
	// Add payment summary
	$custom_items['payment-summary'] = __( 'Payment Summary', 'primefit' );
	
	// Add account details if it exists
	if ( isset( $items['edit-account'] ) ) {
		$custom_items['edit-account'] = $items['edit-account'];
	}
	
	// Add logout if it exists
	if ( isset( $items['customer-logout'] ) ) {
		$custom_items['customer-logout'] = $items['customer-logout'];
	}
	
	return $custom_items;
}

/**
 * Redirect dashboard to orders page since we removed dashboard from menu
 */
add_action( 'template_redirect', 'primefit_redirect_dashboard_to_orders' );
function primefit_redirect_dashboard_to_orders() {
	// Check if we're on the my account dashboard page
	if ( is_account_page() && is_wc_endpoint_url( 'dashboard' ) ) {
		// Redirect to orders page
		$orders_url = wc_get_account_endpoint_url( 'orders' );
		wp_redirect( $orders_url );
		exit;
	}
}

/**
 * Ensure proper order completion and redirect handling
 */
add_action( 'woocommerce_checkout_order_processed', 'primefit_ensure_order_completion', 10, 1 );
function primefit_ensure_order_completion( $order_id ) {
	if ( ! $order_id ) {
		return;
	}
	
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	
	// Log successful order completion for debugging
}

/**
 * Debug checkout redirect issues (only for admins)
 */
add_action( 'template_redirect', 'primefit_debug_checkout_redirect', 1 );
function primefit_debug_checkout_redirect() {
	// Only run for admins and on checkout-related pages
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Debug checkout page access
	if ( is_checkout() ) {
	}
	
	// Debug order received page access
	if ( is_wc_endpoint_url( 'order-received' ) ) {
		$order_id = get_query_var( 'order-received' );
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';
		
		
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
			} else {
			}
		}
	}
}

/**
 * Customize checkout page layout
 */
// Checkout customizations removed - using default WooCommerce checkout

// Redirect function removed - now using custom order-received template

// Fallback redirect function removed - now using custom order-received template

/**
 * Add custom payment summary after order completion
 * Note: This is now handled by the payment summary endpoint page
 */

// Payment summary is now handled directly in thankyou.php template

// Payment summary endpoint already declared above

/**
 * Add JavaScript redirect for order received page (disabled to avoid conflicts)
 * Server-side redirect should handle this properly
 */

/**
 * Force flush rewrite rules if needed
 */
add_action( 'init', 'primefit_force_flush_rewrite_rules', 999 );
function primefit_force_flush_rewrite_rules() {
    // Only flush if we're on the payment summary page and it's not working
    if ( isset( $_GET['flush_rules'] ) && current_user_can( 'manage_options' ) ) {
        flush_rewrite_rules();
        wp_redirect( remove_query_arg( 'flush_rules' ) );
        exit;
    }
}

// Flush rewrite rules function already declared above

/**
 * Debug function to check payment summary status (disabled in production)
 */
// add_action( 'wp_footer', 'primefit_debug_payment_summary' );
function primefit_debug_payment_summary() {
    if ( current_user_can( 'manage_options' ) && isset( $_GET['debug_payment'] ) ) {
        echo '<div style="position: fixed; top: 0; left: 0; background: #000; color: #fff; padding: 10px; z-index: 9999; font-size: 12px;">';
        echo '<strong>Payment Summary Debug:</strong><br>';
        echo 'Is Account Page: ' . ( is_account_page() ? 'Yes' : 'No' ) . '<br>';
        echo 'Is Payment Summary Endpoint: ' . ( is_wc_endpoint_url( 'payment-summary' ) ? 'Yes' : 'No' ) . '<br>';
        echo 'Current User ID: ' . get_current_user_id() . '<br>';
        
        $customer_orders = wc_get_orders( array(
            'customer' => get_current_user_id(),
            'status'   => array( 'completed', 'processing', 'on-hold', 'pending', 'cancelled', 'refunded', 'failed' ),
            'limit'    => 1,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ) );
        
        echo 'Orders Found: ' . count( $customer_orders ) . '<br>';
        if ( ! empty( $customer_orders ) ) {
            $order = $customer_orders[0];
            echo 'Latest Order ID: ' . $order->get_id() . '<br>';
            echo 'Order Status: ' . $order->get_status() . '<br>';
        }
        echo '</div>';
    }
}


/**
 * Alternative approach: Use query var instead of rewrite endpoint
 */
add_action( 'init', 'primefit_add_payment_summary_query_var' );
function primefit_add_payment_summary_query_var() {
    add_rewrite_endpoint( 'payment-summary', EP_ROOT | EP_PAGES );
}

/**
 * Handle payment summary via query var
 */
add_action( 'template_redirect', 'primefit_handle_payment_summary_query' );
function primefit_handle_payment_summary_query() {
    if ( is_account_page() && get_query_var( 'payment-summary' ) ) {
        // Get the most recent order for the current user
        $customer_orders = wc_get_orders( array(
            'customer' => get_current_user_id(),
            'status'   => array( 'completed', 'processing', 'on-hold', 'pending', 'cancelled', 'refunded', 'failed' ),
            'limit'    => 1,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ) );
        
        if ( empty( $customer_orders ) ) {
            echo '<div class="payment-summary-container">';
            echo '<div class="payment-summary-header">';
            echo '<h1 class="payment-summary-title">' . esc_html__( 'No Orders Found', 'primefit' ) . '</h1>';
            echo '<p class="payment-summary-subtitle">' . esc_html__( 'You haven\'t placed any orders yet.', 'primefit' ) . '</p>';
            echo '</div>';
            echo '<div class="payment-summary-actions">';
            echo '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="button button--primary">' . esc_html__( 'Start Shopping', 'primefit' ) . '</a>';
            echo '</div>';
            echo '</div>';
            return;
        }
        
        $order = $customer_orders[0];
        
        // Set global order for template
        global $wp_query;
        $wp_query->query_vars['view-order'] = $order->get_id();
        
        // Load the payment summary template
        get_template_part( 'woocommerce/myaccount/payment-summary' );
        exit;
    }
}

// Payment summary endpoint content function already declared above