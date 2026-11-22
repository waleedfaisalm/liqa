/* Liqa service worker: static assets cache + offline fallback. Never caches pages, forms or API calls. */
const CACHE = 'liqa-v1';
const PRECACHE = ['/offline.html', '/brand/liqa-icon-192.png', '/favicon.svg'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') { return; }
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) { return; }

    // الصفحات: من الشبكة دائمًا، ولو انقطع الاتصال تظهر صفحة بدون إنترنت
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
        return;
    }

    // الملفات الثابتة المُجزّأة والشعارات: من الكاش أولًا
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/brand/')) {
        event.respondWith(
            caches.match(req).then((hit) => hit || fetch(req).then((res) => {
                if (res.ok) { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(req, copy)); }
                return res;
            }))
        );
    }
});
