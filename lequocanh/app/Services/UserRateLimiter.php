<?php

declare(strict_types=1);

namespace App\Services;

/**
 * User-based Rate Limiter
 * 
 * Usage:
 *   $limiter = new UserRateLimiter();
 *   if (!$limiter->check('api', $userId, 60, 60)) {
 *       // Rate limit exceeded
 *   }
 */
class UserRateLimiter
{
    private string $cacheDir;
    private int $cleanupChance;

    public function __construct(?string $cacheDir = null, int $cleanupChance = 100)
    {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../cache/ratelimit';
        $this->cleanupChance = $cleanupChance;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Check rate limit for user.
     *
     * @param string $action    Action name (e.g., 'api', 'login', 'search')
     * @param string $userId    User identifier (user ID, IP, or combination)
     * @param int    $maxAttempts Maximum attempts allowed
     * @param int    $timeWindow  Time window in seconds
     * @return bool True if within limit, false if exceeded
     */
    public function check(string $action, string $userId, int $maxAttempts = 60, int $timeWindow = 60): bool
    {
        // Probabilistic cleanup
        if (random_int(1, $this->cleanupChance) === 1) {
            $this->cleanup($timeWindow);
        }

        $key = $this->getKey($action, $userId);
        $file = $this->cacheDir . '/' . $key . '.json';
        $now = time();

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);

            if (!$data) {
                $data = $this->createEntry($now);
            } elseif ($now - $data['window_start'] > $timeWindow) {
                // Window expired, reset
                $data = $this->createEntry($now);
            } elseif ($data['attempts'] >= $maxAttempts) {
                // Rate limit exceeded
                $data['blocked_until'] = $data['window_start'] + $timeWindow;
                file_put_contents($file, json_encode($data));
                return false;
            } else {
                $data['attempts']++;
            }
        } else {
            $data = $this->createEntry($now);
        }

        file_put_contents($file, json_encode($data));
        return true;
    }

    /**
     * Get remaining attempts.
     */
    public function remaining(string $action, string $userId, int $maxAttempts = 60, int $timeWindow = 60): int
    {
        $key = $this->getKey($action, $userId);
        $file = $this->cacheDir . '/' . $key . '.json';

        if (!file_exists($file)) {
            return $maxAttempts;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data || time() - $data['window_start'] > $timeWindow) {
            return $maxAttempts;
        }

        return max(0, $maxAttempts - $data['attempts']);
    }

    /**
     * Get time until reset.
     */
    public function retryAfter(string $action, string $userId, int $timeWindow = 60): int
    {
        $key = $this->getKey($action, $userId);
        $file = $this->cacheDir . '/' . $key . '.json';

        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data) {
            return 0;
        }

        $resetTime = $data['window_start'] + $timeWindow;
        return max(0, $resetTime - time());
    }

    /**
     * Reset rate limit for user.
     */
    public function reset(string $action, string $userId): void
    {
        $key = $this->getKey($action, $userId);
        $file = $this->cacheDir . '/' . $key . '.json';

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Get rate limit info as headers.
     */
    public function getHeaders(string $action, string $userId, int $maxAttempts = 60, int $timeWindow = 60): array
    {
        return [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $this->remaining($action, $userId, $maxAttempts, $timeWindow),
            'X-RateLimit-Reset' => time() + $this->retryAfter($action, $userId, $timeWindow),
        ];
    }

    /**
     * Generate cache key.
     */
    private function getKey(string $action, string $userId): string
    {
        return 'rate_' . md5($action . ':' . $userId);
    }

    /**
     * Create new entry.
     */
    private function createEntry(int $now): array
    {
        return [
            'attempts' => 1,
            'window_start' => $now,
            'last_attempt' => $now,
        ];
    }

    /**
     * Clean up expired entries.
     */
    private function cleanup(int $timeWindow): void
    {
        $files = glob($this->cacheDir . '/rate_*.json');
        $now = time();

        foreach ($files as $file) {
            if ($now - filemtime($file) > $timeWindow * 2) {
                @unlink($file);
            }
        }
    }
}
