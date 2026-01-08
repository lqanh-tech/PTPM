<?php

require_once __DIR__ . '/../../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../../cache/QueryCache.php';

class ProductService
{
    private static $instance = null;
    private $db;
    private $cache;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->cache = QueryCache::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getProductsByCategory($categoryId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai, hinhanh, idloaihang, mota, idThuongHieu, trang_thai
                FROM hanghoa 
                WHERE idloaihang = ?
                ORDER BY idhanghoa DESC
                LIMIT ? OFFSET ?";
        
        return $this->cache->query($this->db, $sql, [$categoryId, $limit, $offset], 300);
    }

    public function getAllProducts($limit = 100, $offset = 0)
    {
        $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai, hinhanh, idloaihang, mota, idThuongHieu, trang_thai
                FROM hanghoa 
                ORDER BY idhanghoa DESC
                LIMIT ? OFFSET ?";
        
        return $this->cache->query($this->db, $sql, [$limit, $offset], 300);
    }

    public function getProductById($productId)
    {
        $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai, hinhanh, idloaihang, mota, idThuongHieu, trang_thai
                FROM hanghoa 
                WHERE idhanghoa = ?";
        
        return $this->cache->queryOne($this->db, $sql, [$productId], 300);
    }

    public function getProductImage($imageId)
    {
        $sql = "SELECT id, duong_dan, ten_file FROM hinhanh WHERE id = ?";
        return $this->cache->queryOne($this->db, $sql, [$imageId], 600);
    }

    public function getProductRating($productId)
    {
        $sql = "SELECT 
                    COALESCE(AVG(rating), 0) as average,
                    COUNT(*) as count
                FROM product_reviews 
                WHERE product_id = ? AND status = 'approved'";
        
        $result = $this->cache->queryOne($this->db, $sql, [$productId], 180);
        
        return [
            'average' => round($result->average ?? 0, 1),
            'count' => $result->count ?? 0
        ];
    }

    public function getRelatedProducts($productId, $limit = 4)
    {
        $product = $this->getProductById($productId);
        if (!$product) {
            return [];
        }

        $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai, hinhanh, idloaihang, idThuongHieu
                FROM hanghoa 
                WHERE idhanghoa != ? 
                AND (idloaihang = ? OR idThuongHieu = ?)
                ORDER BY 
                    CASE WHEN idThuongHieu = ? THEN 0 ELSE 1 END,
                    RAND()
                LIMIT ?";
        
        return $this->cache->query(
            $this->db, 
            $sql, 
            [$productId, $product->idloaihang, $product->idThuongHieu, $product->idThuongHieu, $limit], 
            300
        );
    }

    public function searchProducts($keyword, $limit = 20)
    {
        $keyword = '%' . $keyword . '%';
        $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai, hinhanh, idloaihang
                FROM hanghoa 
                WHERE tenhanghoa LIKE ? OR mota LIKE ?
                ORDER BY 
                    CASE WHEN tenhanghoa LIKE ? THEN 0 ELSE 1 END,
                    idhanghoa DESC
                LIMIT ?";
        
        return $this->cache->query($this->db, $sql, [$keyword, $keyword, $keyword, $limit], 180);
    }

    public function getDiscountedProducts($limit = 8)
    {
        $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai, hinhanh, idloaihang
                FROM hanghoa 
                WHERE giakhuyenmai > 0 AND giakhuyenmai < giathamkhao
                ORDER BY (giathamkhao - giakhuyenmai) / giathamkhao DESC
                LIMIT ?";
        
        return $this->cache->query($this->db, $sql, [$limit], 300);
    }

    public function getFeaturedProducts($limit = 8)
    {
        $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.idloaihang
                FROM hanghoa h
                INNER JOIN san_pham_noi_bat sp ON h.idhanghoa = sp.idhanghoa
                WHERE sp.is_active = 1 AND sp.end_date >= NOW()
                ORDER BY sp.priority DESC
                LIMIT ?";
        
        return $this->cache->query($this->db, $sql, [$limit], 300);
    }

    public function filterProducts($filters)
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['min_price'])) {
            $conditions[] = "giathamkhao >= ?";
            $params[] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $conditions[] = "giathamkhao <= ?";
            $params[] = $filters['max_price'];
        }

        if (!empty($filters['category'])) {
            $conditions[] = "idloaihang = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['min_rating'])) {
            $conditions[] = "idhanghoa IN (
                SELECT product_id FROM product_reviews 
                WHERE status = 'approved' 
                GROUP BY product_id 
                HAVING AVG(rating) >= ?
            )";
            $params[] = $filters['min_rating'] - 0.5;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai, hinhanh, idloaihang, idThuongHieu
                FROM hanghoa 
                {$whereClause}
                ORDER BY idhanghoa DESC
                LIMIT 100";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function invalidateProductCache($productId = null)
    {
        $this->cache->invalidateProducts();
    }

    public function getCacheStats()
    {
        return $this->cache->getStats();
    }
}

if (!function_exists('getProductService')) {
    function getProductService()
    {
        return ProductService::getInstance();
    }
}
