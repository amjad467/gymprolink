const CACHE_NAME = 'gympro-pwa-v1';
const STATIC_ASSETS = [
  '/',
  '/manifest.json',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  '/icons/icon.svg'
];

// Install: Pre-cache core shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

// Activate: Clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

// Fetch: Network first strategy for dynamic Laravel app
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // For static icons and manifest, cache first
  if (url.pathname.startsWith('/icons/') || url.pathname === '/manifest.json') {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        return cached || fetch(event.request).then((response) => {
          const resClone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, resClone));
          return response;
        });
      })
    );
    return;
  }

  // Network first for all HTML pages and API routes
  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        return networkResponse;
      })
      .catch(() => {
        return caches.match(event.request).then((cached) => {
          return cached || new Response('<html><body style="background:#020617;color:#fff;font-family:sans-serif;text-align:center;padding:50px;"><h2>⚠️ ئینتەرنێت پچڕاوە</h2><p>تکایە پەیوەندی ئینتەرنێتەکەت بپشکنەوە و دووبارە هەوڵبدەرەوە.</p></body></html>', {
            headers: { 'Content-Type': 'text/html; charset=utf-8' }
          });
        });
      })
  );
});
