(function ($) {
  "use strict";

  // Prevent accidental re-adding product on refresh when URL has add-to-cart params
  $(function () {
    try {
      var url = new URL(window.location.href);
      if (
        url.searchParams.has("add-to-cart") ||
        url.searchParams.has("added-to-cart")
      ) {
        // Intervene on all pages except cart/checkout to avoid interfering with notices
        var isCartPage = document.body.classList.contains("woocommerce-cart");
        var isCheckoutPage = document.body.classList.contains("woocommerce-checkout");
        if (!isCartPage && !isCheckoutPage) {
          url.searchParams.delete("add-to-cart");
          url.searchParams.delete("added-to-cart");
          url.searchParams.delete("quantity");
          var newSearch = url.searchParams.toString();
          var newUrl = url.pathname + (newSearch ? "?" + newSearch : "") + url.hash;
          window.history.replaceState({}, "", newUrl);
        }
      }
    } catch (e) {
      // Ignore if URL API not available
    }
  });

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
    console.log('openCart called with:', clickedEl); // Debug log
    const { $wrap, $panel, $toggle } = getCartContext(clickedEl);
    console.log('Cart context:', {wrap: $wrap.length, panel: $panel.length, toggle: $toggle.length}); // Debug log
    
    $wrap.addClass("open").attr("data-open", "true");
    $panel.removeAttr("hidden");
    $toggle.attr("aria-expanded", "true");

    if (window.matchMedia("(max-width: 1024px)").matches) {
      document.body.classList.add("cart-open");
    }

    // Prevent page scrolling when cart is open
    preventPageScroll();
    console.log('Cart opened successfully'); // Debug log
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

  // Cart quantity controls - Mini cart increment/decrement
  $(document).on(
    "click",
    ".woocommerce-mini-cart__item-quantity .plus, .mini_cart_item-quantity .plus, .woocommerce-mini-cart-item-quantity .plus",
    function (e) {
      e.preventDefault();
      const cartItemKey = $(this).data("cart-item-key");
      const $input = $(this).siblings("input");
      const currentQty = parseInt($input.val());
      const maxQty = parseInt($input.attr("max"));

      if (currentQty < maxQty) {
        // Add loading state
        $(this).addClass("loading").prop("disabled", true);
        $input.prop("disabled", true);
        updateCartQuantity(cartItemKey, currentQty + 1, $(this));
      }
    }
  );

  $(document).on(
    "click",
    ".woocommerce-mini-cart__item-quantity .minus, .mini_cart_item-quantity .minus, .woocommerce-mini-cart-item-quantity .minus",
    function (e) {
      e.preventDefault();
      const cartItemKey = $(this).data("cart-item-key");
      const $input = $(this).siblings("input");
      const currentQty = parseInt($input.val());

      if (currentQty > 1) {
        // Add loading state
        $(this).addClass("loading").prop("disabled", true);
        $input.prop("disabled", true);
        updateCartQuantity(cartItemKey, currentQty - 1, $(this));
      }
    }
  );

  $(document).on(
    "change",
    ".woocommerce-mini-cart__item-quantity input, .mini_cart_item-quantity input, .woocommerce-mini-cart-item-quantity input",
    function (e) {
      const cartItemKey = $(this).data("cart-item-key");
      const newQty = parseInt($(this).val());
      const maxQty = parseInt($(this).attr("max"));

      if (newQty >= 1 && newQty <= maxQty && newQty !== parseInt($(this).data("original-value"))) {
        // Add loading state to input
        $(this).addClass("loading").prop("disabled", true);
        updateCartQuantity(cartItemKey, newQty, $(this));
      } else {
        $(this).val($(this).data("original-value") || 1);
      }
    }
  );

  // Remove item from cart: defer to WooCommerce core handler; keep fallback only
  $(document).ready(function() {
    $(document).off("click.primefit-cart", ".woocommerce-mini-cart__item-remove");
    $(document).on("click.primefit-cart", ".woocommerce-mini-cart__item-remove[href='#']", function (e) {
      e.preventDefault();
      const $btn = $(this);
      const cartItemKey = $btn.data("cart-item-key");
      $btn.addClass("loading").prop("disabled", true);
      removeCartItem(cartItemKey, $btn);
    });
  });

  // Update cart quantity via AJAX
  function updateCartQuantity(cartItemKey, quantity, $element) {
    // Validate parameters
    if (!cartItemKey || !quantity || !window.primefit_cart_params) {
      console.error("Invalid parameters for cart update");
      if ($element) {
        $element.removeClass("loading").prop("disabled", false);
      }
      return;
    }

    $.ajax({
      type: "POST",
      url: primefit_cart_params.ajax_url,
      data: {
        action: "wc_ajax_update_cart_item_quantity",
        cart_item_key: cartItemKey,
        quantity: quantity,
        security: primefit_cart_params.update_cart_nonce,
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
          // Note: Avoid triggering added_to_cart without required params
          
          // Check if cart is empty after quantity update
          setTimeout(function () {
            checkAndShowEmptyCartState();
          }, 100);
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
        // Remove loading state from both button and input
        if ($element) {
          $element.removeClass("loading").prop("disabled", false);
          // Also re-enable the input field
          const $input = $element.siblings("input");
          if ($input.length) {
            $input.removeClass("loading").prop("disabled", false);
          }
        }
      },
    });
  }

  // Remove cart item via AJAX
  function removeCartItem(cartItemKey, $element) {
    // Validate parameters
    if (!cartItemKey || !window.primefit_cart_params) {
      console.error("Invalid parameters for cart item removal");
      if ($element) {
        $element.removeClass("loading").prop("disabled", false);
      }
      return;
    }

    // Find the cart item element for animation
    const $cartItem = $(`.woocommerce-mini-cart__item-remove[data-cart-item-key="${cartItemKey}"]`).closest('.woocommerce-mini-cart__item');
    
    // Add loading state to the remove button
    $element.addClass("loading").prop("disabled", true);

    console.log('CART DEBUG: Removing cart item:', cartItemKey); // Debug log
    console.log('CART DEBUG: primefit_cart_params:', primefit_cart_params); // Debug log
    
    // Validate we have the required parameters
    if (!primefit_cart_params.ajax_url) {
      console.error('CART DEBUG: No AJAX URL available');
      alert('Configuration error: No AJAX URL');
      return;
    }
    
    if (!primefit_cart_params.remove_cart_nonce) {
      console.error('CART DEBUG: No remove cart nonce available');
      alert('Configuration error: No security nonce');
      return;
    }
    
    const ajaxData = {
      action: "wc_ajax_remove_cart_item",
      cart_item_key: cartItemKey,
      security: primefit_cart_params.remove_cart_nonce,
    };
    
    console.log('CART DEBUG: AJAX data being sent:', ajaxData);

    $.ajax({
      type: "POST",
      url: primefit_cart_params.ajax_url,
      data: ajaxData,
      success: function (response) {
        console.log('Server response:', response); // Debug log
        
        if (response.success) {
          // Start fade-out animation immediately
          if ($cartItem.length) {
            $cartItem.addClass('removing');
          }
          
          // Update cart fragments - these should now reflect the item being removed
          if (response.data && response.data.fragments) {
            $.each(response.data.fragments, function (key, value) {
              $(key).replaceWith(value);
            });
            
            // Use server's cart state to determine if empty
            console.log('Server says cart is empty:', response.data.cart_is_empty);
            console.log('Server cart contents count:', response.data.cart_contents_count);
            
            // Check cart state using server data
            setTimeout(function () {
              if (response.data.cart_is_empty === true || response.data.cart_contents_count === 0) {
                console.log('Server confirms cart is empty - showing empty state');
                showEmptyCartState();
              } else {
                console.log('Server says cart has items - hiding empty state');
                hideEmptyCartState();
              }
            }, 50);
          } else {
            console.error('No fragments returned from server');
          }
          
          // Trigger WooCommerce cart update events
          $(document.body).trigger("update_checkout");
          $(document.body).trigger("wc_fragment_refresh");
          // Note: Avoid triggering removed_from_cart without required params
          
        } else {
          console.error("CART DEBUG: Server failed to remove cart item:", response);
          console.error("CART DEBUG: Response data:", response.data);
          
          // Remove loading state and fade class on error
          $element.removeClass("loading").prop("disabled", false);
          if ($cartItem.length) {
            $cartItem.removeClass('removing');
          }
          
          // Show specific error message
          let errorMessage = 'Failed to remove item from cart. ';
          if (response.data) {
            errorMessage += 'Error: ' + response.data;
          }
          errorMessage += ' Please check browser console for details.';
          
          alert(errorMessage);
          
          // Fallback: reload page if AJAX fails
          if (response.data && typeof response.data === 'string' && response.data.includes("Security check failed")) {
            console.log('CART DEBUG: Security check failed - reloading page');
            window.location.reload();
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error removing cart item:", {xhr, status, error});
        
        // Remove loading state and fade class on error
        $element.removeClass("loading").prop("disabled", false);
        if ($cartItem.length) {
          $cartItem.removeClass('removing');
        }
        
        // Show user-friendly error  
        alert('Network error. Please check your connection and try again.');
        
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

  // Function to check and show empty cart state if needed
  function checkAndShowEmptyCartState() {
    const $cartItems = $('.woocommerce-mini-cart__items');
    const $cartContent = $('.cart-panel-content');
    
    console.log('Checking empty cart state...'); // Debug log
    console.log('Cart items container exists:', $cartItems.length > 0); // Debug log
    
    // Check multiple indicators to ensure cart is truly empty
    const hasCartItemsContainer = $cartItems.length > 0;
    const cartItemsCount = hasCartItemsContainer ? $cartItems.find('li.woocommerce-mini-cart__item').length : 0;
    const hasEmptyMessage = $('.woocommerce-mini-cart__empty-message').length > 0;
    
    console.log('Cart items count:', cartItemsCount); // Debug log
    console.log('Has empty message:', hasEmptyMessage); // Debug log
    
    // If no cart items container exists OR cart items container is empty
    if (!hasCartItemsContainer || cartItemsCount === 0) {
      console.log('Cart is empty - showing empty state'); // Debug log
      showEmptyCartState();
    } else {
      console.log('Cart has items - hiding empty state'); // Debug log  
      hideEmptyCartState();
    }
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

  // Auto-open mini cart when product is added to cart
  $(document).on("added_to_cart", function (event, fragments, cart_hash, $button) {
    console.log('Product added to cart - auto-opening mini cart'); // Debug log
    console.log('Event data:', {fragments, cart_hash, button: $button}); // Debug log
    
    // Check cart state and hide empty message if needed
    setTimeout(function () {
      checkAndShowEmptyCartState();
    }, 100);
    
    // Open cart immediately
    openCart();
    
    // Auto-close after 5 seconds
    setTimeout(function () {
      console.log('Auto-closing mini cart after 5 seconds'); // Debug log
      closeCart();
    }, 5000);
  });

  // Function to hide empty cart state
  function hideEmptyCartState() {
    console.log('Hiding empty cart state'); // Debug log
    
    const $cartItems = $('.woocommerce-mini-cart__items');
    const $cartTotal = $('.woocommerce-mini-cart__total');
    const $cartButtons = $('.woocommerce-mini-cart__buttons');
    const $cartRecommendations = $('.cart-recommendations');
    const $cartCheckoutSummary = $('.cart-checkout-summary');
    
    // Show all cart content if it exists
    if ($cartItems.length) $cartItems.show();
    if ($cartTotal.length) $cartTotal.show();
    if ($cartButtons.length) $cartButtons.show();
    if ($cartRecommendations.length) $cartRecommendations.show();
    if ($cartCheckoutSummary.length) $cartCheckoutSummary.show();
    
    // Hide empty message
    $emptyMessage.hide();
    
    console.log('Cart content is now visible'); // Debug log
  }

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
      
      // Remove body class when closing
      if (isOpen) {
        document.body.classList.remove("filter-dropdown-open");
        allowPageScroll();
      } else {
        $dropdown.addClass("open");
        // Add body class and prevent scroll on mobile when opening
        if (this.isMobile) {
          document.body.classList.add("filter-dropdown-open");
          preventPageScroll();
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
      allowPageScroll();
      this.applyFilter(filterValue);
    }

    handleOutsideClick(event) {
      const $target = $(event.target);
      if (!$target.closest(".filter-dropdown").length) {
        $(".filter-dropdown").removeClass("open");
        // Remove body class and restore scroll when closing dropdown
        document.body.classList.remove("filter-dropdown-open");
        allowPageScroll();
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

  // Mini Cart Enhancements
  
  // Handle coupon form submission in mini cart
  $(document).on('submit', '.mini-cart-coupon-form', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $input = $form.find('.coupon-code-input');
    const $button = $form.find('.apply-coupon-btn');
    const couponCode = $input.val().trim();
    
    if (!couponCode) {
      return;
    }
    
    // Show loading state
    $button.addClass('loading').prop('disabled', true).text('Applying...');
    
    // Apply coupon via AJAX
    $.ajax({
      type: 'POST',
      url: primefit_cart_params.ajax_url,
      data: {
        action: 'apply_coupon',
        security: primefit_cart_params.apply_coupon_nonce,
        coupon_code: couponCode
      },
      success: function(response) {
        if (response.success) {
          // Clear the input
          $input.val('');
          
          // Refresh cart fragments
          $(document.body).trigger('update_checkout');
          $(document.body).trigger('wc_fragment_refresh');
          
          // Show success message
          $form.after('<div class="coupon-message success">Coupon applied successfully!</div>');
          
          setTimeout(function() {
            $('.coupon-message').fadeOut();
          }, 3000);
          
        } else {
          // Show error message
          let errorMsg = response.data || 'Failed to apply coupon';
          $form.after('<div class="coupon-message error">' + errorMsg + '</div>');
          
          setTimeout(function() {
            $('.coupon-message').fadeOut();
          }, 5000);
        }
      },
      error: function() {
        $form.after('<div class="coupon-message error">Network error. Please try again.</div>');
        setTimeout(function() {
          $('.coupon-message').fadeOut();
        }, 5000);
      },
      complete: function() {
        // Remove loading state
        $button.removeClass('loading').prop('disabled', false).text('APPLY');
      }
    });
  });
  
  // Handle coupon removal
  $(document).on('click', '.remove-coupon', function(e) {
    e.preventDefault();
    
    const $button = $(this);
    const couponCode = $button.data('coupon');
    
    if (!couponCode) {
      return;
    }
    
    // Show loading state
    $button.addClass('loading').prop('disabled', true);
    
    // Remove coupon via AJAX
    $.ajax({
      type: 'POST',
      url: primefit_cart_params.ajax_url,
      data: {
        action: 'remove_coupon',
        security: primefit_cart_params.remove_coupon_nonce,
        coupon: couponCode
      },
      success: function(response) {
        if (response.success) {
          // Refresh cart fragments
          $(document.body).trigger('update_checkout');
          $(document.body).trigger('wc_fragment_refresh');
        }
      },
      error: function() {
        console.error('Failed to remove coupon');
      },
      complete: function() {
        // Remove loading state
        $button.removeClass('loading').prop('disabled', false);
      }
    });
  });
  
  // Handle recommendation item add to cart
  $(document).on('click', '.recommendation-add-btn', function(e) {
    e.preventDefault();
    
    const $button = $(this);
    const productId = $button.data('product-id');
    
    if (!productId) {
      return;
    }
    
    // Show loading state
    $button.addClass('loading').prop('disabled', true).text('Adding...');
    
    // Add to cart via AJAX
    $.ajax({
      type: 'POST',
      url: wc_add_to_cart_params ? wc_add_to_cart_params.ajax_url : primefit_cart_params.ajax_url,
      data: {
        action: 'woocommerce_add_to_cart',
        product_id: productId,
        quantity: 1,
        security: wc_add_to_cart_params ? wc_add_to_cart_params.wc_ajax_add_to_cart_nonce : primefit_cart_params.add_to_cart_nonce
      },
      success: function(response) {
        if (response && !response.error) {
          // Update cart fragments
          if (response.fragments) {
            $.each(response.fragments, function(key, value) {
              $(key).replaceWith(value);
            });
          }
          
          // Trigger cart update events
          $(document.body).trigger('update_checkout');
          $(document.body).trigger('wc_fragment_refresh');
          console.log('Triggering added_to_cart event from recommendation with:', {fragments: response.fragments, cart_hash: response.cart_hash, button: $button}); // Debug log
          $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
          
          // Show success state
          $button.removeClass('loading').addClass('added').text('Added!');
          
          // Reset button after delay
          setTimeout(function() {
            $button.removeClass('added').text('+ ADD').prop('disabled', false);
          }, 2000);
          
        } else {
          // Show error
          $button.removeClass('loading').addClass('error').text('Error').prop('disabled', false);
          
          setTimeout(function() {
            $button.removeClass('error').text('+ ADD');
          }, 2000);
        }
      },
      error: function() {
        // Show error
        $button.removeClass('loading').addClass('error').text('Error').prop('disabled', false);
        
        setTimeout(function() {
          $button.removeClass('error').text('+ ADD');
        }, 2000);
      }
    });
  });
  
})(jQuery);
