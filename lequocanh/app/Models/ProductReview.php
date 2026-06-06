<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

/**
 * ProductReview model - handles rating/review queries.
 * Migrated from hanghoaCls.php rating methods.
 */
class ProductReview
{
    private static function db(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    /**
     * Get average rating and review count for a product.
     * Replaces hanghoaCls::getAverageRating()
     */
    public static function getAverageRating(int $idhanghoa): array
    {
        try {
            $db = self::db();
            $sql = "SELECT COALESCE(AVG(rating), 0) as avg_rating,
                           COUNT(*) as review_count
                    FROM product_reviews
                    WHERE product_id = ?
                    AND is_approved = 1
                    AND (status = 'approved' OR status IS NULL)";

            $stmt = $db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return [
                'average' => round((float)$result->avg_rating, 1),
                'count' => (int) $result->review_count
            ];
        } catch (\PDOException $e) {
            Logger::error('ProductReview::getAverageRating', ['error' => $e->getMessage()]);
            return ['average' => 0, 'count' => 0];
        }
    }
    
    /**
     * Get average ratings for multiple products (batch).
     * More efficient than calling getAverageRating() in a loop.
     */
    public static function getAverageRatingBatch(array $productIds): array
    {
        if (empty($productIds)) return [];
        
        try {
            $db = self::db();
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $sql = "SELECT product_id, 
                           COALESCE(AVG(rating), 0) as avg_rating,
                           COUNT(*) as review_count
                    FROM product_reviews
                    WHERE product_id IN ($placeholders)
                    AND is_approved = 1
                    AND (status = 'approved' OR status IS NULL)
                    GROUP BY product_id";

            $stmt = $db->prepare($sql);
            $stmt->execute($productIds);
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            $ratings = [];
            foreach ($results as $row) {
                $ratings[(int)$row->product_id] = [
                    'average' => round((float)$row->avg_rating, 1),
                    'count' => (int) $row->review_count
                ];
            }
            
            // Fill in defaults for products with no reviews
            foreach ($productIds as $id) {
                if (!isset($ratings[$id])) {
                    $ratings[$id] = ['average' => 0, 'count' => 0];
                }
            }
            
            return $ratings;
        } catch (\PDOException $e) {
            Logger::error('ProductReview::getAverageRatingBatch', ['error' => $e->getMessage()]);
            return array_fill_keys($productIds, ['average' => 0, 'count' => 0]);
        }
    }

    /**
     * Get review count for a product.
     * Replaces hanghoaCls::getReviewCount()
     */
    public static function getReviewCount(int $idhanghoa): int
    {
        try {
            $db = self::db();
            $sql = "SELECT COUNT(*) FROM product_reviews
                    WHERE product_id = ? AND is_approved = 1
                    AND (status = 'approved' OR status IS NULL)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            Logger::error('ProductReview::getReviewCount', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
