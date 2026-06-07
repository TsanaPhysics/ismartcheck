const CACHE_NAME = 'ismartcheck-v2';
const ASSETS_TO_CACHE = [
  './',
  './index.html',
  './assets/css/style.css',
  './assets/js/app.js',
  './manifest.json',
  './assets/img/ai_icon.png'
];

// Install Event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(ASSETS_TO_CACHE);
      })
  );
  self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  if (event.request.url.includes('/api/')) return;

  // Network First for HTML files (to always get latest code)
  if (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html')) {
    event.respondWith(
      fetch(event.request).then(response => {
        // Cache the latest version
        let responseClone = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
        return response;
      }).catch(() => {
        // Fallback to cache if network fails
        return caches.match(event.request);
      })
    );
    return;
  }

  // Stale-While-Revalidate for other static assets
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        const fetchPromise = fetch(event.request).then(networkResponse => {
          if(networkResponse && networkResponse.status === 200) {
            let responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
          }
          return networkResponse;
        }).catch(() => { /* ignore network error for static assets */ });
        
        return cachedResponse || fetchPromise;
      })
  );
});
