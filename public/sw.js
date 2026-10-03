const CACHE_VERSION = 'pkk-v1';

const PRECACHE_URLS = [
    '/offline.html',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
    '/manifest.webmanifest',
];

const STATIC_DESTINATIONS = ['style', 'script', 'image', 'font'];

/*
 * Privasi & keamanan: jangan pernah menyimpan di Cache API respons HTML halaman
 * aplikasi (yang butuh login), respons JSON/API, atau rute dinamis lain.
 * Hanya berkas statis umum (CSS/JS/gambar/font) dan daftar precache di atas.
 * Mengubah aturan ini bisa mengekspos data pengguna di perangkat bersama.
 */

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(PRECACHE_URLS)),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html')),
        );
        return;
    }

    if (STATIC_DESTINATIONS.includes(request.destination)) {
        event.respondWith(
            caches.open(CACHE_VERSION).then(async (cache) => {
                const cached = await cache.match(request);
                const networkUpdate = fetch(request)
                    .then((response) => {
                        if (response.ok) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    })
                    .catch(() => null);

                if (cached) {
                    networkUpdate.catch(() => {});
                    return cached;
                }

                const fromNetwork = await networkUpdate;
                if (fromNetwork) {
                    return fromNetwork;
                }

                return new Response('', { status: 504, statusText: 'Gateway Timeout' });
            }),
        );
        return;
    }

    // Network-only: tidak memanggil respondWith agar permintaan tidak disimpan ke cache.
});
