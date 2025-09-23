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

        // Initialize payment methods with proper timing
        this.initPaymentMethodEnhancements();

        this.isInitialized = true;
        console.log("✨ PrimeFit checkout enhancements loaded");
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
        console.log(`🎫 Found coupon in URL: ${couponCode}`);

        // Check if coupon is already applied
        const appliedCoupons = this.getAppliedCoupons();
        if (appliedCoupons.includes(couponCode.toUpperCase())) {
          console.log(`✅ Coupon ${couponCode} is already applied`);
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
     */
    checkForPendingCouponFromSession: function () {
      // Check for pending coupon data from cart fragments (hidden element)
      const $couponData = jQuery(".primefit-coupon-data");
      if ($couponData.length) {
        const pendingCoupon = $couponData.data("pending-coupon");
        if (pendingCoupon && pendingCoupon.trim()) {
          console.log(`🎫 Found pending coupon from session: ${pendingCoupon}`);

          // Check if coupon is already applied
          const appliedCoupons = this.getAppliedCoupons();
          if (appliedCoupons.includes(pendingCoupon.toUpperCase())) {
            console.log(
              `✅ Pending coupon ${pendingCoupon} is already applied`
            );
            return;
          }

          // Apply the pending coupon with additional safety check
          setTimeout(() => {
            // Double-check that WooCommerce is loaded before applying
            if (
              typeof wc_add_to_cart_params !== "undefined" ||
              jQuery(".woocommerce-checkout").length
            ) {
              this.applyCouponFromUrl(pendingCoupon.trim());
            } else {
              console.log(
                "⏳ Waiting for WooCommerce to load before applying session coupon"
              );
              // Try again after another delay
              setTimeout(() => {
                if (
                  typeof wc_add_to_cart_params !== "undefined" ||
                  jQuery(".woocommerce-checkout").length
                ) {
                  this.applyCouponFromUrl(pendingCoupon.trim());
                } else {
                  console.log(
                    "❌ WooCommerce not loaded, cannot apply session coupon:",
                    pendingCoupon
                  );
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
     */
    applyCouponFromUrl: function (couponCode) {
      console.log(`🚀 Applying coupon from URL: ${couponCode}`);

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
          const $hiddenForm = $(`
            <form class="woocommerce-form-coupon" method="post" style="display: none;">
              <input type="text" name="coupon_code" value="${couponCode}" />
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
        }, 1500);
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