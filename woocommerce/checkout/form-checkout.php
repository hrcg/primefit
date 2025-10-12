<?php
/**
 * Checkout Form Template
 * Modern dark-themed checkout layout
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

?>

<div class="checkout-container">
	<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<div class="checkout-layout">
			<!-- Desktop: Left Column: Contact & Billing Information -->
			<!-- Mobile: Order Summary (order: 1) -->
			<div class="checkout-summary-section">
				<div class="summary-header">
					<h3 class="summary-title">Order summary</h3>
					<div class="summary-total-mobile">
						<?php wc_cart_totals_order_total_html(); ?>
					</div>
					<button type="button" class="summary-toggle mobile-only" aria-label="Toggle order summary">
						<span class="chevron">▼</span>
					</button>
				</div>
				<div class="summary-content">
				
				<div class="order-items">
					<?php
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
						
						if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
							?>
							<div class="order-item">
								<div class="item-image">
									<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ) ); ?>
									<span class="item-quantity"><?php echo esc_html( $cart_item['quantity'] ); ?></span>
								</div>
								<div class="item-details">
									<h4 class="item-name"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?></h4>
									<div class="item-price"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ) ); ?></div>
									<?php
									// Display product attributes
									if ( $_product->is_type( 'variable' ) ) {
										$variation_data = $cart_item['variation'];
										foreach ( $variation_data as $name => $value ) {
											if ( ! empty( $value ) ) {
												$attribute_name = wc_attribute_label( str_replace( 'attribute_', '', $name ) );
												echo '<div class="item-attribute">' . esc_html( $attribute_name ) . ': ' . esc_html( $value ) . '</div>';
											}
										}
									}
									?>
								</div>
								<div class="item-total">
									<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?>
								</div>
							</div>
							<?php
						}
					}
					?>
				</div>

				<!-- Coupon Section -->
				<div class="coupon-section">
					<button type="button" class="coupon-toggle">Add discount code <span class="arrow">▼</span></button>
				</div>

				<!-- Order Totals -->
				<div class="order-totals">
					<div class="total-line">
						<span class="total-label">Subtotal</span>
						<span class="total-value"><?php wc_cart_totals_subtotal_html(); ?></span>
					</div>
					<div class="total-line final-total">
						<span class="total-label">Total</span>
						<span class="total-value"><?php wc_cart_totals_order_total_html(); ?></span>
					</div>
				</div>

				</div> <!-- End summary-content -->
			</div>

			<!-- Desktop: Right Column: Order Summary -->
			<!-- Mobile: Contact Information (order: 2) -->
			<div class="checkout-form-section">
				
				<!-- Contact Information -->
				<div class="form-section">
					<h3 class="section-title">Contact information</h3>
					<p class="section-description">We'll use this email to send you details and updates about your order.</p>
					
					<div class="form-field">
						<?php
						// Check if email should be required based on country
						$selected_country = $checkout->get_value( 'billing_country' );
						$special_countries = array( 'AL', 'XK', 'MK' ); // Albania, Kosovo, North Macedonia
						$email_required = ! in_array( $selected_country, $special_countries );
						$email_placeholder = $email_required ? 'Email address *' : 'Email address';
						?>
						<input type="email" name="billing_email" id="billing_email" placeholder="<?php echo esc_attr( $email_placeholder ); ?>" value="<?php echo esc_attr( $checkout->get_value( 'billing_email' ) ); ?>" <?php echo $email_required ? 'required' : ''; ?> />
					</div>
					
					<?php if ( ! is_user_logged_in() ) : ?>
						<p class="guest-checkout-notice">You are currently checking out as a guest.</p>
					<?php endif; ?>
				</div>

				<!-- Billing Address -->
				<div class="form-section">
					<h3 class="section-title">Billing address</h3>
					<p class="section-description">Enter the billing address that matches your payment method.</p>
					
					<?php if ( $checkout->get_checkout_fields() ) : ?>
						<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
						
						<!-- Custom billing fields with placeholders -->
						<div class="woocommerce-billing-fields">
							<h3>Billing details</h3>
							
							<div class="form-row form-row-first">
								<input type="text" name="billing_first_name" id="billing_first_name" placeholder="First name *" value="<?php echo esc_attr( $checkout->get_value( 'billing_first_name' ) ); ?>" required />
							</div>
							
							<div class="form-row form-row-last">
								<input type="text" name="billing_last_name" id="billing_last_name" placeholder="Last name *" value="<?php echo esc_attr( $checkout->get_value( 'billing_last_name' ) ); ?>" required />
							</div>
							
							<div class="form-row form-row-wide">
								<select name="billing_country" id="billing_country" required>
									<option value="">Country / Region *</option>
									<?php
									$countries = WC()->countries->get_countries();
									$selected_country = $checkout->get_value( 'billing_country' );
									foreach ( $countries as $code => $name ) {
										echo '<option value="' . esc_attr( $code ) . '"' . selected( $selected_country, $code, false ) . '>' . esc_html( $name ) . '</option>';
									}
									?>
								</select>
							</div>
							
							<div class="form-row form-row-wide">
								<input type="text" name="billing_address_1" id="billing_address_1" placeholder="House number and street name *" value="<?php echo esc_attr( $checkout->get_value( 'billing_address_1' ) ); ?>" required />
							</div>
							
							<div class="form-row form-row-wide" id="billing_address_2_field">
								<div class="optional-field-wrapper">
									<input type="text" name="billing_address_2" id="billing_address_2" placeholder="Apartment, suite, unit, etc." value="<?php echo esc_attr( $checkout->get_value( 'billing_address_2' ) ); ?>" />
									<span class="optional-text">(optional)</span>
								</div>
							</div>
							
							<div class="form-row form-row-wide">
								<input type="text" name="billing_city" id="billing_city" placeholder="City *" value="<?php echo esc_attr( $checkout->get_value( 'billing_city' ) ); ?>" required />
							</div>
							
							<div class="form-row form-row-wide">
								<select name="billing_state" id="billing_state" required>
									<option value="">County *</option>
									<?php
									$selected_state = $checkout->get_value( 'billing_state' );
									$selected_country = $checkout->get_value( 'billing_country' );
									
									// If no country is selected, show a message
									if ( ! $selected_country ) {
										echo '<option value="" disabled>Please select a country first</option>';
									} else {
										$states = WC()->countries->get_states( $selected_country );
										if ( $states ) {
											foreach ( $states as $code => $name ) {
												echo '<option value="' . esc_attr( $code ) . '"' . selected( $selected_state, $code, false ) . '>' . esc_html( $name ) . '</option>';
											}
										} else {
											// Country has no predefined states, this will be converted to text input by WooCommerce
											echo '<option value="" disabled>This country has no predefined counties</option>';
										}
									}
									?>
								</select>
							</div>
							
							<div class="form-row form-row-first" id="billing_postcode_field">
								<div class="optional-field-wrapper">
									<input type="text" name="billing_postcode" id="billing_postcode" placeholder="Postal code *" value="<?php echo esc_attr( $checkout->get_value( 'billing_postcode' ) ); ?>" required />
									<span class="optional-text">(required)</span>
								</div>
							</div>
							
							<div class="form-row form-row-last">
								<div class="phone-field-with-help">
									<input type="tel" name="billing_phone" id="billing_phone" placeholder="Phone *" value="<?php echo esc_attr( $checkout->get_value( 'billing_phone' ) ); ?>" pattern="^\+?[0-9\s\-\(\)]+$" title="Please enter a valid phone number (numbers, spaces, hyphens, parentheses, and optional + sign)" required />
									<span class="help-icon-inside" data-tooltip="In case we need to contact you about your order">?</span>
								</div>
							</div>
						</div>
						
						<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
					<?php endif; ?>
				</div>

			</div>

			<!-- Unified Payment Options Section (responsive positioning via CSS) -->
			<div class="payment-options-section">
				<h3 class="section-title">Payment options</h3>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>
				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
			</div>

		</div>

	</form>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>


