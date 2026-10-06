self.addEventListener('install', e => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));
self.addEventListener('fetch', e => {
  const r = e.request;
  if (r.method !== 'GET' || new URL(r.url).pathname.startsWith('/api')) return;
  e.respondWith(fetch(r).then(res => {
    const copy = res.clone();
    caches.open('agro-v1').then(c => c.put(r, copy));
    return res;
  }).catch(() => caches.match(r)));
});
