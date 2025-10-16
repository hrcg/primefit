/**
 * PrimeFit Theme - Search Functionality
 * Product search with AJAX, caching, and overlay management
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Search Manager - Handles all search functionality
   */
  const SearchManager = {
    // Configuration
    config: {
      minQueryLength: 2,
      debounceDelay: 300,
      maxResults: 12,
      cacheTimeout: 300000, // 5 minutes
      ajaxTimeout: 10000
    },

    // State management
    state: {
      isOpen: false,
      isLoading: false,
      currentQuery: '',
      cache: new Map(),
      abortController: null
    },

    // DOM elements
    elements: {
      $body: null,
      $searchToggle: null,
      $searchOverlay: null,
      $searchPanel: null,
      $searchInput: null,
      $searchClose: null,
      $searchResults: null,
      $searchLoading: null,
      $searchNoResults: null,
      $searchResultsCount: null,
      $searchViewAll: null
    },

    /**
     * Initialize search functionality
     */
    init: function() {
      this.cacheElements();
      this.bindEvents();
      this.setupDesktopSearchMenu();
    },

    /**
     * Cache DOM elements for performance
     */
    cacheElements: function() {
      this.elements.$body = $('body');
      this.elements.$searchToggle = $('.search-toggle');
      this.elements.$searchOverlay = $('#search-overlay');
      this.elements.$searchPanel = $('.search-panel');
      this.elements.$searchInput = $('.search-input');
      this.elements.$searchClose = $('.search-close');
      this.elements.$searchResults = $('.search-results');
      this.elements.$searchLoading = $('.search-loading');
      this.elements.$searchNoResults = $('.search-no-results');
      this.elements.$searchResultsCount = $('.search-results-count');
      this.elements.$searchViewAll = $('.search-view-all');
    },

    /**
     * Bind event handlers
     */
    bindEvents: function() {
      // Search toggle events
      this.elements.$searchToggle.on('click', this.handleSearchToggle.bind(this));
      
      // Search close events
      this.elements.$searchClose.on('click', this.closeSearch.bind(this));
      this.elements.$searchOverlay.find('.search-overlay').on('click', this.closeSearch.bind(this));
      
      // Search input events
      this.elements.$searchInput.on('input', this.debounce(this.handleSearchInput.bind(this), this.config.debounceDelay));
      this.elements.$searchInput.on('keydown', this.handleKeydown.bind(this));
      
      // Keyboard events
      $(document).on('keydown', this.handleGlobalKeydown.bind(this));
      
      // Prevent body scroll when search is open
      this.elements.$searchInput.on('focus', this.preventBodyScroll.bind(this));
    },

    /**
     * Setup desktop search menu integration
     */
    setupDesktopSearchMenu: function() {
      // Find and modify the "Search" menu item in secondary navigation
      const $searchMenuItem = $('.menu--secondary a').filter(function() {
        return $(this).text().trim().toLowerCase() === 'search';
      });

      if ($searchMenuItem.length) {
        $searchMenuItem.attr('href', '#');
        $searchMenuItem.on('click', function(e) {
          e.preventDefault();
          SearchManager.openSearch();
        });
      }
    },

    /**
     * Handle search toggle
     */
    handleSearchToggle: function(e) {
      e.preventDefault();
      
      if (this.state.isOpen) {
        this.closeSearch();
      } else {
        this.openSearch();
      }
    },

    /**
     * Open search overlay
     */
    openSearch: function() {
      this.state.isOpen = true;
      this.elements.$body.addClass('search-open');
      this.elements.$searchPanel.removeAttr('hidden');
      this.elements.$searchToggle.attr('aria-expanded', 'true');
      
      // Focus search input after animation
      setTimeout(() => {
        this.elements.$searchInput.focus();
      }, 100);
      
      // Prevent body scroll
      this.preventBodyScroll();
    },

    /**
     * Close search overlay
     */
    closeSearch: function() {
      this.state.isOpen = false;
      this.elements.$body.removeClass('search-open');
      this.elements.$searchPanel.attr('hidden', 'true');
      this.elements.$searchToggle.attr('aria-expanded', 'false');
      
      // Clear search input and results
      this.elements.$searchInput.val('');
      this.clearResults();
      
      // Allow body scroll
      this.allowBodyScroll();
      
      // Abort any pending requests
      if (this.state.abortController) {
        this.state.abortController.abort();
        this.state.abortController = null;
      }
    },

    /**
     * Handle search input
     */
    handleSearchInput: function() {
      const query = this.elements.$searchInput.val().trim();
      
      if (query === this.state.currentQuery) {
        return; // No change
      }
      
      this.state.currentQuery = query;
      
      if (query.length < this.config.minQueryLength) {
        this.clearResults();
        return;
      }
      
      this.performSearch(query);
    },

    /**
     * Handle keyboard events in search input
     */
    handleKeydown: function(e) {
      switch (e.key) {
        case 'Escape':
          e.preventDefault();
          this.closeSearch();
          break;
        case 'Enter':
          e.preventDefault();
          if (this.state.currentQuery.length >= this.config.minQueryLength) {
            this.navigateToSearchResults();
          }
          break;
      }
    },

    /**
     * Handle global keyboard events
     */
    handleGlobalKeydown: function(e) {
      // Open search with Ctrl/Cmd + K
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        this.openSearch();
      }
      
      // Close search with Escape
      if (e.key === 'Escape' && this.state.isOpen) {
        this.closeSearch();
      }
    },

    /**
     * Perform search with AJAX
     */
    performSearch: function(query) {
      // Check cache first
      if (this.state.cache.has(query)) {
        const cachedData = this.state.cache.get(query);
        if (Date.now() - cachedData.timestamp < this.config.cacheTimeout) {
          this.displayResults(cachedData.data);
          return;
        } else {
          this.state.cache.delete(query);
        }
      }
      
      // Show loading state
      this.showLoading();
      
      // Abort previous request
      if (this.state.abortController) {
        this.state.abortController.abort();
      }
      
      // Create new abort controller
      this.state.abortController = new AbortController();
      
      // Prepare AJAX request
      const ajaxData = {
        action: 'primefit_product_search',
        query: query,
        nonce: window.primefitData?.nonce || ''
      };
      
      // Perform AJAX request
      $.ajax({
        url: window.primefitData?.ajaxUrl || '/wp-admin/admin-ajax.php',
        type: 'POST',
        data: ajaxData,
        timeout: this.config.ajaxTimeout,
        signal: this.state.abortController.signal,
        success: (response) => {
          if (response.success && response.data) {
            // Cache results
            this.state.cache.set(query, {
              data: response.data,
              timestamp: Date.now()
            });
            
            // Debug logging
            if (response.data.debug) {
              console.log('Search debug info:', response.data.debug);
            }
            
            this.displayResults(response.data);
          } else {
            console.log('Search response:', response);
            this.showNoResults();
          }
        },
        error: (xhr, status, error) => {
          if (status !== 'abort') {
            console.error('Search error:', error);
            this.showNoResults();
          }
        },
        complete: () => {
          this.hideLoading();
          this.state.abortController = null;
        }
      });
    },

    /**
     * Display search results
     */
    displayResults: function(data) {
      this.hideLoading();
      this.hideNoResults();
      
      if (!data.products || data.products.length === 0) {
        this.showNoResults();
        return;
      }
      
      // Update results count
      this.elements.$searchResultsCount.text(`${data.total} results`);
      
      // Remove view-all usage (button removed from DOM). If present, hide it defensively.
      if (this.elements.$searchViewAll && this.elements.$searchViewAll.length) {
        this.elements.$searchViewAll.hide();
      }
      
      // Prefer server-rendered WooCommerce loop HTML for consistency
      if (data.html) {
        this.elements.$searchResults.html(data.html);
      } else {
        const resultsHtml = this.generateResultsHtml(data.products);
        this.elements.$searchResults.html(resultsHtml);
      }
      
      // Show results
      this.elements.$searchResults.show();
    },

    /**
     * Generate results HTML using existing product loop structure
     */
    generateResultsHtml: function(products) {
      let html = '<div class="search-results-grid">';
      
      products.forEach(product => {
        html += `
          <div class="search-result-item">
            <a href="${product.url}" class="search-result-link">
              <div class="search-result-image">
                ${product.image ? `<img src="${product.image}" alt="${product.title}" loading="lazy">` : ''}
              </div>
              <div class="search-result-content">
                <h3 class="search-result-title">${product.title}</h3>
                <div class="search-result-price">${product.price}</div>
              </div>
            </a>
          </div>
        `;
      });
      
      html += '</div>';
      return html;
    },

    /**
     * Clear search results
     */
    clearResults: function() {
      this.elements.$searchResults.empty().hide();
      this.elements.$searchResultsCount.text('0 results');
      this.hideLoading();
      this.hideNoResults();
    },

    /**
     * Show loading state
     */
    showLoading: function() {
      this.elements.$searchLoading.show();
      this.elements.$searchResults.hide();
      this.elements.$searchNoResults.hide();
    },

    /**
     * Hide loading state
     */
    hideLoading: function() {
      this.elements.$searchLoading.hide();
    },

    /**
     * Show no results state
     */
    showNoResults: function() {
      this.elements.$searchNoResults.show();
      this.elements.$searchResults.hide();
      this.elements.$searchResultsCount.text('0 results');
    },

    /**
     * Hide no results state
     */
    hideNoResults: function() {
      this.elements.$searchNoResults.hide();
    },

    /**
     * Navigate to full search results page
     */
    navigateToSearchResults: function() {
      const searchUrl = `${window.location.origin}/?s=${encodeURIComponent(this.state.currentQuery)}&post_type=product`;
      window.location.href = searchUrl;
    },

    /**
     * Prevent body scroll
     */
    preventBodyScroll: function() {
      this.elements.$body.addClass('search-scroll-locked');
    },

    /**
     * Allow body scroll
     */
    allowBodyScroll: function() {
      this.elements.$body.removeClass('search-scroll-locked');
    },

    /**
     * Debounce function for performance
     */
    debounce: function(func, wait) {
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
  };

  /**
   * Initialize search functionality when DOM is ready
   */
  $(document).ready(function() {
    SearchManager.init();
  });

  // Expose SearchManager globally for debugging
  window.SearchManager = SearchManager;

})(jQuery);
