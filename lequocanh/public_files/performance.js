/**
 * Frontend Performance Optimization
 * Lazy loading, prefetching, và các tối ưu khác
 */

(function() {
    'use strict';
    
    // ============================================
    // 1. LAZY LOADING IMAGES
    // ============================================
    const lazyLoadImages = () => {
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback cho browsers cũ
            images.forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }
    };
    
    // ============================================
    // 2. PREFETCH LINKS ON HOVER
    // ============================================
    const prefetchOnHover = () => {
        const prefetched = new Set();
        
        document.addEventListener('mouseover', (e) => {
            const link = e.target.closest('a');
            if (!link) return;
            
            const href = link.href;
            if (!href || prefetched.has(href)) return;
            if (href.startsWith('javascript:') || href.startsWith('#')) return;
            if (!href.startsWith(window.location.origin)) return;
            
            // Prefetch sau 100ms hover
            const timer = setTimeout(() => {
                const prefetchLink = document.createElement('link');
                prefetchLink.rel = 'prefetch';
                prefetchLink.href = href;
                document.head.appendChild(prefetchLink);
                prefetched.add(href);
            }, 100);
            
            link.addEventListener('mouseout', () => clearTimeout(timer), { once: true });
        });
    };
    
    // ============================================
    // 3. DEBOUNCE SCROLL EVENTS
    // ============================================
    const debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };
    
    // ============================================
    // 4. OPTIMIZE SCROLL PERFORMANCE
    // ============================================
    const optimizeScroll = () => {
        let ticking = false;
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    // Scroll handlers here
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    };
    
    // ============================================
    // 5. CACHE API RESPONSES
    // ============================================
    const apiCache = {
        data: new Map(),
        maxAge: 5 * 60 * 1000, // 5 phút
        
        get(key) {
            const item = this.data.get(key);
            if (!item) return null;
            if (Date.now() - item.timestamp > this.maxAge) {
                this.data.delete(key);
                return null;
            }
            return item.value;
        },
        
        set(key, value) {
            this.data.set(key, {
                value: value,
                timestamp: Date.now()
            });
        }
    };
    
    // Wrap fetch để cache
    const cachedFetch = async (url, options = {}) => {
        // Chỉ cache GET requests
        if (options.method && options.method !== 'GET') {
            return fetch(url, options);
        }
        
        const cached = apiCache.get(url);
        if (cached) {
            return new Response(JSON.stringify(cached), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }
        
        const response = await fetch(url, options);
        const data = await response.clone().json();
        apiCache.set(url, data);
        
        return response;
    };
    
    // ============================================
    // 6. DEFER NON-CRITICAL JS
    // ============================================
    const loadDeferredScripts = () => {
        const scripts = document.querySelectorAll('script[data-defer]');
        scripts.forEach(script => {
            const newScript = document.createElement('script');
            if (script.src) {
                newScript.src = script.src;
            } else {
                newScript.textContent = script.textContent;
            }
            document.body.appendChild(newScript);
            script.remove();
        });
    };
    
    // ============================================
    // 7. OPTIMIZE FORM SUBMISSIONS
    // ============================================
    const optimizeForms = () => {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                // Prevent double submission
                const submitBtn = form.querySelector('[type="submit"]');
                if (submitBtn && submitBtn.disabled) {
                    e.preventDefault();
                    return;
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                }
            });
        });
    };
    
    // ============================================
    // 8. PRELOAD CRITICAL RESOURCES
    // ============================================
    const preloadCritical = () => {
        // Preload fonts
        const fonts = [
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-solid-900.woff2'
        ];
        
        fonts.forEach(font => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'font';
            link.type = 'font/woff2';
            link.href = font;
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        });
    };
    
    // ============================================
    // 9. PERFORMANCE METRICS
    // ============================================
    const logPerformance = () => {
        if (!window.performance) return;
        
        window.addEventListener('load', () => {
            setTimeout(() => {
                const timing = performance.timing;
                const metrics = {
                    dns: timing.domainLookupEnd - timing.domainLookupStart,
                    tcp: timing.connectEnd - timing.connectStart,
                    ttfb: timing.responseStart - timing.requestStart,
                    download: timing.responseEnd - timing.responseStart,
                    domReady: timing.domContentLoadedEventEnd - timing.navigationStart,
                    load: timing.loadEventEnd - timing.navigationStart
                };
                
                console.log('📊 Performance Metrics:', metrics);
                
                // Gửi metrics về server nếu cần
                // fetch('/api/metrics', { method: 'POST', body: JSON.stringify(metrics) });
            }, 0);
        });
    };
    
    // ============================================
    // INITIALIZE
    // ============================================
    const init = () => {
        // Chạy ngay
        optimizeScroll();
        prefetchOnHover();
        
        // Chạy khi DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                lazyLoadImages();
                optimizeForms();
            });
        } else {
            lazyLoadImages();
            optimizeForms();
        }
        
        // Chạy sau khi load xong
        window.addEventListener('load', () => {
            loadDeferredScripts();
            logPerformance();
        });
    };
    
    // Export cho global use
    window.Performance = {
        lazyLoadImages,
        cachedFetch,
        debounce,
        apiCache
    };
    
    init();
})();
