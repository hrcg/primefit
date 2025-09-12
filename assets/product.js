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
    // Handle mobile tap to show/hide sizes
    $(document).on(
      "click",
      ".woocommerce ul.products li.product .product-image-container",
      function (e) {
        if (window.innerWidth <= 768) {
          var $product = $(this).closest(".product");
          var $overlay = $product.find(".product-size-overlay");

          if ($overlay.length > 0) {
            e.preventDefault();
            e.stopPropagation(); // Prevent the product link from being triggered
            $product.toggleClass("show-sizes");

            // Close other open size overlays
            $(".woocommerce ul.products li.product")
              .not($product)
              .removeClass("show-sizes");
          }
        }
      }
    );

    // Handle size option clicks
    $(document).on("click", ".product-size-overlay .size-option", function (e) {
      e.preventDefault();
      e.stopPropagation();

      var url = $(this).attr("href");
      if (url) {
        window.location.href = url;
      }
    });

    // Close size overlays when clicking outside on mobile
    $(document).on("click", function (e) {
      if (window.innerWidth <= 480) {
        if (
          !$(e.target).closest(
            ".product-image-container, .product-size-overlay"
          ).length
        ) {
          $(".woocommerce ul.products li.product").removeClass("show-sizes");
        }
      }
    });

    // Handle keyboard navigation for accessibility
    $(document).on("keydown", ".size-option", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        $(this).click();
      }
    });
  }

  // Hide tap indicator for products without size overlays
  function initTapIndicators() {
    if (window.innerWidth <= 768) {
      $(".woocommerce ul.products li.product").each(function () {
        var $product = $(this);
        var $overlay = $product.find(".product-size-overlay");

        if ($overlay.length === 0) {
          $product.addClass("no-size-overlay");
        }
      });
    }
  }

  $(function () {
    enhanceVariationSwatches();
    initProductLoopSizeSelection();
    initTapIndicators();

    // Re-initialize on window resize
    $(window).on("resize", function () {
      initTapIndicators();
    });
  });
})(jQuery);
