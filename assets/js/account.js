/**
 * PrimeFit Account Page JavaScript
 * Handles login form interactions and password visibility toggle
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Initialize account page functionality
  function initAccountPage() {
    initPasswordToggle();
    initFormValidation();
    initFormSwitching();
  }

  // Password visibility toggle
  function initPasswordToggle() {
    console.log("Initializing password toggle..."); // Debug log

    $(".password-toggle").on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $button = $(this);
      let $input = $button.siblings(
        "input[type='password'], input[type='text']"
      );

      // If no sibling input found, try finding by parent container
      if ($input.length === 0) {
        $input = $button
          .closest(".password-input-wrapper")
          .find("input[type='password'], input[type='text']");
      }

      // If still no input found, try finding by ID
      if ($input.length === 0) {
        $input = $("#password");
      }

      const $svg = $button.find("svg");

      console.log(
        "Password toggle clicked",
        $button.length,
        $input.length,
        $svg.length,
        "Current input type:",
        $input.attr("type"),
        "Input value length:",
        $input.val().length
      ); // Debug log

      if ($input.length === 0) {
        console.error("Password input not found");
        return;
      }

      if ($svg.length === 0) {
        console.error("SVG icon not found");
        return;
      }

      // Store current value to prevent loss
      const currentValue = $input.val();

      // Toggle the input type
      if ($input.attr("type") === "password") {
        $input.attr("type", "text");
        $input.val(currentValue); // Ensure value is preserved
        $svg.html(
          '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>'
        );
        $button.attr("aria-label", "Hide password");
        console.log(
          "Password shown - input type changed to:",
          $input.attr("type"),
          "Value:",
          $input.val()
        );
      } else {
        $input.attr("type", "password");
        $input.val(currentValue); // Ensure value is preserved
        $svg.html(
          '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>'
        );
        $button.attr("aria-label", "Show password");
        console.log(
          "Password hidden - input type changed to:",
          $input.attr("type"),
          "Value:",
          $input.val()
        );
      }
    });

    console.log(
      "Password toggle initialized for",
      $(".password-toggle").length,
      "elements"
    );
  }

  // Form validation
  function initFormValidation() {
    $(".login-form").on("submit", function (e) {
      const $form = $(this);
      const $email = $form.find('input[name="username"]');
      const $password = $form.find('input[name="password"]');

      let isValid = true;

      // Clear previous errors
      $form.find(".error-message").remove();
      $form.find(".form-field").removeClass("error");

      // Validate email
      if (!$email.val() || !isValidEmail($email.val())) {
        showFieldError($email, "Please enter a valid email address");
        isValid = false;
      }

      // Validate password
      if (!$password.val()) {
        showFieldError($password, "Please enter your password");
        isValid = false;
      }

      if (!isValid) {
        e.preventDefault();
      }
    });
  }

  // Show field error
  function showFieldError($field, message) {
    const $formField = $field.closest(".form-field");
    $formField.addClass("error");

    if (!$formField.find(".error-message").length) {
      $formField.append('<div class="error-message">' + message + "</div>");
    }
  }

  // Email validation
  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Form switching functionality
  function initFormSwitching() {
    console.log("Initializing form switching...");

    // Show register form
    $("#show-register-form").on("click", function (e) {
      e.preventDefault();
      console.log("Switching to register form");
      switchForm("register");
    });

    // Show login form
    $("#show-login-form").on("click", function (e) {
      e.preventDefault();
      console.log("Switching to login form");
      switchForm("login");
    });
  }

  // Switch between forms with fade animation
  function switchForm(formType) {
    const $loginForm = $("#login-form");
    const $registerForm = $("#register-form");

    if (formType === "register") {
      // Fade out login form
      $loginForm.removeClass("active");

      // Wait for fade out, then fade in register form
      setTimeout(function () {
        $registerForm.addClass("active");
      }, 150);
    } else if (formType === "login") {
      // Fade out register form
      $registerForm.removeClass("active");

      // Wait for fade out, then fade in login form
      setTimeout(function () {
        $loginForm.addClass("active");
      }, 150);
    }
  }

  // Initialize when document is ready
  $(document).ready(function () {
    console.log("jQuery loaded:", typeof $ !== "undefined");
    console.log("Document ready, initializing account page...");
    initAccountPage();
  });

  // Fallback vanilla JavaScript approach
  document.addEventListener("DOMContentLoaded", function () {
    console.log("Vanilla JS fallback initialized");

    const passwordToggles = document.querySelectorAll(".password-toggle");
    console.log("Found", passwordToggles.length, "password toggle buttons");

    passwordToggles.forEach(function (button) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();

        console.log("Vanilla JS password toggle clicked");

        // Find the password input
        let input = button.parentElement.querySelector(
          'input[type="password"], input[type="text"]'
        );
        if (!input) {
          input = document.getElementById("password");
        }

        if (!input) {
          console.error("Password input not found in vanilla JS");
          return;
        }

        const svg = button.querySelector("svg");
        if (!svg) {
          console.error("SVG not found in vanilla JS");
          return;
        }

        console.log(
          "Current input type:",
          input.type,
          "Value length:",
          input.value.length
        );

        // Store current value to prevent loss
        const currentValue = input.value;

        if (input.type === "password") {
          input.type = "text";
          input.value = currentValue; // Ensure value is preserved
          svg.innerHTML =
            '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
          button.setAttribute("aria-label", "Hide password");
          console.log(
            "Password shown via vanilla JS - input type changed to:",
            input.type,
            "Value:",
            input.value
          );
        } else {
          input.type = "password";
          input.value = currentValue; // Ensure value is preserved
          svg.innerHTML =
            '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
          button.setAttribute("aria-label", "Show password");
          console.log(
            "Password hidden via vanilla JS - input type changed to:",
            input.type,
            "Value:",
            input.value
          );
        }
      });
    });

    // Vanilla JS form switching
    const showRegisterBtn = document.getElementById("show-register-form");
    const showLoginBtn = document.getElementById("show-login-form");

    if (showRegisterBtn) {
      showRegisterBtn.addEventListener("click", function (e) {
        e.preventDefault();
        console.log("Vanilla JS: Switching to register form");
        switchFormVanilla("register");
      });
    }

    if (showLoginBtn) {
      showLoginBtn.addEventListener("click", function (e) {
        e.preventDefault();
        console.log("Vanilla JS: Switching to login form");
        switchFormVanilla("login");
      });
    }
  });

  // Vanilla JS form switching function
  function switchFormVanilla(formType) {
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("register-form");

    if (formType === "register") {
      // Fade out login form
      if (loginForm) loginForm.classList.remove("active");

      // Wait for fade out, then fade in register form
      setTimeout(function () {
        if (registerForm) registerForm.classList.add("active");
      }, 150);
    } else if (formType === "login") {
      // Fade out register form
      if (registerForm) registerForm.classList.remove("active");

      // Wait for fade out, then fade in login form
      setTimeout(function () {
        if (loginForm) loginForm.classList.add("active");
      }, 150);
    }
  }
})(jQuery);
