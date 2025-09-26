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
      this.init();
    }

    init() {
      // Wait for page to be fully loaded before starting video loading
      if (document.readyState === "complete") {
        this.handleHeroVideos();
        this.ensureFallbackVisibility();
      } else {
        window.addEventListener("load", () => {
          this.handleHeroVideos();
          this.ensureFallbackVisibility();
        });
      }

      // Handle window resize (device orientation changes, etc.)
      let resizeTimeout;
      window.addEventListener("resize", () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          this.handleHeroVideos();
          this.ensureFallbackVisibility();
        }, 250);
      });
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
      // When video can play through
      videoElement.addEventListener("canplaythrough", () => {
        this.onVideoReady($video, videoElement);
      });

      // When video starts playing
      videoElement.addEventListener("playing", () => {
        this.onVideoPlaying($video, videoElement);
      });

      // Handle video errors
      videoElement.addEventListener("error", () => {
        this.onVideoError($video, videoElement);
      });

      // Handle video loading
      videoElement.addEventListener("loadstart", () => {
        this.onVideoLoadStart($video, videoElement);
      });
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
