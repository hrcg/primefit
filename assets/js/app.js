(function ($) {
  'use strict';

  /**
   * Header scroll behavior management
   * Provides different behavior for desktop and mobile devices
   */
  class HeaderController {
    constructor() {
      this.$header = $('.site-header');
      this.$window = $(window);
      this.scrollThreshold = 10; // Mobile scroll threshold for transparency
      this.isScrolling = false;
      this.init();
    }

    init() {
      this.bindEvents();
      this.handleScroll(); // Set initial state
    }

    bindEvents() {
      this.$window.on('scroll', this.throttle(this.handleScroll.bind(this), 16));
      this.$window.on('resize', this.debounce(this.handleResize.bind(this), 250));
    }

    handleScroll() {
      if (this.isScrolling) return;
      this.isScrolling = true;

      const scrollTop = this.$window.scrollTop();
      const promoBarHeight = $('.promo-bar').outerHeight() || 40;
      const isMobile = this.isMobileDevice();

      // Ensure header maintains sticky positioning
      if (this.$header.css('position') !== 'sticky') {
        this.$header.css({
          'position': 'sticky',
          'top': '0',
          'z-index': '50'
        });
      }

      if (isMobile) {
        // Mobile: transparent when at top, solid background when scrolled
        if (scrollTop > this.scrollThreshold) {
          this.$header.addClass('scrolled').addClass('mobile-scrolled');
        } else {
          this.$header.removeClass('scrolled').removeClass('mobile-scrolled');
        }
      } else {
        // Desktop: existing behavior (scroll past promo bar)
        if (scrollTop > promoBarHeight) {
          this.$header.addClass('scrolled').removeClass('mobile-scrolled');
        } else {
          this.$header.removeClass('scrolled').removeClass('mobile-scrolled');
        }
      }

      // Reset scrolling flag
      requestAnimationFrame(() => {
        this.isScrolling = false;
      });
    }

    handleResize() {
      // Recalculate on resize to handle device orientation changes
      this.handleScroll();
    }

    isMobileDevice() {
      return window.matchMedia('(max-width: 1024px)').matches;
    }

    // Utility: Throttle function for performance
    throttle(func, wait) {
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

    // Utility: Debounce function for resize events
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

  // Initialize header controller
  const headerController = new HeaderController();

  $(document).on("added_to_cart", function () {
    // Optionally open mini cart on add to cart
    openCart();
    setTimeout(function () {
      closeCart();
    }, 2000);
  });

  // Cart functionality
  function openCart() {
    const $wrap = $("[data-behavior='click']");
    const $panel = $("#mini-cart-panel");
    const $toggle = $(".cart-toggle");

    $wrap.addClass("open").attr("data-open", "true");
    $panel.removeAttr("hidden");
    $toggle.attr("aria-expanded", "true");

    // Prevent body scroll when cart is open on mobile
    if (window.matchMedia("(max-width: 1024px)").matches) {
      document.body.classList.add("cart-open");
    }
  }

  function closeCart() {
    const $wrap = $("[data-behavior='click']");
    const $panel = $("#mini-cart-panel");
    const $toggle = $(".cart-toggle");

    $wrap.removeClass("open").attr("data-open", "false");
    $panel.attr("hidden", true);
    $toggle.attr("aria-expanded", "false");

    // Re-enable body scroll
    document.body.classList.remove("cart-open");
  }

  // Click-to-open cart drawer
  $(document).on("click", "[data-behavior='click'] .cart-toggle", function (e) {
    e.preventDefault();
    const expanded = $(this).attr("aria-expanded") === "true";
    if (expanded) {
      closeCart();
    } else {
      openCart();
    }
  });

  // Close cart via close button
  $(document).on("click", ".cart-close", function (e) {
    e.preventDefault();
    closeCart();
  });

  // Close when clicking overlay
  $(document).on("click", ".cart-overlay", function (e) {
    e.preventDefault();
    closeCart();
  });

  // Close when clicking outside (but not on overlay, as that's handled above)
  $(document).on("click", function (e) {
    const $target = $(e.target);
    const $cartWrap = $("[data-behavior='click']");
    
    // Only close if cart is actually open and click is outside cart elements
    if (
      $cartWrap.attr("data-open") === "true" &&
      !$target.closest("[data-behavior='click']").length &&
      !$target.hasClass("cart-overlay") &&
      e.type === "click" // Ensure this is a click event, not scroll or other events
    ) {
      closeCart();
    }
  });

  // Mega menu: open on click for top-level items with .has-mega
  $(document).on("click", ".left-nav .menu > li.has-mega > a", function (e) {
    if (window.matchMedia("(min-width: 1025px)").matches) {
      e.preventDefault();
      const $li = $(this).closest("li");
      const isOpen = $li.hasClass("open");
      $(".left-nav .menu > li").removeClass("open");
      if (!isOpen) {
        $li.addClass("open");
      }
    }
  });
  // Close mega on outside click
  $(document).on("click", function (e) {
    const $t = $(e.target);
    if (!$t.closest(".left-nav").length) {
      $(".left-nav .menu > li").removeClass("open");
    }
  });

  /**
   * Mobile menu controller
   * Handles mobile navigation toggle with accessibility features
   */
  class MobileMenuController {
    constructor() {
      this.$toggle = $('.menu-toggle, [data-mobile-menu-toggle]');
      this.$overlay = $('.mobile-nav-overlay');
      this.$body = $(document.body);
      this.init();
    }

    init() {
      this.bindEvents();
    }

    bindEvents() {
      $(document).on('click', '.menu-toggle, [data-mobile-menu-toggle]', this.handleToggle.bind(this));
      $(document).on('click', '.menu-close', this.closeMenu.bind(this));
      $(document).on('click', '.mobile-nav-overlay, .left-nav a', this.handleOverlayClick.bind(this));
      
      // Close menu on escape key
      $(document).on('keydown', this.handleKeyDown.bind(this));
    }

    handleToggle(event) {
      event.preventDefault();
      const $button = $(event.currentTarget);
      const isExpanded = $button.attr('aria-expanded') === 'true';
      
      if (isExpanded) {
        this.closeMenu();
      } else {
        this.openMenu();
      }
    }

    openMenu() {
      this.$toggle.attr('aria-expanded', 'true');
      this.$overlay.removeAttr('hidden').attr('aria-hidden', 'false');
      this.setBodyLock(true);
      
      // Focus management for accessibility
      this.$overlay.find('.left-nav a:first').focus();
    }

    closeMenu() {
      this.$toggle.attr('aria-expanded', 'false');
      this.$overlay.attr('hidden', 'true').attr('aria-hidden', 'true');
      this.setBodyLock(false);
      
      // Return focus to toggle button
      this.$toggle.focus();
    }

    handleOverlayClick(event) {
      if (window.matchMedia('(max-width: 1024px)').matches) {
        this.closeMenu();
      }
    }

    handleKeyDown(event) {
      // Close menu on Escape key
      if (event.key === 'Escape' && this.$toggle.attr('aria-expanded') === 'true') {
        event.preventDefault();
        this.closeMenu();
      }
    }

    setBodyLock(locked) {
      this.$body.toggleClass('nav-open', locked);
    }
  }

  // Initialize mobile menu controller
  const mobileMenuController = new MobileMenuController();
})(jQuery);
