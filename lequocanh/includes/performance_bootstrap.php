<?php

define('PERF_START_TIME', microtime(true));
define('PERF_START_MEMORY', memory_get_usage());

require_once __DIR__ . '/advanced_cache.php';
require_once __DIR__ . '/preloader.php';
require_once __DIR__ . '/html_optimizer.php';
require_once __DIR__ . '/image_optimizer.php';
require_once __DIR__ . '/asset_minifier.php';
require_once __DIR__ . '/page_cache.php';
require_once __DIR__ . '/query_builder.php';
require_once __DIR__ . '/async_loader.php';

class PerformanceBootstrap
{
    private static $instance = null;
    private $config = [];
    private $metrics = [];
    
    private function __construct()
    {
        $this->config = [
            'page_cache' => true,
            'page_cache_ttl' => 300,
            'html_minify' => true,
            'lazy_images' => true,
            'critical_css' => true,
            'async_components' => true,
            'debug_bar' => false
        ];
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function init($config = [])
    {
        $instance = self::getInstance();
        $instance->config = array_merge($instance->config, $config);
        return $instance;
    }
    
    public function start()
    {
        if ($this->config['page_cache']) {
            $cached = start_page_cache($this->config['page_cache_ttl']);
            if ($cached === false) {
                return $this;
            }
        }
        
        if ($this->config['html_minify']) {
            html_optimizer()->startCapture();
        }
        
        return $this;
    }
    
    public function end()
    {
        $html = '';
        
        if ($this->config['html_minify']) {
            $html = html_optimizer()->endCapture();
        }
        
        if ($this->config['debug_bar'] && ($_ENV['APP_DEBUG'] ?? false)) {
            $html = $this->injectDebugBar($html);
        }
        
        echo $html;
        
        if ($this->config['page_cache']) {
            end_page_cache();
        }
    }
    
    public function renderHead()
    {
        $html = '';
        
        $preloader = preloader();
        $preloader->preconnect('https://fonts.googleapis.com');
        $preloader->preconnect('https://cdnjs.cloudflare.com');
        
        if ($this->config['critical_css']) {
            $preloader->setCriticalCSS(Preloader::getDefaultCriticalCSS());
        }
        
        $html .= $preloader->renderHead();
        
        if ($this->config['lazy_images']) {
            $html .= ImageOptimizer::getLazyLoadStyles();
        }
        
        if ($this->config['async_components']) {
            $html .= AsyncLoader::getSkeletonStyles();
        }
        
        return $html;
    }
    
    public function renderFooter()
    {
        $html = '';
        
        if ($this->config['lazy_images']) {
            $html .= ImageOptimizer::getLazyLoadScript();
        }
        
        if ($this->config['async_components']) {
            $html .= AsyncLoader::getLoaderScript();
        }
        
        $html .= preloader()->renderScripts();
        
        return $html;
    }
    
    public function getMetrics()
    {
        return [
            'execution_time_ms' => round((microtime(true) - PERF_START_TIME) * 1000, 2),
            'memory_usage_mb' => round((memory_get_usage() - PERF_START_MEMORY) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'cache_stats' => cache()->getStats()
        ];
    }
    
    private function injectDebugBar($html)
    {
        $metrics = $this->getMetrics();
        
        $debugBar = '<div id="perf-debug-bar" style="
            position:fixed;bottom:0;left:0;right:0;
            background:rgba(0,0,0,0.9);color:#fff;
            padding:8px 15px;font-family:monospace;font-size:12px;
            z-index:99999;display:flex;justify-content:space-between;align-items:center;
        ">';
        
        $timeColor = $metrics['execution_time_ms'] < 500 ? '#4CAF50' : ($metrics['execution_time_ms'] < 1000 ? '#FFC107' : '#F44336');
        $debugBar .= '<span>⏱️ <strong style="color:' . $timeColor . '">' . $metrics['execution_time_ms'] . 'ms</strong></span>';
        $debugBar .= '<span>💾 <strong>' . $metrics['memory_usage_mb'] . 'MB</strong></span>';
        $debugBar .= '<span>📦 Cache: <strong style="color:#4CAF50">' . $metrics['cache_stats']['hit_rate'] . '%</strong></span>';
        $debugBar .= '<button onclick="this.parentElement.remove()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:16px">×</button>';
        $debugBar .= '</div>';
        
        return str_replace('</body>', $debugBar . '</body>', $html);
    }
    
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
        return $this;
    }
    
    public function enableDebugBar()
    {
        $this->config['debug_bar'] = true;
        return $this;
    }
    
    public function disablePageCache()
    {
        $this->config['page_cache'] = false;
        return $this;
    }
}

function perf() {
    return PerformanceBootstrap::getInstance();
}

function perf_init($config = []) {
    return PerformanceBootstrap::init($config);
}

function perf_head() {
    return PerformanceBootstrap::getInstance()->renderHead();
}

function perf_footer() {
    return PerformanceBootstrap::getInstance()->renderFooter();
}
