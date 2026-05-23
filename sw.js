var CACHE_NAME = 'jogos-festa-v11';
var URLS_TO_CACHE = [
    './',
    './index.html',
    './justone.html',
    './justone_v2.html',
    './impostor.html',
    './convite.html',
    './manifest.json'
];

self.addEventListener('install', function(e) {
    e.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(URLS_TO_CACHE);
            })
            .then(function() { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function(e) {
    e.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(
                keys.filter(function(k) { return k !== CACHE_NAME; })
                    .map(function(k) { return caches.delete(k); })
            );
        }).then(function() {
            return self.clients.claim();
        }).then(function() {
            return self.clients.matchAll().then(function(clients) {
                clients.forEach(function(client) {
                    client.postMessage({ type: 'SW_UPDATED' });
                });
            });
        })
    );
});

self.addEventListener('fetch', function(e) {
    // Nunca cachear chamadas API
    if (e.request.url.indexOf('api.php') !== -1) return;
    if (e.request.url.indexOf('create_icons') !== -1) return;
    if (e.request.url.indexOf('icon.php') !== -1) return;

    // Network-first: tentar sempre buscar do servidor primeiro
    e.respondWith(
        fetch(e.request)
            .then(function(response) {
                if (response.status === 200 && response.type === 'basic') {
                    var clone = response.clone();
                    caches.open(CACHE_NAME).then(function(cache) {
                        cache.put(e.request, clone);
                    });
                }
                return response;
            })
            .catch(function() {
                return caches.match(e.request);
            })
    );
});

// Escutar mensagem para forçar update
self.addEventListener('message', function(e) {
    if (e.data && e.data.type === 'FORCE_UPDATE') {
        caches.keys().then(function(keys) {
            return Promise.all(keys.map(function(k) { return caches.delete(k); }));
        }).then(function() {
            return self.skipWaiting();
        });
    }
});
