const CACHE_NAME = 'flute-cache-v3';
const OFFLINE_URL = '/offline';

const ASSET_CACHE = 'flute-assets-v1';
const FONT_CACHE = 'flute-fonts-v1';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) =>
                cache.addAll([
                    OFFLINE_URL,
                    '/assets/fonts/manrope/Manrope-Regular.woff2',
                    '/assets/fonts/manrope/Manrope-Medium.woff2',
                    '/assets/js/htmx/core.js',
                    '/assets/js/app.js',
                    '/assets/js/libs/jquery.js',
                    '/assets/js/libs/floating.js',
                    '/assets/js/libs/a11y-dialog.js',
                ]),
            )
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    const keepCaches = [CACHE_NAME, ASSET_CACHE, FONT_CACHE];
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => !keepCaches.includes(name))
                        .map((name) => caches.delete(name)),
                );
            })
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Font files — cache-first, long-lived
    if (url.pathname.match(/\.(woff2?|ttf|eot)(\?|$)/)) {
        event.respondWith(
            caches.open(FONT_CACHE).then((cache) =>
                cache.match(event.request).then(
                    (cached) =>
                        cached ||
                        fetch(event.request).then((response) => {
                            if (response.ok) {
                                cache.put(event.request, response.clone());
                            }
                            return response;
                        }),
                ),
            ),
        );
        return;
    }

    // Static assets (CSS/JS with ?v= cache-bust) — cache-first
    if (
        url.origin === self.location.origin &&
        url.pathname.match(/\.(css|js)(\?|$)/) &&
        url.pathname.startsWith('/assets/')
    ) {
        event.respondWith(
            caches.open(ASSET_CACHE).then((cache) =>
                cache.match(event.request).then(
                    (cached) =>
                        cached ||
                        fetch(event.request).then((response) => {
                            if (response.ok) {
                                cache.put(event.request, response.clone());
                            }
                            return response;
                        }),
                ),
            ),
        );
        return;
    }

    // Navigation — network-first with offline fallback
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(OFFLINE_URL)),
        );
        return;
    }
});

self.addEventListener('push', (event) => {
    event.waitUntil(
        fetch(self.location.origin + '/api/push/pending', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => (r.ok ? r.json() : null))
            .then((data) => {
                if (!data || !data.title) return;

                let notificationUrl = '/';
                if (data.url) {
                    try {
                        const parsed = new URL(data.url, self.location.origin);
                        if (parsed.origin === self.location.origin) {
                            notificationUrl = parsed.pathname + parsed.search;
                        }
                    } catch {
                        notificationUrl = '/';
                    }
                }

                return self.registration.showNotification(
                    String(data.title).slice(0, 200),
                    {
                        body: String(data.body || '').slice(0, 500),
                        icon: data.icon || '/assets/img/logo.ico',
                        badge: '/assets/img/logo.ico',
                        data: { url: notificationUrl },
                        vibrate: [100, 50, 100],
                        tag: 'flute-' + (data.timestamp || Date.now()),
                        renotify: true,
                    },
                );
            })
            .catch(() => {}),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const rawUrl = event.notification.data?.url || '/';

    let targetUrl;
    try {
        const parsed = new URL(rawUrl, self.location.origin);
        targetUrl =
            parsed.origin === self.location.origin ? parsed.href : '/';
    } catch {
        targetUrl = self.location.origin + '/';
    }

    event.waitUntil(
        clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                for (const client of windowClients) {
                    if (
                        client.url.startsWith(self.location.origin) &&
                        'focus' in client
                    ) {
                        client.navigate(targetUrl);
                        return client.focus();
                    }
                }
                return clients.openWindow(targetUrl);
            }),
    );
});
