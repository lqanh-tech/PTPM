<?php

?>

<!-- Deferred JavaScript Loading -->
<script>

(function() {
    function loadScript(src, async = true) {
        var script = document.createElement('script');
        script.src = src;
        script.async = async;
        document.body.appendChild(script);
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {

            loadScript('public_files/performance.js');
        });
    } else {
        loadScript('public_files/performance.js');
    }
})();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/lequocanh/sw.js').catch(function(err) {

        });
    });
}

if ('PerformanceObserver' in window) {
    try {

        new PerformanceObserver(function(entryList) {
            var entries = entryList.getEntries();
            var lastEntry = entries[entries.length - 1];
            console.log('LCP:', lastEntry.startTime.toFixed(2) + 'ms');
        }).observe({type: 'largest-contentful-paint', buffered: true});
        
        new PerformanceObserver(function(entryList) {
            var entries = entryList.getEntries();
            entries.forEach(function(entry) {
                console.log('FID:', entry.processingStart - entry.startTime + 'ms');
            });
        }).observe({type: 'first-input', buffered: true});
    } catch(e) {}
}

document.addEventListener('mouseover', function(e) {
    var link = e.target.closest('a');
    if (!link || !link.href) return;
    if (link.href.startsWith('javascript:') || link.href.startsWith('#')) return;
    if (!link.href.startsWith(window.location.origin)) return;
    
    if (link.dataset.prefetched) return;
    
    var prefetch = document.createElement('link');
    prefetch.rel = 'prefetch';
    prefetch.href = link.href;
    document.head.appendChild(prefetch);
    link.dataset.prefetched = 'true';
}, {passive: true});
</script>

<?php

if (defined('PAGE_START_TIME')) {
    $endTime = microtime(true);
    $executionTime = round(($endTime - PAGE_START_TIME) * 1000, 2);
    $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "\n<!-- Page generated in {$executionTime}ms | Memory: {$memoryUsage}MB -->\n";
    }
}
?>
