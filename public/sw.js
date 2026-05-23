const CACHE_NAME = 'jogos-v1';
const URLS_TO_CACHE = ['/', '/justone.html', '/impostor.html', '/manifest.json'];

self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE_NAME).then(c => c.addAll(URLS_TO_CACHE)));
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(caches.keys().then(keys =>
        Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ));
    self.clients.claim();
});

self.addEventListener('fetch', e => {
    if (e.request.url.includes('socket.io')) return;
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request))
    );
});
