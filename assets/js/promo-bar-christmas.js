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

        for (let i = 0; i < COUNT; i++) {
          snowflake = snowflakes[i];
          snowflake.y += snowflake.vy;
          snowflake.x += snowflake.vx;

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
