(function ($) {
  'use strict';

  // Cart functionality (preserved)
  function getCartContext(clickedEl) {
    const $root = clickedEl ? $(clickedEl).closest('[data-behavior="click"]') : $('[data-behavior="click"]').first();
    return {
      $wrap: $root,
      $panel: $root.find('#mini-cart-panel'),
      $toggle: $root.find('.cart-toggle')
    };
  }

  function openCart(clickedEl) {
    const { $wrap, $panel, $toggle } = getCartContext(clickedEl);
    $wrap.addClass('open').attr('data-open', 'true');
    $panel.removeAttr('hidden');
    $toggle.attr('aria-expanded', 'true');

    if (window.matchMedia('(max-width: 1024px)').matches) {
      document.body.classList.add('cart-open');
    }
  }

  function closeCart(clickedEl) {
    const { $wrap, $panel, $toggle } = getCartContext(clickedEl);
    $wrap.removeClass('open').attr('data-open', 'false');
    $panel.attr('hidden', true);
    $toggle.attr('aria-expanded', 'false');

    document.body.classList.remove('cart-open');
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
      this.$gridOptions = $('.grid-option');
      this.$productsGrid = $('.woocommerce ul.products');
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
      $(document).on('click', '.grid-option', this.handleGridClick.bind(this));
      $(document).on('click', '.filter-dropdown-toggle', this.handleFilterToggle.bind(this));
      $(document).on('click', '.filter-dropdown-option', this.handleFilterOption.bind(this));
      $(document).on('click', this.handleOutsideClick.bind(this));
      $(window).on('resize', this.debounce(this.handleResize.bind(this), 250));
    }

    handleGridClick(event) {
      event.preventDefault();
      const $button = $(event.currentTarget);
      const gridValue = $button.data('grid');
      if (this.isMobile && (gridValue === '3' || gridValue === '4')) return;
      if (!this.isMobile && (gridValue === '1' || gridValue === '2')) return;
      this.$gridOptions.removeClass('active');
      $button.addClass('active');
      this.currentGrid = gridValue;
      this.setCookie('primefit_grid_view', gridValue, 30);
      this.applyGridLayout();
    }

    applyGridLayout() {
      this.$productsGrid.removeClass('grid-1 grid-2 grid-3 grid-4');
      this.$productsGrid.addClass(`grid-${this.currentGrid}`);
      this.$productsGrid.attr('class', this.$productsGrid.attr('class').replace(/columns-\d+/, `columns-${this.currentGrid}`));
    }

    handleFilterToggle(event) {
      event.preventDefault();
      event.stopPropagation();
      const $dropdown = $(event.currentTarget).closest('.filter-dropdown');
      const isOpen = $dropdown.hasClass('open');
      $('.filter-dropdown').removeClass('open');
      if (!isOpen) $dropdown.addClass('open');
    }

    handleFilterOption(event) {
      event.preventDefault();
      const $option = $(event.currentTarget);
      const $dropdown = $option.closest('.filter-dropdown');
      const filterValue = $option.data('filter');
      const filterText = $option.text().trim();
      $dropdown.find('.filter-dropdown-text').text(filterText);
      $dropdown.removeClass('open');
      this.applyFilter(filterValue);
    }

    handleOutsideClick(event) {
      const $target = $(event.target);
      if (!$target.closest('.filter-dropdown').length) {
        $('.filter-dropdown').removeClass('open');
      }
    }

    applyFilter(filterValue) {
      const filterMap = {
        'featured': 'menu_order',
        'best-selling': 'popularity',
        'alphabetical-az': 'title',
        'alphabetical-za': 'title-desc',
        'price-low-high': 'price',
        'price-high-low': 'price-desc',
        'date-old-new': 'date',
        'date-new-old': 'date-desc'
      };
      const orderbyValue = filterMap[filterValue] || 'menu_order';
      const $hiddenSelect = $('.woocommerce-ordering .orderby');
      $hiddenSelect.val(orderbyValue);
      $('.woocommerce-ordering').submit();
    }

    getCurrentGrid() {
      const cookieValue = this.getCookie('primefit_grid_view');
      if (cookieValue) return cookieValue;
      return this.isMobileDevice() ? '2' : '3';
    }

    isMobileDevice() {
      return window.matchMedia('(max-width: 1024px)').matches;
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
          this.currentGrid = '2';
          this.setCookie('primefit_grid_view', '2', 30);
        }
      } else {
        if (currentGrid < 3) {
          this.currentGrid = '3';
          this.setCookie('primefit_grid_view', '3', 30);
        }
      }
      this.updateActiveGridOption();
      this.applyGridLayout();
    }

    updateActiveGridOption() {
      this.$gridOptions.removeClass('active');
      const $activeOption = this.$gridOptions.filter(`[data-grid="${this.currentGrid}"]`);
      if ($activeOption.length) {
        $activeOption.addClass('active');
      }
    }

    setCookie(name, value, days) {
      const expires = new Date();
      expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
      document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
    }

    getCookie(name) {
      const nameEQ = name + "=";
      const ca = document.cookie.split(';');
      for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
      }
      return null;
    }

    syncFilterState() {
      const urlParams = new URLSearchParams(window.location.search);
      const currentOrderby = urlParams.get('orderby') || $('.woocommerce-ordering .orderby').val() || 'menu_order';
      const orderbyMap = {
        'menu_order': 'featured',
        'popularity': 'best-selling',
        'title': 'alphabetical-az',
        'title-desc': 'alphabetical-za',
        'price': 'price-low-high',
        'price-desc': 'price-high-low',
        'date': 'date-old-new',
        'date-desc': 'date-new-old'
      };
      const currentFilter = orderbyMap[currentOrderby] || 'featured';
      const $dropdown = $('.filter-dropdown');
      const $activeOption = $dropdown.find(`[data-filter="${currentFilter}"]`);
      if ($activeOption.length) {
        $dropdown.find('.filter-dropdown-text').text($activeOption.text().trim());
        $dropdown.find('.filter-dropdown-option').removeClass('active');
        $activeOption.addClass('active');
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

  if ($('.grid-option').length > 0) {
    const shopFilterController = new ShopFilterController();
  }

  // Header: add scrolled state to force black background on sticky
  const $header = $('.site-header');
  if ($header.length) {
    const toggleScrolled = () => {
      if (window.scrollY > 10) {
        $header.addClass('is-scrolled');
      } else {
        $header.removeClass('is-scrolled');
      }
    };
    toggleScrolled();
    $(window).on('scroll', toggleScrolled);
  }

  // Mobile hamburger menu
  $(document).on('click', '.hamburger', function(e) {
    e.preventDefault();
    const $body = $('body');
    const $nav = $('#mobile-nav');
    const isOpen = $body.hasClass('mobile-open');
    
    if (isOpen) {
      $body.removeClass('mobile-open');
      $(this).attr('aria-expanded', 'false');
    } else {
      $body.addClass('mobile-open');
      $(this).attr('aria-expanded', 'true');
    }
  });

  // Close mobile nav
  $(document).on('click', '.mobile-nav-close, .mobile-nav-overlay', function(e) {
    e.preventDefault();
    $('body').removeClass('mobile-open');
    $('.hamburger').attr('aria-expanded', 'false');
  });
})(jQuery);
