/**
 * Service Worker - Caching và Offline Support
 * Tối ưu hiệu suất với cache strategies
 */

const CACHE_NAME = 'lqa-shop-v1';
const STATIC_CACHE = 'lqa-static-v1';
const DYNAMIC_CACHE = 'lqa-dynamic-v1';

// Static assets to cache immediately
const STATIC_ASSETS = [
    '/lequocanh/',
    '/lequocanh/index.php',
    '/lequocanh/public_files/critical.css',
    '/lequocanh/public_files/mycss.css',
    '/lequocanh/public_files/performance.js',
    '/lequocanh/administrator/elements_LQA/img_LQA/no-image.png'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map(key => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') return;
    
    // Skip admin and API requests
    if (url.pathname.includes('/administrator/') || 
        url.pathname.includes('/api/') ||
        url.pathname.includes('/payment/')) {
        return;
    }
    
    // Cache strategy based on request type
    if (isStaticAsset(url.pathname)) {
        // Cache First for static assets
        event.respondWith(cacheFirst(request));
    } else if (isImage(url.pathname)) {
        // Cache First with network fallback for images
        event.respondWith(cacheFirstWithFallback(request));
    } else {
        // Network First for dynamic content
        event.respondWith(networkFirst(request));
    }
});

// Check if request is for static asset
function isStaticAsset(pathname) {
    return /\.(css|js|woff|woff2|ttf|eot)$/i.test(pathname);
}

// Check if request is for image
function isImage(pathname) {
    return /\.(jpg|jpeg|png|gif|webp|svg|ico)$/i.test(pathname);
}

// Cache First strategy
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response('Offline', { status: 503 });
    }
}

// Cache First with fallback
async function cacheFirstWithFallback(request) {
    const cached = await caches.match(request);
    if (cached) return cached;
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        // Return placeholder image
        return caches.match('/lequocanh/administrator/elements_LQA/img_LQA/no-image.png');
    }
}

// Network First strategy
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) return cached;
        
        return new Response('Offline - Please check your connection', {
            status: 503,
            headers: { 'Content-Type': 'text/html' }
        });
    }
}

// Background sync for failed requests
self.addEventListener('sync', event => {
    if (event.tag === 'sync-cart') {
        event.waitUntil(syncCart());
    }
});

async function syncCart() {
    // Sync cart data when back online
    console.log('Syncing cart data...');
}
