/* TRD Rider Hub shell cache — lives at site root so Add to Home Screen works on Namecheap. */
const CACHE = 'trd-hub-v4';
const SHELL = [
  './',
  './assets/app.css',
  './assets/app.js',
  './assets/manifest.webmanifest',
  './assets/icon-192.png',
  './assets/icon-512.png',
  './assets/favicon.png',
  './assets/rider.jpg',
  './assets/driver.jpg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;

  const isAsset = url.pathname.includes('/assets/');
  if (isAsset) {
    event.respondWith(
      caches.match(req).then((hit) => {
        const fresh = fetch(req).then((res) => {
          if (res && res.ok) {
            const copy = res.clone();
            caches.open(CACHE).then((c) => c.put(req, copy));
          }
          return res;
        }).catch(() => hit);
        return hit || fresh;
      })
    );
    return;
  }

  event.respondWith(
    fetch(req).then((res) => res).catch(() =>
      caches.match(req).then((hit) => hit || caches.match('./'))
    )
  );
});
