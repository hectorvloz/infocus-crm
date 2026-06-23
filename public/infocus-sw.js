self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (error) {
    data = {};
  }

  const title = data.title || 'Infocus';
  const options = {
    body: data.body || data.message || 'Tienes una nueva notificación.',
    tag: data.tag || data.id || 'infocus-notification',
    renotify: false,
    icon: data.icon || '/favicon.ico',
    badge: data.badge || '/favicon.ico',
    data: {
      url: data.url || '/',
      id: data.id || '',
    },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = event.notification?.data?.url || '/';

  event.waitUntil((async () => {
    const allClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });
    const absoluteUrl = new URL(targetUrl, self.location.origin).href;

    for (const client of allClients) {
      if ('focus' in client) {
        if (client.url === absoluteUrl) {
          return client.focus();
        }
      }
    }

    if (clients.openWindow) {
      return clients.openWindow(absoluteUrl);
    }
  })());
});
