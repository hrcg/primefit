/**
 * PrimeFit Theme - Elegant Checkout JavaScript
 * Works harmoniously with WooCommerce's native systems
 * No forced interceptions - pure UI enhancements
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Elegant Checkout Enhancement
   * Enhances UX without interfering with WooCommerce core functionality
   */
  const CheckoutManager = {
    // State management to prevent race conditions
    isInitialized: false,
    isPaymentMethodsEnhanced: false,
    initializationTimeout: null,
    paymentMethodObserver: null,

    init: function () {
      // Prevent multiple initializations
      if (this.isInitialized) {
        return;
      }

      // Use requestAnimationFrame for smoother initialization
      requestAnimationFrame(() => {
        // Check for coupon in URL parameter first
        this.checkUrlCoupon();

        // Initialize UI enhancements only - batch DOM operations
        this.improveFormUsability();
        this.initCouponToggle();
        this.initSummaryToggle();
        this.initHelpTooltips();
        this.initFieldSpecificErrors();
        this.initCountryBasedFieldHiding();

        // Initialize payment methods with proper timing
        this.initPaymentMethodEnhancements();

        // Override WooCommerce's checkout processing
        this.overrideCheckoutProcessing();
        this.overrideBlockUI();

        this.isInitialized = true;
      });
    },

    /**
     * Check for coupon in URL parameter and apply it automatically
     */
    checkUrlCoupon: function () {
      // Get URL parameters
      const urlParams = new URLSearchParams(window.location.search);
      const couponCode = urlParams.get("coupon");

      if (couponCode && couponCode.trim()) {

        // Check if coupon is already applied
        const appliedCoupons = this.getAppliedCoupons();
        if (appliedCoupons.includes(couponCode.toUpperCase())) {
          return;
        }

        // Apply the coupon with a slight delay to ensure DOM is ready
        setTimeout(() => {
          this.applyCouponFromUrl(couponCode.trim());
        }, 500);
      } else {
        // Check for pending coupon from session (base URL case)
        this.checkForPendingCouponFromSession();
      }
    },

    /**
     * Check for pending coupon from session
     * FIXED: Added state management to prevent race conditions
     */
    checkForPendingCouponFromSession: function () {
      // Check for pending coupon data from cart fragments (hidden element)
      const $couponData = jQuery(".primefit-coupon-data");
      if ($couponData.length) {
        const pendingCoupon = $couponData.data("pending-coupon");
        if (pendingCoupon && pendingCoupon.trim()) {

          // Check if coupon is already applied
          const appliedCoupons = this.getAppliedCoupons();
          if (appliedCoupons.includes(pendingCoupon.toUpperCase())) {
            return;
          }

          // CRITICAL: Check if coupon is already being processed to prevent race conditions
          if (window.primefitCouponProcessing && window.primefitCouponProcessing === pendingCoupon.toUpperCase()) {
            return;
          }

          // Mark as processing to prevent race conditions
          window.primefitCouponProcessing = pendingCoupon.toUpperCase();

          // Apply the pending coupon with additional safety check
          setTimeout(() => {
            // Double-check that WooCommerce is loaded before applying
            if (
              typeof wc_add_to_cart_params !== "undefined" ||
              jQuery(".woocommerce-checkout").length
            ) {
              this.applyCouponFromUrl(pendingCoupon.trim());
            } else {
              // Try again after another delay
              setTimeout(() => {
                if (
                  typeof wc_add_to_cart_params !== "undefined" ||
                  jQuery(".woocommerce-checkout").length
                ) {
                  this.applyCouponFromUrl(pendingCoupon.trim());
                } else {
                  // Clear processing flag on failure
                  delete window.primefitCouponProcessing;
                }
              }, 2000);
            }
          }, 1000); // Slightly longer delay for session-based coupons
        }
      }
    },

    /**
     * Get currently applied coupons
     */
    getAppliedCoupons: function () {
      const appliedCoupons = [];

      // Check WooCommerce's applied coupons
      if (
        typeof wc_add_to_cart_params !== "undefined" &&
        wc_add_to_cart_params.applied_coupons
      ) {
        appliedCoupons.push(...wc_add_to_cart_params.applied_coupons);
      }

      // Also check from cart data if available
      if (
        window.wc_cart_fragments_params &&
        window.wc_cart_fragments_params.cart_hash
      ) {
        // Try to get from any visible coupon displays
        $(
          ".applied-coupon .coupon-code, .woocommerce-notices-wrapper .coupon-code"
        ).each(function () {
          const code = $(this).text().trim();
          if (code && !appliedCoupons.includes(code)) {
            appliedCoupons.push(code);
          }
        });
      }

      return appliedCoupons.map((code) => code.toUpperCase());
    },

    /**
     * Apply coupon from URL parameter
     * FIXED: Added proper state management to prevent race conditions
     */
    applyCouponFromUrl: function (couponCode) {

      // Check if coupon is already applied
      const appliedCoupons = this.getAppliedCoupons();
      if (appliedCoupons.includes(couponCode.toUpperCase())) {
        // Clear processing flag since we're done
        delete window.primefitCouponProcessing;
        return;
      }

      // Show loading state
      this.showCouponLoadingState();

      // Apply the coupon using existing method
      this.applyCouponElegantly(couponCode);

      // Clean URL after application attempt
      this.cleanUrlAfterCouponApplication(couponCode);
    },

    /**
     * Show loading state for coupon application
     */
    showCouponLoadingState: function () {
      const $couponToggle = $(".coupon-toggle");
      const $couponSection = $(".coupon-section");

      // If coupon form is visible, show loading
      if ($couponSection.find(".coupon-form").is(":visible")) {
        const $input = $couponSection.find(".coupon-input");
        const $applyBtn = $couponSection.find(".coupon-apply-btn");

        if ($input.length && $applyBtn.length) {
          $input.val("Loading...");
          $applyBtn.text("Applying...").prop("disabled", true);
        }
      } else {
        // Show the coupon form first
        $couponToggle.trigger("click");

        // Wait for form to appear, then show loading state
        setTimeout(() => {
          const $input = $couponSection.find(".coupon-input");
          const $applyBtn = $couponSection.find(".coupon-apply-btn");

          if ($input.length && $applyBtn.length) {
            $input.val("Loading...");
            $applyBtn.text("Applying...").prop("disabled", true);
          }
        }, 350);
      }
    },

    /**
     * Clean URL after coupon application attempt
     */
    cleanUrlAfterCouponApplication: function (couponCode) {
      // Remove coupon parameter from URL after 3 seconds
      setTimeout(() => {
        if (window.history && window.history.replaceState) {
          const url = new URL(window.location);
          url.searchParams.delete("coupon");

          // Only update if there are other parameters or if this is the only parameter
          if (
            url.searchParams.toString() ||
            url.search === "?coupon=" + encodeURIComponent(couponCode)
          ) {
            window.history.replaceState(
              {},
              document.title,
              url.pathname + url.search + url.hash
            );
          }
        }
      }, 3000);
    },

    /**
     * Improve form usability without breaking WooCommerce functionality
     */
    improveFormUsability: function () {
      // Ensure proper input sizing on mobile
      $(
        '.woocommerce-checkout input[type="text"], .woocommerce-checkout input[type="email"], .woocommerce-checkout input[type="tel"], .woocommerce-checkout select'
      ).css({
        "font-size": "16px", // Prevent zoom on iOS
        "box-sizing": "border-box",
      });

      // Add basic form validation feedback
      $(".woocommerce-checkout input, .woocommerce-checkout select").on(
        "blur",
        function () {
          const $field = $(this);
          const isRequired =
            $field.prop("required") || $field.hasClass("validate-required");

          if (isRequired && !$field.val().trim()) {
            $field.addClass("woocommerce-invalid");
          } else {
            $field.removeClass("woocommerce-invalid");
          }
        }
      );

      // Add phone field validation
      this.initPhoneFieldValidation();
    },

    /**
     * Initialize coupon toggle functionality - UI only
     */
    initCouponToggle: function () {
      const $couponToggle = $(".coupon-toggle");
      const $couponSection = $(".coupon-section");

      if ($couponToggle.length) {
        // Create coupon form HTML that integrates with WooCommerce
        const couponFormHTML = `
          <div class="coupon-form" style="display: none;">
            <div class="coupon-input-group">
              <input type="text" name="coupon_code" class="coupon-input" placeholder="Enter discount code" />
              <button type="button" class="coupon-apply-btn">Apply</button>
            </div>
          </div>
        `;

        // Insert coupon form after toggle button
        $couponToggle.after(couponFormHTML);

        // Toggle coupon form visibility
        $couponToggle.on("click", function (e) {
          e.preventDefault();
          const $form = $couponSection.find(".coupon-form");
          const $arrow = $(this).find(".arrow");

          if ($form.is(":visible")) {
            $form.slideUp(300);
            $arrow.text("▼");
          } else {
            $form.slideDown(300);
            $arrow.text("▲");
          }
        });

        // Handle coupon application - delegate to WooCommerce
        $couponSection.on("click", ".coupon-apply-btn", function (e) {
          e.preventDefault();
          const couponCode = $couponSection.find(".coupon-input").val().trim();

          if (couponCode) {
            CheckoutManager.applyCouponElegantly(couponCode);
          }
        });

        // Handle Enter key in coupon input
        $couponSection.on("keypress", ".coupon-input", function (e) {
          if (e.which === 13) {
            // Enter key
            e.preventDefault();
            const couponCode = $(this).val().trim();

            if (couponCode) {
              CheckoutManager.applyCouponElegantly(couponCode);
            }
          }
        });
      }
    },

    /**
     * Apply coupon elegantly - work with WooCommerce's native system
     * Optimized to prevent duplicate submissions and improve performance
     * FIXED: Added better error handling and state management
     */
    applyCouponElegantly: function (couponCode) {
      const $couponSection = $(".coupon-section");
      const $applyBtn = $couponSection.find(".coupon-apply-btn");
      const $input = $couponSection.find(".coupon-input");

      // Prevent duplicate submissions
      if ($applyBtn.prop("disabled")) {
        return;
      }

      // Show loading state
      $applyBtn.text("Applying...").prop("disabled", true);

      // Use requestAnimationFrame for smoother UI updates
      requestAnimationFrame(() => {
        try {
          // Look for WooCommerce's native coupon form
          let $wcCouponInput = $(
            '.woocommerce-form-coupon input[name="coupon_code"]'
          );
          let $wcCouponBtn = $(
            '.woocommerce-form-coupon button[name="apply_coupon"]'
          );

          // If WooCommerce coupon form exists, use it
          if ($wcCouponInput.length && $wcCouponBtn.length) {
            $wcCouponInput.val(couponCode);
            $wcCouponBtn.trigger("click");
          } else {
            // Create a hidden WooCommerce-compatible form and submit it
            // SECURITY: Sanitize coupon code to prevent XSS
            const sanitizedCouponCode = couponCode.replace(/[<>\"'&]/g, '');
            const $hiddenForm = $(`
              <form class="woocommerce-form-coupon" method="post" style="display: none;">
                <input type="text" name="coupon_code" value="${sanitizedCouponCode}" />
                <button type="submit" name="apply_coupon" value="Apply coupon">Apply</button>
              </form>
            `);

            $("body").append($hiddenForm);
            $hiddenForm.submit();
            $hiddenForm.remove();
          }

          // Reset UI state with reduced timeout
          setTimeout(() => {
            $applyBtn.text("Apply").prop("disabled", false);
            $input.val("");

            // Clear processing flag since coupon application attempt is complete
            if (window.primefitCouponProcessing) {
              delete window.primefitCouponProcessing;
            }
          }, 1500);
        } catch (error) {

          // Reset UI state on error
          $applyBtn.text("Apply").prop("disabled", false);
          $input.val("");

          // Clear processing flag on error
          if (window.primefitCouponProcessing) {
            delete window.primefitCouponProcessing;
          }
        }
      });
    },

    /**
     * Initialize summary toggle for mobile
     */
    initSummaryToggle: function () {
      const $toggle = $(".summary-toggle");
      const $content = $(".summary-content");
      const $header = $(".summary-header");

      if ($toggle.length && $content.length && $header.length) {
        // Set initial state - open by default on mobile
        if (window.innerWidth <= 1024) {
          $toggle.removeClass("collapsed");
          $content.removeClass("collapsed");
        }

        // Toggle functionality
        $header.on("click", function (e) {
          if (
            $(e.target).hasClass("summary-toggle") ||
            $(e.target).closest(".summary-toggle").length
          ) {
            return;
          }

          const $summaryContent = $(".summary-content");
          $toggle.toggleClass("collapsed");
          $summaryContent.toggleClass("collapsed");
        });

        $toggle.on("click", function (e) {
          e.stopPropagation();
          const $summaryContent = $(".summary-content");
          $(this).toggleClass("collapsed");
          $summaryContent.toggleClass("collapsed");
        });

        // Handle window resize
        $(window).on("resize", function () {
          if (window.innerWidth > 1024) {
            $toggle.removeClass("collapsed");
            $content.removeClass("collapsed");
          } else {
            // Keep open by default on mobile
            $toggle.removeClass("collapsed");
            $content.removeClass("collapsed");
          }
        });
      }
    },

    /**
     * Initialize payment method enhancements - UI only
     * Optimized with better timing and reduced DOM queries
     */
    initPaymentMethodEnhancements: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods");

      if ($paymentMethods.length && !this.isPaymentMethodsEnhanced) {
        // Clear any existing timeout
        if (this.initializationTimeout) {
          clearTimeout(this.initializationTimeout);
        }

        // Use requestAnimationFrame for better performance
        this.initializationTimeout = setTimeout(() => {
          requestAnimationFrame(() => {
            this.enhancePaymentMethodCards();
            this.addPaymentMethodIcons();
            this.addPaymentMethodBadges();
            this.initPaymentMethodInteractions();
            this.setupPaymentMethodObserver();

            // Add enhanced class to show the styled payment methods
            $paymentMethods.addClass("enhanced");
            this.isPaymentMethodsEnhanced = true;
          });
        }, 100); // Reduced delay
      }
    },

    /**
     * Setup mutation observer to watch for changes to payment methods
     * Optimized to reduce unnecessary reprocessing
     */
    setupPaymentMethodObserver: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods");

      if (
        $paymentMethods.length &&
        window.MutationObserver &&
        !this.paymentMethodObserver
      ) {
        // Debounce mutations to avoid excessive processing
        let timeoutId = null;

        this.paymentMethodObserver = new MutationObserver((mutations) => {
          if (timeoutId) {
            clearTimeout(timeoutId);
          }

          timeoutId = setTimeout(() => {
            let shouldReapply = false;

            mutations.forEach((mutation) => {
              if (
                mutation.type === "childList" ||
                mutation.type === "attributes"
              ) {
                // Check if payment method structure has changed
                const $currentPaymentMethods = $(
                  ".woocommerce-checkout .payment_methods"
                );
                if (
                  $currentPaymentMethods.length &&
                  !$currentPaymentMethods.find(".payment_method").length
                ) {
                  shouldReapply = true;
                }
              }
            });

            if (shouldReapply && this.isPaymentMethodsEnhanced) {
              // Reset the enhancement flag and reapply
              this.isPaymentMethodsEnhanced = false;
              const $currentPaymentMethods = $(
                ".woocommerce-checkout .payment_methods"
              );
              $currentPaymentMethods.removeClass("enhanced");

              // Use requestAnimationFrame for smoother UI updates
              requestAnimationFrame(() => {
                this.enhancePaymentMethodCards();
                this.addPaymentMethodIcons();
                this.addPaymentMethodBadges();
                $currentPaymentMethods.addClass("enhanced");
                this.isPaymentMethodsEnhanced = true;
              });
            }
          }, 50); // 50ms debounce
        });

        this.paymentMethodObserver.observe($paymentMethods[0], {
          childList: true,
          subtree: true,
          attributes: true,
          attributeFilter: ["class"],
        });
      }
    },

    /**
     * Enhance payment method cards with better structure
     */
    enhancePaymentMethodCards: function () {
      $(".woocommerce-checkout .payment_methods li").each(function () {
        const $li = $(this);
        const $label = $li.find("label");
        const $radio = $li.find('input[type="radio"]');
        const $paymentBox = $li.find(".payment_box");

        // Only enhance if not already enhanced
        if (!$li.find(".payment_method").length && $label.length) {
          $li.wrapInner('<div class="payment_method"></div>');
        }

        // Only restructure if payment-method-content doesn't exist
        if ($label.length && !$label.find(".payment-method-content").length) {
          const labelText = $label.text().trim();
          const $paymentContent = $(
            '<div class="payment-method-content"></div>'
          );

          const parts = labelText.split(" - ");
          const title = parts[0] || labelText;
          const description = parts[1] || "";

          $paymentContent.html(`
            <div class="payment-method-title">${title}</div>
            ${
              description
                ? `<div class="payment-method-description">${description}</div>`
                : ""
            }
          `);

          $paymentContent.css("position", "relative");

          $label.empty();
          $label.append($radio);
          $label.append($paymentContent);

          if ($paymentBox.length) {
            $paymentBox.appendTo($li.find(".payment_method"));
          }
        }
      });
    },

    /**
     * Add payment method icons
     */
    addPaymentMethodIcons: function () {
      $(".woocommerce-checkout .payment_methods li").each(function () {
        const $li = $(this);
        const $label = $li.find("label");
        const title = $li.find(".payment-method-title").text().toLowerCase();

        let iconClass = "payment-method-icon";
        if (title.includes("cash") || title.includes("delivery")) {
          iconClass += " cash";
        } else if (
          title.includes("card") ||
          title.includes("credit") ||
          title.includes("debit")
        ) {
          iconClass += " card";
        } else if (title.includes("paypal")) {
          iconClass += " paypal";
        } else if (title.includes("apple")) {
          iconClass += " apple-pay";
        }

        if (!$label.find(".payment-method-icon").length) {
          const $icon = $(`<div class="${iconClass}">💳</div>`);
          $label.prepend($icon);
        }
      });
    },

    /**
     * Add payment method badges
     */
    addPaymentMethodBadges: function () {
      $(".woocommerce-checkout .payment_methods li").each(function () {
        const $li = $(this);
        const $paymentMethod = $li.find(".payment_method");
        const title = $li.find(".payment-method-title").text().toLowerCase();

        if (title.includes("cash") || title.includes("delivery")) {
          if (!$paymentMethod.find(".payment-method-badge").length) {
            $paymentMethod.append(
              '<div class="payment-method-badge recommended">Recommended</div>'
            );
          }
        } else if (
          title.includes("card") ||
          title.includes("credit") ||
          title.includes("debit")
        ) {
          if (!$paymentMethod.find(".payment-method-badge").length) {
            $paymentMethod.append(
              '<div class="payment-method-badge secure">Secure</div>'
            );
          }
        }
      });
    },

    /**
     * Initialize payment method interactions
     */
    initPaymentMethodInteractions: function () {
      $(".woocommerce-checkout .payment_methods li").on("click", function (e) {
        const $li = $(this);
        const $radio = $li.find('input[type="radio"]');

        if (!$(e.target).is('input[type="radio"]')) {
          $radio.prop("checked", true).trigger("change");
        }
      });

      $(".woocommerce-checkout .payment_methods input[type='radio']").on(
        "change",
        function () {
          const $radio = $(this);
          const $li = $radio.closest("li");
          const $paymentMethod = $li.find(".payment_method");

          $(
            ".woocommerce-checkout .payment_methods .payment_method"
          ).removeClass("selected");

          if ($radio.is(":checked")) {
            $paymentMethod.addClass("selected");
          }
        }
      );

      // Auto-select the first payment method
      const $firstPaymentMethod = $(
        ".woocommerce-checkout .payment_methods input[type='radio']"
      ).first();
      if ($firstPaymentMethod.length && !$firstPaymentMethod.is(":checked")) {
        $firstPaymentMethod.prop("checked", true).trigger("change");
      }

      $(
        ".woocommerce-checkout .payment_methods input[type='radio']:checked"
      ).trigger("change");
    },

    /**
     * Initialize help tooltip functionality
     */
    initHelpTooltips: function () {
      $(".help-icon-inside").each(function () {
        const $helpIcon = $(this);

        $helpIcon.on("click", function (e) {
          e.preventDefault();

          if ($(window).width() <= 768) {
            $helpIcon.toggleClass("mobile-tooltip-active");
          }
        });

        $(document).on("click", function (e) {
          if (!$(e.target).closest(".help-icon-inside").length) {
            $helpIcon.removeClass("mobile-tooltip-active");
          }
        });
      });

      $(window).on("resize", function () {
        $(".help-icon-inside").removeClass("mobile-tooltip-active");
      });
    },

    /**
     * Cleanup method to dispose of observers and timeouts
     */
    cleanup: function () {
      if (this.initializationTimeout) {
        clearTimeout(this.initializationTimeout);
        this.initializationTimeout = null;
      }

      if (this.paymentMethodObserver) {
        this.paymentMethodObserver.disconnect();
        this.paymentMethodObserver = null;
      }

      this.isInitialized = false;
      this.isPaymentMethodsEnhanced = false;
    },

    /**
     * Initialize phone field validation
     * Only allow numbers, spaces, hyphens, parentheses, and plus sign
     */
    initPhoneFieldValidation: function () {
      const $phoneField = $("#billing_phone");
      
      if (!$phoneField.length) {
        return;
      }
      
      // Country to phone prefix mapping
      const countryPrefixes = {
        'AD': '+376', // Andorra
        'AE': '+971', // United Arab Emirates
        'AF': '+93',  // Afghanistan
        'AG': '+1268', // Antigua and Barbuda
        'AI': '+1264', // Anguilla
        'AL': '+355', // Albania
        'AM': '+374', // Armenia
        'AO': '+244', // Angola
        'AQ': '+672', // Antarctica
        'AR': '+54',  // Argentina
        'AS': '+1684', // American Samoa
        'AT': '+43',  // Austria
        'AU': '+61',  // Australia
        'AW': '+297', // Aruba
        'AX': '+358', // Åland Islands
        'AZ': '+994', // Azerbaijan
        'BA': '+387', // Bosnia and Herzegovina
        'BB': '+1246', // Barbados
        'BD': '+880', // Bangladesh
        'BE': '+32',  // Belgium
        'BF': '+226', // Burkina Faso
        'BG': '+359', // Bulgaria
        'BH': '+973', // Bahrain
        'BI': '+257', // Burundi
        'BJ': '+229', // Benin
        'BL': '+590', // Saint Barthélemy
        'BM': '+1441', // Bermuda
        'BN': '+673', // Brunei
        'BO': '+591', // Bolivia
        'BQ': '+599', // Caribbean Netherlands
        'BR': '+55',  // Brazil
        'BS': '+1242', // Bahamas
        'BT': '+975', // Bhutan
        'BV': '+47',  // Bouvet Island
        'BW': '+267', // Botswana
        'BY': '+375', // Belarus
        'BZ': '+501', // Belize
        'CA': '+1',   // Canada
        'CC': '+61',  // Cocos Islands
        'CD': '+243', // Democratic Republic of the Congo
        'CF': '+236', // Central African Republic
        'CG': '+242', // Republic of the Congo
        'CH': '+41',  // Switzerland
        'CI': '+225', // Côte d'Ivoire
        'CK': '+682', // Cook Islands
        'CL': '+56',  // Chile
        'CM': '+237', // Cameroon
        'CN': '+86',  // China
        'CO': '+57',  // Colombia
        'CR': '+506', // Costa Rica
        'CU': '+53',  // Cuba
        'CV': '+238', // Cape Verde
        'CW': '+599', // Curaçao
        'CX': '+61',  // Christmas Island
        'CY': '+357', // Cyprus
        'CZ': '+420', // Czech Republic
        'DE': '+49',  // Germany
        'DJ': '+253', // Djibouti
        'DK': '+45',  // Denmark
        'DM': '+1767', // Dominica
        'DO': '+1809', // Dominican Republic
        'DZ': '+213', // Algeria
        'EC': '+593', // Ecuador
        'EE': '+372', // Estonia
        'EG': '+20',  // Egypt
        'EH': '+212', // Western Sahara
        'ER': '+291', // Eritrea
        'ES': '+34',  // Spain
        'ET': '+251', // Ethiopia
        'FI': '+358', // Finland
        'FJ': '+679', // Fiji
        'FK': '+500', // Falkland Islands
        'FM': '+691', // Micronesia
        'FO': '+298', // Faroe Islands
        'FR': '+33',  // France
        'GA': '+241', // Gabon
        'GB': '+44',  // United Kingdom
        'GD': '+1473', // Grenada
        'GE': '+995', // Georgia
        'GF': '+594', // French Guiana
        'GG': '+44',  // Guernsey
        'GH': '+233', // Ghana
        'GI': '+350', // Gibraltar
        'GL': '+299', // Greenland
        'GM': '+220', // Gambia
        'GN': '+224', // Guinea
        'GP': '+590', // Guadeloupe
        'GQ': '+240', // Equatorial Guinea
        'GR': '+30',  // Greece
        'GS': '+500', // South Georgia and the South Sandwich Islands
        'GT': '+502', // Guatemala
        'GU': '+1671', // Guam
        'GW': '+245', // Guinea-Bissau
        'GY': '+592', // Guyana
        'HK': '+852', // Hong Kong
        'HM': '+672', // Heard Island and McDonald Islands
        'HN': '+504', // Honduras
        'HR': '+385', // Croatia
        'HT': '+509', // Haiti
        'HU': '+36',  // Hungary
        'ID': '+62',  // Indonesia
        'IE': '+353', // Ireland
        'IL': '+972', // Israel
        'IM': '+44',  // Isle of Man
        'IN': '+91',  // India
        'IO': '+246', // British Indian Ocean Territory
        'IQ': '+964', // Iraq
        'IR': '+98',  // Iran
        'IS': '+354', // Iceland
        'IT': '+39',  // Italy
        'JE': '+44',  // Jersey
        'JM': '+1876', // Jamaica
        'JO': '+962', // Jordan
        'JP': '+81',  // Japan
        'KE': '+254', // Kenya
        'KG': '+996', // Kyrgyzstan
        'KH': '+855', // Cambodia
        'KI': '+686', // Kiribati
        'KM': '+269', // Comoros
        'KN': '+1869', // Saint Kitts and Nevis
        'KP': '+850', // North Korea
        'KR': '+82',  // South Korea
        'KW': '+965', // Kuwait
        'KY': '+1345', // Cayman Islands
        'KZ': '+7',   // Kazakhstan
        'LA': '+856', // Laos
        'LB': '+961', // Lebanon
        'LC': '+1758', // Saint Lucia
        'LI': '+423', // Liechtenstein
        'LK': '+94',  // Sri Lanka
        'LR': '+231', // Liberia
        'LS': '+266', // Lesotho
        'LT': '+370', // Lithuania
        'LU': '+352', // Luxembourg
        'LV': '+371', // Latvia
        'LY': '+218', // Libya
        'MA': '+212', // Morocco
        'MC': '+377', // Monaco
        'MD': '+373', // Moldova
        'ME': '+382', // Montenegro
        'MF': '+590', // Saint Martin
        'MG': '+261', // Madagascar
        'MH': '+692', // Marshall Islands
        'MK': '+389', // North Macedonia
        'ML': '+223', // Mali
        'MM': '+95',  // Myanmar
        'MN': '+976', // Mongolia
        'MO': '+853', // Macau
        'MP': '+1670', // Northern Mariana Islands
        'MQ': '+596', // Martinique
        'MR': '+222', // Mauritania
        'MS': '+1664', // Montserrat
        'MT': '+356', // Malta
        'MU': '+230', // Mauritius
        'MV': '+960', // Maldives
        'MW': '+265', // Malawi
        'MX': '+52',  // Mexico
        'MY': '+60',  // Malaysia
        'MZ': '+258', // Mozambique
        'NA': '+264', // Namibia
        'NC': '+687', // New Caledonia
        'NE': '+227', // Niger
        'NF': '+672', // Norfolk Island
        'NG': '+234', // Nigeria
        'NI': '+505', // Nicaragua
        'NL': '+31',  // Netherlands
        'NO': '+47',  // Norway
        'NP': '+977', // Nepal
        'NR': '+674', // Nauru
        'NU': '+683', // Niue
        'NZ': '+64',  // New Zealand
        'OM': '+968', // Oman
        'PA': '+507', // Panama
        'PE': '+51',  // Peru
        'PF': '+689', // French Polynesia
        'PG': '+675', // Papua New Guinea
        'PH': '+63',  // Philippines
        'PK': '+92',  // Pakistan
        'PL': '+48',  // Poland
        'PM': '+508', // Saint Pierre and Miquelon
        'PN': '+64',  // Pitcairn Islands
        'PR': '+1787', // Puerto Rico
        'PS': '+970', // Palestine
        'PT': '+351', // Portugal
        'PW': '+680', // Palau
        'PY': '+595', // Paraguay
        'QA': '+974', // Qatar
        'RE': '+262', // Réunion
        'RO': '+40',  // Romania
        'RS': '+381', // Serbia
        'RU': '+7',   // Russia
        'RW': '+250', // Rwanda
        'SA': '+966', // Saudi Arabia
        'SB': '+677', // Solomon Islands
        'SC': '+248', // Seychelles
        'SD': '+249', // Sudan
        'SE': '+46',  // Sweden
        'SG': '+65',  // Singapore
        'SH': '+290', // Saint Helena
        'SI': '+386', // Slovenia
        'SJ': '+47',  // Svalbard and Jan Mayen
        'SK': '+421', // Slovakia
        'SL': '+232', // Sierra Leone
        'SM': '+378', // San Marino
        'SN': '+221', // Senegal
        'SO': '+252', // Somalia
        'SR': '+597', // Suriname
        'SS': '+211', // South Sudan
        'ST': '+239', // São Tomé and Príncipe
        'SV': '+503', // El Salvador
        'SX': '+1721', // Sint Maarten
        'SY': '+963', // Syria
        'SZ': '+268', // Eswatini
        'TC': '+1649', // Turks and Caicos Islands
        'TD': '+235', // Chad
        'TF': '+262', // French Southern Territories
        'TG': '+228', // Togo
        'TH': '+66',  // Thailand
        'TJ': '+992', // Tajikistan
        'TK': '+690', // Tokelau
        'TL': '+670', // East Timor
        'TM': '+993', // Turkmenistan
        'TN': '+216', // Tunisia
        'TO': '+676', // Tonga
        'TR': '+90',  // Turkey
        'TT': '+1868', // Trinidad and Tobago
        'TV': '+688', // Tuvalu
        'TW': '+886', // Taiwan
        'TZ': '+255', // Tanzania
        'UA': '+380', // Ukraine
        'UG': '+256', // Uganda
        'UM': '+1',   // United States Minor Outlying Islands
        'US': '+1',   // United States
        'UY': '+598', // Uruguay
        'UZ': '+998', // Uzbekistan
        'VA': '+379', // Vatican City
        'VC': '+1784', // Saint Vincent and the Grenadines
        'VE': '+58',  // Venezuela
        'VG': '+1284', // British Virgin Islands
        'VI': '+1340', // U.S. Virgin Islands
        'VN': '+84',  // Vietnam
        'VU': '+678', // Vanuatu
        'WF': '+681', // Wallis and Futuna
        'WS': '+685', // Samoa
        'XK': '+383', // Kosovo
        'YE': '+967', // Yemen
        'YT': '+262', // Mayotte
        'ZA': '+27',  // South Africa
        'ZM': '+260', // Zambia
        'ZW': '+263'  // Zimbabwe
      };
      
      // Phone number regex: allows + at start, numbers, spaces, hyphens, parentheses
      const phoneRegex = /^\+?[0-9\s\-\(\)]*$/;
      
      // Prevent invalid characters from being typed
      $phoneField.on("keypress", function(e) {
        const char = String.fromCharCode(e.which);
        const currentValue = $(this).val();
        const newValue = currentValue + char;
        
        // Allow backspace, delete, tab, escape, enter
        if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
            // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true)) {
          return;
        }
        
        // Check if the new value would be valid
        if (!phoneRegex.test(newValue)) {
          e.preventDefault();
          return false;
        }
      });
      
      // Validate on paste
      $phoneField.on("paste", function(e) {
        const $this = $(this);
        setTimeout(function() {
          const pastedValue = $this.val();
          if (!phoneRegex.test(pastedValue)) {
            // Remove invalid characters
            const cleanedValue = pastedValue.replace(/[^\+0-9\s\-\(\)]/g, '');
            $this.val(cleanedValue);
          }
        }, 10);
      });
      
      // Validate on input change
      $phoneField.on("input", function() {
        const $this = $(this);
        const value = $this.val();
        
        if (value && !phoneRegex.test(value)) {
          // Remove invalid characters
          const cleanedValue = value.replace(/[^\+0-9\s\-\(\)]/g, '');
          $this.val(cleanedValue);
        }
        
        // Visual feedback
        if (value && phoneRegex.test(value)) {
          $this.removeClass("phone-invalid").addClass("phone-valid");
        } else if (value) {
          $this.removeClass("phone-valid").addClass("phone-invalid");
        } else {
          $this.removeClass("phone-valid phone-invalid");
        }
      });
      
      // Validate on blur
      $phoneField.on("blur", function() {
        const $this = $(this);
        const value = $this.val().trim();
        
        if (!value) {
          // Phone field is now required
          $this.addClass("woocommerce-invalid");
          this.showPhoneError($this, "Phone number is required");
        } else if (!phoneRegex.test(value)) {
          $this.addClass("woocommerce-invalid");
          // Show error message
          this.showPhoneError($this, "Please enter a valid phone number");
        } else {
          $this.removeClass("woocommerce-invalid");
          this.hidePhoneError($this);
        }
      }.bind(this));
      
      // Initialize country prefix functionality with delay to ensure DOM is ready
      setTimeout(() => {
        this.initCountryPrefixLogic(countryPrefixes);
      }, 100);
      
      // Re-bind events on checkout updates
      $(document.body).on("updated_checkout", function() {
        $("#billing_phone").off("keypress paste input blur").on({
          keypress: $phoneField.data("events")?.keypress[0].handler,
          paste: $phoneField.data("events")?.paste[0].handler,
          input: $phoneField.data("events")?.input[0].handler,
          blur: $phoneField.data("events")?.blur[0].handler
        });
        
        // Re-initialize country prefix logic with delay
        setTimeout(() => {
          this.initCountryPrefixLogic(countryPrefixes);
        }, 100);
      }.bind(this));
    },

    /**
     * Show phone field error message
     */
    showPhoneError: function($field, message) {
      this.hidePhoneError($field);
      const $errorDiv = $('<div class="phone-error-message">' + message + '</div>');
      $field.after($errorDiv);
    },

    /**
     * Hide phone field error message
     */
    hidePhoneError: function($field) {
      $field.siblings('.phone-error-message').remove();
    },

    /**
     * Initialize country prefix logic for phone field
     */
    initCountryPrefixLogic: function(countryPrefixes) {
      const $countrySelect = $("#billing_country");
      const $phoneField = $("#billing_phone");
      
      // Debug logging
      console.log('Initializing country prefix logic...');
      console.log('Country select found:', $countrySelect.length);
      console.log('Phone field found:', $phoneField.length);
      
      if (!$countrySelect.length || !$phoneField.length) {
        console.log('Required elements not found, retrying...');
        // Retry after a short delay
        setTimeout(() => {
          this.initCountryPrefixLogic(countryPrefixes);
        }, 200);
        return;
      }
      
      // Remove any existing event handlers to prevent duplicates
      $countrySelect.off('change.countryPrefix');
      $phoneField.off('focus.countryPrefix input.countryPrefix');
      
      // Function to apply country prefix
      const applyCountryPrefix = function() {
        const selectedCountry = $countrySelect.val();
        const currentPhoneValue = $phoneField.val().trim();
        
        console.log('Applying country prefix for:', selectedCountry);
        console.log('Current phone value:', currentPhoneValue);
        
        if (!selectedCountry || !countryPrefixes[selectedCountry]) {
          console.log('No prefix found for country:', selectedCountry);
          return;
        }
        
        const countryPrefix = countryPrefixes[selectedCountry];
        console.log('Country prefix:', countryPrefix);
        
        // If phone field is empty, add the prefix
        if (!currentPhoneValue) {
          console.log('Phone field empty, adding prefix');
          $phoneField.val(countryPrefix + ' ');
          $phoneField.focus();
          // Position cursor after the prefix
          setTimeout(() => {
            const prefixLength = countryPrefix.length + 1; // +1 for space
            $phoneField[0].setSelectionRange(prefixLength, prefixLength);
          }, 10);
          return;
        }
        
        // If phone field has content but doesn't start with any prefix, add the country prefix
        const hasAnyPrefix = Object.values(countryPrefixes).some(prefix => 
          currentPhoneValue.startsWith(prefix)
        );
        
        if (!hasAnyPrefix && !currentPhoneValue.startsWith('+')) {
          console.log('Adding prefix to existing number');
          // Remove any existing numbers at the start and add the country prefix
          const cleanNumber = currentPhoneValue.replace(/^[0-9\s\-\(\)]+/, '');
          $phoneField.val(countryPrefix + ' ' + cleanNumber);
          
          // Position cursor after the prefix
          setTimeout(() => {
            const prefixLength = countryPrefix.length + 1; // +1 for space
            $phoneField[0].setSelectionRange(prefixLength, prefixLength);
          }, 10);
        }
      };
      
      // Function to remove prefix when country changes
      const removePreviousPrefix = function() {
        const currentPhoneValue = $phoneField.val().trim();
        
        if (!currentPhoneValue) {
          return;
        }
        
        // Find and remove any existing country prefix
        Object.values(countryPrefixes).forEach(prefix => {
          if (currentPhoneValue.startsWith(prefix)) {
            const numberWithoutPrefix = currentPhoneValue.substring(prefix.length).trim();
            $phoneField.val(numberWithoutPrefix);
            return;
          }
        });
      };
      
      // Listen for country changes
      $countrySelect.on('change.countryPrefix', function() {
        console.log('Country changed to:', $countrySelect.val());
        // Remove any existing prefix first
        removePreviousPrefix();
        
        // Apply new prefix if phone field is not empty
        const currentPhoneValue = $phoneField.val().trim();
        if (currentPhoneValue) {
          applyCountryPrefix();
        }
      });
      
      // Auto-apply prefix when phone field gets focus and is empty
      $phoneField.on('focus.countryPrefix', function() {
        console.log('Phone field focused');
        const currentPhoneValue = $phoneField.val().trim();
        const selectedCountry = $countrySelect.val();
        
        if (!currentPhoneValue && selectedCountry && countryPrefixes[selectedCountry]) {
          applyCountryPrefix();
        }
      });
      
      // Handle when user starts typing in an empty phone field
      $phoneField.on('input.countryPrefix', function() {
        const currentPhoneValue = $phoneField.val().trim();
        const selectedCountry = $countrySelect.val();
        
        // If user starts typing a number and we have a country selected, add prefix
        if (currentPhoneValue && 
            /^[0-9]/.test(currentPhoneValue) && 
            selectedCountry && 
            countryPrefixes[selectedCountry] &&
            !currentPhoneValue.startsWith('+')) {
          
          const countryPrefix = countryPrefixes[selectedCountry];
          const numberWithoutPrefix = currentPhoneValue.replace(/^[0-9\s\-\(\)]+/, '');
          
          if (numberWithoutPrefix !== currentPhoneValue) {
            $phoneField.val(countryPrefix + ' ' + numberWithoutPrefix);
            
            // Position cursor at the end
            setTimeout(() => {
              $phoneField[0].setSelectionRange($phoneField.val().length, $phoneField.val().length);
            }, 10);
          }
        }
      });
      
      console.log('Country prefix logic initialized successfully');
    },

    /**
     * Initialize country-based field hiding
     * Hide billing_address_2 and billing_postcode for specific countries
     */
    initCountryBasedFieldHiding: function () {
      const $countrySelect = $("#billing_country");
      const $address2Field = $("#billing_address_2_field");
      const $postcodeField = $("#billing_postcode_field");
      
      // Countries where address_2 and postcode should be hidden
      const hiddenCountries = ['AL', 'XK', 'MK']; // Albania, Kosovo, North Macedonia
      
      // Function to toggle field visibility
      const toggleFields = function() {
        const selectedCountry = $countrySelect.val();
        const shouldHide = hiddenCountries.includes(selectedCountry);
        
        if (shouldHide) {
          $address2Field.slideUp(300);
          $postcodeField.slideUp(300);
          // Clear the field values when hiding
          $("#billing_address_2").val('');
          $("#billing_postcode").val('');
        } else {
          $address2Field.slideDown(300);
          $postcodeField.slideDown(300);
        }
      };
      
      // Initial check on page load
      toggleFields();
      
      // Listen for country changes
      $countrySelect.on('change', toggleFields);
      
      // Also listen for WooCommerce checkout updates
      $(document.body).on('updated_checkout', function() {
        // Re-bind the change event in case the select was recreated
        $("#billing_country").off('change').on('change', toggleFields);
        // Re-check visibility
        toggleFields();
      });
    },

    /**
     * Initialize field-specific error messages
     * Moves error messages from the banner to individual fields
     */
    initFieldSpecificErrors: function () {
      // Field mapping for error messages
      const fieldMapping = {
        billing_first_name: "billing_first_name",
        billing_last_name: "billing_last_name",
        billing_address_1: "billing_address_1",
        billing_city: "billing_city",
        billing_postcode: "billing_postcode",
        billing_email: "billing_email",
        billing_phone: "billing_phone",
        billing_country: "billing_country",
        billing_state: "billing_state",
        billing_address_2: "billing_address_2",
      };

      // Process error messages
      this.processFieldErrors(fieldMapping);

      // Listen for WooCommerce checkout updates
      $(document.body).on("updated_checkout", () => {
        this.processFieldErrors(fieldMapping);
      });

      // Listen for form validation errors
      $(document.body).on("checkout_error", () => {
        setTimeout(() => {
          this.processFieldErrors(fieldMapping);
        }, 100);
      });
    },

    /**
     * Process and move error messages to individual fields
     */
    processFieldErrors: function (fieldMapping) {
      const $errorBanner = $(".woocommerce-error");

      if (!$errorBanner.length) {
        return;
      }

      // Clear existing field errors
      $(".field-error").remove();
      $(".form-row").removeClass("error");

      // Process each error message
      $errorBanner.find("li").each((index, errorItem) => {
        const $errorItem = $(errorItem);
        const errorText = $errorItem.text().trim();

        // Extract field name from error message
        const fieldName = this.extractFieldNameFromError(
          errorText,
          fieldMapping
        );

        if (fieldName) {
          // Find the corresponding form field
          const $formRow = $(`#${fieldName}`).closest(".form-row");

          if ($formRow.length) {
            // Add error class to form row
            $formRow.addClass("error");

            // Create field-specific error message
            const $fieldError = $(`
              <div class="field-error">
                ${errorText}
              </div>
            `);

            // Insert error message above the field
            $formRow.prepend($fieldError);
          }
        }
      });

      // Hide the main error banner
      $errorBanner.hide();
    },

    /**
     * Extract field name from error message text
     */
    extractFieldNameFromError: function (errorText, fieldMapping) {
      // Convert error text to lowercase for matching
      const lowerErrorText = errorText.toLowerCase();

      // Look for field names in the error text
      for (const [fieldId, fieldName] of Object.entries(fieldMapping)) {
        const fieldLabel = fieldId.replace("billing_", "").replace("_", " ");

        if (
          lowerErrorText.includes(fieldLabel) ||
          lowerErrorText.includes(fieldId)
        ) {
          return fieldId;
        }
      }

      // Fallback: try to match common patterns
      if (lowerErrorText.includes("first name")) return "billing_first_name";
      if (lowerErrorText.includes("last name")) return "billing_last_name";
      if (
        lowerErrorText.includes("street address") ||
        lowerErrorText.includes("address")
      )
        return "billing_address_1";
      if (lowerErrorText.includes("city") || lowerErrorText.includes("town"))
        return "billing_city";
      if (lowerErrorText.includes("postcode") || lowerErrorText.includes("zip"))
        return "billing_postcode";
      if (lowerErrorText.includes("email")) return "billing_email";
      if (lowerErrorText.includes("phone")) return "billing_phone";
      if (lowerErrorText.includes("country")) return "billing_country";
      if (lowerErrorText.includes("state") || lowerErrorText.includes("county"))
        return "billing_state";

      return null;
    },

    /**
     * Override WooCommerce's default checkout processing
     * Replace the white overlay with our custom dark theme overlay
     */
    overrideCheckoutProcessing: function () {
      // Listen for checkout form submission
      $(document.body).on('submit', '.woocommerce-checkout form', function(e) {
        // Show our custom processing indicator
        CheckoutManager.showCustomProcessingIndicator();

        // Prevent the default WooCommerce blockUI overlay
        e.preventDefault();

        // Submit the form via AJAX to avoid the white overlay
        const formData = new FormData(this);
        formData.append('action', 'woocommerce_checkout');
        formData.append('security', wc_checkout_params.checkout_nonce);

        $.ajax({
          url: wc_checkout_params.ajax_url,
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(response) {
            if (response.result === 'success') {
              // Redirect to success page
              window.location.href = response.redirect;
            } else {
              // Show errors
              CheckoutManager.hideCustomProcessingIndicator();
              $('.woocommerce-error').remove();
              $('.woocommerce-notices-wrapper').prepend(response.messages);
            }
          },
          error: function() {
            CheckoutManager.hideCustomProcessingIndicator();
            alert('There was an error processing your order. Please try again.');
          }
        });
      });
    },

    /**
     * Show custom processing indicator
     */
    showCustomProcessingIndicator: function () {
      const indicator = `
        <div class="checkout-processing-indicator">
          <div class="spinner"></div>
          <div class="message">Processing your order...</div>
        </div>
      `;

      $('body').append(indicator);
      $('body').addClass('checkout-processing');
    },

    /**
     * Hide custom processing indicator
     */
    hideCustomProcessingIndicator: function () {
      $('.checkout-processing-indicator').remove();
      $('body').removeClass('checkout-processing');
    },

    /**
     * Override WooCommerce's blockUI for checkout
     */
    overrideBlockUI: function () {
      // Override the blockUI plugin for checkout pages
      if (typeof $.blockUI !== 'undefined') {
        const originalBlockUI = $.blockUI;

        $.blockUI = function(opts) {
          // Only override on checkout pages
          if ($('body').hasClass('woocommerce-checkout')) {
            return; // Don't show the default blockUI
          }

          // Use original blockUI for other pages
          return originalBlockUI.apply(this, arguments);
        };
      }
    },

    /**
     * Test function for country prefix functionality
     * Can be called from browser console: CheckoutManager.testCountryPrefix()
     */
    testCountryPrefix: function() {
      console.log('Testing country prefix functionality...');
      
      const $countrySelect = $("#billing_country");
      const $phoneField = $("#billing_phone");
      
      console.log('Country select element:', $countrySelect.length);
      console.log('Phone field element:', $phoneField.length);
      
      if ($countrySelect.length && $phoneField.length) {
        // Test with US
        $countrySelect.val('US');
        $countrySelect.trigger('change');
        console.log('Phone field value after US selection:', $phoneField.val());
        
        // Test with UK
        setTimeout(() => {
          $countrySelect.val('GB');
          $countrySelect.trigger('change');
          console.log('Phone field value after UK selection:', $phoneField.val());
        }, 1000);
      } else {
        console.log('Required elements not found');
      }
    }
  };

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    // Only initialize on checkout page
    if ($("body").hasClass("woocommerce-checkout")) {
      CheckoutManager.init();

      // Handle WooCommerce checkout updates - only reapply if needed
      $(document.body).on("updated_checkout", function () {
        // Only reinitialize if payment methods were reset
        const $paymentMethods = $(".woocommerce-checkout .payment_methods");
        if (
          $paymentMethods.length &&
          !$paymentMethods.find(".payment_method").length
        ) {
          $paymentMethods.removeClass("enhanced");
          CheckoutManager.isPaymentMethodsEnhanced = false;
          CheckoutManager.initPaymentMethodEnhancements();
        }
      });

      // Handle WooCommerce fragments refresh - only reapply if needed
      $(document.body).on("wc_fragments_refreshed", function () {
        // Only reinitialize if payment methods were reset
        const $paymentMethods = $(".woocommerce-checkout .payment_methods");
        if (
          $paymentMethods.length &&
          !$paymentMethods.find(".payment_method").length
        ) {
          $paymentMethods.removeClass("enhanced");
          CheckoutManager.isPaymentMethodsEnhanced = false;
          CheckoutManager.initPaymentMethodEnhancements();
        }
      });
    }
  });

  /**
   * Expose CheckoutManager globally for debugging
   */
  window.CheckoutManager = CheckoutManager;
})(jQuery);