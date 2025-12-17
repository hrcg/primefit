/**
 * Snow Animation for Hero Section
 * Adds falling snow animation to the hero section
 */

(function() {
  'use strict';

  // Snow animation styles
  const styles = `
    .hero {
      position: relative;
      overflow: hidden;
    }

    .hero-snow-canvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1;
    }
  `;

  function initSnowAnimation() {
    const heroSection = document.querySelector('.hero');
    
    if (!heroSection) {
      return;
    }

    // Add styles
    const styleElement = document.createElement('style');
    styleElement.innerHTML = styles;
    document.head.appendChild(styleElement);

    // Create snow animation in hero
    function createSnow() {
      // Reduce snowflake count on mobile for better performance
      const isMobile = window.innerWidth <= 768;
      const COUNT = isMobile ? 150 : 300;
      const canvas = document.createElement('canvas');
      canvas.className = 'hero-snow-canvas';
      const ctx = canvas.getContext('2d');
      
      // Device tilt influence
      let tiltX = 0;
      let tiltY = 0;
      let targetTiltX = 0;
      let targetTiltY = 0;
      const TILT_SCALE = 0.05; // stronger response to tilt
      const MAX_TILT = 3; // allow more noticeable drift

      let width = heroSection.clientWidth;
      let height = heroSection.clientHeight;
      let active = false;

      function onResize() {
        width = heroSection.clientWidth;
        height = heroSection.clientHeight;
        canvas.width = width;
        canvas.height = height;
        ctx.fillStyle = '#FFF';
        
        const wasActive = active;
        active = true; // Always active on all screen sizes
        
        if (!wasActive && active) {
          requestAnimFrame(update);
        }
      }

      const Snowflake = function () {
        this.x = 0;
        this.y = 0;
        this.vy = 0;
        this.vx = 0;
        this.r = 0;
        this.reset();
      };

      Snowflake.prototype.reset = function() {
        this.x = Math.random() * width;
        this.y = Math.random() * -height;
        this.vy = 0.3 + Math.random() * 0.7; // Slower fall speed (0.3 to 1.0 pixels per frame)
        this.vx = 0.5 - Math.random();
        this.r = 1 + Math.random() * 2;
        this.o = 0.5 + Math.random() * 0.5;
      };

      canvas.style.position = 'absolute';
      canvas.style.left = canvas.style.top = '0';

      const snowflakes = [];
      let snowflake;

      function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
      }

      function startOrientationTracking() {
        if (!('DeviceOrientationEvent' in window)) {
          return;
        }

        const handleOrientation = (event) => {
          const gamma = event.gamma || 0; // left/right tilt
          const beta = event.beta || 0;   // front/back tilt
          targetTiltX = clamp(gamma * TILT_SCALE, -MAX_TILT, MAX_TILT);
          targetTiltY = clamp(beta * TILT_SCALE, -MAX_TILT, MAX_TILT);
        };

        const attachListener = () => {
          window.addEventListener('deviceorientation', handleOrientation, true);
        };

        // iOS requires a user gesture before requesting permission
        if (typeof DeviceOrientationEvent.requestPermission === 'function') {
          const requestPermission = () => {
            DeviceOrientationEvent.requestPermission()
              .then((res) => {
                if (res === 'granted') {
                  attachListener();
                }
              })
              .catch(() => {
                // ignore if permission is denied
              });
          };

          heroSection.addEventListener('click', requestPermission, { once: true });
          heroSection.addEventListener('touchend', requestPermission, { once: true });
        } else {
          attachListener();
        }
      }

      for (let i = 0; i < COUNT; i++) {
        snowflake = new Snowflake();
        snowflake.reset();
        snowflakes.push(snowflake);
      }

      function update() {
        ctx.clearRect(0, 0, width, height);
        
        if (!active) {
          return;
        }

        // smooth tilt changes to avoid jitter
        tiltX = tiltX * 0.75 + targetTiltX * 0.25;
        tiltY = tiltY * 0.75 + targetTiltY * 0.25;

        for (let i = 0; i < COUNT; i++) {
          snowflake = snowflakes[i];
          snowflake.y += snowflake.vy + tiltY;
          snowflake.x += snowflake.vx + tiltX;

          ctx.globalAlpha = snowflake.o;
          ctx.beginPath();
          ctx.arc(snowflake.x, snowflake.y, snowflake.r, 0, Math.PI * 2, false);
          ctx.closePath();
          ctx.fill();

          if (snowflake.y > height) {
            snowflake.reset();
          }
        }

        requestAnimFrame(update);
      }

      // shim layer with setTimeout fallback
      window.requestAnimFrame = (function(){
        return  window.requestAnimationFrame       ||
                window.webkitRequestAnimationFrame ||
                window.mozRequestAnimationFrame    ||
                function( callback ){
                  window.setTimeout(callback, 1000 / 60);
                };
      })();

      onResize();
      window.addEventListener('resize', onResize, false);
      heroSection.appendChild(canvas);
      startOrientationTracking();
      
      // Start animation immediately
      active = true;
      requestAnimFrame(update);
    }

    // Initialize after a short delay to ensure hero section is fully rendered
    setTimeout(() => {
      createSnow();
    }, 500);
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSnowAnimation);
  } else {
    initSnowAnimation();
  }

})();
