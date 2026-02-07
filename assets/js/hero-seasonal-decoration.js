/**
 * Hero Seasonal Decoration
 * Adds falling decorative particles to the hero section.
 * Enabled and variant set via Customizer (Hero Section). Reusable for Valentine's, Christmas, New Year's.
 */

(function() {
  'use strict';

  const config = typeof window.primefitSeasonalDecoration !== 'undefined' && window.primefitSeasonalDecoration.enabled
    ? window.primefitSeasonalDecoration
    : { enabled: false, variant: 'valentines' };

  if (!config.enabled) {
    return;
  }

  const PARTICLE_THEME = config.variant || 'valentines';

  const styles = `
    .hero {
      position: relative;
      overflow: hidden;
    }

    .hero-seasonal-canvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1;
    }
  `;

  function initSeasonalDecoration() {
    const heroSection = document.querySelector('.hero');

    if (!heroSection) {
      return;
    }

    const styleElement = document.createElement('style');
    styleElement.innerHTML = styles;
    document.head.appendChild(styleElement);

    function createParticles() {
      const isMobile = window.innerWidth <= 768;
      const COUNT = isMobile ? 25 : 55;
      const canvas = document.createElement('canvas');
      canvas.className = 'hero-seasonal-canvas';
      const ctx = canvas.getContext('2d');

      let tiltX = 0;
      let tiltY = 0;
      let targetTiltX = 0;
      let targetTiltY = 0;
      const TILT_SCALE = 0.05;
      const MAX_TILT = 3;

      let width = heroSection.clientWidth;
      let height = heroSection.clientHeight;
      let active = false;

      function onResize() {
        width = heroSection.clientWidth;
        height = heroSection.clientHeight;
        canvas.width = width;
        canvas.height = height;
        const wasActive = active;
        active = true;

        if (!wasActive && active) {
          requestAnimFrame(update);
        }
      }

      const Particle = function () {
        this.x = 0;
        this.y = 0;
        this.vy = 0;
        this.vx = 0;
        this.r = 0;
        this.o = 1;
        this.type = 'heart';
        this.rotation = 0;
        this.vRotation = 0;
        this.reset();
      };

      Particle.prototype.reset = function () {
        this.x = Math.random() * width;
        this.y = Math.random() * -height;
        this.vy = 0.25 + Math.random() * 0.6;
        this.vx = 0.4 - Math.random() * 0.8;
        this.r = 2 + Math.random() * 3;
        this.o = 0.4 + Math.random() * 0.5;
        this.type = Math.random() < 0.5 ? 'heart' : 'flower';
        this.rotation = Math.random() * Math.PI * 2;
        this.vRotation = this.type === 'heart' ? 0 : (Math.random() - 0.5) * 0.015;
      };

      function drawHeart(ctx, x, y, r) {
        ctx.save();
        ctx.translate(x, y);
        ctx.scale((r * 6) / 8, (r * 6) / 8);
        ctx.beginPath();
        ctx.moveTo(0, 2.5);
        ctx.bezierCurveTo(0, 0, -4, -2.5, -4, 1);
        ctx.bezierCurveTo(-4, 4, 0, 6, 0, 8);
        ctx.bezierCurveTo(0, 6, 4, 4, 4, 1);
        ctx.bezierCurveTo(4, -2.5, 0, 0, 0, 2.5);
        ctx.fill();
        ctx.restore();
      }

      function drawFlower(ctx, x, y, r, rotation) {
        const s = ((r * 6) / 8) * 2.8;
        const stroke = Math.max(0.15 * s, 0.8);
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(rotation);
        ctx.strokeStyle = '#000';
        ctx.lineWidth = stroke;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';

        // Rose bloom: layered petals with black outline (vector/tattoo style)
        const petal = function (cx, cy, w, h, angle) {
          ctx.save();
          ctx.translate(cx, cy);
          ctx.rotate(angle);
          ctx.beginPath();
          ctx.ellipse(0, 0, w, h, 0, 0, Math.PI * 2);
          ctx.fillStyle = '#c62828';
          ctx.fill();
          ctx.stroke();
          ctx.restore();
        };

        // Center tight petals
        for (let i = 0; i < 5; i++) {
          const a = (i / 5) * Math.PI * 2 - Math.PI / 2;
          petal(Math.cos(a) * s * 0.25, Math.sin(a) * s * 0.25, s * 0.35, s * 0.45, a);
        }
        // Middle ring
        for (let i = 0; i < 6; i++) {
          const a = (i / 6) * Math.PI * 2 - Math.PI / 2;
          petal(Math.cos(a) * s * 0.6, Math.sin(a) * s * 0.6, s * 0.5, s * 0.65, a);
        }
        // Outer wavy petals
        for (let i = 0; i < 7; i++) {
          const a = (i / 7) * Math.PI * 2 - Math.PI / 2;
          petal(Math.cos(a) * s * 0.95, Math.sin(a) * s * 0.95, s * 0.55, s * 0.75, a);
        }
        // Dark center
        ctx.beginPath();
        ctx.arc(0, 0, s * 0.2, 0, Math.PI * 2);
        ctx.fillStyle = '#8e0000';
        ctx.fill();
        ctx.stroke();

        // Black stem (below bloom)
        const stemTop = s * 0.9;
        const stemLen = s * 1.4;
        ctx.strokeStyle = '#000';
        ctx.lineWidth = stroke * 1.8;
        ctx.beginPath();
        ctx.moveTo(0, stemTop);
        ctx.lineTo(s * -0.15, stemTop + stemLen);
        ctx.stroke();

        // Green leaves with vein
        ctx.fillStyle = '#1b5e20';
        ctx.strokeStyle = '#000';
        ctx.lineWidth = stroke * 0.9;
        const leaf = function (lx, ly, la, lw, lh) {
          ctx.save();
          ctx.translate(lx, ly);
          ctx.rotate(la);
          ctx.beginPath();
          ctx.ellipse(0, 0, lw, lh, 0, 0, Math.PI * 2);
          ctx.fill();
          ctx.stroke();
          ctx.beginPath();
          ctx.moveTo(-lw * 0.3, 0);
          ctx.lineTo(lw * 0.3, 0);
          ctx.stroke();
          ctx.restore();
        };
        leaf(s * -0.2, stemTop + s * 0.4, -0.4, s * 0.35, s * 0.2);
        leaf(s * 0.05, stemTop + s * 0.85, 0.2, s * 0.4, s * 0.22);
        leaf(s * -0.25, stemTop + s * 1.15, -0.3, s * 0.3, s * 0.18);

        ctx.restore();
      }

      function drawParticle(ctx, particle) {
        ctx.globalAlpha = particle.o;
        if (PARTICLE_THEME === 'valentines') {
          if (particle.type === 'heart') {
            ctx.fillStyle = '#e91e63';
            drawHeart(ctx, particle.x, particle.y, particle.r);
          } else {
            drawFlower(ctx, particle.x, particle.y, particle.r, particle.rotation);
          }
        } else if (PARTICLE_THEME === 'christmas') {
          ctx.fillStyle = '#FFF';
          ctx.beginPath();
          ctx.arc(particle.x, particle.y, particle.r, 0, Math.PI * 2, false);
          ctx.closePath();
          ctx.fill();
        } else {
          ctx.fillStyle = '#FFF';
          ctx.beginPath();
          ctx.arc(particle.x, particle.y, particle.r, 0, Math.PI * 2, false);
          ctx.closePath();
          ctx.fill();
        }
        ctx.globalAlpha = 1;
      }

      canvas.style.position = 'absolute';
      canvas.style.left = canvas.style.top = '0';

      const particles = [];
      let particle;

      function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
      }

      function startOrientationTracking() {
        if (!('DeviceOrientationEvent' in window)) {
          return;
        }

        const handleOrientation = (event) => {
          const gamma = event.gamma || 0;
          const beta = event.beta || 0;
          targetTiltX = clamp(gamma * TILT_SCALE, -MAX_TILT, MAX_TILT);
          targetTiltY = clamp(beta * TILT_SCALE, -MAX_TILT, MAX_TILT);
        };

        const attachListener = () => {
          window.addEventListener('deviceorientation', handleOrientation, true);
        };

        if (typeof DeviceOrientationEvent.requestPermission === 'function') {
          const requestPermission = () => {
            DeviceOrientationEvent.requestPermission()
              .then((res) => {
                if (res === 'granted') {
                  attachListener();
                }
              })
              .catch(() => {});
          };
          heroSection.addEventListener('click', requestPermission, { once: true });
          heroSection.addEventListener('touchend', requestPermission, { once: true });
        } else {
          attachListener();
        }
      }

      for (let i = 0; i < COUNT; i++) {
        particle = new Particle();
        particle.reset();
        particles.push(particle);
      }

      function update() {
        ctx.clearRect(0, 0, width, height);

        if (!active) {
          return;
        }

        tiltX = tiltX * 0.75 + targetTiltX * 0.25;
        tiltY = tiltY * 0.75 + targetTiltY * 0.25;

        for (let i = 0; i < particles.length; i++) {
          particle = particles[i];
          particle.y += particle.vy + tiltY;
          particle.x += particle.vx + tiltX;
          if (particle.type === 'flower') {
            particle.rotation += particle.vRotation;
          }

          drawParticle(ctx, particle);

          if (particle.y > height) {
            particle.reset();
          }
        }

        requestAnimFrame(update);
      }

      window.requestAnimFrame = (function () {
        return (
          window.requestAnimationFrame ||
          window.webkitRequestAnimationFrame ||
          window.mozRequestAnimationFrame ||
          function (callback) {
            window.setTimeout(callback, 1000 / 60);
          }
        );
      })();

      onResize();
      window.addEventListener('resize', onResize, false);
      heroSection.appendChild(canvas);
      startOrientationTracking();

      active = true;
      requestAnimFrame(update);
    }

    setTimeout(() => {
      createParticles();
    }, 500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSeasonalDecoration);
  } else {
    initSeasonalDecoration();
  }
})();
