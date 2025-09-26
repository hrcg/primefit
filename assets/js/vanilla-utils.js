/**
 * PrimeFit Theme - Vanilla JavaScript Utilities
 * Lightweight replacements for common jQuery functionality
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Lightweight DOM utilities to reduce jQuery dependency
const VanillaUtils = {
  // Simple query selector with fallback
  $(selector) {
    if (typeof selector === "string") {
      return document.querySelector(selector);
    }
    return selector;
  },

  // Query selector all
  $$(selector) {
    return document.querySelectorAll(selector);
  },

  // Add event listener with delegation support
  on(element, event, selector, handler) {
    if (typeof selector === "function") {
      // Direct event binding
      element.addEventListener(event, selector);
    } else {
      // Event delegation
      element.addEventListener(event, (e) => {
        if (e.target.matches(selector)) {
          handler.call(e.target, e);
        }
      });
    }
  },

  // Remove event listener
  off(element, event, handler) {
    element.removeEventListener(event, handler);
  },

  // Add class
  addClass(element, className) {
    if (element) {
      element.classList.add(className);
    }
  },

  // Remove class
  removeClass(element, className) {
    if (element) {
      element.classList.remove(className);
    }
  },

  // Toggle class
  toggleClass(element, className) {
    if (element) {
      element.classList.toggle(className);
    }
  },

  // Check if element has class
  hasClass(element, className) {
    return element ? element.classList.contains(className) : false;
  },

  // Set attribute
  attr(element, name, value) {
    if (element) {
      if (value !== undefined) {
        element.setAttribute(name, value);
      } else {
        return element.getAttribute(name);
      }
    }
  },

  // Set data attribute
  data(element, name, value) {
    if (element) {
      if (value !== undefined) {
        element.dataset[name] = value;
      } else {
        return element.dataset[name];
      }
    }
  },

  // Get/set text content
  text(element, value) {
    if (element) {
      if (value !== undefined) {
        element.textContent = value;
      } else {
        return element.textContent;
      }
    }
  },

  // Get/set HTML content
  html(element, value) {
    if (element) {
      if (value !== undefined) {
        element.innerHTML = value;
      } else {
        return element.innerHTML;
      }
    }
  },

  // Get/set value
  val(element, value) {
    if (element) {
      if (value !== undefined) {
        element.value = value;
      } else {
        return element.value;
      }
    }
  },

  // Show element
  show(element) {
    if (element) {
      element.style.display = "";
    }
  },

  // Hide element
  hide(element) {
    if (element) {
      element.style.display = "none";
    }
  },

  // Fade in (simple implementation)
  fadeIn(element, duration = 300) {
    if (element) {
      // Use transform and opacity for better performance (no layout reflow)
      element.style.opacity = "0";
      element.style.display = "";
      element.style.transition = `opacity ${duration}ms ease`;

      // Use requestAnimationFrame to ensure display change is applied
      requestAnimationFrame(() => {
        element.style.opacity = "1";
      });
    }
  },

  // Fade out (simple implementation)
  fadeOut(element, duration = 300) {
    if (element) {
      element.style.transition = `opacity ${duration}ms ease`;
      element.style.opacity = "0";

      // Use requestAnimationFrame for better timing
      requestAnimationFrame(() => {
        setTimeout(() => {
          element.style.display = "none";
        }, duration);
      });
    }
  },

  // Find parent element matching selector
  closest(element, selector) {
    if (element) {
      return element.closest(selector);
    }
    return null;
  },

  // Find child elements
  find(element, selector) {
    if (element) {
      return element.querySelectorAll(selector);
    }
    return [];
  },

  // Get parent element
  parent(element) {
    return element ? element.parentElement : null;
  },

  // Get siblings
  siblings(element) {
    if (element && element.parentElement) {
      return Array.from(element.parentElement.children).filter(
        (child) => child !== element
      );
    }
    return [];
  },

  // Create element
  create(tagName, attributes = {}) {
    const element = document.createElement(tagName);
    Object.keys(attributes).forEach((key) => {
      if (key === "text") {
        element.textContent = attributes[key];
      } else if (key === "html") {
        element.innerHTML = attributes[key];
      } else {
        element.setAttribute(key, attributes[key]);
      }
    });
    return element;
  },

  // Append element
  append(parent, child) {
    if (parent && child) {
      parent.appendChild(child);
    }
  },

  // Remove element
  remove(element) {
    if (element && element.parentElement) {
      element.parentElement.removeChild(element);
    }
  },

  // Check if element exists
  exists(element) {
    return element !== null && element !== undefined;
  },

  // Get element dimensions (cached to avoid multiple reflows)
  dimensions(element) {
    if (element) {
      // Use requestAnimationFrame to batch measurements
      return new Promise((resolve) => {
        requestAnimationFrame(() => {
          const rect = element.getBoundingClientRect();
          resolve({
            width: rect.width,
            height: rect.height,
            top: rect.top,
            left: rect.left,
            bottom: rect.bottom,
            right: rect.right,
          });
        });
      });
    }
    return Promise.resolve(null);
  },

  // Debounce function
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
  },

  // Throttle function
  throttle(func, limit) {
    let inThrottle;
    return function () {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => (inThrottle = false), limit);
      }
    };
  },
};

// Make VanillaUtils available globally
window.VanillaUtils = VanillaUtils;
