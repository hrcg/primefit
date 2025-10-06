(function ($) {
  // Store references for cleanup
  const productCleanupHandlers = {
    eventHandlers: new Map(),
    timeouts: new Set(),

    /**
     * Cleanup method to prevent memory leaks
     */
    cleanup: function() {
      // Remove all event listeners
      this.eventHandlers.forEach((handler, selector) => {
        $(document).off("click", selector, handler);
        $(document).off("keydown", selector, handler);
        $(document).off("found_variation", selector, handler);
      });
      this.eventHandlers.clear();

      // Clear all timeouts
      this.timeouts.forEach(timeout => {
        clearTimeout(timeout);
      });
      this.timeouts.clear();

      // Remove window resize handler
      $(window).off("resize");
    }
  };

  function swapMainImageByVariationImage(variation) {
    if (!variation || !variation.image || !variation.image.src) return;
    var $gallery = $(document).find(".woocommerce-product-gallery");
    var $img = $gallery.find(".woocommerce-product-gallery__image img").first();
    if ($img.length) {
      $img.attr("src", variation.image.src);
      if (variation.image.srcset) {
        $img.attr("srcset", variation.image.srcset);
      }
      if (variation.image.sizes) {
        $img.attr("sizes", variation.image.sizes);
      }
    }
  }

  // Convert select options into clickable swatches (basic output) - optimized
  function enhanceVariationSwatches() {
    const $selects = $(".variations_form select");

    for (const element of $selects) {
      const $select = $(element);
      if ($select.data("pf-enhanced")) continue;
      $select.data("pf-enhanced", true);

      const attrName = $select.attr("name");
      const $wrap = $(`<div class="pf-swatches" data-attr="${attrName}"></div>`);

      const $options = $select.find("option");
      const currentValue = $select.val();

      for (const optionElement of $options) {
        const $option = $(optionElement);
        const val = $option.val();
        if (!val) continue;

        const label = $option.text();
        const $btn = $(`<button type="button" class="pf-swatch" data-value="${val}">${label}</button>`);

        if (currentValue === val) {
          $btn.addClass("active");
        }
        $wrap.append($btn);
      }

      $select.hide().after($wrap);
    }
  }

  const swatchClickHandler = function () {
    var $btn = $(this);
    var value = $btn.data("value");
    var $wrap = $btn.closest(".pf-swatches");
    var attr = $wrap.data("attr");
    var $select = $('select[name="' + attr + '"]').first();
    $wrap.find(".pf-swatch").removeClass("active");
    $btn.addClass("active");
    $select.val(value).trigger("change");
  };

  $(document).on("click", ".pf-swatch", swatchClickHandler);
  productCleanupHandlers.eventHandlers.set(".pf-swatch", swatchClickHandler);

  const foundVariationHandler = function (evt, variation) {
    swapMainImageByVariationImage(variation);
  };

  $(document).on("found_variation", ".variations_form", foundVariationHandler);
  productCleanupHandlers.eventHandlers.set(".variations_form", foundVariationHandler);

  // Handle size selection in product loops
  function initProductLoopSizeSelection() {
    // Disable mobile size option showing - let users navigate to product page instead
    // Mobile users will click to go to product page rather than showing size options

    // Note: Size options are hidden on mobile via CSS, and clicking the product
    // will navigate to the product page where users can select sizes properly

    // Size option clicks are handled by app.js to avoid conflicts
    // The app.js handler properly opens the mini cart instead of redirecting

    // Size options are disabled on mobile - users navigate to product page instead

    // Handle keyboard navigation for accessibility
    const sizeOptionKeydownHandler = function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        $(this).click();
      }
    };

    $(document).on("keydown", ".size-option", sizeOptionKeydownHandler);
    productCleanupHandlers.eventHandlers.set(".size-option", sizeOptionKeydownHandler);
  }

  // Hide tap indicator for products without size options - optimized
  function initTapIndicators() {
    const $products = $(".woocommerce ul.products li.product");

    for (const element of $products) {
      const $product = $(element);
      const $sizeOptions = $product.find(".product-size-options");

      if ($sizeOptions.length === 0) {
        $product.addClass("no-size-options");
      }
    }
  }

  // Shop Filter Bar functionality
  function initShopFilterBar() {
    // Sort dropdown change
    const shopFilterHandler = function () {
      $(this).closest("form").submit();
    };

    $(".woocommerce-ordering .orderby").on("change", shopFilterHandler);
    productCleanupHandlers.eventHandlers.set(".woocommerce-ordering .orderby", shopFilterHandler);
  }

  // Cookie utility functions
  function setCookie(name, value, days) {
    var expires = "";
    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
  }

  function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(";");
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == " ") c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  }

  $(function () {
    enhanceVariationSwatches();
    initProductLoopSizeSelection();
    initTapIndicators();
    initShopFilterBar();

    // Re-initialize on window resize with stored handler for cleanup
    const resizeHandler = function () {
      initTapIndicators();
    };

    $(window).on("resize", resizeHandler);
    productCleanupHandlers.eventHandlers.set("window-resize", resizeHandler);

    // Add cleanup on page unload to prevent memory leaks
    window.addEventListener("beforeunload", () => {
      productCleanupHandlers.cleanup();
    });
  });
})(jQuery);
