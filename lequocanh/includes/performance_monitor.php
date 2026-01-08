<?php

class PerformanceMonitor
{
    private static $instance = null;
    private $startTime;
    private $startMemory;
    private $queryCount = 0;
    private $cacheHits = 0;
    private $cacheMisses = 0;
    private $enabled = true;

    private function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function start()
    {
        return self::getInstance();
    }

    public function incrementQueryCount()
    {
        $this->queryCount++;
    }

    public function incrementCacheHit()
    {
        $this->cacheHits++;
    }

    public function incrementCacheMiss()
    {
        $this->cacheMisses++;
    }

    public function getExecutionTime()
    {
        return round((microtime(true) - $this->startTime) * 1000, 2);
    }

    public function getMemoryUsage()
    {
        return round((memory_get_usage() - $this->startMemory) / 1024 / 1024, 2);
    }

    public function getPeakMemory()
    {
        return round(memory_get_peak_usage() / 1024 / 1024, 2);
    }

    public function getQueryCount()
    {
        return $this->queryCount;
    }

    public function getCacheHitRate()
    {
        $total = $this->cacheHits + $this->cacheMisses;
        if ($total === 0) {
            return 0;
        }
        return round(($this->cacheHits / $total) * 100, 2);
    }

    public function getMetrics()
    {
        return [
            'execution_time_ms' => $this->getExecutionTime(),
            'memory_usage_mb' => $this->getMemoryUsage(),
            'peak_memory_mb' => $this->getPeakMemory(),
            'query_count' => $this->queryCount,
            'cache_hits' => $this->cacheHits,
            'cache_misses' => $this->cacheMisses,
            'cache_hit_rate' => $this->getCacheHitRate()
        ];
    }

    public function renderDebugBar()
    {
        if (!$this->enabled || !($_ENV['APP_DEBUG'] ?? false)) {
            return '';
        }

        $metrics = $this->getMetrics();
        
        $html = '<div id="performance-debug-bar" style="
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.9);
            color: #fff;
            padding: 8px 15px;
            font-family: monospace;
            font-size: 12px;
            z-index: 99999;
            display: flex;
            justify-content: space-between;
            align-items: center;
        ">';
        
        $html .= '<div style="display: flex; gap: 20px;">';
        
        $timeColor = $metrics['execution_time_ms'] < 500 ? '#4CAF50' : ($metrics['execution_time_ms'] < 1000 ? '#FFC107' : '#F44336');
        $html .= '<span>⏱️ <strong style="color: ' . $timeColor . '">' . $metrics['execution_time_ms'] . 'ms</strong></span>';
        
        $html .= '<span>💾 <strong>' . $metrics['memory_usage_mb'] . 'MB</strong> (peak: ' . $metrics['peak_memory_mb'] . 'MB)</span>';
        
        $queryColor = $metrics['query_count'] < 20 ? '#4CAF50' : ($metrics['query_count'] < 50 ? '#FFC107' : '#F44336');
        $html .= '<span>🔍 <strong style="color: ' . $queryColor . '">' . $metrics['query_count'] . '</strong> queries</span>';
        
        $cacheColor = $metrics['cache_hit_rate'] > 70 ? '#4CAF50' : ($metrics['cache_hit_rate'] > 40 ? '#FFC107' : '#F44336');
        $html .= '<span>📦 Cache: <strong style="color: ' . $cacheColor . '">' . $metrics['cache_hit_rate'] . '%</strong> (' . $metrics['cache_hits'] . '/' . ($metrics['cache_hits'] + $metrics['cache_misses']) . ')</span>';
        
        $html .= '</div>';
        
        $html .= '<button onclick="this.parentElement.style.display=\'none\'" style="
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
        ">×</button>';
        
        $html .= '</div>';
        
        return $html;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function enable()
    {
        $this->enabled = true;
    }
}

if (!function_exists('perf_start')) {
    function perf_start()
    {
        return PerformanceMonitor::start();
    }
}

if (!function_exists('perf_metrics')) {
    function perf_metrics()
    {
        return PerformanceMonitor::getInstance()->getMetrics();
    }
}

if (!function_exists('perf_debug_bar')) {
    function perf_debug_bar()
    {
        return PerformanceMonitor::getInstance()->renderDebugBar();
    }
}
