<?php
require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

$db = new Database();

// Add improved getRelatedProducts method to hanghoaCls.php
$hanghoaFile = 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';
$content = file_get_contents($hanghoaFile);

// Find the existing getRelatedProducts method and replace it
$newMethod = '    /**
     * Get related products with intelligent multi-tier fallback system
     * 
     * @param int $idhanghoa Current product ID
     * @param int $limit Maximum number of related products to return
     * @return array Array of related product objects with recommendation type
     */
    public function getRelatedProducts($idhanghoa, $limit = 6)
    {
        try {
            // Get current product info
            $current = $this->HanghoaGetbyId($idhanghoa);

            if (!$current) {
                return [];
            }

            $results = [];
            $remaining = $limit;

            // Tier 1: Same category + same brand
            if ($remaining > 0) {
                $tier1 = $this->getRelatedProductsTier1($current, $remaining);
                foreach ($tier1 as $product) {
                    $product->recommendation_type = "same_category_brand";
                    $product->recommendation_title = "Cùng thương hiệu & danh mục";
                    $results[] = $product;
                }
                $remaining -= count($tier1);
            }

            // Tier 2: Same brand (different category)
            if ($remaining > 0) {
                $tier2 = $this->getRelatedProductsTier2($current, $remaining, array_column($results, "idhanghoa"));
                foreach ($tier2 as $product) {
                    $product->recommendation_type = "same_brand";
                    $product->recommendation_title = "Cùng thương hiệu";
                    $results[] = $product;
                }
                $remaining -= count($tier2);
            }

            // Tier 3: Same category (different brand)
            if ($remaining > 0) {
                $tier3 = $this->getRelatedProductsTier3($current, $remaining, array_column($results, "idhanghoa"));
                foreach ($tier3 as $product) {
                    $product->recommendation_type = "same_category";
                    $product->recommendation_title = "Cùng danh mục";
                    $results[] = $product;
                }
                $remaining -= count($tier3);
            }

            // Tier 4: Similar price range
            if ($remaining > 0) {
                $tier4 = $this->getRelatedProductsTier4($current, $remaining, array_column($results, "idhanghoa"));
                foreach ($tier4 as $product) {
                    $product->recommendation_type = "similar_price";
                    $product->recommendation_title = "Tầm giá tương tự";
                    $results[] = $product;
                }
                $remaining -= count($tier4);
            }

            // Tier 5: Best sellers (if still need more)
            if ($remaining > 0) {
                $tier5 = $this->getRelatedProductsTier5($current, $remaining, array_column($results, "idhanghoa"));
                foreach ($tier5 as $product) {
                    $product->recommendation_type = "bestseller";
                    $product->recommendation_title = "Sản phẩm bán chạy";
                    $results[] = $product;
                }
                $remaining -= count($tier5);
            }

            // Tier 6: Newest products (last resort)
            if ($remaining > 0) {
                $tier6 = $this->getRelatedProductsTier6($current, $remaining, array_column($results, "idhanghoa"));
                foreach ($tier6 as $product) {
                    $product->recommendation_type = "newest";
                    $product->recommendation_title = "Sản phẩm mới";
                    $results[] = $product;
                }
            }

            return array_slice($results, 0, $limit);
        } catch (PDOException $e) {
            error_log("Error getting related products: " . $e->getMessage());
            return [];
        }
    }

    private function getRelatedProductsTier1($current, $limit)
    {
        $sql = "SELECT h.* FROM hanghoa h
                WHERE h.idhanghoa != ? 
                AND h.idloaihang = ? 
                AND h.idThuongHieu = ?
                AND h.trang_thai != 2
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != \'\' THEN 0 ELSE 1 END,
                    h.tenhanghoa ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$current->idhanghoa, $current->idloaihang, $current->idThuongHieu, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getRelatedProductsTier2($current, $limit, $excludeIds = [])
    {
        $excludeClause = !empty($excludeIds) ? "AND h.idhanghoa NOT IN (" . implode(",", $excludeIds) . ")" : "";
        
        $sql = "SELECT h.* FROM hanghoa h
                WHERE h.idhanghoa != ? 
                AND h.idThuongHieu = ?
                AND h.idloaihang != ?
                AND h.trang_thai != 2
                {$excludeClause}
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != \'\' THEN 0 ELSE 1 END,
                    h.tenhanghoa ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$current->idhanghoa, $current->idThuongHieu, $current->idloaihang, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getRelatedProductsTier3($current, $limit, $excludeIds = [])
    {
        $excludeClause = !empty($excludeIds) ? "AND h.idhanghoa NOT IN (" . implode(",", $excludeIds) . ")" : "";
        
        $sql = "SELECT h.* FROM hanghoa h
                WHERE h.idhanghoa != ? 
                AND h.idloaihang = ?
                AND h.idThuongHieu != ?
                AND h.trang_thai != 2
                {$excludeClause}
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != \'\' THEN 0 ELSE 1 END,
                    h.tenhanghoa ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$current->idhanghoa, $current->idloaihang, $current->idThuongHieu, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getRelatedProductsTier4($current, $limit, $excludeIds = [])
    {
        $excludeClause = !empty($excludeIds) ? "AND h.idhanghoa NOT IN (" . implode(",", $excludeIds) . ")" : "";
        $priceMin = $current->giathamkhao * 0.5;
        $priceMax = $current->giathamkhao * 1.5;
        
        $sql = "SELECT h.* FROM hanghoa h
                WHERE h.idhanghoa != ? 
                AND h.giathamkhao BETWEEN ? AND ?
                AND h.trang_thai != 2
                {$excludeClause}
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != \'\' THEN 0 ELSE 1 END,
                    ABS(h.giathamkhao - ?) ASC,
                    h.tenhanghoa ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$current->idhanghoa, $priceMin, $priceMax, $current->giathamkhao, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getRelatedProductsTier5($current, $limit, $excludeIds = [])
    {
        $excludeClause = !empty($excludeIds) ? "AND h.idhanghoa NOT IN (" . implode(",", $excludeIds) . ")" : "";
        
        // Simulate bestsellers by products with reviews or random selection
        $sql = "SELECT h.* FROM hanghoa h
                LEFT JOIN (
                    SELECT ma_san_pham, COUNT(*) as review_count 
                    FROM product_reviews 
                    WHERE is_approved = 1 
                    GROUP BY ma_san_pham
                ) r ON h.idhanghoa = r.ma_san_pham
                WHERE h.idhanghoa != ? 
                AND h.trang_thai != 2
                {$excludeClause}
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != \'\' THEN 0 ELSE 1 END,
                    COALESCE(r.review_count, 0) DESC,
                    h.tenhanghoa ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$current->idhanghoa, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getRelatedProductsTier6($current, $limit, $excludeIds = [])
    {
        $excludeClause = !empty($excludeIds) ? "AND h.idhanghoa NOT IN (" . implode(",", $excludeIds) . ")" : "";
        
        $sql = "SELECT h.* FROM hanghoa h
                WHERE h.idhanghoa != ? 
                AND h.trang_thai != 2
                {$excludeClause}
                ORDER BY 
                    CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != \'\' THEN 0 ELSE 1 END,
                    h.idhanghoa DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$current->idhanghoa, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }';

// Replace the existing method
$pattern = '/public function getRelatedProducts\([^}]+\{[^}]+\}/s';
if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, $newMethod, $content);
} else {
    // If method doesn\'t exist, add it before the last closing brace
    $content = str_replace('}\n?>', $newMethod . "\n}\n?>", $content);
}

file_put_contents($hanghoaFile, $content);

echo "✅ Improved getRelatedProducts method has been added to hanghoaCls.php\n";
echo "✅ Multi-tier fallback system implemented:\n";
echo "   - Tier 1: Same category + same brand\n";
echo "   - Tier 2: Same brand (different category)\n";
echo "   - Tier 3: Same category (different brand)\n";
echo "   - Tier 4: Similar price range\n";
echo "   - Tier 5: Best sellers\n";
echo "   - Tier 6: Newest products\n";
?>