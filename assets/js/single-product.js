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

      // Update main image
      const imageUrl = $mainImage.attr("src").replace(/\d+/, index);
      $mainImage.attr("src", imageUrl);
      $mainImage.attr("data-image-index", index);

      // Update active states
      $thumbnails.removeClass("active");
      $thumbnails.eq(index).addClass("active");

      $dots.removeClass("active");
      $dots.eq(index).addClass("active");
    }

    previousImage() {
      const $gallery = $(".product-gallery-container");
      const $thumbnails = $gallery.find(".thumbnail-item");
      const currentIndex = parseInt(
        $gallery.find(".thumbnail-item.active").data("image-index")
      );
      const newIndex =
        currentIndex > 0 ? currentIndex - 1 : $thumbnails.length - 1;
      this.switchImage(newIndex);
    }

    nextImage() {
      const $gallery = $(".product-gallery-container");
      const $thumbnails = $gallery.find(".thumbnail-item");
      const currentIndex = parseInt(
        $gallery.find(".thumbnail-item.active").data("image-index")
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

      $(".product-main-image").on("touchstart", (e) => {
        startX = e.originalEvent.touches[0].clientX;
        startY = e.originalEvent.touches[0].clientY;
      });

      $(".product-main-image").on("touchend", (e) => {
        endX = e.originalEvent.changedTouches[0].clientX;
        endY = e.originalEvent.changedTouches[0].clientY;
        this.handleSwipe(startX, startY, endX, endY);
      });

      $(".product-main-image").on("touchmove", (e) => {
        e.preventDefault();
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
      this.init();
    }

    init() {
      this.bindEvents();
    }

    bindEvents() {
      // Color selection
      $(document).on("click", ".color-option", (e) => {
        e.preventDefault();
        const $option = $(e.currentTarget);
        const color = $option.data("color");
        this.selectColor(color, $option);
      });

      // Size selection
      $(document).on("click", ".size-option", (e) => {
        e.preventDefault();
        const $option = $(e.currentTarget);
        const size = $option.data("size");
        this.selectSize(size, $option);
      });
    }

    selectColor(color, $option) {
      $(".color-option").removeClass("active");
      $option.addClass("active");

      // Update product color display
      $(".color-value").text(color);

      // Trigger variation change if needed
      this.updateVariation();
    }

    selectSize(size, $option) {
      $(".size-option").removeClass("selected");
      $option.addClass("selected");

      // Trigger variation change if needed
      this.updateVariation();
    }

    updateVariation() {
      const selectedColor = $(".color-option.active").data("color");
      const selectedSize = $(".size-option.selected").data("size");

      if (selectedColor && selectedSize) {
        // Find matching variation
        const variationId = this.findVariationId(selectedColor, selectedSize);
        if (variationId) {
          this.updateAddToCartButton(variationId);
        }
      }
    }

    findVariationId(color, size) {
      // This would need to be implemented based on WooCommerce variation data
      // For now, return null to use default behavior
      return null;
    }

    updateAddToCartButton(variationId) {
      // Update add to cart button with variation ID
      $(".single_add_to_cart_button").data("variation-id", variationId);
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
   * Initialize all functionality when document is ready
   */
  $(document).ready(function () {
    // Only initialize on single product pages
    if ($("body").hasClass("single-product")) {
      new ProductGallery();
      new ProductInformation();
      new ProductVariations();
      new NotifyAvailability();
    }
  });
})(jQuery);
