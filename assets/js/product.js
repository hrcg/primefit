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

  // Shop Filter Bar functionality
  function initShopFilterBar() {
    // Grid toggle functionality
    $('.grid-option').on('click', function(e) {
      e.preventDefault();
      
      var $btn = $(this);
      var gridType = $btn.data('grid');
      var $products = $('.woocommerce ul.products');
      
      // Update active state
      $('.grid-option').removeClass('active');
      $btn.addClass('active');
      
      // Remove existing grid classes
      $products.removeClass('grid-2 grid-3 grid-4');
      
      // Add new grid class
      if (gridType) {
        $products.addClass('grid-' + gridType);
      }
      
      // Store preference in cookie
      setCookie('primefit_grid_view', gridType, 30); // 30 days
    });
    
    // Sort dropdown change
    $('.woocommerce-ordering .orderby').on('change', function() {
      $(this).closest('form').submit();
    });
    
    // Initialize grid view from cookie
    var savedGrid = getCookie('primefit_grid_view');
    if (savedGrid) {
      $('.grid-option[data-grid="' + savedGrid + '"]').addClass('active');
      $('.woocommerce ul.products').removeClass('grid-2 grid-3 grid-4').addClass('grid-' + savedGrid);
    } else {
      // Default to 3 column grid
      $('.grid-option[data-grid="3"]').addClass('active');
      $('.woocommerce ul.products').addClass('grid-3');
    }
  }
  
  // Cookie utility functions
  function setCookie(name, value, days) {
    var expires = "";
    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
      expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
  }
  
  function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') c = c.substring(1, c.length);
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
