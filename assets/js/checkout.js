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
    console.log("PrimeFit: checkout.js skipped - not on checkout page");
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
          CouponManager.applyCoupon(couponCode.trim(), {
            isCheckout: true,
            onSuccess: () =>
              CouponManager.cleanUrlAfterCouponApplication(couponCode.trim()),
          });
        }, 500);
      } else {
        // Check for pending coupon from session (base URL case)
        this.checkForPendingCouponFromSession();
      }
    },

    /**
     * Check for pending coupon from session
     * Now uses unified CouponManager for race condition prevention
     */
    checkForPendingCouponFromSession: function () {
      // Check for pending coupon data from cart fragments (hidden element)
      const $couponData = jQuery(".primefit-coupon-data");
      if ($couponData.length) {
        const pendingCoupon = $couponData.data("pending-coupon");
        if (pendingCoupon && pendingCoupon.trim()) {
          // Use unified CouponManager to apply pending coupon
          setTimeout(() => {
            // Double-check that WooCommerce is loaded before applying
            if (
              typeof wc_add_to_cart_params !== "undefined" ||
              jQuery(".woocommerce-checkout").length
            ) {
              CouponManager.applyCoupon(pendingCoupon.trim(), {
                isCheckout: true,
                onSuccess: () =>
                  CouponManager.cleanUrlAfterCouponApplication(
                    pendingCoupon.trim()
                  ),
              });
            } else {
              // Try again after another delay
              setTimeout(() => {
                if (
                  typeof wc_add_to_cart_params !== "undefined" ||
                  jQuery(".woocommerce-checkout").length
                ) {
                  CouponManager.applyCoupon(pendingCoupon.trim(), {
                    isCheckout: true,
                    onSuccess: () =>
                      CouponManager.cleanUrlAfterCouponApplication(
                        pendingCoupon.trim()
                      ),
                  });
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
      console.warn(
        "showCouponLoadingState is deprecated. Loading states are now handled by CouponManager."
      );
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
          attributes: true,
          attributeFilter: ["class", "id", "data-*"], // More specific attribute filtering
          characterData: false, // Disable character data observation
          attributeOldValue: false, // Don't store old attribute values
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

      // Only reapply if payment methods were actually reset
      if (
        $currentPaymentMethods.length &&
        !$currentPaymentMethods.hasClass("enhanced") &&
        !$currentPaymentMethods.find(".payment_method").length
      ) {
        // Batch DOM operations using requestAnimationFrame to prevent layout thrashing
        requestAnimationFrame(() => {
          // Start batch DOM operations
          this.startBatchDOMOperations();

          // Remove enhanced class temporarily
          $currentPaymentMethods.removeClass("enhanced");
          this.isPaymentMethodsEnhanced = false;

          // Batch all enhancement operations
          this.enhancePaymentMethodCards();
          this.addPaymentMethodIcons();
          this.addPaymentMethodBadges();

          // End batch DOM operations
          this.endBatchDOMOperations();

          // Re-add enhanced class in a single operation
          $currentPaymentMethods.addClass("enhanced");
          this.isPaymentMethodsEnhanced = true;
        });
      }
    },

    /**
     * Start batch DOM operations to minimize layout thrashing
     */
    startBatchDOMOperations: function () {
      // Temporarily disable layout calculations
      if (document.body.style) {
        document.body.style.display = "none";
        // Force a reflow
        document.body.offsetHeight;
        document.body.style.display = "";
      }
    },

    /**
     * End batch DOM operations and restore normal layout
     */
    endBatchDOMOperations: function () {
      // Force a final reflow to ensure all changes are applied
      requestAnimationFrame(() => {
        document.body.offsetHeight;
      });
    },

    /**
     * Enhance payment method cards with better structure - batched operations
     */
    enhancePaymentMethodCards: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods li");
      const enhancements = [];

      // Collect all enhancement operations first
      $paymentMethods.each(function () {
        const $li = $(this);
        const $label = $li.find("label");
        const $radio = $li.find('input[type="radio"]');
        const $paymentBox = $li.find(".payment_box");

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

            $label.empty();
            $label.append($radio);
            $label.append($paymentContent);

            if ($paymentBox.length) {
              $paymentBox.appendTo($li.find(".payment_method"));
            }
          });
        }
      });

      // Execute all enhancements in a single batch
      enhancements.forEach((enhancement) => enhancement());
    },

    /**
     * Add payment method icons - batched operations
     */
    addPaymentMethodIcons: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods li");
      const iconOperations = [];

      // Collect all icon operations first
      $paymentMethods.each(function () {
        const $li = $(this);
        const $label = $li.find("label");
        const title = $li.find(".payment-method-title").text().toLowerCase();

        if (!$label.find(".payment-method-icon").length) {
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

          iconOperations.push(() => {
            const $icon = $(`<div class="${iconClass}">💳</div>`);
            $label.prepend($icon);
          });
        }
      });

      // Execute all icon operations in a single batch
      iconOperations.forEach((operation) => operation());
    },

    /**
     * Add payment method badges - batched operations
     */
    addPaymentMethodBadges: function () {
      const $paymentMethods = $(".woocommerce-checkout .payment_methods li");
      const badgeOperations = [];

      // Collect all badge operations first
      $paymentMethods.each(function () {
        const $li = $(this);
        const $paymentMethod = $li.find(".payment_method");
        const title = $li.find(".payment-method-title").text().toLowerCase();

        if (!$paymentMethod.find(".payment-method-badge").length) {
          if (title.includes("cash") || title.includes("delivery")) {
            badgeOperations.push(() => {
              $paymentMethod.append(
                '<div class="payment-method-badge recommended">Recommended</div>'
              );
            });
          } else if (
            title.includes("card") ||
            title.includes("credit") ||
            title.includes("debit")
          ) {
            badgeOperations.push(() => {
              $paymentMethod.append(
                '<div class="payment-method-badge secure">Secure</div>'
              );
            });
          }
        }
      });

      // Execute all badge operations in a single batch
      badgeOperations.forEach((operation) => operation());
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

      // Lazy-loaded country prefix manager with reduced memory footprint
      const CountryPrefixManager = {
        cache: new Map(),
        loadedRegions: new Set(),

        // Core regions with most common countries
        corePrefixes: {
          US: "+1",
          CA: "+1",
          GB: "+44",
          DE: "+49",
          FR: "+33",
          IT: "+39",
          ES: "+34",
          NL: "+31",
          BE: "+32",
          CH: "+41",
          AT: "+43",
          SE: "+46",
          NO: "+47",
          DK: "+45",
          FI: "+358",
          PL: "+48",
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
          CY: "+357",
          MT: "+356",
          LU: "+352",
          IS: "+354",
          LI: "+423",
          MC: "+377",
          SM: "+378",
          VA: "+379",
          AD: "+376",
          AL: "+355",
          BA: "+387",
          ME: "+382",
          MK: "+389",
          RS: "+381",
          XK: "+383",
          TR: "+90",
          RU: "+7",
          UA: "+380",
          BY: "+375",
          MD: "+373",
          GE: "+995",
          AM: "+374",
          AZ: "+994",
          KZ: "+7",
          KG: "+996",
          TJ: "+992",
          TM: "+993",
          UZ: "+998",
          MN: "+976",
          CN: "+86",
          JP: "+81",
          KR: "+82",
          TW: "+886",
          HK: "+852",
          MO: "+853",
          SG: "+65",
          MY: "+60",
          TH: "+66",
          VN: "+84",
          LA: "+856",
          KH: "+855",
          MM: "+95",
          PH: "+63",
          ID: "+62",
          BN: "+673",
          TL: "+670",
          AU: "+61",
          NZ: "+64",
          FJ: "+679",
          PG: "+675",
          SB: "+677",
          VU: "+678",
          NC: "+687",
          PF: "+689",
          WF: "+681",
          WS: "+685",
          TO: "+676",
          KI: "+686",
          TV: "+688",
          NR: "+674",
          PW: "+680",
          FM: "+691",
          MH: "+692",
          CK: "+682",
          NU: "+683",
          TK: "+690",
          IN: "+91",
          PK: "+92",
          BD: "+880",
          LK: "+94",
          MV: "+960",
          BT: "+975",
          NP: "+977",
          AF: "+93",
          IR: "+98",
          IQ: "+964",
          SA: "+966",
          AE: "+971",
          IL: "+972",
          JO: "+962",
          LB: "+961",
          SY: "+963",
          PS: "+970",
          KW: "+965",
          QA: "+974",
          BH: "+973",
          OM: "+968",
          YE: "+967",
          EG: "+20",
          LY: "+218",
          TN: "+216",
          DZ: "+213",
          MA: "+212",
          SD: "+249",
          SS: "+211",
          ET: "+251",
          ER: "+291",
          DJ: "+253",
          SO: "+252",
          KE: "+254",
          UG: "+256",
          TZ: "+255",
          RW: "+250",
          BI: "+257",
          MW: "+265",
          ZM: "+260",
          ZW: "+263",
          BW: "+267",
          NA: "+264",
          SZ: "+268",
          LS: "+266",
          ZA: "+27",
          MG: "+261",
          MU: "+230",
          SC: "+248",
          KM: "+269",
          YT: "+262",
          RE: "+262",
          MZ: "+258",
          MW: "+265",
          AO: "+244",
          CD: "+243",
          CG: "+242",
          CF: "+236",
          TD: "+235",
          CM: "+237",
          GQ: "+240",
          GA: "+241",
          ST: "+239",
          CV: "+238",
          GM: "+220",
          GN: "+224",
          GW: "+245",
          SL: "+232",
          LR: "+231",
          CI: "+225",
          GH: "+233",
          TG: "+228",
          BJ: "+229",
          NE: "+227",
          BF: "+226",
          ML: "+223",
          SN: "+221",
          MR: "+222",
          NG: "+234",
          TD: "+235",
          BF: "+226",
          ML: "+223",
          SN: "+221",
          MR: "+222",
          NG: "+234",
          BR: "+55",
          AR: "+54",
          CL: "+56",
          UY: "+598",
          PY: "+595",
          BO: "+591",
          PE: "+51",
          EC: "+593",
          CO: "+57",
          VE: "+58",
          GY: "+592",
          SR: "+597",
          GF: "+594",
          FK: "+500",
          GS: "+500",
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
          VI: "+1340",
          AG: "+1268",
          AI: "+1264",
          VG: "+1284",
          BQ: "+599",
          CW: "+599",
          SX: "+1721",
          KN: "+1869",
          LC: "+1758",
          VC: "+1784",
          GD: "+1473",
          TT: "+1868",
          BB: "+1246",
          BS: "+1242",
          TC: "+1649",
          KY: "+1345",
          BM: "+1441",
          AW: "+297",
          AN: "+599",
          DM: "+1767",
          MS: "+1664",
          GU: "+1671",
          MP: "+1670",
          AS: "+1684",
          UM: "+1",
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

          // For countries not in core set, return null (can be extended later)
          return null;
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
     */
    initCountryBasedFieldHiding: function () {
      const $countrySelect = $("#billing_country");
      const $address2Field = $("#billing_address_2_field");
      const $postcodeField = $("#billing_postcode_field");

      // Countries where address_2 and postcode should be hidden
      const hiddenCountries = ["AL", "XK", "MK"]; // Albania, Kosovo, North Macedonia

      // Function to toggle field visibility
      const toggleFields = function () {
        const selectedCountry = $countrySelect.val();
        const shouldHide = hiddenCountries.includes(selectedCountry);

        if (shouldHide) {
          $address2Field.slideUp(300);
          $postcodeField.slideUp(300);
          // Clear the field values when hiding
          $("#billing_address_2").val("");
          $("#billing_postcode").val("");
        } else {
          $address2Field.slideDown(300);
          $postcodeField.slideDown(300);
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
      $(document.body).on("submit", ".woocommerce-checkout form", function (e) {
        // Show our custom processing indicator
        CheckoutManager.showCustomProcessingIndicator();

        // Prevent the default WooCommerce blockUI overlay
        e.preventDefault();

        // Submit the form via AJAX to avoid the white overlay
        const formData = new FormData(this);
        formData.append("action", "woocommerce_checkout");
        formData.append("security", wc_checkout_params.checkout_nonce);

        $.ajax({
          url: wc_checkout_params.ajax_url,
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          success: function (response) {
            if (response.result === "success") {
              // Redirect to success page
              window.location.href = response.redirect;
            } else {
              // Show errors
              CheckoutManager.hideCustomProcessingIndicator();
              $(".woocommerce-error").remove();
              $(".woocommerce-notices-wrapper").prepend(response.messages);
            }
          },
          error: function () {
            CheckoutManager.hideCustomProcessingIndicator();
            alert(
              "There was an error processing your order. Please try again."
            );
          },
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

      $("body").append(indicator);
      $("body").addClass("checkout-processing");
    },

    /**
     * Hide custom processing indicator
     */
    hideCustomProcessingIndicator: function () {
      $(".checkout-processing-indicator").remove();
      $("body").removeClass("checkout-processing");
    },

    /**
     * Override WooCommerce's blockUI for checkout
     */
    overrideBlockUI: function () {
      // Override the blockUI plugin for checkout pages
      if (typeof $.blockUI !== "undefined") {
        const originalBlockUI = $.blockUI;

        $.blockUI = function (opts) {
          // Only override on checkout pages
          if ($("body").hasClass("woocommerce-checkout")) {
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
    testCountryPrefix: function () {
      console.log("Testing country prefix functionality...");

      const $countrySelect = $("#billing_country");
      const $phoneField = $("#billing_phone");

      console.log("Country select element:", $countrySelect.length);
      console.log("Phone field element:", $phoneField.length);

      if ($countrySelect.length && $phoneField.length) {
        // Test with US
        $countrySelect.val("US");
        $countrySelect.trigger("change");
        console.log("Phone field value after US selection:", $phoneField.val());

        // Test with UK
        setTimeout(() => {
          $countrySelect.val("GB");
          $countrySelect.trigger("change");
          console.log(
            "Phone field value after UK selection:",
            $phoneField.val()
          );
        }, 1000);
      } else {
        console.log("Required elements not found");
      }
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
   * CartManager and CouponManager are already exposed from app.js
   */
  window.CheckoutManager = CheckoutManager;
})(jQuery);
