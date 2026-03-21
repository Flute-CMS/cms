const CACHE_NAME = 'flute-cache-v1';
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => cache.add(OFFLINE_URL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => cacheName !== CACHE_NAME)
                        .map((cacheName) => caches.delete(cacheName)),
                );
            })
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(OFFLINE_URL)),
        );
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
