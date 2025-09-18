/**
 * PrimeFit Theme - Checkout JavaScript
 * Basic checkout functionality for WooCommerce
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Basic Checkout Enhancement
   */
  const CheckoutManager = {
    init: function () {
      // Only add basic enhancements that don't interfere with WooCommerce
      this.improveFormUsability();
      this.initCouponToggle();
      this.initCountryStateHandler();
      this.initSummaryToggle();
      this.initCartRemovalHandler();
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
     * Initialize coupon toggle functionality
     */
    initCouponToggle: function () {
      const $couponToggle = $(".coupon-toggle");
      const $couponSection = $(".coupon-section");

      if ($couponToggle.length) {
        // Create coupon form HTML
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

        // Handle coupon application
        $couponSection.on("click", ".coupon-apply-btn", function (e) {
          e.preventDefault();
          const couponCode = $couponSection.find(".coupon-input").val().trim();

          if (couponCode) {
            CheckoutManager.applyCoupon(couponCode);
          }
        });

        // Handle Enter key in coupon input
        $couponSection.on("keypress", ".coupon-input", function (e) {
          if (e.which === 13) {
            // Enter key
            e.preventDefault();
            const couponCode = $(this).val().trim();

            if (couponCode) {
              CheckoutManager.applyCoupon(couponCode);
            }
          }
        });
      }
    },

    /**
     * Apply coupon code
     */
    applyCoupon: function (couponCode) {
      const $couponSection = $(".coupon-section");
      const $applyBtn = $couponSection.find(".coupon-apply-btn");
      const $input = $couponSection.find(".coupon-input");

      // Show loading state
      $applyBtn.text("Applying...").prop("disabled", true);

      // Use WooCommerce AJAX to apply coupon
      $.ajax({
        type: "POST",
        url: wc_checkout_params.ajax_url,
        data: {
          action: "woocommerce_apply_coupon",
          security: wc_checkout_params.apply_coupon_nonce,
          coupon_code: couponCode,
        },
        success: function (response) {
          if (response.success) {
            // Reload checkout to update totals
            $("body").trigger("update_checkout");
            $input.val(""); // Clear input
          } else {
            // Show error message
            alert(response.data || "Invalid coupon code");
          }
        },
        error: function () {
          alert("Error applying coupon. Please try again.");
        },
        complete: function () {
          $applyBtn.text("Apply").prop("disabled", false);
        },
      });
    },

    /**
     * Initialize country-state handler for dynamic state dropdown
     */
    initCountryStateHandler: function () {
      const $countrySelect = $("#billing_country");
      const $stateSelect = $("#billing_state");

      if ($countrySelect.length && $stateSelect.length) {
        $countrySelect.on("change", function () {
          const countryCode = $(this).val();

          // Clear current state options
          $stateSelect.empty();
          $stateSelect.append('<option value="">County</option>');

          if (countryCode) {
            // Use WooCommerce AJAX to get states for selected country
            $.ajax({
              type: "POST",
              url: wc_checkout_params.ajax_url,
              data: {
                action: "woocommerce_get_states",
                country: countryCode,
              },
              success: function (response) {
                if (response && response.length > 0) {
                  $.each(response, function (code, name) {
                    $stateSelect.append(
                      '<option value="' + code + '">' + name + "</option>"
                    );
                  });
                }
              },
            });
          }
        });
      }
    },

    /**
     * Initialize summary toggle for mobile
     */
    initSummaryToggle: function () {
      const $toggle = $(".summary-toggle");
      const $content = $(".summary-content");
      const $header = $(".summary-header");

      if ($toggle.length && $content.length && $header.length) {
        // Set initial state - collapsed on mobile
        if (window.innerWidth <= 1024) {
          $toggle.addClass("collapsed");
          $content.addClass("collapsed");
        }

        // Toggle functionality - click anywhere on header
        $header.on("click", function (e) {
          // Prevent event bubbling if clicking the toggle button specifically
          if (
            $(e.target).hasClass("summary-toggle") ||
            $(e.target).closest(".summary-toggle").length
          ) {
            return;
          }

          const $summaryContent = $(".summary-content");

          // Toggle both ways - expand if collapsed, collapse if expanded
          $toggle.toggleClass("collapsed");
          $summaryContent.toggleClass("collapsed");
        });

        // Toggle button functionality (for explicit toggle)
        $toggle.on("click", function (e) {
          e.stopPropagation(); // Prevent header click
          const $this = $(this);
          const $summaryContent = $(".summary-content");

          $this.toggleClass("collapsed");
          $summaryContent.toggleClass("collapsed");
        });

        // Handle window resize
        $(window).on("resize", function () {
          if (window.innerWidth > 1024) {
            // Desktop - always show content
            $toggle.removeClass("collapsed");
            $content.removeClass("collapsed");
          } else {
            // Mobile - maintain current state or default to collapsed
            if (
              !$toggle.hasClass("collapsed") &&
              !$content.hasClass("collapsed")
            ) {
              // If currently expanded, keep it expanded
              return;
            }
            // Otherwise default to collapsed
            $toggle.addClass("collapsed");
            $content.addClass("collapsed");
          }
        });
      }
    },

    /**
     * Initialize cart removal handler for checkout page
     */
    initCartRemovalHandler: function () {
      // Check for server-side redirect flag on page load
      if (
        typeof wc_checkout_params !== "undefined" &&
        wc_checkout_params.should_redirect
      ) {
        CheckoutManager.redirectToShop();
        return;
      }

      // Listen for WooCommerce cart removal events
      $(document).on(
        "removed_from_cart",
        function (event, fragments, cart_hash, button) {
          console.log("CART DEBUG: removed_from_cart event triggered");
          CheckoutManager.checkCartAndRedirect();
        }
      );

      // Listen for cart updates
      $(document).on("updated_cart_totals", function () {
        console.log("CART DEBUG: updated_cart_totals event triggered");
        CheckoutManager.checkCartAndRedirect();
      });

      // Listen for checkout updates
      $(document).on("updated_checkout", function () {
        console.log("CART DEBUG: updated_checkout event triggered");
        CheckoutManager.checkCartAndRedirect();
      });

      // Monitor all AJAX responses for cart-related actions
      $(document).ajaxSuccess(function (event, xhr, settings) {
        // Check if this is any cart-related request
        if (
          settings.data &&
          (settings.data.indexOf("wc_ajax_remove_cart_item") !== -1 ||
            settings.data.indexOf("remove_cart_item") !== -1 ||
            settings.data.indexOf("update_cart") !== -1)
        ) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.success && response.data) {
              // Check if cart is empty from response
              if (
                response.data.cart_is_empty ||
                response.data.cart_contents_count === 0
              ) {
                console.log("CART DEBUG: Cart is empty from AJAX response");
                CheckoutManager.redirectToShop(response.data.shop_url);
              }
            }
          } catch (e) {
            // Ignore JSON parse errors
          }
        }
      });

      // Also check cart state periodically (fallback)
      setInterval(function () {
        CheckoutManager.checkCartAndRedirect();
      }, 2000);
    },

    /**
     * Check cart state and redirect if empty
     */
    checkCartAndRedirect: function () {
      // Check if cart items exist in DOM
      const cartItems = $(
        ".order-item, .woocommerce-mini-cart__item, .cart_item"
      );
      if (cartItems.length === 0) {
        console.log(
          "CART DEBUG: No cart items found in DOM, redirecting to shop"
        );
        CheckoutManager.redirectToShop();
      }
    },

    /**
     * Redirect to shop page
     */
    redirectToShop: function (shopUrl) {
      const url =
        shopUrl ||
        (typeof wc_checkout_params !== "undefined"
          ? wc_checkout_params.shop_url
          : "/shop/");

      // Redirect immediately
      window.location.href = url;
    },
  };

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    // Only initialize on checkout page
    if ($("body").hasClass("woocommerce-checkout")) {
      CheckoutManager.init();
    }
  });

  /**
   * Expose CheckoutManager globally for debugging
   */
  window.CheckoutManager = CheckoutManager;
})(jQuery);
