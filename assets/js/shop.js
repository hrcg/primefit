/**
 * PrimeFit Theme - Shop Module
 * Shop-specific functionality including filters, grid options, and product interactions
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Shop Filter Bar Controller
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
        if (typeof window.allowPageScroll === "function") {
          window.allowPageScroll();
        }
      } else {
        $dropdown.addClass("open");
        // Add body class and prevent scroll on mobile when opening
        if (this.isMobile) {
          document.body.classList.add("filter-dropdown-open");
          if (typeof window.preventPageScroll === "function") {
            window.preventPageScroll().catch(console.error);
          }
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
      if (typeof window.allowPageScroll === "function") {
        window.allowPageScroll();
      }
      this.applyFilter(filterValue);
    }

    handleOutsideClick(event) {
      const $target = $(event.target);
      if (!$target.closest(".filter-dropdown").length) {
        $(".filter-dropdown").removeClass("open");
        // Remove body class and restore scroll when closing dropdown
        document.body.classList.remove("filter-dropdown-open");
        if (typeof window.allowPageScroll === "function") {
          window.allowPageScroll();
        }
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

  // Product Loop Color Swatches Functionality
  $(document).ready(function () {
    // Handle color swatch clicks in product loops
    $(document).on(
      "click",
      ".product-loop-color-swatches .color-swatch",
      function (e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent triggering the product link

        const $swatch = $(this);
        const $productContainer = $swatch.closest(".product");
        const $imageContainer = $productContainer.find(
          ".product-image-container"
        );
        const $mainImage = $imageContainer
          .find(".attachment-woocommerce_thumbnail, img")
          .first();
        const $secondImage = $imageContainer.find(".product-second-image");
        const variationImage = $swatch.data("variation-image");
        const selectedColor = $swatch.data("color");

        // If no main image found, try to find any img element in the product container
        if ($mainImage.length === 0) {
          const $fallbackImage = $productContainer.find("img").first();
          if ($fallbackImage.length > 0) {
            $mainImage = $fallbackImage;
          }
        }

        // Update active state
        $productContainer.find(".color-swatch").removeClass("active");
        $swatch.addClass("active");

        // If there's a variation image, update the main image
        if (variationImage && variationImage !== "" && $mainImage.length > 0) {
          // Add a class to prevent hover effects from interfering
          $productContainer.addClass("color-swatch-active");

          // Update the main image directly
          const oldSrc = $mainImage.attr("src");
          const oldSrcset = $mainImage.attr("srcset");

          // Add cache busting parameter to prevent browser caching
          const cacheBustedImage = variationImage.includes("?")
            ? variationImage + "&t=" + Date.now()
            : variationImage + "?t=" + Date.now();

          // Update both src and srcset attributes
          $mainImage.attr("src", cacheBustedImage);
          $mainImage.removeAttr("srcset"); // Remove srcset to force browser to use src

          // Hide the second image to prevent hover conflicts
          if ($secondImage.length > 0) {
            $secondImage.css("opacity", "0");
          }
        } else if (variationImage && variationImage !== "") {
          // Fallback: try to find and update any image in the product container
          const $anyImage = $productContainer.find("img").first();
          if ($anyImage.length > 0) {
            const oldSrc = $anyImage.attr("src");
            const oldSrcset = $anyImage.attr("srcset");
            // Add cache busting parameter to prevent browser caching
            const cacheBustedImage = variationImage.includes("?")
              ? variationImage + "&t=" + Date.now()
              : variationImage + "?t=" + Date.now();
            $anyImage.attr("src", cacheBustedImage);
            $anyImage.removeAttr("srcset"); // Remove srcset to force browser to use src
            $productContainer.addClass("color-swatch-active");
          }
        }

        // Update size options based on selected color (safely wrapped in try-catch)
        try {
          updateSizeOptionsForColor($productContainer, selectedColor);
        } catch (error) {
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
          const variationsData = $sizeOption
            .closest(".product-size-options")
            .data("variations");
          if (variationsData) {
            // Get the first available color from variations data
            let defaultColor = "";
            for (const variationId in variationsData) {
              const variation = variationsData[variationId];
              if (variation.color) {
                defaultColor = variation.color;
                break;
              }
            }

            if (defaultColor) {
              // Create a temporary color swatch element for single color products
              const $tempColorSwatch = $(
                '<div class="temp-color-swatch" data-color="' +
                  defaultColor +
                  '"></div>'
              );
              addVariationToCart(
                $productContainer,
                $tempColorSwatch,
                $sizeOption
              );
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
      const $colorSwatches = $productContainer.find(
        ".product-loop-color-swatches"
      );

      // If there are no color swatches but there are size options, initialize with default color
      if (
        $colorSwatches.length === 0 ||
        $colorSwatches.find(".color-swatch").length === 0
      ) {
        const variationsData = $sizeOptions.data("variations");
        if (variationsData) {
          // Get the first available color from variations data
          let defaultColor = "";
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

  /**
   * Add variation to cart via AJAX - simulates clicking the add to cart button
   */
  function addVariationToCart($productContainer, $colorSwatch, $sizeOption) {
    const variationId = $sizeOption.data("variation-id");
    const productId = $sizeOption.data("product-id");
    const colorValue = $colorSwatch.data("color");
    const sizeValue = $sizeOption.data("size");

    if (!variationId || !productId || variationId === 0) {
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

    // Make AJAX request using WooCommerce's standard approach
    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: formData,
      dataType: "json",
      timeout: 8000,
      cache: false,
      success: function (response) {
        // Handle the response exactly like WooCommerce does
        if (response.error && response.product_url) {
          // This might be a redirect response, check if product was actually added
          checkCartAfterAdd($productContainer, $sizeOption, $colorSwatch);
        } else if (response.error) {
          // Show error message
          showCartFeedback(
            $productContainer,
            "Error adding to cart: " + (response.error || "Unknown error"),
            "error"
          );
        } else {
          // Success - update cart fragments

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

            // Open mini cart immediately after fragments are updated
            // Use requestAnimationFrame to ensure DOM updates are complete
            requestAnimationFrame(function () {
              if (typeof window.openCart === "function") {
                window.openCart();
              }
            });
          } else {
            // Fallback: open cart with small delay if no fragments
            setTimeout(function () {
              if (typeof window.openCart === "function") {
                window.openCart();
              }
            }, 100);
          }
        }
      },
      error: function (xhr, status, error) {
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

    // Trigger cart fragment refresh using unified CartManager
    if (typeof window.CartManager !== "undefined") {
      window.CartManager.queueRefresh("wc_fragment_refresh");
    }

    // Open mini cart after fragments are refreshed
    // Use a shorter delay since CartManager handles the refresh timing
    setTimeout(function () {
      if (typeof window.openCart === "function") {
        window.openCart();
      }
    }, 200);
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

  // Initialize shop filter controller if grid options exist
  if ($(".grid-option").length > 0) {
    const shopFilterController = new ShopFilterController();
  }
})(jQuery);
