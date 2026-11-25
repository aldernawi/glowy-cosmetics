// Keeps the register opening instantly without a connection.
// VERSION must change whenever a shell file changes; PosOfflineShellTest checks it.
const VERSION = '26f56e39ce4c';
const SHELL_CACHE = `glowy-pos-shell-${VERSION}`;
const IMAGE_CACHE = 'glowy-pos-images';
const SHELL = [
    '/pos',
    '/pos-assets/app.css',
    '/pos-assets/app.js',
    '/pos-assets/core.js',
    '/pos-assets/db.js',
    '/pos-assets/manifest.webmanifest',
    '/images/glowy-logo.png',
    '/images/product-placeholder.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(SHELL_CACHE).then((cache) => cache.addAll(SHELL.map((url) => new Request(url, { cache: 'reload' })))).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys()
        .then((names) => Promise.all(names.filter((name) => name.startsWith('glowy-pos-shell-') && name !== SHELL_CACHE).map((name) => caches.delete(name))))
        .then(() => self.clients.claim()));
});

const cacheFirst = async (request, cacheName, key = request) => {
    const cached = await caches.match(key, { cacheName });
    if (cached) return cached;
    const response = await fetch(request);
    if (response.ok && cacheName === IMAGE_CACHE) (await caches.open(cacheName)).put(key, response.clone());
    return response;
};

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin || url.pathname.startsWith('/pos/api/')) return;
    if (request.mode === 'navigate') {
        if (url.pathname === '/pos') event.respondWith(cacheFirst(request, SHELL_CACHE, '/pos'));
        return;
    }
    if (SHELL.includes(url.pathname)) event.respondWith(cacheFirst(request, SHELL_CACHE, url.pathname));
    else if (request.destination === 'image') event.respondWith(cacheFirst(request, IMAGE_CACHE));
});
