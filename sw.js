/**
 * PrimeFit Service Worker - Mobile-First Caching Strategy
 * Optimized for mobile performance with intelligent caching
 *
 * @package PrimeFit
 * @since 1.0.0
 */

const CACHE_VERSION = "primefit-mobile-v2.0";
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;
const IMAGE_CACHE = `${CACHE_VERSION}-images`;

// Cache strategies for different resource types
const CACHE_STRATEGIES = {
  // Static assets - cache first, long TTL
  static: {
    maxAge: 7 * 24 * 60 * 60 * 1000, // 7 days
    maxEntries: 50,
  },
  // Dynamic content - network first, short TTL
  dynamic: {
    maxAge: 24 * 60 * 60 * 1000, // 1 day
    maxEntries: 30,
  },
  // Images - cache first, medium TTL
  images: {
    maxAge: 3 * 24 * 60 * 60 * 1000, // 3 days
    maxEntries: 100,
  },
};

// Critical resources for immediate caching
const CRITICAL_RESOURCES = [
  "/",
  "/wp-content/themes/primefit/assets/css/app.css",
  "/wp-content/themes/primefit/assets/css/header.css",
  "/wp-content/themes/primefit/assets/js/core.js",
  "/wp-content/themes/primefit/assets/js/app.js",
];

// Mobile-specific optimizations
const isMobile = () => {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
    navigator.userAgent
  );
};

// Install event - cache critical resources
self.addEventListener("install", (event) => {
  console.log("PrimeFit SW: Installing...");

  event.waitUntil(
    Promise.all([
      // Cache critical static resources
      caches.open(STATIC_CACHE).then((cache) => {
        return cache.addAll(
          CRITICAL_RESOURCES.map((url) => new Request(url, { cache: "reload" }))
        );
      }),
      // Skip waiting to activate immediately
      self.skipWaiting(),
    ])
  );
});

// Activate event - clean up old caches
self.addEventListener("activate", (event) => {
  console.log("PrimeFit SW: Activating...");

  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (
              cacheName !== STATIC_CACHE &&
              cacheName !== DYNAMIC_CACHE &&
              cacheName !== IMAGE_CACHE
            ) {
              console.log("PrimeFit SW: Deleting old cache:", cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      // Take control of all clients
      self.clients.claim(),
    ])
  );
});

// Fetch event - intelligent caching strategy
self.addEventListener("fetch", (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== "GET") {
    return;
  }

  // Skip cross-origin requests (except for our theme assets)
  if (
    url.origin !== location.origin &&
    !url.href.includes("/wp-content/themes/primefit/")
  ) {
    return;
  }

  // Determine caching strategy based on request type
  if (isStaticAsset(request)) {
    event.respondWith(cacheFirstStrategy(request, STATIC_CACHE));
  } else if (isImageRequest(request)) {
    event.respondWith(cacheFirstStrategy(request, IMAGE_CACHE));
  } else if (isAPIRequest(request)) {
    event.respondWith(networkFirstStrategy(request, DYNAMIC_CACHE));
  } else {
    event.respondWith(staleWhileRevalidateStrategy(request, DYNAMIC_CACHE));
  }
});

// Cache First Strategy - for static assets
async function cacheFirstStrategy(request, cacheName) {
  try {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
      // Check if cache is still valid
      if (isCacheValid(cachedResponse, CACHE_STRATEGIES.static.maxAge)) {
        return cachedResponse;
      }
    }

    // Fetch from network and cache
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.error("PrimeFit SW: Cache first strategy failed:", error);
    return new Response("Offline", { status: 503 });
  }
}

// Network First Strategy - for dynamic content
async function networkFirstStrategy(request, cacheName) {
  try {
    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, networkResponse.clone());
    }

    return networkResponse;
  } catch (error) {
    // Fallback to cache
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
      return cachedResponse;
    }

    return new Response("Offline", { status: 503 });
  }
}

// Stale While Revalidate Strategy - for HTML pages
async function staleWhileRevalidateStrategy(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cachedResponse = await cache.match(request);

  // Fetch from network in background
  const networkResponsePromise = fetch(request)
    .then((response) => {
      if (response.ok) {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch(() => null);

  // Return cached version immediately if available
  if (cachedResponse) {
    return cachedResponse;
  }

  // Otherwise wait for network
  return networkResponsePromise || new Response("Offline", { status: 503 });
}

// Helper functions
function isStaticAsset(request) {
  const url = new URL(request.url);
  return (
    url.pathname.includes("/wp-content/themes/primefit/assets/") ||
    url.pathname.endsWith(".css") ||
    url.pathname.endsWith(".js") ||
    url.pathname.endsWith(".woff2") ||
    url.pathname.endsWith(".woff")
  );
}

function isImageRequest(request) {
  const url = new URL(request.url);
  return url.pathname.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i);
}

function isAPIRequest(request) {
  const url = new URL(request.url);
  return (
    url.pathname.includes("/wp-admin/admin-ajax.php") ||
    url.pathname.includes("/wp-json/") ||
    url.pathname.includes("wc-ajax")
  );
}

function isCacheValid(response, maxAge) {
  const dateHeader = response.headers.get("date");
  if (!dateHeader) return false;

  const responseDate = new Date(dateHeader);
  const now = new Date();
  return now - responseDate < maxAge;
}

// Background sync for offline actions
self.addEventListener("sync", (event) => {
  if (event.tag === "background-sync") {
    event.waitUntil(doBackgroundSync());
  }
});

async function doBackgroundSync() {
  // Handle offline actions when connection is restored
  console.log("PrimeFit SW: Background sync triggered");
}

// Push notifications (for future use)
self.addEventListener("push", (event) => {
  if (event.data) {
    const data = event.data.json();
    const options = {
      body: data.body,
      icon: "/wp-content/themes/primefit/assets/images/icon-192.png",
      badge: "/wp-content/themes/primefit/assets/images/badge-72.png",
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: 1,
      },
    };

    event.waitUntil(self.registration.showNotification(data.title, options));
  }
});

// Message handling for cache management
self.addEventListener("message", (event) => {
  if (event.data && event.data.type === "SKIP_WAITING") {
    self.skipWaiting();
  }

  if (event.data && event.data.type === "CLEAR_CACHE") {
    event.waitUntil(clearAllCaches());
  }
});

async function clearAllCaches() {
  const cacheNames = await caches.keys();
  return Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
}
