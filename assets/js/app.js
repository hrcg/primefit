/**
 * PrimeFit Theme - Main App JavaScript
 * Essential initialization only - functionality moved to modules
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // This file now only contains essential initialization
  // Core functionality has been moved to separate modules:
  // - core.js: CartManager, CouponManager, scroll utilities, lazy loading
  // - cart.js: Cart-specific functionality
  // - shop.js: Shop filters, product interactions
  // - mega-menu.js: Mega menu functionality
  // - hero-video.js: Hero video backgrounds

  // Essential initialization only - most functionality moved to modules
  $(function () {
    // URL parameter cleanup for add-to-cart
    try {
      var url = new URL(window.location.href);
      if (
        url.searchParams.has("add-to-cart") ||
        url.searchParams.has("added-to-cart")
      ) {
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
    } catch (e) {
      // Ignore if URL API not available
    }

    // Header back button logic (for single product pages)
    var backBtn = document.querySelector(".header-back-button");
    if (backBtn) {
      backBtn.addEventListener("click", function (e) {
        e.preventDefault();
        var homeUrl = backBtn.getAttribute("data-home-url") || "/";
        var ref = document.referrer;
        var isSameOriginReferrer = false;
        if (ref) {
          try {
            var refUrl = new URL(ref);
            isSameOriginReferrer = refUrl.origin === window.location.origin;
          } catch (err) {
            isSameOriginReferrer = false;
          }
        }

        if (isSameOriginReferrer) {
          window.history.back();
        } else {
          window.location.href = homeUrl;
        }
      });
    }
  });
})(jQuery);
