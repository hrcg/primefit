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
      // Prevent duplicate instances
      if (window.productGallery && window.productGallery !== this) {
        console.warn('ProductGallery already exists, returning existing instance');
        return window.productGallery;
      }

      // Cache DOM selectors for better performance
      this.$gallery = null;
      this.$mainImage = null;
      this.$thumbnails = null;
      this.$dots = null;
      this.currentIndex = 0;
      this.isAnimating = false;

      console.log('ProductGallery: Creating new instance');
      this.init();
    }

    init() {
      // Cache DOM elements immediately to avoid repeated queries
      this.cacheDOM();
      this.bindEvents();
      this.initSwipe();
    }

    cacheDOM() {
      this.$gallery = $(".product-gallery-container");
      if (this.$gallery.length) {
        this.$mainImage = this.$gallery.find(".main-product-image");
        this.$thumbnails = this.$gallery.find(".thumbnail-item");
        this.$dots = this.$gallery.find(".image-dot");
        this.currentIndex = parseInt(this.$mainImage.attr("data-image-index")) || 0;
        console.log(`Gallery: Initialized - Found ${this.$thumbnails.length} thumbnails, current index: ${this.currentIndex}`);
      }
    }

    // Method to completely reset gallery state
    resetGalleryState() {
      console.log('Gallery: Resetting gallery state');
      this.isAnimating = false;
      this.cacheDOM();
      this.$gallery.find('.main-image-wrapper').removeClass('loading');
    }

    // Method to clean up event listeners
    destroy() {
      if (this.$gallery) {
        this.$gallery.off('.thumbnail .dot .nav .swipe');
      }
      $(document).off('.gallery');
    }

    bindEvents() {
      // Use event delegation with optimized selectors and cached elements
      this.$gallery.on("click.thumbnail", ".thumbnail-item", (e) => {
        e.preventDefault();
        const index = $(e.currentTarget).data("image-index");
        this.switchImage(index);
      });

      this.$gallery.on("click.dot", ".image-dot", (e) => {
        e.preventDefault();
        const index = $(e.currentTarget).data("image-index");
        this.switchImage(index);
      });

      this.$gallery.on("click.nav", ".image-nav-prev", (e) => {
        e.preventDefault();
        this.previousImage();
      });

      this.$gallery.on("click.nav", ".image-nav-next", (e) => {
        e.preventDefault();
        this.nextImage();
      });

      // Optimized keyboard navigation with passive event listener and cached check
      $(document).on("keydown.gallery", (e) => {
        if (this.isAnimating) return; // Prevent multiple rapid keypresses

        // Use more specific check for better performance
        if (
          e.target.closest &&
          $(e.target).closest(".product-gallery-container").length
        ) {
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
      // Use cached DOM elements instead of re-querying
      if (!this.$gallery || !this.$gallery.length) return;

      // Refresh cached elements to ensure they're up to date
      this.$thumbnails = this.$gallery.find(".thumbnail-item");
      this.$dots = this.$gallery.find(".image-dot");
      this.$mainImage = this.$gallery.find(".main-product-image");

      const $thumbnails = this.$thumbnails;
      const $dots = this.$dots;
      const $mainImage = this.$mainImage;

      // Don't animate if clicking the same image or already animating
      if (this.currentIndex === index || this.isAnimating) {
        return;
      }

      // Prevent rapid clicking - ensure we're not in the middle of an animation
      if (this.$gallery.find('.main-image-wrapper').hasClass('loading')) {
        return;
      }

      // Determine slide direction
      const isNext = index > this.currentIndex;
      const slideDirection = isNext ? "right" : "left";

      // Get the thumbnail image to extract the correct URL
      const $thumbnailImg = $thumbnails.eq(index).find(".thumbnail-image");
      if ($thumbnailImg.length) {
        // Use the thumbnail URL directly - the PHP template already uses 'full' size
        // so these URLs should be pointing to the correct full-size images
        const imageUrl = $thumbnailImg.attr("src");
        const imageAlt = $thumbnailImg.attr("alt");

        console.log(`Gallery: Switching to image ${index} - URL: ${imageUrl.substring(0, 60)}..., Direction: ${slideDirection}`);

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

      // Update current index - do this before starting animation
      const oldIndex = this.currentIndex;
      this.currentIndex = index;
      console.log(`Gallery: Index updated from ${oldIndex} to ${index}`);

      // Update cached main image reference to prevent stale references
      this.$mainImage = this.$gallery.find(".main-product-image");
    }

    animateSlide($mainImage, imageUrl, imageAlt, index, direction) {
      // Use cached elements to avoid repeated DOM queries
      if (!this.$gallery || this.isAnimating) {
        return;
      }

      const $mainImageWrapper = this.$gallery.find(".main-image-wrapper");

      // Prevent multiple animations
      if ($mainImageWrapper.hasClass("loading")) {
        return;
      }

      // Set animation flag
      this.isAnimating = true;

      // Add loading state
      $mainImageWrapper.addClass("loading");

      // Add cache busting parameter to prevent browser caching issues
      const timestamp = Date.now();
      const cacheBustUrl = imageUrl + (imageUrl.includes('?') ? '&' : '?') + 't=' + timestamp;
      
      console.log(`Gallery: Attempting to load image - Original: ${imageUrl}, Cache-busted: ${cacheBustUrl}`);

      // Simplified image switch - DISABLED temp-image functionality
      const $newImage = $("<img>", {
        src: cacheBustUrl,
        alt: imageAlt,
        class: "main-product-image slide-in-active",
        "data-image-index": index,
      });

      // Simple image replacement with optimized callbacks
      $newImage.on("load", () => {
        console.log(`Gallery: Image loaded successfully - Index: ${index}, URL: ${cacheBustUrl.substring(0, 50)}...`);

        // Remove the old image after the new one has loaded successfully
        $mainImage.remove();
        $newImage.appendTo($mainImageWrapper);
        $mainImageWrapper.removeClass("loading");

        // Update the cached main image reference
        this.$mainImage = $newImage;

        this.isAnimating = false; // Reset animation flag
        console.log(`Gallery: Animation complete for image ${index}`);
      });

      // Fallback if image doesn't load
      $newImage.on("error", () => {
        console.error('Failed to load gallery image:', cacheBustUrl);
        console.error('Original URL:', imageUrl);
        
        // Try loading the image without cache busting as fallback
        console.log('Trying fallback without cache busting...');
        const fallbackImage = new Image();
        fallbackImage.onload = () => {
          console.log('Fallback image loaded successfully');
          $mainImage.remove();
          $newImage.attr('src', imageUrl); // Use original URL
          $newImage.appendTo($mainImageWrapper);
          $mainImageWrapper.removeClass("loading");
          this.$mainImage = $newImage;
          this.isAnimating = false;
        };
        fallbackImage.onerror = () => {
          console.error('Even fallback image failed to load');
          console.error('This suggests the image file does not exist at:', imageUrl);
          $mainImageWrapper.removeClass("loading");
          this.isAnimating = false;
        };
        fallbackImage.src = imageUrl;
      });
    }

    previousImage() {
      // Use cached elements and current index
      if (!this.$gallery || !this.$thumbnails || this.isAnimating) return;

      // Refresh cached elements to ensure they're current
      this.$thumbnails = this.$gallery.find(".thumbnail-item");
      this.$mainImage = this.$gallery.find(".main-product-image");

      const newIndex =
        this.currentIndex > 0 ? this.currentIndex - 1 : this.$thumbnails.length - 1;

      console.log(`Gallery: Previous image - Current: ${this.currentIndex}, New: ${newIndex}, Total: ${this.$thumbnails.length}`);
      
      // Prevent rapid clicking by adding a small delay
      if (this.lastClickTime && Date.now() - this.lastClickTime < 300) {
        console.log('Gallery: Click too rapid, ignoring');
        return;
      }
      this.lastClickTime = Date.now();
      
      this.switchImage(newIndex);
    }

    nextImage() {
      // Use cached elements and current index
      if (!this.$gallery || !this.$thumbnails || this.isAnimating) return;

      // Refresh cached elements to ensure they're current
      this.$thumbnails = this.$gallery.find(".thumbnail-item");
      this.$mainImage = this.$gallery.find(".main-product-image");

      const newIndex =
        this.currentIndex < this.$thumbnails.length - 1 ? this.currentIndex + 1 : 0;

      console.log(`Gallery: Next image - Current: ${this.currentIndex}, New: ${newIndex}, Total: ${this.$thumbnails.length}`);
      
      // Prevent rapid clicking by adding a small delay
      if (this.lastClickTime && Date.now() - this.lastClickTime < 300) {
        console.log('Gallery: Click too rapid, ignoring');
        return;
      }
      this.lastClickTime = Date.now();
      
      this.switchImage(newIndex);
    }

    initSwipe() {
      // Touch/swipe support for mobile - use cached elements
      if (!this.$gallery) return;

      const $productMainImage = this.$gallery.find(".product-main-image");
      if (!$productMainImage.length) return;

      let startX = 0;
      let startY = 0;
      let endX = 0;
      let endY = 0;
      let isScrolling = false;
      let touchStartTime = 0;
      let isSwipeGesture = false;

      // Use passive event listeners for better performance
      $productMainImage.on("touchstart.swipe", (e) => {
        startX = e.originalEvent.touches[0].clientX;
        startY = e.originalEvent.touches[0].clientY;
        touchStartTime = Date.now();
        isScrolling = false;
        isSwipeGesture = false;
      }, { passive: true });

      $productMainImage.on("touchmove.swipe", (e) => {
        const currentX = e.originalEvent.touches[0].clientX;
        const currentY = e.originalEvent.touches[0].clientY;
        const deltaX = Math.abs(currentX - startX);
        const deltaY = Math.abs(currentY - startY);

        // If vertical movement is greater than horizontal, allow scrolling
        if (deltaY > deltaX && deltaY > 10) {
          isScrolling = true;
          isSwipeGesture = false;
          return; // Allow default scroll behavior
        }

        // If horizontal movement is greater, prevent scrolling for swipe
        if (deltaX > deltaY && deltaX > 10) {
          isSwipeGesture = true;
          e.preventDefault();
        }
      });

      $productMainImage.on("touchend.swipe", (e) => {
        endX = e.originalEvent.changedTouches[0].clientX;
        endY = e.originalEvent.changedTouches[0].clientY;

        // Only handle swipe if it wasn't a scroll gesture and was a valid swipe
        if (!isScrolling && isSwipeGesture) {
          this.handleSwipe(startX, startY, endX, endY, touchStartTime);
        }
      });

      // Also add swipe support to the main image wrapper for better touch area
      const $mainImageWrapper = this.$gallery.find(".main-image-wrapper");
      if ($mainImageWrapper.length) {
        $mainImageWrapper.on("touchstart.swipe", (e) => {
          startX = e.originalEvent.touches[0].clientX;
          startY = e.originalEvent.touches[0].clientY;
          touchStartTime = Date.now();
          isScrolling = false;
          isSwipeGesture = false;
        }, { passive: true });

        $mainImageWrapper.on("touchmove.swipe", (e) => {
          const currentX = e.originalEvent.touches[0].clientX;
          const currentY = e.originalEvent.touches[0].clientY;
          const deltaX = Math.abs(currentX - startX);
          const deltaY = Math.abs(currentY - startY);

          // If vertical movement is greater than horizontal, allow scrolling
          if (deltaY > deltaX && deltaY > 10) {
            isScrolling = true;
            isSwipeGesture = false;
            return; // Allow default scroll behavior
          }

          // If horizontal movement is greater, prevent scrolling for swipe
          if (deltaX > deltaY && deltaX > 10) {
            isSwipeGesture = true;
            e.preventDefault();
          }
        });

        $mainImageWrapper.on("touchend.swipe", (e) => {
          endX = e.originalEvent.changedTouches[0].clientX;
          endY = e.originalEvent.changedTouches[0].clientY;

          // Only handle swipe if it wasn't a scroll gesture and was a valid swipe
          if (!isScrolling && isSwipeGesture) {
            this.handleSwipe(startX, startY, endX, endY);
          }
        });
      }
    }

    handleSwipe(startX, startY, endX, endY, touchStartTime = 0) {
      const deltaX = endX - startX;
      const deltaY = endY - startY;
      const minSwipeDistance = 30; // Reduced for better responsiveness
      const maxSwipeTime = 500; // Maximum time for a swipe gesture
      const swipeTime = Date.now() - touchStartTime;

      // Debug logging for mobile testing
      console.log('Swipe detected:', {
        deltaX,
        deltaY,
        minDistance: minSwipeDistance,
        swipeTime,
        isHorizontal: Math.abs(deltaX) > Math.abs(deltaY)
      });

      // Only handle horizontal swipes that are quick enough and meet distance requirements
      if (
        Math.abs(deltaX) > Math.abs(deltaY) &&
        Math.abs(deltaX) > minSwipeDistance &&
        swipeTime < maxSwipeTime
      ) {
        if (deltaX > 0) {
          console.log('Swipe left - previous image');
          this.previousImage();
        } else {
          console.log('Swipe right - next image');
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
      this.initializeCollapsibleSections();
    }

    initializeCollapsibleSections() {
      // Initialize collapsible sections to be open by default
      $(".collapsible-content").each(function () {
        const $content = $(this);
        const $section = $content.closest(".collapsible-section");
        const $icon = $section.find(".collapsible-icon");

        // Remove closed class if it exists and set initial icon rotation for open state
        $content.removeClass("closed");
        $icon.css("transform", "rotate(180deg)");
        $content.attr("aria-expanded", "true");
      });
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
        const $content = $toggle
          .closest(".collapsible-section")
          .find(".collapsible-content")
          .first();
        const $icon = $toggle.find(".collapsible-icon");

        if ($content.hasClass("closed")) {
          $content.removeClass("closed");
          $icon.css("transform", "rotate(180deg)");
        } else {
          $content.addClass("closed");
          $icon.css("transform", "rotate(0deg)");
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
      // Cache DOM selectors for better performance
      this.$colorOptions = null;
      this.$sizeOptions = null;
      this.$addToCartButton = null;
      this.$variationForm = null;
      this.isInitializing = true; // Flag to prevent auto-add during initialization
      this.hasUserInteracted = false; // Flag to track if user has interacted with the page
      this.debouncedInputs = new Map(); // Store debounced input handlers
      this.updateTimer = null; // Timer for batch DOM updates
      this.pendingUpdates = new Set(); // Track pending updates

      this.init();
    }

    init() {
      this.cacheDOM();
      this.bindEvents();
      this.initializeVariations();
      this.initializeVirtualScrolling();
      this.initializeDebouncedInputs();

      // Add a small delay to ensure page is fully loaded before allowing auto-add
      this.updateTimer = window.memoryLeakManager.setTimeout(() => {
        this.isInitializing = false; // Set to false after initialization is complete
      }, 100);
    }

    cacheDOM() {
      this.$colorOptions = $(".color-option");
      this.$sizeOptions = $(".size-option");
      this.$addToCartButton = $(".single_add_to_cart_button");
      this.$variationForm = $(".primefit-variations-form");
    }

    bindEvents() {
      // Color selection with memory leak management
      window.memoryLeakManager.addEventListener(document, 'click', (e) => {
        if (e.target.matches('.color-option')) {
          e.preventDefault();
          this.hasUserInteracted = true; // Mark that user has interacted
          const $option = $(e.target);
          this.selectColor($option);
        }
      });

      // Size selection with memory leak management
      window.memoryLeakManager.addEventListener(document, 'click', (e) => {
        if (e.target.matches('.size-option')) {
          e.preventDefault();
          this.hasUserInteracted = true; // Mark that user has interacted
          const $option = $(e.target);
          this.selectSize($option);
        }
      });

      // Form submission with memory leak management
      window.memoryLeakManager.addEventListener(document, 'submit', (e) => {
        if (e.target.matches('.primefit-variations-form')) {
          this.handleFormSubmission(e);
        }
      });

      // Add cleanup function to memory manager
      window.memoryLeakManager.addCleanupFunction(() => {
        this.cleanup();
      });
    }

    initializeVirtualScrolling() {
      // Temporarily disable virtual scrolling to fix size option issues
      // TODO: Re-enable after fixing integration with selection system
      return;

      if (this.$sizeContainer.length && this.$sizeOptions.length > 10) {
        // Only enable virtual scrolling for products with many size options
        this.virtualScroller = new VirtualSizeOptions(this.$sizeContainer[0], {
          itemHeight: 50,
          visibleCount: 8
        });

        // Add CSS classes for performance optimizations
        this.$sizeContainer.addClass('virtual-scroll large-list');

        // Update virtual scroller when size options change
        this.virtualScroller.updateItems();
      }
    }

    initializeDebouncedInputs() {
      // Debounce quantity input changes
      const $quantityInput = $('input[name="quantity"]');
      if ($quantityInput.length) {
        const debouncedQuantity = new DebouncedInput($quantityInput[0], (e) => {
          this.updateQuantity(e.target.value);
        }, 150);

        this.debouncedInputs.set('quantity', debouncedQuantity);
      }

      // Debounce search inputs if they exist
      const $searchInputs = $('input[type="search"], input[name*="search"]');
      $searchInputs.each((index, input) => {
        const debouncedSearch = new DebouncedInput(input, (e) => {
          this.handleSearchInput(e.target.value);
        }, 300);

        this.debouncedInputs.set(`search-${index}`, debouncedSearch);
      });
    }

    batchUpdateDOM(updates) {
      // Batch DOM updates to reduce reflows
      if (this.updateTimer) {
        clearTimeout(this.updateTimer);
      }

      this.pendingUpdates.add(updates);

      this.updateTimer = window.memoryLeakManager.setTimeout(() => {
        this.processPendingUpdates();
      }, 16); // ~60fps
    }

    processPendingUpdates() {
      const updates = Array.from(this.pendingUpdates);
      this.pendingUpdates.clear();

      // Add updating class to prevent transitions during batch updates
      $('body').addClass('product-variations-updating');

      // Process all updates in a single DOM manipulation batch
      requestAnimationFrame(() => {
        updates.forEach(update => update());

        // Remove updating class after updates complete
        setTimeout(() => {
          $('body').removeClass('product-variations-updating');
        }, 16); // Next frame
      });
    }

    initializeVariations() {
      // Get default values from product data
      const defaultColor = window.primefitProductData?.defaultColor;
      const defaultSize = window.primefitProductData?.defaultSize;

      // Initialize with default color if available, otherwise first color
      let $activeColor = this.$colorOptions.filter(".active").first();

      if (defaultColor) {
        const $defaultColorOption = this.$colorOptions.filter(`[data-color="${defaultColor}"]`);
        if ($defaultColorOption.length) {
          $activeColor = $defaultColorOption;
          // Update active state
          this.$colorOptions.removeClass("active");
          $defaultColorOption.addClass("active");
        }
      }

      if ($activeColor.length) {
        // Initialize color without triggering auto-add
        this.initializeColor($activeColor);
      }

      // Select default size if available
      if (defaultSize) {
        const $defaultSizeOption = this.$sizeOptions.filter(`[data-size="${defaultSize}"]`);
        if (
          $defaultSizeOption.length &&
          $defaultSizeOption.is(":visible") &&
          !$defaultSizeOption.prop("disabled")
        ) {
          this.$sizeOptions.removeClass("selected");
          $defaultSizeOption.addClass("selected");
        }
      }

      // Update add to cart button state
      this.updateAddToCartButton();

      // Initialize WooCommerce variation form inputs with defaults
      if (defaultColor && defaultSize) {
        this.updateVariationFormInputs(defaultColor, defaultSize);
      }
    }

    initializeColor($option) {
      // Use cached selectors for better performance
      this.$colorOptions.removeClass("active");
      $option.addClass("active");

      const color = $option.data("color");

      // Update product color display - use display name if available
      const $colorOption = this.$colorOptions.filter(`[data-color="${color}"]`);
      const displayColor = $colorOption.data("color-display") || color;
      $(".color-value").text(displayColor);

      // Update gallery - switch to variation-specific gallery if available
      this.updateGalleryForColor(color);

      // Update available sizes from variation stock for this color
      const sizesForColor = this.getAvailableSizesForColor(color);
      this.updateAvailableSizes(sizesForColor);

      // Update add to cart button
      this.updateAddToCartButton();

      // Don't auto-add during initialization
    }

    selectColor($option) {
      // Use batch DOM updates for better performance
      this.batchUpdateDOM(() => {
        // Use cached selectors for better performance
        this.$colorOptions.removeClass("active");
        $option.addClass("active");

        const color = $option.data("color");
        const variationImage = $option.data("variation-image");

        // Update product color display - use display name if available
        const $colorOption = this.$colorOptions.filter(`[data-color="${color}"]`);
        const displayColor = $colorOption.data("color-display") || color;
        $(".color-value").text(displayColor);

        // Update gallery - switch to variation-specific gallery if available
        this.updateGalleryForColor(color);

        // Update sizes based on actual stock for selected color
        const sizesForColor = this.getAvailableSizesForColor(color);
        this.updateAvailableSizes(sizesForColor);

        // Update add to cart button
        this.updateAddToCartButton();
      });
      // No auto add
    }

    selectSize($option) {
      // Use batch DOM updates for better performance
      this.batchUpdateDOM(() => {
        // Use cached selectors for better performance
        this.$sizeOptions.removeClass("selected");
        $option.addClass("selected");

        // Update add to cart button
        this.updateAddToCartButton();
      });
      // No auto add
    }

    updateGalleryForColor(color) {
      // Use cached selectors for better performance
      const $colorOption = this.$colorOptions.filter(`[data-color="${color}"]`);
      const variationImage = $colorOption.data("variation-image");

      if (variationImage) {
        this.updateGalleryImage(variationImage);
      }
    }

    updateGalleryImage(imageUrl) {
      // Use cached gallery instance elements
      if (!this.$gallery || !this.$mainImage) return;

      if (imageUrl) {
        // Use slide animation for variation image changes
        const currentIndex = this.currentIndex;
        const imageAlt = this.$mainImage.attr("alt") || "";

        // Use the global gallery instance to avoid creating duplicates
        if (window.productGallery) {
          window.productGallery.animateSlide(
            this.$mainImage,
            imageUrl,
            imageAlt,
            currentIndex,
            "right"
          );
        } else {
          console.warn('ProductGallery instance not found for variation image');
        }
      } else {
        // Fallback to first gallery image if no variation image
        const $firstThumbnail = this.$thumbnails.first();
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
            const currentIndex = this.currentIndex;
            const imageAlt = this.$mainImage.attr("alt") || "";

            if (window.productGallery) {
              window.productGallery.animateSlide(
                this.$mainImage,
                fullSizeImage,
                imageAlt,
                currentIndex,
                "right"
              );
            } else {
              console.warn('ProductGallery instance not found for fallback image');
            }
          }
        }
      }
    }

    updateAvailableSizes(availableSizes) {
      const sizes = Array.isArray(availableSizes) ? availableSizes : [];

      this.batchUpdateDOM(() => {
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
      });

      // If the selected size is no longer available, clear selection
      const $selectedSize = $(".size-option.selected");
      if ($selectedSize.length && $selectedSize.prop("disabled")) {
        this.batchUpdateDOM(() => {
          $selectedSize.removeClass("selected");
        });
      }
    }

    updateQuantity(value) {
      // Optimized quantity update with debouncing
      if (value && parseInt(value) > 0) {
        this.batchUpdateDOM(() => {
          $('input[name="quantity"]').val(value);
          this.updateAddToCartButton();
        });
      }
    }

    handleSearchInput(value) {
      // Handle search input with debouncing (if search functionality exists)
      if (typeof this.onSearchInput === 'function') {
        this.onSearchInput(value);
      }
    }

    cleanup() {
      // Clean up all resources
      if (this.updateTimer) {
        window.memoryLeakManager.clearTimeout(this.updateTimer);
        this.updateTimer = null;
      }

      // Clean up debounced inputs
      this.debouncedInputs.forEach((debouncedInput, key) => {
        debouncedInput.destroy();
      });
      this.debouncedInputs.clear();

      // Clear pending updates
      this.pendingUpdates.clear();
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
      // Use cached selectors for better performance
      const $selectedColor = this.$colorOptions.filter(".active");
      const $selectedSize = this.$sizeOptions.filter(".selected");

      if ($selectedColor.length && $selectedSize.length) {
        this.$addToCartButton.prop("disabled", false).text("ADD TO CART");
        // Ensure variation_id is set when both options selected
        const colorValue = $selectedColor.data("color");
        const sizeValue = $selectedSize.data("size");
        const variationId = this.findVariationId(colorValue, sizeValue);
        if (variationId) {
          $(".variation_id").val(variationId);

          // CRITICAL: Update WooCommerce variation form inputs
          this.updateVariationFormInputs(colorValue, sizeValue);

          // Update quantity min/max based on selected variation stock/limits
          this.updateQuantityMaxForVariation(variationId);
        }
      } else {
        this.$addToCartButton.prop("disabled", true).text("SELECT OPTIONS");
        $(".variation_id").val("0");

        // Clear variation form inputs
        this.clearVariationFormInputs();

        // Reset quantity bounds to defaults when no valid variation is selected
        this.setQuantityBounds(1, 999);
      }
    }

    /**
     * Update WooCommerce variation form inputs with selected attributes
     * This is critical for WooCommerce's variation validation to work
     */
    updateVariationFormInputs(colorValue, sizeValue) {
      // Update color attribute input
      if (colorValue) {
        // Try different possible attribute names for color
        const colorInputs = [
          ".attribute_color_input",
          'input[name="attribute_pa_color"]',
          'input[name="attribute_color"]',
          'select[name="attribute_pa_color"]',
          'select[name="attribute_color"]',
        ];

        colorInputs.forEach((selector) => {
          const $input = $(selector);
          if ($input.length) {
            if ($input.is("select")) {
              $input.val(colorValue).trigger("change");
            } else {
              $input.val(colorValue);
            }
          }
        });
      }

      // Update size attribute input
      if (sizeValue) {
        // Try different possible attribute names for size
        const sizeInputs = [
          ".attribute_size_input",
          'input[name="attribute_pa_size"]',
          'input[name="attribute_size"]',
          'select[name="attribute_pa_size"]',
          'select[name="attribute_size"]',
        ];

        sizeInputs.forEach((selector) => {
          const $input = $(selector);
          if ($input.length) {
            if ($input.is("select")) {
              $input.val(sizeValue).trigger("change");
            } else {
              $input.val(sizeValue);
            }
          }
        });
      }

      // Trigger WooCommerce variation change event
      $(".variations_form, .primefit-variations-form").trigger(
        "woocommerce_variation_select_change"
      );
    }

    /**
     * Clear WooCommerce variation form inputs
     */
    clearVariationFormInputs() {
      // Clear color attribute inputs
      const colorInputs = [
        ".attribute_color_input",
        'input[name="attribute_pa_color"]',
        'input[name="attribute_color"]',
        'select[name="attribute_pa_color"]',
        'select[name="attribute_color"]',
      ];

      colorInputs.forEach((selector) => {
        const $input = $(selector);
        if ($input.length) {
          if ($input.is("select")) {
            $input.val("").trigger("change");
          } else {
            $input.val("");
          }
        }
      });

      // Clear size attribute inputs
      const sizeInputs = [
        ".attribute_size_input",
        'input[name="attribute_pa_size"]',
        'input[name="attribute_size"]',
        'select[name="attribute_pa_size"]',
        'select[name="attribute_size"]',
      ];

      sizeInputs.forEach((selector) => {
        const $input = $(selector);
        if ($input.length) {
          if ($input.is("select")) {
            $input.val("").trigger("change");
          } else {
            $input.val("");
          }
        }
      });
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
        const list =
          window.primefitProductData && window.primefitProductData.variations
            ? window.primefitProductData.variations
            : [];
        let maxQty = 999;
        const minQty = 1;

        for (let i = 0; i < list.length; i++) {
          const v = list[i];
          if (!v) continue;
          if (String(v.variation_id) === String(variationId)) {
            // Prefer WooCommerce provided max_qty on available_variations
            if (
              typeof v.max_qty !== "undefined" &&
              v.max_qty !== null &&
              v.max_qty !== ""
            ) {
              const parsedMax = parseInt(v.max_qty);
              if (!isNaN(parsedMax) && parsedMax > 0) {
                maxQty = parsedMax;
              }
            } else if (
              typeof v.stock_quantity !== "undefined" &&
              v.stock_quantity !== null &&
              v.stock_quantity !== ""
            ) {
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
      const $mainInput = $(
        ".product-actions .quantity input[type='number']"
      ).first();
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
        const clampedSticky = Math.max(
          minValue,
          Math.min(currentSticky, maxValue)
        );
        $stickyInput.val(clampedSticky);
        if (
          window.stickyAddToCartInstance &&
          window.stickyAddToCartInstance.updateStickyQuantityButtons
        ) {
          window.stickyAddToCartInstance.updateStickyQuantityButtons();
        }
      }

      if (
        window.quantityControlsInstance &&
        window.quantityControlsInstance.updateButtonStates
      ) {
        window.quantityControlsInstance.updateButtonStates();
      }
    }

    autoAddToCart() {
      // Don't auto-add during initialization or if user hasn't interacted
      if (this.isInitializing || !this.hasUserInteracted) {
        return;
      }

      // Additional check: don't auto-add if page was just loaded/refreshed
      if (
        performance.navigation &&
        performance.navigation.type === performance.navigation.TYPE_RELOAD
      ) {
        // If this is a page reload, don't auto-add for the first few seconds
        const timeSinceLoad = Date.now() - performance.timing.navigationStart;
        if (timeSinceLoad < 2000) {
          // 2 seconds
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
      $addToCartButton
        .addClass("loading")
        .prop("disabled", true)
        .text("Adding...");

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
      $.each(formArray, function (i, field) {
        formData[field.name] = field.value;
      });

      // Ensure we have required fields
      formData.action = "wc_ajax_add_to_cart";

      // Get product ID
      if (!formData.product_id) {
        formData.product_id =
          formData["add-to-cart"] ||
          $form.data("product_id") ||
          $form.find('input[name="product_id"]').val();
      }

      // Set default quantity if not provided
      if (!formData.quantity) {
        formData.quantity = 1;
      }

      // Add security nonce
      if (
        window.primefit_cart_params &&
        window.primefit_cart_params.add_to_cart_nonce
      ) {
        formData.security = window.primefit_cart_params.add_to_cart_nonce;
      } else if (
        window.wc_add_to_cart_params &&
        window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce
      ) {
        formData.security =
          window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce;
      }

      return formData;
    }

    submitToCartAjax(formData, $button) {
      const ajaxUrl =
        (window.primefit_cart_params && window.primefit_cart_params.ajax_url) ||
        (window.wc_add_to_cart_params &&
          window.wc_add_to_cart_params.ajax_url) ||
        "/wp-admin/admin-ajax.php";

      // Use requestAnimationFrame for smoother UI updates
      requestAnimationFrame(() => {
        // Check for browser cache first
        if (this.checkBrowserCache(formData)) {
          return; // Data found in cache, skip AJAX
        }

        // Implement connection pooling and request batching
        this.batchAjaxRequest({
          type: "POST",
          url: ajaxUrl,
          data: formData,
          dataType: "json",
          timeout: 8000, // Increased timeout for reliability
          cache: false,
          success: (response) => {
            this.handleAjaxSuccess(response, $button);
          },
          error: (xhr, status, error) => {
            this.handleAjaxError(xhr, status, error, $button);
          },
        });
      });
    }

    // Check browser cache before making AJAX requests
    checkBrowserCache(formData) {
      if (typeof(Storage) !== 'undefined' && formData.product_id) {
        const cacheKey = `primefit_product_${formData.product_id}`;
        const cached = localStorage.getItem(cacheKey);

        if (cached) {
          const cacheData = JSON.parse(cached);
          if (cacheData.expires && cacheData.expires > Date.now() / 1000) {
            // Use cached data instead of making AJAX call
            console.log('Using cached product data for faster response');
            return true;
          } else {
            // Expired cache, remove it
            localStorage.removeItem(cacheKey);
          }
        }
      }
      return false;
    }

    // Batch AJAX requests to reduce network overhead
    batchAjaxRequest(options) {
      // Initialize request queue if not exists
      this.requestQueue = this.requestQueue || [];
      this.requestQueue.push(options);

      // Process queue if not already processing
      if (this.isProcessingQueue) return;
      this.isProcessingQueue = true;

      this.processRequestQueue();
    }

    processRequestQueue() {
      if (this.requestQueue.length === 0) {
        this.isProcessingQueue = false;
        return;
      }

      const options = this.requestQueue.shift();

      // Add abort controller for better request management
      const controller = new AbortController();
      options.abort = controller;

      $.ajax(options).always(() => {
        // Process next request in queue
        setTimeout(() => this.processRequestQueue(), 100);
      });
    }

    // Abort pending requests
    abortPendingRequests() {
      if (this.requestQueue && this.requestQueue.length > 0) {
        this.requestQueue.forEach(request => {
          if (request.abort) {
            request.abort.abort();
          }
        });
        this.requestQueue = [];
      }
    }

    handleAjaxSuccess(response, $button) {
      // Check for actual errors vs success-with-redirect
      if (response.error) {
        // If there are fragments, it means the product was actually added successfully
        if (response.fragments && Object.keys(response.fragments).length > 0) {
          // Treat as success since we have fragments
        } else if (response.product_url && !response.data && !response.notice) {
          // This might be a redirect response, which could be success

          // Force refresh cart fragments to check if item was added
          this.checkCartAfterAutoAdd($button);
          return;
        } else {
          // This appears to be an actual error
          let errorMessage = "Failed to add product to cart.";

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
        $.each(response.fragments, function (key, value) {
          $(key).replaceWith(value);
        });
      }

      // Reset quantity inputs to 1 after successful auto-add
      this.resetQuantityInputs();

      // Trigger WooCommerce events
      $(document.body).trigger("update_checkout");
      $(document.body).trigger("wc_fragment_refresh");
      $(document.body).trigger("added_to_cart", [
        response.fragments,
        response.cart_hash,
        $button,
      ]);

      // Show success state
      this.hideAutoAddLoadingState($button, true);

      // Show success message
      this.showAutoAddSuccess("");
    }

    handleAjaxError(xhr, status, error, $button) {
      let errorMessage = "Unable to add product to cart. Please try again.";

      // More specific error messages based on status
      if (status === "timeout") {
        errorMessage =
          "Request timed out. Please check your connection and try again.";
      } else if (status === "error" && xhr.status === 0) {
        errorMessage = "Network error. Please check your internet connection.";
      } else if (xhr.status === 403) {
        errorMessage =
          "Security check failed. Please refresh the page and try again.";
      } else if (xhr.status === 500) {
        errorMessage = "Server error. Please try again in a moment.";
      } else if (xhr.status === 404) {
        errorMessage = "Service unavailable. Please try again later.";
      }

      this.showAutoAddError(errorMessage);
      this.hideAutoAddLoadingState($button, false);
    }

    hideAutoAddLoadingState($button, success = true) {
      if ($button.length) {
        const originalText = $button.data("original-text") || "Add to Cart";

        $button.removeClass("loading").prop("disabled", false);

        // Remove loading state from size option
        $(".size-option.loading").removeClass("loading");

        if (success) {
          $button.addClass("added").text("Added!");

          // Reset button text after 2 seconds
          setTimeout(() => {
            $button.removeClass("added").text(originalText);
          }, 2000);
        } else {
          $button.addClass("error").text("Error");

          // Reset button text after 3 seconds
          setTimeout(() => {
            $button.removeClass("error").text(originalText);
          }, 3000);
        }
      }
    }

    checkCartAfterAutoAdd($button) {
      // Force refresh cart fragments to check if product was actually added
      const ajaxUrl =
        (window.primefit_cart_params && window.primefit_cart_params.ajax_url) ||
        (window.wc_add_to_cart_params &&
          window.wc_add_to_cart_params.ajax_url) ||
        "/wp-admin/admin-ajax.php";

      $.ajax({
        type: "POST",
        url: ajaxUrl,
        data: {
          action: "woocommerce_get_refreshed_fragments",
        },
        success: function (response) {
          if (response && response.fragments) {
            // Update fragments - this will show the new cart count if product was added
            $.each(response.fragments, function (key, value) {
              $(key).replaceWith(value);
            });

            // Reset quantity inputs to 1 after successful auto-add
            this.resetQuantityInputs();

            // Trigger WooCommerce events
            $(document.body).trigger("update_checkout");
            $(document.body).trigger("wc_fragment_refresh");
            $(document.body).trigger("added_to_cart", [
              response.fragments,
              response.cart_hash,
              $button,
            ]);

            // Show success state
            this.hideAutoAddLoadingState($button, true);
            this.showAutoAddSuccess("");
          } else {
            // If no fragments, treat as error
            this.showAutoAddError(
              "Unable to verify if product was added. Please check your cart."
            );
            this.hideAutoAddLoadingState($button, false);
          }
        }.bind(this),
        error: function () {
          // Fallback: show ambiguous message
          this.showAutoAddError(
            "Product may have been added. Please check your cart."
          );
          this.hideAutoAddLoadingState($button, false);
        }.bind(this),
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
        if (
          window.stickyAddToCartInstance &&
          window.stickyAddToCartInstance.updateStickyQuantityButtons
        ) {
          window.stickyAddToCartInstance.updateStickyQuantityButtons();
        }
      }

      // Update main quantity button states
      if (
        window.quantityControlsInstance &&
        window.quantityControlsInstance.updateButtonStates
      ) {
        window.quantityControlsInstance.updateButtonStates();
      }
    }

    showAutoAddSuccess(message) {
      // Show success notification (can be customized)
      if (message && message.trim()) {
      }
    }

    showAutoAddError(message) {
      // Show error notification
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
      $(document).on(
        "change",
        ".product-actions .quantity input[type='number']",
        (e) => {
          this.validateQuantity();
        }
      );

      // Handle input keydown
      $(document).on(
        "keydown",
        ".product-actions .quantity input[type='number']",
        (e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            this.validateQuantity();
          }
        }
      );
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
        $input.on("input.independent-quantity", function () {
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
        if (
          window.stickyAddToCartInstance &&
          window.stickyAddToCartInstance.updateStickyQuantityButtons
        ) {
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
      $(document).on(
        "change",
        ".product-actions .quantity input[type='number']",
        () => {
          this.syncButtonState();
        }
      );
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
        if (
          window.quantityControlsInstance &&
          window.quantityControlsInstance.updateButtonStates
        ) {
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

    // Method to reset submission flag (useful for external calls)
    resetSubmissionFlag() {
      this.isSubmitting = false;
    }

    init() {
      this.bindEvents();
    }

    bindEvents() {
      // Intercept all cart form submissions on single product pages
      $(document).on(
        "submit",
        "form.cart, form.variations_form, form.primefit-variations-form",
        (e) => {
          // Skip temporary forms created by product loop functionality
          const $form = $(e.target);
          if ($form.hasClass("temp-form") || $form.data("temp-form")) {
            return; // Let the form submit normally
          }

          e.preventDefault();
          this.handleFormSubmission(e);
        }
      );
    }

    handleFormSubmission(e) {
      // Prevent multiple simultaneous submissions
      if (this.isSubmitting) {
        return false;
      }

      const $form = $(e.target);
      const $button = $form
        .find('button[type="submit"], input[type="submit"]')
        .first();

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
      if (
        $form.hasClass("variations_form") ||
        $form.hasClass("primefit-variations-form")
      ) {
        const variationId =
          $form.find('input[name="variation_id"]').val() ||
          $form.find(".variation_id").val();

        if (!variationId || variationId === "0" || variationId === "") {
          this.showError(
            "Please select all product options before adding to cart."
          );
          return false;
        }

        // Check if all required variation attributes are selected
        let missingOptions = [];
        $form.find('select[name^="attribute_"]').each(function () {
          const $select = $(this);
          const value = $select.val();
          const label =
            $select.closest(".variation-option").find("label").text() ||
            $select.closest(".value").prev(".label").text() ||
            $select
              .attr("name")
              .replace("attribute_pa_", "")
              .replace("attribute_", "");

          if (!value || value === "") {
            missingOptions.push(label);
          }
        });

        if (missingOptions.length > 0) {
          const optionText = missingOptions.length === 1 ? "option" : "options";
          this.showError(
            `Please select all product ${optionText}: ${missingOptions.join(
              ", "
            )}`
          );
          return false;
        }

        // Validate quantity against stock limits for variations
        const quantity =
          parseInt($form.find('input[name="quantity"]').val()) || 1;
        if (quantity < 1) {
          this.showError("Please enter a valid quantity.");
          return false;
        }

        // Check stock limits for the selected variation
        const stockValidation = this.validateVariationStock(
          variationId,
          quantity
        );
        if (!stockValidation.valid) {
          this.showError(stockValidation.message);
          return false;
        }
      } else {
        // For simple products, validate quantity
        const quantity =
          parseInt($form.find('input[name="quantity"]').val()) || 1;
        if (quantity < 1) {
          this.showError("Please enter a valid quantity.");
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
                message: "This variation is currently out of stock.",
              };
            }

            // Get maximum quantity allowed
            let maxQty = 999; // Default fallback

            // Prefer WooCommerce provided max_qty
            if (
              typeof variation.max_qty !== "undefined" &&
              variation.max_qty !== null &&
              variation.max_qty !== ""
            ) {
              const parsedMax = parseInt(variation.max_qty);
              if (!isNaN(parsedMax) && parsedMax > 0) {
                maxQty = parsedMax;
              }
            } else if (
              typeof variation.stock_quantity !== "undefined" &&
              variation.stock_quantity !== null &&
              variation.stock_quantity !== ""
            ) {
              const parsedStock = parseInt(variation.stock_quantity);
              if (!isNaN(parsedStock) && parsedStock > 0) {
                maxQty = parsedStock;
              }
            }

            // Check if requested quantity exceeds available stock
            if (quantity > maxQty) {
              return {
                valid: false,
                message: `Only ${maxQty} items available in stock. Please reduce your quantity.`,
              };
            }

            return { valid: true };
          }
        }

        // If variation not found, return error
        return {
          valid: false,
          message:
            "Selected variation not found. Please refresh the page and try again.",
        };
      } catch (error) {
        return {
          valid: false,
          message: "Unable to validate stock. Please try again.",
        };
      }
    }

    getFormData($form) {
      const formArray = $form.serializeArray();
      const formData = {};

      // Convert form array to object
      $.each(formArray, function (i, field) {
        formData[field.name] = field.value;
      });

      // Ensure we have required fields
      formData.action = "wc_ajax_add_to_cart";

      // Get product ID from various possible sources
      if (!formData.product_id) {
        formData.product_id =
          formData["add-to-cart"] ||
          $form.data("product_id") ||
          $form.find('input[name="product_id"]').val();
      }

      // Set default quantity if not provided
      if (!formData.quantity) {
        formData.quantity = 1;
      }

      // For variable products, ensure all variation attributes are included
      if (
        $form.hasClass("variations_form") ||
        $form.hasClass("primefit-variations-form")
      ) {
        // Get selected color and size from our custom interface
        const $selectedColor = $(".color-option.active");
        const $selectedSize = $(".size-option.selected");

        if ($selectedColor.length && $selectedSize.length) {
          const colorValue = $selectedColor.data("color");
          const sizeValue = $selectedSize.data("size");

          // Add variation attributes to form data
          if (colorValue) {
            // Try different possible attribute names for color
            formData["attribute_pa_color"] = colorValue;
            formData["attribute_color"] = colorValue;
          }

          if (sizeValue) {
            // Try different possible attribute names for size
            formData["attribute_pa_size"] = sizeValue;
            formData["attribute_size"] = sizeValue;
          }

          // Find and set the correct variation ID
          const variationId = this.findVariationId(colorValue, sizeValue);
          if (variationId) {
            formData.variation_id = variationId;
          }
        }

        // Get all variation attributes from the form (fallback)
        $form
          .find('select[name^="attribute_"], input[name^="attribute_"]')
          .each(function () {
            const $field = $(this);
            const name = $field.attr("name");
            const value = $field.val();

            if (name && value) {
              formData[name] = value;
            }
          });

        // Also check for variation data attributes on select elements
        $form.find(".variations select").each(function () {
          const $select = $(this);
          const attrName = $select.attr("name");
          const attrValue = $select.val();

          if (attrName && attrValue) {
            formData[attrName] = attrValue;
          }
        });

        // Ensure variation_id is properly set (fallback)
        const variationId =
          $form.find('input[name="variation_id"]').val() ||
          $form.find(".variation_id").val();
        if (variationId && variationId !== "0") {
          formData.variation_id = variationId;
        }
      }

      // Add security nonce
      if (
        window.primefit_cart_params &&
        window.primefit_cart_params.add_to_cart_nonce
      ) {
        formData.security = window.primefit_cart_params.add_to_cart_nonce;
      } else if (
        window.wc_add_to_cart_params &&
        window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce
      ) {
        formData.security =
          window.wc_add_to_cart_params.wc_ajax_add_to_cart_nonce;
      }

      return formData;
    }

    showLoadingState($button) {
      if ($button.length) {
        $button
          .prop("disabled", true)
          .addClass("loading")
          .data("original-text", $button.text())
          .text("Adding to Cart...");
      }
    }

    hideLoadingState($button, success = true) {
      if ($button.length) {
        const originalText = $button.data("original-text") || "Add to Cart";

        $button.removeClass("loading").prop("disabled", false);

        if (success) {
          $button.addClass("added").text("Added!");

          // Reset button text after 2 seconds
          setTimeout(() => {
            $button.removeClass("added").text(originalText);
          }, 2000);
        } else {
          $button.addClass("error").text("Error");

          // Reset button text after 3 seconds
          setTimeout(() => {
            $button.removeClass("error").text(originalText);
          }, 3000);
        }
      }
    }

    submitToCart(formData, $button, retryCount = 0) {
      const ajaxUrl =
        (window.primefit_cart_params && window.primefit_cart_params.ajax_url) ||
        (window.wc_add_to_cart_params &&
          window.wc_add_to_cart_params.ajax_url) ||
        "/wp-admin/admin-ajax.php";

      // Set a shorter timeout for better user experience
      const timeoutId = setTimeout(() => {
        this.hideLoadingState($button, false);
        this.abortPendingRequests(); // Abort any pending requests
      }, 10000); // 10 second timeout

      // Use connection pooling and optimized AJAX settings
      const ajaxOptions = {
        type: "POST",
        url: ajaxUrl,
        data: formData,
        dataType: "json",
        timeout: 8000, // Explicit timeout
        cache: false, // Prevent caching issues
        // Connection pooling and optimization
        beforeSend: (xhr) => {
          // Set connection header for better performance
          xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
          // Add to connection pool
          this.xhrPool = this.xhrPool || [];
          this.xhrPool.push(xhr);
        },
        success: (response) => {
          clearTimeout(timeoutId);
          this.removeFromPool(xhr);
          this.handleSuccess(response, $button);
        },
        error: (xhr, status, error) => {
          clearTimeout(timeoutId);
          this.removeFromPool(xhr);

          // Smart retry logic with exponential backoff and jitter
          if (this.shouldRetryRequest(xhr, status, error, retryCount)) {
            const delay = this.calculateRetryDelay(retryCount);
            console.log(`Retrying request in ${delay}ms (attempt ${retryCount + 1}/3)`);

            setTimeout(() => {
              this.submitToCart(formData, $button, retryCount + 1);
            }, delay);
            return;
          }

          this.handleError(xhr, status, error, $button, retryCount);
        },
      };

      const xhr = $.ajax(ajaxOptions);
    }

    // Smart retry logic
    shouldRetryRequest(xhr, status, error, retryCount) {
      // Don't retry if we've already tried too many times
      if (retryCount >= 2) return false;

      // Don't retry on client errors (4xx)
      if (xhr.status >= 400 && xhr.status < 500) return false;

      // Retry on network errors, timeouts, or server errors
      return (
        status === "timeout" ||
        status === "error" ||
        xhr.status === 0 ||
        (xhr.status >= 500 && xhr.status < 600)
      );
    }

    // Calculate retry delay with exponential backoff and jitter
    calculateRetryDelay(retryCount) {
      const baseDelay = 1000; // 1 second base delay
      const maxDelay = 5000; // Maximum 5 second delay
      const exponentialDelay = baseDelay * Math.pow(2, retryCount);
      const cappedDelay = Math.min(exponentialDelay, maxDelay);

      // Add jitter to prevent thundering herd
      const jitter = Math.random() * 1000;
      return cappedDelay + jitter;
    }

    // Remove XHR from connection pool
    removeFromPool(xhr) {
      if (this.xhrPool) {
        const index = this.xhrPool.indexOf(xhr);
        if (index > -1) {
          this.xhrPool.splice(index, 1);
        }
      }
    }

    // Get connection pool status
    getConnectionPoolStatus() {
      return {
        activeConnections: this.xhrPool ? this.xhrPool.length : 0,
        pendingRequests: this.requestQueue ? this.requestQueue.length : 0,
        isProcessing: this.isProcessingQueue || false
      };
    }

    handleSuccess(response, $button) {
      // Check for actual errors vs success-with-redirect
      if (response.error) {
        // If there are fragments, it means the product was actually added successfully
        // WooCommerce sometimes returns error: true for redirects/notices, not actual errors
        if (response.fragments && Object.keys(response.fragments).length > 0) {
          // Treat as success since we have fragments
        } else if (response.product_url && !response.data && !response.notice) {
          // This might be a redirect response, which could be success
          // Let's try to determine if it's actually an error or just a redirect

          // Force refresh cart fragments to check if item was added
          this.checkCartAfterAdd($button);
          return;
        } else {
          // This appears to be an actual error - check if it's a variation validation error
          let errorMessage = "Failed to add product to cart.";
          let isVariationError = false;

          if (response.data) {
            errorMessage = response.data;
            // Check if this is a variation validation error
            if (
              typeof response.data === "string" &&
              (response.data.includes("Please choose product options") ||
                response.data.includes("variation") ||
                response.data.includes("attribute"))
            ) {
              isVariationError = true;
            }
          } else if (response.notice) {
            errorMessage = response.notice;
            if (
              typeof response.notice === "string" &&
              (response.notice.includes("Please choose product options") ||
                response.notice.includes("variation") ||
                response.notice.includes("attribute"))
            ) {
              isVariationError = true;
            }
          }

          // If it's a variation error, try to fix the form and retry
          if (isVariationError) {
            this.fixVariationFormAndRetry($button);
            return;
          }

          this.showError(errorMessage);
          this.hideLoadingState($button, false);
          return;
        }
      }

      // Success! Update cart fragments and show feedback
      if (response.fragments) {
        // Update cart count and other fragments
        $.each(response.fragments, function (key, value) {
          $(key).replaceWith(value);
        });
      }

      // Trigger WooCommerce events first
      $(document.body).trigger("update_checkout");
      $(document.body).trigger("wc_fragment_refresh");
      $(document.body).trigger("added_to_cart", [
        response.fragments,
        response.cart_hash,
        $button,
      ]);

      // Reset quantity inputs to 1 after WooCommerce events have completed
      // Use a delay to ensure WooCommerce has finished updating fragments
      setTimeout(() => {
        this.resetQuantityInputs();
      }, 100);

      // Show success state
      this.hideLoadingState($button, true);

      // Show success message
      this.showSuccess("");

      // Clean up URL to prevent issues with browser back/forward
      this.cleanUpURL();

      // Reset submission flag
      this.isSubmitting = false;
    }

    handleError(xhr, status, error, $button, retryCount = 0) {
      let errorMessage = "Unable to add product to cart. Please try again.";

      // More specific error messages based on status
      if (status === "timeout") {
        errorMessage =
          "Request timed out. Please check your connection and try again.";
      } else if (status === "error" && xhr.status === 0) {
        errorMessage = "Network error. Please check your internet connection.";
      } else if (xhr.status === 403) {
        errorMessage =
          "Security check failed. Please refresh the page and try again.";
      } else if (xhr.status === 500) {
        errorMessage = "Server error. Please try again in a moment.";
      } else if (xhr.status === 404) {
        errorMessage = "Service unavailable. Please try again later.";
      } else if (retryCount > 0) {
        errorMessage = "Failed after multiple attempts. Please try again.";
      }

      this.showError(errorMessage);
      this.hideLoadingState($button, false);

      // Reset submission flag
      this.isSubmitting = false;
    }

    checkCartAfterAdd($button) {
      // Force refresh cart fragments to check if product was actually added
      const ajaxUrl =
        (window.primefit_cart_params && window.primefit_cart_params.ajax_url) ||
        (window.wc_add_to_cart_params &&
          window.wc_add_to_cart_params.ajax_url) ||
        "/wp-admin/admin-ajax.php";

      $.ajax({
        type: "POST",
        url: ajaxUrl,
        data: {
          action: "woocommerce_get_refreshed_fragments",
        },
        success: function (response) {
          if (response && response.fragments) {
            // Update fragments - this will show the new cart count if product was added
            $.each(response.fragments, function (key, value) {
              $(key).replaceWith(value);
            });

            // Trigger WooCommerce events first
            $(document.body).trigger("update_checkout");
            $(document.body).trigger("wc_fragment_refresh");
            $(document.body).trigger("added_to_cart", [
              response.fragments,
              response.cart_hash,
              $button,
            ]);

            // Reset quantity inputs to 1 after WooCommerce events have completed
            // Use a delay to ensure WooCommerce has finished updating fragments
            setTimeout(() => {
              this.resetQuantityInputs();
            }, 100);

            // Show success state
            this.hideLoadingState($button, true);
            this.showSuccess("");
            this.cleanUpURL();
          } else {
            // If no fragments, treat as error
            this.showError(
              "Unable to verify if product was added. Please check your cart."
            );
            this.hideLoadingState($button, false);
          }
        }.bind(this),
        error: function () {
          // Fallback: show ambiguous message
          this.showError(
            "Product may have been added. Please check your cart."
          );
          this.hideLoadingState($button, false);
        }.bind(this),
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
        if (
          window.stickyAddToCartInstance &&
          window.stickyAddToCartInstance.updateStickyQuantityButtons
        ) {
          window.stickyAddToCartInstance.updateStickyQuantityButtons();
        }
      }

      // Update main quantity button states
      if (
        window.quantityControlsInstance &&
        window.quantityControlsInstance.updateButtonStates
      ) {
        window.quantityControlsInstance.updateButtonStates();
      }
    }

    showSuccess(message) {
      // Show success notification (can be customized)
      if (message && message.trim()) {
      }
    }

    showError(message) {
      // Show error notification
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
          url.searchParams.delete("add-to-cart");
          url.searchParams.delete("added-to-cart");
          url.searchParams.delete("quantity");

          const newSearch = url.searchParams.toString();
          const newUrl =
            url.pathname + (newSearch ? "?" + newSearch : "") + url.hash;

          window.history.replaceState({}, "", newUrl);
        } catch (e) {
          // Ignore if URL API not available
        }
      }
    }

    /**
     * Fix variation form validation and retry add to cart
     */
    fixVariationFormAndRetry($button) {
      // Get the current form
      const $form = $(".primefit-variations-form");
      if (!$form.length) {
        this.showError(
          "Please select all product options before adding to cart."
        );
        this.hideLoadingState($button, false);
        return;
      }

      // Get selected color and size
      const $selectedColor = $(".color-option.active");
      const $selectedSize = $(".size-option.selected");

      if (!$selectedColor.length || !$selectedSize.length) {
        this.showError("Please select both color and size options.");
        this.hideLoadingState($button, false);
        return;
      }

      const colorValue = $selectedColor.data("color");
      const sizeValue = $selectedSize.data("size");

      // Ensure variation form inputs are properly set
      this.updateVariationFormInputs(colorValue, sizeValue);

      // Find the correct variation ID
      const variationId = this.findVariationId(colorValue, sizeValue);
      if (!variationId) {
        this.showError(
          "Selected combination is not available. Please choose different options."
        );
        this.hideLoadingState($button, false);
        return;
      }

      // Update the variation_id field
      $form.find('input[name="variation_id"]').val(variationId);
      $form.find(".variation_id").val(variationId);

      // Trigger WooCommerce variation change event to ensure validation
      $form.trigger("woocommerce_variation_select_change");

      // Wait a moment for WooCommerce to process the change
      setTimeout(() => {
        // Retry the add to cart with the corrected form
        this.retryAddToCart($form, $button);
      }, 100);
    }

    /**
     * Retry add to cart with corrected form data
     */
    retryAddToCart($form, $button) {
      // Get updated form data
      const formData = this.getFormData($form);

      // Submit via AJAX again
      this.submitToCart(formData, $button);
    }

    /**
     * Update variation form inputs with selected attributes
     * This ensures WooCommerce's variation validation works properly
     */
    updateVariationFormInputs(colorValue, sizeValue) {
      // Update color attribute input
      if (colorValue) {
        const colorInputs = [
          ".attribute_color_input",
          'input[name="attribute_pa_color"]',
          'input[name="attribute_color"]',
          'select[name="attribute_pa_color"]',
          'select[name="attribute_color"]',
        ];

        colorInputs.forEach((selector) => {
          const $input = $(selector);
          if ($input.length) {
            if ($input.is("select")) {
              $input.val(colorValue).trigger("change");
            } else {
              $input.val(colorValue);
            }
          }
        });
      }

      // Update size attribute input
      if (sizeValue) {
        const sizeInputs = [
          ".attribute_size_input",
          'input[name="attribute_pa_size"]',
          'input[name="attribute_size"]',
          'select[name="attribute_pa_size"]',
          'select[name="attribute_size"]',
        ];

        sizeInputs.forEach((selector) => {
          const $input = $(selector);
          if ($input.length) {
            if ($input.is("select")) {
              $input.val(sizeValue).trigger("change");
            } else {
              $input.val(sizeValue);
            }
          }
        });
      }

      // Trigger WooCommerce variation change event
      $(".variations_form, .primefit-variations-form").trigger(
        "woocommerce_variation_select_change"
      );
    }

    /**
     * Find variation ID based on selected attributes
     */
    findVariationId(color, size) {
      // Cache variations data to avoid repeated lookups
      if (!this.variationsCache) {
        this.variationsCache = window.primefitProductData?.variations || [];
      }

      const variations = this.variationsCache;

      // Optimized search with early returns
      for (let i = 0; i < variations.length; i++) {
        const variation = variations[i];
        if (!variation) continue;

        const attributes = variation.attributes;
        if (!attributes) continue;

        let hasColor = false;
        let hasSize = false;

        // Check for color match first (most common filter)
        for (const attrName in attributes) {
          if (!attributes[attrName]) continue;

          const attrValue = attributes[attrName];

          if (
            (attrName.toLowerCase().includes("color") ||
              attrName.includes("pa_color") ||
              attrName.includes("attribute_pa_color")) &&
            attrValue === color
          ) {
            hasColor = true;
          } else if (
            (attrName.toLowerCase().includes("size") ||
              attrName.includes("pa_size") ||
              attrName.includes("attribute_pa_size")) &&
            attrValue === size
          ) {
            hasSize = true;
          }

          // Early return if both found
          if (hasColor && hasSize) {
            return variation.variation_id;
          }
        }
      }
      return null;
    }
  }

  /**
   * Image Preloading and Optimization System
   */
  class ImagePreloader {
    constructor() {
      this.imageCache = new Map();
      this.preloadQueue = [];
      this.maxConcurrent = 2; // Reduced from 3 since we only preload critical images
      this.activePreloads = 0;
      this.preloadedImages = new Set();
      this.thumbnailData = [];
      this.variationImages = [];
      this.init();
    }

    init() {
      // Wait for DOM to be ready before preloading
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
          this.preloadCriticalImages();
          this.optimizeMainImageLoading();
          this.setupLazyLoading();
          this.setupSmartThumbnailPreloading();
          this.setupIntersectionObserver();
        });
      } else {
        this.preloadCriticalImages();
        this.optimizeMainImageLoading();
        this.setupLazyLoading();
        this.setupSmartThumbnailPreloading();
        this.setupIntersectionObserver();
      }
    }

    // Preload only the most critical images (main product image only)
    preloadCriticalImages() {
      const criticalImages = this.getCriticalImages();

      // Only preload the main product image immediately (highest priority)
      if (criticalImages.length > 0) {
        const mainImage = criticalImages[0];
        this.preloadImage(mainImage, 'high');

        // Set up smart preloading for thumbnails when they come into view
        this.setupSmartThumbnailPreloading();
      }
    }

    getCriticalImages() {
      const images = [];
      const $gallery = $(".product-gallery-container");

      if ($gallery.length) {
        // Only get main product image for critical preloading
        const $mainImage = $gallery.find(".main-product-image");
        const mainSrc = $mainImage.attr("src");
        if (mainSrc) {
          images.push(mainSrc);
        }

        // Store thumbnail data for smart preloading (don't preload yet)
        this.thumbnailData = [];
        $gallery.find(".thumbnail-item").each(function(index) {
          const $img = $(this).find(".thumbnail-image");
          const src = $img.attr("src");
          if (src) {
            this.thumbnailData.push({
              src: src,
              element: this,
              index: index
            });
          }
        }.bind(this));

        // Store variation images for smart preloading
        this.variationImages = [];
        $gallery.find(".color-option").each(function() {
          const variationImage = $(this).data("variation-image");
          if (variationImage && !this.variationImages.includes(variationImage)) {
            this.variationImages.push(variationImage);
          }
        }.bind(this));
      }

      return images; // Only return main image for critical preloading
    }

    preloadImage(imageUrl, priority = 'normal') {
      if (this.imageCache.has(imageUrl)) {
        return Promise.resolve(this.imageCache.get(imageUrl));
      }

      if (this.preloadedImages.has(imageUrl)) {
        return Promise.resolve(null);
      }

      // Check network conditions and adjust concurrent loading
      this.adjustConcurrentLoading();

      if (this.activePreloads >= this.maxConcurrent) {
        this.preloadQueue.push({ url: imageUrl, priority });
        this.sortPreloadQueue();
        return Promise.resolve(null);
      }

      this.activePreloads++;
      this.preloadedImages.add(imageUrl);

      return new Promise((resolve) => {
        const img = new Image();

        // Set priority based on image type
        if (priority === 'high') {
          img.fetchPriority = 'high';
        }

        img.onload = () => {
          this.imageCache.set(imageUrl, img);
          this.activePreloads--;
          this.processQueue();
          resolve(img);
        };

        img.onerror = () => {
          this.activePreloads--;
          this.processQueue();
          // Remove from preloaded set on error
          this.preloadedImages.delete(imageUrl);
          resolve(null);
        };

        // Add loading optimization
        img.loading = 'eager';
        img.decoding = 'async';
        img.src = imageUrl;
      });
    }

    // Adjust concurrent loading based on network conditions
    adjustConcurrentLoading() {
      if ('connection' in navigator) {
        const connection = navigator.connection;
        if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
          this.maxConcurrent = 1;
        } else if (connection.effectiveType === '3g') {
          this.maxConcurrent = 2;
        } else {
          this.maxConcurrent = 3;
        }
      }
    }

    // Sort preload queue by priority
    sortPreloadQueue() {
      this.preloadQueue.sort((a, b) => {
        const priorityOrder = { high: 0, normal: 1, low: 2 };
        return priorityOrder[a.priority] - priorityOrder[b.priority];
      });
    }

    processQueue() {
      if (this.preloadQueue.length > 0 && this.activePreloads < this.maxConcurrent) {
        const nextItem = this.preloadQueue.shift();
        this.preloadImage(nextItem.url, nextItem.priority);
      }
    }

    setupLazyLoading() {
      // Fallback for browsers without IntersectionObserver
      const lazyImages = $("[data-src]");

      if (lazyImages.length === 0) return;

      const loadLazyImage = (img) => {
        const $img = $(img);
        const src = $img.data("src");
        if (src) {
          $img.attr("src", src).removeAttr("data-src");
        }
      };

      // Use requestIdleCallback for better performance if available
      if ('requestIdleCallback' in window) {
        requestIdleCallback(() => {
          lazyImages.each(function() {
            loadLazyImage(this);
          });
        });
      } else {
        // Fallback to setTimeout
        setTimeout(() => {
          lazyImages.each(function() {
            loadLazyImage(this);
          });
        }, 1000);
      }
    }

    setupIntersectionObserver() {
      // Enhanced intersection observer with root margin and threshold
      if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const $img = $(entry.target);
              const src = $img.data("src");
              if (src) {
                // Preload the image before setting it
                this.preloadImage(src, 'normal').then(() => {
                  $img.attr("src", src).removeAttr("data-src");
                });
                imageObserver.unobserve(entry.target);
              }
            }
          });
        }, {
          rootMargin: '50px 0px', // Start loading 50px before image comes into view
          threshold: 0.1
        });

        // Observe images with data-src attribute
        $("[data-src]").each(function() {
          imageObserver.observe(this);
        });
      }
    }

    // Optimize main product image loading priority
    optimizeMainImageLoading() {
      const $mainImage = $(".main-product-image");
      if ($mainImage.length) {
        // Ensure main image has high priority
        $mainImage.attr('fetchpriority', 'high');
        $mainImage.attr('loading', 'eager');

        // Add decoding hint for better performance
        $mainImage.attr('decoding', 'async');

        // Optimize size guide modal image loading
        const $sizeGuideImage = $("#size-guide-modal-image");
        if ($sizeGuideImage.length) {
          $sizeGuideImage.attr('loading', 'lazy');
          $sizeGuideImage.attr('decoding', 'async');
        }
      }
    }

    // Smart thumbnail preloading - only preload when thumbnails are about to come into view
    setupSmartThumbnailPreloading() {
      if (!this.thumbnailData || this.thumbnailData.length === 0) return;

      // Enhanced intersection observer specifically for thumbnails
      if ('IntersectionObserver' in window) {
        const thumbnailObserver = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const thumbnailData = entry.target.thumbnailData;
              if (thumbnailData && thumbnailData.src) {
                // Preload thumbnail image with low priority
                this.preloadImage(thumbnailData.src, 'low').then(() => {
                  // Mark as preloaded to avoid duplicate requests
                  this.preloadedImages.add(thumbnailData.src);
                });

                // Preload next thumbnail proactively (look ahead) with lower priority
                const nextIndex = thumbnailData.index + 1;
                if (this.thumbnailData[nextIndex]) {
                  const nextThumbnail = this.thumbnailData[nextIndex];
                  if (!this.preloadedImages.has(nextThumbnail.src)) {
                    // Use requestIdleCallback if available for non-blocking preloading
                    const preloadNext = () => {
                      this.preloadImage(nextThumbnail.src, 'low');
                      this.preloadedImages.add(nextThumbnail.src);
                    };

                    if ('requestIdleCallback' in window) {
                      requestIdleCallback(preloadNext);
                    } else {
                      setTimeout(preloadNext, 1000); // Longer delay for next thumbnails
                    }
                  }
                }

                // Stop observing this thumbnail
                thumbnailObserver.unobserve(entry.target);
              }
            }
          });
        }, {
          rootMargin: '100px 0px', // Start preloading 100px before thumbnail comes into view
          threshold: 0.1
        });

        // Observe all thumbnail elements
        this.thumbnailData.forEach((thumbnail, index) => {
          if (thumbnail.element) {
            thumbnail.element.thumbnailData = thumbnail;
            thumbnailObserver.observe(thumbnail.element);
          }
        });
      } else {
        // Fallback for browsers without IntersectionObserver
        // Preload first few thumbnails after main image loads
        setTimeout(() => {
          this.thumbnailData.slice(0, 2).forEach(thumbnail => {
            if (thumbnail.src && !this.preloadedImages.has(thumbnail.src)) {
              this.preloadImage(thumbnail.src, 'low');
            }
          });
        }, 2000); // Wait 2 seconds after page load
      }
    }

    // Get preload statistics
    getPreloadStats() {
      return {
        cachedImages: this.imageCache.size,
        preloadedImages: this.preloadedImages.size,
        activePreloads: this.activePreloads,
        queueLength: this.preloadQueue.length,
        maxConcurrent: this.maxConcurrent,
        thumbnailsStored: this.thumbnailData ? this.thumbnailData.length : 0,
        variationImagesStored: this.variationImages ? this.variationImages.length : 0
      };
    }
  }

  /**
   * Initialize all functionality when document is ready
   */
  $(document).ready(function () {
    // Only initialize on single product pages
    if ($("body").hasClass("single-product")) {
      // Initialize image preloader for better performance
      new ImagePreloader();

      // Initialize main product gallery (only once)
      // Note: Gallery is now handled by native JavaScript in product-image.php
      // if (!window.productGallery) {
      //   window.productGallery = new ProductGallery();
      // }
      new ProductInformation();
      new ProductVariations();
      new NotifyAvailability();

      // Store instances globally for quantity reset functionality
      window.quantityControlsInstance = new WooCommerceQuantityControls();
      window.stickyAddToCartInstance = new StickyAddToCart();
      window.ajaxAddToCartInstance = new AjaxAddToCart(); // Initialize AJAX cart functionality
    }
  });
  /**
   * Size Guide Modal Functionality
   */
  class SizeGuideModal {
    constructor() {
      this.modal = document.getElementById("size-guide-modal");
      this.modalImage = document.getElementById("size-guide-modal-image");
      this.closeButton = document.querySelector(".size-guide-modal-close");
      this.overlay = document.querySelector(".size-guide-modal-overlay");
      this.isOpen = false;

      this.init();
    }

    init() {
      this.bindEvents();
    }

    bindEvents() {
      // Handle size guide link clicks
      document.addEventListener("click", (e) => {
        const sizeGuideLink = e.target.closest(".size-guide-link");
        if (sizeGuideLink) {
          e.preventDefault();
          const imageUrl = sizeGuideLink.getAttribute("data-size-guide-image");
          if (imageUrl) {
            this.openModal(imageUrl);
          }
        }
      });

      // Handle close button clicks
      if (this.closeButton) {
        this.closeButton.addEventListener("click", () => {
          this.closeModal();
        });
      }

      // Handle overlay clicks
      if (this.overlay) {
        this.overlay.addEventListener("click", () => {
          this.closeModal();
        });
      }

      // Handle escape key
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && this.isOpen) {
          this.closeModal();
        }
      });
    }

    openModal(imageUrl) {
      if (!this.modal || !this.modalImage) return;

      // Set the image source
      this.modalImage.src = imageUrl;
      this.modalImage.alt = "Size Guide";

      // Show the modal
      this.modal.style.display = "flex";

      // Trigger reflow to ensure display change is applied
      this.modal.offsetHeight;

      // Add active class for animation
      this.modal.classList.add("active");

      // Focus the close button for accessibility
      if (this.closeButton) {
        this.closeButton.focus();
      }

      // Prevent body scroll
      document.body.style.overflow = "hidden";

      this.isOpen = true;
    }

    closeModal() {
      if (!this.modal) return;

      // Remove active class for animation
      this.modal.classList.remove("active");

      // Wait for animation to complete before hiding
      setTimeout(() => {
        this.modal.style.display = "none";

        // Restore body scroll
        document.body.style.overflow = "";

        this.isOpen = false;
      }, 300);
    }
  }

  /**
   * Virtual Scrolling for Size Options
   */
  class VirtualSizeOptions {
    constructor(container, options = {}) {
      this.container = container;
      this.options = options;
      this.items = [];
      this.itemHeight = options.itemHeight || 50;
      this.visibleCount = options.visibleCount || 10;
      this.totalHeight = 0;
      this.scrollTop = 0;
      this.startIndex = 0;
      this.endIndex = 0;
      this.renderedItems = new Map();
      this.debounceTimer = null;

      this.init();
    }

    init() {
      if (!this.container) return;

      this.setupContainer();
      this.bindEvents();
      this.updateItems();
    }

    setupContainer() {
      this.container.style.position = 'relative';
      this.container.style.overflowY = 'auto';
      this.container.style.height = `${this.visibleCount * this.itemHeight}px`;
    }

    bindEvents() {
      this.container.addEventListener('scroll', this.handleScroll.bind(this), { passive: true });

      // Handle window resize
      window.addEventListener('resize', this.debounce(this.updateDimensions.bind(this), 100));

      // Clean up on page unload
      window.addEventListener('beforeunload', this.cleanup.bind(this));
    }

    handleScroll(e) {
      const newScrollTop = e.target.scrollTop;
      if (Math.abs(newScrollTop - this.scrollTop) > this.itemHeight) {
        this.scrollTop = newScrollTop;
        this.updateVisibleItems();
      }
    }

    updateDimensions() {
      const containerRect = this.container.getBoundingClientRect();
      this.visibleCount = Math.ceil(containerRect.height / this.itemHeight) + 2; // Add buffer
      this.container.style.height = `${this.visibleCount * this.itemHeight}px`;
      this.updateVisibleItems();
    }

    updateItems() {
      this.items = Array.from(this.container.querySelectorAll('.size-option'));
      this.totalHeight = this.items.length * this.itemHeight;
      this.updateVisibleItems();
    }

    updateVisibleItems() {
      if (this.items.length === 0) return;

      const startIndex = Math.max(0, Math.floor(this.scrollTop / this.itemHeight) - 2);
      const endIndex = Math.min(
        this.items.length - 1,
        startIndex + this.visibleCount + 4
      );

      if (startIndex === this.startIndex && endIndex === this.endIndex) return;

      this.startIndex = startIndex;
      this.endIndex = endIndex;

      this.renderItems();
    }

    renderItems() {
      // Clear rendered items outside the visible range
      this.renderedItems.forEach((item, index) => {
        if (index < this.startIndex || index > this.endIndex) {
          if (item.parentNode === this.container) {
            this.container.removeChild(item);
          }
          this.renderedItems.delete(index);
        }
      });

      // Render items in the visible range
      for (let i = this.startIndex; i <= this.endIndex; i++) {
        if (!this.renderedItems.has(i)) {
          const item = this.items[i];
          if (item) {
            const clone = item.cloneNode(true);
            clone.style.position = 'absolute';
            clone.style.top = `${i * this.itemHeight}px`;
            clone.style.width = '100%';
            clone.style.height = `${this.itemHeight}px`;

            this.container.appendChild(clone);
            this.renderedItems.set(i, clone);
          }
        }
      }
    }

    updateItem(index, data) {
      const item = this.renderedItems.get(index);
      if (item) {
        // Update item properties based on data
        if (data.selected !== undefined) {
          item.classList.toggle('selected', data.selected);
        }
        if (data.disabled !== undefined) {
          item.disabled = data.disabled;
          item.setAttribute('aria-disabled', data.disabled ? 'true' : 'false');
        }
      }
    }

    setItemHeight(height) {
      this.itemHeight = height;
      this.updateDimensions();
    }

    debounce(func, wait) {
      return (...args) => {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => func.apply(this, args), wait);
      };
    }

    cleanup() {
      if (this.debounceTimer) {
        clearTimeout(this.debounceTimer);
      }
      this.renderedItems.clear();
    }
  }

  /**
   * Debounced Input Handler
   */
  class DebouncedInput {
    constructor(element, callback, delay = 300) {
      this.element = element;
      this.callback = callback;
      this.delay = delay;
      this.timer = null;
      this.isProcessing = false;

      this.init();
    }

    init() {
      if (!this.element) return;

      this.element.addEventListener('input', this.handleInput.bind(this), { passive: true });
      this.element.addEventListener('blur', this.flush.bind(this));
    }

    handleInput(e) {
      if (this.isProcessing) return;

      clearTimeout(this.timer);
      this.timer = setTimeout(() => {
        this.isProcessing = true;
        this.callback(e);
        this.isProcessing = false;
      }, this.delay);
    }

    flush() {
      if (this.timer) {
        clearTimeout(this.timer);
        this.isProcessing = true;
        this.callback({ target: this.element });
        this.isProcessing = false;
      }
    }

    destroy() {
      if (this.timer) {
        clearTimeout(this.timer);
      }
      if (this.element) {
        this.element.removeEventListener('input', this.handleInput);
        this.element.removeEventListener('blur', this.flush);
      }
    }
  }

  /**
   * Memory Leak Prevention Manager
   */
  class MemoryLeakManager {
    constructor() {
      this.listeners = new WeakMap();
      this.timers = new Set();
      this.observers = new Set();
      this.cleanupFunctions = new Set();
    }

    addEventListener(element, event, handler, options = {}) {
      if (!element || !handler) return;

      const listener = element.addEventListener(event, handler, options);

      // Store weak reference to prevent memory leaks
      if (!this.listeners.has(element)) {
        this.listeners.set(element, new Map());
      }

      const elementListeners = this.listeners.get(element);
      elementListeners.set(handler, { event, options });

      return listener;
    }

    removeEventListener(element, event, handler) {
      if (!element || !handler) return;

      element.removeEventListener(event, handler);

      if (this.listeners.has(element)) {
        const elementListeners = this.listeners.get(element);
        elementListeners.delete(handler);
      }
    }

    setTimeout(callback, delay) {
      const timer = window.setTimeout(callback, delay);
      this.timers.add(timer);
      return timer;
    }

    clearTimeout(timer) {
      if (this.timers.has(timer)) {
        window.clearTimeout(timer);
        this.timers.delete(timer);
      }
    }

    setInterval(callback, delay) {
      const timer = window.setInterval(callback, delay);
      this.timers.add(timer);
      return timer;
    }

    clearInterval(timer) {
      if (this.timers.has(timer)) {
        window.clearInterval(timer);
        this.timers.delete(timer);
      }
    }

    observe(element, callback) {
      if (!element) return;

      const observer = new MutationObserver(callback);
      observer.observe(element, { childList: true, subtree: true });
      this.observers.add(observer);

      return observer;
    }

    unobserve(observer) {
      if (this.observers.has(observer)) {
          observer.disconnect();
          this.observers.delete(observer);
      }
    }

    addCleanupFunction(func) {
      this.cleanupFunctions.add(func);
    }

    cleanup() {
      // Clear all timers
      this.timers.forEach(timer => {
        window.clearTimeout(timer);
        window.clearInterval(timer);
      });
      this.timers.clear();

      // Disconnect all observers
      this.observers.forEach(observer => {
        observer.disconnect();
      });
      this.observers.clear();

      // Run cleanup functions
      this.cleanupFunctions.forEach(func => {
        try {
          func();
        } catch (e) {
          console.warn('Cleanup function failed:', e);
        }
      });
      this.cleanupFunctions.clear();

      // Clear listener references (but keep actual listeners)
      this.listeners = new WeakMap();
    }
  }

  // Initialize global memory leak manager
  window.memoryLeakManager = new MemoryLeakManager();

  /**
   * Initialize all functionality when DOM is ready
   */
  $(document).ready(function () {
    // Initialize product gallery (only if not already initialized above)
    // Note: Gallery is now handled by native JavaScript in product-image.php
    // if (!window.productGallery) {
    //   window.productGallery = new ProductGallery();
    // }

    // Initialize product variations
    new ProductVariations();

    // Initialize size guide modal
    new SizeGuideModal();

    // Initialize memory leak manager cleanup on page unload
    window.addEventListener('beforeunload', () => {
      if (window.memoryLeakManager) {
        window.memoryLeakManager.cleanup();
      }
    });
  });
})(jQuery);
