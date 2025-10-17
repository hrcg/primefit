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

  // Ensure this script only runs on checkout pages
  if (
    !document.body.classList.contains("woocommerce-checkout") &&
    !document.querySelector(".woocommerce-checkout") &&
    !document.querySelector("form.checkout")
  ) {
    // PrimeFit: checkout.js skipped - not on checkout page
    return;
  }

  // CartManager and CouponManager are now defined in app.js
  // They are available globally via window.CartManager and window.CouponManager

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
    isProcessingOrder: false,
    loadingStartTime: null,
    // Track Select2 open state to avoid destroying while open (mobile auto-close bug)
    isCountryDropdownOpen: false,
    // Throttle for syncing totals UI
    lastTotalsSyncTs: 0,
    // Cache last seen totals to avoid redundant DOM writes
    lastSubtotalHtml: null,
    lastTotalHtml: null,

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
        this.initCouponRemoval();
        this.initSummaryToggle();
        this.initHelpTooltips();
        this.initFieldSpecificErrors();
        this.initCountryBasedFieldHiding();
        this.initCheckoutTotalsAutoUpdate();
        this.initCountrySearch();

        // Initialize payment methods with proper timing
        this.initPaymentMethodEnhancements();

        // Override WooCommerce's checkout processing
        this.overrideCheckoutProcessing();
        this.overrideBlockUI();

        this.isInitialized = true;
      });
    },

    /**
     * Ensure shipping methods and totals refresh when address fields change
     * Adds WooCommerce-recognized classes and debounced update triggers
     */
    initCheckoutTotalsAutoUpdate: function () {
      const $form = $("form.checkout");
      if (!$form.length) return;

      const fieldSelectors = [
        "#billing_country",
        "#billing_state",
        "#billing_postcode",
        "#billing_city",
        "#billing_address_1",
        "#billing_address_2",
      ].join(", ");

      const markWooClasses = function () {
        $(fieldSelectors).addClass("update_totals_on_change address-field");
        $("#billing_country").addClass("country_to_state");
        $("#billing_state").addClass("state_select");
      };

      markWooClasses();

      // Initial check for billing_state placeholder
      const $billingState = $("#billing_state");
      if (
        $billingState.is("input[type='text']") &&
        !$billingState.attr("placeholder")
      ) {
        $billingState.attr("placeholder", "State / County *");
      }

      let updateTimeout = null;
      const debouncedUpdate = function () {
        if (updateTimeout) clearTimeout(updateTimeout);
        updateTimeout = setTimeout(function () {
          $(document.body).trigger("update_checkout");
        }, 120);
      };

      $(document).on("change", fieldSelectors, debouncedUpdate);
      $(document).on(
        "input",
        "#billing_postcode, #billing_city, #billing_address_1, #billing_address_2",
        debouncedUpdate
      );

      $(document.body).on("updated_checkout", function () {
        markWooClasses();

        // Fix billing_state placeholder when it becomes a text input
        const $billingState = $("#billing_state");
        if (
          $billingState.is("input[type='text']") &&
          !$billingState.attr("placeholder")
        ) {
          $billingState.attr("placeholder", "State / County *");
        }

        // Ensure county dropdown is properly updated after country change
        const $countrySelect = $("#billing_country");
        const $stateSelect = $("#billing_state");
        if ($countrySelect.length && $stateSelect.length) {
          // Re-apply WooCommerce classes to ensure proper functionality
          $countrySelect.addClass("country_to_state");
          $stateSelect.addClass("state_select");
        }

        // Sync our visible totals from Woo's review table (throttled)
        try {
          const nowTs = Date.now();
          if (nowTs - CheckoutManager.lastTotalsSyncTs > 300) {
            CheckoutManager.syncOrderTotalsFromReview();
            CheckoutManager.lastTotalsSyncTs = nowTs;
          }
        } catch (e) {}

        // Trigger cart fragments refresh only if not too recent to avoid churn
        (function () {
          const now = Date.now();
          const lastRefresh =
            $(document.body).data("last-wc-fragment-refresh") || 0;
          if (now - lastRefresh > 3000) {
            if (
              typeof CartManager !== "undefined" &&
              CartManager.queueRefresh
            ) {
              CartManager.queueRefresh("wc_fragment_refresh");
            } else {
              // Fallback if CartManager is not available
              $(document.body).trigger("wc_fragment_refresh");
            }
            $(document.body).data("last-wc-fragment-refresh", now);
          }
        })();
      });

      // Important: Do NOT trigger update_checkout on wc_fragments_refreshed,
      // it creates a loop with global listeners that refresh fragments after checkout updates.

      // Don't auto-trigger periodic updates; only initialize classes once
    },

    /**
     * Initialize country field search functionality
     * Uses WooCommerce's built-in Select2 for efficient search
     */
    initCountrySearch: function () {
      const $countryField = $("#billing_country");

      if (!$countryField.length) {
        return;
      }

      // Check if Select2 is available (comes with WooCommerce)
      if (typeof $.fn.select2 === "undefined") {
        // If Select2 is not loaded yet, wait and try again
        setTimeout(() => {
          this.initCountrySearch();
        }, 500);
        return;
      }

      // Destroy existing Select2 instance if present
      if ($countryField.hasClass("select2-hidden-accessible")) {
        $countryField.select2("destroy");
      }

      // Initialize Select2 on country field
      $countryField.select2({
        placeholder: "Country / Region *",
        allowClear: false,
        width: "100%",
        minimumResultsForSearch: 0, // Always show search box
        theme: "default",
        dropdownAutoWidth: false,
      });

      // Track open/close to prevent re-init while user is interacting
      $countryField.on("select2:open", () => {
        this.isCountryDropdownOpen = true;
      });
      $countryField.on("select2:close", () => {
        this.isCountryDropdownOpen = false;
      });

      // Initialize state field
      this.initStateField();

      // Trigger WooCommerce update when country changes
      $countryField.on("select2:select", function (e) {
        // Trigger change event for WooCommerce
        $(this).trigger("change");

        // Trigger WooCommerce checkout update
        $(document.body).trigger("update_checkout");
      });

      // Re-apply Select2 after WooCommerce updates
      $(document.body).on("updated_checkout", () => {
        // Skip re-initialization if user is currently interacting with the dropdown
        if (this.isCountryDropdownOpen) {
          return;
        }
        const $updatedCountryField = $("#billing_country");

        if ($updatedCountryField.length) {
          // Destroy existing Select2 instance if present
          if ($updatedCountryField.hasClass("select2-hidden-accessible")) {
            $updatedCountryField.select2("destroy");
          }

          // Reinitialize Select2
          $updatedCountryField.select2({
            placeholder: "Country / Region *",
            allowClear: false,
            width: "100%",
            minimumResultsForSearch: 0,
            theme: "default",
            dropdownAutoWidth: false,
          });

          // Re-bind open/close listeners after re-init
          $updatedCountryField.on("select2:open", () => {
            this.isCountryDropdownOpen = true;
          });
          $updatedCountryField.on("select2:close", () => {
            this.isCountryDropdownOpen = false;
          });

          // Re-bind the select event
          $updatedCountryField.on("select2:select", function (e) {
            $(this).trigger("change");
            $(document.body).trigger("update_checkout");
          });
        }

        // Re-initialize state field after checkout update
        this.initStateField();
      });
    },

    /**
     * Initialize state/county field with proper placeholder and search
     */
    initStateField: function () {
      const $stateField = $("#billing_state");

      if (!$stateField.length) {
        return;
      }

      // Check if Select2 is available
      if (typeof $.fn.select2 === "undefined") {
        return;
      }

      // If it's a select field (not text input)
      if ($stateField.is("select")) {
        // Ensure the first option has the placeholder
        const $firstOption = $stateField.find("option:first");
        if ($firstOption.length && !$firstOption.val()) {
          $firstOption.text("County *");
        }

        // Destroy existing Select2 instance if present
        if ($stateField.hasClass("select2-hidden-accessible")) {
          $stateField.select2("destroy");
        }

        // Initialize Select2 on state field with search
        $stateField.select2({
          placeholder: "County *",
          allowClear: false,
          width: "100%",
          minimumResultsForSearch: 0, // Always show search box
          theme: "default",
          dropdownAutoWidth: false,
        });

        // Trigger WooCommerce update when state changes
        $stateField.on("select2:select", function (e) {
          $(this).trigger("change");
        });
      } else if ($stateField.is("input[type='text']")) {
        // If it's a text input, ensure placeholder is set
        if (!$stateField.attr("placeholder")) {
          $stateField.attr("placeholder", "State / County *");
        }
      }
    },

    /**
     * Copy totals from Woo review order table into our custom summary blocks
     */
    syncOrderTotalsFromReview: function () {
      const $review = $(".woocommerce-checkout-review-order, #order_review");
      if (!$review.length) return;

      const $subtotal = $review.find(
        ".woocommerce-checkout-review-order-table tr.cart-subtotal td, .woocommerce-checkout-review-order-table tr.cart-subtotal .amount"
      );
      const $total = $review.find(
        ".woocommerce-checkout-review-order-table tr.order-total td, .woocommerce-checkout-review-order-table tr.order-total .amount"
      );

      // Prefer the last .amount inside each cell if multiple
      const subtotalHtml =
        $subtotal.find(".amount").last().html() || $subtotal.last().html();
      const totalHtml =
        $total.find(".amount").last().html() || $total.last().html();

      if (subtotalHtml && subtotalHtml !== this.lastSubtotalHtml) {
        $(".order-totals .total-line:contains('Subtotal') .total-value").html(
          subtotalHtml
        );
        this.lastSubtotalHtml = subtotalHtml;
      }

      if (totalHtml && totalHtml !== this.lastTotalHtml) {
        $(".order-totals .final-total .total-value").html(totalHtml);
        $(".summary-total-mobile").html(totalHtml);
        this.lastTotalHtml = totalHtml;
      }
    },

    /**
     * Handle coupon removal on checkout using same AJAX flow as mini cart
     */
    initCouponRemoval: function () {
      const self = this;

      $(document).on(
        "click",
        ".woocommerce-checkout a.woocommerce-remove-coupon, .woocommerce-checkout .remove-coupon",
        function (e) {
          e.preventDefault();

          const $button = $(this);

          // Resolve coupon code from data attribute or URL
          let couponCode = $button.data("coupon");
          if (!couponCode && $button.attr("href")) {
            try {
              const href = $button.attr("href");
              const url = new URL(href, window.location.origin);
              couponCode =
                url.searchParams.get("coupon") ||
                (href.match(/remove_coupon=([^&#]+)/) || [])[1];
            } catch (err) {
              // Ignore URL parsing errors
            }
          }

          if (!couponCode) return;

          // Loading state
          $button.addClass("loading").prop("disabled", true);

          // Remove via AJAX just like mini cart
          $.ajax({
            type: "POST",
            url: window.primefit_cart_params
              ? window.primefit_cart_params.ajax_url
              : "/wp-admin/admin-ajax.php",
            data: {
              action: "remove_coupon",
              security: window.primefit_cart_params
                ? window.primefit_cart_params.remove_coupon_nonce
                : "",
              coupon: couponCode,
            },
            success: function (response) {
              if (response && response.success) {
                // Update fragments directly if available
                var frags =
                  (response && response.fragments) ||
                  (response && response.data && response.data.fragments);
                if (frags) {
                  $.each(frags, function (key, value) {
                    $(key).replaceWith(value);
                  });
                }

                // Refresh checkout + fragments via unified manager
                if (typeof CartManager !== "undefined") {
                  CartManager.queueRefresh("update_checkout");
                  CartManager.queueRefresh("wc_fragment_refresh");
                } else {
                  // Fallback to WooCommerce events
                  $(document.body).trigger("update_checkout");
                  $(document.body).trigger("wc_fragment_refresh");
                }
              } else if ($button.attr("href")) {
                // Fallback to default navigation if AJAX fails logically
                window.location.href = $button.attr("href");
              }
            },
            error: function () {
              // Hard fallback to original link
              if ($button.attr("href")) {
                window.location.href = $button.attr("href");
              }
            },
            complete: function () {
              $button.removeClass("loading").prop("disabled", false);
            },
          });
        }
      );
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
          CouponManager.applyCoupon(couponCode.trim(), {
            isCheckout: true,
            onSuccess: () =>
              CouponManager.cleanUrlAfterCouponApplication(couponCode.trim()),
          });
        }, 500);
      } else {
        // Note: Legacy session-based coupon checking removed;
        // coupon persistence is now handled via cookies on the server-side
      }
    },

    /**
     * Check for pending coupon from session
     * Now uses unified CouponManager for race condition prevention
     * Note: Legacy DOM-based coupon data elements are no longer used;
     * coupon persistence is now handled via cookies on the server-side
     */

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
     * Apply coupon from URL parameter (DEPRECATED - use CouponManager.applyCoupon instead)
     * @deprecated Use CouponManager.applyCoupon() instead
     */
    applyCouponFromUrl: function (couponCode) {
      CouponManager.applyCoupon(couponCode, {
        isCheckout: true,
        onSuccess: () =>
          CouponManager.cleanUrlAfterCouponApplication(couponCode),
      });
    },

    /**
     * Show loading state for coupon application (DEPRECATED - now handled by CouponManager)
     * @deprecated Loading states are now handled by CouponManager
     */
    showCouponLoadingState: function () {
      // This is now handled by CouponManager.applyCouponElegantly
      // showCouponLoadingState is deprecated
    },

    /**
     * Clean URL after coupon application attempt (DEPRECATED - now handled by CouponManager)
     * @deprecated URL cleaning is now handled by CouponManager
     */
    cleanUrlAfterCouponApplication: function (couponCode) {
      CouponManager.cleanUrlAfterCouponApplication(couponCode);
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

        // Handle coupon application - delegate to unified CouponManager
        $couponSection.on("click", ".coupon-apply-btn", function (e) {
          e.preventDefault();
          const couponCode = $couponSection.find(".coupon-input").val().trim();

          if (couponCode) {
            CouponManager.applyCoupon(couponCode, { isCheckout: true });
          }
        });

        // Handle Enter key in coupon input
        $couponSection.on("keypress", ".coupon-input", function (e) {
          if (e.which === 13) {
            // Enter key
            e.preventDefault();
            const couponCode = $(this).val().trim();

            if (couponCode) {
              CouponManager.applyCoupon(couponCode, { isCheckout: true });
            }
          }
        });
      }
    },

    /**
     * Apply coupon elegantly - work with WooCommerce's native system (DEPRECATED)
     * @deprecated Use CouponManager.applyCouponElegantly() instead
     */
    applyCouponElegantly: function (couponCode, options = {}) {
      // Delegate to CouponManager for unified processing
      CouponManager.applyCoupon(couponCode, {
        isCheckout: true,
        $section: $(".coupon-section"),
        ...options,
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
            this.addPaymentMethodBadges();
            // Ensure Stripe markup stays untouched and valid
            this.fixStripePaymentMethodMarkup();
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
     * Optimized to reduce unnecessary reprocessing with enhanced debouncing and batching
     */
    setupPaymentMethodObserver: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods");

      if (
        $paymentMethods.length &&
        window.MutationObserver &&
        !this.paymentMethodObserver
      ) {
        // Enhanced debouncing with cooldown and batch processing
        let timeoutId = null;
        let cooldownUntil = 0;
        let pendingMutations = [];
        let lastProcessTime = 0;
        const minProcessInterval = 300; // Minimum 300ms between processing
        const maxBatchSize = 10; // Limit batch size to prevent memory issues

        this.paymentMethodObserver = new MutationObserver((mutations) => {
          // Add mutations to pending batch (limit batch size)
          pendingMutations.push(
            ...mutations.slice(0, maxBatchSize - pendingMutations.length)
          );

          if (timeoutId) {
            clearTimeout(timeoutId);
          }

          // Only process if cooldown period has passed and minimum interval elapsed
          const now = Date.now();
          if (
            now < cooldownUntil ||
            now - lastProcessTime < minProcessInterval
          ) {
            return;
          }

          timeoutId = setTimeout(() => {
            // Filter significant mutations only
            const significantMutations =
              this.filterSignificantMutations(pendingMutations);

            if (significantMutations.length > 0) {
              // Batch DOM operations using requestAnimationFrame
              requestAnimationFrame(() => {
                this.processPaymentMethodChanges();
                lastProcessTime = Date.now();
              });
            }

            // Clear pending mutations
            pendingMutations = [];

            // Set cooldown period to prevent rapid successive triggers
            cooldownUntil = Date.now() + 800; // Increased to 800ms cooldown
          }, 250); // Increased debounce to 250ms
        });

        this.paymentMethodObserver.observe($paymentMethods[0], {
          childList: true,
          subtree: true,
          // Only watch structural changes; attribute changes like class toggles can
          // cause unnecessary reprocessing and gateway refreshes
          attributes: false,
          characterData: false,
        });
      }
    },

    /**
     * Filter mutations to only process significant changes
     */
    filterSignificantMutations: function (mutations) {
      return mutations.filter((mutation) => {
        // Only process childList changes that add/remove elements
        if (mutation.type === "childList") {
          return (
            mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0
          );
        }

        // Only process attribute changes on specific elements
        if (mutation.type === "attributes") {
          const target = mutation.target;
          return (
            target.classList?.contains("payment_method") ||
            target.classList?.contains("payment_methods")
          );
        }

        return false;
      });
    },

    /**
     * Process payment method changes with batched operations to prevent layout thrashing
     */
    processPaymentMethodChanges: function () {
      const $currentPaymentMethods = $(
        ".woocommerce-checkout .payment_methods"
      );

      // Check if any payment methods need enhancement
      const $unenhanedItems = $currentPaymentMethods
        .find("li")
        .filter(function () {
          return !$(this).find(".payment_method").length;
        });

      // If there are unenhanced items, enhance them (even if Stripe is present)
      if ($unenhanedItems.length > 0) {
        // Batch DOM operations using requestAnimationFrame to prevent layout thrashing
        requestAnimationFrame(() => {
          // Start batch DOM operations
          this.startBatchDOMOperations();

          // Batch all enhancement operations
          this.enhancePaymentMethodCards();
          this.addPaymentMethodBadges();
          // After enhancements, normalize Stripe radio to prevent duplicate IDs/structure
          this.fixStripePaymentMethodMarkup();

          // End batch DOM operations
          this.endBatchDOMOperations();

          // Add enhanced class in a single operation
          $currentPaymentMethods.addClass("enhanced");
          this.isPaymentMethodsEnhanced = true;
        });
      } else if (!$currentPaymentMethods.hasClass("enhanced")) {
        // If all items have .payment_method but container not marked as enhanced
        $currentPaymentMethods.addClass("enhanced");
        this.isPaymentMethodsEnhanced = true;
      }
    },

    /**
     * Normalize Stripe payment method markup to avoid duplicate radios/IDs
     * and ensure WooCommerce/Stripe JS can bind correctly on desktop.
     */
    fixStripePaymentMethodMarkup: function () {
      const $methods = $(".woocommerce-checkout .payment_methods li");

      $methods.each(function () {
        const $li = $(this);
        const isStripe =
          $li.hasClass("payment_method_stripe") ||
          $li.find('input[type="radio"][value="stripe"]').length > 0 ||
          $li.find('label[for="payment_method_stripe"]').length > 0;

        if (!isStripe) return;

        const $label = $li.find('label[for="payment_method_stripe"]').first();
        // Prefer the standard WooCommerce radio by ID, otherwise first radio in li
        let $primaryRadio = $li
          .find(
            'input[type="radio"][name="payment_method"][id="payment_method_stripe"]'
          )
          .first();
        if (!$primaryRadio.length) {
          $primaryRadio = $li
            .find('input[type="radio"][name="payment_method"]')
            .first();
        }

        if (!$primaryRadio.length) return;

        // Ensure the radio has the canonical ID and the label points to it
        if ($primaryRadio.attr("id") !== "payment_method_stripe") {
          $primaryRadio.attr("id", "payment_method_stripe");
        }
        if ($label.length && $label.attr("for") !== "payment_method_stripe") {
          $label.attr("for", "payment_method_stripe");
        }

        // Remove duplicate Stripe radios within this li (keep the first)
        $li
          .find(
            'input[type="radio"][name="payment_method"][id="payment_method_stripe"]'
          )
          .not($primaryRadio)
          .remove();

        // If the radio is nested inside the label, move it directly before the label
        if ($primaryRadio.closest("label").length && $label.length) {
          const $moved = $primaryRadio.detach();
          $label.before($moved);
        }
      });
    },

    /**
     * Start batch DOM operations to minimize layout thrashing
     */
    startBatchDOMOperations: function () {
      // Use requestAnimationFrame to batch DOM operations without forced reflow
      requestAnimationFrame(() => {
        // DOM operations will be batched naturally by the browser
        // No need to force reflow
      });
    },

    /**
     * End batch DOM operations and restore normal layout
     */
    endBatchDOMOperations: function () {
      // Use requestAnimationFrame to ensure all changes are applied without forced reflow
      requestAnimationFrame(() => {
        // Browser will naturally apply all batched changes
        // No need to force reflow
      });
    },

    /**
     * Enhance payment method cards with better structure - optimized batched operations
     */
    enhancePaymentMethodCards: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods li");
      const enhancements = [];

      // Collect all enhancement operations first - optimized with for...of loop
      for (const element of $paymentMethods) {
        const $li = $(element);
        const $label = $li.find("label");
        const $radio = $li.find('input[type="radio"]');
        const $paymentBox = $li.find(".payment_box");

        // Check if this is Stripe
        const isStripe =
          $li.hasClass("payment_method_stripe") ||
          $radio.filter('[value="stripe"]').length > 0 ||
          $label.attr("for") === "payment_method_stripe";

        // Only enhance if not already enhanced
        if (!$li.find(".payment_method").length && $label.length) {
          enhancements.push(() =>
            $li.wrapInner('<div class="payment_method"></div>')
          );
        }

        // Only restructure if payment-method-content doesn't exist
        if ($label.length && !$label.find(".payment-method-content").length) {
          const labelText = $label.text().trim();
          const parts = labelText.split(" - ");
          const title = parts[0] || labelText;
          const description = parts[1] || "";

          const paymentContentHTML = `
            <div class="payment-method-title">${title}</div>
            ${
              description
                ? `<div class="payment-method-description">${description}</div>`
                : ""
            }
          `;

          enhancements.push(() => {
            const $paymentContent = $(
              `<div class="payment-method-content">${paymentContentHTML}</div>`
            );
            $paymentContent.css("position", "relative");

            if (isStripe) {
              // For Stripe: keep radio outside label, just add styling structure
              $label.empty();
              $label.append($paymentContent);
              // Radio will be repositioned by fixStripePaymentMethodMarkup()
            } else {
              // For other methods: move radio into label
              const $movedRadio = $radio.first().detach();
              $label.empty();
              if ($movedRadio && $movedRadio.length) {
                $label.append($movedRadio);
              }
              $label.append($paymentContent);
            }

            if ($paymentBox.length) {
              $paymentBox.appendTo($li.find(".payment_method"));
            }
          });
        }
      }

      // Execute all enhancements in a single batch
      enhancements.forEach((enhancement) => enhancement());
    },

    /**
     * Add payment method badges - optimized batched operations
     */
    addPaymentMethodBadges: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods li");
      const badgeOperations = [];

      // Collect all badge operations first - optimized with for...of loop
      for (const element of $paymentMethods) {
        const $li = $(element);
        const $paymentMethodTitle = $li.find(".payment-method-title");
        const title = $paymentMethodTitle.text().toLowerCase();

        if (
          !$li.find(".payment-method-badge").length &&
          $paymentMethodTitle.length
        ) {
          if (title.includes("cash") || title.includes("delivery")) {
            badgeOperations.push(() => {
              $paymentMethodTitle.after(
                '<div class="payment-method-badge recommended">Recommended</div>'
              );
            });
          } else if (
            title.includes("card") ||
            title.includes("credit") ||
            title.includes("debit")
          ) {
            badgeOperations.push(() => {
              $paymentMethodTitle.after(
                '<div class="payment-method-badge secure">Secure</div>'
              );
            });
          }
        }
      }

      // Execute all badge operations in a single batch
      badgeOperations.forEach((operation) => operation());
    },

    /**
     * Initialize payment method interactions
     */
    initPaymentMethodInteractions: function () {
      // Use event delegation with more specific targeting
      $(document).off("click.paymentMethodClick");
      $(document).on(
        "click.paymentMethodClick",
        ".woocommerce-checkout .payment_methods li",
        function (e) {
          if ($(e.target).is('input[type="radio"], a')) return;

          const $radio = $(this).find('input[type="radio"]').first();
          if (!$radio.length) return;

          // Don't preventDefault/stopPropagation - WooCommerce needs the event to bubble
          // for payment_method_selected and gateway initialization (e.g., Stripe Elements)

          // Only act if changing selection
          if ($radio.is(":checked")) return;

          $radio.prop("checked", true).trigger("change");
        }
      );

      // Handle radio change to add selected class
      $(document).off("change.paymentMethodChange");
      $(document).on(
        "change.paymentMethodChange",
        ".woocommerce-checkout .payment_methods input[type='radio']",
        function () {
          const $radio = $(this);
          const $li = $radio.closest("li");
          const $paymentMethod = $li.find(".payment_method");

          // Only update if this is being checked (not unchecked by another selection)
          if ($radio.is(":checked")) {
            // Remove selected only from currently selected items (more efficient)
            const $currentlySelected = $paymentMethod
              .closest("ul")
              .find(".payment_method.selected");

            if ($currentlySelected.length && !$currentlySelected.is($paymentMethod)) {
              $currentlySelected.removeClass("selected");
            }

            // Add selected to the checked one
            $paymentMethod.addClass("selected");
          }
          // Let WooCommerce handle payment_method_selected and iframe loads
        }
      );

      // Do not auto-force clicks/changes here; Woo sets default selection.
    },

    /**
     * Restore selected state based on which radio is currently checked
     * Call this after WooCommerce updates the checkout HTML
     */
    restorePaymentMethodSelectedState: function () {
      // Find the checked radio
      const $checkedRadio = $(
        ".woocommerce-checkout .payment_methods input[type='radio']:checked"
      );

      if ($checkedRadio.length) {
        const $li = $checkedRadio.closest("li");
        const $paymentMethod = $li.find(".payment_method");

        // Only manipulate if not already selected (avoid unnecessary DOM ops)
        if (!$paymentMethod.hasClass("selected")) {
          // Remove selected only from currently selected items (more efficient)
          $paymentMethod
            .closest("ul")
            .find(".payment_method.selected")
            .removeClass("selected");

          // Add selected to the checked one
          $paymentMethod.addClass("selected");
        }
      }
    },

    /**
     * Initialize help tooltip functionality - optimized with cached selectors
     */
    initHelpTooltips: function () {
      const $helpIcons = $(".help-icon-inside");

      // Use for...of loop for better performance
      for (const element of $helpIcons) {
        const $helpIcon = $(element);

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
      }

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

      // Lazy-loaded country prefix manager with minimal initial footprint
      const CountryPrefixManager = {
        cache: new Map(),
        loadedRegions: new Set(),

        // Minimal core prefixes - only most commonly used countries (lazy-loaded)
        corePrefixes: {
          // North America
          US: "+1",
          CA: "+1",
          // Europe (top 10 by population)
          DE: "+49",
          GB: "+44",
          FR: "+33",
          IT: "+39",
          ES: "+34",
          TR: "+90",
          RU: "+7",
          PL: "+48",
          UA: "+380",
          NL: "+31",
          AT: "+43",
          // Asia-Pacific (top 10)
          CN: "+86",
          IN: "+91",
          JP: "+81",
          KR: "+82",
          ID: "+62",
          PH: "+63",
          VN: "+84",
          TH: "+66",
          MY: "+60",
          SG: "+65",
          // South America (top 5)
          BR: "+55",
          AR: "+54",
          CO: "+57",
          PE: "+51",
          VE: "+58",
          // Africa (top 5)
          NG: "+234",
          EG: "+20",
          ZA: "+27",
          DZ: "+213",
          MA: "+212",
          // Oceania
          AU: "+61",
          NZ: "+64",
        },

        // Lazy load additional regions on demand
        loadRegion: function (regionCode) {
          if (this.loadedRegions.has(regionCode)) {
            return Promise.resolve(this.corePrefixes);
          }

          return new Promise((resolve) => {
            // Simulate async loading of additional regions
            // In production, this could load from a JSON file or API
            setTimeout(() => {
              this.loadedRegions.add(regionCode);

              // Add region-specific prefixes (example data)
              const regionPrefixes = this.getRegionPrefixes(regionCode);
              Object.assign(this.corePrefixes, regionPrefixes);

              resolve(this.corePrefixes);
            }, 10); // Minimal delay for smooth UX
          });
        },

        // Get region-specific prefixes (example implementation)
        getRegionPrefixes: function (regionCode) {
          const regions = {
            europe: {
              BE: "+32",
              CH: "+41",
              AT: "+43",
              SE: "+46",
              NO: "+47",
              DK: "+45",
              FI: "+358",
              CZ: "+420",
              HU: "+36",
              RO: "+40",
              BG: "+359",
              HR: "+385",
              SI: "+386",
              SK: "+421",
              LT: "+370",
              LV: "+371",
              EE: "+372",
              IE: "+353",
              PT: "+351",
              GR: "+30",
              AL: "+355", // Albania
              XK: "+383", // Kosovo
              MK: "+389", // North Macedonia
            },
            africa: {
              KE: "+254",
              UG: "+256",
              TZ: "+255",
              GH: "+233",
              CI: "+225",
              SN: "+221",
              ML: "+223",
              BF: "+226",
              NE: "+227",
              TD: "+235",
            },
            asia: {
              TW: "+886",
              HK: "+852",
              MO: "+853",
              KH: "+855",
              LA: "+856",
              MM: "+95",
              BD: "+880",
              LK: "+94",
              MV: "+960",
              BT: "+975",
              NP: "+977",
              PK: "+92",
              AF: "+93",
              IR: "+98",
              IQ: "+964",
            },
            americas: {
              MX: "+52",
              GT: "+502",
              BZ: "+501",
              SV: "+503",
              HN: "+504",
              NI: "+505",
              CR: "+506",
              PA: "+507",
              CU: "+53",
              JM: "+1876",
              HT: "+509",
              DO: "+1809",
              PR: "+1787",
              CL: "+56",
              UY: "+598",
              PY: "+595",
              BO: "+591",
              EC: "+593",
              GY: "+592",
              SR: "+597",
            },
          };

          return regions[regionCode] || {};
        },

        // Get prefix for country with lazy loading
        getPrefix: function (countryCode) {
          if (!countryCode) return null;

          // Check cache first
          if (this.cache.has(countryCode)) {
            return this.cache.get(countryCode);
          }

          // Check core prefixes
          if (this.corePrefixes[countryCode]) {
            const prefix = this.corePrefixes[countryCode];
            this.cache.set(countryCode, prefix);
            return prefix;
          }

          // Lazy load region if country not found in core
          const regionCode = this.getCountryRegion(countryCode);
          if (regionCode) {
            // Load region asynchronously and cache result
            this.loadRegion(regionCode).then(() => {
              if (this.corePrefixes[countryCode]) {
                this.cache.set(countryCode, this.corePrefixes[countryCode]);
              }
            });
          }

          // For countries not in core set, return null (can be extended later)
          return null;
        },

        // Determine which region a country belongs to for lazy loading
        getCountryRegion: function (countryCode) {
          // Simple region mapping - in production, this could be more sophisticated
          const regionMap = {
            // Europe
            BE: "europe",
            CH: "europe",
            AT: "europe",
            SE: "europe",
            NO: "europe",
            DK: "europe",
            FI: "europe",
            CZ: "europe",
            HU: "europe",
            RO: "europe",
            BG: "europe",
            HR: "europe",
            SI: "europe",
            SK: "europe",
            LT: "europe",
            LV: "europe",
            EE: "europe",
            IE: "europe",
            PT: "europe",
            GR: "europe",
            AL: "europe", // Albania
            XK: "europe", // Kosovo
            MK: "europe", // North Macedonia
            // Africa
            KE: "africa",
            UG: "africa",
            TZ: "africa",
            GH: "africa",
            CI: "africa",
            SN: "africa",
            ML: "africa",
            BF: "africa",
            NE: "africa",
            TD: "africa",
            // Asia
            TW: "asia",
            HK: "asia",
            MO: "asia",
            KH: "asia",
            LA: "asia",
            MM: "asia",
            BD: "asia",
            LK: "asia",
            MV: "asia",
            BT: "asia",
            NP: "asia",
            PK: "asia",
            AF: "asia",
            IR: "asia",
            IQ: "asia",
            // Americas
            MX: "americas",
            GT: "americas",
            BZ: "americas",
            SV: "americas",
            HN: "americas",
            NI: "americas",
            CR: "americas",
            PA: "americas",
            CU: "americas",
            JM: "americas",
            HT: "americas",
            DO: "americas",
            PR: "americas",
            CL: "americas",
            UY: "americas",
            PY: "americas",
            BO: "americas",
            EC: "americas",
            GY: "americas",
            SR: "americas",
          };

          return regionMap[countryCode];
        },

        // Check if country has any prefix
        hasPrefix: function (countryCode) {
          return this.getPrefix(countryCode) !== null;
        },

        // Get all prefixes for validation
        getAllPrefixes: function () {
          return Object.values(this.corePrefixes);
        },
      };

      // Phone number regex: allows + at start, numbers, spaces, hyphens, parentheses
      const phoneRegex = /^\+?[0-9\s\-\(\)]*$/;

      // Prevent invalid characters from being typed
      $phoneField.on("keypress", function (e) {
        const char = String.fromCharCode(e.which);
        const currentValue = $(this).val();
        const newValue = currentValue + char;

        // Allow backspace, delete, tab, escape, enter
        if (
          [8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
          // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
          (e.keyCode === 65 && e.ctrlKey === true) ||
          (e.keyCode === 67 && e.ctrlKey === true) ||
          (e.keyCode === 86 && e.ctrlKey === true) ||
          (e.keyCode === 88 && e.ctrlKey === true)
        ) {
          return;
        }

        // Check if the new value would be valid
        if (!phoneRegex.test(newValue)) {
          e.preventDefault();
          return false;
        }
      });

      // Validate on paste
      $phoneField.on("paste", function (e) {
        const $this = $(this);
        setTimeout(function () {
          const pastedValue = $this.val();
          if (!phoneRegex.test(pastedValue)) {
            // Remove invalid characters
            const cleanedValue = pastedValue.replace(/[^\+0-9\s\-\(\)]/g, "");
            $this.val(cleanedValue);
          }
        }, 10);
      });

      // Validate on input change
      $phoneField.on("input", function () {
        const $this = $(this);
        const value = $this.val();

        if (value && !phoneRegex.test(value)) {
          // Remove invalid characters
          const cleanedValue = value.replace(/[^\+0-9\s\-\(\)]/g, "");
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
      $phoneField.on(
        "blur",
        function () {
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
        }.bind(this)
      );

      // Initialize country prefix functionality with delay to ensure DOM is ready
      setTimeout(() => {
        this.initCountryPrefixLogic(CountryPrefixManager);
      }, 100);

      // Re-bind events on checkout updates
      $(document.body).on(
        "updated_checkout",
        function () {
          $("#billing_phone")
            .off("keypress paste input blur")
            .on({
              keypress: $phoneField.data("events")?.keypress[0].handler,
              paste: $phoneField.data("events")?.paste[0].handler,
              input: $phoneField.data("events")?.input[0].handler,
              blur: $phoneField.data("events")?.blur[0].handler,
            });

          // Re-initialize country prefix logic with delay
          setTimeout(() => {
            this.initCountryPrefixLogic(CountryPrefixManager);
          }, 100);
        }.bind(this)
      );
    },

    /**
     * Show phone field error message
     */
    showPhoneError: function ($field, message) {
      this.hidePhoneError($field);
      const $errorDiv = $(
        '<div class="phone-error-message">' + message + "</div>"
      );
      $field.after($errorDiv);
    },

    /**
     * Hide phone field error message
     */
    hidePhoneError: function ($field) {
      $field.siblings(".phone-error-message").remove();
    },

    /**
     * Initialize country prefix logic for phone field - optimized version
     */
    initCountryPrefixLogic: function (countryPrefixManager) {
      const $countrySelect = $("#billing_country");
      const $phoneField = $("#billing_phone");

      if (!$countrySelect.length || !$phoneField.length) {
        // Retry after a short delay
        setTimeout(() => {
          this.initCountryPrefixLogic(countryPrefixManager);
        }, 200);
        return;
      }

      // Remove any existing event handlers to prevent duplicates
      $countrySelect.off("change.countryPrefix");
      $phoneField.off("focus.countryPrefix input.countryPrefix");

      // Debounced prefix application to prevent excessive DOM manipulation
      let prefixTimeout = null;
      const debouncedApplyPrefix = () => {
        if (prefixTimeout) {
          clearTimeout(prefixTimeout);
        }
        prefixTimeout = setTimeout(() => {
          this.applyCountryPrefix(
            $countrySelect,
            $phoneField,
            countryPrefixManager
          );
        }, 100);
      };

      // Debounced prefix removal
      let removeTimeout = null;
      const debouncedRemovePrefix = () => {
        if (removeTimeout) {
          clearTimeout(removeTimeout);
        }
        removeTimeout = setTimeout(() => {
          this.removePreviousPrefix($phoneField, countryPrefixManager);
        }, 50);
      };

      // Single event handler for country changes
      $countrySelect.on("change.countryPrefix", function () {
        debouncedRemovePrefix();

        // Apply new prefix if phone field has content
        const currentPhoneValue = $phoneField.val().trim();
        if (currentPhoneValue) {
          debouncedApplyPrefix();
        }
      });

      // Single event handler for phone field focus
      $phoneField.on("focus.countryPrefix", function () {
        const currentPhoneValue = $phoneField.val().trim();
        const selectedCountry = $countrySelect.val();

        if (
          !currentPhoneValue &&
          selectedCountry &&
          countryPrefixManager.hasPrefix(selectedCountry)
        ) {
          debouncedApplyPrefix();
        }
      });

      // Single event handler for phone field input
      $phoneField.on("input.countryPrefix", function () {
        const currentPhoneValue = $phoneField.val().trim();
        const selectedCountry = $countrySelect.val();

        // If user starts typing a number and we have a country selected, add prefix
        if (
          currentPhoneValue &&
          /^[0-9]/.test(currentPhoneValue) &&
          selectedCountry &&
          countryPrefixManager.hasPrefix(selectedCountry) &&
          !currentPhoneValue.startsWith("+")
        ) {
          debouncedApplyPrefix();
        }
      });
    },

    /**
     * Apply country prefix with optimized DOM manipulation
     */
    applyCountryPrefix: function (
      $countrySelect,
      $phoneField,
      countryPrefixManager
    ) {
      const selectedCountry = $countrySelect.val();
      const currentPhoneValue = $phoneField.val().trim();

      if (
        !selectedCountry ||
        !countryPrefixManager.hasPrefix(selectedCountry)
      ) {
        return;
      }

      const countryPrefix = countryPrefixManager.getPrefix(selectedCountry);

      // If phone field is empty, add the prefix
      if (!currentPhoneValue) {
        $phoneField.val(countryPrefix + " ");
        $phoneField.focus();
        // Position cursor after the prefix
        setTimeout(() => {
          const prefixLength = countryPrefix.length + 1; // +1 for space
          $phoneField[0].setSelectionRange(prefixLength, prefixLength);
        }, 10);
        return;
      }

      // If phone field has content but doesn't start with any prefix, add the country prefix
      const hasAnyPrefix = countryPrefixManager
        .getAllPrefixes()
        .some((prefix) => currentPhoneValue.startsWith(prefix));

      if (!hasAnyPrefix && !currentPhoneValue.startsWith("+")) {
        // Remove any existing numbers at the start and add the country prefix
        const cleanNumber = currentPhoneValue.replace(/^[0-9\s\-\(\)]+/, "");
        $phoneField.val(countryPrefix + " " + cleanNumber);

        // Position cursor after the prefix
        setTimeout(() => {
          const prefixLength = countryPrefix.length + 1; // +1 for space
          $phoneField[0].setSelectionRange(prefixLength, prefixLength);
        }, 10);
      }
    },

    /**
     * Remove previous prefix with optimized DOM manipulation
     */
    removePreviousPrefix: function ($phoneField, countryPrefixManager) {
      const currentPhoneValue = $phoneField.val().trim();

      if (!currentPhoneValue) {
        return;
      }

      // Find and remove any existing country prefix
      const prefixes = countryPrefixManager.getAllPrefixes();
      for (const prefix of prefixes) {
        if (currentPhoneValue.startsWith(prefix)) {
          const numberWithoutPrefix = currentPhoneValue
            .substring(prefix.length)
            .trim();
          $phoneField.val(numberWithoutPrefix);
          break; // Only remove the first matching prefix
        }
      }
    },

    /**
     * Initialize country-based field hiding
     * Hide billing_address_2 and billing_postcode for specific countries
     * Make billing_email not required for specific countries
     */
    initCountryBasedFieldHiding: function () {
      const $countrySelect = $("#billing_country");
      const $address2Field = $("#billing_address_2_field");
      const $postcodeField = $("#billing_postcode_field");
      const $emailField = $("#billing_email");

      // Countries where address_2, postcode, and email should be optional
      const hiddenCountries = ["AL", "XK", "MK"]; // Albania, Kosovo, North Macedonia

      // Function to toggle field visibility and requirements
      const toggleFields = function () {
        const selectedCountry = $countrySelect.val();
        const shouldHide = hiddenCountries.includes(selectedCountry);
        const $postcodeInput = $("#billing_postcode");
        const $postcodeWrapper = $postcodeField.find(".optional-field-wrapper");

        if (shouldHide) {
          $address2Field.slideUp(300);
          $postcodeField.slideUp(300);
          // Clear the field values when hiding
          $("#billing_address_2").val("");
          $("#billing_postcode").val("");
          // Remove required attribute when hidden
          $postcodeInput.removeAttr("required");

          // Make email not required for these countries
          $emailField.removeAttr("required");
          $emailField.attr("placeholder", "Email address");
        } else {
          $address2Field.slideDown(300);
          $postcodeField.slideDown(300);
          // Make postcode required when visible
          $postcodeInput.attr("required", "required");
          // Update placeholder and remove optional text
          $postcodeInput.attr("placeholder", "Postal code *");
          $postcodeWrapper.find(".optional-text").text("(required)");

          // Make email required for other countries
          $emailField.attr("required", "required");
          $emailField.attr("placeholder", "Email address *");
        }
      };

      // Initial check on page load
      toggleFields();

      // Listen for country changes
      $countrySelect.on("change", toggleFields);

      // Also listen for WooCommerce checkout updates
      $(document.body).on("updated_checkout", function () {
        // Re-bind the change event in case the select was recreated
        $("#billing_country").off("change").on("change", toggleFields);
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
        this.handleCouponErrors(); // Handle coupon-specific errors

        // Re-initialize payment method interactions after checkout update
        // This ensures our click handlers work even after WooCommerce refreshes the payment methods
        setTimeout(() => {
          this.initPaymentMethodInteractions();
        }, 100);
      });

      // Listen for form validation errors
      $(document.body).on("checkout_error", () => {
        setTimeout(() => {
          this.processFieldErrors(fieldMapping);
          this.handleCouponErrors(); // Handle coupon-specific errors
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

      // Process each error message - optimized with for...of loop
      const $errorItems = $errorBanner.find("li");
      for (const errorElement of $errorItems) {
        const $errorItem = $(errorElement);
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
      }

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
     * Handle coupon-specific errors from WooCommerce notices
     */
    handleCouponErrors: function () {
      const $errorBanner = $(
        ".woocommerce-error, .woocommerce-NoticeGroup-checkout .woocommerce-error"
      );

      if (!$errorBanner.length) {
        return;
      }

      // Process each error message
      const $errorItems = $errorBanner.find("li");
      for (const errorElement of $errorItems) {
        const $errorItem = $(errorElement);
        const errorText = $errorItem.text().trim();
        const lowerErrorText = errorText.toLowerCase();

        // Check if this is a coupon-related error
        const isCouponError =
          lowerErrorText.includes("coupon") ||
          lowerErrorText.includes("discount code");

        // Check if this is a usage limit error
        const isUsageLimitError =
          lowerErrorText.includes("usage limit") ||
          lowerErrorText.includes("has been reached") ||
          lowerErrorText.includes("maximum usage");

        // Show toast notification for coupon errors
        if (isCouponError && typeof ToastNotification !== "undefined") {
          if (isUsageLimitError) {
            // Show error toast for usage limit
            ToastNotification.error(errorText, {
              duration: 6000,
              position: "top-right",
            });

            // Show follow-up toast prompting to remove the coupon
            setTimeout(() => {
              ToastNotification.warning(
                "Please remove this coupon code to continue",
                {
                  duration: 5000,
                  position: "top-right",
                }
              );
            }, 500);
          } else {
            // Show regular error toast for other coupon errors
            ToastNotification.error(errorText, {
              duration: 5000,
              position: "top-right",
            });
          }
        }
      }
    },

    /**
     * Override WooCommerce's default checkout processing
     * Replace the white overlay with our custom dark theme overlay
     */
    overrideCheckoutProcessing: function () {
      // Show loading indicator when checkout processing starts
      $(document.body).on("checkout_place_order", function () {
        CheckoutManager.showCustomProcessingIndicator();
        // Return true to allow the checkout to proceed
        return true;
      });

      // Hide loading indicator when there's an error
      $(document.body).on("checkout_error", function () {
        // Only hide if we're actually processing an order
        if (CheckoutManager.isProcessingOrder) {
          CheckoutManager.hideCustomProcessingIndicator();
        }
      });

      // Hide loading indicator when checkout completes successfully
      $(document.body).on("checkout_complete", function () {
        CheckoutManager.hideCustomProcessingIndicator();
      });

      // Also handle the case when the form is submitted successfully
      // This covers cases where WooCommerce redirects to thank you page
      $(document).on("submit", "form.checkout", function () {
        // Small delay to ensure the loading indicator shows before redirect
        setTimeout(() => {
          CheckoutManager.showCustomProcessingIndicator();
        }, 100);
      });

      // Show loading dots immediately when place order button is clicked
      // This provides immediate visual feedback without interfering with form submission
      $(document).on("click", "#place_order", function (e) {
        // Only show loading if the button is not already in loading state
        if (!$(this).hasClass("loading")) {
          CheckoutManager.showPlaceOrderLoading();

          // Also show the full processing indicator after a short delay
          // This ensures the loading state persists even if checkout_place_order fires quickly
          setTimeout(() => {
            CheckoutManager.showCustomProcessingIndicator();
          }, 200);
        }
        // Don't prevent default - let WooCommerce handle the form submission
      });
    },

    /**
     * Show custom processing indicator
     */
    showCustomProcessingIndicator: function () {
      // Remove any existing indicators first
      $(
        ".checkout-processing-backdrop, .checkout-processing-indicator"
      ).remove();

      const backdrop = '<div class="checkout-processing-backdrop"></div>';
      const indicator = `
        <div class="checkout-processing-indicator">
          <div class="spinner"></div>
          <div class="message">Processing your order...</div>
        </div>
      `;

      $("body").append(backdrop + indicator);
      $("body").addClass("checkout-processing");

      // Show loading dots on place order button if not already showing
      const $placeOrderBtn = $("#place_order");
      if ($placeOrderBtn.length && !$placeOrderBtn.hasClass("loading")) {
        this.showPlaceOrderLoading();
      }
    },

    /**
     * Hide custom processing indicator
     */
    hideCustomProcessingIndicator: function () {
      $(
        ".checkout-processing-backdrop, .checkout-processing-indicator"
      ).remove();
      $("body").removeClass("checkout-processing");

      // Hide loading dots on place order button
      this.hidePlaceOrderLoading();
    },

    /**
     * Show loading dots on place order button
     */
    showPlaceOrderLoading: function () {
      const $placeOrderBtn = $("#place_order");
      if ($placeOrderBtn.length) {
        // Store original text
        const originalText = $placeOrderBtn.text().trim();
        $placeOrderBtn.data("original-text", originalText);

        // Add loading class and replace text with dots
        $placeOrderBtn.addClass("loading");
        $placeOrderBtn.html(
          '<span class="loading-dots"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span>'
        );
        // Don't disable the button here - let WooCommerce handle the disabling

        // Set processing flag
        this.isProcessingOrder = true;
        this.loadingStartTime = Date.now();
      }
    },

    /**
     * Hide loading dots on place order button
     */
    hidePlaceOrderLoading: function () {
      const $placeOrderBtn = $("#place_order");
      if ($placeOrderBtn.length) {
        // Ensure minimum display time of 1 second for better UX
        const minDisplayTime = 1000; // 1 second
        const elapsedTime = this.loadingStartTime
          ? Date.now() - this.loadingStartTime
          : 0;
        const remainingTime = Math.max(0, minDisplayTime - elapsedTime);

        setTimeout(() => {
          // Remove loading class and restore original text
          $placeOrderBtn.removeClass("loading");
          const originalText =
            $placeOrderBtn.data("original-text") || "Place order";
          $placeOrderBtn.text(originalText);
          // Don't re-enable the button here - let WooCommerce handle the state

          // Reset processing flag
          this.isProcessingOrder = false;
          this.loadingStartTime = null;
        }, remainingTime);
      }
    },

    /**
     * Override WooCommerce's blockUI for checkout
     */
    overrideBlockUI: function () {
      // Override the blockUI plugin for checkout pages
      if (typeof $.blockUI !== "undefined" && !$.blockUI.__primefitOverridden) {
        const originalBlockUI = $.blockUI;
        const originalDefaults =
          originalBlockUI && originalBlockUI.defaults
            ? originalBlockUI.defaults
            : {};
        const originalVersion =
          originalBlockUI && originalBlockUI.version
            ? originalBlockUI.version
            : undefined;
        const originalSetDefaults =
          originalBlockUI && originalBlockUI.setDefaults
            ? originalBlockUI.setDefaults
            : undefined;

        // Create a thin wrapper that disables ONLY the global page overlay on checkout
        const blockUIWrapper = function () {
          if ($("body").hasClass("woocommerce-checkout")) {
            // Suppress the full-page white overlay on checkout
            return;
          }
          return originalBlockUI.apply(this, arguments);
        };

        // Preserve plugin metadata/properties so $.fn.block continues to work
        blockUIWrapper.defaults = originalDefaults;
        if (originalVersion) blockUIWrapper.version = originalVersion;
        if (originalSetDefaults)
          blockUIWrapper.setDefaults = originalSetDefaults;
        blockUIWrapper.__primefitOverridden = true;

        $.blockUI = blockUIWrapper;
      }
    },

    /**
     * Test function for country prefix functionality
     * Can be called from browser console: CheckoutManager.testCountryPrefix()
     */
    testCountryPrefix: function () {
      // Testing country prefix functionality

      const $countrySelect = $("#billing_country");
      const $phoneField = $("#billing_phone");

      // Country select element found
      // Phone field element found

      if ($countrySelect.length && $phoneField.length) {
        // Test with US
        $countrySelect.val("US");
        $countrySelect.trigger("change");
        // Phone field value after US selection

        // Test with UK
        setTimeout(() => {
          $countrySelect.val("GB");
          $countrySelect.trigger("change");
          // Phone field value after UK selection
        }, 1000);
      } else {
        // Required elements not found
      }
    },

    /**
     * Test function for payment method selection
     * Can be called from browser console: CheckoutManager.testPaymentMethodSelection()
     */
    testPaymentMethodSelection: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods li");

      if ($paymentMethods.length > 0) {
        // Test clicking on the first payment method
        const $firstPaymentMethod = $paymentMethods.first();
        const $radio = $firstPaymentMethod.find('input[type="radio"]').first();
        const $paymentMethodContainer =
          $firstPaymentMethod.find(".payment_method");

        if ($radio.length) {
          console.log("Testing payment method selection...");
          console.log("Radio checked before click:", $radio.is(":checked"));
          console.log(
            "Payment method selected before click:",
            $paymentMethodContainer.hasClass("selected")
          );

          // Simulate click on the li element
          $firstPaymentMethod.click();

          setTimeout(() => {
            console.log("Radio checked after click:", $radio.is(":checked"));
            console.log(
              "Payment method selected after click:",
              $paymentMethodContainer.hasClass("selected")
            );

            // Check if payment gateway elements exist (like Stripe iframe containers)
            const $paymentBoxes = $firstPaymentMethod.find(".payment_box");
            console.log("Payment boxes found:", $paymentBoxes.length);

            // Check for specific gateway elements
            const $stripeElements = $firstPaymentMethod.find(
              '[id*="stripe"], [class*="stripe"]'
            );
            console.log("Stripe elements found:", $stripeElements.length);

            console.log("Payment method selection test complete");
          }, 150);
        } else {
          console.log("No radio button found in payment method");
        }
      } else {
        console.log("No payment methods found");
      }
    },

    /**
     * Test function for switching between payment methods
     * Can be called from browser console: CheckoutManager.testPaymentMethodSwitching()
     */
    testPaymentMethodSwitching: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods li");

      if ($paymentMethods.length > 1) {
        const $firstPaymentMethod = $paymentMethods.first();
        const $secondPaymentMethod = $paymentMethods.eq(1);

        console.log("Testing payment method switching...");

        // Click on first payment method
        $firstPaymentMethod.click();

        setTimeout(() => {
          console.log("After clicking first payment method:");
          console.log(
            "- First radio checked:",
            $firstPaymentMethod.find('input[type="radio"]').is(":checked")
          );
          console.log(
            "- Second radio checked:",
            $secondPaymentMethod.find('input[type="radio"]').is(":checked")
          );

          // Click on second payment method
          $secondPaymentMethod.click();

          setTimeout(() => {
            console.log("After clicking second payment method:");
            console.log(
              "- First radio checked:",
              $firstPaymentMethod.find('input[type="radio"]').is(":checked")
            );
            console.log(
              "- Second radio checked:",
              $secondPaymentMethod.find('input[type="radio"]').is(":checked")
            );

            console.log("Payment method switching test complete");
          }, 150);
        }, 150);
      } else {
        console.log("Need at least 2 payment methods for switching test");
      }
    },
  };

  // ----- Private helpers: safe checkout redirect when cart is empty -----
  let hasCheckoutRedirected = false;

  function isOnCheckoutPage() {
    return document.body.classList.contains("woocommerce-checkout");
  }

  function isOnOrderEndpoint() {
    var path = window.location.pathname || "";
    return (
      path.indexOf("/order-received") !== -1 ||
      path.indexOf("/order-pay") !== -1
    );
  }

  function isCartEmptyInDom() {
    var miniEmpty =
      jQuery(".woocommerce-mini-cart__empty-message").length > 0 ||
      jQuery(".pf-mini-cart-empty").length > 0;

    var hasReviewTable =
      jQuery(".woocommerce-checkout-review-order-table").length > 0;
    var reviewHasItems =
      jQuery(".woocommerce-checkout-review-order-table .cart_item").length > 0;
    var reviewEmpty = hasReviewTable && !reviewHasItems;

    return miniEmpty || reviewEmpty;
  }

  function tryCheckoutRedirectIfCartEmpty(force) {
    if (!isOnCheckoutPage() || isOnOrderEndpoint()) return;
    if (hasCheckoutRedirected && !force) return;
    if (!force && !isCartEmptyInDom()) return;

    var shopUrl =
      (window.primefit_checkout_params &&
        window.primefit_checkout_params.shop_url) ||
      "/";
    if (!shopUrl) return;

    hasCheckoutRedirected = true;
    window.location.href = shopUrl;
  }

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    // Only initialize on checkout page
    if ($("body").hasClass("woocommerce-checkout")) {
      CheckoutManager.init();

      // Handle WooCommerce checkout updates - only reapply if needed
      $(document.body).on("updated_checkout", function () {
        // Check if any payment methods need enhancement
        const $paymentMethods = $(".woocommerce-checkout .payment_methods");

        if ($paymentMethods.length) {
          const $unenhanedItems = $paymentMethods
            .find("li")
            .filter(function () {
              return !$(this).find(".payment_method").length;
            });

          // Only reinitialize if there are unenhanced items
          if ($unenhanedItems.length > 0) {
            CheckoutManager.isPaymentMethodsEnhanced = false;
            CheckoutManager.initPaymentMethodEnhancements();
          }
          // Always ensure Stripe's markup is normalized after every refresh
          CheckoutManager.fixStripePaymentMethodMarkup();

          // Restore the selected state after WooCommerce refreshes the HTML
          CheckoutManager.restorePaymentMethodSelectedState();
        }
      });

      // Handle WooCommerce fragments refresh - only reapply if needed
      $(document.body).on("wc_fragments_refreshed", function () {
        // Check if any payment methods need enhancement
        const $paymentMethods = $(".woocommerce-checkout .payment_methods");

        if ($paymentMethods.length) {
          const $unenhanedItems = $paymentMethods
            .find("li")
            .filter(function () {
              return !$(this).find(".payment_method").length;
            });

          // Only reinitialize if there are unenhanced items
          if ($unenhanedItems.length > 0) {
            CheckoutManager.isPaymentMethodsEnhanced = false;
            CheckoutManager.initPaymentMethodEnhancements();
          }
          // Normalize Stripe radio/label positions and IDs to keep Elements working
          CheckoutManager.fixStripePaymentMethodMarkup();

          // Restore the selected state after fragments refresh
          CheckoutManager.restorePaymentMethodSelectedState();
        }

        // After fragments refresh, verify if cart became empty and redirect safely
        tryCheckoutRedirectIfCartEmpty();
      });

      // Also listen for cart item removal events to check emptiness
      $(document.body).on("removed_from_cart", function () {
        setTimeout(function () {
          tryCheckoutRedirectIfCartEmpty();
        }, 50);
      });

      // If server indicated redirect (rare), honor it once on load
      if (
        typeof window.primefit_checkout_params !== "undefined" &&
        window.primefit_checkout_params &&
        window.primefit_checkout_params.should_redirect
      ) {
        setTimeout(function () {
          tryCheckoutRedirectIfCartEmpty(true);
        }, 50);
      }
    }
  });

  /**
   * Expose CheckoutManager globally
   * CartManager and CouponManager are already exposed from app.js
   */
  window.CheckoutManager = CheckoutManager;
})(jQuery);
