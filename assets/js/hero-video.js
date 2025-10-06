/**
 * PrimeFit Theme - Hero Video Module
 * Hero video background functionality
 *
 * @package PrimeFit
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Hero Video Background Handler
  class HeroVideoHandler {
    constructor() {
      this.resizeTimeout = null;
      this.loadHandler = null;
      this.resizeHandler = null;
      this.videoEventHandlers = new Map(); // Store video event handlers for cleanup
      this.init();
    }

    init() {
      // Wait for page to be fully loaded before starting video loading
      if (document.readyState === "complete") {
        this.handleHeroVideos();
        this.ensureFallbackVisibility();
      } else {
        this.loadHandler = () => {
          this.handleHeroVideos();
          this.ensureFallbackVisibility();
        };
        window.addEventListener("load", this.loadHandler);
      }

      // Handle window resize (device orientation changes, etc.)
      this.resizeHandler = () => {
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
          this.handleHeroVideos();
          this.ensureFallbackVisibility();
        }, 250);
      };
      window.addEventListener("resize", this.resizeHandler);

      // Add cleanup on page unload to prevent memory leaks
      window.addEventListener("beforeunload", () => {
        this.cleanup();
      });
    }

    /**
     * Cleanup method to prevent memory leaks
     */
    cleanup() {
      // Remove load event listener
      if (this.loadHandler) {
        window.removeEventListener("load", this.loadHandler);
        this.loadHandler = null;
      }

      // Remove resize event listener and clear timeout
      if (this.resizeHandler) {
        window.removeEventListener("resize", this.resizeHandler);
        this.resizeHandler = null;
      }

      if (this.resizeTimeout) {
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = null;
      }

      // Remove video event listeners
      this.videoEventHandlers.forEach((handlers, videoElement) => {
        handlers.forEach((handler, event) => {
          videoElement.removeEventListener(event, handler);
        });
      });
      this.videoEventHandlers.clear();
    }

    handleHeroVideos() {
      const $heroVideos = $(".hero-video");

      if ($heroVideos.length === 0) return;

      $heroVideos.each((index, video) => {
        const $video = $(video);
        const videoElement = video;

        // Check if this video should be active on current device
        if (!this.shouldVideoBeActive($video)) {
          return; // Skip this video
        }

        // Set up video event listeners
        this.setupVideoEvents($video, videoElement);

        // Start loading the video after a small delay to ensure page is fully rendered
        setTimeout(() => {
          this.loadVideo($video, videoElement);
        }, 100);
      });
    }

    shouldVideoBeActive($video) {
      // Check if video is visible (not hidden by CSS)
      const isVisible = $video.is(":visible");

      // Additional check: ensure the video element is not display: none
      const videoElement = $video[0];
      const computedStyle = window.getComputedStyle(videoElement);
      const isDisplayed = computedStyle.display !== "none";

      return isVisible && isDisplayed;
    }

    setupVideoEvents($video, videoElement) {
      // Store event handlers for cleanup
      if (!this.videoEventHandlers.has(videoElement)) {
        this.videoEventHandlers.set(videoElement, new Map());
      }

      const handlers = this.videoEventHandlers.get(videoElement);

      // When video can play through
      const canPlayHandler = () => {
        this.onVideoReady($video, videoElement);
      };
      videoElement.addEventListener("canplaythrough", canPlayHandler);
      handlers.set("canplaythrough", canPlayHandler);

      // When video starts playing
      const playingHandler = () => {
        this.onVideoPlaying($video, videoElement);
      };
      videoElement.addEventListener("playing", playingHandler);
      handlers.set("playing", playingHandler);

      // Handle video errors
      const errorHandler = () => {
        this.onVideoError($video, videoElement);
      };
      videoElement.addEventListener("error", errorHandler);
      handlers.set("error", errorHandler);

      // Handle video loading
      const loadStartHandler = () => {
        this.onVideoLoadStart($video, videoElement);
      };
      videoElement.addEventListener("loadstart", loadStartHandler);
      handlers.set("loadstart", loadStartHandler);
    }

    loadVideo($video, videoElement) {
      // Set video source and start loading
      const sources = videoElement.querySelectorAll("source");
      if (sources.length > 0) {
        // Let the browser choose the best source
        videoElement.load();
      }
    }

    onVideoLoadStart($video, videoElement) {
      // Video is starting to load
      $video.addClass("loading");
    }

    onVideoReady($video, videoElement) {
      // Video is ready to play
      $video.addClass("loaded");

      // Try to play the video
      const playPromise = videoElement.play();

      if (playPromise !== undefined) {
        playPromise
          .then(() => {
            this.onVideoPlaying($video, videoElement);
          })
          .catch((error) => {
            this.onVideoError($video, videoElement);
          });
      }
    }

    onVideoPlaying($video, videoElement) {
      // Video is playing successfully

      // Only hide fallback image if this video is actually visible
      if (this.shouldVideoBeActive($video)) {
        // Hide fallback image with smooth transition
        const $fallbackImage = $video
          .closest(".hero-media")
          .find(".hero-fallback-image");
        $fallbackImage.css("opacity", "0");

        // Also hide the fallback image using CSS class
        $video.closest(".hero-media").addClass("video-playing");
      }
    }

    onVideoError($video, videoElement) {
      // Video failed to load or play

      // Ensure fallback image is visible
      const $fallbackImage = $video
        .closest(".hero-media")
        .find(".hero-fallback-image");
      $fallbackImage.css("opacity", "1");

      // Hide the video
      $video.css("opacity", "0");

      // Remove video-playing class
      $video.closest(".hero-media").removeClass("video-playing");
    }

    ensureFallbackVisibility() {
      // Check if any videos are actually active/visible
      const $activeVideos = $(".hero-video").filter((index, video) => {
        return this.shouldVideoBeActive($(video));
      });

      // If no videos are active, ensure fallback images are visible
      if ($activeVideos.length === 0) {
        $(".hero-media").each((index, media) => {
          const $media = $(media);
          const $fallbackImage = $media.find(".hero-fallback-image");

          // Ensure fallback image is visible
          $fallbackImage.css("opacity", "1");
          $media.removeClass("video-playing");
        });
      }
    }
  }

  // Initialize hero video handler
  if ($(".hero-video").length > 0) {
    new HeroVideoHandler();
  }
})(jQuery);
