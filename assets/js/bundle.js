/* global jQuery, primefitBundleData */
(function ($) {
  'use strict';

  function formatMoney(amount) {
    try {
      const data = window.primefitBundleData;
      if (!data) return '—';
      
      const n = Number(amount);
      if (Number.isNaN(n) || n < 0) return '—';
      
      const symbol = data.currencySymbol || '';
      const decimals = parseInt(data.priceDecimals, 10) || 2;
      const decimalSep = data.priceDecimalSep || '.';
      const thousandSep = data.priceThousandSep || ',';
      const position = data.currencyPosition || 'left';
      
      // Format number with decimals
      const fixed = n.toFixed(decimals);
      const parts = fixed.split('.');
      let integerPart = parts[0];
      
      // Apply thousand separator if needed
      if (thousandSep && thousandSep !== '') {
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
      }
      
      const decimalPart = parts[1] || '';
      let formattedNumber = integerPart;
      if (decimals > 0 && decimalPart) {
        formattedNumber += decimalSep + decimalPart;
      }
      
      // Apply currency position
      if (position === 'right' || position === 'right_space') {
        return formattedNumber + (position === 'right_space' ? ' ' : '') + symbol;
      } else if (position === 'left_space') {
        return symbol + ' ' + formattedNumber;
      } else {
        // Default: left (no space)
        return symbol + formattedNumber;
      }
    } catch (e) {
      console.error('Error formatting money:', e);
      return '—';
    }
  }

  function initBundle() {
    const data = window.primefitBundleData;
    if (!data || !data.items || !data.items.length) return;

    const $form = $('.primefit-bundle-form').first();
    if (!$form.length) return;

    const $submit = $form.find('[data-primefit-bundle-submit]').first();
    const $itemsTotal = $form.find('[data-items-total]').first();
    const $savings = $form.find('[data-savings]').first();
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
      return item.products.find((p) => Number(p.id) === Number(productId)) || null;
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
      if (product && typeof product.regular_price !== 'undefined') {
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
      $itemEl.find('[data-item-product-input]').val(String(productId || ''));
      $itemEl.find('[data-item-variation-input]').val(String(variationId || 0));
    }

    function renderSizes($itemEl, itemKey, productId) {
      const product = getProduct(itemKey, productId);
      const $sizes = $itemEl.find('[data-item-sizes]').first();
      $sizes.empty();

      if (!product || !product.sizes) {
        $sizes.append('<button type="button" class="size-option unavailable" disabled>Select color first</button>');
        return;
      }

      const sizeEntries = Object.entries(product.sizes);
      if (!sizeEntries.length) {
        $sizes.append('<button type="button" class="size-option unavailable" disabled>No sizes</button>');
        return;
      }

      sizeEntries.forEach(([sizeKey, info]) => {
        const inStock = !!info.in_stock;
        const variationId = Number(info.variation_id || 0);
        const regular = Number(info.regular_price || 0);

        const $btn = $('<button type="button" class="size-option primefit-bundle-size" />');
        $btn.attr('data-size-key', sizeKey);
        $btn.attr('data-variation-id', String(variationId));
        $btn.attr('data-regular-price', String(regular));
        $btn.prop('disabled', !inStock);
        $btn.text(String(sizeKey).toUpperCase());
        if (!inStock) $btn.addClass('unavailable');

        $sizes.append($btn);
      });
    }

    function updatePricing() {
      let itemsUnitTotal = 0;
      let allSelected = true;

      data.items.forEach((item) => {
        const sel = state.selections[item.key];
        if (!sel || !sel.productId) {
          allSelected = false;
          return;
        }
        
        // Since all sizes have the same price, we can use the product price even without size selection
        const product = getProduct(item.key, sel.productId);
        let priceToUse = sel.regularPrice || 0;
        
        // If no price in selection, get it from product
        if (priceToUse === 0 && product) {
          priceToUse = getProductPrice(product);
        }
        
        // Gate submit on actual purchasable selection rules (server requires variation_id for variable products).
        if (product) {
          if (!hasAnyInStockOption(product)) {
            allSelected = false;
          }

          if (isVariableProduct(product)) {
            if (!sel.variationId || Number(sel.variationId) <= 0) {
              allSelected = false;
            } else if (sel.sizeKey && product.sizes && product.sizes[sel.sizeKey]) {
              const chosen = product.sizes[sel.sizeKey];
              if (chosen && chosen.in_stock === false) {
                allSelected = false;
              }
            }
          }
        } else {
          allSelected = false;
        }
        
        const itemQty = Math.max(1, parseInt(item.qty, 10) || 1);
        itemsUnitTotal += priceToUse * itemQty;
      });

      const itemsTotal = itemsUnitTotal * bundleQty;
      const bundleTotal = Number(data.bundlePrice || 0) * bundleQty;
      const savings = itemsTotal - bundleTotal;

      // Always show prices if at least one product is selected
      const hasAnySelection = data.items.some((item) => {
        const sel = state.selections[item.key];
        return sel && sel.productId;
      });

      if (hasAnySelection) {
        $itemsTotal.text(formatMoney(itemsTotal));
        $savings.text(formatMoney(Math.max(0, savings)));
      } else {
        $itemsTotal.text('—');
        $savings.text('—');
      }

      // Enable submit only if all required selections are made
      if (allSelected) {
        $submit.prop('disabled', false);
      } else {
        $submit.prop('disabled', true);
      }
    }

    // Color select
    $form.on('click', '.primefit-bundle-color', function (event) {
      // Prevent the theme's global color-option listener from firing.
      if (event && typeof event.stopPropagation === 'function') event.stopPropagation();

      const $btn = $(this);
      const $item = $btn.closest('.primefit-bundle-item');
      const itemKey = $item.data('item-key');
      const productId = Number($btn.data('product-id'));
      const product = getProduct(itemKey, productId);

      $item.find('.primefit-bundle-color').removeClass('active');
      $btn.addClass('active');

      // Get price immediately (all sizes have same price)
      const productPrice = getProductPrice(product);
      
      // If product has only one size, auto-select it
      const sizes = product && product.sizes ? product.sizes : {};
      const sizeKeys = Object.keys(sizes);
      let sizeKey = '';
      let variationId = 0;
      let regularPrice = productPrice;
      
      if (sizeKeys.length === 1) {
        // Auto-select the only size
        sizeKey = sizeKeys[0];
        const sizeInfo = sizes[sizeKey];
        const inStock = !!(sizeInfo && sizeInfo.in_stock);
        if (inStock) {
          variationId = Number(sizeInfo.variation_id || 0);
          regularPrice = Number(sizeInfo.regular_price || sizeInfo.price || productPrice);
        } else {
          // Don't auto-select an out-of-stock size.
          sizeKey = '';
          variationId = 0;
          regularPrice = productPrice;
        }
      }

      // Update selection
      state.selections[itemKey] = { productId, sizeKey, variationId, regularPrice };
      setHiddenInputs($item, productId, variationId);
      $item.find('[data-item-price]').text(formatMoney(regularPrice));

      renderSizes($item, itemKey, productId);
      
      // If only one size, mark it as selected
      if (sizeKeys.length === 1 && sizeKey) {
        $item.find('.primefit-bundle-size[data-size-key="' + sizeKey + '"]').addClass('selected');
      }
      
      updatePricing();
    });

    // Size select
    $form.on('click', '.primefit-bundle-size', function () {
      const $btn = $(this);
      const $item = $btn.closest('.primefit-bundle-item');
      const itemKey = $item.data('item-key');
      const productId = Number($item.find('[data-item-product-input]').val() || 0);
      const variationId = Number($btn.data('variation-id') || 0);
      const regularPrice = Number($btn.data('regular-price') || 0);
      const sizeKey = String($btn.data('size-key') || '');

      if (!productId) return;

      $item.find('.primefit-bundle-size').removeClass('selected');
      $btn.addClass('selected');

      state.selections[itemKey] = { productId, sizeKey, variationId, regularPrice };
      setHiddenInputs($item, productId, variationId);
      // Price already displayed, but update to ensure consistency
      $item.find('[data-item-price]').text(formatMoney(regularPrice));

      updatePricing();
    });

    // Bundle quantity is always 1, no need for quantity change handler

    // Initialize defaults: pick first color for each item, but require user to pick size.
    data.items.forEach((item) => {
      const $item = $form.find('.primefit-bundle-item[data-item-key="' + item.key + '"]').first();
      if (!$item.length) return;

      const firstProduct = item.products && item.products.length ? item.products[0] : null;
      if (!firstProduct) return;

      // If only one product, auto-select it (no color selection UI)
      if (item.products.length === 1) {
        const productId = Number(firstProduct.id);
        const sizes = firstProduct.sizes || {};
        const sizeKeys = Object.keys(sizes);
        
        // Get price immediately (all sizes have same price)
        const productPrice = getProductPrice(firstProduct);
        
        // If it's a simple product (one-size) or has only one size, auto-select it
        if (sizeKeys.length <= 1) {
          const sizeKey = sizeKeys[0] || 'one-size';
          const sizeInfo = sizes[sizeKey] || { variation_id: 0, regular_price: productPrice };
          const inStock = !!(sizeInfo && sizeInfo.in_stock);
          const variationId = inStock ? Number(sizeInfo.variation_id || 0) : 0;
          const regularPrice = Number(sizeInfo.regular_price || sizeInfo.price || productPrice);
          
          state.selections[item.key] = { productId, sizeKey: inStock ? sizeKey : '', variationId, regularPrice };
          setHiddenInputs($item, productId, variationId);
          $item.find('[data-item-price]').text(formatMoney(regularPrice));
        } else {
          // Variable product with multiple sizes - show price immediately but require size selection
          state.selections[item.key] = { productId, sizeKey: '', variationId: 0, regularPrice: productPrice };
          setHiddenInputs($item, productId, 0);
          $item.find('[data-item-price]').text(formatMoney(productPrice));
        }
        renderSizes($item, item.key, productId);
      } else {
        // Multiple products - trigger color selection
        const $firstColorBtn = $item.find('.primefit-bundle-color[data-product-id="' + firstProduct.id + '"]').first();
        if ($firstColorBtn.length) $firstColorBtn.trigger('click');
      }
    });

    updatePricing();
  }

  $(initBundle);
})(jQuery);


