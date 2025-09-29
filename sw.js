/**
 * PrimeFit Service Worker - Mobile-First Caching Strategy with Compression
 * Optimized for mobile performance with intelligent caching and response compression
 *
 * @package PrimeFit
 * @since 1.0.0
 */

const CACHE_VERSION = "primefit-mobile-v2.1";
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;
const IMAGE_CACHE = `${CACHE_VERSION}-images`;
const COMPRESSED_CACHE = `${CACHE_VERSION}-compressed`;

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
  // Compressed content - cache first, medium TTL
  compressed: {
    maxAge: 2 * 24 * 60 * 60 * 1000, // 2 days
    maxEntries: 40,
  },
};

// Compression settings
const COMPRESSION_CONFIG = {
  // Minimum size to compress (bytes)
  minSize: 1024, // 1KB
  // Maximum size to compress (bytes) - avoid compressing very large files
  maxSize: 5 * 1024 * 1024, // 5MB
  // Content types to compress
  compressibleTypes: [
    'text/html',
    'text/css',
    'text/javascript',
    'application/javascript',
    'application/json',
    'text/plain',
    'application/xml',
    'text/xml',
    'application/rss+xml',
    'application/atom+xml'
  ],
  // Compression quality (0-1)
  quality: 0.8
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
  console.log("PrimeFit SW: Installing with compression support...");

  event.waitUntil(
    Promise.all([
      // Cache critical static resources
      caches.open(STATIC_CACHE).then((cache) => {
        return cache.addAll(
          CRITICAL_RESOURCES.map((url) => new Request(url, { cache: "reload" }))
        );
      }),
      // Initialize compressed cache
      caches.open(COMPRESSED_CACHE).then((cache) => {
        console.log("PrimeFit SW: Compressed cache initialized");
        return cache;
      }),
      // Skip waiting to activate immediately
      self.skipWaiting(),
    ])
  );
});

// Activate event - clean up old caches
self.addEventListener("activate", (event) => {
  console.log("PrimeFit SW: Activating with compression support...");

  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (
              cacheName !== STATIC_CACHE &&
              cacheName !== DYNAMIC_CACHE &&
              cacheName !== IMAGE_CACHE &&
              cacheName !== COMPRESSED_CACHE
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

// Fetch event - intelligent caching strategy with compression
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
    event.respondWith(cacheFirstStrategyWithCompression(request, STATIC_CACHE));
  } else if (isImageRequest(request)) {
    event.respondWith(cacheFirstStrategy(request, IMAGE_CACHE));
  } else if (isAPIRequest(request)) {
    event.respondWith(networkFirstStrategyWithCompression(request, DYNAMIC_CACHE));
  } else {
    event.respondWith(staleWhileRevalidateStrategyWithCompression(request, DYNAMIC_CACHE));
  }
});

// Compression utility functions
function shouldCompressResponse(response) {
  const contentType = response.headers.get('content-type') || '';
  const contentLength = parseInt(response.headers.get('content-length') || '0');
  
  return (
    COMPRESSION_CONFIG.compressibleTypes.some(type => contentType.includes(type)) &&
    contentLength >= COMPRESSION_CONFIG.minSize &&
    contentLength <= COMPRESSION_CONFIG.maxSize
  );
}

async function compressResponse(response) {
  if (!shouldCompressResponse(response)) {
    return response;
  }

  try {
    const originalBody = await response.arrayBuffer();
    const compressedBody = await compressData(originalBody);
    
    // Create new response with compressed body
    const compressedResponse = new Response(compressedBody, {
      status: response.status,
      statusText: response.statusText,
      headers: {
        ...Object.fromEntries(response.headers.entries()),
        'content-encoding': 'gzip',
        'content-length': compressedBody.byteLength.toString(),
        'x-compressed': 'true'
      }
    });
    
    return compressedResponse;
  } catch (error) {
    console.warn("PrimeFit SW: Compression failed, returning original response:", error);
    return response;
  }
}

async function compressData(data) {
  // Use CompressionStream API if available (modern browsers)
  if ('CompressionStream' in window) {
    const stream = new CompressionStream('gzip');
    const writer = stream.writable.getWriter();
    const reader = stream.readable.getReader();
    
    writer.write(data);
    writer.close();
    
    const chunks = [];
    let done = false;
    
    while (!done) {
      const { value, done: readerDone } = await reader.read();
      done = readerDone;
      if (value) {
        chunks.push(value);
      }
    }
    
    const compressedLength = chunks.reduce((acc, chunk) => acc + chunk.byteLength, 0);
    const compressed = new Uint8Array(compressedLength);
    let offset = 0;
    
    for (const chunk of chunks) {
      compressed.set(chunk, offset);
      offset += chunk.byteLength;
    }
    
    return compressed.buffer;
  }
  
  // Fallback: simple text compression for text-based content
  const text = new TextDecoder().decode(data);
  const compressed = text.replace(/\s+/g, ' ').trim();
  return new TextEncoder().encode(compressed).buffer;
}

async function decompressResponse(response) {
  if (response.headers.get('x-compressed') !== 'true') {
    return response;
  }
  
  try {
    const compressedBody = await response.arrayBuffer();
    const decompressedBody = await decompressData(compressedBody);
    
    return new Response(decompressedBody, {
      status: response.status,
      statusText: response.statusText,
      headers: {
        ...Object.fromEntries(response.headers.entries()),
        'content-encoding': '',
        'content-length': decompressedBody.byteLength.toString(),
        'x-compressed': ''
      }
    });
  } catch (error) {
    console.warn("PrimeFit SW: Decompression failed, returning original response:", error);
    return response;
  }
}

async function decompressData(data) {
  // Use DecompressionStream API if available
  if ('DecompressionStream' in window) {
    const stream = new DecompressionStream('gzip');
    const writer = stream.writable.getWriter();
    const reader = stream.readable.getReader();
    
    writer.write(data);
    writer.close();
    
    const chunks = [];
    let done = false;
    
    while (!done) {
      const { value, done: readerDone } = await reader.read();
      done = readerDone;
      if (value) {
        chunks.push(value);
      }
    }
    
    const decompressedLength = chunks.reduce((acc, chunk) => acc + chunk.byteLength, 0);
    const decompressed = new Uint8Array(decompressedLength);
    let offset = 0;
    
    for (const chunk of chunks) {
      decompressed.set(chunk, offset);
      offset += chunk.byteLength;
    }
    
    return decompressed.buffer;
  }
  
  // Fallback: return original data
  return data;
}

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

// Cache First Strategy with Compression - for static assets
async function cacheFirstStrategyWithCompression(request, cacheName) {
  try {
    const cache = await caches.open(cacheName);
    const compressedCache = await caches.open(COMPRESSED_CACHE);
    
    // Check regular cache first
    let cachedResponse = await cache.match(request);
    
    if (cachedResponse && isCacheValid(cachedResponse, CACHE_STRATEGIES.static.maxAge)) {
      return cachedResponse;
    }
    
    // Check compressed cache
    const compressedResponse = await compressedCache.match(request);
    if (compressedResponse && isCacheValid(compressedResponse, CACHE_STRATEGIES.compressed.maxAge)) {
      return await decompressResponse(compressedResponse);
    }

    // Fetch from network
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      // Cache original response
      cache.put(request, networkResponse.clone());
      
      // Try to compress and cache compressed version
      const compressed = await compressResponse(networkResponse.clone());
      if (compressed !== networkResponse) {
        compressedCache.put(request, compressed);
      }
    }
    
    return networkResponse;
  } catch (error) {
    console.error("PrimeFit SW: Cache first strategy with compression failed:", error);
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

// Network First Strategy with Compression - for dynamic content
async function networkFirstStrategyWithCompression(request, cacheName) {
  try {
    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
      const cache = await caches.open(cacheName);
      const compressedCache = await caches.open(COMPRESSED_CACHE);
      
      // Cache original response
      cache.put(request, networkResponse.clone());
      
      // Try to compress and cache compressed version
      const compressed = await compressResponse(networkResponse.clone());
      if (compressed !== networkResponse) {
        compressedCache.put(request, compressed);
      }
    }

    return networkResponse;
  } catch (error) {
    // Fallback to cache
    const cache = await caches.open(cacheName);
    const compressedCache = await caches.open(COMPRESSED_CACHE);
    
    // Try regular cache first
    let cachedResponse = await cache.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Try compressed cache
    const compressedResponse = await compressedCache.match(request);
    if (compressedResponse) {
      return await decompressResponse(compressedResponse);
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

// Stale While Revalidate Strategy with Compression - for HTML pages
async function staleWhileRevalidateStrategyWithCompression(request, cacheName) {
  const cache = await caches.open(cacheName);
  const compressedCache = await caches.open(COMPRESSED_CACHE);
  
  // Check regular cache first
  let cachedResponse = await cache.match(request);
  
  // Check compressed cache if no regular cache
  if (!cachedResponse) {
    const compressedResponse = await compressedCache.match(request);
    if (compressedResponse) {
      cachedResponse = await decompressResponse(compressedResponse);
    }
  }

  // Fetch from network in background
  const networkResponsePromise = fetch(request)
    .then(async (response) => {
      if (response.ok) {
        // Cache original response
        cache.put(request, response.clone());
        
        // Try to compress and cache compressed version
        const compressed = await compressResponse(response.clone());
        if (compressed !== response) {
          compressedCache.put(request, compressed);
        }
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

  if (event.data && event.data.type === "CLEAR_COMPRESSED_CACHE") {
    event.waitUntil(clearCompressedCache());
  }

  if (event.data && event.data.type === "GET_CACHE_STATS") {
    event.waitUntil(getCacheStats().then(stats => {
      event.ports[0].postMessage(stats);
    }));
  }
});

async function clearAllCaches() {
  const cacheNames = await caches.keys();
  return Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
}

async function clearCompressedCache() {
  return caches.delete(COMPRESSED_CACHE);
}

async function getCacheStats() {
  const cacheNames = await caches.keys();
  const stats = {};
  
  for (const cacheName of cacheNames) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    stats[cacheName] = {
      entries: keys.length,
      size: await estimateCacheSize(cache)
    };
  }
  
  return stats;
}

async function estimateCacheSize(cache) {
  const keys = await cache.keys();
  let totalSize = 0;
  
  for (const key of keys) {
    const response = await cache.match(key);
    if (response) {
      const body = await response.arrayBuffer();
      totalSize += body.byteLength;
    }
  }
  
  return totalSize;
}
