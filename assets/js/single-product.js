/**
 * Single Product Page JavaScript
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Product Gallery Functionality
   */
  class ProductGallery {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
      this.initSwipe();
    }

    bindEvents() {
      // Thumbnail clicks
      $(document).on("click", ".thumbnail-item", (e) => {
        e.preventDefault();
        const index = $(e.currentTarget).data("image-index");
        this.switchImage(index);
      });

      // Dot navigation clicks
      $(document).on("click", ".image-dot", (e) => {
        e.preventDefault();
        const index = $(e.currentTarget).data("image-index");
        this.switchImage(index);
      });

      // Arrow navigation
      $(document).on("click", ".image-nav-prev", (e) => {
        e.preventDefault();
        this.previousImage();
      });

      $(document).on("click", ".image-nav-next", (e) => {
        e.preventDefault();
        this.nextImage();
      });

      // Keyboard navigation
      $(document).on("keydown", (e) => {
        if ($(e.target).closest(".product-gallery-container").length) {
          if (e.key === "ArrowLeft") {
            e.preventDefault();
            this.previousImage();
          } else if (e.key === "ArrowRight") {
            e.preventDefault();
            this.nextImage();
          }
        }
      });
    }

    switchImage(index) {
      const $gallery = $(".product-gallery-container");
      const $mainImage = $gallery.find(".main-product-image");
      const $thumbnails = $gallery.find(".thumbnail-item");
      const $dots = $gallery.find(".image-dot");

      // Get current index
      const currentIndex = parseInt($mainImage.attr("data-image-index")) || 0;

      // Don't animate if clicking the same image
      if (currentIndex === index) {
        return;
      }

      // Determine slide direction
      const isNext = index > currentIndex;
      const slideDirection = isNext ? "right" : "left";

      // Get the thumbnail image to extract the correct URL
      const $thumbnailImg = $thumbnails.eq(index).find(".thumbnail-image");
      if ($thumbnailImg.length) {
        // Try multiple approaches to get high-quality image
        let imageUrl = $thumbnailImg.attr("src");

        // Replace thumbnail size with full size for maximum quality
        imageUrl = imageUrl.replace("woocommerce_gallery_thumbnail", "full");

        // If that didn't work, try replacing common thumbnail dimensions with full size
        if (imageUrl === $thumbnailImg.attr("src")) {
          imageUrl = imageUrl.replace(/-150x150/, "");
        }
        if (imageUrl === $thumbnailImg.attr("src")) {
          imageUrl = imageUrl.replace(/-100x100/, "");
        }
        if (imageUrl === $thumbnailImg.attr("src")) {
          imageUrl = imageUrl.replace(/-300x300/, "");
        }
        if (imageUrl === $thumbnailImg.attr("src")) {
          imageUrl = imageUrl.replace(/-200x200/, "");
        }
        if (imageUrl === $thumbnailImg.attr("src")) {
          imageUrl = imageUrl.replace(/-400x400/, "");
        }
        if (imageUrl === $thumbnailImg.attr("src")) {
          imageUrl = imageUrl.replace(/-600x600/, "");
        }

        const imageAlt = $thumbnailImg.attr("alt");

        // Start slide animation
        this.animateSlide(
          $mainImage,
          imageUrl,
          imageAlt,
          index,
          slideDirection
        );
      }

      // Update active states
      $thumbnails.removeClass("active");
      $thumbnails.eq(index).addClass("active");

      $dots.removeClass("active");
      $dots.eq(index).addClass("active");
    }

    animateSlide($mainImage, imageUrl, imageAlt, index, direction) {
      const $gallery = $(".product-gallery-container");
      const $mainImageWrapper = $gallery.find(".main-image-wrapper");

      // Prevent multiple animations
      if ($mainImageWrapper.hasClass("loading")) {
        return;
      }

      // Add loading state
      $mainImageWrapper.addClass("loading");

      // Remove any existing animation classes
      $mainImage.removeClass(
        "slide-out-left slide-out-right slide-in-left slide-in-right slide-in-active"
      );

      // Create a temporary image element for the new image
      const $tempImage = $("<img>", {
        src: imageUrl,
        alt: imageAlt,
        class: "main-product-image temp-image",
        "data-image-index": index,
      });

      // Preload the image to prevent flicker
      $tempImage.on("load", () => {
        // Add slide-in class based on direction
        const slideInClass =
          direction === "right" ? "slide-in-right" : "slide-in-left";
        $tempImage.addClass(slideInClass);

        // Add slide-out class to current image
        const slideOutClass =
          direction === "right" ? "slide-out-left" : "slide-out-right";
        $mainImage.addClass(slideOutClass);

        // Append the new image
        $mainImageWrapper.append($tempImage);

        // Trigger the slide-in animation after a brief delay
        setTimeout(() => {
          $tempImage.addClass("slide-in-active");
        }, 10);

        // Remove the old image and clean up after animation completes
        setTimeout(() => {
          $mainImage.remove();
          $tempImage
            .removeClass("temp-image slide-in-left slide-in-right")
            .addClass("slide-in-active");
          $mainImageWrapper.removeClass("loading");
        }, 250);
      });

      // Fallback if image doesn't load
      $tempImage.on("error", () => {
        $mainImageWrapper.removeClass("loading");
      });
    }

    previousImage() {
      const $gallery = $(".product-gallery-container");
      const $thumbnails = $gallery.find(".thumbnail-item");
      const currentIndex = parseInt(
        $gallery.find(".main-product-image").attr("data-image-index") || 0
      );
      const newIndex =
        currentIndex > 0 ? currentIndex - 1 : $thumbnails.length - 1;
      this.switchImage(newIndex);
    }

    nextImage() {
      const $gallery = $(".product-gallery-container");
      const $thumbnails = $gallery.find(".thumbnail-item");
      const currentIndex = parseInt(
        $gallery.find(".main-product-image").attr("data-image-index") || 0
      );
      const newIndex =
        currentIndex < $thumbnails.length - 1 ? currentIndex + 1 : 0;
      this.switchImage(newIndex);
    }

    initSwipe() {
      // Touch/swipe support for mobile
      let startX = 0;
      let startY = 0;
      let endX = 0;
      let endY = 0;
      let isScrolling = false;
      let touchStartTime = 0;

      $(".product-main-image").on("touchstart", (e) => {
        startX = e.originalEvent.touches[0].clientX;
        startY = e.originalEvent.touches[0].clientY;
        touchStartTime = Date.now();
        isScrolling = false;
      });

      $(".product-main-image").on("touchmove", (e) => {
        const currentX = e.originalEvent.touches[0].clientX;
        const currentY = e.originalEvent.touches[0].clientY;
        const deltaX = Math.abs(currentX - startX);
        const deltaY = Math.abs(currentY - startY);

        // If vertical movement is greater than horizontal, allow scrolling
        if (deltaY > deltaX && deltaY > 10) {
          isScrolling = true;
          return; // Allow default scroll behavior
        }

        // If horizontal movement is greater, prevent scrolling for swipe
        if (deltaX > deltaY && deltaX > 10) {
          e.preventDefault();
        }
      });

      $(".product-main-image").on("touchend", (e) => {
        endX = e.originalEvent.changedTouches[0].clientX;
        endY = e.originalEvent.changedTouches[0].clientY;

        // Only handle swipe if it wasn't a scroll gesture
        if (!isScrolling) {
          this.handleSwipe(startX, startY, endX, endY);
        }
      });
    }

    handleSwipe(startX, startY, endX, endY) {
      const deltaX = endX - startX;
      const deltaY = endY - startY;
      const minSwipeDistance = 50;

      // Only handle horizontal swipes
      if (
        Math.abs(deltaX) > Math.abs(deltaY) &&
        Math.abs(deltaX) > minSwipeDistance
      ) {
        if (deltaX > 0) {
          this.previousImage();
        } else {
          this.nextImage();
        }
      }
    }
  }

  /**
   * Product Information Toggle Functionality
   */
  class ProductInformation {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
    }

    bindEvents() {
      $(document).on("click", ".information-toggle", (e) => {
        e.preventDefault();
        const $toggle = $(e.currentTarget);
        const targetId = $toggle.data("target");
        const $content = $("#" + targetId);
        const $icon = $toggle.find(".toggle-icon svg");

        if ($content.hasClass("open")) {
          this.closeSection($content, $icon);
        } else {
          this.openSection($content, $icon);
        }
      });

      // Collapsible sections used in product summary
      $(document).on("click", ".collapsible-toggle", (e) => {
        e.preventDefault();
        const $toggle = $(e.currentTarget);
        const $content = $toggle.closest(".collapsible-section").find(".collapsible-content").first();
        const $icon = $toggle.find(".collapsible-icon");

        if ($content.hasClass("open")) {
          $content.removeClass("open");
          $icon.css("transform", "rotate(0deg)");
        } else {
          $content.addClass("open");
          $icon.css("transform", "rotate(180deg)");
        }
      });

      // Keyboard support
      $(document).on("keydown", ".information-toggle", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          $(e.currentTarget).click();
        }
      });
    }

    openSection($content, $icon) {
      $content.addClass("open");
      $icon.css("transform", "rotate(180deg)");
      $content.attr("aria-expanded", "true");
    }

    closeSection($content, $icon) {
      $content.removeClass("open");
      $icon.css("transform", "rotate(0deg)");
      $content.attr("aria-expanded", "false");
    }
  }

  /**
   * Product Variation Selection
   */
  class ProductVariations {
    constructor() {
      this.isInitializing = true; // Flag to prevent auto-add during initialization
      this.hasUserInteracted = false; // Flag to track if user has interacted with the page
      this.init();
    }

    init() {
      this.bindEvents();
      this.initializeVariations();
      
      // Add a small delay to ensure page is fully loaded before allowing auto-add
      setTimeout(() => {
        this.isInitializing = false; // Set to false after initialization is complete
      }, 100);
    }

    bindEvents() {
      // Color selection
      $(document).on("click", ".color-option", (e) => {
        e.preventDefault();
        this.hasUserInteracted = true; // Mark that user has interacted
        const $option = $(e.currentTarget);
        this.selectColor($option);
      });

      // Size selection
      $(document).on("click", ".size-option", (e) => {
        e.preventDefault();
        this.hasUserInteracted = true; // Mark that user has interacted
        const $option = $(e.currentTarget);
        this.selectSize($option);
      });

      // Form submission
      $(document).on("submit", ".primefit-variations-form", (e) => {
        this.handleFormSubmission(e);
      });
    }

    initializeVariations() {
      // Get default values from product data
      const defaultColor = window.primefitProductData?.defaultColor;
      const defaultSize = window.primefitProductData?.defaultSize;

      // Initialize with default color if available, otherwise first color
      let $activeColor = $(".color-option.active").first();

      if (defaultColor) {
        const $defaultColorOption = $(
          `.color-option[data-color="${defaultColor}"]`
        );
        if ($defaultColorOption.length) {
          $activeColor = $defaultColorOption;
          // Update active state
          $(".color-option").removeClass("active");
          $defaultColorOption.addClass("active");
        }
      }

      if ($activeColor.length) {
        // Initialize color without triggering auto-add
        this.initializeColor($activeColor);
      }

      // Select default size if available
      if (defaultSize) {
        const $defaultSizeOption = $(
          `.size-option[data-size="${defaultSize}"]`
        );
        if (
          $defaultSizeOption.length &&
          $defaultSizeOption.is(":visible") &&
          !$defaultSizeOption.prop("disabled")
        ) {
          $(".size-option").removeClass("selected");
          $defaultSizeOption.addClass("selected");
        }
      }

      // Update add to cart button state
      this.updateAddToCartButton();
    }

    initializeColor($option) {
      $(".color-option").removeClass("active");
      $option.addClass("active");

      const color = $option.data("color");
      const variationImage = $option.data("variation-image");

      // Update product color display
      $(".color-value").text(color);

      // Update gallery image if variation image exists
      if (variationImage) {
        this.updateGalleryImage(variationImage);
      }

      // Update available sizes from variation stock for this color
      const sizesForColor = this.getAvailableSizesForColor(color);
      this.updateAvailableSizes(sizesForColor);

      // Update add to cart button
      this.updateAddToCartButton();
      
      // Don't auto-add during initialization
    }

    selectColor($option) {
      $(".color-option").removeClass("active");
      $option.addClass("active");

      const color = $option.data("color");
      const variationImage = $option.data("variation-image");

      // Update product color display
      $(".color-value").text(color);

      // Update gallery image if variation image exists
      if (variationImage) {
        this.updateGalleryImage(variationImage);
      }

      // Update sizes based on actual stock for selected color
      const sizesForColor = this.getAvailableSizesForColor(color);
      this.updateAvailableSizes(sizesForColor);

      // Update add to cart button
      this.updateAddToCartButton();
      // No auto add
    }

    selectSize($option) {
      $(".size-option").removeClass("selected");
      $option.addClass("selected");

      // Update add to cart button
      this.updateAddToCartButton();
      // No auto add
    }

    updateGalleryImage(imageUrl) {
      const $mainImage = $(".main-product-image");
      if ($mainImage.length) {
        if (imageUrl) {
          // Use slide animation for variation image changes
          const currentIndex =
            parseInt($mainImage.attr("data-image-index")) || 0;
          const imageAlt = $mainImage.attr("alt") || "";

          // Create a new ProductGallery instance to use its animation method
          const gallery = new ProductGallery();
          gallery.animateSlide(
            $mainImage,
            imageUrl,
            imageAlt,
            currentIndex,
            "right"
          );
        } else {
          // Fallback to first gallery image if no variation image
          const $firstThumbnail = $(".thumbnail-item").first();
          if ($firstThumbnail.length) {
            const fallbackImage = $firstThumbnail
              .find(".thumbnail-image")
              .attr("src");
            if (fallbackImage) {
              // Convert thumbnail URL to full size
              const fullSizeImage = fallbackImage.replace(
                "woocommerce_gallery_thumbnail",
                "woocommerce_single"
              );

              // Use slide animation for fallback image
              const currentIndex =
                parseInt($mainImage.attr("data-image-index")) || 0;
              const imageAlt = $mainImage.attr("alt") || "";

              const gallery = new ProductGallery();
              gallery.animateSlide(
                $mainImage,
                fullSizeImage,
                imageAlt,
                currentIndex,
                "right"
              );
            }
          }
        }
      }
    }

    updateAvailableSizes(availableSizes) {
      const sizes = Array.isArray(availableSizes) ? availableSizes : [];

      $(".size-option").each(function () {
        const $sizeOption = $(this);
        const sizeValue = $sizeOption.data("size");

        if (sizes.includes(sizeValue)) {
          $sizeOption
            .show()
            .prop("disabled", false)
            .attr("aria-disabled", "false")
            .removeClass("unavailable");
        } else {
          // Grey out but keep visible
          $sizeOption
            .show()
            .prop("disabled", true)
            .attr("aria-disabled", "true")
            .addClass("unavailable")
            .removeClass("selected");
        }
      });

      // If the selected size is no longer available, clear selection
      const $selectedSize = $(".size-option.selected");
      if ($selectedSize.length && $selectedSize.prop("disabled")) {
        $selectedSize.removeClass("selected");
      }
    }

    getAvailableSizesForColor(colorValue) {
      const results = [];
      const variations = window.primefitProductData?.variations || [];

      for (let i = 0; i < variations.length; i++) {
        const v = variations[i];
        if (!v) continue;

        // Only consider in-stock variations
        if (v.is_in_stock === false) continue;

        let colorMatches = false;
        let sizeValue = null;

        for (const key in v.attributes) {
          const val = v.attributes[key];
          if (
            (key.toLowerCase().includes("color") ||
              key.includes("pa_color") ||
              key.includes("attribute_pa_color")) &&
            val === colorValue
          ) {
            colorMatches = true;
          }
          if (
            key.toLowerCase().includes("size") ||
            key.includes("pa_size") ||
            key.includes("attribute_pa_size")
          ) {
            sizeValue = val;
          }
        }

        if (colorMatches && sizeValue && !results.includes(sizeValue)) {
          results.push(sizeValue);
        }
      }

      return results;
    }

    updateAddToCartButton() {
      const $selectedColor = $(".color-option.active");
      const $selectedSize = $(".size-option.selected");
      const $addToCartButton = $(".single_add_to_cart_button");

      if ($selectedColor.length && $selectedSize.length) {
        $addToCartButton.prop("disabled", false).text("ADD TO CART");
        // Ensure variation_id is set when both options selected
        const colorValue = $selectedColor.data("color");
        const sizeValue = $selectedSize.data("size");
        const variationId = this.findVariationId(colorValue, sizeValue);
        if (variationId) {
          $(".variation_id").val(variationId);
          // Update quantity min/max based on selected variation stock/limits
          this.updateQuantityMaxForVariation(variationId);
        }
      } else {
        $addToCartButton.prop("disabled", true).text("SELECT OPTIONS");
        $(".variation_id").val("0");
        // Reset quantity bounds to defaults when no valid variation is selected
        this.setQuantityBounds(1, 999);
      }
    }

    handleFormSubmission(e) {
      const $form = $(e.currentTarget);
      const $selectedColor = $(".color-option.active");
      const $selectedSize = $(".size-option.selected");

      if (!$selectedColor.length || !$selectedSize.length) {
        e.preventDefault();
        alert("Please select both color and size options.");
        return false;
      }

      // Add variation attributes to form
      const colorValue = $selectedColor.data("color");
      const sizeValue = $selectedSize.data("size");

      // Find the correct variation ID
      const variationId = this.findVariationId(colorValue, sizeValue);
      if (variationId) {
        $(".variation_id").val(variationId);
      }

      return true;
    }

    findVariationId(color, size) {
      if (window.primefitProductData && window.primefitProductData.variations) {
        const variations = window.primefitProductData.variations;

        for (let i = 0; i < variations.length; i++) {
          const variation = variations[i];
          let hasColor = false;
          let hasSize = false;

          for (const attrName in variation.attributes) {
            const attrValue = variation.attributes[attrName];

            // Check for color match (more flexible matching)
            if (
              (attrName.toLowerCase().includes("color") ||
                attrName.includes("pa_color") ||
                attrName.includes("attribute_pa_color")) &&
              attrValue === color
            ) {
              hasColor = true;
            }

            // Check for size match (more flexible matching)
            if (
              (attrName.toLowerCase().includes("size") ||
                attrName.includes("pa_size") ||
                attrName.includes("attribute_pa_size")) &&
              attrValue === size
            ) {
              hasSize = true;
            }
          }

          if (hasColor && hasSize) {
            return variation.variation_id;
          }
        }
      }
      return null;
    }

    // Update quantity inputs (main and sticky) with the correct bounds for the selected variation
    updateQuantityMaxForVariation(variationId) {
      try {
        const list = (window.primefitProductData && window.primefitProductData.variations) ? window.primefitProductData.variations : [];
        let maxQty = 999;
        const minQty = 1;

        for (let i = 0; i < list.length; i++) {
          const v = list[i];
          if (!v) continue;
          if (String(v.variation_id) === String(variationId)) {
            // Prefer WooCommerce provided max_qty on available_variations
            if (typeof v.max_qty !== 'undefined' && v.max_qty !== null && v.max_qty !== '') {
              const parsedMax = parseInt(v.max_qty);
              if (!isNaN(parsedMax) && parsedMax > 0) {
                maxQty = parsedMax;
              }
            } else if (typeof v.stock_quantity !== 'undefined' && v.stock_quantity !== null && v.stock_quantity !== '') {
              const parsedStock = parseInt(v.stock_quantity);
              if (!isNaN(parsedStock) && parsedStock > 0) {
                maxQty = parsedStock;
              }
            }
            break;
          }
        }

        this.setQuantityBounds(minQty, maxQty);
      } catch (err) {
        this.setQuantityBounds(1, 999);
      }
    }

    // Apply bounds and clamp current values; also refresh button enable/disable states
    setQuantityBounds(minValue, maxValue) {
      const $mainInput = $(".product-actions .quantity input[type='number']").first();
      if ($mainInput.length) {
        $mainInput.attr({ min: String(minValue), max: String(maxValue) });
        const current = parseInt($mainInput.val()) || minValue;
        const clamped = Math.max(minValue, Math.min(current, maxValue));
        $mainInput.val(clamped);
      }

      const $stickyInput = $(".sticky-quantity-input");
      if ($stickyInput.length) {
        $stickyInput.attr({ min: String(minValue), max: String(maxValue) });
        const currentSticky = parseInt($stickyInput.val()) || minValue;
        const clampedSticky = Math.max(minValue, Math.min(currentSticky, maxValue));
        $stickyInput.val(clampedSticky);
        if (window.stickyAddToCartInstance && window.stickyAddToCartInstance.updateStickyQuantityButtons) {
          window.stickyAddToCartInstance.updateStickyQuantityButtons();
        }
      }

      if (window.quantityControlsInstance && window.quantityControlsInstance.updateButtonStates) {
        window.quantityControlsInstance.updateButtonStates();
      }
    }

    autoAddToCart() {
      // Don't auto-add during initialization or if user hasn't interacted
      if (this.isInitializing || !this.hasUserInteracted) {
        return;
      }

      // Additional check: don't auto-add if page was just loaded/refreshed
      if (performance.navigation && performance.navigation.type === performance.navigation.TYPE_RELOAD) {
        // If this is a page reload, don't auto-add for the first few seconds
        const timeSinceLoad = Date.now() - performance.timing.navigationStart;
        if (timeSinceLoad < 2000) { // 2 seconds
          return;
        }
      }

      const $selectedColor = $(".color-option.active");
      const $selectedSize = $(".size-option.selected");
      const $addToCartButton = $(".single_add_to_cart_button");

      // Only proceed if both color and size are selected
      if (!$selectedColor.length || !$selectedSize.length) {
        return;
      }

      // Check if button is enabled (meaning we have a valid variation)
      if ($addToCartButton.prop("disabled")) {
        return;
      }

      // Get the form data
      const $form = $(".primefit-variations-form");
      if (!$form.length) {
        return;
      }

      // Prevent multiple simultaneous requests
      if ($addToCartButton.hasClass("loading")) {
        return;
      }

      // Show loading state
      $addToCartButton.addClass("loading").prop("disabled", true).text("Adding...");
      
      // Also show loading state on the selected size option
      $selectedSize.addClass("loading");

      // Get form data
      const formData = this.getFormDataForAjax($form);

      // Submit via AJAX
      this.submitToCartAjax(formData, $addToCartButton);
    }

    getFormDataForAjax($form) {
      const formArray = $form.serializeArray();
      const formData = {};
      
      // Convert form array to object
      $.each(formArray, function(i, field) {
        formData[field.name] = field.value;
      });

      // Ensure we have required fields
      formData.action = 'wc_ajax_add_to_cart';
      
      // Get product ID
      if (!formData.product_id) {
        formData.product_id = formData['add-to-cart'] || $form.data('product_id') || $form.find('input[name="product_id"]').val();
      }
      
      // Set default quantity if not provided
      if (!formData.quantity) {
        formData.quantity = 1;
      }

      // Add security nonce
      if (window.primefit_cart_params && window.primefit_cart_params.add_to_cart_nonce) {
        formData.security = window.primefit_cart_params.add_to_cart_nonce;
      } else if (window.wc_add_to_cart_params && window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce) {
        formData.security = window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce;
      }

      return formData;
    }

    submitToCartAjax(formData, $button) {
      const ajaxUrl = (window.primefit_cart_params && window.primefit_cart_params.ajax_url) || 
                     (window.wc_add_to_cart_params && window.wc_add_to_cart_params.ajax_url) || 
                     '/wp-admin/admin-ajax.php';

      $.ajax({
        type: 'POST',
        url: ajaxUrl,
        data: formData,
        dataType: 'json',
        timeout: 8000, // Explicit timeout
        cache: false, // Prevent caching issues
        success: (response) => {
          this.handleAjaxSuccess(response, $button);
        },
        error: (xhr, status, error) => {
          this.handleAjaxError(xhr, status, error, $button);
        }
      });
    }

    handleAjaxSuccess(response, $button) {
      console.log('Auto add to cart response:', response);

      // Check for actual errors vs success-with-redirect
      if (response.error) {
        // If there are fragments, it means the product was actually added successfully
        if (response.fragments && Object.keys(response.fragments).length > 0) {
          console.log('Product added successfully (with fragments despite error flag)');
          // Treat as success since we have fragments
        } else if (response.product_url && !response.data && !response.notice) {
          // This might be a redirect response, which could be success
          console.log('Received redirect response, checking if product was actually added...');
          
          // Force refresh cart fragments to check if item was added
          this.checkCartAfterAutoAdd($button);
          return;
        } else {
          // This appears to be an actual error
          let errorMessage = 'Failed to add product to cart.';
          
          if (response.data) {
            errorMessage = response.data;
          } else if (response.notice) {
            errorMessage = response.notice;
          }
          
          this.showAutoAddError(errorMessage);
          this.hideAutoAddLoadingState($button, false);
          return;
        }
      }

      // Success! Update cart fragments and show feedback
      if (response.fragments) {
        // Update cart count and other fragments
        $.each(response.fragments, function(key, value) {
          $(key).replaceWith(value);
        });
      }

      // Reset quantity inputs to 1 after successful auto-add
      this.resetQuantityInputs();

      // Trigger WooCommerce events
      $(document.body).trigger('update_checkout');
      $(document.body).trigger('wc_fragment_refresh');
      $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

      // Show success state
      this.hideAutoAddLoadingState($button, true);
      
      // Show success message
      this.showAutoAddSuccess('');
    }

    handleAjaxError(xhr, status, error, $button) {
      console.error('AJAX error auto adding to cart:', {xhr, status, error});
      
      let errorMessage = 'Unable to add product to cart. Please try again.';
      
      // More specific error messages based on status
      if (status === 'timeout') {
        errorMessage = 'Request timed out. Please check your connection and try again.';
      } else if (status === 'error' && xhr.status === 0) {
        errorMessage = 'Network error. Please check your internet connection.';
      } else if (xhr.status === 403) {
        errorMessage = 'Security check failed. Please refresh the page and try again.';
      } else if (xhr.status === 500) {
        errorMessage = 'Server error. Please try again in a moment.';
      } else if (xhr.status === 404) {
        errorMessage = 'Service unavailable. Please try again later.';
      }
      
      this.showAutoAddError(errorMessage);
      this.hideAutoAddLoadingState($button, false);
    }

    hideAutoAddLoadingState($button, success = true) {
      if ($button.length) {
        const originalText = $button.data('original-text') || 'Add to Cart';
        
        $button.removeClass('loading')
               .prop('disabled', false);
        
        // Remove loading state from size option
        $('.size-option.loading').removeClass('loading');
        
        if (success) {
          $button.addClass('added').text('Added!');
          
          // Reset button text after 2 seconds
          setTimeout(() => {
            $button.removeClass('added').text(originalText);
          }, 2000);
        } else {
          $button.addClass('error').text('Error');
          
          // Reset button text after 3 seconds
          setTimeout(() => {
            $button.removeClass('error').text(originalText);
          }, 3000);
        }
      }
    }


    checkCartAfterAutoAdd($button) {
      // Force refresh cart fragments to check if product was actually added
      const ajaxUrl = (window.primefit_cart_params && window.primefit_cart_params.ajax_url) || 
                     (window.wc_add_to_cart_params && window.wc_add_to_cart_params.ajax_url) || 
                     '/wp-admin/admin-ajax.php';

      $.ajax({
        type: 'POST',
        url: ajaxUrl,
        data: {
          action: 'woocommerce_get_refreshed_fragments'
        },
        success: function(response) {
          console.log('Cart check response:', response);
          
          if (response && response.fragments) {
            // Update fragments - this will show the new cart count if product was added
            $.each(response.fragments, function(key, value) {
              $(key).replaceWith(value);
            });

            // Reset quantity inputs to 1 after successful auto-add
            this.resetQuantityInputs();

            // Trigger WooCommerce events
            $(document.body).trigger('update_checkout');
            $(document.body).trigger('wc_fragment_refresh');
            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

            // Show success state
            this.hideAutoAddLoadingState($button, true);
            this.showAutoAddSuccess('');
          } else {
            // If no fragments, treat as error
            this.showAutoAddError('Unable to verify if product was added. Please check your cart.');
            this.hideAutoAddLoadingState($button, false);
          }
        }.bind(this),
        error: function() {
          // Fallback: show ambiguous message
          this.showAutoAddError('Product may have been added. Please check your cart.');
          this.hideAutoAddLoadingState($button, false);
        }.bind(this)
      });
    }

    resetQuantityInputs() {
      // Reset main quantity input to 1
      const $mainQuantityInput = $(".quantity input[type='number']");
      if ($mainQuantityInput.length) {
        $mainQuantityInput.val(1);
      }
      
      // Reset sticky quantity input to 1
      const $stickyQuantityInput = $(".sticky-quantity-input");
      if ($stickyQuantityInput.length) {
        $stickyQuantityInput.val(1);
        
        // Update sticky quantity button states
        if (window.stickyAddToCartInstance && window.stickyAddToCartInstance.updateStickyQuantityButtons) {
          window.stickyAddToCartInstance.updateStickyQuantityButtons();
        }
      }
      
      // Update main quantity button states
      if (window.quantityControlsInstance && window.quantityControlsInstance.updateButtonStates) {
        window.quantityControlsInstance.updateButtonStates();
      }
    }

    showAutoAddSuccess(message) {
      // Show success notification (can be customized)
      if (message && message.trim()) {
        console.log('Auto add to cart success:', message);
      }
    }

    showAutoAddError(message) {
      // Show error notification
      console.error('Auto add to cart error:', message);
      if (message && message.trim()) {
        // You can implement a toast notification system here
        alert(message);
      }
    }
  }

  /**
   * Notify When Available Functionality
   */
  class NotifyAvailability {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
    }

    bindEvents() {
      $(document).on("click", ".notify-button", (e) => {
        e.preventDefault();
        this.showNotifyForm();
      });

      $(document).on("click", ".notify-form-cancel", (e) => {
        e.preventDefault();
        this.hideNotifyForm();
      });

      $(document).on("submit", ".notify-form", (e) => {
        e.preventDefault();
        this.submitNotifyForm();
      });
    }

    showNotifyForm() {
      const $button = $(".notify-button");
      const productId =
        $button.data("product-id") || $('input[name="add-to-cart"]').val();

      const formHtml = `
                <div class="notify-form-overlay">
                    <div class="notify-form-container">
                        <h3>Notify When Available</h3>
                        <form class="notify-form">
                            <input type="hidden" name="product_id" value="${productId}">
                            <div class="form-group">
                                <label for="notify-email">Email Address</label>
                                <input type="email" id="notify-email" name="email" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="notify-submit">Notify Me</button>
                                <button type="button" class="notify-form-cancel">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

      $("body").append(formHtml);
    }

    hideNotifyForm() {
      $(".notify-form-overlay").remove();
    }

    submitNotifyForm() {
      const $form = $(".notify-form");
      const formData = $form.serialize();

      $.ajax({
        url: primefitData.ajaxUrl,
        type: "POST",
        data: {
          action: "primefit_notify_availability",
          nonce: primefitData.nonce,
          ...formData,
        },
        success: (response) => {
          if (response.success) {
            this.showSuccessMessage();
          } else {
            this.showErrorMessage(response.data);
          }
        },
        error: () => {
          this.showErrorMessage("An error occurred. Please try again.");
        },
      });
    }

    showSuccessMessage() {
      this.hideNotifyForm();
      $(".notify-button").text("We'll notify you!").prop("disabled", true);
    }

    showErrorMessage(message) {
      alert(message);
    }
  }

  /**
   * WooCommerce Quantity Controls Enhancement
   */
  class WooCommerceQuantityControls {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
      this.ensureIndependentQuantity();
      this.initializeButtonStates();
    }

    bindEvents() {
      // Handle quantity button clicks
      $(document).on("click", ".product-actions .quantity .plus", (e) => {
        e.preventDefault();
        this.increaseQuantity();
      });

      $(document).on("click", ".product-actions .quantity .minus", (e) => {
        e.preventDefault();
        this.decreaseQuantity();
      });

      // Handle input changes
      $(document).on("change", ".product-actions .quantity input[type='number']", (e) => {
        this.validateQuantity();
      });

      // Handle input keydown
      $(document).on("keydown", ".product-actions .quantity input[type='number']", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          this.validateQuantity();
        }
      });
    }

    increaseQuantity() {
      const $input = $(".product-actions .quantity input[type='number']");
      const currentValue = parseInt($input.val()) || 1;
      const maxValue = parseInt($input.attr("max")) || 999;
      const newValue = Math.min(currentValue + 1, maxValue);

      // Set value without triggering change event to prevent WooCommerce interference
      $input.val(newValue);
      this.updateButtonStates();
      
      // Sync with sticky quantity controls
      this.syncOriginalQuantityToSticky();
    }

    decreaseQuantity() {
      const $input = $(".product-actions .quantity input[type='number']");
      const currentValue = parseInt($input.val()) || 1;
      const minValue = parseInt($input.attr("min")) || 1;
      const newValue = Math.max(currentValue - 1, minValue);

      // Set value without triggering change event to prevent WooCommerce interference
      $input.val(newValue);
      this.updateButtonStates();
      
      // Sync with sticky quantity controls
      this.syncOriginalQuantityToSticky();
    }

    validateQuantity() {
      const $input = $(".product-actions .quantity input[type='number']");
      const currentValue = parseInt($input.val()) || 1;
      const minValue = parseInt($input.attr("min")) || 1;
      const maxValue = parseInt($input.attr("max")) || 999;

      // Ensure value is within bounds
      const validValue = Math.max(minValue, Math.min(currentValue, maxValue));
      $input.val(validValue);

      this.updateButtonStates();
      
      // Sync with sticky quantity controls
      this.syncOriginalQuantityToSticky();
    }

    ensureIndependentQuantity() {
      // Force quantity to start at 1, independent of cart contents
      const $input = $(".product-actions .quantity input[type='number']");
      if ($input.length) {
        $input.val(1);
        
        // Listen for external changes that might sync with cart and override them
        $input.on('input.independent-quantity', function() {
          const value = parseInt($(this).val()) || 1;
          if (value < 1) {
            $(this).val(1);
          }
        });
      }
    }

    initializeButtonStates() {
      // Wait for WooCommerce to initialize
      setTimeout(() => {
        this.updateButtonStates();
      }, 100);
    }

    updateButtonStates() {
      const $input = $(".product-actions .quantity input[type='number']");
      const $decreaseBtn = $(".product-actions .quantity .minus");
      const $increaseBtn = $(".product-actions .quantity .plus");

      if ($input.length && $decreaseBtn.length && $increaseBtn.length) {
        const currentValue = parseInt($input.val()) || 1;
        const minValue = parseInt($input.attr("min")) || 1;
        const maxValue = parseInt($input.attr("max")) || 999;

        // Update decrease button state
        if (currentValue <= minValue) {
          $decreaseBtn.prop("disabled", true);
        } else {
          $decreaseBtn.prop("disabled", false);
        }

        // Update increase button state
        if (currentValue >= maxValue) {
          $increaseBtn.prop("disabled", true);
        } else {
          $increaseBtn.prop("disabled", false);
        }
      }
    }

    syncOriginalQuantityToSticky() {
      const $stickyInput = $(".sticky-quantity-input");
      const $originalInput = $(".quantity input[type='number']");

      if ($stickyInput.length && $originalInput.length) {
        const originalValue = $originalInput.val();
        // Set value without triggering change event to prevent WooCommerce interference
        $stickyInput.val(originalValue);
        
        // Update sticky button states via the sticky instance
        if (window.stickyAddToCartInstance && window.stickyAddToCartInstance.updateStickyQuantityButtons) {
          window.stickyAddToCartInstance.updateStickyQuantityButtons();
        }
      }
    }
  }

  /**
   * Sticky Add to Cart Functionality
   */
  class StickyAddToCart {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
      this.createStickyButton();
      this.checkVisibility();
    }

    bindEvents() {
      // Scroll event to check visibility
      $(window).on("scroll", () => {
        this.checkVisibility();
      });

      // Resize event to recalculate positions
      $(window).on("resize", () => {
        this.checkVisibility();
      });

      // Handle sticky button click
      $(document).on("click", ".sticky-add-to-cart-button", (e) => {
        e.preventDefault();
        this.handleStickyButtonClick();
      });

      // Sync with original button state changes
      $(document).on("change", ".single_add_to_cart_button", () => {
        this.syncButtonState();
      });

      // Sync when variations change
      $(document).on("click", ".color-option, .size-option", () => {
        setTimeout(() => this.syncButtonState(), 100);
      });

      // Sync when form is submitted
      $(document).on("submit", ".primefit-variations-form, .cart", () => {
        this.syncButtonState();
      });

      // Sync when original quantity changes
      $(document).on("change", ".product-actions .quantity input[type='number']", () => {
        this.syncButtonState();
      });
    }

    createStickyButton() {
      const $originalButton = $(".single_add_to_cart_button");
      const $notifyButton = $(".notify-button");
      const $originalQuantity = $(".quantity");

      if (!$originalButton.length && !$notifyButton.length) {
        return;
      }

      // Determine which button to clone
      const $sourceButton = $originalButton.length
        ? $originalButton
        : $notifyButton;
      const buttonText = $sourceButton.text();
      const isDisabled = $sourceButton.prop("disabled");

      // Get quantity information - always start at 1, independent of cart
      const quantityValue = "1";
      const minValue =
        $originalQuantity.find("input[type='number']").attr("min") || "1";
      const maxValue =
        $originalQuantity.find("input[type='number']").attr("max") || "999";

      const stickyHtml = `
        <div class="sticky-add-to-cart" id="sticky-add-to-cart">
          <div class="sticky-add-to-cart-content">
            <div class="sticky-quantity-wrapper">
              <div class="sticky-quantity">
                <button class="minus" type="button">−</button>
                <input type="number" value="${quantityValue}" min="${minValue}" max="${maxValue}" class="sticky-quantity-input">
                <button class="plus" type="button">+</button>
              </div>
            </div>
            <button class="sticky-add-to-cart-button" ${
              isDisabled ? "disabled" : ""
            }>
              ${buttonText}
            </button>
          </div>
        </div>
      `;

      $("body").append(stickyHtml);
      this.bindStickyQuantityEvents();
    }

    bindStickyQuantityEvents() {
      // Handle sticky quantity button clicks
      $(document).on("click", ".sticky-quantity .plus", (e) => {
        e.preventDefault();
        this.increaseStickyQuantity();
      });

      $(document).on("click", ".sticky-quantity .minus", (e) => {
        e.preventDefault();
        this.decreaseStickyQuantity();
      });

      // Handle sticky quantity input changes
      $(document).on("change", ".sticky-quantity-input", (e) => {
        this.syncStickyQuantityToOriginal();
      });

      // Handle sticky quantity input keydown
      $(document).on("keydown", ".sticky-quantity-input", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          this.syncStickyQuantityToOriginal();
        }
      });
    }

    increaseStickyQuantity() {
      const $stickyInput = $(".sticky-quantity-input");
      const currentValue = parseInt($stickyInput.val()) || 1;
      const maxValue = parseInt($stickyInput.attr("max")) || 999;
      const newValue = Math.min(currentValue + 1, maxValue);

      $stickyInput.val(newValue);
      this.syncStickyQuantityToOriginal();
    }

    decreaseStickyQuantity() {
      const $stickyInput = $(".sticky-quantity-input");
      const currentValue = parseInt($stickyInput.val()) || 1;
      const minValue = parseInt($stickyInput.attr("min")) || 1;
      const newValue = Math.max(currentValue - 1, minValue);

      $stickyInput.val(newValue);
      this.syncStickyQuantityToOriginal();
    }

    syncStickyQuantityToOriginal() {
      const $stickyInput = $(".sticky-quantity-input");
      const $originalInput = $(".quantity input[type='number']");

      if ($stickyInput.length && $originalInput.length) {
        const stickyValue = $stickyInput.val();
        // Set value without triggering change event to prevent WooCommerce interference
        $originalInput.val(stickyValue);
        this.updateStickyQuantityButtons();
        
        // Update main quantity button states
        if (window.quantityControlsInstance && window.quantityControlsInstance.updateButtonStates) {
          window.quantityControlsInstance.updateButtonStates();
        }
      }
    }

    syncOriginalQuantityToSticky() {
      const $stickyInput = $(".sticky-quantity-input");
      const $originalInput = $(".quantity input[type='number']");

      if ($stickyInput.length && $originalInput.length) {
        const originalValue = $originalInput.val();
        // Set value without triggering change event to prevent WooCommerce interference
        $stickyInput.val(originalValue);
        this.updateStickyQuantityButtons();
      }
    }

    updateStickyQuantityButtons() {
      const $stickyInput = $(".sticky-quantity-input");
      const $stickyMinus = $(".sticky-quantity .minus");
      const $stickyPlus = $(".sticky-quantity .plus");

      if ($stickyInput.length && $stickyMinus.length && $stickyPlus.length) {
        const currentValue = parseInt($stickyInput.val()) || 1;
        const minValue = parseInt($stickyInput.attr("min")) || 1;
        const maxValue = parseInt($stickyInput.attr("max")) || 999;

        // Update decrease button state
        if (currentValue <= minValue) {
          $stickyMinus.prop("disabled", true);
        } else {
          $stickyMinus.prop("disabled", false);
        }

        // Update increase button state
        if (currentValue >= maxValue) {
          $stickyPlus.prop("disabled", true);
        } else {
          $stickyPlus.prop("disabled", false);
        }
      }
    }

    checkVisibility() {
      const $originalButton = $(".single_add_to_cart_button");
      const $notifyButton = $(".notify-button");
      const $stickyButton = $("#sticky-add-to-cart");

      if (!$stickyButton.length) {
        return;
      }

      // Check if any of the original buttons are visible
      const $sourceButton = $originalButton.length
        ? $originalButton
        : $notifyButton;

      if (!$sourceButton.length) {
        return;
      }

      const buttonRect = $sourceButton[0].getBoundingClientRect();
      const windowHeight = window.innerHeight;
      const isVisible =
        buttonRect.top >= 0 && buttonRect.bottom <= windowHeight;

      // Add a small buffer to prevent flickering
      const buffer = 50;
      const isVisibleWithBuffer =
        buttonRect.top >= -buffer && buttonRect.bottom <= windowHeight + buffer;

      if (!isVisibleWithBuffer) {
        $stickyButton.addClass("visible");
      } else {
        $stickyButton.removeClass("visible");
      }
    }

    handleStickyButtonClick() {
      const $originalButton = $(".single_add_to_cart_button");
      const $notifyButton = $(".notify-button");
      const $sourceButton = $originalButton.length
        ? $originalButton
        : $notifyButton;

      if ($sourceButton.length) {
        // For add to cart buttons, submit the form
        if ($originalButton.length) {
          const $form = $sourceButton.closest("form");
          if ($form.length) {
            $form.trigger("submit");
          } else {
            $sourceButton.trigger("click");
          }
        } else {
          // For notify buttons, just trigger click
          $sourceButton.trigger("click");
        }
      }
    }

    syncButtonState() {
      const $originalButton = $(".single_add_to_cart_button");
      const $notifyButton = $(".notify-button");
      const $stickyButton = $(".sticky-add-to-cart-button");
      const $stickyQuantityInput = $(".sticky-quantity-input");

      if (!$stickyButton.length) {
        return;
      }

      const $sourceButton = $originalButton.length
        ? $originalButton
        : $notifyButton;

      if ($sourceButton.length) {
        // Sync button text and disabled state
        $stickyButton.text($sourceButton.text());
        $stickyButton.prop("disabled", $sourceButton.prop("disabled"));
      }

      // Sync quantity values bidirectionally
      if ($stickyQuantityInput.length) {
        const $originalQuantityInput = $(".quantity input[type='number']");
        if ($originalQuantityInput.length) {
          const originalValue = parseInt($originalQuantityInput.val()) || 1;
          const stickyValue = parseInt($stickyQuantityInput.val()) || 1;
          
          // Always sync original to sticky to keep them in sync
          if (originalValue !== stickyValue) {
            $stickyQuantityInput.val(originalValue);
            this.updateStickyQuantityButtons();
          }
        }
      }
    }
  }

  /**
   * AJAX Add to Cart functionality to prevent form resubmission on refresh
   */
  class AjaxAddToCart {
    constructor() {
      this.isSubmitting = false; // Prevent multiple simultaneous submissions
      this.init();
    }

    init() {
      this.bindEvents();
    }

    bindEvents() {
      // Intercept all cart form submissions on single product pages
      $(document).on('submit', 'form.cart, form.variations_form, form.primefit-variations-form', (e) => {
        e.preventDefault();
        this.handleFormSubmission(e);
      });
    }

    handleFormSubmission(e) {
      // Prevent multiple simultaneous submissions
      if (this.isSubmitting) {
        console.log('Form submission already in progress, ignoring duplicate submission');
        return false;
      }
      
      const $form = $(e.target);
      const $button = $form.find('button[type="submit"], input[type="submit"]').first();
      
      // Validate form data
      if (!this.validateForm($form)) {
        return false;
      }

      // Get form data
      const formData = this.getFormData($form);
      
      // Mark as submitting
      this.isSubmitting = true;
      
      // Show loading state
      this.showLoadingState($button);
      
      // Submit via AJAX
      this.submitToCart(formData, $button);
    }

    validateForm($form) {
      // For variable products, ensure a variation is selected and all attributes are set
      if ($form.hasClass('variations_form') || $form.hasClass('primefit-variations-form')) {
        const variationId = $form.find('input[name="variation_id"]').val() || $form.find('.variation_id').val();
        
        if (!variationId || variationId === '0' || variationId === '') {
          this.showError('Please select all product options before adding to cart.');
          return false;
        }

        // Check if all required variation attributes are selected
        let missingOptions = [];
        $form.find('select[name^="attribute_"]').each(function() {
          const $select = $(this);
          const value = $select.val();
          const label = $select.closest('.variation-option').find('label').text() || 
                       $select.closest('.value').prev('.label').text() || 
                       $select.attr('name').replace('attribute_pa_', '').replace('attribute_', '');
          
          if (!value || value === '') {
            missingOptions.push(label);
          }
        });

        if (missingOptions.length > 0) {
          const optionText = missingOptions.length === 1 ? 'option' : 'options';
          this.showError(`Please select all product ${optionText}: ${missingOptions.join(', ')}`);
          return false;
        }

        // Validate quantity against stock limits for variations
        const quantity = parseInt($form.find('input[name="quantity"]').val()) || 1;
        if (quantity < 1) {
          this.showError('Please enter a valid quantity.');
          return false;
        }

        // Check stock limits for the selected variation
        const stockValidation = this.validateVariationStock(variationId, quantity);
        if (!stockValidation.valid) {
          this.showError(stockValidation.message);
          return false;
        }
      } else {
        // For simple products, validate quantity
        const quantity = parseInt($form.find('input[name="quantity"]').val()) || 1;
        if (quantity < 1) {
          this.showError('Please enter a valid quantity.');
          return false;
        }
      }

      return true;
    }

    validateVariationStock(variationId, quantity) {
      try {
        const variations = window.primefitProductData?.variations || [];
        
        for (let i = 0; i < variations.length; i++) {
          const variation = variations[i];
          if (!variation) continue;
          
          if (String(variation.variation_id) === String(variationId)) {
            // Check if variation is in stock
            if (!variation.is_in_stock) {
              return {
                valid: false,
                message: 'This variation is currently out of stock.'
              };
            }
            
            // Get maximum quantity allowed
            let maxQty = 999; // Default fallback
            
            // Prefer WooCommerce provided max_qty
            if (typeof variation.max_qty !== 'undefined' && variation.max_qty !== null && variation.max_qty !== '') {
              const parsedMax = parseInt(variation.max_qty);
              if (!isNaN(parsedMax) && parsedMax > 0) {
                maxQty = parsedMax;
              }
            } else if (typeof variation.stock_quantity !== 'undefined' && variation.stock_quantity !== null && variation.stock_quantity !== '') {
              const parsedStock = parseInt(variation.stock_quantity);
              if (!isNaN(parsedStock) && parsedStock > 0) {
                maxQty = parsedStock;
              }
            }
            
            // Check if requested quantity exceeds available stock
            if (quantity > maxQty) {
              return {
                valid: false,
                message: `Only ${maxQty} items available in stock. Please reduce your quantity.`
              };
            }
            
            return { valid: true };
          }
        }
        
        // If variation not found, return error
        return {
          valid: false,
          message: 'Selected variation not found. Please refresh the page and try again.'
        };
        
      } catch (error) {
        console.error('Error validating variation stock:', error);
        return {
          valid: false,
          message: 'Unable to validate stock. Please try again.'
        };
      }
    }

    getFormData($form) {
      const formArray = $form.serializeArray();
      const formData = {};
      
      // Convert form array to object
      $.each(formArray, function(i, field) {
        formData[field.name] = field.value;
      });

      // Ensure we have required fields
      formData.action = 'wc_ajax_add_to_cart';
      
      // Get product ID from various possible sources
      if (!formData.product_id) {
        formData.product_id = formData['add-to-cart'] || $form.data('product_id') || $form.find('input[name="product_id"]').val();
      }
      
      // Set default quantity if not provided
      if (!formData.quantity) {
        formData.quantity = 1;
      }

      // For variable products, ensure all variation attributes are included
      if ($form.hasClass('variations_form') || $form.hasClass('primefit-variations-form')) {
        // Get all variation attributes from the form
        $form.find('select[name^="attribute_"], input[name^="attribute_"]').each(function() {
          const $field = $(this);
          const name = $field.attr('name');
          const value = $field.val();
          
          if (name && value) {
            formData[name] = value;
          }
        });

        // Also check for variation data attributes on select elements
        $form.find('.variations select').each(function() {
          const $select = $(this);
          const attrName = $select.attr('name');
          const attrValue = $select.val();
          
          if (attrName && attrValue) {
            formData[attrName] = attrValue;
          }
        });

        // Ensure variation_id is properly set
        const variationId = $form.find('input[name="variation_id"]').val() || $form.find('.variation_id').val();
        if (variationId) {
          formData.variation_id = variationId;
        }
      }

      // Add security nonce
      if (window.primefit_cart_params && window.primefit_cart_params.add_to_cart_nonce) {
        formData.security = window.primefit_cart_params.add_to_cart_nonce;
      } else if (window.wc_add_to_cart_params && window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce) {
        formData.security = window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce;
      }

      console.log('Form data (updated):', formData); // Debug log

      return formData;
    }

    showLoadingState($button) {
      if ($button.length) {
        $button.prop('disabled', true)
               .addClass('loading')
               .data('original-text', $button.text())
               .text('Adding to Cart...');
      }
    }

    hideLoadingState($button, success = true) {
      if ($button.length) {
        const originalText = $button.data('original-text') || 'Add to Cart';
        
        $button.removeClass('loading')
               .prop('disabled', false);
        
        if (success) {
          $button.addClass('added').text('Added!');
          
          // Reset button text after 2 seconds
          setTimeout(() => {
            $button.removeClass('added').text(originalText);
          }, 2000);
        } else {
          $button.addClass('error').text('Error');
          
          // Reset button text after 3 seconds
          setTimeout(() => {
            $button.removeClass('error').text(originalText);
          }, 3000);
        }
      }
    }

    submitToCart(formData, $button, retryCount = 0) {
      const ajaxUrl = (window.primefit_cart_params && window.primefit_cart_params.ajax_url) || 
                     (window.wc_add_to_cart_params && window.wc_add_to_cart_params.ajax_url) || 
                     '/wp-admin/admin-ajax.php';

      // Set a shorter timeout for better user experience
      const timeoutId = setTimeout(() => {
        console.log('AJAX timeout - resetting button state');
        this.hideLoadingState($button, false);
      }, 8000); // 8 second timeout

      $.ajax({
        type: 'POST',
        url: ajaxUrl,
        data: formData,
        dataType: 'json',
        timeout: 8000, // Explicit timeout
        cache: false, // Prevent caching issues
        success: (response) => {
          clearTimeout(timeoutId);
          this.handleSuccess(response, $button);
        },
        error: (xhr, status, error) => {
          clearTimeout(timeoutId);
          
          // Retry logic for network errors
          if (retryCount < 2 && (status === 'timeout' || status === 'error' || xhr.status === 0)) {
            console.log(`Retrying AJAX request (attempt ${retryCount + 1}/3)`);
            setTimeout(() => {
              this.submitToCart(formData, $button, retryCount + 1);
            }, 1000 * (retryCount + 1)); // Exponential backoff
            return;
          }
          
          this.handleError(xhr, status, error, $button, retryCount);
        }
      });
    }

    handleSuccess(response, $button) {
      console.log('Add to cart response:', response); // Debug log

      // Check for actual errors vs success-with-redirect
      if (response.error) {
        console.log('Response has error flag:', response.error);
        console.log('Response data:', response.data);
        console.log('Response notice:', response.notice);
        console.log('Response fragments:', response.fragments);
        // If there are fragments, it means the product was actually added successfully
        // WooCommerce sometimes returns error: true for redirects/notices, not actual errors
        if (response.fragments && Object.keys(response.fragments).length > 0) {
          console.log('Product added successfully (with fragments despite error flag)');
          // Treat as success since we have fragments
        } else if (response.product_url && !response.data && !response.notice) {
          // This might be a redirect response, which could be success
          // Let's try to determine if it's actually an error or just a redirect
          console.log('Received redirect response, checking if product was actually added...');
          
          // Force refresh cart fragments to check if item was added
          this.checkCartAfterAdd($button);
          return;
        } else {
          // This appears to be an actual error
          let errorMessage = 'Failed to add product to cart.';
          
          if (response.data) {
            errorMessage = response.data;
          } else if (response.notice) {
            errorMessage = response.notice;
          }
          
          console.log('Actual error detected:', errorMessage);
          this.showError(errorMessage);
          this.hideLoadingState($button, false);
          return;
        }
      }

      // Success! Update cart fragments and show feedback
      if (response.fragments) {
        // Update cart count and other fragments
        $.each(response.fragments, function(key, value) {
          $(key).replaceWith(value);
        });
      }

      // Trigger WooCommerce events first
      $(document.body).trigger('update_checkout');
      $(document.body).trigger('wc_fragment_refresh');
      console.log('Triggering added_to_cart event with:', {fragments: response.fragments, cart_hash: response.cart_hash, button: $button}); // Debug log
      $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

      // Reset quantity inputs to 1 after WooCommerce events have completed
      // Use a delay to ensure WooCommerce has finished updating fragments
      setTimeout(() => {
        this.resetQuantityInputs();
      }, 100);

      // Show success state
      this.hideLoadingState($button, true);
      
      // Show success message
      this.showSuccess('');

      // Clean up URL to prevent issues with browser back/forward
      this.cleanUpURL();
      
      // Reset submission flag
      this.isSubmitting = false;
    }

    handleError(xhr, status, error, $button, retryCount = 0) {
      console.error('AJAX error adding to cart:', {xhr, status, error, retryCount}); // Debug logwoocommerce-mini-cart__item-details
      
      let errorMessage = 'Unable to add product to cart. Please try again.';
      
      // More specific error messages based on status
      if (status === 'timeout') {
        errorMessage = 'Request timed out. Please check your connection and try again.';
      } else if (status === 'error' && xhr.status === 0) {
        errorMessage = 'Network error. Please check your internet connection.';
      } else if (xhr.status === 403) {
        errorMessage = 'Security check failed. Please refresh the page and try again.';
      } else if (xhr.status === 500) {
        errorMessage = 'Server error. Please try again in a moment.';
      } else if (xhr.status === 404) {
        errorMessage = 'Service unavailable. Please try again later.';
      } else if (retryCount > 0) {
        errorMessage = 'Failed after multiple attempts. Please try again.';
      }
      
      this.showError(errorMessage);
      this.hideLoadingState($button, false);
      
      // Reset submission flag
      this.isSubmitting = false;
    }


    checkCartAfterAdd($button) {
      // Force refresh cart fragments to check if product was actually added
      const ajaxUrl = (window.primefit_cart_params && window.primefit_cart_params.ajax_url) || 
                     (window.wc_add_to_cart_params && window.wc_add_to_cart_params.ajax_url) || 
                     '/wp-admin/admin-ajax.php';

      $.ajax({
        type: 'POST',
        url: ajaxUrl,
        data: {
          action: 'woocommerce_get_refreshed_fragments'
        },
        success: function(response) {
          console.log('Cart check response:', response);
          
          if (response && response.fragments) {
            // Update fragments - this will show the new cart count if product was added
            $.each(response.fragments, function(key, value) {
              $(key).replaceWith(value);
            });

            // Trigger WooCommerce events first
            $(document.body).trigger('update_checkout');
            $(document.body).trigger('wc_fragment_refresh');
            console.log('Triggering added_to_cart event from checkCartAfterAdd with:', {fragments: response.fragments, cart_hash: response.cart_hash, button: $button}); // Debug log
            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

            // Reset quantity inputs to 1 after WooCommerce events have completed
            // Use a delay to ensure WooCommerce has finished updating fragments
            setTimeout(() => {
              this.resetQuantityInputs();
            }, 100);

            // Show success state
            this.hideLoadingState($button, true);
            this.showSuccess('');
            this.cleanUpURL();
          } else {
            // If no fragments, treat as error
            this.showError('Unable to verify if product was added. Please check your cart.');
            this.hideLoadingState($button, false);
          }
        }.bind(this),
        error: function() {
          // Fallback: show ambiguous message
          this.showError('Product may have been added. Please check your cart.');
          this.hideLoadingState($button, false);
        }.bind(this)
      });
    }

    resetQuantityInputs() {
      // Reset main quantity input to 1
      const $mainQuantityInput = $(".quantity input[type='number']");
      if ($mainQuantityInput.length) {
        $mainQuantityInput.val(1);
      }
      
      // Reset sticky quantity input to 1
      const $stickyQuantityInput = $(".sticky-quantity-input");
      if ($stickyQuantityInput.length) {
        $stickyQuantityInput.val(1);
        
        // Update sticky quantity button states
        if (window.stickyAddToCartInstance && window.stickyAddToCartInstance.updateStickyQuantityButtons) {
          window.stickyAddToCartInstance.updateStickyQuantityButtons();
        }
      }
      
      // Update main quantity button states
      if (window.quantityControlsInstance && window.quantityControlsInstance.updateButtonStates) {
        window.quantityControlsInstance.updateButtonStates();
      }
    }

    showSuccess(message) {
      // Show success notification (can be customized)
      if (message && message.trim()) {
        console.log('Add to cart success:', message);
      }
    }

    showError(message) {
      // Show error notification
      console.error('Add to cart error:', message);
      if (message && message.trim()) {
        // You can implement a toast notification system here
        alert(message);
      }
    }

    cleanUpURL() {
      // Remove any add-to-cart parameters from URL to prevent resubmission
      if (window.history && window.history.replaceState) {
        try {
          const url = new URL(window.location.href);
          url.searchParams.delete('add-to-cart');
          url.searchParams.delete('added-to-cart');
          url.searchParams.delete('quantity');
          
          const newSearch = url.searchParams.toString();
          const newUrl = url.pathname + (newSearch ? '?' + newSearch : '') + url.hash;
          
          window.history.replaceState({}, '', newUrl);
        } catch (e) {
          // Ignore if URL API not available
        }
      }
    }
  }

  /**
   * Initialize all functionality when document is ready
   */
  $(document).ready(function () {
    // Only initialize on single product pages
    if ($("body").hasClass("single-product")) {
      new ProductGallery();
      new ProductInformation();
      new ProductVariations();
      new NotifyAvailability();
      
      // Store instances globally for quantity reset functionality
      window.quantityControlsInstance = new WooCommerceQuantityControls();
      window.stickyAddToCartInstance = new StickyAddToCart();
      new AjaxAddToCart(); // Initialize AJAX cart functionality
    }
  });
})(jQuery);
