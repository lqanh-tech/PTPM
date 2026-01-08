<?php

class AsyncLoader
{
    private static $instance = null;
    private $components = [];
    private $loadedComponents = [];
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function register($name, $callback, $priority = 10)
    {
        $this->components[$name] = [
            'callback' => $callback,
            'priority' => $priority,
            'loaded' => false
        ];
        return $this;
    }
    
    public function load($name)
    {
        if (!isset($this->components[$name])) {
            return null;
        }
        
        if ($this->components[$name]['loaded']) {
            return $this->loadedComponents[$name];
        }
        
        $result = call_user_func($this->components[$name]['callback']);
        $this->components[$name]['loaded'] = true;
        $this->loadedComponents[$name] = $result;
        
        return $result;
    }
    
    public function placeholder($name, $height = '200px', $class = '')
    {
        return <<<HTML
<div class="async-placeholder {$class}" data-component="{$name}" style="min-height:{$height}">
    <div class="skeleton" style="width:100%;height:100%;border-radius:8px"></div>
</div>
HTML;
    }
    
    public function renderEndpoint()
    {
        if (!isset($_GET['async_component'])) {
            return;
        }
        
        $name = $_GET['async_component'];
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: public, max-age=300');
        
        echo $this->load($name);
        exit;
    }
    
    public static function getLoaderScript()
    {
        return <<<'JS'
<script>
(function() {
    var placeholders = document.querySelectorAll('.async-placeholder');
    
    function loadComponent(placeholder) {
        var component = placeholder.dataset.component;
        var url = window.location.pathname + '?async_component=' + encodeURIComponent(component);
        
        fetch(url)
            .then(function(response) { return response.text(); })
            .then(function(html) {
                var temp = document.createElement('div');
                temp.innerHTML = html;
                placeholder.parentNode.replaceChild(temp.firstChild, placeholder);
            })
            .catch(function(error) {
                console.error('Failed to load component:', component, error);
                placeholder.innerHTML = '<div class="alert alert-danger">Failed to load</div>';
            });
    }
    
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    loadComponent(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '100px' });
        
        placeholders.forEach(function(p) { observer.observe(p); });
    } else {
        placeholders.forEach(loadComponent);
    }
})();
</script>
JS;
    }
    
    public static function getSkeletonStyles()
    {
        return <<<'CSS'
<style>
.async-placeholder{position:relative;overflow:hidden;background:#f8f9fa;border-radius:8px}
.skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.5s infinite}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
.skeleton-text{height:1em;margin:8px 0;border-radius:4px}
.skeleton-title{height:1.5em;width:60%;margin-bottom:12px}
.skeleton-image{width:100%;padding-top:75%;border-radius:8px}
.skeleton-card{padding:16px}
</style>
CSS;
    }
    
    public static function skeletonCard()
    {
        return <<<'HTML'
<div class="skeleton-card">
    <div class="skeleton skeleton-image"></div>
    <div class="skeleton skeleton-title"></div>
    <div class="skeleton skeleton-text" style="width:80%"></div>
    <div class="skeleton skeleton-text" style="width:60%"></div>
</div>
HTML;
    }
    
    public static function skeletonList($count = 5)
    {
        $html = '<div class="skeleton-list">';
        for ($i = 0; $i < $count; $i++) {
            $html .= '<div class="skeleton skeleton-text" style="width:' . (70 + rand(0, 30)) . '%"></div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    public static function skeletonGrid($cols = 4, $rows = 2)
    {
        $html = '<div style="display:grid;grid-template-columns:repeat(' . $cols . ',1fr);gap:20px">';
        for ($i = 0; $i < $cols * $rows; $i++) {
            $html .= self::skeletonCard();
        }
        $html .= '</div>';
        return $html;
    }
}

function async_loader() {
    return AsyncLoader::getInstance();
}
