/**
 * PrimeFit Theme - Core JavaScript Module
 * Essential functionality that must be loaded on all pages
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * WooCommerce mini-cart fragments are cached in localStorage (HTML),
   * which can persist across theme code updates. If we change markup in PHP,
   * users may still see stale fragment HTML until their cart hash changes.
   *
   * Fix: when our theme asset version changes, clear the WC fragment cache
   * so the mini cart re-renders with the latest template output.
   */
  (function primefitInvalidateWooFragmentsOnThemeUpdate() {
    try {
      var currentVersion =
        window.PrimeFit && window.PrimeFit.assetVersion
          ? String(window.PrimeFit.assetVersion)
          : null;
      if (!currentVersion || !window.localStorage) return;

      var key = "primefit_asset_version";
      var previousVersion = window.localStorage.getItem(key);
      if (previousVersion === currentVersion) return;

      // Clear WooCommerce cached fragments (keys vary slightly by WC versions).
      window.localStorage.removeItem("wc_fragments");
      window.localStorage.removeItem("wc_cart_hash");
      window.localStorage.removeItem("wc_cart_created");
      window.sessionStorage &&
        window.sessionStorage.removeItem("wc_fragments");

      window.localStorage.setItem(key, currentVersion);

      // If wc-fragments is present, ask it to refresh ASAP.
      if (document && document.body) {
        $(document.body).trigger("wc_fragment_refresh");
      }
    } catch (e) {
      // no-op
    }
  })();

  function isIOSSafari() {
    var ua = navigator.userAgent || navigator.vendor || window.opera;
    var isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
    var isWebKit = /WebKit\//.test(ua) && !/CriOS|FxiOS|EdgiOS/.test(ua);
    return isIOS && isWebKit;
  }
  function getIOSMajorVersion() {
    try {
      var ua = navigator.userAgent || "";
      // Example: CPU iPhone OS 17_5 like Mac OS X
      var match = ua.match(/OS\s(\d+)[._]/);
      if (match && match[1]) {
        return parseInt(match[1], 10);
      }
    } catch (e) {}
    return null;
  }

  /**
   * Unified Cart Manager - Prevents cart fragment refresh conflicts
   * Enhanced with proper queuing, priority system, and conflict resolution
   */
  const CartManager = {
    // Centralized state management
    refreshQueue: [],
    isRefreshing: false,
    refreshTimeout: null,
    debounceDelay: 50, // Optimized to 50ms for faster cart operations
    maxRetries: 3,
    retryDelay: 200,
    operationPriorities: {
      wc_fragment_refresh: 1, // Highest priority
      update_checkout: 2,
      added_to_cart: 3,
      removed_from_cart: 3,
    },

    /**
     * Queue a cart refresh operation with priority
     * @param {string} operation - Type of operation
     * @param {Object} options - Additional options including priority
     */
    queueRefresh: function (operation, options = {}) {
      const priority =
        options.priority || this.operationPriorities[operation] || 5;
      const operationData = {
        operation,
        priority,
        timestamp: Date.now(),
        retries: 0,
      };

      // Remove existing operation of same type to prevent duplicates
      this.refreshQueue = this.refreshQueue.filter(
        (item) => item.operation !== operation
      );

      // Add new operation
      this.refreshQueue.push(operationData);

      // Sort by priority (lower number = higher priority)
      this.refreshQueue.sort((a, b) => a.priority - b.priority);

      // Clear existing timeout
      if (this.refreshTimeout) {
        clearTimeout(this.refreshTimeout);
      }

      // Debounce the refresh
      this.refreshTimeout = setTimeout(() => {
        this.executeRefreshQueue();
      }, this.debounceDelay);
    },

    /**
     * Execute all queued refresh operations with enhanced conflict resolution
     */
    executeRefreshQueue: function () {
      if (this.isRefreshing) {
        // If already refreshing, queue another refresh after completion
        setTimeout(() => this.executeRefreshQueue(), this.retryDelay);
        return;
      }

      if (this.refreshQueue.length === 0) {
        return;
      }

      this.isRefreshing = true;
      const operations = [...this.refreshQueue];
      this.refreshQueue = [];

      // Execute operations with proper timing and error handling
      this.executeOperations(operations);
    },

    /**
     * Execute operations in the correct order with retry logic
     */
    executeOperations: function (operations) {
      if (operations.length === 0) {
        this.isRefreshing = false;
        return;
      }

      const operationData = operations.shift();
      const { operation, retries } = operationData;

      // Execute operation with error handling
      this.executeOperation(operation)
        .then(() => {
          // Success - continue with next operation
          setTimeout(() => {
            this.executeOperations(operations);
          }, 50);
        })
        .catch((error) => {
          // Error executing cart operation - handled silently in production

          // Retry logic
          if (retries < this.maxRetries) {
            operationData.retries++;
            operations.unshift(operationData); // Put back at front of queue
          }

          // Continue with next operation after delay
          setTimeout(() => {
            this.executeOperations(operations);
          }, this.retryDelay);
        });
    },

    /**
     * Execute a single operation with Promise-based error handling
     */
    executeOperation: function (operation) {
      return new Promise((resolve, reject) => {
        try {
          // Use requestAnimationFrame for smoother execution
          requestAnimationFrame(() => {
            try {
              switch (operation) {
                case "update_checkout":
                  $(document.body).trigger("update_checkout");
                  break;
                case "wc_fragment_refresh":
                  $(document.body).trigger("wc_fragment_refresh");
                  break;
                case "added_to_cart":
                  // Only trigger if not already triggered
                  if (!$(document.body).data("added-to-cart-triggered")) {
                    $(document.body).trigger("added_to_cart");
                    $(document.body).data(
                      "added-to-cart-triggered",
                      Date.now()
                    );
                    // Clear flag after 1 second
                    setTimeout(() => {
                      $(document.body).removeData("added-to-cart-triggered");
                    }, 1000);
                  }
                  break;
                case "removed_from_cart":
                  // Only trigger if not already triggered
                  if (!$(document.body).data("removed-from-cart-triggered")) {
                    $(document.body).trigger("removed_from_cart");
                    $(document.body).data(
                      "removed-from-cart-triggered",
                      Date.now()
                    );
                    // Clear flag after 1 second
                    setTimeout(() => {
                      $(document.body).removeData(
                        "removed-from-cart-triggered"
                      );
                    }, 1000);
                  }
                  break;
                default:
                // Unknown cart operation - handled silently
              }
              resolve();
            } catch (error) {
              reject(error);
            }
          });
        } catch (error) {
          reject(error);
        }
      });
    },

    /**
     * Force immediate refresh (bypasses queue for urgent operations)
     */
    forceRefresh: function (
      operations = ["update_checkout", "wc_fragment_refresh"]
    ) {
      // Clear any pending queue
      this.refreshQueue = [];
      if (this.refreshTimeout) {
        clearTimeout(this.refreshTimeout);
      }

      // Execute immediately with priority
      const operationData = operations.map((op) => ({
        operation: op,
        priority: this.operationPriorities[op] || 1,
        timestamp: Date.now(),
        retries: 0,
      }));

      this.executeOperations(operationData);
    },

    /**
     * Check if cart is currently refreshing
     */
    isCurrentlyRefreshing: function () {
      return this.isRefreshing;
    },

    /**
     * Get queue status
     */
    getQueueStatus: function () {
      return {
        isRefreshing: this.isRefreshing,
        queueLength: this.refreshQueue.length,
        queue: this.refreshQueue.map((item) => ({
          operation: item.operation,
          priority: item.priority,
          retries: item.retries,
        })),
      };
    },
  };

  /**
   * Unified Coupon Manager - Shared between checkout and cart
   * Prevents race conditions and duplicate coupon applications
   *
   * Features:
   * - Toast notifications for coupon errors (including usage limit errors)
   * - Automatic detection of usage limit errors with special handling
   * - User prompts to remove invalid coupons
   * - Race condition prevention with processing queue
   * - Consistent error handling across cart and checkout pages
   */
  const CouponManager = {
    // Centralized state management
    processingQueue: new Map(), // Track coupons being processed
    retryTimeouts: new Map(), // Track retry attempts
    maxRetries: 2,
    retryDelay: 1000,
    clearDelay: 3000, // Clear processing flag after 3 seconds

    /**
     * Apply coupon with race condition prevention
     * @param {string} couponCode - The coupon code to apply
     * @param {Object} options - Configuration options
     */
    applyCoupon: function (couponCode, options = {}) {
      const normalizedCode = couponCode.toUpperCase().trim();

      // Check if already applied
      const appliedCoupons = this.getAppliedCoupons();
      if (appliedCoupons.includes(normalizedCode)) {
        this.clearProcessingFlag(normalizedCode);
        if (options.onSuccess) options.onSuccess();
        return;
      }

      // Check if already being processed
      if (this.isProcessing(normalizedCode)) {
        return; // Already being processed, let it complete
      }

      // Mark as processing
      this.setProcessing(normalizedCode);

      // Apply coupon based on context
      if (options.isCheckout) {
        this.applyCouponElegantly(couponCode, options);
      } else {
        this.applyCouponViaAjax(couponCode, options);
      }
    },

    /**
     * Check if coupon is currently being processed
     */
    isProcessing: function (couponCode) {
      const normalizedCode = couponCode.toUpperCase();
      const processingItem = this.processingQueue.get(normalizedCode);

      if (!processingItem) return false;

      // Check if processing has timed out (3 seconds)
      if (Date.now() - processingItem.timestamp > this.clearDelay) {
        this.processingQueue.delete(normalizedCode);
        return false;
      }

      return true;
    },

    /**
     * Mark coupon as being processed
     */
    setProcessing: function (couponCode) {
      const normalizedCode = couponCode.toUpperCase();
      this.processingQueue.set(normalizedCode, {
        timestamp: Date.now(),
        retries: 0,
      });
    },

    /**
     * Clear processing flag
     */
    clearProcessingFlag: function (couponCode) {
      const normalizedCode = couponCode.toUpperCase();
      this.processingQueue.delete(normalizedCode);
    },

    /**
     * Get currently applied coupons - optimized with cached selectors
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

      // Also check from cart data if available - use cached selectors
      if (
        window.wc_cart_fragments_params &&
        window.wc_cart_fragments_params.cart_hash
      ) {
        // Cache selectors to avoid repeated DOM queries
        const $couponElements = $(
          ".applied-coupon .coupon-code, .woocommerce-notices-wrapper .coupon-code"
        );

        // Use for...of loop instead of jQuery.each for better performance
        for (const element of $couponElements) {
          const code = element.textContent.trim();
          if (code && !appliedCoupons.includes(code)) {
            appliedCoupons.push(code);
          }
        }
      }

      return appliedCoupons.map((code) => code.toUpperCase());
    },

    /**
     * Apply coupon via AJAX (for cart/mini-cart)
     */
    applyCouponViaAjax: function (couponCode, options = {}) {
      const $couponForm = options.$form || jQuery(".mini-cart-coupon-form");
      const normalizedCode = couponCode.toUpperCase();

      // Show loading state
      if ($couponForm.length) {
        const $input = $couponForm.find(".coupon-code-input");
        const $button = $couponForm.find(".apply-coupon-btn");

        if ($input.length && $button.length) {
          $input.val("Loading...");
          $button
            .addClass("loading")
            .prop("disabled", true)
            .text("Applying...");
        }
      }

      // Apply coupon via AJAX
      const ajaxRequest = jQuery.ajax({
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
        timeout: 8000,
        success: (response) => {
          if (response.success) {
            this.clearProcessingFlag(normalizedCode);

            // Update fragments directly if available - optimized with for...of loop
            var frags =
              (response && response.fragments) ||
              (response && response.data && response.data.fragments);
            if (frags) {
              for (const [key, value] of Object.entries(frags)) {
                const $element = $(key);
                if ($element.length) {
                  $element.replaceWith(value);
                }
              }
            }

            // Clear loading state
            if ($couponForm.length) {
              $couponForm.find(".coupon-code-input").val("");
              $couponForm
                .find(".apply-coupon-btn")
                .removeClass("loading")
                .prop("disabled", false)
                .text("APPLY");
            }

            // Refresh cart fragments using unified CartManager
            CartManager.queueRefresh("update_checkout");
            CartManager.queueRefresh("wc_fragment_refresh");

            // Show success message
            if ($couponForm.length && options.showSuccessMessage !== false) {
              $couponForm.after(
                '<div class="coupon-message success">Coupon applied successfully!</div>'
              );
              setTimeout(() => jQuery(".coupon-message").fadeOut(), 3000);
            }

            if (options.onSuccess) options.onSuccess();
          } else {
            this.handleCouponError(
              normalizedCode,
              response,
              $couponForm,
              options
            );
          }
        },
        error: (jqXHR, textStatus, errorThrown) => {
          this.handleCouponError(
            normalizedCode,
            { data: this.getErrorMessage(textStatus) },
            $couponForm,
            options
          );
        },
        complete: () => {
          // Clean up after delay
          setTimeout(
            () => this.clearProcessingFlag(normalizedCode),
            this.clearDelay
          );
        },
      });
    },

    /**
     * Apply coupon elegantly (for checkout page)
     */
    applyCouponElegantly: function (couponCode, options = {}) {
      const $couponSection = options.$section || $(".coupon-section");
      const $applyBtn = $couponSection.find(".coupon-apply-btn");
      const $input = $couponSection.find(".coupon-input");
      const normalizedCode = couponCode.toUpperCase();

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
            const sanitizedCouponCode = couponCode.replace(/[<>\"'&]/g, "");
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
            this.clearProcessingFlag(normalizedCode);
            if (options.onSuccess) options.onSuccess();
          }, 1500);
        } catch (error) {
          this.handleCouponError(
            normalizedCode,
            { data: "An error occurred while applying the coupon" },
            $couponSection,
            options
          );
        }
      });
    },

    /**
     * Handle coupon application errors
     */
    handleCouponError: function (normalizedCode, response, $form, options) {
      this.clearProcessingFlag(normalizedCode);

      // Clear loading state
      if ($form.length) {
        $form.find("input").val("");
        $form
          .find("button")
          .removeClass("loading")
          .prop("disabled", false)
          .text("APPLY");
      }

      // Get error message
      const errorMsg = response.data || "Failed to apply coupon";
      const cleanErrorMsg = errorMsg.replace(/[<>\"'&]/g, "");

      // Check if this is a usage limit error
      const isUsageLimitError =
        cleanErrorMsg.toLowerCase().includes("usage limit") ||
        cleanErrorMsg.toLowerCase().includes("has been reached") ||
        cleanErrorMsg.toLowerCase().includes("maximum usage");

      // Show toast notification for coupon errors
      if (typeof ToastNotification !== "undefined") {
        if (isUsageLimitError) {
          // Show error toast for usage limit
          ToastNotification.error(cleanErrorMsg, {
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
          // Show regular error toast
          ToastNotification.error(cleanErrorMsg, {
            duration: 5000,
            position: "top-right",
          });
        }
      } else {
        // Fallback: Show inline error message if ToastNotification not available
        if ($form.length && options.showErrorMessage !== false) {
          $form.after(
            '<div class="coupon-message error">' + cleanErrorMsg + "</div>"
          );
          setTimeout(() => jQuery(".coupon-message").fadeOut(), 5000);
        }
      }

      if (options.onError) options.onError(errorMsg);
    },

    /**
     * Get user-friendly error message
     */
    getErrorMessage: function (textStatus) {
      const messages = {
        timeout: "Request timed out. Please check your connection.",
        abort: "Request was cancelled.",
        default: "Network error. Please try again.",
      };
      return messages[textStatus] || messages.default;
    },

    /**
     * Clean URL after coupon application
     */
    cleanUrlAfterCouponApplication: function (couponCode) {
      setTimeout(() => {
        try {
          if (window.history && window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete("coupon");

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
      }, this.clearDelay);
    },
  };

  /**
   * Toast Notification System - Unified notification system for the site
   * Optimized with queueing, deduplication, accessibility, and controls
   */
  const ToastNotification = {
    // Default configuration
    defaults: {
      duration: 3000,
      position: "top-right",
      type: "info",
      animation: "slideInRight",
      maxVisible: 3,
      pauseOnHover: true,
      showClose: false,
      dedupeWindowMs: 2000,
    },

    // Internal state
    _containers: new Map(), // position -> container element
    _queues: new Map(), // position -> [{ message, options }]
    _visibleCount: new Map(), // position -> number
    _recentMessages: new Map(), // message -> timestamp

    /**
     * Show a toast notification
     */
    show: function (message, options = {}) {
      const config = { ...this.defaults, ...options };

      // Dedupe identical messages shown within a short window
      const now = Date.now();
      const lastShown = this._recentMessages.get(message);
      if (lastShown && now - lastShown < config.dedupeWindowMs) {
        // If an identical toast is currently visible, bump its counter and reset timer
        const existing = this._findExistingToast(message, config.position);
        if (existing) {
          this._incrementCountBadge(existing);
          this._resetAutoRemove(existing);
          return existing;
        }
      }
      this._recentMessages.set(message, now);

      // If too many visible for this position, enqueue
      const visible = this._visibleCount.get(config.position) || 0;
      if (visible >= config.maxVisible) {
        const queue = this._queues.get(config.position) || [];
        queue.push({ message, options: config });
        this._queues.set(config.position, queue);
        return null;
      }

      const container = this._getContainer(config.position);
      const toast = this._createToastElement(message, config);
      container.appendChild(toast);

      // Track visible count
      this._visibleCount.set(config.position, visible + 1);

      // Animate in
      this._animateIn(toast, config);

      // Auto remove handling
      this._setAutoRemove(toast, config);

      return toast;
    },

    success: function (message, options = {}) {
      return this.show(message, { ...options, type: "success" });
    },
    error: function (message, options = {}) {
      return this.show(message, { ...options, type: "error" });
    },
    warning: function (message, options = {}) {
      return this.show(message, { ...options, type: "warning" });
    },
    info: function (message, options = {}) {
      return this.show(message, { ...options, type: "info" });
    },

    /** Create or get container for a position */
    _getContainer: function (position) {
      if (this._containers.has(position)) return this._containers.get(position);

      const container = document.createElement("div");
      container.className = `toast-container position-${position}`;
      container.setAttribute(
        "aria-live",
        position.includes("bottom") ? "polite" : "polite"
      );
      container.setAttribute("role", "status");
      container.style.cssText = this._getContainerStyles(position);

      document.body.appendChild(container);
      this._containers.set(position, container);
      return container;
    },

    /** Find existing toast with same message in a position */
    _findExistingToast: function (message, position) {
      const container = this._containers.get(position);
      if (!container) return null;
      return container.querySelector(
        `.toast-notification[data-message="${this._escapeAttr(message)}"]`
      );
    },

    /** Escape attribute value */
    _escapeAttr: function (str) {
      return String(str).replace(/"/g, "&quot;");
    },

    /** Create toast DOM element with controls and a11y */
    _createToastElement: function (message, config) {
      const toast = document.createElement("div");
      toast.className = `toast-notification toast-${config.type}`;
      toast.setAttribute("data-message", message);
      toast.setAttribute(
        "role",
        config.type === "error" || config.type === "warning"
          ? "alert"
          : "status"
      );
      toast.setAttribute(
        "aria-live",
        config.type === "error" || config.type === "warning"
          ? "assertive"
          : "polite"
      );
      toast.style.cssText = this._getToastStyles(config);
      toast.style.pointerEvents = "auto";

      // Message container
      const textEl = document.createElement("span");
      textEl.className = "toast-message";
      textEl.textContent = message;
      textEl.style.display = "inline-block";

      toast.appendChild(textEl);

      // Duplicate count badge
      const countEl = document.createElement("span");
      countEl.className = "toast-count";
      countEl.style.cssText = "margin-left:8px;font-weight:700;opacity:0.9;";
      countEl.hidden = true;
      toast.appendChild(countEl);

      // Pause on hover
      if (config.pauseOnHover) {
        toast.addEventListener("mouseenter", () =>
          this._pauseAutoRemove(toast)
        );
        toast.addEventListener("mouseleave", () =>
          this._resumeAutoRemove(toast)
        );
      }

      return toast;
    },

    /** Container fixed styles by position */
    _getContainerStyles: function (position) {
      const base = {
        position: "fixed",
        zIndex: "10000",
        display: "flex",
        flexDirection: "column",
        gap: "8px",
        pointerEvents: "none",
      };

      const pos = {
        "top-right": { top: "65px", right: "0px", alignItems: "flex-end" },
        "top-left": { top: "20px", left: "20px", alignItems: "flex-start" },
        "bottom-right": {
          bottom: "20px",
          right: "20px",
          alignItems: "flex-end",
        },
        "bottom-left": {
          bottom: "20px",
          left: "20px",
          alignItems: "flex-start",
        },
        "top-center": {
          top: "20px",
          left: "50%",
          transform: "translateX(-50%)",
        },
        "bottom-center": {
          bottom: "20px",
          left: "50%",
          transform: "translateX(-50%)",
        },
      }[position] || { top: "65px", right: "15px" };

      return Object.entries({ ...base, ...pos })
        .map(([k, v]) => `${k}: ${v}`)
        .join("; ");
    },

    /** Toast styles */
    _getToastStyles: function (config) {
      const prefersReducedMotion =
        window.matchMedia &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches;

      const base = {
        position: "relative",
        padding: "12px 16px",
        borderRadius: "8px",
        fontWeight: "600",
        fontSize: "14px",
        maxWidth: "340px",
        boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
        opacity: "0",
        transform: prefersReducedMotion ? "none" : "translateY(-6px)",
        transition: prefersReducedMotion
          ? "opacity 0.2s ease"
          : "all 0.3s ease",
      };

      const type = {
        success: { background: "#4CAF50", color: "#ffffff" },
        error: { background: "#f44336", color: "#ffffff" },
        warning: { background: "#ff9800", color: "#ffffff" },
        info: { background: "#2196F3", color: "#ffffff" },
      }[config.type] || { background: "#333", color: "#fff" };

      return Object.entries({ ...base, ...type })
        .map(([k, v]) => `${k}: ${v}`)
        .join("; ");
    },

    /** Animate in */
    _animateIn: function (toast, config) {
      // initial styles already set; just trigger to visible
      requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "none";
      });
    },

    /** Set auto remove with references for pause/resume */
    _setAutoRemove: function (toast, config) {
      const removeAt = Date.now() + config.duration;
      toast.__toastConfig = { config, removeAt };
      toast.__toastTimer = setTimeout(
        () => this._dismissToast(toast, config),
        config.duration
      );
    },

    _resetAutoRemove: function (toast) {
      if (!toast || !toast.__toastConfig) return;
      clearTimeout(toast.__toastTimer);
      this._setAutoRemove(toast, toast.__toastConfig.config);
    },

    _pauseAutoRemove: function (toast) {
      if (!toast || !toast.__toastConfig) return;
      clearTimeout(toast.__toastTimer);
      const remaining = toast.__toastConfig.removeAt - Date.now();
      toast.__toastConfig.remaining = Math.max(0, remaining);
    },

    _resumeAutoRemove: function (toast) {
      if (!toast || !toast.__toastConfig) return;
      const remaining =
        toast.__toastConfig.remaining || toast.__toastConfig.config.duration;
      toast.__toastTimer = setTimeout(
        () => this._dismissToast(toast, toast.__toastConfig.config),
        remaining
      );
    },

    /** Dismiss and handle queue */
    _dismissToast: function (toast, config) {
      if (!toast || toast.__closing) return;
      toast.__closing = true;
      toast.style.animation = "fadeOut 0.25s ease";
      setTimeout(() => {
        const parent = toast.parentElement;
        if (parent && parent.removeChild) parent.removeChild(toast);

        // Decrement visible count and show next from queue
        const pos = config.position;
        const current = this._visibleCount.get(pos) || 1;
        this._visibleCount.set(pos, Math.max(0, current - 1));

        const queue = this._queues.get(pos) || [];
        if (queue.length > 0) {
          const next = queue.shift();
          this._queues.set(pos, queue);
          this.show(next.message, next.options);
        }
      }, 250);
    },

    /** Increment duplicate counter */
    _incrementCountBadge: function (toast) {
      const badge = toast.querySelector(".toast-count");
      let count = parseInt(badge?.getAttribute("data-count") || "1", 10) + 1;
      badge.hidden = false;
      badge.setAttribute("data-count", String(count));
      badge.textContent = `(${count})`;
    },

    /**
     * Test function - can be called from console to test toast notifications
     * Example: ToastNotification.test()
     */
    test: function () {
      this.success("Success! This is a success message");
      setTimeout(() => this.error("Error! This is an error message"), 1000);
      setTimeout(
        () => this.warning("Warning! This is a warning message"),
        2000
      );
      setTimeout(() => this.info("Info! This is an info message"), 3000);
      setTimeout(
        () =>
          this.error(
            'Usage limit for coupon "swiss10" has been reached. Please remove this coupon code to continue.',
            { duration: 6000 }
          ),
        4000
      );
    },
  };

  // Scroll prevention utilities
  let scrollPosition = 0;

  function getScrollbarWidth() {
    // Create a temporary div to measure scrollbar width
    const outer = document.createElement("div");
    outer.style.visibility = "hidden";
    outer.style.overflow = "scroll";
    outer.style.msOverflowStyle = "scrollbar";
    outer.style.position = "absolute";
    outer.style.top = "-9999px";
    outer.style.width = "100px";
    outer.style.height = "100px";

    document.body.appendChild(outer);

    const inner = document.createElement("div");
    inner.style.width = "100%";
    inner.style.height = "200px";
    outer.appendChild(inner);

    // Use requestAnimationFrame to ensure DOM is ready before measuring
    return new Promise((resolve) => {
      requestAnimationFrame(() => {
        const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
        outer.parentNode.removeChild(outer);
        resolve(scrollbarWidth);
      });
    });
  }

  async function preventPageScroll() {
    // Only prevent scroll if not already locked
    if (document.body.classList.contains("scroll-locked")) {
      return;
    }

    // Store current scroll position
    scrollPosition = window.pageYOffset || document.documentElement.scrollTop;

    // Calculate scrollbar width to prevent content shift
    const scrollbarWidth = await getScrollbarWidth();

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

  // Smart lazy loading with intersection observer
  function initSmartLazyLoading() {
    // Check if browser supports Intersection Observer
    if ("IntersectionObserver" in window) {
      var lazyImages = document.querySelectorAll('img[loading="lazy"]');
      var isIOS = isIOSSafari();
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

              // If we control the src via data-src, preload then swap
              if (img.dataset.src) {
                // Add loading class for smooth transition only when swapping src
                img.classList.add("loading");
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
                // For native lazy-loading images (no data-src), do nothing to avoid
                // class toggles that can trigger unnecessary composites on iOS.
              }

              observer.unobserve(img);
            }
          });
        },
        {
          // Wider pre-load window on iOS to avoid decode-on-enter flicker
          rootMargin: isIOS ? "200px 0px" : "50px 0px",
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

      // Listen for changes with stored handler for cleanup
      window.reducedDataHandler = function (e) {
        if (e.matches) {
          document.documentElement.classList.add("reduced-data-mode");
        } else {
          document.documentElement.classList.remove("reduced-data-mode");
        }
      };

      reducedDataQuery.addListener(window.reducedDataHandler);
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

      // Listen for connection changes with stored handler for cleanup
      window.connectionChangeHandler = function () {
        if (
          connection.effectiveType === "slow-2g" ||
          connection.effectiveType === "2g"
        ) {
          document.documentElement.classList.add("slow-connection");
        } else {
          document.documentElement.classList.remove("slow-connection");
        }
      };

      connection.addEventListener("change", window.connectionChangeHandler);
    }
  }

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
          // Check if we're on checkout page - let checkout.js handle it
          if (isCheckoutPage) {
            return;
          }

          // Check if coupon is already applied
          var appliedCoupons = CouponManager.getAppliedCoupons();
          if (appliedCoupons.includes(couponCode.toUpperCase())) {
            return;
          }

          // Apply the coupon with a slight delay to ensure DOM is ready
          setTimeout(function () {
            CouponManager.applyCoupon(couponCode.trim(), {
              $form: jQuery(".mini-cart-coupon-form"),
              onSuccess: () =>
                CouponManager.cleanUrlAfterCouponApplication(couponCode.trim()),
            });
          }, 500);
        }
      } else {
        // Note: Legacy session-based coupon checking removed;
        // coupon persistence is now handled via cookies on the server-side
      }
    } catch (e) {
      // Ignore if URL API not available
    }

    // Add iOS Safari class for CSS-based fixes
    try {
      if (isIOSSafari()) {
        document.documentElement.classList.add("ios-safari-flicker-fix");
        var iosMajor = getIOSMajorVersion();
        if (iosMajor) {
          document.documentElement.classList.add("ios-" + iosMajor);
        }
      }
    } catch (e) {}

    // Keep mini cart and checkout in sync when coupons change anywhere
    // Listen for WooCommerce coupon events and refresh fragments accordingly
    try {
      $(document.body).on(
        "applied_coupon removed_coupon updated_checkout",
        function () {
          if (typeof CartManager !== "undefined") {
            CartManager.queueRefresh("wc_fragment_refresh");
          } else {
            // Fallback to WooCommerce event if CartManager is not available
            $(document.body).trigger("wc_fragment_refresh");
          }
        }
      );
    } catch (e) {
      // Silently ignore event binding errors
    }

    // Function to override browser alert to use toast notifications
    function overrideBrowserAlert() {
      // Store the original alert function for fallback
      const originalAlert = window.alert;

      // Override the alert function
      window.alert = function (message) {
        // Check if ToastNotification is available
        if (window.ToastNotification) {
          // Show as error toast instead of browser alert
          ToastNotification.error(message);
        } else {
          // Fallback to original alert if ToastNotification is not available
          originalAlert.call(window, message);
        }
      };

      // Also override console.error for better error handling
      const originalConsoleError = console.error;
      console.error = function (...args) {
        // Always call original console.error
        originalConsoleError.apply(console, args);
      };
    }

    // Non-critical initialization - deferred to next animation frame
    requestAnimationFrame(function () {
      initSmartLazyLoading();
      initImageQualityPreferences();
      initConnectionAwareImageLoading();

      // Check for server-side toast triggers
      if (window.primefitShowOutOfStockToast) {
        ToastNotification.error("This variation is currently out of stock.");
        delete window.primefitShowOutOfStockToast;
      }
    });
  });

  // Function to check for pending coupon from session
  // Now uses unified CouponManager for race condition prevention
  // Note: Legacy DOM-based coupon data elements are no longer used;
  // coupon persistence is now handled via cookies on the server-side

  // Header: add scrolled state to force black background on sticky
  function initHeaderScroll() {
    // Use a more robust selector and check multiple times
    const checkForHeader = () => {
      const $header = $(".site-header");
      if ($header.length) {
        const toggleScrolled = () => {
          if (window.scrollY > 10) {
            $header.addClass("is-scrolled");
          } else {
            $header.removeClass("is-scrolled");
          }
        };

        // Initialize immediately
        toggleScrolled();

        // Add scroll listener with throttling for better performance
        let scrollTimeout;
        $(window).on("scroll", function () {
          if (scrollTimeout) {
            clearTimeout(scrollTimeout);
          }
          scrollTimeout = setTimeout(toggleScrolled, 1);
        });

        return true; // Header found and initialized
      }
      return false; // Header not found yet
    };

    // Try to find header immediately
    if (!checkForHeader()) {
      // If header not found, try again after a short delay
      setTimeout(checkForHeader, 100);
      setTimeout(checkForHeader, 500);
    }
  }

  // Expose function globally for inline script
  window.initHeaderScroll = initHeaderScroll;

  // Initialize header scroll when DOM is ready or when jQuery is available
  if (typeof jQuery !== "undefined") {
    $(document).ready(function () {
      initHeaderScroll();
      initPromoBarScroll();
      initWhatsAppButtonScroll();
    });
  } else {
    // Fallback if jQuery is not loaded yet
    document.addEventListener("DOMContentLoaded", function () {
      if (typeof jQuery !== "undefined") {
        $(document).ready(function () {
          initHeaderScroll();
          initPromoBarScroll();
          initWhatsAppButtonScroll();
        });
      } else {
        // Last resort - use vanilla JavaScript
        initHeaderScrollVanilla();
        initPromoBarScrollVanilla();
        initWhatsAppButtonScroll();
      }
    });
  }

  // Expose vanilla function globally as well
  window.initHeaderScrollVanilla = initHeaderScrollVanilla;

  // Promo Bar: add scrolled state for sticky behavior
  function initPromoBarScroll() {
    // Use a more robust selector and check multiple times
    const checkForPromoBar = () => {
      const $promoBar = $(".promo-bar");
      if ($promoBar.length) {
        const toggleScrolled = () => {
          if (window.scrollY > 10) {
            $promoBar.addClass("is-scrolled");
          } else {
            $promoBar.removeClass("is-scrolled");
          }
        };

        // Initialize immediately
        toggleScrolled();

        // Add scroll listener with throttling for better performance
        let scrollTimeout;
        $(window).on("scroll", function () {
          if (scrollTimeout) {
            clearTimeout(scrollTimeout);
          }
          scrollTimeout = setTimeout(toggleScrolled, 1);
        });

        return true; // Promo bar found and initialized
      }
      return false; // Promo bar not found yet
    };

    // Try to find promo bar immediately
    if (!checkForPromoBar()) {
      // If promo bar not found, try again after a short delay
      setTimeout(checkForPromoBar, 100);
      setTimeout(checkForPromoBar, 500);
    }
  }

  // Expose function globally for inline script
  window.initPromoBarScroll = initPromoBarScroll;

  // Vanilla JavaScript fallback for header scroll
  function initHeaderScrollVanilla() {
    const checkForHeader = () => {
      const header = document.querySelector(".site-header");
      if (header) {
        const toggleScrolled = () => {
          if (window.scrollY > 10) {
            header.classList.add("is-scrolled");
          } else {
            header.classList.remove("is-scrolled");
          }
        };

        // Initialize immediately
        toggleScrolled();

        // Add scroll listener
        let scrollTimeout;
        window.addEventListener("scroll", function () {
          if (scrollTimeout) {
            clearTimeout(scrollTimeout);
          }
          scrollTimeout = setTimeout(toggleScrolled, 1);
        });

        return true; // Header found and initialized
      }
      return false; // Header not found yet
    };

    // Try to find header immediately
    if (!checkForHeader()) {
      // If header not found, try again after delays
      setTimeout(checkForHeader, 100);
      setTimeout(checkForHeader, 500);
    }
  }

  // Vanilla JavaScript fallback for promo bar scroll
  function initPromoBarScrollVanilla() {
    const checkForPromoBar = () => {
      const promoBar = document.querySelector(".promo-bar");
      if (promoBar) {
        const toggleScrolled = () => {
          if (window.scrollY > 10) {
            promoBar.classList.add("is-scrolled");
          } else {
            promoBar.classList.remove("is-scrolled");
          }
        };

        // Initialize immediately
        toggleScrolled();

        // Add scroll listener
        let scrollTimeout;
        window.addEventListener("scroll", function () {
          if (scrollTimeout) {
            clearTimeout(scrollTimeout);
          }
          scrollTimeout = setTimeout(toggleScrolled, 1);
        });

        return true; // Promo bar found and initialized
      }
      return false; // Promo bar not found yet
    };

    // Try to find promo bar immediately
    if (!checkForPromoBar()) {
      // If promo bar not found, try again after delays
      setTimeout(checkForPromoBar, 100);
      setTimeout(checkForPromoBar, 500);
    }
  }

  // Expose vanilla function globally as well
  window.initPromoBarScrollVanilla = initPromoBarScrollVanilla;

  // WhatsApp button: show after 100px scroll
  // Skip on product pages - button should be visible normally
  function initWhatsAppButtonScroll() {
    // Don't apply scroll behavior on product pages
    if (document.body.classList.contains("single-product")) {
      return;
    }

    const checkForWhatsAppButton = () => {
      const whatsappButton = document.querySelector(
        ".qlwapp__button.qlwapp__button--bubble"
      );
      if (whatsappButton) {
        const toggleVisibility = () => {
          if (window.scrollY > 100) {
            whatsappButton.classList.add("is-visible");
          } else {
            whatsappButton.classList.remove("is-visible");
          }
        };

        // Initialize immediately
        toggleVisibility();

        // Add scroll listener with throttling for better performance
        let scrollTimeout;
        window.addEventListener("scroll", function () {
          if (scrollTimeout) {
            clearTimeout(scrollTimeout);
          }
          scrollTimeout = setTimeout(toggleVisibility, 1);
        }, { passive: true });

        return true; // WhatsApp button found and initialized
      }
      return false; // WhatsApp button not found yet
    };

    // Try to find WhatsApp button immediately
    if (!checkForWhatsAppButton()) {
      // If button not found, try again after delays
      setTimeout(checkForWhatsAppButton, 100);
      setTimeout(checkForWhatsAppButton, 500);
      setTimeout(checkForWhatsAppButton, 1000);
    }
  }

  // Expose function globally for inline script
  window.initWhatsAppButtonScroll = initWhatsAppButtonScroll;

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
      preventPageScroll().catch(() => {});
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

        // Toggle the current submenu
        if (isOpen) {
          $parent.removeClass("mobile-submenu-open");
        } else {
          $parent.addClass("mobile-submenu-open");
        }
      }
    }
  );

  /**
   * Expose CartManager, CouponManager, and ToastNotification globally
   */
  window.CartManager = CartManager;
  window.CouponManager = CouponManager;
  window.ToastNotification = ToastNotification;
  window.preventPageScroll = preventPageScroll;
  window.allowPageScroll = allowPageScroll;
})(jQuery);
