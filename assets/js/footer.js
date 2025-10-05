/**
 * Footer Mobile Toggle Functionality
 *
 * Handles collapsible footer columns on mobile devices
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Initialize footer toggles
   */
  function initFooterToggles() {
    // Only initialize on mobile devices
    if (window.innerWidth > 768) {
      return;
    }

    const footerColumns = document.querySelectorAll(".footer-column");

    footerColumns.forEach(function (column) {
      const toggle = column.querySelector(".footer-toggle");
      const links = column.querySelector(".footer-links");
      const heading = column.querySelector(".footer-heading");

      // Skip if no toggle or links found
      if (!toggle || !links || !heading) {
        return;
      }

      // Initialize collapsed state
      column.classList.add("collapsed");
      toggle.setAttribute("aria-expanded", "false");

      // Add click event listener to the entire heading
      heading.addEventListener("click", function (e) {
        e.preventDefault();

        const isExpanded = column.classList.contains("expanded");

        if (isExpanded) {
          // Collapse
          column.classList.remove("expanded");
          column.classList.add("collapsed");
          toggle.setAttribute("aria-expanded", "false");
        } else {
          // Expand
          column.classList.remove("collapsed");
          column.classList.add("expanded");
          toggle.setAttribute("aria-expanded", "true");
        }
      });
    });
  }

  /**
   * Handle window resize
   */
  function handleResize() {
    const footerColumns = document.querySelectorAll(".footer-column");

    if (window.innerWidth > 768) {
      // Desktop: remove all toggle states and show all links
      footerColumns.forEach(function (column) {
        column.classList.remove("collapsed", "expanded");
        const toggle = column.querySelector(".footer-toggle");
        if (toggle) {
          toggle.setAttribute("aria-expanded", "false");
        }
      });
    } else {
      // Mobile: reinitialize toggles
      initFooterToggles();
    }
  }

  /**
   * Initialize when DOM is ready
   */
  function init() {
    // Initialize on page load
    initFooterToggles();

    // Handle window resize
    let resizeTimeout;
    window.addEventListener("resize", function () {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(handleResize, 250);
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(jQuery);
