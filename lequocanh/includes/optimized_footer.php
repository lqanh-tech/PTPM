<?php
/**
 * Optimized Footer - Include ở cuối các trang
 * Thêm deferred scripts và performance tracking
 */
?>

<!-- Deferred JavaScript Loading -->
<script>
// Lazy load non-critical scripts
(function() {
    function loadScript(src, async = true) {
        var script = document.createElement('script');
        script.src = src;
        script.async = async;
        document.body.appendChild(script);
    }
    
    // Load after page is interactive
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Load performance.js
            loadScript('public_files/performance.js');
        });
    } else {
        loadScript('public_files/performance.js');
    }
})();

// Service Worker Registration (for PWA support)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/lequocanh/sw.js').catch(function(err) {
            // Service worker registration failed
        });
    });
}

// Performance Observer
if ('PerformanceObserver' in window) {
    try {
        // Observe Largest Contentful Paint
        new PerformanceObserver(function(entryList) {
            var entries = entryList.getEntries();
            var lastEntry = entries[entries.length - 1];
            console.log('LCP:', lastEntry.startTime.toFixed(2) + 'ms');
        }).observe({type: 'largest-contentful-paint', buffered: true});
        
        // Observe First Input Delay
        new PerformanceObserver(function(entryList) {
            var entries = entryList.getEntries();
            entries.forEach(function(entry) {
                console.log('FID:', entry.processingStart - entry.startTime + 'ms');
            });
        }).observe({type: 'first-input', buffered: true});
    } catch(e) {}
}

// Preload next likely pages on hover
document.addEventListener('mouseover', function(e) {
    var link = e.target.closest('a');
    if (!link || !link.href) return;
    if (link.href.startsWith('javascript:') || link.href.startsWith('#')) return;
    if (!link.href.startsWith(window.location.origin)) return;
    
    // Check if already prefetched
    if (link.dataset.prefetched) return;
    
    var prefetch = document.createElement('link');
    prefetch.rel = 'prefetch';
    prefetch.href = link.href;
    document.head.appendChild(prefetch);
    link.dataset.prefetched = 'true';
}, {passive: true});
</script>

<?php
// Render performance debug info
if (defined('PAGE_START_TIME')) {
    $endTime = microtime(true);
    $executionTime = round(($endTime - PAGE_START_TIME) * 1000, 2);
    $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    
    // Chỉ hiển thị trong development
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "\n<!-- Page generated in {$executionTime}ms | Memory: {$memoryUsage}MB -->\n";
    }
}
?>
