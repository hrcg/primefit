(function ($) {
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

  // Convert select options into clickable swatches (basic output)
  function enhanceVariationSwatches() {
    $(".variations_form select").each(function () {
      var $select = $(this);
      if ($select.data("pf-enhanced")) return;
      $select.data("pf-enhanced", true);
      var attrName = $select.attr("name");
      var $wrap = $(
        '<div class="pf-swatches" data-attr="' + attrName + '"></div>'
      );
      $select.find("option").each(function () {
        var val = $(this).val();
        if (!val) return;
        var label = $(this).text();
        var $btn = $(
          '<button type="button" class="pf-swatch" data-value="' +
            val +
            '">' +
            label +
            "</button>"
        );
        if ($select.val() === val) {
          $btn.addClass("active");
        }
        $wrap.append($btn);
      });
      $select.hide().after($wrap);
    });
  }

  $(document).on("click", ".pf-swatch", function () {
    var $btn = $(this);
    var value = $btn.data("value");
    var $wrap = $btn.closest(".pf-swatches");
    var attr = $wrap.data("attr");
    var $select = $('select[name="' + attr + '"]').first();
    $wrap.find(".pf-swatch").removeClass("active");
    $btn.addClass("active");
    $select.val(value).trigger("change");
  });

  $(document).on(
    "found_variation",
    ".variations_form",
    function (evt, variation) {
      swapMainImageByVariationImage(variation);
    }
  );

  // Handle size selection in product loops
  function initProductLoopSizeSelection() {
    // Disable mobile size option showing - let users navigate to product page instead
    // Mobile users will click to go to product page rather than showing size options

    // Note: Size options are hidden on mobile via CSS, and clicking the product
    // will navigate to the product page where users can select sizes properly

    // Handle size option clicks - add to cart functionality
    $(document).on("click", ".product-size-options .size-option", function (e) {
      e.preventDefault();
      e.stopPropagation();

      var $button = $(this);
      var variationId = $button.data("variation-id");
      var productId = $button.data("product-id");
      var size = $button.data("size");
      var isInStock = $button.data("is-in-stock") === "true";

      // Check if we have valid data for adding to cart
      if (!variationId || variationId === 0 || !productId) {
        console.error("Missing variation or product ID", {
          variationId,
          productId,
          isInStock,
        });
        // Redirect to product page for unavailable sizes
        var productUrl = $button.closest(".product").find("a").attr("href");
        if (productUrl) {
          window.location.href = productUrl;
        }
        return;
      }

      // Add loading state
      $button.addClass("loading").text("...");

      // Prepare AJAX data
      const ajaxData = {
        action: "wc_ajax_add_to_cart",
        product_id: productId,
        variation_id: variationId,
        quantity: 1,
        security:
          (window.primefit_cart_params &&
            window.primefit_cart_params.add_to_cart_nonce) ||
          (window.wc_add_to_cart_params &&
            window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce),
      };

      // Add to cart via AJAX
      $.ajax({
        url:
          (window.primefit_cart_params &&
            window.primefit_cart_params.ajax_url) ||
          (window.wc_add_to_cart_params &&
            window.wc_add_to_cart_params.ajax_url) ||
          "/wp-admin/admin-ajax.php",
        type: "POST",
        data: ajaxData,
        success: function (response) {
          if (response.error && response.product_url) {
            // If there's an error, redirect to product page
            window.location.href = response.product_url;
          } else {
            // Success - trigger cart update
            $(document.body).trigger("added_to_cart", [
              response.fragments,
              response.cart_hash,
              $button,
            ]);

            // Show success feedback
            $button.removeClass("loading").text("✓");
            setTimeout(function () {
              $button.text(size.toUpperCase());
            }, 1500);
          }
        },
        error: function (xhr, status, error) {
          // Remove loading state
          $button.removeClass("loading").text(size.toUpperCase());

          // On error, redirect to product page
          var productUrl = $button.closest(".product").find("a").attr("href");
          if (productUrl) {
            window.location.href = productUrl;
          }
        },
      });
    });

    // Size options are disabled on mobile - users navigate to product page instead

    // Handle keyboard navigation for accessibility
    $(document).on("keydown", ".size-option", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        $(this).click();
      }
    });
  }

  // Hide tap indicator for products without size options
  function initTapIndicators() {
    $(".woocommerce ul.products li.product").each(function () {
      var $product = $(this);
      var $sizeOptions = $product.find(".product-size-options");

      if ($sizeOptions.length === 0) {
        $product.addClass("no-size-options");
      }
    });
  }

  // Shop Filter Bar functionality
  function initShopFilterBar() {
    // Sort dropdown change
    $(".woocommerce-ordering .orderby").on("change", function () {
      $(this).closest("form").submit();
    });
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

    // Re-initialize on window resize
    $(window).on("resize", function () {
      initTapIndicators();
    });
  });
})(jQuery);
