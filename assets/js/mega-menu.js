/**
 * PrimeFit Theme - Mega Menu Module
 * Mega menu functionality for desktop navigation
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Mega Menu Controller
  class MegaMenuController {
    constructor() {
      this.$megaMenu = $("#mega-menu");
      this.$header = $(".site-header");
      this.isDesktop = this.isDesktopDevice();
      this.isOpen = false;
      this.hoverTimeout = null;
      this.resizeHandler = null;
      this.outsideClickHandler = null;
      this.init();
    }

    init() {
      if (this.$megaMenu.length === 0) return;

      this.bindEvents();
      this.handleResize();

      // Add cleanup on page unload to prevent memory leaks
      window.addEventListener("beforeunload", () => {
        this.cleanup();
      });
    }

    /**
     * Cleanup method to prevent memory leaks
     */
    cleanup() {
      // Clear hover timeout
      if (this.hoverTimeout) {
        clearTimeout(this.hoverTimeout);
        this.hoverTimeout = null;
      }

      // Remove resize event listener
      if (this.resizeHandler) {
        $(window).off("resize", this.resizeHandler);
        this.resizeHandler = null;
      }

      // Remove document event listeners
      if (this.outsideClickHandler) {
        $(document).off("click", this.outsideClickHandler);
        this.outsideClickHandler = null;
      }

      // Remove jQuery event listeners using proper namespace
      $(document).off("mouseenter.megaMenu mouseleave.megaMenu click.megaMenu");
    }

    bindEvents() {
      // Show mega menu only on specific menu item hover (desktop only)
      // Look for menu items with data-mega-menu="true" attribute on the link
      $(document).on(
        "mouseenter.megaMenu",
        ".menu--primary .menu-item a[data-mega-menu='true']",
        this.handleMenuItemHover.bind(this)
      );
      $(document).on(
        "mouseleave.megaMenu",
        ".menu--primary .menu-item a[data-mega-menu='true']",
        this.handleMenuItemLeave.bind(this)
      );

      // Also handle hover on the mega menu itself to keep it open
      $(document).on(
        "mouseenter.megaMenu",
        ".mega-menu",
        this.handleMegaMenuHover.bind(this)
      );
      $(document).on(
        "mouseleave.megaMenu",
        ".mega-menu",
        this.handleMegaMenuLeave.bind(this)
      );

      // Hide mega menu when clicking outside
      this.outsideClickHandler = this.handleOutsideClick.bind(this);
      $(document).on("click.megaMenu", this.outsideClickHandler);

      // Handle window resize with stored reference for cleanup
      this.resizeHandler = this.debounce(this.handleResize.bind(this), 250);
      $(window).on("resize.megaMenu", this.resizeHandler);
    }

    handleMenuItemHover(event) {
      if (!this.isDesktop) return;

      clearTimeout(this.hoverTimeout);
      this.showMegaMenu();
    }

    handleMenuItemLeave(event) {
      if (!this.isDesktop) return;

      this.hoverTimeout = setTimeout(() => {
        this.hideMegaMenu();
      }, 150);
    }

    handleMegaMenuHover() {
      if (!this.isDesktop) return;

      clearTimeout(this.hoverTimeout);
    }

    handleMegaMenuLeave() {
      if (!this.isDesktop) return;

      this.hoverTimeout = setTimeout(() => {
        this.hideMegaMenu();
      }, 150);
    }

    handleOutsideClick(event) {
      if (!this.isDesktop || !this.isOpen) return;

      const $target = $(event.target);
      if (
        !$target.closest(".site-header").length &&
        !$target.closest(".mega-menu").length
      ) {
        this.hideMegaMenu();
      }
    }

    showMegaMenu() {
      if (this.isOpen) return;

      this.isOpen = true;
      this.$megaMenu.addClass("active").attr("aria-hidden", "false");
      this.$header.addClass("mega-menu-open");
    }

    hideMegaMenu() {
      if (!this.isOpen) return;

      this.isOpen = false;
      this.$megaMenu.removeClass("active").attr("aria-hidden", "true");
      this.$header.removeClass("mega-menu-open");
    }

    isDesktopDevice() {
      return window.matchMedia("(min-width: 1025px)").matches;
    }

    handleResize() {
      const wasDesktop = this.isDesktop;
      this.isDesktop = this.isDesktopDevice();

      if (wasDesktop !== this.isDesktop) {
        if (!this.isDesktop) {
          this.hideMegaMenu();
        }
      }
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

  // Initialize mega menu controller
  if ($("#mega-menu").length > 0) {
    new MegaMenuController();
  }
})(jQuery);
