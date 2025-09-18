/**
 * PrimeFit Theme - Checkout JavaScript
 * Enhanced checkout functionality with Shopify-style interactions
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Checkout Manager
     */
    const CheckoutManager = {
        init: function() {
            this.bindEvents();
            this.initializeForm();
            this.setupValidation();
            this.setupOrderSummary();
            this.setupExpressCheckout();
            this.setupDiscountCode();
            this.setupProgressIndicator();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form field changes
            $(document).on('change', '.checkout-form-input, .checkout-form-select', this.handleFieldChange.bind(this));
            $(document).on('blur', '.checkout-form-input, .checkout-form-select', this.validateField.bind(this));
            
            // Checkbox changes
            $(document).on('change', '.checkout-form-checkbox input', this.handleCheckboxChange.bind(this));
            
            // Continue button
            $(document).on('click', '.checkout-continue-button', this.handleContinueClick.bind(this));
            
            // Express checkout
            $(document).on('click', '.express-checkout-button', this.handleExpressCheckout.bind(this));
            
            // Discount code
            $(document).on('click', '.discount-apply-btn', this.handleDiscountApply.bind(this));
            $(document).on('keypress', '.discount-input', this.handleDiscountKeypress.bind(this));
            
            // Order summary toggle
            $(document).on('click', '.order-summary-toggle', this.toggleOrderSummary.bind(this));
            
            // WooCommerce events
            $(document.body).on('updated_checkout', this.handleCheckoutUpdate.bind(this));
            $(document.body).on('checkout_error', this.handleCheckoutError.bind(this));
        },

        /**
         * Initialize form with default values and styling
         */
        initializeForm: function() {
            // Add custom classes to WooCommerce form elements
            this.enhanceWooCommerceForms();
            
            // Set up field placeholders
            this.setupFieldPlaceholders();
            
            // Initialize country/region dropdown
            this.initializeCountryDropdown();
            
            // Set up phone number formatting
            this.setupPhoneFormatting();
        },

        /**
         * Enhance WooCommerce form elements with custom styling
         */
        enhanceWooCommerceForms: function() {
            // Wrap form fields in custom containers
            $('.woocommerce-checkout .form-row').each(function() {
                const $row = $(this);
                const $input = $row.find('input, select, textarea');
                
                if ($input.length) {
                    $input.addClass('checkout-form-input');
                    
                    // Add custom label if WooCommerce label exists
                    const $label = $row.find('label');
                    if ($label.length && $label.text().trim()) {
                        $input.attr('placeholder', $label.text().trim());
                    }
                }
            });

            // Style checkboxes
            $('.woocommerce-checkout .form-row input[type="checkbox"]').each(function() {
                const $checkbox = $(this);
                const $label = $checkbox.closest('label');
                
                if ($label.length) {
                    $label.addClass('checkout-form-checkbox-label');
                    $checkbox.wrap('<div class="checkout-form-checkbox"></div>');
                    $checkbox.parent().append($label);
                }
            });

            // Style radio buttons
            $('.woocommerce-checkout .form-row input[type="radio"]').each(function() {
                $(this).addClass('checkout-form-radio');
            });
        },

        /**
         * Set up field placeholders
         */
        setupFieldPlaceholders: function() {
            const placeholders = {
                'billing_first_name': 'First name',
                'billing_last_name': 'Last name',
                'billing_email': 'Email',
                'billing_phone': 'Phone',
                'billing_address_1': 'Address',
                'billing_address_2': 'Apartment, suite, etc. (optional)',
                'billing_city': 'City',
                'billing_postcode': 'Postal code (optional)',
                'shipping_first_name': 'First name',
                'shipping_last_name': 'Last name',
                'shipping_address_1': 'Address',
                'shipping_address_2': 'Apartment, suite, etc. (optional)',
                'shipping_city': 'City',
                'shipping_postcode': 'Postal code (optional)'
            };

            Object.keys(placeholders).forEach(function(fieldName) {
                const $field = $('[name="' + fieldName + '"]');
                if ($field.length && !$field.attr('placeholder')) {
                    $field.attr('placeholder', placeholders[fieldName]);
                }
            });
        },

        /**
         * Initialize country/region dropdown
         */
        initializeCountryDropdown: function() {
            const $countrySelect = $('#billing_country, #shipping_country');
            
            if ($countrySelect.length) {
                $countrySelect.addClass('checkout-form-select');
                
                // Add custom styling for select dropdown
                $countrySelect.wrap('<div class="checkout-form-field"></div>');
            }
        },

        /**
         * Set up phone number formatting
         */
        setupPhoneFormatting: function() {
            const $phoneInput = $('#billing_phone');
            
            if ($phoneInput.length) {
                // Add help icon
                $phoneInput.after('<span class="checkout-form-help" title="We\'ll use this to contact you about your order">?</span>');
                
                // Basic phone formatting
                $phoneInput.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 0) {
                        if (value.length <= 3) {
                            value = value;
                        } else if (value.length <= 6) {
                            value = value.slice(0, 3) + '-' + value.slice(3);
                        } else {
                            value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
                        }
                    }
                    $(this).val(value);
                });
            }
        },

        /**
         * Set up form validation
         */
        setupValidation: function() {
            // Real-time validation
            this.validationRules = {
                email: {
                    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                    message: 'Please enter a valid email address'
                },
                phone: {
                    pattern: /^[\d\-\s\(\)]+$/,
                    message: 'Please enter a valid phone number'
                },
                required: {
                    pattern: /.+/,
                    message: 'This field is required'
                }
            };
        },

        /**
         * Handle field changes
         */
        handleFieldChange: function(e) {
            const $field = $(e.target);
            const fieldName = $field.attr('name');
            
            // Update order summary if billing fields change
            if (fieldName && fieldName.startsWith('billing_')) {
                this.updateOrderSummary();
            }
            
            // Handle country change
            if (fieldName === 'billing_country' || fieldName === 'shipping_country') {
                this.handleCountryChange($field);
            }
        },

        /**
         * Handle country change
         */
        handleCountryChange: function($countryField) {
            const countryCode = $countryField.val();
            
            // Update state/province field
            const $stateField = $countryField.attr('name').replace('country', 'state');
            const $stateSelect = $('[name="' + $stateField + '"]');
            
            if ($stateSelect.length) {
                // Trigger WooCommerce state update
                $stateSelect.trigger('change');
            }
        },

        /**
         * Validate individual field
         */
        validateField: function(e) {
            const $field = $(e.target);
            const fieldName = $field.attr('name');
            const fieldValue = $field.val().trim();
            
            // Remove existing error state
            $field.removeClass('error');
            $field.siblings('.error-message').remove();
            
            // Skip validation for optional fields
            if ($field.attr('placeholder') && $field.attr('placeholder').includes('optional')) {
                return;
            }
            
            // Validate required fields
            if (!fieldValue && this.isRequiredField(fieldName)) {
                this.showFieldError($field, 'This field is required');
                return;
            }
            
            // Validate email
            if (fieldName === 'billing_email' && fieldValue) {
                if (!this.validationRules.email.pattern.test(fieldValue)) {
                    this.showFieldError($field, this.validationRules.email.message);
                    return;
                }
            }
            
            // Validate phone
            if (fieldName === 'billing_phone' && fieldValue) {
                if (!this.validationRules.phone.pattern.test(fieldValue)) {
                    this.showFieldError($field, this.validationRules.phone.message);
                    return;
                }
            }
        },

        /**
         * Check if field is required
         */
        isRequiredField: function(fieldName) {
            const requiredFields = [
                'billing_first_name',
                'billing_last_name',
                'billing_email',
                'billing_address_1',
                'billing_city',
                'billing_country'
            ];
            
            return requiredFields.includes(fieldName);
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            $field.addClass('error');
            $field.after('<div class="error-message">' + message + '</div>');
        },

        /**
         * Handle checkbox changes
         */
        handleCheckboxChange: function(e) {
            const $checkbox = $(e.target);
            const isChecked = $checkbox.is(':checked');
            
            // Handle newsletter signup
            if ($checkbox.attr('name') === 'newsletter_signup') {
                // Store preference for later use
                localStorage.setItem('newsletter_signup', isChecked);
            }
        },

        /**
         * Set up order summary
         */
        setupOrderSummary: function() {
            // Make order summary sticky
            this.makeOrderSummarySticky();
            
            // Update order summary on page load
            this.updateOrderSummary();
        },

        /**
         * Make order summary sticky
         */
        makeOrderSummarySticky: function() {
            const $summary = $('.checkout-summary-column');
            
            if ($summary.length) {
                $(window).on('scroll', function() {
                    const scrollTop = $(window).scrollTop();
                    const summaryTop = $summary.offset().top;
                    const summaryHeight = $summary.outerHeight();
                    const windowHeight = $(window).height();
                    
                    if (scrollTop > summaryTop && summaryHeight < windowHeight) {
                        $summary.addClass('sticky');
                    } else {
                        $summary.removeClass('sticky');
                    }
                });
            }
        },

        /**
         * Update order summary
         */
        updateOrderSummary: function() {
            // This will be handled by WooCommerce's built-in update mechanisms
            // We just need to ensure our custom styling is applied
            this.styleOrderSummary();
        },

        /**
         * Style order summary
         */
        styleOrderSummary: function() {
            // Style order review table
            $('.woocommerce-checkout-review-order-table').addClass('order-summary-table');
            
            // Style order totals
            $('.woocommerce-checkout-review-order-table .order-total').addClass('order-total-line total');
        },

        /**
         * Set up express checkout
         */
        setupExpressCheckout: function() {
            // Add express checkout button if not present
            if (!$('.express-checkout-section').length) {
                this.addExpressCheckoutSection();
            }
        },

        /**
         * Add express checkout section
         */
        addExpressCheckoutSection: function() {
            const expressCheckoutHTML = `
                <div class="express-checkout-section">
                    <h3 class="express-checkout-title">Express checkout</h3>
                    <button type="button" class="express-checkout-button">
                        Shop Pay
                    </button>
                    <div class="express-checkout-divider">
                        <span>OR</span>
                    </div>
                </div>
            `;
            
            $('.woocommerce-checkout .woocommerce-billing-fields').before(expressCheckoutHTML);
        },

        /**
         * Handle express checkout
         */
        handleExpressCheckout: function(e) {
            e.preventDefault();
            
            // Show loading state
            const $button = $(e.target);
            $button.addClass('loading').prop('disabled', true);
            
            // Simulate express checkout process
            setTimeout(() => {
                $button.removeClass('loading').prop('disabled', false);
                // Here you would integrate with your express checkout provider
                console.log('Express checkout initiated');
            }, 2000);
        },

        /**
         * Set up discount code functionality
         */
        setupDiscountCode: function() {
            // Add discount code section if not present
            if (!$('.discount-section').length) {
                this.addDiscountSection();
            }
        },

        /**
         * Add discount section
         */
        addDiscountSection: function() {
            const discountHTML = `
                <div class="discount-section">
                    <div class="discount-input-group">
                        <input type="text" class="discount-input" placeholder="Discount code or gift card">
                        <button type="button" class="discount-apply-btn">Apply</button>
                    </div>
                </div>
            `;
            
            $('.order-summary-items').after(discountHTML);
        },

        /**
         * Handle discount code application
         */
        handleDiscountApply: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const $input = $button.siblings('.discount-input');
            const couponCode = $input.val().trim();
            
            if (!couponCode) {
                this.showDiscountError('Please enter a discount code');
                return;
            }
            
            // Show loading state
            $button.addClass('loading').prop('disabled', true);
            
            // Apply coupon via WooCommerce AJAX
            $.ajax({
                url: wc_checkout_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'woocommerce_apply_coupon',
                    security: wc_checkout_params.apply_coupon_nonce,
                    coupon_code: couponCode
                },
                success: function(response) {
                    if (response.success) {
                        $input.val('');
                        CheckoutManager.showDiscountSuccess('Coupon applied successfully!');
                        // Trigger checkout update
                        $('body').trigger('update_checkout');
                    } else {
                        CheckoutManager.showDiscountError(response.data || 'Invalid coupon code');
                    }
                },
                error: function() {
                    CheckoutManager.showDiscountError('Failed to apply coupon. Please try again.');
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Handle discount code keypress
         */
        handleDiscountKeypress: function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $(e.target).siblings('.discount-apply-btn').click();
            }
        },

        /**
         * Show discount success message
         */
        showDiscountSuccess: function(message) {
            this.showDiscountMessage(message, 'success');
        },

        /**
         * Show discount error message
         */
        showDiscountError: function(message) {
            this.showDiscountMessage(message, 'error');
        },

        /**
         * Show discount message
         */
        showDiscountMessage: function(message, type) {
            const $section = $('.discount-section');
            $section.find('.discount-message').remove();
            
            const messageHTML = `<div class="discount-message ${type}">${message}</div>`;
            $section.append(messageHTML);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $section.find('.discount-message').fadeOut();
            }, 5000);
        },

        /**
         * Set up progress indicator
         */
        setupProgressIndicator: function() {
            // Add progress indicator if not present
            if (!$('.checkout-progress').length) {
                this.addProgressIndicator();
            }
        },

        /**
         * Add progress indicator
         */
        addProgressIndicator: function() {
            const progressHTML = `
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
            `;
            
            $('.checkout-header').after(progressHTML);
        },

        /**
         * Toggle order summary
         */
        toggleOrderSummary: function(e) {
            e.preventDefault();
            
            const $summary = $('.checkout-summary-column');
            const $toggle = $(e.target);
            
            $summary.toggleClass('collapsed');
            
            if ($summary.hasClass('collapsed')) {
                $toggle.text('Show order summary');
            } else {
                $toggle.text('Hide order summary');
            }
        },

        /**
         * Handle continue button click
         */
        handleContinueClick: function(e) {
            e.preventDefault();
            
            // Validate form
            if (!this.validateForm()) {
                return;
            }
            
            // Show loading state
            const $button = $(e.target);
            $button.addClass('loading').prop('disabled', true);
            
            // Submit form
            setTimeout(() => {
                $('form.checkout').submit();
            }, 500);
        },

        /**
         * Validate entire form
         */
        validateForm: function() {
            let isValid = true;
            
            // Validate all required fields
            $('.checkout-form-input, .checkout-form-select').each((index, field) => {
                const $field = $(field);
                const fieldName = $field.attr('name');
                
                if (this.isRequiredField(fieldName)) {
                    const fieldValue = $field.val().trim();
                    
                    if (!fieldValue) {
                        this.showFieldError($field, 'This field is required');
                        isValid = false;
                    }
                }
            });
            
            return isValid;
        },

        /**
         * Handle checkout update
         */
        handleCheckoutUpdate: function() {
            // Re-style elements after WooCommerce update
            this.enhanceWooCommerceForms();
            this.styleOrderSummary();
            this.updateOrderSummaryDisplay();
        },

        /**
         * Update order summary display
         */
        updateOrderSummaryDisplay: function() {
            // Update order total in header
            const $orderTotal = $('.order-summary-total');
            const $wcTotal = $('.woocommerce-checkout-review-order-table .order-total .amount');
            
            if ($wcTotal.length && $orderTotal.length) {
                $orderTotal.text($wcTotal.text());
            }
            
            // Update individual line items
            $('.order-summary-items').empty();
            
            // Rebuild order summary items from WooCommerce data
            $('.woocommerce-checkout-review-order-table tbody tr').each(function() {
                const $row = $(this);
                const $name = $row.find('.product-name');
                const $total = $row.find('.product-total');
                
                if ($name.length && $total.length && !$row.hasClass('order-total')) {
                    const productName = $name.text().trim();
                    const productTotal = $total.text().trim();
                    
                    const itemHTML = `
                        <div class="order-summary-item">
                            <div class="order-summary-item-details">
                                <h4 class="order-summary-item-name">${productName}</h4>
                                <p class="order-summary-item-price">${productTotal}</p>
                            </div>
                        </div>
                    `;
                    
                    $('.order-summary-items').append(itemHTML);
                }
            });
        },

        /**
         * Handle checkout error
         */
        handleCheckoutError: function() {
            // Remove loading states
            $('.checkout-continue-button, .express-checkout-button').removeClass('loading').prop('disabled', false);
            
            // Scroll to first error
            const $firstError = $('.woocommerce-error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Only initialize on checkout page
        if ($('body').hasClass('woocommerce-checkout')) {
            CheckoutManager.init();
        }
    });

    /**
     * Expose CheckoutManager globally for debugging
     */
    window.CheckoutManager = CheckoutManager;

})(jQuery);
