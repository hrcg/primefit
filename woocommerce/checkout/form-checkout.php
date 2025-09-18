<?php
/**
 * Checkout Form Template
 * Custom Shopify-style checkout layout
 *
 * @package PrimeFit
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Get cart contents for order summary
$cart_contents = WC()->cart->get_cart();
$cart_total = WC()->cart->get_total();
?>

<div class="checkout-wrapper">
    <!-- Checkout Header -->
    <div class="checkout-header">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
            <?php echo get_bloginfo( 'name' ); ?>
        </a>
    </div>

    <!-- Checkout Progress -->
    <div class="checkout-progress">
        <div class="checkout-progress-steps">
            <div class="checkout-progress-step completed">Cart</div>
            <div class="checkout-progress-separator">></div>
            <div class="checkout-progress-step active">Information</div>
            <div class="checkout-progress-separator">></div>
            <div class="checkout-progress-step">Shipping</div>
            <div class="checkout-progress-separator">></div>
            <div class="checkout-progress-step">Payment</div>
        </div>
    </div>

    <!-- Main Checkout Content -->
    <div class="checkout-content">
        <!-- Left Column - Checkout Form -->
        <div class="checkout-form-column">
            <!-- Express Checkout Section -->
            <div class="express-checkout-section">
                <h3 class="express-checkout-title">Express checkout</h3>
                <button type="button" class="express-checkout-button">
                    Shop Pay
                </button>
                <div class="express-checkout-divider">
                    <span>OR</span>
                </div>
            </div>

            <!-- Checkout Form -->
            <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
                <?php if ( $checkout->get_checkout_fields() ) : ?>
                    <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

                    <!-- Contact Section -->
                    <div class="checkout-form-section">
                        <h3 class="checkout-form-section-title">
                            Contact
                            <a href="#" class="checkout-form-section-link">Sign in</a>
                        </h3>
                        <div class="checkout-form-fields">
                            <div class="checkout-form-field">
                                <input type="email" class="checkout-form-input" name="billing_email" id="billing_email" placeholder="Email" value="<?php echo esc_attr( $checkout->get_value( 'billing_email' ) ); ?>" required />
                            </div>
                            <div class="checkout-form-checkbox">
                                <input type="checkbox" id="newsletter_signup" name="newsletter_signup" value="1" checked />
                                <label for="newsletter_signup" class="checkout-form-checkbox-label">Email me with news and offers</label>
                            </div>
                            <div class="checkout-form-checkbox">
                                <input type="checkbox" id="package_protection" name="package_protection" value="1" />
                                <label for="package_protection" class="checkout-form-checkbox-label">Package Protection for <?php echo get_woocommerce_currency_symbol() . '100'; ?></label>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address Section -->
                    <div class="checkout-form-section">
                        <h3 class="checkout-form-section-title">Shipping address</h3>
                        <div class="checkout-form-fields">
                            <div class="checkout-form-field">
                                <select name="billing_country" id="billing_country" class="checkout-form-select" required>
                                    <option value="">Country/Region</option>
                                    <?php
                                    $countries = WC()->countries->get_countries();
                                    foreach ( $countries as $code => $name ) {
                                        $selected = ( $checkout->get_value( 'billing_country' ) === $code ) ? 'selected' : '';
                                        echo '<option value="' . esc_attr( $code ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="checkout-form-row">
                                <div class="checkout-form-field half-width">
                                    <input type="text" class="checkout-form-input" name="billing_first_name" id="billing_first_name" placeholder="First name" value="<?php echo esc_attr( $checkout->get_value( 'billing_first_name' ) ); ?>" required />
                                </div>
                                <div class="checkout-form-field half-width">
                                    <input type="text" class="checkout-form-input" name="billing_last_name" id="billing_last_name" placeholder="Last name" value="<?php echo esc_attr( $checkout->get_value( 'billing_last_name' ) ); ?>" required />
                                </div>
                            </div>
                            <div class="checkout-form-field">
                                <input type="text" class="checkout-form-input" name="billing_address_1" id="billing_address_1" placeholder="Address" value="<?php echo esc_attr( $checkout->get_value( 'billing_address_1' ) ); ?>" required />
                            </div>
                            <div class="checkout-form-field">
                                <input type="text" class="checkout-form-input" name="billing_address_2" id="billing_address_2" placeholder="Apartment, suite, etc. (optional)" value="<?php echo esc_attr( $checkout->get_value( 'billing_address_2' ) ); ?>" />
                            </div>
                            <div class="checkout-form-row">
                                <div class="checkout-form-field half-width">
                                    <input type="text" class="checkout-form-input" name="billing_city" id="billing_city" placeholder="City" value="<?php echo esc_attr( $checkout->get_value( 'billing_city' ) ); ?>" required />
                                </div>
                                <div class="checkout-form-field half-width">
                                    <input type="text" class="checkout-form-input" name="billing_postcode" id="billing_postcode" placeholder="Postal code (optional)" value="<?php echo esc_attr( $checkout->get_value( 'billing_postcode' ) ); ?>" />
                                </div>
                            </div>
                            <div class="checkout-form-field">
                                <input type="tel" class="checkout-form-input" name="billing_phone" id="billing_phone" placeholder="Phone" value="<?php echo esc_attr( $checkout->get_value( 'billing_phone' ) ); ?>" required />
                                <span class="checkout-form-help" title="We'll use this to contact you about your order">?</span>
                            </div>
                            <div class="checkout-form-checkbox">
                                <input type="checkbox" id="sms_signup" name="sms_signup" value="1" />
                                <label for="sms_signup" class="checkout-form-checkbox-label">Text me with news and offers</label>
                            </div>
                        </div>
                    </div>

                    <!-- Processing Times Section -->
                    <div class="processing-times-section">
                        <h3 class="processing-times-title">Processing Times</h3>
                        <p class="processing-times-text">
                            Please allow 1-2 business days for order processing during normal business hours, regardless of your chosen shipping method. During sales and holidays, processing may take 5-7 business days.
                        </p>
                    </div>

                    <!-- Hidden WooCommerce Fields -->
                    <?php do_action( 'woocommerce_checkout_billing' ); ?>
                    <?php do_action( 'woocommerce_checkout_shipping' ); ?>

                    <!-- Checkout Actions -->
                    <div class="checkout-actions">
                        <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="checkout-back-link">&lt; Return to cart</a>
                        <button type="submit" class="checkout-continue-button">Continue to shipping</button>
                    </div>

                    <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
                <?php endif; ?>

                <!-- Order Review Section (Hidden by default, shown on next step) -->
                <div class="woocommerce-checkout-review-order" style="display: none;">
                    <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
                    <div id="order_review" class="woocommerce-checkout-review-order">
                        <?php do_action( 'woocommerce_checkout_order_review' ); ?>
                    </div>
                    <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
                </div>

                <?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
            </form>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="checkout-summary-column">
            <div class="order-summary-header">
                <h3 class="order-summary-title">Order summary</h3>
                <span class="order-summary-total"><?php echo $cart_total; ?></span>
            </div>

            <!-- Order Items -->
            <div class="order-summary-items">
                <?php foreach ( $cart_contents as $cart_item_key => $cart_item ) :
                    $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                    if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) :
                        $product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                        $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'thumbnail' ), $cart_item, $cart_item_key );
                        $product_price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                        $variation_data = $cart_item['variation'];
                        ?>
                        <div class="order-summary-item">
                            <div class="order-summary-item-image">
                                <?php echo $thumbnail; ?>
                                <div class="order-summary-item-quantity"><?php echo $cart_item['quantity']; ?></div>
                            </div>
                            <div class="order-summary-item-details">
                                <h4 class="order-summary-item-name"><?php echo $product_name; ?></h4>
                                <?php if ( ! empty( $variation_data ) ) : ?>
                                    <p class="order-summary-item-variant">
                                        <?php echo wc_get_formatted_variation( $variation_data, true ); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="order-summary-item-price"><?php echo $product_price; ?></p>
                            </div>
                        </div>
                    <?php endif;
                endforeach; ?>
            </div>

            <!-- Discount Code Section -->
            <div class="discount-section">
                <div class="discount-input-group">
                    <input type="text" class="discount-input" placeholder="Discount code or gift card" />
                    <button type="button" class="discount-apply-btn">Apply</button>
                </div>
            </div>

            <!-- Order Totals -->
            <div class="order-totals">
                <div class="order-total-line">
                    <span class="order-total-label">Subtotal</span>
                    <span class="order-total-value"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
                </div>
                <div class="order-total-line">
                    <span class="order-total-label">Shipping</span>
                    <span class="order-total-value"><?php echo WC()->cart->get_cart_shipping_total(); ?></span>
                </div>
                <div class="order-total-line total">
                    <span class="order-total-label">Total</span>
                    <span class="order-total-value"><?php echo $cart_total; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
