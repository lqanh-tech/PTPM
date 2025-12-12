<?php

/**
 * Rate Limiting Middleware
 * Phase 4 - Advanced rate limiting with Redis support
 */

class RateLimitMiddleware
{
    private $limit;
    private $window;
    private $useRedis;

    public function __construct()
    {
        $this->limit = (int)($_ENV['API_RATE_LIMIT'] ?? 100);
        $this->window = (int)($_ENV['API_RATE_WINDOW'] ?? 60);
        $this->useRedis = extension_loaded('redis') && class_exists('Redis');
    }

    public function handle()
    {
        $identifier = $this->getIdentifier();
        $key = "rate_limit:$identifier";

        if ($this->useRedis) {
            $this->handleRedisRateLimit($key);
        } else {
            $this->handleFileRateLimit($key);
        }
    }

    private function getIdentifier()
    {
        // Use user ID if authenticated, otherwise IP
        if (isset($_REQUEST['user']['id'])) {
            return 'user:' . $_REQUEST['user']['id'];
        }

        return 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function handleRedisRateLimit($key)
    {
        try {
            if (!class_exists('Redis')) {
                throw new Exception('Redis class not available');
            }

            $redisClass = 'Redis';
            $redis = new $redisClass();
            $redis->connect('redis', 6379);

            $current = $redis->incr($key);

            if ($current === 1) {
                $redis->expire($key, $this->window);
            }

            if ($current > $this->limit) {
                $this->rateLimitExceeded($current, $this->limit);
            }

            $this->addRateLimitHeaders($current, $this->limit);
        } catch (Exception $e) {
            // Fallback to file-based rate limiting
            $this->handleFileRateLimit($key);
        }
    }

    private function handleFileRateLimit($key)
    {
        $cacheDir = __DIR__ . '/../../../cache/rate_limit';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $file = $cacheDir . '/' . md5($key);
        $now = time();

        $data = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true) ?: [];
        }

        // Clean old entries
        $data = array_filter($data, function ($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window;
        });

        // Add current request
        $data[] = $now;

        // Check limit
        if (count($data) > $this->limit) {
            $this->rateLimitExceeded(count($data), $this->limit);
        }

        // Save data
        file_put_contents($file, json_encode($data));

        $this->addRateLimitHeaders(count($data), $this->limit);
    }

    private function rateLimitExceeded($current, $limit)
    {
        http_response_code(429);
        header('Retry-After: ' . $this->window);

        echo json_encode([
            'error' => 'Rate limit exceeded',
            'message' => "Too many requests. Limit: $limit requests per {$this->window} seconds",
            'current' => $current,
            'limit' => $limit,
            'window' => $this->window
        ]);

        exit;
    }

    private function addRateLimitHeaders($current, $limit)
    {
        header("X-RateLimit-Limit: $limit");
        header("X-RateLimit-Remaining: " . max(0, $limit - $current));
        header("X-RateLimit-Reset: " . (time() + $this->window));
    }
}
