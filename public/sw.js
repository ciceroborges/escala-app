// Service worker do PWA.
// - Assets versionados do Vite (/build/*) e ícones: cache-first (o nome do arquivo muda a cada build).
// - Navegação: sempre rede (dados da escala precisam estar atualizados); sem rede, página offline.
// - Requisições do Inertia (X-Inertia) e mutações nunca são cacheadas.
const VERSION = 'v1';
const STATIC_CACHE = `escala-static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll([OFFLINE_URL, '/icons/icon-192.png', '/manifest.webmanifest']))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== STATIC_CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                }
                return response;
            })),
        );
        return;
    }

    if (request.mode === 'navigate' && !request.headers.get('X-Inertia')) {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    }
});
