/* global jQuery, primefitBundleData */
(function ($) {
  "use strict";

  function formatMoney(amount) {
    try {
      const data = window.primefitBundleData;
      if (!data) return "—";

      const n = Number(amount);
      if (Number.isNaN(n) || n < 0) return "—";

      const symbol = data.currencySymbol || "";
      const decimals = parseInt(data.priceDecimals, 10) || 2;
      const decimalSep = data.priceDecimalSep || ".";
      const thousandSep = data.priceThousandSep || ",";
      const position = data.currencyPosition || "left";

      // Format number with decimals
      const fixed = n.toFixed(decimals);
      const parts = fixed.split(".");
      let integerPart = parts[0];

      // Apply thousand separator if needed
      if (thousandSep && thousandSep !== "") {
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
      }

      const decimalPart = parts[1] || "";
      let formattedNumber = integerPart;
      if (decimals > 0 && decimalPart) {
        formattedNumber += decimalSep + decimalPart;
      }

      // Apply currency position
      if (position === "right" || position === "right_space") {
        return (
          formattedNumber + (position === "right_space" ? " " : "") + symbol
        );
      } else if (position === "left_space") {
        return symbol + " " + formattedNumber;
      } else {
        // Default: left (no space)
        return symbol + formattedNumber;
      }
    } catch (e) {
      console.error("Error formatting money:", e);
      return "—";
    }
  }

  function initBundle() {
    const data = window.primefitBundleData;
    if (!data || !data.items || !data.items.length) return;

    const $form = $(".primefit-bundle-form").first();
    if (!$form.length) return;

    const $submit = $form.find("[data-primefit-bundle-submit]").first();
    const $itemsTotal = $form.find("[data-items-total]").first();
    const $savings = $form.find("[data-savings]").first();
    const bundleQty = 1; // Bundles are always quantity 1

    const state = {
      selections: {}, // itemKey => { productId, sizeKey, variationId, regularPrice }
    };

    function getItem(itemKey) {
      return data.items.find((i) => i.key === itemKey);
    }

    function getProduct(itemKey, productId) {
      const item = getItem(itemKey);
      if (!item) return null;
      return (
        item.products.find((p) => Number(p.id) === Number(productId)) || null
      );
    }

    function isVariableProduct(product) {
      return !!(product && product.is_variable);
    }

    function hasAnyInStockOption(product) {
      if (!product || !product.sizes) return false;
      const sizeEntries = Object.entries(product.sizes);
      if (!sizeEntries.length) return false;
      return sizeEntries.some(([, info]) => !!(info && info.in_stock));
    }

    function getProductPrice(product) {
      // Get price from first available size (all sizes have same price)
      if (!product) return 0;
      if (product && typeof product.regular_price !== "undefined") {
        const rp = Number(product.regular_price || 0);
        if (!Number.isNaN(rp) && rp > 0) return rp;
      }
      if (!product.sizes) return 0;
      const sizeKeys = Object.keys(product.sizes);
      if (sizeKeys.length === 0) return 0;
      const firstSize = product.sizes[sizeKeys[0]];
      return Number(firstSize.regular_price || firstSize.price || 0);
    }

    function setHiddenInputs($itemEl, productId, variationId) {
      $itemEl.find("[data-item-product-input]").val(String(productId || ""));
      $itemEl.find("[data-item-variation-input]").val(String(variationId || 0));
    }

    function sortSizes(sizeEntries) {
      // Define standard size order
      const sizeOrder = {
        'xxs': 1, '2xs': 1,
        'xs': 2,
        's': 3, 'small': 3,
        'm': 4, 'medium': 4,
        'l': 5, 'large': 5,
        'xl': 6,
        'xxl': 7, '2xl': 7,
        '3xl': 8, 'xxxl': 8,
        '4xl': 9,
        '5xl': 10,
        '6xl': 11,
        'one-size': 99
      };

      return sizeEntries.sort(([sizeA], [sizeB]) => {
        const a = String(sizeA).toLowerCase().trim();
        const b = String(sizeB).toLowerCase().trim();

        // Check if sizes are in the standard order map
        const orderA = sizeOrder[a];
        const orderB = sizeOrder[b];

        if (orderA !== undefined && orderB !== undefined) {
          return orderA - orderB;
        }
        if (orderA !== undefined) return -1;
        if (orderB !== undefined) return 1;

        // Try parsing as numbers (for numeric sizes like 38, 40, 42)
        const numA = parseFloat(a);
        const numB = parseFloat(b);
        if (!isNaN(numA) && !isNaN(numB)) {
          return numA - numB;
        }

        // Fall back to alphabetical
        return a.localeCompare(b);
      });
    }

    function renderSizes($itemEl, itemKey, productId) {
      const product = getProduct(itemKey, productId);
      const $sizes = $itemEl.find("[data-item-sizes]").first();
      $sizes.empty();

      if (!product || !product.sizes) {
        $sizes.append(
          '<button type="button" class="size-option unavailable" disabled>Select color first</button>'
        );
        return;
      }

      const sizeEntries = Object.entries(product.sizes);
      if (!sizeEntries.length) {
        $sizes.append(
          '<button type="button" class="size-option unavailable" disabled>No sizes</button>'
        );
        return;
      }

      // Sort sizes from smallest to largest
      const sortedSizes = sortSizes(sizeEntries);

      sortedSizes.forEach(([sizeKey, info]) => {
        const inStock = !!info.in_stock;
        const variationId = Number(info.variation_id || 0);
        const regular = Number(info.regular_price || 0);

        const $btn = $(
          '<button type="button" class="size-option primefit-bundle-size" />'
        );
        $btn.attr("data-size-key", sizeKey);
        $btn.attr("data-variation-id", String(variationId));
        $btn.attr("data-regular-price", String(regular));
        $btn.prop("disabled", !inStock);
        $btn.text(String(sizeKey).toUpperCase());
        if (!inStock) $btn.addClass("unavailable");

        $sizes.append($btn);
      });
    }

    function updatePricing() {
      let itemsUnitTotal = 0;
      let allSelected = true;

      // Check every single item in the bundle
      data.items.forEach((item) => {
        const sel = state.selections[item.key];
        
        // MUST have a selection entry
        if (!sel || !sel.productId) {
          allSelected = false;
          return;
        }

        const product = getProduct(item.key, sel.productId);
        
        // MUST have a valid product
        if (!product) {
          allSelected = false;
          return;
        }

        // MUST have at least one in-stock option
        if (!hasAnyInStockOption(product)) {
          allSelected = false;
          return;
        }

        // MUST have a size selected (even if it's "one-size")
        if (!sel.sizeKey) {
          allSelected = false;
          return;
        }

        // Variable products MUST have a variation ID
        if (isVariableProduct(product)) {
          if (!sel.variationId || Number(sel.variationId) <= 0) {
            allSelected = false;
            return;
          }
        }
        
        // Check if selected size/variation is in stock
        if (sel.sizeKey && product.sizes && product.sizes[sel.sizeKey]) {
          const chosen = product.sizes[sel.sizeKey];
          if (chosen && chosen.in_stock === false) {
            allSelected = false;
            return;
          }
        }

        // Use price from selection for total calculation
        let priceToUse = sel.regularPrice || 0;
        if (priceToUse === 0 && product) {
          priceToUse = getProductPrice(product);
        }

        const itemQty = Math.max(1, parseInt(item.qty, 10) || 1);
        itemsUnitTotal += priceToUse * itemQty;
      });

      const itemsTotal = itemsUnitTotal * bundleQty;
      const bundleTotal = Number(data.bundlePrice || 0) * bundleQty;
      const savings = itemsTotal - bundleTotal;

      // Always show calculated totals
      if (itemsUnitTotal > 0) {
        $itemsTotal.text(formatMoney(itemsTotal));
        $savings.text(formatMoney(Math.max(0, savings)));
      } else {
        // Calculate default items total from first products if no selections yet
        let defaultTotal = 0;
        data.items.forEach((item) => {
          const firstProduct = item.products && item.products.length ? item.products[0] : null;
          if (firstProduct) {
            const itemQty = Math.max(1, parseInt(item.qty, 10) || 1);
            defaultTotal += getProductPrice(firstProduct) * itemQty;
          }
        });
        $itemsTotal.text(formatMoney(defaultTotal * bundleQty));
        $savings.text(formatMoney(Math.max(0, defaultTotal * bundleQty - bundleTotal)));
      }

      // Enable submit only if all required selections are made
      if (allSelected) {
        $submit.prop("disabled", false);
        const readyText = $submit.attr("data-text-ready");
        if (readyText) $submit.text(readyText);
      } else {
        $submit.prop("disabled", true);
        $submit.text("Select Options");
      }
    }

    // Color select
    $form.on("click", ".primefit-bundle-color", function (event) {
      // Prevent the theme's global color-option listener from firing.
      if (event && typeof event.stopPropagation === "function")
        event.stopPropagation();

      const $btn = $(this);
      const $item = $btn.closest(".primefit-bundle-item");
      const itemKey = $item.data("item-key");
      const productId = Number($btn.data("product-id"));
      const product = getProduct(itemKey, productId);

      $item.find(".primefit-bundle-color").removeClass("active");
      $btn.addClass("active");

      // Get price immediately (all sizes have same price)
      const productPrice = getProductPrice(product);

      // If product has only one size, auto-select it
      const sizes = product && product.sizes ? product.sizes : {};
      const sizeKeys = Object.keys(sizes);
      let sizeKey = "";
      let variationId = 0;
      let regularPrice = productPrice;

      if (sizeKeys.length === 1) {
        // Auto-select the only size
        sizeKey = sizeKeys[0];
        const sizeInfo = sizes[sizeKey];
        const inStock = !!(sizeInfo && sizeInfo.in_stock);
        if (inStock) {
          variationId = Number(sizeInfo.variation_id || 0);
          regularPrice = Number(
            sizeInfo.regular_price || sizeInfo.price || productPrice
          );
        } else {
          // Don't auto-select an out-of-stock size.
          sizeKey = "";
          variationId = 0;
          regularPrice = productPrice;
        }
      }

      // Update selection
      state.selections[itemKey] = {
        productId,
        sizeKey,
        variationId,
        regularPrice,
      };
      setHiddenInputs($item, productId, variationId);
      $item.find("[data-item-price]").text(formatMoney(regularPrice));

      renderSizes($item, itemKey, productId);

      // If only one size, mark it as selected
      if (sizeKeys.length === 1 && sizeKey) {
        $item
          .find('.primefit-bundle-size[data-size-key="' + sizeKey + '"]')
          .addClass("selected");
      }

      updatePricing();
    });

    // Size select
    $form.on("click", ".primefit-bundle-size", function () {
      const $btn = $(this);
      const $item = $btn.closest(".primefit-bundle-item");
      const itemKey = $item.data("item-key");
      const productId = Number(
        $item.find("[data-item-product-input]").val() || 0
      );
      const variationId = Number($btn.data("variation-id") || 0);
      const regularPrice = Number($btn.data("regular-price") || 0);
      const sizeKey = String($btn.data("size-key") || "");

      if (!productId) return;

      $item.find(".primefit-bundle-size").removeClass("selected");
      $btn.addClass("selected");

      state.selections[itemKey] = {
        productId,
        sizeKey,
        variationId,
        regularPrice,
      };
      setHiddenInputs($item, productId, variationId);
      // Price already displayed, but update to ensure consistency
      $item.find("[data-item-price]").text(formatMoney(regularPrice));

      updatePricing();
    });

    // Bundle quantity is always 1, no need for quantity change handler

    // Initialize: show prices and render sizes for single-product items
    data.items.forEach((item) => {
      const $item = $form
        .find('.primefit-bundle-item[data-item-key="' + item.key + '"]')
        .first();
      if (!$item.length) return;

      const firstProduct =
        item.products && item.products.length ? item.products[0] : null;
      if (!firstProduct) return;

      // Get price of first product to display by default
      const firstProductPrice = getProductPrice(firstProduct);

      // Show price for all items
      $item.find("[data-item-price]").text(formatMoney(firstProductPrice));

      // If there's only one product (no color choice), set up size selection
      if (item.products.length === 1) {
        const productId = Number(firstProduct.id);
        
        // Set product ID in hidden input so size selection works
        $item.find("[data-item-product-input]").val(String(productId));
        
        // Render sizes (user must click to select)
        renderSizes($item, item.key, productId);
      }
      // If multiple products, user must select a color first (which will render sizes)
    });

    updatePricing();
  }

  $(initBundle);
})(jQuery);
