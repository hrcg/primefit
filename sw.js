
const CACHE_NAME = "primefit-v1";
const urlsToCache = [
	"/",
	"http://prime.local/wp-content/themes/primefit/assets/css/app.css",
	"http://prime.local/wp-content/themes/primefit/assets/js/app.js"
];

self.addEventListener("install", function(event) {
	event.waitUntil(
		caches.open(CACHE_NAME)
			.then(function(cache) {
				return cache.addAll(urlsToCache);
			})
	);
});

self.addEventListener("fetch", function(event) {
	event.respondWith(
		caches.match(event.request)
			.then(function(response) {
				if (response) {
					return response;
				}
				return fetch(event.request);
			}
		)
	);
});
		