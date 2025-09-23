(function ($) {
  "use strict";

  // Prevent accidental re-adding product on refresh when URL has add-to-cart params
  // and handle URL coupon detection
  $(function () {
    // Critical initialization - runs immediately
    // URL parameter cleanup
    try {
      var url = new URL(window.location.href);

      // Handle add-to-cart URL cleanup
      if (
        url.searchParams.has("add-to-cart") ||
        url.searchParams.has("added-to-cart")
      ) {
        // Intervene on all pages except cart/checkout to avoid interfering with notices
        var isCartPage = document.body.classList.contains("woocommerce-cart");
        var isCheckoutPage = document.body.classList.contains(
          "woocommerce-checkout"
        );
        if (!isCartPage && !isCheckoutPage) {
          url.searchParams.delete("add-to-cart");
          url.searchParams.delete("added-to-cart");
          url.searchParams.delete("quantity");
          var newSearch = url.searchParams.toString();
          var newUrl =
            url.pathname + (newSearch ? "?" + newSearch : "") + url.hash;
          window.history.replaceState({}, "", newUrl);
        }
      }

      // Handle URL coupon detection and application
      if (url.searchParams.has("coupon")) {
        var couponCode = url.searchParams.get("coupon");

        if (couponCode && couponCode.trim()) {
          console.log("🎫 Found coupon in URL:", couponCode);

          // Check if we're on checkout page - let checkout.js handle it
          if (isCheckoutPage) {
            console.log(
              "Checkout page detected - letting checkout.js handle coupon"
            );
            return;
          }

          // Check if coupon is already applied
          var appliedCoupons = getAppliedCoupons();
          if (appliedCoupons.includes(couponCode.toUpperCase())) {
            console.log("✅ Coupon " + couponCode + " is already applied");
            return;
          }

          // Apply the coupon with a slight delay to ensure DOM is ready
          setTimeout(function () {
            applyCouponFromUrl(couponCode.trim());
          }, 500);
        }
      } else {
        // Check for pending coupon from session (base URL case)
        checkForPendingCouponFromSession();
      }
    } catch (e) {
      // Ignore if URL API not available
    }

    // Non-critical initialization - deferred to next animation frame
    requestAnimationFrame(function () {
      initSmartLazyLoading();
      initImageQualityPreferences();
      initConnectionAwareImageLoading();
    });
  });

  // Function to get currently applied coupons
  function getAppliedCoupons() {
    var appliedCoupons = [];

    // Check WooCommerce's applied coupons
    if (
      typeof wc_add_to_cart_params !== "undefined" &&
      wc_add_to_cart_params.applied_coupons
    ) {
      appliedCoupons.push.apply(
        appliedCoupons,
        wc_add_to_cart_params.applied_coupons
      );
    }

    // Also check from cart data if available
    if (
      window.wc_cart_fragments_params &&
      window.wc_cart_fragments_params.cart_hash
    ) {
      // Try to get from any visible coupon displays
      jQuery(
        ".applied-coupon .coupon-code, .woocommerce-notices-wrapper .coupon-code"
      ).each(function () {
        var code = jQuery(this).text().trim();
        if (code && appliedCoupons.indexOf(code) === -1) {
          appliedCoupons.push(code);
        }
      });
    }

    return appliedCoupons.map(function (code) {
      return code.toUpperCase();
    });
  }

  // Smart lazy loading with intersection observer
  function initSmartLazyLoading() {
    // Check if browser supports Intersection Observer
    if ("IntersectionObserver" in window) {
      var lazyImages = document.querySelectorAll('img[loading="lazy"]');
      var imageObserver = new IntersectionObserver(
        function (entries, observer) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              var img = entry.target;

              // Check if user has requested reduced data usage
              var reducedData =
                window.matchMedia &&
                window.matchMedia("(prefers-reduced-data: reduce)").matches;

              if (reducedData) {
                // Skip loading if user prefers reduced data
                img.classList.add("reduced-data");
                observer.unobserve(img);
                return;
              }

              // Add loading class for smooth transition
              img.classList.add("loading");

              // For images without data-src, just mark as loaded immediately
              // since native lazy loading will handle the actual loading
              if (img.dataset.src) {
                // Preload the image
                var newImg = new Image();
                newImg.onload = function () {
                  img.src = img.dataset.src;
                  img.classList.remove("loading");
                  img.classList.add("loaded");
                };
                newImg.onerror = function () {
                  img.classList.remove("loading");
                  img.classList.add("error");
                };
                newImg.src = img.dataset.src;
              } else {
                // For native lazy loading, just add loaded class
                img.classList.remove("loading");
                img.classList.add("loaded");
              }

              observer.unobserve(img);
            }
          });
        },
        {
          rootMargin: "50px 0px", // Load images 50px before they come into view
          threshold: 0.01,
        }
      );

      lazyImages.forEach(function (img) {
        imageObserver.observe(img);
      });
    }
  }

  // Initialize image quality preferences
  function initImageQualityPreferences() {
    // Check for user's data preference
    if (window.matchMedia) {
      var reducedDataQuery = window.matchMedia(
        "(prefers-reduced-data: reduce)"
      );

      // Set initial preference based on system settings
      if (reducedDataQuery.matches) {
        document.documentElement.classList.add("reduced-data-mode");
      }

      // Listen for changes
      reducedDataQuery.addListener(function (e) {
        if (e.matches) {
          document.documentElement.classList.add("reduced-data-mode");
        } else {
          document.documentElement.classList.remove("reduced-data-mode");
        }
      });
    }

    // Check for save data header
    if (navigator.connection && navigator.connection.saveData) {
      document.documentElement.classList.add("save-data-mode");
    }
  }

  // Connection-aware image loading
  function initConnectionAwareImageLoading() {
    if ("connection" in navigator) {
      var connection = navigator.connection;

      // Adjust image loading based on connection type
      if (
        connection.effectiveType === "slow-2g" ||
        connection.effectiveType === "2g"
      ) {
        document.documentElement.classList.add("slow-connection");
        // Reduce image quality for slow connections
        var style = document.createElement("style");
        style.textContent = `
          .slow-connection img[loading="lazy"] {
            opacity: 0.7;
            filter: blur(1px);
            transition: all 0.5s ease;
          }
          .slow-connection img[loading="lazy"].loaded {
            opacity: 1;
            filter: none;
          }
        `;
        document.head.appendChild(style);
      }

      // Listen for connection changes
      connection.addEventListener("change", function () {
        if (
          connection.effectiveType === "slow-2g" ||
          connection.effectiveType === "2g"
        ) {
          document.documentElement.classList.add("slow-connection");
        } else {
          document.documentElement.classList.remove("slow-connection");
        }
      });
    }
  }

  // Function to apply coupon from URL parameter - optimized with better error handling
  // FIXED: Added state management to prevent race conditions
  function applyCouponFromUrl(couponCode) {
    console.log("🚀 Applying coupon from URL:", couponCode);

    // Check if coupon is already applied
    var appliedCoupons = getAppliedCoupons();
    if (appliedCoupons.includes(couponCode.toUpperCase())) {
      console.log("✅ Coupon " + couponCode + " is already applied");
      // Clear processing flag since we're done
      delete window.primefitCouponProcessing;
      return;
    }

    // Show loading state if mini cart coupon form exists
    var $couponForm = jQuery(".mini-cart-coupon-form");
    if ($couponForm.length) {
      var $input = $couponForm.find(".coupon-code-input");
      var $button = $couponForm.find(".apply-coupon-btn");

      if ($input.length && $button.length) {
        $input.val("Loading...");
        $button.addClass("loading").prop("disabled", true).text("Applying...");
      }
    }

    // Apply coupon via AJAX using existing handler with improved error handling
    // FIXED: Added request abortion and better error recovery
    var ajaxRequest = jQuery.ajax({
      type: "POST",
      url: window.primefit_cart_params
        ? window.primefit_cart_params.ajax_url
        : "/wp-admin/admin-ajax.php",
      data: {
        action: "apply_coupon",
        security: window.primefit_cart_params
          ? window.primefit_cart_params.apply_coupon_nonce
          : "",
        coupon_code: couponCode,
      },
      timeout: 8000, // Reduced timeout to 8 seconds for better UX
      xhr: function() {
        // Enable request abortion
        var xhr = jQuery.ajaxSettings.xhr();
        if (xhr) {
          // Store reference for potential abortion
          ajaxRequest.abortController = { xhr: xhr };
        }
        return xhr;
      },
      beforeSend: function(jqXHR, settings) {
        // Store request reference for cleanup
        ajaxRequest.jqXHR = jqXHR;
      },
      success: function (response) {
        if (response.success) {
          console.log("✅ Coupon applied successfully from URL");

          // Clear loading state
          if ($couponForm.length) {
            $input.val("");
            $button
              .removeClass("loading")
              .prop("disabled", false)
              .text("APPLY");
          }

          // Refresh cart fragments efficiently
          jQuery(document.body).trigger("update_checkout");
          jQuery(document.body).trigger("wc_fragment_refresh");

          // Show success message
          if ($couponForm.length) {
            $couponForm.after(
              '<div class="coupon-message success">Coupon applied successfully!</div>'
            );

            setTimeout(function () {
              jQuery(".coupon-message").fadeOut();
            }, 3000);
          }
        } else {
          console.log("❌ Failed to apply coupon from URL:", response.data);

          // Clear loading state
          if ($couponForm.length) {
            $input.val("");
            $button
              .removeClass("loading")
              .prop("disabled", false)
              .text("APPLY");

            // Show error message with sanitized content
            var errorMsg = response.data || "Failed to apply coupon";
            if (typeof errorMsg === 'string') {
              $couponForm.after(
                '<div class="coupon-message error">' + errorMsg.replace(/[<>\"'&]/g, '') + "</div>"
              );
            }

            setTimeout(function () {
              jQuery(".coupon-message").fadeOut();
            }, 5000);
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log("❌ Network error applying coupon from URL:", textStatus, errorThrown);

        // Handle different error types
        var errorMessage = "Network error. Please try again.";
        if (textStatus === "timeout") {
          errorMessage = "Request timed out. Please check your connection.";
        } else if (textStatus === "abort") {
          errorMessage = "Request was cancelled.";
        }

        if ($couponForm.length) {
          $input.val("");
          $button.removeClass("loading").prop("disabled", false).text("APPLY");

          $couponForm.after(
            '<div class="coupon-message error">' + errorMessage + "</div>"
          );

          setTimeout(function () {
            jQuery(".coupon-message").fadeOut();
          }, 5000);
        }
      },
      complete: function () {
        // Clear processing flag since coupon application attempt is complete
        if (window.primefitCouponProcessing) {
          delete window.primefitCouponProcessing;
        }

        // Clean up request references to prevent memory leaks
        if (ajaxRequest.jqXHR) {
          ajaxRequest.jqXHR = null;
        }
        if (ajaxRequest.abortController) {
          ajaxRequest.abortController = null;
        }

        // Clean URL after application attempt
        cleanUrlAfterCouponApplication(couponCode);
      },
    });

    // Add request abortion capability for cleanup
    applyCouponFromUrl.abort = function() {
      if (ajaxRequest.jqXHR && ajaxRequest.jqXHR.readyState !== 4) {
        ajaxRequest.jqXHR.abort();
      }
    };
  }

  // Function to check for pending coupon from session
  // FIXED: Added state management to prevent race conditions
  function checkForPendingCouponFromSession() {
    // Check for pending coupon data from cart fragments (hidden element)
    var $couponData = jQuery(".primefit-coupon-data");
    if ($couponData.length) {
      var pendingCoupon = $couponData.data("pending-coupon");
      if (pendingCoupon && pendingCoupon.trim()) {
        console.log("🎫 Found pending coupon from session:", pendingCoupon);

        // Check if coupon is already applied
        var appliedCoupons = getAppliedCoupons();
        if (appliedCoupons.includes(pendingCoupon.toUpperCase())) {
          console.log(
            "✅ Pending coupon " + pendingCoupon + " is already applied"
          );
          return;
        }

        // CRITICAL: Check if coupon is already being processed to prevent race conditions
        if (window.primefitCouponProcessing && window.primefitCouponProcessing === pendingCoupon.toUpperCase()) {
          console.log("⏳ Coupon " + pendingCoupon + " is already being processed, skipping duplicate attempt");
          return;
        }

        // Mark as processing to prevent race conditions
        window.primefitCouponProcessing = pendingCoupon.toUpperCase();

        // Apply the pending coupon with additional safety check
        setTimeout(function () {
          // Double-check that WooCommerce is loaded before applying
          if (
            typeof wc_add_to_cart_params !== "undefined" ||
            jQuery(".woocommerce-mini-cart").length
          ) {
            applyCouponFromUrl(pendingCoupon.trim());
          } else {
            console.log(
              "⏳ Waiting for WooCommerce to load before applying session coupon"
            );
            // Try again after another delay
            setTimeout(function () {
              if (
                typeof wc_add_to_cart_params !== "undefined" ||
                jQuery(".woocommerce-mini-cart").length
              ) {
                applyCouponFromUrl(pendingCoupon.trim());
              } else {
                console.log(
                  "❌ WooCommerce not loaded, cannot apply session coupon:",
                  pendingCoupon
                );
                // Clear processing flag on failure
                delete window.primefitCouponProcessing;
              }
            }, 2000);
          }
        }, 1000); // Slightly longer delay for session-based coupons
      }
    }
  }

  // Function to clean URL after coupon application attempt
  function cleanUrlAfterCouponApplication(couponCode) {
    setTimeout(function () {
      try {
        if (window.history && window.history.replaceState) {
          var url = new URL(window.location);
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
      } catch (e) {
        // Ignore URL manipulation errors
      }
    }, 3000);
  }

  // Scroll prevention utilities
  let scrollPosition = 0;

  function getScrollbarWidth() {
    // Create a temporary div to measure scrollbar width
    const outer = document.createElement("div");
    outer.style.visibility = "hidden";
    outer.style.overflow = "scroll";
    outer.style.msOverflowStyle = "scrollbar";
    document.body.appendChild(outer);

    const inner = document.createElement("div");
    outer.appendChild(inner);

    const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
    outer.parentNode.removeChild(outer);

    return scrollbarWidth;
  }

  function preventPageScroll() {
    // Only prevent scroll if not already locked
    if (document.body.classList.contains("scroll-locked")) {
      return;
    }

    // Store current scroll position
    scrollPosition = window.pageYOffset || document.documentElement.scrollTop;

    // Calculate scrollbar width to prevent content shift
    const scrollbarWidth = getScrollbarWidth();

    // Add class to body for CSS styling
    document.body.classList.add("scroll-locked");

    // Set body position to fixed to prevent scrolling
    document.body.style.position = "fixed";
    document.body.style.top = `-${scrollPosition}px`;
    document.body.style.width = "100%";

    // Prevent content shift by adding padding for scrollbar
    if (scrollbarWidth > 0) {
      document.body.style.paddingRight = `${scrollbarWidth}px`;
    }
  }

  function allowPageScroll() {
    // Only allow scroll if currently locked
    if (!document.body.classList.contains("scroll-locked")) {
      return;
    }

    // Remove scroll lock class
    document.body.classList.remove("scroll-locked");

    // Restore body styles
    document.body.style.position = "";
    document.body.style.top = "";
    document.body.style.width = "";
    document.body.style.paddingRight = "";

    // Restore scroll position
    window.scrollTo(0, scrollPosition);
  }

  // Cart functionality (preserved)
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
    console.log("openCart called with:", clickedEl); // Debug log
    const { $wrap, $panel, $toggle } = getCartContext(clickedEl);
    console.log("Cart context:", {
      wrap: $wrap.length,
      panel: $panel.length,
      toggle: $toggle.length,
    }); // Debug log


    $wrap.addClass("open").attr("data-open", "true");
    $panel.removeAttr("hidden");
    $toggle.attr("aria-expanded", "true");

    if (window.matchMedia("(max-width: 1024px)").matches) {
      document.body.classList.add("cart-open");

      // Add iOS Safari specific prevention
      this.addIOSPrevention();
    }

    // Prevent page scrolling when cart is open
    preventPageScroll();


    // Ensure all quantity inputs are properly synced when cart opens
    // First try to get fresh cart fragments to ensure we have the latest data
    if (window.primefit_cart_params && window.primefit_cart_params.ajax_url) {
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
                  console.log(
                    "CART DEBUG: Synced quantity on cart open (fresh data) for item",
                    cartItemKey,
                    "to",
                    currentVal
                  );
                }
              });
            }, 50);
          }
        },
        error: function () {
          // Fallback: sync with current values if AJAX fails
          setTimeout(function () {
            $(
              ".woocommerce-mini-cart__item-quantity input[data-cart-item-key]"
            ).each(function () {
              const $input = $(this);
              const cartItemKey = $input.data("cart-item-key");
              const currentVal = parseInt($input.val()) || 1;

              if (currentVal && cartItemKey) {
                $input.val(currentVal);
                $input.attr("data-original-value", currentVal);
                console.log(
                  "CART DEBUG: Fallback sync quantity on cart open for item",
                  cartItemKey,
                  "to",
                  currentVal
                );
              }
            });
          }, 50);
        },
      });
    } else {
      // Fallback if no AJAX params available
      setTimeout(function () {
        $(
          ".woocommerce-mini-cart__item-quantity input[data-cart-item-key]"
        ).each(function () {
          const $input = $(this);
          const cartItemKey = $input.data("cart-item-key");
          const currentVal = parseInt($input.val()) || 1;

          if (currentVal && cartItemKey) {
            $input.val(currentVal);
            $input.attr("data-original-value", currentVal);
            console.log(
              "CART DEBUG: Basic sync quantity on cart open for item",
              cartItemKey,
              "to",
              currentVal
            );
          }
        });
      }, 50);
    }

    console.log("Cart opened successfully"); // Debug log
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
    allowPageScroll();

    // Reset form submission flag to allow new submissions
    if (
      window.ajaxAddToCartInstance &&
      typeof window.ajaxAddToCartInstance.resetSubmissionFlag === "function"
    ) {
      window.ajaxAddToCartInstance.resetSubmissionFlag();
    }
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
    // Handle WooCommerce core remove buttons
    $(document).off("click.primefit-cart", ".remove_from_cart_button");
    $(document).on(
      "click.primefit-cart",
      ".remove_from_cart_button",
      function (e) {
        e.preventDefault();
        const $btn = $(this);
        const cartItemKey = $btn.data("cart_item_key");
        console.log(
          "CART DEBUG: Remove button clicked, cart item key:",
          cartItemKey
        );

        if (cartItemKey) {
          $btn.addClass("loading").prop("disabled", true);
          removeCartItem(cartItemKey, $btn);
        } else {
          console.error("CART DEBUG: No cart item key found");
        }
      }
    );

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
      console.error("Invalid parameters for cart update");
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

                  // Debug log to track quantity sync
                  console.log(
                    "CART DEBUG: Synced quantity for item",
                    cartItemKey,
                    "to",
                    actualQuantity
                  );
                }
              });
            }, 100);
          }

          // Trigger WooCommerce cart update events
          $(document.body).trigger("update_checkout");
          $(document.body).trigger("wc_fragment_refresh");
          // Note: Avoid triggering added_to_cart without required params

          // Check if cart is empty after quantity update
          setTimeout(function () {
            checkAndShowEmptyCartState();
          }, 100);
        } else {
          console.error("Failed to update cart quantity:", response.data);
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
        console.error("AJAX error updating cart quantity:", error);
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
      console.error("Invalid parameters for cart item removal");
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

    console.log("CART DEBUG: Removing cart item:", cartItemKey); // Debug log
    console.log("CART DEBUG: primefit_cart_params:", primefit_cart_params); // Debug log

    // Validate we have the required parameters
    if (!primefit_cart_params.ajax_url) {
      console.error("CART DEBUG: No AJAX URL available");
      alert("Configuration error: No AJAX URL");
      return;
    }

    if (!primefit_cart_params.remove_cart_nonce) {
      console.error("CART DEBUG: No remove cart nonce available");
      alert("Configuration error: No security nonce");
      return;
    }

    const ajaxData = {
      action: "wc_ajax_remove_cart_item",
      cart_item_key: cartItemKey,
      security: primefit_cart_params.remove_cart_nonce,
    };

    console.log("CART DEBUG: AJAX data being sent:", ajaxData);

    $.ajax({
      type: "POST",
      url: primefit_cart_params.ajax_url,
      data: ajaxData,
      success: function (response) {
        console.log("Server response:", response); // Debug log

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
                  console.log(
                    "CART DEBUG: Synced quantity after removal for item",
                    cartItemKey,
                    "to",
                    currentVal
                  );
                }
              });
            }, 100);

            // Use server's cart state to determine if empty
            console.log(
              "Server says cart is empty:",
              response.data.cart_is_empty
            );
            console.log(
              "Server cart contents count:",
              response.data.cart_contents_count
            );

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
          } else {
            console.error("No fragments returned from server");
          }

          // Trigger WooCommerce cart update events
          $(document.body).trigger("update_checkout");
          $(document.body).trigger("wc_fragment_refresh");
          // Note: Avoid triggering removed_from_cart without required params
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

          alert(errorMessage);

          // Fallback: reload page if AJAX fails
          if (
            response.data &&
            typeof response.data === "string" &&
            response.data.includes("Security check failed")
          ) {
            console.log("CART DEBUG: Security check failed - reloading page");
            window.location.reload();
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error removing cart item:", { xhr, status, error });

        // Remove loading state and fade class on error
        $element.removeClass("loading").prop("disabled", false);
        if ($cartItem.length) {
          $cartItem.removeClass("removing");
        }

        // Show user-friendly error
        alert("Network error. Please check your connection and try again.");

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

    console.log("Checking empty cart state..."); // Debug log
    console.log("Cart items container exists:", $cartItems.length > 0); // Debug log

    // Check multiple indicators to ensure cart is truly empty
    const hasCartItemsContainer = $cartItems.length > 0;
    const cartItemsCount = hasCartItemsContainer
      ? $cartItems.find("li.woocommerce-mini-cart__item").length
      : 0;
    const hasEmptyMessage =
      $(".woocommerce-mini-cart__empty-message").length > 0;

    console.log("Cart items count:", cartItemsCount); // Debug log
    console.log("Has empty message:", hasEmptyMessage); // Debug log

    // If no cart items container exists OR cart items container is empty
    if (!hasCartItemsContainer || cartItemsCount === 0) {
      console.log("Cart is empty - showing empty state"); // Debug log
      showEmptyCartState();
    } else {
      console.log("Cart has items - hiding empty state"); // Debug log
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
      console.log("Product added to cart - auto-opening mini cart"); // Debug log
      console.log("Event data:", { fragments, cart_hash, button: $button }); // Debug log

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
                        console.log(
                          "CART DEBUG: Synced quantity after fresh fragments for item",
                          cartItemKey,
                          "to",
                          currentVal
                        );
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
                    console.log(
                      "CART DEBUG: Fallback sync quantity for item",
                      cartItemKey,
                      "to",
                      currentVal
                    );
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
                console.log(
                  "CART DEBUG: Basic sync quantity for item",
                  cartItemKey,
                  "to",
                  currentVal
                );
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

      // Auto-close after 5 seconds
      setTimeout(function () {
        console.log("Auto-closing mini cart after 5 seconds"); // Debug log
        closeCart();
      }, 5000);
    }
  );

  // Function to show empty cart state
  function showEmptyCartState() {
    console.log("Showing empty cart state"); // Debug log

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

    console.log("Empty cart state is now visible"); // Debug log
  }

  // Function to hide empty cart state
  function hideEmptyCartState() {
    console.log("Hiding empty cart state"); // Debug log

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

    console.log("Cart content is now visible"); // Debug log
  }

  // Shop Filter Bar Controller (preserved)
  class ShopFilterController {
    constructor() {
      this.$gridOptions = $(".grid-option");
      this.$productsGrid = $(".woocommerce ul.products");
      this.currentGrid = this.getCurrentGrid();
      this.isMobile = this.isMobileDevice();
      this.init();
    }

    init() {
      this.bindEvents();
      this.handleResize();
      this.applyGridLayout();
      this.syncFilterState();
    }

    bindEvents() {
      $(document).on("click", ".grid-option", this.handleGridClick.bind(this));
      $(document).on(
        "click",
        ".filter-dropdown-toggle",
        this.handleFilterToggle.bind(this)
      );
      $(document).on(
        "click",
        ".filter-dropdown-option",
        this.handleFilterOption.bind(this)
      );
      $(document).on("click", this.handleOutsideClick.bind(this));
      $(window).on("resize", this.debounce(this.handleResize.bind(this), 250));
    }

    handleGridClick(event) {
      event.preventDefault();
      const $button = $(event.currentTarget);
      const gridValue = $button.data("grid");
      if (this.isMobile && (gridValue === "3" || gridValue === "4")) return;
      if (!this.isMobile && (gridValue === "1" || gridValue === "2")) return;
      this.$gridOptions.removeClass("active");
      $button.addClass("active");
      this.currentGrid = gridValue;
      this.setCookie("primefit_grid_view", gridValue, 30);
      this.applyGridLayout();
    }

    applyGridLayout() {
      this.$productsGrid.removeClass("grid-1 grid-2 grid-3 grid-4");
      this.$productsGrid.addClass(`grid-${this.currentGrid}`);
      this.$productsGrid.attr(
        "class",
        this.$productsGrid
          .attr("class")
          .replace(/columns-\d+/, `columns-${this.currentGrid}`)
      );
    }

    handleFilterToggle(event) {
      event.preventDefault();
      event.stopPropagation();
      const $dropdown = $(event.currentTarget).closest(".filter-dropdown");
      const isOpen = $dropdown.hasClass("open");
      $(".filter-dropdown").removeClass("open");

      // Remove body class when closing
      if (isOpen) {
        document.body.classList.remove("filter-dropdown-open");
        allowPageScroll();
      } else {
        $dropdown.addClass("open");
        // Add body class and prevent scroll on mobile when opening
        if (this.isMobile) {
          document.body.classList.add("filter-dropdown-open");
          preventPageScroll();
        }
      }
    }

    handleFilterOption(event) {
      event.preventDefault();
      const $option = $(event.currentTarget);
      const $dropdown = $option.closest(".filter-dropdown");
      const filterValue = $option.data("filter");
      const filterText = $option.text().trim();
      $dropdown.find(".filter-dropdown-text").text(filterText);
      $dropdown.removeClass("open");
      // Remove body class and restore scroll when selecting option
      document.body.classList.remove("filter-dropdown-open");
      allowPageScroll();
      this.applyFilter(filterValue);
    }

    handleOutsideClick(event) {
      const $target = $(event.target);
      if (!$target.closest(".filter-dropdown").length) {
        $(".filter-dropdown").removeClass("open");
        // Remove body class and restore scroll when closing dropdown
        document.body.classList.remove("filter-dropdown-open");
        allowPageScroll();
      }
    }

    applyFilter(filterValue) {
      const filterMap = {
        featured: "menu_order",
        "best-selling": "popularity",
        "alphabetical-az": "title",
        "alphabetical-za": "title-desc",
        "price-low-high": "price",
        "price-high-low": "price-desc",
        "date-old-new": "date",
        "date-new-old": "date-desc",
      };
      const orderbyValue = filterMap[filterValue] || "menu_order";
      const $hiddenSelect = $(".woocommerce-ordering .orderby");
      $hiddenSelect.val(orderbyValue);
      $(".woocommerce-ordering").submit();
    }

    getCurrentGrid() {
      const cookieValue = this.getCookie("primefit_grid_view");
      if (cookieValue) return cookieValue;
      return this.isMobileDevice() ? "2" : "4";
    }

    isMobileDevice() {
      return window.matchMedia("(max-width: 1024px)").matches;
    }

    handleResize() {
      const wasMobile = this.isMobile;
      this.isMobile = this.isMobileDevice();
      if (wasMobile !== this.isMobile) {
        this.handleDeviceChange();
      }
    }

    handleDeviceChange() {
      const currentGrid = parseInt(this.currentGrid);
      if (this.isMobile) {
        if (currentGrid > 2) {
          this.currentGrid = "2";
          this.setCookie("primefit_grid_view", "2", 30);
        }
      } else {
        if (currentGrid < 3) {
          this.currentGrid = "4";
          this.setCookie("primefit_grid_view", "4", 30);
        }
      }
      this.updateActiveGridOption();
      this.applyGridLayout();
    }

    updateActiveGridOption() {
      this.$gridOptions.removeClass("active");
      const $activeOption = this.$gridOptions.filter(
        `[data-grid="${this.currentGrid}"]`
      );
      if ($activeOption.length) {
        $activeOption.addClass("active");
      }
    }

    setCookie(name, value, days) {
      const expires = new Date();
      expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
      document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
    }

    getCookie(name) {
      const nameEQ = name + "=";
      const ca = document.cookie.split(";");
      for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === " ") c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0)
          return c.substring(nameEQ.length, c.length);
      }
      return null;
    }

    syncFilterState() {
      const urlParams = new URLSearchParams(window.location.search);
      const currentOrderby =
        urlParams.get("orderby") ||
        $(".woocommerce-ordering .orderby").val() ||
        "menu_order";
      const orderbyMap = {
        menu_order: "featured",
        popularity: "best-selling",
        title: "alphabetical-az",
        "title-desc": "alphabetical-za",
        price: "price-low-high",
        "price-desc": "price-high-low",
        date: "date-old-new",
        "date-desc": "date-new-old",
      };
      const currentFilter = orderbyMap[currentOrderby] || "featured";
      const $dropdown = $(".filter-dropdown");
      const $activeOption = $dropdown.find(`[data-filter="${currentFilter}"]`);
      if ($activeOption.length) {
        $dropdown
          .find(".filter-dropdown-text")
          .text($activeOption.text().trim());
        $dropdown.find(".filter-dropdown-option").removeClass("active");
        $activeOption.addClass("active");
      }
      this.updateActiveGridOption();
    }

    debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }
  }

  if ($(".grid-option").length > 0) {
    const shopFilterController = new ShopFilterController();
  }

  // Header: add scrolled state to force black background on sticky
  const $header = $(".site-header");
  if ($header.length) {
    const toggleScrolled = () => {
      if (window.scrollY > 10) {
        $header.addClass("is-scrolled");
      } else {
        $header.removeClass("is-scrolled");
      }
    };
    toggleScrolled();
    $(window).on("scroll", toggleScrolled);
  }

  // Mobile hamburger menu
  $(document).on("click", ".hamburger", function (e) {
    e.preventDefault();
    const $body = $("body");
    const $nav = $("#mobile-nav");
    const isOpen = $body.hasClass("mobile-open");

    if (isOpen) {
      $body.removeClass("mobile-open");
      $(this).attr("aria-expanded", "false");
      // Re-enable page scrolling when mobile menu is closed
      allowPageScroll();
    } else {
      $body.addClass("mobile-open");
      $(this).attr("aria-expanded", "true");
      // Prevent page scrolling when mobile menu is open
      preventPageScroll();
    }
  });

  // Close mobile nav
  $(document).on(
    "click",
    ".mobile-nav-close, .mobile-nav-overlay",
    function (e) {
      e.preventDefault();
      $("body").removeClass("mobile-open");
      $(".hamburger").attr("aria-expanded", "false");
      // Re-enable page scrolling when mobile menu is closed
      allowPageScroll();
    }
  );

  // Mobile menu dropdown functionality
  $(document).on(
    "click",
    ".mobile-menu .menu-item-has-children > a",
    function (e) {
      e.preventDefault();
      const $parent = $(this).parent();
      const $submenu = $parent.find(".sub-menu");

      if ($submenu.length) {
        const isOpen = $parent.hasClass("mobile-submenu-open");

        // Close all other open submenus in the same menu
        $parent
          .siblings(".menu-item-has-children")
          .removeClass("mobile-submenu-open");

        if (!isOpen) {
          $parent.addClass("mobile-submenu-open");
        }
      }
    }
  );

  // Hero Video Background Handler
  class HeroVideoHandler {
    constructor() {
      this.init();
    }

    init() {
      this.handleHeroVideos();
    }

    handleHeroVideos() {
      const $heroVideos = $(".hero-video");

      if ($heroVideos.length === 0) return;

      $heroVideos.each((index, video) => {
        const $video = $(video);
        const videoElement = video;

        // Set up video event listeners
        this.setupVideoEvents($video, videoElement);

        // Start loading the video
        this.loadVideo($video, videoElement);
      });
    }

    setupVideoEvents($video, videoElement) {
      // When video can play through
      videoElement.addEventListener("canplaythrough", () => {
        this.onVideoReady($video, videoElement);
      });

      // When video starts playing
      videoElement.addEventListener("playing", () => {
        this.onVideoPlaying($video, videoElement);
      });

      // Handle video errors
      videoElement.addEventListener("error", () => {
        this.onVideoError($video, videoElement);
      });

      // Handle video loading
      videoElement.addEventListener("loadstart", () => {
        this.onVideoLoadStart($video, videoElement);
      });
    }

    loadVideo($video, videoElement) {
      // Set video source and start loading
      const sources = videoElement.querySelectorAll("source");
      if (sources.length > 0) {
        // Let the browser choose the best source
        videoElement.load();
      }
    }

    onVideoReady($video, videoElement) {
      // Video is ready to play
      $video.addClass("loaded");

      // Try to play the video
      const playPromise = videoElement.play();

      if (playPromise !== undefined) {
        playPromise
          .then(() => {
            this.onVideoPlaying($video, videoElement);
          })
          .catch((error) => {
            this.onVideoError($video, videoElement);
          });
      }
    }

    onVideoPlaying($video, videoElement) {
      // Video is playing successfully

      // Hide fallback image with smooth transition
      const $fallbackImage = $video
        .closest(".hero-media")
        .find(".hero-fallback-image");
      $fallbackImage.css("opacity", "0");
    }

    onVideoError($video, videoElement) {
      // Video failed to load or play

      // Ensure fallback image is visible
      const $fallbackImage = $video
        .closest(".hero-media")
        .find(".hero-fallback-image");
      $fallbackImage.css("opacity", "1");

      // Hide the video
      $video.css("opacity", "0");
    }
  }

  // Initialize hero video handler
  if ($(".hero-video").length > 0) {
    new HeroVideoHandler();
  }

  // Mega Menu Controller
  class MegaMenuController {
    constructor() {
      this.$megaMenu = $("#mega-menu");
      this.$header = $(".site-header");
      this.isDesktop = this.isDesktopDevice();
      this.isOpen = false;
      this.hoverTimeout = null;
      this.init();
    }

    init() {
      if (this.$megaMenu.length === 0) return;

      this.bindEvents();
      this.handleResize();
    }

    bindEvents() {
      // Show mega menu only on specific menu item hover (desktop only)
      // Look for menu items with data-mega-menu="true" attribute on the link
      $(document).on(
        "mouseenter",
        ".menu--primary .menu-item a[data-mega-menu='true']",
        this.handleMenuItemHover.bind(this)
      );
      $(document).on(
        "mouseleave",
        ".menu--primary .menu-item a[data-mega-menu='true']",
        this.handleMenuItemLeave.bind(this)
      );

      // Also handle hover on the mega menu itself to keep it open
      $(document).on(
        "mouseenter",
        ".mega-menu",
        this.handleMegaMenuHover.bind(this)
      );
      $(document).on(
        "mouseleave",
        ".mega-menu",
        this.handleMegaMenuLeave.bind(this)
      );

      // Hide mega menu when clicking outside
      $(document).on("click", this.handleOutsideClick.bind(this));

      // Handle window resize
      $(window).on("resize", this.debounce(this.handleResize.bind(this), 250));
    }

    handleMenuItemHover(event) {
      if (!this.isDesktop) return;

      clearTimeout(this.hoverTimeout);
      this.showMegaMenu();
    }

    handleMenuItemLeave(event) {
      if (!this.isDesktop) return;

      this.hoverTimeout = setTimeout(() => {
        this.hideMegaMenu();
      }, 150);
    }

    handleMegaMenuHover() {
      if (!this.isDesktop) return;

      clearTimeout(this.hoverTimeout);
    }

    handleMegaMenuLeave() {
      if (!this.isDesktop) return;

      this.hoverTimeout = setTimeout(() => {
        this.hideMegaMenu();
      }, 150);
    }

    handleOutsideClick(event) {
      if (!this.isDesktop || !this.isOpen) return;

      const $target = $(event.target);
      if (
        !$target.closest(".site-header").length &&
        !$target.closest(".mega-menu").length
      ) {
        this.hideMegaMenu();
      }
    }

    showMegaMenu() {
      if (this.isOpen) return;

      this.isOpen = true;
      this.$megaMenu.addClass("active").attr("aria-hidden", "false");
      this.$header.addClass("mega-menu-open");
    }

    hideMegaMenu() {
      if (!this.isOpen) return;

      this.isOpen = false;
      this.$megaMenu.removeClass("active").attr("aria-hidden", "true");
      this.$header.removeClass("mega-menu-open");
    }

    isDesktopDevice() {
      return window.matchMedia("(min-width: 1025px)").matches;
    }

    handleResize() {
      const wasDesktop = this.isDesktop;
      this.isDesktop = this.isDesktopDevice();

      if (wasDesktop !== this.isDesktop) {
        if (!this.isDesktop) {
          this.hideMegaMenu();
        }
      }
    }

    debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }
  }

  // Initialize mega menu controller
  if ($("#mega-menu").length > 0) {
    new MegaMenuController();
  }

  // Mini Cart Enhancements

  // Handle coupon form submission in mini cart
  $(document).on("submit", ".mini-cart-coupon-form", function (e) {
    e.preventDefault();

    const $form = $(this);
    const $input = $form.find(".coupon-code-input");
    const $button = $form.find(".apply-coupon-btn");
    const couponCode = $input.val().trim();

    if (!couponCode) {
      return;
    }

    // Show loading state
    $button.addClass("loading").prop("disabled", true).text("Applying...");

    // Apply coupon via AJAX
    $.ajax({
      type: "POST",
      url: primefit_cart_params.ajax_url,
      data: {
        action: "apply_coupon",
        security: primefit_cart_params.apply_coupon_nonce,
        coupon_code: couponCode,
      },
      success: function (response) {
        if (response.success) {
          // Clear the input
          $input.val("");

          // Refresh cart fragments
          $(document.body).trigger("update_checkout");
          $(document.body).trigger("wc_fragment_refresh");

          // Show success message
          $form.after(
            '<div class="coupon-message success">Coupon applied successfully!</div>'
          );

          setTimeout(function () {
            $(".coupon-message").fadeOut();
          }, 3000);
        } else {
          // Show error message
          let errorMsg = response.data || "Failed to apply coupon";
          $form.after(
            '<div class="coupon-message error">' + errorMsg + "</div>"
          );

          setTimeout(function () {
            $(".coupon-message").fadeOut();
          }, 5000);
        }
      },
      error: function () {
        $form.after(
          '<div class="coupon-message error">Network error. Please try again.</div>'
        );
        setTimeout(function () {
          $(".coupon-message").fadeOut();
        }, 5000);
      },
      complete: function () {
        // Remove loading state
        $button.removeClass("loading").prop("disabled", false).text("APPLY");
      },
    });
  });

  // Handle coupon removal
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
          // Refresh cart fragments
          $(document.body).trigger("update_checkout");
          $(document.body).trigger("wc_fragment_refresh");
        }
      },
      error: function () {
        console.error("Failed to remove coupon");
      },
      complete: function () {
        // Remove loading state
        $button.removeClass("loading").prop("disabled", false);
      },
    });
  });

  /**
   * Add variation to cart via AJAX - simulates clicking the add to cart button
   */
  function addVariationToCart($productContainer, $colorSwatch, $sizeOption) {
    const variationId = $sizeOption.data("variation-id");
    const productId = $sizeOption.data("product-id");
    const colorValue = $colorSwatch.data("color");
    const sizeValue = $sizeOption.data("size");

    console.log("Size option data:", {
      variationId,
      productId,
      colorValue,
      sizeValue,
      isInStock: $sizeOption.data("is-in-stock"),
      stockQuantity: $sizeOption.data("stock-quantity"),
    });

    if (!variationId || !productId || variationId === 0) {
      console.error("Missing or invalid variation/product ID:", {
        variationId,
        productId,
      });
      showCartFeedback(
        $productContainer,
        "Invalid product configuration",
        "error"
      );
      return;
    }

    // Set loading state
    $sizeOption.addClass("loading").prop("disabled", true);
    $colorSwatch.addClass("loading");

    // Create a temporary form to simulate WooCommerce's add to cart form
    const $tempForm = $("<form>")
      .attr({
        method: "POST",
        action: window.location.href,
        enctype: "multipart/form-data",
      })
      .addClass("temp-form")
      .data("temp-form", true);

    // Add all required form fields exactly like WooCommerce expects
    $tempForm.append(
      $("<input>").attr({
        type: "hidden",
        name: "add-to-cart",
        value: productId,
      })
    );

    $tempForm.append(
      $("<input>").attr({
        type: "hidden",
        name: "product_id",
        value: productId,
      })
    );

    $tempForm.append(
      $("<input>").attr({
        type: "hidden",
        name: "variation_id",
        value: variationId,
      })
    );

    $tempForm.append(
      $("<input>").attr({
        type: "hidden",
        name: "quantity",
        value: 1,
      })
    );

    // Add variation attributes with proper naming convention
    if (colorValue) {
      $tempForm.append(
        $("<input>").attr({
          type: "hidden",
          name: "attribute_pa_color",
          value: colorValue,
        })
      );
    }

    if (sizeValue) {
      $tempForm.append(
        $("<input>").attr({
          type: "hidden",
          name: "attribute_pa_size",
          value: sizeValue,
        })
      );
    }

    // Serialize the form data
    const formData = $tempForm.serializeArray().reduce(function (obj, item) {
      obj[item.name] = item.value;
      return obj;
    }, {});

    // Use standard WooCommerce add to cart endpoint
    formData.action = "woocommerce_add_to_cart";

    // Get security nonce
    const securityNonce =
      (window.primefit_cart_params &&
        window.primefit_cart_params.add_to_cart_nonce) ||
      (window.wc_add_to_cart_params &&
        window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce);

    if (!securityNonce) {
      console.error("No security nonce available");
      showCartFeedback($productContainer, "Configuration error", "error");
      $sizeOption.removeClass("loading").prop("disabled", false);
      $colorSwatch.removeClass("loading");
      return;
    }

    formData.security = securityNonce;

    // Get AJAX URL
    const ajaxUrl =
      (window.primefit_cart_params && window.primefit_cart_params.ajax_url) ||
      (window.wc_add_to_cart_params && window.wc_add_to_cart_params.ajax_url) ||
      "/wp-admin/admin-ajax.php";

    console.log("Adding to cart with form data:", formData);

    // Make AJAX request using WooCommerce's standard approach
    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: formData,
      dataType: "json",
      timeout: 8000,
      cache: false,
      success: function (response) {
        console.log("Add to cart response:", response);

        // Handle the response exactly like WooCommerce does
        if (response.error && response.product_url) {
          // This might be a redirect response, check if product was actually added
          console.log("Received redirect response, checking cart...");
          checkCartAfterAdd($productContainer, $sizeOption, $colorSwatch);
        } else if (response.error) {
          // Show error message
          console.log("Error adding to cart:", response.error);
          showCartFeedback(
            $productContainer,
            "Error adding to cart: " + (response.error || "Unknown error"),
            "error"
          );
        } else {
          // Success - update cart fragments
          console.log("Successfully added to cart");

          // Trigger cart update events
          $(document.body).trigger("added_to_cart", [
            response.fragments,
            response.cart_hash,
            $sizeOption,
          ]);

          // Update cart fragments if available
          if (response.fragments) {
            $.each(response.fragments, function (key, value) {
              $(key).replaceWith(value);
            });
          }

          // Open mini cart automatically after successful add (with small delay to ensure fragments are updated)
          setTimeout(function () {
            openMiniCart();
          }, 100);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", { xhr, status, error });
        showCartFeedback($productContainer, "Error adding to cart", "error");
      },
      complete: function () {
        // Remove loading state
        $sizeOption.removeClass("loading").prop("disabled", false);
        $colorSwatch.removeClass("loading");
      },
    });
  }

  /**
   * Check cart after add to see if product was actually added
   */
  function checkCartAfterAdd($productContainer, $sizeOption, $colorSwatch) {
    // Remove loading state
    $sizeOption.removeClass("loading").prop("disabled", false);
    $colorSwatch.removeClass("loading");

    // Trigger cart fragment refresh
    $(document.body).trigger("wc_fragment_refresh");

    // Open mini cart automatically after a short delay
    setTimeout(function () {
      openMiniCart();
    }, 500);
  }

  /**
   * Open mini cart automatically
   */
  function openMiniCart() {
    // Find the cart toggle button and trigger it
    const $cartToggle = $(".cart-toggle");
    if ($cartToggle.length > 0) {
      // Check if cart is already open
      const isExpanded = $cartToggle.attr("aria-expanded") === "true";
      if (!isExpanded) {
        // Trigger click to open cart
        $cartToggle.trigger("click");
      }
    }
  }

  /**
   * Show cart feedback message
   */
  function showCartFeedback($productContainer, message, type) {
    // Remove any existing feedback
    $productContainer.find(".cart-feedback").remove();

    // Create feedback element
    const $feedback = $(
      `<div class="cart-feedback cart-feedback-${type}">${message}</div>`
    );

    // Add to product container
    $productContainer.append($feedback);

    // Auto-remove after 3 seconds
    setTimeout(() => {
      $feedback.fadeOut(300, function () {
        $(this).remove();
      });
    }, 3000);
  }

  /**
   * Update size options based on selected color
   */
  function updateSizeOptionsForColor($productContainer, selectedColor) {
    const $sizeOptions = $productContainer.find(".product-size-options");
    const $sizeButtons = $sizeOptions.find(".size-option");
    const variationsData = $sizeOptions.data("variations");

    if (!variationsData || !selectedColor) {
      return;
    }

    // Reset all size buttons
    $sizeButtons.removeClass("out-of-stock").prop("disabled", false);

    // Update each size button based on color availability
    $sizeButtons.each(function () {
      const $button = $(this);
      const size = $button.data("size");
      const buttonColor = $button.data("color");

      // Find the variation for this size and color combination
      let matchingVariation = null;
      for (const variationId in variationsData) {
        const variation = variationsData[variationId];
        if (variation.size === size && variation.color === selectedColor) {
          matchingVariation = variation;
          break;
        }
      }

      if (matchingVariation) {
        // Update button with variation data
        $button.data("variation-id", matchingVariation.variation_id);
        $button.data("is-in-stock", matchingVariation.is_in_stock);
        $button.data("stock-quantity", matchingVariation.stock_quantity);
        $button.data(
          "max-purchase-quantity",
          matchingVariation.max_purchase_quantity
        );

        // Update visual state based on stock availability
        if (!matchingVariation.is_in_stock) {
          $button.addClass("out-of-stock").prop("disabled", true);
        } else {
          $button.removeClass("out-of-stock").prop("disabled", false);
        }
      } else {
        // No variation found for this size/color combination
        $button.addClass("out-of-stock").prop("disabled", true);
        $button.data("variation-id", 0);
        $button.data("is-in-stock", false);
      }
    });
  }

  /**
   * Product Loop Color Swatches Functionality
   */
  $(document).ready(function () {
    // Handle color swatch clicks in product loops
    $(document).on(
      "click",
      ".product-loop-color-swatches .color-swatch",
      function (e) {
        console.log('Color swatch click event triggered!');
        e.preventDefault();
        e.stopPropagation(); // Prevent triggering the product link

        const $swatch = $(this);
        const $productContainer = $swatch.closest(".product");
        const $imageContainer = $productContainer.find(
          ".product-image-container"
        );
        const $mainImage = $imageContainer.find(
          ".attachment-woocommerce_thumbnail, img"
        ).first();
        const $secondImage = $imageContainer.find(".product-second-image");
        const variationImage = $swatch.data("variation-image");
        const selectedColor = $swatch.data("color");

        // Debug logging
        console.log('Color swatch clicked:', {
          element: $swatch,
          color: selectedColor,
          variationImage: variationImage,
          hasVariationImage: !!variationImage,
          productId: $productContainer.data('product-id'),
          swatchDataAttributes: {
            color: $swatch.data('color'),
            variationImage: $swatch.data('variation-image'),
            productId: $swatch.data('product-id')
          }
        });

        // If no main image found, try to find any img element in the product container
        if ($mainImage.length === 0) {
          const $fallbackImage = $productContainer.find('img').first();
          if ($fallbackImage.length > 0) {
            $mainImage = $fallbackImage;
          }
        }

        // Update active state
        $productContainer.find(".color-swatch").removeClass("active");
        $swatch.addClass("active");

        // If there's a variation image, update the main image
        if (variationImage && variationImage !== "" && $mainImage.length > 0) {
          console.log('Updating main image to:', variationImage);
          console.log('Main image element found:', $mainImage[0]);
          // Add a class to prevent hover effects from interfering
          $productContainer.addClass("color-swatch-active");

          // Update the main image directly
          const oldSrc = $mainImage.attr("src");
          const oldSrcset = $mainImage.attr("srcset");
          console.log('Current image src:', oldSrc);
          console.log('Current image srcset:', oldSrcset);
          
          // Add cache busting parameter to prevent browser caching
          const cacheBustedImage = variationImage.includes('?') ? variationImage + '&t=' + Date.now() : variationImage + '?t=' + Date.now();
          console.log('Setting new image src to:', cacheBustedImage);
          
          // Update both src and srcset attributes
          $mainImage.attr("src", cacheBustedImage);
          $mainImage.removeAttr("srcset"); // Remove srcset to force browser to use src

          // Hide the second image to prevent hover conflicts
          if ($secondImage.length > 0) {
            $secondImage.css("opacity", "0");
          }

          console.log('Image updated from', oldSrc, 'to', cacheBustedImage);
        } else if (variationImage && variationImage !== "") {
          console.log('Using fallback image update method for:', variationImage);
          console.log('Main image not found, searching for any image in product container');
          // Fallback: try to find and update any image in the product container
          const $anyImage = $productContainer.find('img').first();
          console.log('Found fallback image element:', $anyImage[0]);
          if ($anyImage.length > 0) {
            const oldSrc = $anyImage.attr("src");
            const oldSrcset = $anyImage.attr("srcset");
            console.log('Fallback current image src:', oldSrc);
            console.log('Fallback current image srcset:', oldSrcset);
            // Add cache busting parameter to prevent browser caching
            const cacheBustedImage = variationImage.includes('?') ? variationImage + '&t=' + Date.now() : variationImage + '?t=' + Date.now();
            console.log('Setting fallback image src to:', cacheBustedImage);
            $anyImage.attr("src", cacheBustedImage);
            $anyImage.removeAttr("srcset"); // Remove srcset to force browser to use src
            $productContainer.addClass("color-swatch-active");
            console.log('Fallback image updated from', oldSrc, 'to', cacheBustedImage);
          } else {
            console.log('No image element found to update');
          }
        } else {
          console.log('No variation image available');
        }

        // Update size options based on selected color (safely wrapped in try-catch)
        try {
          updateSizeOptionsForColor($productContainer, selectedColor);
        } catch (error) {
          console.warn('Error updating size options for color:', error);
          // Continue with the color switcher functionality even if size options fail
        }
      }
    );

    // Handle hover effects for color swatches
    $(document).on(
      "mouseenter",
      ".product-loop-color-swatches .color-swatch",
      function () {
        $(this).addClass("hover");
      }
    );

    $(document).on(
      "mouseleave",
      ".product-loop-color-swatches .color-swatch",
      function () {
        $(this).removeClass("hover");
      }
    );

    // Handle size option clicks in product loops
    $(document).on("click", ".product-size-options .size-option", function (e) {
      e.preventDefault();
      e.stopPropagation(); // Prevent triggering the product link

      const $sizeOption = $(this);
      const $productContainer = $sizeOption.closest(".product");

      // Only prevent if the exact same size option is already loading
      if ($sizeOption.hasClass("loading")) {
        console.log(
          "This size option is already processing, ignoring duplicate click"
        );
        return;
      }

      // Check if the size option is in stock
      const isInStock = $sizeOption.data("is-in-stock");
      if (!isInStock) {
        return; // Don't proceed if out of stock
      }

      // Get the selected color
      const $selectedColor = $productContainer.find(".color-swatch.active");
      if (!$selectedColor.length) {
        // If no color selected, try to get the first available color
        const $firstColor = $productContainer.find(".color-swatch").first();
        if ($firstColor.length) {
          $firstColor.addClass("active");
          addVariationToCart($productContainer, $firstColor, $sizeOption);
        } else {
          // No color swatches available (single color product), create a dummy color swatch
          const variationsData = $sizeOption.closest(".product-size-options").data("variations");
          if (variationsData) {
            // Get the first available color from variations data
            let defaultColor = '';
            for (const variationId in variationsData) {
              const variation = variationsData[variationId];
              if (variation.color) {
                defaultColor = variation.color;
                break;
              }
            }
            
            if (defaultColor) {
              // Create a temporary color swatch element for single color products
              const $tempColorSwatch = $('<div class="temp-color-swatch" data-color="' + defaultColor + '"></div>');
              addVariationToCart($productContainer, $tempColorSwatch, $sizeOption);
            }
          }
        }
      } else {
        addVariationToCart($productContainer, $selectedColor, $sizeOption);
      }
    });

    // Initialize size options for default color on page load
    $(".product-loop-color-swatches .color-swatch.active").each(function () {
      const $swatch = $(this);
      const $productContainer = $swatch.closest(".product");
      const selectedColor = $swatch.data("color");
      updateSizeOptionsForColor($productContainer, selectedColor);
    });

    // If no active color swatch, initialize with first available color
    $(".product-loop-color-swatches").each(function () {
      const $colorSwatches = $(this);
      const $productContainer = $colorSwatches.closest(".product");

      if ($colorSwatches.find(".color-swatch.active").length === 0) {
        const $firstSwatch = $colorSwatches.find(".color-swatch").first();
        if ($firstSwatch.length > 0) {
          $firstSwatch.addClass("active");
          const firstColor = $firstSwatch.data("color");
          updateSizeOptionsForColor($productContainer, firstColor);
        }
      }
    });

    // Handle products with size options but no color swatches (single color products)
    $(".product-size-options").each(function () {
      const $sizeOptions = $(this);
      const $productContainer = $sizeOptions.closest(".product");
      const $colorSwatches = $productContainer.find(".product-loop-color-swatches");
      
      // If there are no color swatches but there are size options, initialize with default color
      if ($colorSwatches.length === 0 || $colorSwatches.find(".color-swatch").length === 0) {
        const variationsData = $sizeOptions.data("variations");
        if (variationsData) {
          // Get the first available color from variations data
          let defaultColor = '';
          for (const variationId in variationsData) {
            const variation = variationsData[variationId];
            if (variation.color) {
              defaultColor = variation.color;
              break;
            }
          }
          
          if (defaultColor) {
            updateSizeOptionsForColor($productContainer, defaultColor);
          }
        }
      }
    });

    // Reset color swatch state when leaving the product
    $(document).on(
      "mouseleave",
      ".woocommerce ul.products li.product",
      function () {
        const $product = $(this);
        const $activeSwatch = $product.find(".color-swatch.active");

        // Only reset if there's an active swatch and it's not the default
        if ($activeSwatch.length && !$activeSwatch.hasClass("default-color")) {
          // Remove the active class and reset to default
          $product.removeClass("color-swatch-active");
          $product.find(".color-swatch").removeClass("active");

          // Reset to the first/default color swatch
          const $defaultSwatch = $product.find(".color-swatch").first();
          if ($defaultSwatch.length) {
            $defaultSwatch.addClass("active");
            const defaultImage = $defaultSwatch.data("variation-image");
            if (defaultImage && defaultImage !== "") {
              $product
                .find(".attachment-woocommerce_thumbnail")
                .attr("src", defaultImage);
            }
          }
        }
      }
    );
  });
})(jQuery);