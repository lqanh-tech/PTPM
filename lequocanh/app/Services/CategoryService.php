<?php

require_once __DIR__ . '/../../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../../cache/QueryCache.php';

class CategoryService
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

    public function getAllCategories()
    {
        $sql = "SELECT idloaihang, tenloaihang, mota, hinhanh 
                FROM loaihang 
                ORDER BY tenloaihang ASC";
        
        return $this->cache->query($this->db, $sql, [], 600);
    }

    public function getCategoryById($categoryId)
    {
        $sql = "SELECT idloaihang, tenloaihang, mota, hinhanh 
                FROM loaihang 
                WHERE idloaihang = ?";
        
        return $this->cache->queryOne($this->db, $sql, [$categoryId], 600);
    }

    public function getCategoriesWithProductCount()
    {
        $sql = "SELECT l.idloaihang, l.tenloaihang, l.mota, l.hinhanh,
                       COUNT(h.idhanghoa) as product_count
                FROM loaihang l
                LEFT JOIN hanghoa h ON l.idloaihang = h.idloaihang
                GROUP BY l.idloaihang, l.tenloaihang, l.mota, l.hinhanh
                ORDER BY l.tenloaihang ASC";
        
        return $this->cache->query($this->db, $sql, [], 600);
    }

    public function invalidateCategoryCache()
    {
        $this->cache->invalidateProducts();
    }
}

if (!function_exists('getCategoryService')) {
    function getCategoryService()
    {
        return CategoryService::getInstance();
    }
}
