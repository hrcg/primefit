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
      ajaxTimeout: 6000, // Reduced from 10s to 6s for faster failure detection
      maxCacheEntries: 100, // LRU cache limit
      debugMode: false, // Set to true for development debugging
    },

    // State management
    state: {
      isOpen: false,
      isLoading: false,
      currentQuery: "",
      cache: new Map(),
      currentRequest: null, // jqXHR object for proper cancellation
      lastRequestId: 0, // Track request sequence to prevent stale responses
      trendingSearchesLoaded: false, // Track if trending searches have been loaded
      trendingSearchesCache: null, // Cache trending searches data
    },

    // DOM elements
    elements: {
      $body: null,
      $searchToggle: null,
      $searchOverlay: null,
      $searchPanel: null,
      $searchInput: null,
      $searchClose: null,
      $searchBack: null,
      $searchClear: null,
      $searchResults: null,
      $searchLoading: null,
      $searchNoResults: null,
      $searchResultsCount: null,
      $searchViewAll: null,
      $trendingSearches: null,
      $trendingSearchesList: null,
      $recentlyViewed: null,
      $recentlyViewedList: null,
      $recentlyViewedClear: null,
    },

    /**
     * Initialize search functionality
     */
    init: function () {
      this.cacheElements();
      this.bindEvents();
      this.setupDesktopSearchMenu();
      // Don't load trending searches immediately - load when search opens
    },

    /**
     * Cache DOM elements for performance
     */
    cacheElements: function () {
      this.elements.$body = $("body");
      this.elements.$searchToggle = $(".search-toggle");
      this.elements.$searchOverlay = $("#search-overlay");
      this.elements.$searchPanel = $(".search-panel");
      this.elements.$searchInput = $(".search-input");
      this.elements.$searchClose = $(".search-close");
      this.elements.$searchBack = $(".search-back");
      this.elements.$searchClear = $(".search-clear");
      this.elements.$searchResults = $(".search-results");
      this.elements.$searchLoading = $(".search-loading");
      this.elements.$searchNoResults = $(".search-no-results");
      this.elements.$searchResultsCount = $(".search-results-count");
      this.elements.$searchViewAll = $(".search-view-all");
      this.elements.$trendingSearches = $("#trending-searches");
      this.elements.$trendingSearchesList = $(".trending-searches-list");
      this.elements.$recentlyViewed = $("#recently-viewed");
      this.elements.$recentlyViewedList = $(".recently-viewed-list");
      this.elements.$recentlyViewedClear = $(".recently-viewed-clear");
    },

    /**
     * Bind event handlers
     */
    bindEvents: function () {
      // Search toggle events
      this.elements.$searchToggle.on(
        "click",
        this.handleSearchToggle.bind(this)
      );

      // Search close events
      this.elements.$searchClose.on("click", this.closeSearch.bind(this));
      this.elements.$searchBack.on("click", this.closeSearch.bind(this));
      this.elements.$searchOverlay
        .find(".search-overlay")
        .on("click", this.closeSearch.bind(this));

      // Search input events
      this.elements.$searchInput.on(
        "input",
        this.debounce(
          this.handleSearchInput.bind(this),
          this.config.debounceDelay
        )
      );
      this.elements.$searchInput.on("input", this.toggleClearButton.bind(this));
      this.elements.$searchInput.on("keydown", this.handleKeydown.bind(this));

      // Clear button event
      this.elements.$searchClear.on("click", this.clearSearchInput.bind(this));

      // Keyboard events
      $(document).on("keydown", this.handleGlobalKeydown.bind(this));

      // Prevent body scroll when search is open
      this.elements.$searchInput.on("focus", this.preventBodyScroll.bind(this));

      // Recently viewed clear
      this.elements.$recentlyViewed.on(
        "click",
        ".recently-viewed-clear",
        (e) => {
          e.preventDefault();
          this.clearRecentlyViewed();
        }
      );
    },

    /**
     * Setup desktop search menu integration
     */
    setupDesktopSearchMenu: function () {
      // Find and modify the "Search" menu item in secondary navigation
      const $searchMenuItem = $(".menu--secondary a").filter(function () {
        return $(this).text().trim().toLowerCase() === "search";
      });

      if ($searchMenuItem.length) {
        $searchMenuItem.attr("href", "#");
        $searchMenuItem.on("click", function (e) {
          e.preventDefault();
          SearchManager.openSearch();
        });
      }
    },

    /**
     * Handle search toggle
     */
    handleSearchToggle: function (e) {
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
    openSearch: function () {
      this.state.isOpen = true;
      this.elements.$body.addClass("search-open");
      this.elements.$searchPanel.removeAttr("hidden");
      this.elements.$searchToggle.attr("aria-expanded", "true");

      // Load trending searches only when search opens (lazy loading)
      if (!this.state.trendingSearchesLoaded) {
        this.loadTrendingSearches();
      } else if (this.state.trendingSearchesCache) {
        this.displayTrendingSearches(this.state.trendingSearchesCache);
      }

      // Load and show recently viewed
      this.displayRecentlyViewed(this.loadRecentlyViewed());

      // Focus search input after animation with multiple attempts for reliability
      setTimeout(() => {
        this.elements.$searchInput.focus();
        // Ensure focus is actually applied
        if (document.activeElement !== this.elements.$searchInput[0]) {
          this.elements.$searchInput[0].focus();
        }
      }, 150);

      // Additional focus attempt after panel animation completes
      setTimeout(() => {
        if (
          this.state.isOpen &&
          document.activeElement !== this.elements.$searchInput[0]
        ) {
          this.elements.$searchInput[0].focus();
        }
      }, 400);

      // Prevent body scroll
      this.preventBodyScroll();
    },

    /**
     * Close search overlay
     */
    closeSearch: function () {
      this.state.isOpen = false;
      this.elements.$body.removeClass("search-open");
      this.elements.$searchPanel.attr("hidden", "true");
      this.elements.$searchToggle.attr("aria-expanded", "false");

      // Clear search input and results
      this.elements.$searchInput.val("");
      this.state.currentQuery = ""; // Reset current query
      this.toggleClearButton(); // Hide clear button
      this.clearResults();

      // Allow body scroll
      this.allowBodyScroll();

      // Abort any pending requests using jqXHR.abort()
      if (this.state.currentRequest) {
        this.state.currentRequest.abort();
        this.state.currentRequest = null;
      }

      // Clean up event handlers to prevent memory leaks
      this.elements.$trendingSearchesList.off("click.trending");
    },

    /**
     * Toggle clear button visibility based on input value
     */
    toggleClearButton: function () {
      const hasValue = this.elements.$searchInput.val().trim().length > 0;
      const $container = this.elements.$searchInput.closest(
        ".search-input-container"
      );
      if (hasValue) {
        this.elements.$searchClear.show().attr("aria-hidden", "false");
        $container.addClass("has-clear");
      } else {
        this.elements.$searchClear.hide().attr("aria-hidden", "true");
        $container.removeClass("has-clear");
      }
    },

    /**
     * Clear search input
     */
    clearSearchInput: function (e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      this.elements.$searchInput.val("");
      this.state.currentQuery = "";
      this.elements.$searchInput.focus();
      this.toggleClearButton();
      this.clearResults();
      this.showTrendingSearches();
    },

    /**
     * Handle search input
     */
    handleSearchInput: function () {
      const rawQuery = this.elements.$searchInput.val().trim();
      const normalizedQuery = this.normalizeQuery(rawQuery);

      if (normalizedQuery === this.state.currentQuery) {
        return; // No change
      }

      this.state.currentQuery = normalizedQuery;

      if (rawQuery.length < this.config.minQueryLength) {
        this.clearResults();
        this.showTrendingSearches();
        return;
      }

      this.hideTrendingSearches();
      this.performSearch(normalizedQuery);
    },

    /**
     * Handle keyboard events in search input
     */
    handleKeydown: function (e) {
      switch (e.key) {
        case "Escape":
          e.preventDefault();
          this.closeSearch();
          break;
        case "Enter":
          e.preventDefault();
          // Disabled: Enter key no longer navigates to search results page
          // Users can click on individual products or use trending searches
          break;
      }
    },

    /**
     * Handle global keyboard events
     */
    handleGlobalKeydown: function (e) {
      // Open search with Ctrl/Cmd + K
      if ((e.ctrlKey || e.metaKey) && e.key === "k") {
        e.preventDefault();
        this.openSearch();
      }

      // Close search with Escape
      if (e.key === "Escape" && this.state.isOpen) {
        this.closeSearch();
      }
    },

    /**
     * Perform search with AJAX
     */
    performSearch: function (query) {
      // Check cache first using normalized query
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

      // Abort previous request using jqXHR.abort()
      if (this.state.currentRequest) {
        this.state.currentRequest.abort();
      }

      // Generate unique request ID to prevent stale responses
      const requestId = ++this.state.lastRequestId;

      // Prepare AJAX request
      const ajaxData = {
        action: "primefit_product_search",
        query: query,
        nonce: window.primefitData?.nonce || "",
      };

      // Perform AJAX request
      this.state.currentRequest = $.ajax({
        url: window.primefitData?.ajaxUrl || "/wp-admin/admin-ajax.php",
        type: "POST",
        data: ajaxData,
        timeout: this.config.ajaxTimeout,
        success: (response) => {
          // Guard against stale responses
          if (
            requestId !== this.state.lastRequestId ||
            query !== this.state.currentQuery
          ) {
            return;
          }

          if (response.success && response.data) {
            // Cache results with pruning
            this.state.cache.set(query, {
              data: response.data,
              timestamp: Date.now(),
            });
            this.pruneCache();

            // Debug logging (gated)
            if (this.config.debugMode && response.data.debug) {
              console.log("Search debug info:", response.data.debug);
            }

            this.displayResults(response.data);
          } else {
            if (this.config.debugMode) {
              console.log("Search response:", response);
            }
            this.showNoResults();
          }
        },
        error: (xhr, status, error) => {
          // Guard against stale responses
          if (
            requestId !== this.state.lastRequestId ||
            query !== this.state.currentQuery
          ) {
            return;
          }

          if (status !== "abort") {
            if (this.config.debugMode) {
              console.error("Search error:", error);
            }
            this.showNoResults();
          }
        },
        complete: () => {
          this.hideLoading();
          this.state.currentRequest = null;
        },
      });
    },

    /**
     * Display search results
     */
    displayResults: function (data) {
      this.hideLoading();
      this.hideNoResults();

      if (!data.products || data.products.length === 0) {
        this.showNoResults();
        return;
      }

      // Show search results header
      this.elements.$searchResultsCount.parent().show();

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
    generateResultsHtml: function (products) {
      let html = '<div class="search-results-grid">';

      products.forEach((product) => {
        html += `
          <div class="search-result-item">
            <a href="${product.url}" class="search-result-link">
              <div class="search-result-image">
                ${
                  product.image
                    ? `<img src="${product.image}" alt="${product.title}" loading="lazy">`
                    : ""
                }
              </div>
              <div class="search-result-content">
                <h3 class="search-result-title">${product.title}</h3>
                <div class="search-result-price">${product.price}</div>
              </div>
            </a>
          </div>
        `;
      });

      html += "</div>";
      return html;
    },

    /**
     * Clear search results
     */
    clearResults: function () {
      this.elements.$searchResults.empty().hide();

      // Hide search results header when clearing
      this.elements.$searchResultsCount.parent().hide();

      // Only show "0 results" if user has actually searched
      if (
        this.state.currentQuery &&
        this.state.currentQuery.length >= this.config.minQueryLength
      ) {
        this.elements.$searchResultsCount.text("0 results");
      } else {
        this.elements.$searchResultsCount.text("");
      }
      this.hideLoading();
      this.hideNoResults();

      // Show trending searches when clearing results (only if there are trending searches)
      this.showTrendingSearches();
    },

    /**
     * Show loading state
     */
    showLoading: function () {
      this.elements.$searchLoading.show();
      this.elements.$searchResults.hide();
      this.elements.$searchNoResults.hide();
    },

    /**
     * Hide loading state
     */
    hideLoading: function () {
      this.elements.$searchLoading.hide();
    },

    /**
     * Show no results state
     */
    showNoResults: function () {
      // Show search results header
      this.elements.$searchResultsCount.parent().show();

      this.elements.$searchNoResults.show();
      this.elements.$searchResults.hide();
      this.elements.$searchResultsCount.text("0 results");
    },

    /**
     * Hide no results state
     */
    hideNoResults: function () {
      this.elements.$searchNoResults.hide();
    },

    /**
     * Navigate to full search results page
     */
    navigateToSearchResults: function () {
      // Use the raw input value for URL, not the normalized query
      const rawQuery = this.elements.$searchInput.val().trim();
      const searchUrl = `${window.location.origin}/?s=${encodeURIComponent(
        rawQuery
      )}&post_type=product`;
      window.location.href = searchUrl;
    },

    /**
     * Prevent body scroll (mobile only)
     */
    preventBodyScroll: function () {
      // Check if we're on mobile (using a mobile breakpoint, typically 768px or 1024px)
      const isMobile = window.innerWidth <= 1024;

      if (isMobile) {
        // Use the native scroll prevention from core.js for mobile
        if (typeof preventPageScroll === "function") {
          preventPageScroll().catch(() => {});
        }
      }
    },

    /**
     * Allow body scroll
     */
    allowBodyScroll: function () {
      // Check if we're on mobile
      const isMobile = window.innerWidth <= 1024;

      if (isMobile) {
        // Use the native scroll prevention from core.js for mobile
        if (typeof allowPageScroll === "function") {
          allowPageScroll();
        }
      }
    },

    /**
     * Debounce function for performance
     */
    debounce: function (func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    },

    /**
     * Prune cache using LRU strategy to prevent unbounded growth
     */
    pruneCache: function () {
      while (this.state.cache.size > this.config.maxCacheEntries) {
        // Remove oldest entry (first key in Map iteration order)
        const oldestKey = this.state.cache.keys().next().value;
        this.state.cache.delete(oldestKey);
      }
    },

    /**
     * Normalize search query for consistent caching
     */
    normalizeQuery: function (query) {
      return query.toLowerCase().trim();
    },

    /**
     * Load trending searches from server
     */
    loadTrendingSearches: function () {
      // Return cached data if available
      if (this.state.trendingSearchesCache) {
        this.displayTrendingSearches(this.state.trendingSearchesCache);
        return;
      }

      const ajaxData = {
        action: "primefit_get_trending_searches",
        nonce: window.primefitData?.nonce || "",
      };

      $.ajax({
        url: window.primefitData?.ajaxUrl || "/wp-admin/admin-ajax.php",
        type: "POST",
        data: ajaxData,
        timeout: 5000,
        success: (response) => {
          if (response.success && response.data.trending_searches) {
            // Cache the results
            this.state.trendingSearchesCache = response.data.trending_searches;
            this.state.trendingSearchesLoaded = true;
            this.displayTrendingSearches(response.data.trending_searches);
          }
        },
        error: (xhr, status, error) => {
          if (this.config.debugMode) {
            console.error("Failed to load trending searches:", error);
          }
          this.state.trendingSearchesLoaded = true; // Mark as loaded to prevent retries
        },
      });
    },

    /**
     * Display trending searches
     */
    displayTrendingSearches: function (trendingSearches) {
      if (!trendingSearches || trendingSearches.length === 0) {
        this.elements.$trendingSearches.hide();
        return;
      }

      // Unbind previous event handlers to prevent memory leaks
      this.elements.$trendingSearchesList.off("click.trending");

      // Limit to 8 trending searches
      const limitedSearches = trendingSearches.slice(0, 6);

      let html = "";
      limitedSearches.forEach((searchTerm) => {
        html += `<button class="trending-search-item" data-search-term="${this.escapeHtml(
          searchTerm
        )}">${this.escapeHtml(searchTerm)}</button>`;
      });

      this.elements.$trendingSearchesList.html(html);

      // Bind click events with namespace to prevent memory leaks
      this.elements.$trendingSearchesList.on(
        "click.trending",
        ".trending-search-item",
        (e) => {
          const searchTerm = $(e.target).data("search-term");
          this.elements.$searchInput.val(searchTerm);
          this.elements.$searchInput.trigger("input");
          this.performSearch(this.normalizeQuery(searchTerm));
        }
      );

      // Show the entire trending searches section (including header) only when there are results
      this.elements.$trendingSearches.show();
    },

    /**
     * Hide trending searches when user starts typing
     */
    hideTrendingSearches: function () {
      this.elements.$trendingSearches.hide();
      if (
        this.elements.$recentlyViewed &&
        this.elements.$recentlyViewed.length
      ) {
        this.elements.$recentlyViewed.hide();
      }
    },

    /**
     * Show trending searches when search input is empty
     */
    showTrendingSearches: function () {
      if (
        this.elements.$searchInput.val().trim().length === 0 &&
        this.state.trendingSearchesCache &&
        this.state.trendingSearchesCache.length > 0
      ) {
        this.elements.$trendingSearches.show();
      }

      if (this.elements.$searchInput.val().trim().length === 0) {
        this.displayRecentlyViewed(this.loadRecentlyViewed());
      }
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function (text) {
      const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      };
      return text.replace(/[&<>"']/g, function (m) {
        return map[m];
      });
    },

    /**
     * Load recently viewed items from localStorage
     */
    loadRecentlyViewed: function () {
      if (typeof Storage === "undefined") return [];
      try {
        const data = localStorage.getItem("primefit_recently_viewed");
        if (!data) return [];
        const parsed = JSON.parse(data);
        if (Array.isArray(parsed)) {
          return parsed.slice(0, 3);
        }
      } catch (_) {}
      return [];
    },

    /**
     * Display recently viewed thumbnails
     */
    displayRecentlyViewed: function (items) {
      if (!this.elements.$recentlyViewed || !this.elements.$recentlyViewedList)
        return;

      if (!items || items.length === 0) {
        this.elements.$recentlyViewed.hide();
        this.elements.$recentlyViewedList.empty();
        return;
      }

      let html = "";
      items.slice(0, 3).forEach((item) => {
        const title = this.escapeHtml(item.title || "");
        const url = item.url || "#";
        const img = item.image
          ? `<img src="${item.image}" alt="${title}">`
          : "";
        html += `<a class="recently-viewed-item" href="${url}" aria-label="${title}">${img}</a>`;
      });

      this.elements.$recentlyViewedList.html(html);
      this.elements.$recentlyViewed.show();
    },

    /**
     * Clear recently viewed
     */
    clearRecentlyViewed: function () {
      if (typeof Storage !== "undefined") {
        try {
          localStorage.removeItem("primefit_recently_viewed");
        } catch (_) {}
      }
      if (this.elements.$recentlyViewed) {
        this.elements.$recentlyViewed.hide();
        this.elements.$recentlyViewedList &&
          this.elements.$recentlyViewedList.empty();
      }
    },
  };

  /**
   * Initialize search functionality when DOM is ready
   */
  $(document).ready(function () {
    SearchManager.init();
  });

  // Expose SearchManager globally for debugging
  window.SearchManager = SearchManager;
})(jQuery);
