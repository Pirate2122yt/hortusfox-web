self.addEventListener("install", (event) => {
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
});

self.addEventListener("fetch", (event) => {
    event.respondWith(fetch(event.request));
});

self.addEventListener("push", (event) => {
    let data = { title: 'HortusFox', body: '', url: '/', icon: '/logo.png', badge: '/logo.png' };

    if (event.data) {
        try {
            data = Object.assign(data, event.data.json());
        } catch (e) {
            data.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon,
            badge: data.badge,
            data: { url: data.url }
        })
    );
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ((client.url === targetUrl) && ('focus' in client)) {
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});
