<?php

declare(strict_types=1);

namespace App\Services;

use Database;
use PDO;

/**
 * Database-backed Rate Limiter
 *
 * Falls back to file-based if DB unavailable.
 * Usage:
 *   $limiter = new DbRateLimiter();
 *   if (!$limiter->check('api', 'user123', 60, 60)) {
 *       // Rate limit exceeded
 *   }
 */
class DbRateLimiter
{
    private ?PDO $db;
    private UserRateLimiter $fallback;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
            $this->ensureTable();
        } catch (\Exception $e) {
            $this->db = null;
        }

        $this->fallback = new UserRateLimiter();
    }

    /**
     * Check rate limit.
     */
    public function check(string $action, string $userId, int $maxAttempts = 60, int $timeWindow = 60): bool
    {
        if (!$this->db) {
            return $this->fallback->check($action, $userId, $maxAttempts, $timeWindow);
        }

        try {
            $key = $this->getKey($action, $userId);
            $now = time();
            $windowStart = $now - $timeWindow;

            // Clean old entries periodically
            if (random_int(1, 100) === 1) {
                $this->cleanup($timeWindow);
            }

            // Count attempts in current window
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as cnt FROM rate_limits WHERE action_key = ? AND created_at > ?"
            );
            $stmt->execute([$key, date('Y-m-d H:i:s', $windowStart)]);
            $count = (int) $stmt->fetchColumn();

            if ($count >= $maxAttempts) {
                return false;
            }

            // Record this attempt
            $stmt = $this->db->prepare(
                "INSERT INTO rate_limits (action_key, created_at) VALUES (?, ?)"
            );
            $stmt->execute([$key, date('Y-m-d H:i:s', $now)]);

            return true;
        } catch (\Exception $e) {
            // Fallback to file-based on DB error
            return $this->fallback->check($action, $userId, $maxAttempts, $timeWindow);
        }
    }

    /**
     * Get remaining attempts.
     */
    public function remaining(string $action, string $userId, int $maxAttempts = 60, int $timeWindow = 60): int
    {
        if (!$this->db) {
            return $this->fallback->remaining($action, $userId, $maxAttempts, $timeWindow);
        }

        try {
            $key = $this->getKey($action, $userId);
            $windowStart = time() - $timeWindow;

            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM rate_limits WHERE action_key = ? AND created_at > ?"
            );
            $stmt->execute([$key, date('Y-m-d H:i:s', $windowStart)]);
            $count = (int) $stmt->fetchColumn();

            return max(0, $maxAttempts - $count);
        } catch (\Exception $e) {
            return $this->fallback->remaining($action, $userId, $maxAttempts, $timeWindow);
        }
    }

    /**
     * Get time until window resets.
     */
    public function retryAfter(string $action, string $userId, int $timeWindow = 60): int
    {
        if (!$this->db) {
            return $this->fallback->retryAfter($action, $userId, $timeWindow);
        }

        try {
            $key = $this->getKey($action, $userId);

            $stmt = $this->db->prepare(
                "SELECT MIN(created_at) FROM rate_limits WHERE action_key = ? AND created_at > ?"
            );
            $stmt->execute([$key, date('Y-m-d H:i:s', time() - $timeWindow)]);
            $oldest = $stmt->fetchColumn();

            if (!$oldest) {
                return 0;
            }

            $resetTime = strtotime($oldest) + $timeWindow;
            return max(0, $resetTime - time());
        } catch (\Exception $e) {
            return $this->fallback->retryAfter($action, $userId, $timeWindow);
        }
    }

    /**
     * Reset rate limit for user/action.
     */
    public function reset(string $action, string $userId): void
    {
        if (!$this->db) {
            $this->fallback->reset($action, $userId);
            return;
        }

        try {
            $key = $this->getKey($action, $userId);
            $this->db->prepare("DELETE FROM rate_limits WHERE action_key = ?")->execute([$key]);
        } catch (\Exception $e) {
            $this->fallback->reset($action, $userId);
        }
    }

    /**
     * Get rate limit headers.
     */
    public function getHeaders(string $action, string $userId, int $maxAttempts = 60, int $timeWindow = 60): array
    {
        return [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $this->remaining($action, $userId, $maxAttempts, $timeWindow),
            'X-RateLimit-Reset' => time() + $this->retryAfter($action, $userId, $timeWindow),
        ];
    }

    private function getKey(string $action, string $userId): string
    {
        return "{$action}:{$userId}";
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                action_key VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_action_key (action_key, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function cleanup(int $timeWindow): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - $timeWindow * 2);
        $this->db->prepare("DELETE FROM rate_limits WHERE created_at < ?")->execute([$cutoff]);
    }
}
