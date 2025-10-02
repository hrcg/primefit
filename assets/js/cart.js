/**
 * PrimeFit Theme - Cart Module
 * Cart-specific functionality including mini cart, quantity controls, and cart operations
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Ensure CartManager is available (from core.js)
  if (typeof window.CartManager === "undefined") {
    // CartManager not found - core.js must be loaded first
    return;
  }

  // Global variables for auto-close timer management
  let autoCloseTimeout = null;
  let userInteractionTimeout = null;
  
  function startAutoCloseTimer() {
    // Clear any existing timeout
    if (autoCloseTimeout) {
      clearTimeout(autoCloseTimeout);
    }
    
    // Set new timeout for 5 seconds
    autoCloseTimeout = setTimeout(function () {
      closeCart();
    }, 5000);
  }
  
  function cancelAutoClose() {
    if (autoCloseTimeout) {
      clearTimeout(autoCloseTimeout);
      autoCloseTimeout = null;
    }
  }
  
  function restartAutoCloseAfterDelay() {
    // Clear any existing restart timeout
    if (userInteractionTimeout) {
      clearTimeout(userInteractionTimeout);
    }
    
    // Restart auto-close after 2 seconds of no interaction
    userInteractionTimeout = setTimeout(function () {
      startAutoCloseTimer();
    }, 2000);
  }
  
  function cleanupAutoCloseTimers() {
    if (autoCloseTimeout) {
      clearTimeout(autoCloseTimeout);
      autoCloseTimeout = null;
    }
    if (userInteractionTimeout) {
      clearTimeout(userInteractionTimeout);
      userInteractionTimeout = null;
    }
  }

  // Cart functionality
  function getCartContext(clickedEl) {
    const $root = clickedEl
      ? $(clickedEl).closest('[data-behavior="click"]')
      : $('[data-behavior="click"]').first();
    return {
      $wrap: $root,
      $panel: $root.find("#mini-cart-panel"),
      $toggle: $root.find(".cart-toggle"),
    };
  }

  function openCart(clickedEl) {
    const { $wrap, $panel, $toggle } = getCartContext(clickedEl);

    // Check if cart is already open to avoid unnecessary refreshes
    const isAlreadyOpen = $wrap.hasClass("open");

    // Cancel any existing auto-close timers when cart is manually opened
    cleanupAutoCloseTimers();

    // Open cart immediately for better user experience
    openCartPanel($wrap, $panel, $toggle);

    // Only refresh fragments if cart was already open (to update content without user action)
    if (
      isAlreadyOpen &&
      window.primefit_cart_params &&
      window.primefit_cart_params.ajax_url
    ) {
      // Refresh fragments in background after cart is already open
      requestAnimationFrame(() => {
        $.ajax({
          type: "POST",
          url: window.primefit_cart_params.ajax_url,
          data: {
            action: "woocommerce_get_refreshed_fragments",
          },
          success: function (response) {
            if (response && response.fragments) {
              // Update fragments with fresh data
              $.each(response.fragments, function (key, value) {
                $(key).replaceWith(value);
              });
            }
          },
          error: function () {
            // Silently fail - cart is already open and functional
          },
        });
      });
    }
  }

  function openCartPanel($wrap, $panel, $toggle) {
    $wrap.addClass("open").attr("data-open", "true");
    $panel.removeAttr("hidden");
    $toggle.attr("aria-expanded", "true");

    if (window.matchMedia("(max-width: 1024px)").matches) {
      document.body.classList.add("cart-open");

      // Add iOS Safari specific prevention
      addIOSPrevention();
    }

    // Prevent page scrolling when cart is open
    if (typeof window.preventPageScroll === "function") {
      window.preventPageScroll().catch(() => {});
    }

    // Ensure all quantity inputs are properly synced when cart opens
    // Sync quantity inputs with current values (immediate execution)
    requestAnimationFrame(function () {
      $(".woocommerce-mini-cart__item-quantity input[data-cart-item-key]").each(
        function () {
          const $input = $(this);
          const cartItemKey = $input.data("cart-item-key");
          const currentVal = parseInt($input.val()) || 1;

          if (currentVal && cartItemKey) {
            $input.val(currentVal);
            $input.attr("data-original-value", currentVal);
          }
        }
      );
    });
  }

  function addIOSPrevention() {
    // Add iOS Safari specific prevention for pull-to-refresh
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
      const originalContent = viewport.getAttribute("content");
      viewport.setAttribute("content", originalContent + ", user-scalable=no");
      viewport.setAttribute("data-original-content", originalContent);
    }
  }

  function removeIOSPrevention() {
    // Remove iOS Safari specific prevention
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport && viewport.hasAttribute("data-original-content")) {
      const originalContent = viewport.getAttribute("data-original-content");
      viewport.setAttribute("content", originalContent);
      viewport.removeAttribute("data-original-content");
    }
  }

  function closeCart(clickedEl) {
    const { $wrap, $panel, $toggle } = getCartContext(clickedEl);
    $wrap.removeClass("open").attr("data-open", "false");
    $panel.attr("hidden", true);
    $toggle.attr("aria-expanded", "false");

    document.body.classList.remove("cart-open");

    // Remove iOS Safari specific prevention
    removeIOSPrevention();

    // Re-enable page scrolling when cart is closed
    if (typeof window.allowPageScroll === "function") {
      window.allowPageScroll();
    }

    // Reset form submission flag to allow new submissions
    if (
      window.ajaxAddToCartInstance &&
      typeof window.ajaxAddToCartInstance.resetSubmissionFlag === "function"
    ) {
      window.ajaxAddToCartInstance.resetSubmissionFlag();
    }
    
    // Clean up auto-close timers when cart is manually closed
    cleanupAutoCloseTimers();
  }

  // Click-to-open cart drawer
  $(document).on("click", "[data-behavior='click'] .cart-toggle", function (e) {
    e.preventDefault();
    const expanded = $(this).attr("aria-expanded") === "true";
    if (expanded) {
      closeCart(this);
    } else {
      openCart(this);
    }
  });

  // Close cart via close button
  $(document).on("click", ".cart-close", function (e) {
    e.preventDefault();
    closeCart(this);
  });

  // Cart quantity controls - Mini cart increment/decrement
  $(document).on(
    "click",
    ".woocommerce-mini-cart__item-quantity .plus",
    function (e) {
      e.preventDefault();
      const cartItemKey = $(this).data("cart-item-key");
      if (!cartItemKey) {
        return; // Exit early if this isn't a cart item quantity button
      }

      const $input = $(this).siblings("input");
      const currentQty = parseInt($input.val());
      const maxQty = parseInt($input.attr("max"));

      if (currentQty < maxQty) {
        // Add loading state
        $(this).addClass("loading").prop("disabled", true);
        $input.prop("disabled", true);
        updateCartQuantity(cartItemKey, currentQty + 1, $(this));
      }
    }
  );

  $(document).on(
    "click",
    ".woocommerce-mini-cart__item-quantity .minus",
    function (e) {
      e.preventDefault();
      const cartItemKey = $(this).data("cart-item-key");
      if (!cartItemKey) {
        return; // Exit early if this isn't a cart item quantity button
      }

      const $input = $(this).siblings("input");
      const currentQty = parseInt($input.val());

      if (currentQty > 1) {
        // Add loading state
        $(this).addClass("loading").prop("disabled", true);
        $input.prop("disabled", true);
        updateCartQuantity(cartItemKey, currentQty - 1, $(this));
      }
    }
  );

  $(document).on(
    "change",
    ".woocommerce-mini-cart__item-quantity input",
    function (e) {
      // Only handle cart quantity inputs that have cart-item-key data
      const cartItemKey = $(this).data("cart-item-key");
      if (!cartItemKey) {
        return; // Exit early if this isn't a cart item quantity input
      }

      // Skip processing if this input is currently being updated via AJAX
      if ($(this).hasClass("loading") || $(this).prop("disabled")) {
        return;
      }

      const newQty = parseInt($(this).val());
      const maxQty = parseInt($(this).attr("max"));
      const originalValue = parseInt($(this).data("original-value"));

      // Validate the new quantity
      if (isNaN(newQty) || newQty < 1) {
        $(this).val(originalValue || 1);
        return;
      }

      if (newQty > maxQty) {
        $(this).val(maxQty);
        return;
      }

      // Only update if the quantity actually changed
      if (newQty !== originalValue) {
        // Add loading state to input
        $(this).addClass("loading").prop("disabled", true);
        updateCartQuantity(cartItemKey, newQty, $(this));
      }
    }
  );

  // Handle quantity input focus to store the current value
  $(document).on(
    "focus",
    ".woocommerce-mini-cart__item-quantity input",
    function (e) {
      // Only handle cart quantity inputs that have cart-item-key data
      const cartItemKey = $(this).data("cart-item-key");
      if (!cartItemKey) {
        return;
      }

      // Store the current value as the focus value for comparison later
      $(this).data("focus-value", $(this).val());
    }
  );

  // Handle quantity input blur to trigger update if value changed
  $(document).on(
    "blur",
    ".woocommerce-mini-cart__item-quantity input",
    function (e) {
      // Only handle cart quantity inputs that have cart-item-key data
      const cartItemKey = $(this).data("cart-item-key");
      if (!cartItemKey) {
        return;
      }

      const focusValue = parseInt($(this).data("focus-value")) || 1;
      const currentValue = parseInt($(this).val()) || 1;

      // If value changed during focus, trigger change event
      if (focusValue !== currentValue) {
        $(this).trigger("change");
      }
    }
  );

  // Remove item from cart: handle both WooCommerce core and custom handlers
  $(document).ready(function () {
    // Let WooCommerce core handle default .remove_from_cart_button clicks
    // (prevents duplicate AJAX requests and intermittent errors)

    // Keep fallback for custom remove buttons
    $(document).off(
      "click.primefit-cart",
      ".woocommerce-mini-cart__item-remove"
    );
    $(document).on(
      "click.primefit-cart",
      ".woocommerce-mini-cart__item-remove[href='#']",
      function (e) {
        e.preventDefault();
        const $btn = $(this);
        const cartItemKey = $btn.data("cart-item-key");
        $btn.addClass("loading").prop("disabled", true);
        removeCartItem(cartItemKey, $btn);
      }
    );
  });

  // Update cart quantity via AJAX
  function updateCartQuantity(cartItemKey, quantity, $element) {
    // Validate parameters
    if (!cartItemKey || !quantity || !window.primefit_cart_params) {
      if ($element) {
        $element.removeClass("loading").prop("disabled", false);
      }
      return;
    }

    $.ajax({
      type: "POST",
      url: primefit_cart_params.ajax_url,
      data: {
        action: "wc_ajax_update_cart_item_quantity",
        cart_item_key: cartItemKey,
        quantity: quantity,
        security: primefit_cart_params.update_cart_nonce,
      },
      success: function (response) {
        if (response.success) {
          // Update cart fragments
          if (response.data && response.data.fragments) {
            $.each(response.data.fragments, function (key, value) {
              $(key).replaceWith(value);
            });

            // After fragments are updated, ensure all quantity inputs have proper data attributes
            // Use a longer timeout to ensure DOM is fully updated
            setTimeout(function () {
              $(
                ".woocommerce-mini-cart__item-quantity input[data-cart-item-key]"
              ).each(function () {
                const $input = $(this);
                const cartItemKey = $input.data("cart-item-key");

                // Get the actual quantity from the server response or cart data
                let actualQuantity = response.data.updated_quantity;

                // If this is the item we just updated, use the updated quantity
                if (cartItemKey === response.data.cart_item_key) {
                  actualQuantity = response.data.updated_quantity;
                } else {
                  // For other items, try to get quantity from the input value
                  actualQuantity = parseInt($input.val()) || 1;
                }

                // Update both the input value and the data attribute
                if (actualQuantity && cartItemKey) {
                  $input.val(actualQuantity);
                  $input.attr("data-original-value", actualQuantity);
                }
              });
            }, 100);
          }

          // Trigger WooCommerce cart update events using unified CartManager
          CartManager.queueRefresh("update_checkout");
          CartManager.queueRefresh("wc_fragment_refresh");

          // Check if cart is empty after quantity update
          setTimeout(function () {
            checkAndShowEmptyCartState();
          }, 100);
        } else {
          // Fallback: reload page if AJAX fails
          if (
            response.data &&
            response.data.includes("Security check failed")
          ) {
            window.location.reload();
          }
        }
      },
      error: function (xhr, status, error) {
        // Fallback: reload page on critical errors
        if (xhr.status === 403 || xhr.status === 500) {
          window.location.reload();
        }
      },
      complete: function () {
        // Remove loading state - but don't try to re-enable elements after fragments update
        // The fragments replace the entire mini-cart HTML, so the element references become stale
        // This is handled by the fragment update mechanism instead
      },
    });
  }

  // Remove cart item via AJAX
  function removeCartItem(cartItemKey, $element) {
    // Validate parameters
    if (!cartItemKey || !window.primefit_cart_params) {
      if ($element) {
        $element.removeClass("loading").prop("disabled", false);
      }
      return;
    }

    // Find the cart item element for animation
    const $cartItem = $(
      `.remove_from_cart_button[data-cart_item_key="${cartItemKey}"]`
    ).closest(".woocommerce-mini-cart-item");

    // Add loading state to the remove button
    $element.addClass("loading").prop("disabled", true);

    // Validate we have the required parameters
    if (!primefit_cart_params.ajax_url) {
      // Configuration error: No AJAX URL
      return;
    }

    if (!primefit_cart_params.remove_cart_nonce) {
      // Configuration error: No security nonce
      return;
    }

    const ajaxData = {
      action: "wc_ajax_remove_cart_item",
      cart_item_key: cartItemKey,
      security: primefit_cart_params.remove_cart_nonce,
    };

    $.ajax({
      type: "POST",
      url: primefit_cart_params.ajax_url,
      data: ajaxData,
      success: function (response) {
        if (response.success) {
          // Start fade-out animation immediately
          if ($cartItem.length) {
            $cartItem.addClass("removing");
          }

          // Update cart fragments - these should now reflect the item being removed
          if (response.data && response.data.fragments) {
            $.each(response.data.fragments, function (key, value) {
              $(key).replaceWith(value);
            });

            // After fragments are updated, ensure all quantity inputs have proper data attributes
            setTimeout(function () {
              $(
                ".woocommerce-mini-cart__item-quantity input[data-cart-item-key]"
              ).each(function () {
                const $input = $(this);
                const cartItemKey = $input.data("cart-item-key");
                const currentVal = parseInt($input.val()) || 1;

                // Update both the input value and the data attribute
                if (currentVal && cartItemKey) {
                  $input.val(currentVal);
                  $input.attr("data-original-value", currentVal);
                }
              });
            }, 100);

            // Check cart state using server data
            setTimeout(function () {
              if (
                response.data.cart_is_empty === true ||
                response.data.cart_contents_count === 0
              ) {
                showEmptyCartState();
              } else {
                hideEmptyCartState();
              }
            }, 50);
          }
        } else {
          // Remove loading state and fade class on error
          $element.removeClass("loading").prop("disabled", false);
          if ($cartItem.length) {
            $cartItem.removeClass("removing");
          }

          // Show specific error message
          let errorMessage = "Failed to remove item from cart. ";
          if (response.data) {
            errorMessage += "Error: " + response.data;
          }
          errorMessage += " Please check browser console for details.";

          // Cart removal failed
          
          // Show error in cart if possible
          const $cartPanel = $("#mini-cart-panel");
          if ($cartPanel.length) {
            const $errorDiv = $('<div class="cart-error-message" style="background: #ff4444; color: white; padding: 10px; margin: 10px; border-radius: 4px; text-align: center;">Unable to remove item. Please try again.</div>');
            $cartPanel.prepend($errorDiv);
            setTimeout(() => $errorDiv.fadeOut(300, () => $errorDiv.remove()), 3000);
          }

          // Fallback: reload page if AJAX fails
          if (
            response.data &&
            typeof response.data === "string" &&
            response.data.includes("Security check failed")
          ) {
            window.location.reload();
          }
        }

        // Trigger WooCommerce cart update events using unified CartManager
        CartManager.queueRefresh("update_checkout");
        CartManager.queueRefresh("wc_fragment_refresh");
      },
      error: function (xhr, status, error) {
        // Remove loading state and fade class on error
        $element.removeClass("loading").prop("disabled", false);
        if ($cartItem.length) {
          $cartItem.removeClass("removing");
        }

        // Show user-friendly error message instead of alert
        const errorMessage = "Unable to remove item. Please try again.";
        
        // Try to show error in cart if possible, otherwise use console
        if (typeof window.showCartNotification === "function") {
          window.showCartNotification(errorMessage, "error");
        } else {
          // Cart removal failed
          // Fallback: show a temporary message in the cart
          const $cartPanel = $("#mini-cart-panel");
          if ($cartPanel.length) {
            const $errorDiv = $('<div class="cart-error-message" style="background: #ff4444; color: white; padding: 10px; margin: 10px; border-radius: 4px; text-align: center;">' + errorMessage + '</div>');
            $cartPanel.prepend($errorDiv);
            setTimeout(() => $errorDiv.fadeOut(300, () => $errorDiv.remove()), 3000);
          }
        }

        // Fallback: reload page on critical errors
        if (xhr.status === 403 || xhr.status === 500) {
          window.location.reload();
        }
      },
      complete: function () {
        // Remove loading state
        if ($element) {
          $element.removeClass("loading").prop("disabled", false);
        }
      },
    });
  }

  // Function to check and show empty cart state if needed
  function checkAndShowEmptyCartState() {
    const $cartItems = $(".woocommerce-mini-cart__items");
    const $cartContent = $(".cart-panel-content");

    // Check multiple indicators to ensure cart is truly empty
    const hasCartItemsContainer = $cartItems.length > 0;
    const cartItemsCount = hasCartItemsContainer
      ? $cartItems.find("li.woocommerce-mini-cart__item").length
      : 0;
    const hasEmptyMessage =
      $(".woocommerce-mini-cart__empty-message").length > 0;

    // If no cart items container exists OR cart items container is empty
    if (!hasCartItemsContainer || cartItemsCount === 0) {
      showEmptyCartState();
    } else {
      hideEmptyCartState();
    }
  }

  // Close when clicking overlay
  $(document).on("click", ".cart-overlay", function (e) {
    e.preventDefault();
    closeCart(this);
  });

  // Close when clicking outside (but not on overlay, as that's handled above)
  $(document).on("click", function (e) {
    const $target = $(e.target);
    const $cartWrap = $("[data-behavior='click']").first();
    if (
      $cartWrap.attr("data-open") === "true" &&
      !$target.closest("[data-behavior='click']").length &&
      !$target.hasClass("cart-overlay") &&
      e.type === "click"
    ) {
      closeCart();
    }
  });

  // Auto-open mini cart when product is added to cart
  $(document).on(
    "added_to_cart",
    function (event, fragments, cart_hash, $button) {
      // Update fragments if provided
      if (fragments) {
        $.each(fragments, function (key, value) {
          $(key).replaceWith(value);
        });

        // Force a more aggressive quantity sync after adding to cart
        setTimeout(function () {
          // First, try to get fresh cart fragments to ensure we have the latest data
          if (
            window.primefit_cart_params &&
            window.primefit_cart_params.ajax_url
          ) {
            $.ajax({
              type: "POST",
              url: window.primefit_cart_params.ajax_url,
              data: {
                action: "woocommerce_get_refreshed_fragments",
              },
              success: function (response) {
                if (response && response.fragments) {
                  // Update fragments with fresh data
                  $.each(response.fragments, function (key, value) {
                    $(key).replaceWith(value);
                  });

                  // Now sync all quantity inputs with the fresh data
                  setTimeout(function () {
                    $(
                      ".woocommerce-mini-cart__item-quantity input[data-cart-item-key]"
                    ).each(function () {
                      const $input = $(this);
                      const cartItemKey = $input.data("cart-item-key");
                      const currentVal = parseInt($input.val()) || 1;

                      // Update both the input value and the data attribute
                      if (currentVal && cartItemKey) {
                        $input.val(currentVal);
                        $input.attr("data-original-value", currentVal);
                      }
                    });
                  }, 50);
                }
              },
              error: function () {
                // Fallback: sync with current values if AJAX fails
                $(
                  ".woocommerce-mini-cart__item-quantity input[data-cart-item-key]"
                ).each(function () {
                  const $input = $(this);
                  const cartItemKey = $input.data("cart-item-key");
                  const currentVal = parseInt($input.val()) || 1;

                  if (currentVal && cartItemKey) {
                    $input.val(currentVal);
                    $input.attr("data-original-value", currentVal);
                  }
                });
              },
            });
          } else {
            // Fallback if no AJAX params available
            $(
              ".woocommerce-mini-cart__item-quantity input[data-cart-item-key]"
            ).each(function () {
              const $input = $(this);
              const cartItemKey = $input.data("cart-item-key");
              const currentVal = parseInt($input.val()) || 1;

              if (currentVal && cartItemKey) {
                $input.val(currentVal);
                $input.attr("data-original-value", currentVal);
              }
            });
          }
        }, 150);
      }

      // Check cart state and hide empty message if needed
      setTimeout(function () {
        checkAndShowEmptyCartState();
      }, 100);

      // Open cart immediately
      openCart();

      // Start the initial auto-close timer
      startAutoCloseTimer();
    }
  );

  // Detect user interaction with cart form elements to prevent auto-close
  $(document).on('focus', '#mini-cart-panel input, #mini-cart-panel textarea, #mini-cart-panel select', function() {
    cancelAutoClose();
  });
  
  $(document).on('input', '#mini-cart-panel input, #mini-cart-panel textarea, #mini-cart-panel select', function() {
    cancelAutoClose();
  });
  
  $(document).on('keydown', '#mini-cart-panel input, #mini-cart-panel textarea, #mini-cart-panel select', function() {
    cancelAutoClose();
  });
  
  $(document).on('keyup', '#mini-cart-panel input, #mini-cart-panel textarea, #mini-cart-panel select', function() {
    cancelAutoClose();
  });
  
  // Detect when user stops interacting
  $(document).on('blur', '#mini-cart-panel input, #mini-cart-panel textarea, #mini-cart-panel select', function() {
    restartAutoCloseAfterDelay();
  });

  // Function to show empty cart state
  function showEmptyCartState() {
    const $cartItems = $(".woocommerce-mini-cart__items");
    const $cartTotal = $(".woocommerce-mini-cart__total");
    const $cartButtons = $(".woocommerce-mini-cart__buttons");
    const $cartRecommendations = $(".cart-recommendations");
    const $cartCheckoutSummary = $(".cart-checkout-summary");
    const $emptyMessage = $(".woocommerce-mini-cart__empty-message");
    const $customEmptyCart = $(".pf-mini-cart-empty");

    // Hide all cart content
    if ($cartItems.length) $cartItems.hide();
    if ($cartTotal.length) $cartTotal.hide();
    if ($cartButtons.length) $cartButtons.hide();
    if ($cartRecommendations.length) $cartRecommendations.hide();
    if ($cartCheckoutSummary.length) $cartCheckoutSummary.hide();

    // Show empty message (either default or custom)
    if ($emptyMessage.length) {
      $emptyMessage.show();
    } else if ($customEmptyCart.length) {
      $customEmptyCart.show();
    }
  }

  // Function to hide empty cart state
  function hideEmptyCartState() {
    const $cartItems = $(".woocommerce-mini-cart__items");
    const $cartTotal = $(".woocommerce-mini-cart__total");
    const $cartButtons = $(".woocommerce-mini-cart__buttons");
    const $cartRecommendations = $(".cart-recommendations");
    const $cartCheckoutSummary = $(".cart-checkout-summary");
    const $emptyMessage = $(".woocommerce-mini-cart__empty-message");
    const $customEmptyCart = $(".pf-mini-cart-empty");

    // Show all cart content if it exists
    if ($cartItems.length) $cartItems.show();
    if ($cartTotal.length) $cartTotal.show();
    if ($cartButtons.length) $cartButtons.show();
    if ($cartRecommendations.length) $cartRecommendations.show();
    if ($cartCheckoutSummary.length) $cartCheckoutSummary.show();

    // Hide empty message (both default and custom)
    if ($emptyMessage.length) $emptyMessage.hide();
    if ($customEmptyCart.length) $customEmptyCart.hide();
  }

  // Mini Cart Enhancements

  // Handle coupon form submission in mini cart - now uses unified CouponManager
  $(document).on("submit", ".mini-cart-coupon-form", function (e) {
    e.preventDefault();

    const $form = $(this);
    const $input = $form.find(".coupon-code-input");
    const couponCode = $input.val().trim();

    if (!couponCode) {
      return;
    }

    // Use unified CouponManager
    if (typeof window.CouponManager !== "undefined") {
      window.CouponManager.applyCoupon(couponCode, { $form });
    }
  });

  // Handle coupon removal - now uses unified CouponManager
  $(document).on("click", ".remove-coupon", function (e) {
    e.preventDefault();

    const $button = $(this);
    const couponCode = $button.data("coupon");

    if (!couponCode) {
      return;
    }

    // Show loading state
    $button.addClass("loading").prop("disabled", true);

    // Remove coupon via AJAX
    $.ajax({
      type: "POST",
      url: primefit_cart_params.ajax_url,
      data: {
        action: "remove_coupon",
        security: primefit_cart_params.remove_coupon_nonce,
        coupon: couponCode,
      },
      success: function (response) {
        if (response.success) {
          // Update fragments directly if available
          var frags = (response && response.fragments) || (response && response.data && response.data.fragments);
          if (frags) {
            $.each(frags, function (key, value) {
              $(key).replaceWith(value);
            });
          }
          
          // Refresh cart fragments using unified CartManager
          CartManager.queueRefresh("update_checkout");
          CartManager.queueRefresh("wc_fragment_refresh");
        }
      },
      error: function () {
        // Remove loading state on error
        $button.removeClass("loading").prop("disabled", false);
      },
      complete: function () {
        // Remove loading state
        $button.removeClass("loading").prop("disabled", false);
      },
    });
  });

  // Expose cart functions globally
  window.openCart = openCart;
  window.closeCart = closeCart;
})(jQuery);
