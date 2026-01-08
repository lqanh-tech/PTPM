<?php

class ImageOptimizer
{
    private static $instance = null;
    private $cacheDir;
    private $quality = 85;
    private $maxWidth = 1200;
    private $maxHeight = 1200;
    private $thumbnailSizes = [
        'thumb' => ['width' => 150, 'height' => 150],
        'small' => ['width' => 300, 'height' => 300],
        'medium' => ['width' => 600, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 1200]
    ];
    
    private function __construct()
    {
        $this->cacheDir = __DIR__ . '/../cache/images/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getOptimizedUrl($imagePath, $size = 'medium')
    {
        if (empty($imagePath)) {
            return '/administrator/elements_LQA/img_LQA/no-image.png';
        }
        
        $cacheKey = md5($imagePath . $size) . '.jpg';
        $cachePath = $this->cacheDir . $cacheKey;
        
        if (file_exists($cachePath) && filemtime($cachePath) > time() - 86400) {
            return '/cache/images/' . $cacheKey;
        }
        
        return $imagePath;
    }
    
    public function generateLazyLoadHtml($imagePath, $alt = '', $class = '', $size = 'medium')
    {
        $placeholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300"%3E%3Crect fill="%23f0f0f0" width="300" height="300"/%3E%3C/svg%3E';
        
        $dimensions = isset($this->thumbnailSizes[$size]) ? $this->thumbnailSizes[$size] : $this->thumbnailSizes['medium'];
        
        $html = '<img ';
        $html .= 'src="' . htmlspecialchars($placeholder) . '" ';
        $html .= 'data-src="' . htmlspecialchars($imagePath) . '" ';
        $html .= 'alt="' . htmlspecialchars($alt) . '" ';
        $html .= 'class="lazy-load ' . htmlspecialchars($class) . '" ';
        $html .= 'width="' . $dimensions['width'] . '" ';
        $html .= 'height="' . $dimensions['height'] . '" ';
        $html .= 'loading="lazy" ';
        $html .= 'decoding="async">';
        
        return $html;
    }
    
    public function generateResponsiveHtml($imagePath, $alt = '', $class = '')
    {
        $html = '<picture>';
        
        $html .= '<source media="(max-width: 576px)" srcset="' . $this->getOptimizedUrl($imagePath, 'small') . '">';
        $html .= '<source media="(max-width: 992px)" srcset="' . $this->getOptimizedUrl($imagePath, 'medium') . '">';
        $html .= '<source media="(min-width: 993px)" srcset="' . $this->getOptimizedUrl($imagePath, 'large') . '">';
        
        $html .= '<img src="' . htmlspecialchars($imagePath) . '" ';
        $html .= 'alt="' . htmlspecialchars($alt) . '" ';
        $html .= 'class="' . htmlspecialchars($class) . '" ';
        $html .= 'loading="lazy" decoding="async">';
        
        $html .= '</picture>';
        
        return $html;
    }
    
    public static function getLazyLoadScript()
    {
        return <<<'JS'
<script>
(function() {
    var lazyImages = document.querySelectorAll('img.lazy-load');
    
    if ('IntersectionObserver' in window) {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var image = entry.target;
                    image.src = image.dataset.src;
                    image.classList.remove('lazy-load');
                    image.classList.add('lazy-loaded');
                    imageObserver.unobserve(image);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
        
        lazyImages.forEach(function(image) {
            imageObserver.observe(image);
        });
    } else {
        // Fallback for older browsers
        lazyImages.forEach(function(image) {
            image.src = image.dataset.src;
        });
    }
})();
</script>
JS;
    }
    
    public static function getLazyLoadStyles()
    {
        return <<<'CSS'
<style>
.lazy-load {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}
.lazy-loaded {
    opacity: 1;
    animation: none;
    background: none;
}
@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
</style>
CSS;
    }
}

function lazy_img($src, $alt = '', $class = '', $size = 'medium') {
    return ImageOptimizer::getInstance()->generateLazyLoadHtml($src, $alt, $class, $size);
}

function responsive_img($src, $alt = '', $class = '') {
    return ImageOptimizer::getInstance()->generateResponsiveHtml($src, $alt, $class);
}
