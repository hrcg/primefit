/**
 * PrimeFit Theme - Payment Summary JavaScript
 * Interactive functionality for payment summary page
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Ensure this script only runs on order received pages
  const urlParams = new URLSearchParams(window.location.search);
  const isOrderReceivedPage =
    // Check for order received page indicators
    document.body.classList.contains("woocommerce-order-received") ||
    document.querySelector(".woocommerce-order-received") ||
    document.querySelector(".order-received") ||
    document.querySelector("#order-received") ||
    // Check for URL parameters that indicate order received page
    (urlParams.get("key") && urlParams.get("order")) ||
    // Check for order-received endpoint in URL path
    window.location.pathname.includes("/order-received/");

  if (!isOrderReceivedPage) {
    // PrimeFit: payment-summary.js skipped - not on order received page
    return;
  }

  /**
   * Payment Summary functionality
   */
  const PaymentSummary = {
    // Store references for cleanup
    observers: [],
    eventHandlers: new Map(),

    /**
     * Initialize payment summary features
     */
    init: function () {
      this.bindEvents();
      this.initAnimations();
      this.initPrintFunctionality();

      // Add cleanup on page unload to prevent memory leaks
      window.addEventListener("beforeunload", () => {
        this.cleanup();
      });
    },

    /**
     * Bind event handlers with stored references for cleanup
     */
    bindEvents: function () {
      // Print order functionality
      const printHandler = this.printOrder.bind(this);
      $(document).on("click", ".print-order-btn", printHandler);
      this.eventHandlers.set("print-order-btn", printHandler);

      // Copy order number functionality
      const copyHandler = this.copyOrderNumber.bind(this);
      $(document).on("click", ".copy-order-number", copyHandler);
      this.eventHandlers.set("copy-order-number", copyHandler);

      // Share order functionality
      const shareHandler = this.shareOrder.bind(this);
      $(document).on("click", ".share-order-btn", shareHandler);
      this.eventHandlers.set("share-order-btn", shareHandler);
    },

    /**
     * Cleanup method to prevent memory leaks
     */
    cleanup: function () {
      // Disconnect all observers
      this.observers.forEach(observer => {
        observer.disconnect();
      });
      this.observers.length = 0;

      // Remove all event listeners
      this.eventHandlers.forEach((handler, selector) => {
        $(document).off("click", selector, handler);
      });
      this.eventHandlers.clear();
    },

    /**
     * Initialize animations
     */
    initAnimations: function () {
      // Animate cards on scroll
      if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                entry.target.classList.add("animate-in");
              }
            });
          },
          {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px",
          }
        );

        // Store observer for cleanup
        this.observers.push(observer);

        document.querySelectorAll(".payment-summary-card").forEach((card) => {
          observer.observe(card);
        });
      }
    },

    /**
     * Initialize print functionality
     */
    initPrintFunctionality: function () {
      // Add print button if not exists
      if (!document.querySelector(".print-order-btn")) {
        const printBtn = document.createElement("button");
        printBtn.className = "print-order-btn button button--secondary";
        printBtn.innerHTML =
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 6,2 18,2 18,9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Print Order';

        const actionsContainer = document.querySelector(
          ".payment-summary-actions"
        );
        if (actionsContainer) {
          actionsContainer.appendChild(printBtn);
        }
      }
    },

    /**
     * Print order functionality
     */
    printOrder: function (e) {
      e.preventDefault();

      // Create print window
      const printWindow = window.open("", "_blank");
      const orderContent = document.querySelector(
        ".payment-summary-container"
      ).innerHTML;

      printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Order Summary - Print</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .payment-summary-container { max-width: none; padding: 0; }
                        .payment-summary-header { text-align: center; margin-bottom: 30px; }
                        .payment-summary-icon { display: none; }
                        .payment-summary-actions { display: none; }
                        .payment-summary-card { break-inside: avoid; margin-bottom: 20px; }
                        .card-header { background: #f5f5f5; padding: 15px; }
                        .card-content { padding: 15px; }
                        .order-item { border: 1px solid #ddd; margin-bottom: 10px; padding: 10px; }
                        .summary-line { border-bottom: 1px solid #eee; padding: 8px 0; }
                        .summary-line.total { border-top: 2px solid #0d0d0d; font-weight: bold; }
                        @media print {
                            body { margin: 0; }
                            .payment-summary-card { box-shadow: none; border: 1px solid #0d0d0d; }
                        }
                    </style>
                </head>
                <body>
                    ${orderContent}
                </body>
                </html>
            `);

      printWindow.document.close();
      printWindow.focus();

      // Wait for content to load then print
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 500);
    },

    /**
     * Copy order number to clipboard
     */
    copyOrderNumber: function (e) {
      e.preventDefault();

      const orderNumber = document.querySelector(".order-number")?.textContent;
      if (!orderNumber) return;

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(orderNumber).then(() => {
          PaymentSummary.showNotification("Order number copied to clipboard!");
        });
      } else {
        // Fallback for older browsers
        const textArea = document.createElement("textarea");
        textArea.value = orderNumber;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        PaymentSummary.showNotification("Order number copied to clipboard!");
      }
    },

    /**
     * Share order functionality
     */
    shareOrder: function (e) {
      e.preventDefault();

      const orderNumber = document.querySelector(".order-number")?.textContent;
      const orderTotal = document.querySelector(
        ".summary-line.total .summary-value"
      )?.textContent;

      if (!orderNumber || !orderTotal) return;

      const shareText = `I just placed an order #${orderNumber} for ${orderTotal} on ${window.location.hostname}`;
      const shareUrl = window.location.href;

      if (navigator.share) {
        navigator.share({
          title: "My Order Summary",
          text: shareText,
          url: shareUrl,
        });
      } else {
        // Fallback - copy to clipboard
        const shareContent = `${shareText}\n\nView details: ${shareUrl}`;

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(shareContent).then(() => {
            PaymentSummary.showNotification(
              "Order details copied to clipboard!"
            );
          });
        } else {
          const textArea = document.createElement("textarea");
          textArea.value = shareContent;
          document.body.appendChild(textArea);
          textArea.select();
          document.execCommand("copy");
          document.body.removeChild(textArea);
          PaymentSummary.showNotification("Order details copied to clipboard!");
        }
      }
    },

    /**
     * Show notification
     */
    showNotification: function (message) {
      // Remove existing notification
      const existingNotification = document.querySelector(
        ".payment-summary-notification"
      );
      if (existingNotification) {
        existingNotification.remove();
      }

      // Create notification
      const notification = document.createElement("div");
      notification.className = "payment-summary-notification";
      notification.textContent = message;
      notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--payment-summary-success, #44ff44);
                color: #0d0d0d;
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: 600;
                z-index: 10000;
                animation: slideInRight 0.3s ease;
            `;

      document.body.appendChild(notification);

      // Remove after 3 seconds
      setTimeout(() => {
        notification.style.animation = "slideOutRight 0.3s ease";
        setTimeout(() => {
          notification.remove();
        }, 300);
      }, 3000);
    },
  };

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    // Only initialize on payment summary pages
    if (document.querySelector(".payment-summary-container")) {
      PaymentSummary.init();
    }
  });

  /**
   * Add CSS animations
   */
  const style = document.createElement("style");
  style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .payment-summary-card {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease;
        }
        
        .payment-summary-card.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .payment-summary-card:nth-child(1) { transition-delay: 0.1s; }
        .payment-summary-card:nth-child(2) { transition-delay: 0.2s; }
        .payment-summary-card:nth-child(3) { transition-delay: 0.3s; }
        .payment-summary-card:nth-child(4) { transition-delay: 0.4s; }
        .payment-summary-card:nth-child(5) { transition-delay: 0.5s; }
    `;
  document.head.appendChild(style);
})(jQuery);
