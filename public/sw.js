const CACHE_VERSION = 'pkk-v2';

const PRECACHE_URLS = [
    '/offline.html',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
    '/manifest.webmanifest',
];

const CACHEABLE_PATH_PREFIXES = ['/build/', '/icons/', '/js/', '/css/', '/fonts/'];

/*
 * Privasi & keamanan: jangan pernah menyimpan di Cache API respons HTML halaman
 * aplikasi (yang butuh login), respons JSON/API, gambar dari rute terautentikasi,
 * atau jalur dinamis lain. Hanya berkas tampilan statis same-origin yang jalurnya
 * dimulai dengan salah satu prefix daftar putih di atas, plus daftar precache.
 * Contoh yang TIDAK disimpan: /kegiatan-foto/{id}/berkas, /orang/.../foto, /dashboard.
 * Mengubah aturan ini bisa mengekspos data pengguna di perangkat bersama.
 */

function isCacheableStaticPath(request) {
    if (request.method !== 'GET') {
        return false;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return false;
    }

    return CACHEABLE_PATH_PREFIXES.some((prefix) => url.pathname.startsWith(prefix));
}

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

    if (isCacheableStaticPath(request)) {
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
