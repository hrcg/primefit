(function ($) {
  "use strict";

  // Scroll prevention utilities
  let scrollPosition = 0;

  function getScrollbarWidth() {
    // Create a temporary div to measure scrollbar width
    const outer = document.createElement("div");
    outer.style.visibility = "hidden";
    outer.style.overflow = "scroll";
    outer.style.msOverflowStyle = "scrollbar";
    document.body.appendChild(outer);

    const inner = document.createElement("div");
    outer.appendChild(inner);

    const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
    outer.parentNode.removeChild(outer);

    return scrollbarWidth;
  }

  function preventPageScroll() {
    // Only prevent scroll if not already locked
    if (document.body.classList.contains("scroll-locked")) {
      return;
    }

    // Store current scroll position
    scrollPosition = window.pageYOffset || document.documentElement.scrollTop;

    // Calculate scrollbar width to prevent content shift
    const scrollbarWidth = getScrollbarWidth();

    // Add class to body for CSS styling
    document.body.classList.add("scroll-locked");

    // Set body position to fixed to prevent scrolling
    document.body.style.position = "fixed";
    document.body.style.top = `-${scrollPosition}px`;
    document.body.style.width = "100%";

    // Prevent content shift by adding padding for scrollbar
    if (scrollbarWidth > 0) {
      document.body.style.paddingRight = `${scrollbarWidth}px`;
    }
  }

  function allowPageScroll() {
    // Only allow scroll if currently locked
    if (!document.body.classList.contains("scroll-locked")) {
      return;
    }

    // Remove scroll lock class
    document.body.classList.remove("scroll-locked");

    // Restore body styles
    document.body.style.position = "";
    document.body.style.top = "";
    document.body.style.width = "";
    document.body.style.paddingRight = "";

    // Restore scroll position
    window.scrollTo(0, scrollPosition);
  }

  // Cart functionality (preserved)
  function getCartContext(clickedEl) {
    const $root = clickedEl
      ? $(clickedEl).closest('[data-behavior="click"]')
      : $('[data-behavior="click"]').first();
    return {
      $wrap: $root,
      $panel: $root.find("#mini-cart-panel"),
      $toggle: $root.find(".cart-toggle"),
    };
  }

  function openCart(clickedEl) {
    const { $wrap, $panel, $toggle } = getCartContext(clickedEl);
    $wrap.addClass("open").attr("data-open", "true");
    $panel.removeAttr("hidden");
    $toggle.attr("aria-expanded", "true");

    if (window.matchMedia("(max-width: 1024px)").matches) {
      document.body.classList.add("cart-open");
    }

    // Prevent page scrolling when cart is open
    preventPageScroll();
  }

  function closeCart(clickedEl) {
    const { $wrap, $panel, $toggle } = getCartContext(clickedEl);
    $wrap.removeClass("open").attr("data-open", "false");
    $panel.attr("hidden", true);
    $toggle.attr("aria-expanded", "false");

    document.body.classList.remove("cart-open");

    // Re-enable page scrolling when cart is closed
    allowPageScroll();
  }

  // Click-to-open cart drawer
  $(document).on("click", "[data-behavior='click'] .cart-toggle", function (e) {
    e.preventDefault();
    const expanded = $(this).attr("aria-expanded") === "true";
    if (expanded) {
      closeCart(this);
    } else {
      openCart(this);
    }
  });

  // Close cart via close button
  $(document).on("click", ".cart-close", function (e) {
    e.preventDefault();
    closeCart(this);
  });

  // Cart quantity controls
  $(document).on(
    "click",
    ".woocommerce-mini-cart__item-quantity .plus",
    function (e) {
      e.preventDefault();
      const cartItemKey = $(this).data("cart-item-key");
      const $input = $(this).siblings("input");
      const currentQty = parseInt($input.val());
      const maxQty = parseInt($input.attr("max"));

      if (currentQty < maxQty) {
        // Add loading state
        $(this).addClass("loading").prop("disabled", true);
        updateCartQuantity(cartItemKey, currentQty + 1, $(this));
      }
    }
  );

  $(document).on(
    "click",
    ".woocommerce-mini-cart__item-quantity .minus",
    function (e) {
      e.preventDefault();
      const cartItemKey = $(this).data("cart-item-key");
      const $input = $(this).siblings("input");
      const currentQty = parseInt($input.val());

      if (currentQty > 1) {
        // Add loading state
        $(this).addClass("loading").prop("disabled", true);
        updateCartQuantity(cartItemKey, currentQty - 1, $(this));
      }
    }
  );

  $(document).on(
    "change",
    ".woocommerce-mini-cart__item-quantity input",
    function (e) {
      const cartItemKey = $(this).data("cart-item-key");
      const newQty = parseInt($(this).val());
      const maxQty = parseInt($(this).attr("max"));

      if (newQty >= 1 && newQty <= maxQty) {
        // Add loading state to input
        $(this).addClass("loading").prop("disabled", true);
        updateCartQuantity(cartItemKey, newQty, $(this));
      } else {
        $(this).val($(this).data("original-value") || 1);
      }
    }
  );

  // Remove item from cart
  $(document).on("click", ".woocommerce-mini-cart__item-remove", function (e) {
    e.preventDefault();
    const cartItemKey = $(this).data("cart-item-key");
    // Add loading state
    $(this).addClass("loading").prop("disabled", true);
    removeCartItem(cartItemKey, $(this));
  });

  // Update cart quantity via AJAX
  function updateCartQuantity(cartItemKey, quantity, $element) {
    // Validate parameters
    if (!cartItemKey || !quantity || !wc_add_to_cart_params) {
      console.error("Invalid parameters for cart update");
      if ($element) {
        $element.removeClass("loading").prop("disabled", false);
      }
      return;
    }

    $.ajax({
      type: "POST",
      url: wc_add_to_cart_params.ajax_url,
      data: {
        action: "woocommerce_update_cart_item_quantity",
        cart_item_key: cartItemKey,
        quantity: quantity,
        security: wc_add_to_cart_params.update_cart_nonce,
      },
      success: function (response) {
        if (response.success) {
          // Update cart fragments
          if (response.data && response.data.fragments) {
            $.each(response.data.fragments, function (key, value) {
              $(key).replaceWith(value);
            });
          }
          // Trigger WooCommerce cart update events
          $(document.body).trigger("update_checkout");
          $(document.body).trigger("wc_fragment_refresh");
          $(document.body).trigger("added_to_cart");
        } else {
          console.error("Failed to update cart quantity:", response.data);
          // Fallback: reload page if AJAX fails
          if (
            response.data &&
            response.data.includes("Security check failed")
          ) {
            window.location.reload();
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error updating cart quantity:", error);
        // Fallback: reload page on critical errors
        if (xhr.status === 403 || xhr.status === 500) {
          window.location.reload();
        }
      },
      complete: function () {
        // Remove loading state
        if ($element) {
          $element.removeClass("loading").prop("disabled", false);
        }
      },
    });
  }

  // Remove cart item via AJAX
  function removeCartItem(cartItemKey, $element) {
    // Validate parameters
    if (!cartItemKey || !wc_add_to_cart_params) {
      console.error("Invalid parameters for cart item removal");
      if ($element) {
        $element.removeClass("loading").prop("disabled", false);
      }
      return;
    }

    $.ajax({
      type: "POST",
      url: wc_add_to_cart_params.ajax_url,
      data: {
        action: "woocommerce_remove_cart_item",
        cart_item_key: cartItemKey,
        security: wc_add_to_cart_params.remove_cart_nonce,
      },
      success: function (response) {
        if (response.success) {
          // Update cart fragments
          if (response.data && response.data.fragments) {
            $.each(response.data.fragments, function (key, value) {
              $(key).replaceWith(value);
            });
          }
          // Trigger WooCommerce cart update events
          $(document.body).trigger("update_checkout");
          $(document.body).trigger("wc_fragment_refresh");
          $(document.body).trigger("removed_from_cart");

          // Check if cart is empty and close panel
          setTimeout(function () {
            if ($(".woocommerce-mini-cart__items li").length === 0) {
              closeCart();
            }
          }, 100);
        } else {
          console.error("Failed to remove cart item:", response.data);
          // Fallback: reload page if AJAX fails
          if (
            response.data &&
            response.data.includes("Security check failed")
          ) {
            window.location.reload();
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error removing cart item:", error);
        // Fallback: reload page on critical errors
        if (xhr.status === 403 || xhr.status === 500) {
          window.location.reload();
        }
      },
      complete: function () {
        // Remove loading state
        if ($element) {
          $element.removeClass("loading").prop("disabled", false);
        }
      },
    });
  }

  // Close when clicking overlay
  $(document).on("click", ".cart-overlay", function (e) {
    e.preventDefault();
    closeCart(this);
  });

  // Close when clicking outside (but not on overlay, as that's handled above)
  $(document).on("click", function (e) {
    const $target = $(e.target);
    const $cartWrap = $("[data-behavior='click']").first();
    if (
      $cartWrap.attr("data-open") === "true" &&
      !$target.closest("[data-behavior='click']").length &&
      !$target.hasClass("cart-overlay") &&
      e.type === "click"
    ) {
      closeCart();
    }
  });

  // Initialize open/close feedback on add to cart (optional)
  $(document).on("added_to_cart", function () {
    openCart();
    setTimeout(function () {
      closeCart();
    }, 2000);
  });

  // Shop Filter Bar Controller (preserved)
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
      if (!isOpen) $dropdown.addClass("open");
    }

    handleFilterOption(event) {
      event.preventDefault();
      const $option = $(event.currentTarget);
      const $dropdown = $option.closest(".filter-dropdown");
      const filterValue = $option.data("filter");
      const filterText = $option.text().trim();
      $dropdown.find(".filter-dropdown-text").text(filterText);
      $dropdown.removeClass("open");
      this.applyFilter(filterValue);
    }

    handleOutsideClick(event) {
      const $target = $(event.target);
      if (!$target.closest(".filter-dropdown").length) {
        $(".filter-dropdown").removeClass("open");
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
      return this.isMobileDevice() ? "2" : "3";
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
          this.currentGrid = "3";
          this.setCookie("primefit_grid_view", "3", 30);
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

  if ($(".grid-option").length > 0) {
    const shopFilterController = new ShopFilterController();
  }

  // Header: add scrolled state to force black background on sticky
  const $header = $(".site-header");
  if ($header.length) {
    const toggleScrolled = () => {
      if (window.scrollY > 10) {
        $header.addClass("is-scrolled");
      } else {
        $header.removeClass("is-scrolled");
      }
    };
    toggleScrolled();
    $(window).on("scroll", toggleScrolled);
  }

  // Mobile hamburger menu
  $(document).on("click", ".hamburger", function (e) {
    e.preventDefault();
    const $body = $("body");
    const $nav = $("#mobile-nav");
    const isOpen = $body.hasClass("mobile-open");

    if (isOpen) {
      $body.removeClass("mobile-open");
      $(this).attr("aria-expanded", "false");
      // Re-enable page scrolling when mobile menu is closed
      allowPageScroll();
    } else {
      $body.addClass("mobile-open");
      $(this).attr("aria-expanded", "true");
      // Prevent page scrolling when mobile menu is open
      preventPageScroll();
    }
  });

  // Close mobile nav
  $(document).on(
    "click",
    ".mobile-nav-close, .mobile-nav-overlay",
    function (e) {
      e.preventDefault();
      $("body").removeClass("mobile-open");
      $(".hamburger").attr("aria-expanded", "false");
      // Re-enable page scrolling when mobile menu is closed
      allowPageScroll();
    }
  );

  // Mobile menu dropdown functionality
  $(document).on(
    "click",
    ".mobile-menu .menu-item-has-children > a",
    function (e) {
      e.preventDefault();
      const $parent = $(this).parent();
      const $submenu = $parent.find(".sub-menu");

      if ($submenu.length) {
        const isOpen = $parent.hasClass("mobile-submenu-open");

        // Close all other open submenus in the same menu
        $parent
          .siblings(".menu-item-has-children")
          .removeClass("mobile-submenu-open");

        if (!isOpen) {
          $parent.addClass("mobile-submenu-open");
        }
      }
    }
  );

  // Hero Video Background Handler
  class HeroVideoHandler {
    constructor() {
      this.init();
    }

    init() {
      this.handleHeroVideos();
    }

    handleHeroVideos() {
      const $heroVideos = $(".hero-video");

      if ($heroVideos.length === 0) return;

      $heroVideos.each((index, video) => {
        const $video = $(video);
        const videoElement = video;

        // Set up video event listeners
        this.setupVideoEvents($video, videoElement);

        // Start loading the video
        this.loadVideo($video, videoElement);
      });
    }

    setupVideoEvents($video, videoElement) {
      // When video can play through
      videoElement.addEventListener("canplaythrough", () => {
        this.onVideoReady($video, videoElement);
      });

      // When video starts playing
      videoElement.addEventListener("playing", () => {
        this.onVideoPlaying($video, videoElement);
      });

      // Handle video errors
      videoElement.addEventListener("error", () => {
        this.onVideoError($video, videoElement);
      });

      // Handle video loading
      videoElement.addEventListener("loadstart", () => {
        this.onVideoLoadStart($video, videoElement);
      });
    }

    loadVideo($video, videoElement) {
      // Set video source and start loading
      const sources = videoElement.querySelectorAll("source");
      if (sources.length > 0) {
        // Let the browser choose the best source
        videoElement.load();
      }
    }

    onVideoReady($video, videoElement) {
      // Video is ready to play
      $video.addClass("loaded");

      // Try to play the video
      const playPromise = videoElement.play();

      if (playPromise !== undefined) {
        playPromise
          .then(() => {
            this.onVideoPlaying($video, videoElement);
          })
          .catch((error) => {
            this.onVideoError($video, videoElement);
          });
      }
    }

    onVideoPlaying($video, videoElement) {
      // Video is playing successfully

      // Hide fallback image with smooth transition
      const $fallbackImage = $video
        .closest(".hero-media")
        .find(".hero-fallback-image");
      $fallbackImage.css("opacity", "0");
    }

    onVideoError($video, videoElement) {
      // Video failed to load or play

      // Ensure fallback image is visible
      const $fallbackImage = $video
        .closest(".hero-media")
        .find(".hero-fallback-image");
      $fallbackImage.css("opacity", "1");

      // Hide the video
      $video.css("opacity", "0");
    }
  }

  // Initialize hero video handler
  if ($(".hero-video").length > 0) {
    new HeroVideoHandler();
  }

  // Mega Menu Controller
  class MegaMenuController {
    constructor() {
      this.$megaMenu = $("#mega-menu");
      this.$header = $(".site-header");
      this.isDesktop = this.isDesktopDevice();
      this.isOpen = false;
      this.hoverTimeout = null;
      this.init();
    }

    init() {
      if (this.$megaMenu.length === 0) return;

      this.bindEvents();
      this.handleResize();
    }

    bindEvents() {
      // Show mega menu only on specific menu item hover (desktop only)
      // Look for menu items with data-mega-menu="true" attribute on the link
      $(document).on(
        "mouseenter",
        ".menu--primary .menu-item a[data-mega-menu='true']",
        this.handleMenuItemHover.bind(this)
      );
      $(document).on(
        "mouseleave",
        ".menu--primary .menu-item a[data-mega-menu='true']",
        this.handleMenuItemLeave.bind(this)
      );

      // Also handle hover on the mega menu itself to keep it open
      $(document).on(
        "mouseenter",
        ".mega-menu",
        this.handleMegaMenuHover.bind(this)
      );
      $(document).on(
        "mouseleave",
        ".mega-menu",
        this.handleMegaMenuLeave.bind(this)
      );

      // Hide mega menu when clicking outside
      $(document).on("click", this.handleOutsideClick.bind(this));

      // Handle window resize
      $(window).on("resize", this.debounce(this.handleResize.bind(this), 250));
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
