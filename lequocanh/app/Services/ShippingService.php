<?php

require_once __DIR__ . '/../../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../../cache/QueryCache.php';

class ShippingService
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

    public function getActiveShippingMethods()
    {
        $sql = "SELECT id, code, name, description, base_fee, is_active, sort_order,
                       min_order_free_ship, estimated_days
                FROM shipping_methods 
                WHERE is_active = 1
                ORDER BY sort_order DESC";
        
        return $this->cache->query($this->db, $sql, [], 600);
    }

    public function getShippingMethodByCode($code)
    {
        $sql = "SELECT id, code, name, description, base_fee, is_active, sort_order,
                       min_order_free_ship, estimated_days
                FROM shipping_methods 
                WHERE code = ?";
        
        return $this->cache->queryOne($this->db, $sql, [$code], 600);
    }

    public function getShippingMethodById($id)
    {
        $sql = "SELECT id, code, name, description, base_fee, is_active, sort_order,
                       min_order_free_ship, estimated_days
                FROM shipping_methods 
                WHERE id = ?";
        
        return $this->cache->queryOne($this->db, $sql, [$id], 600);
    }

    public function getShippingFees($methodId)
    {
        $sql = "SELECT id, shipping_method_id, min_weight, max_weight, fee, 
                       min_order_value, max_order_value, priority, is_active
                FROM shipping_fees 
                WHERE shipping_method_id = ? AND is_active = 1
                ORDER BY priority DESC";
        
        return $this->cache->query($this->db, $sql, [$methodId], 600);
    }

    public function calculateShippingFee($methodCode, $orderTotal, $weight = 0)
    {
        $method = $this->getShippingMethodByCode($methodCode);
        if (!$method) {
            return 0;
        }

        if ($method->min_order_free_ship > 0 && $orderTotal >= $method->min_order_free_ship) {
            return 0;
        }

        $fees = $this->getShippingFees($method->id);
        foreach ($fees as $fee) {
            if ($weight >= $fee->min_weight && $weight <= $fee->max_weight) {
                if ($orderTotal >= $fee->min_order_value && $orderTotal <= $fee->max_order_value) {
                    return $fee->fee;
                }
            }
        }

        return $method->base_fee ?? 0;
    }

    public function getShippingMethodsWithFees()
    {
        $sql = "SELECT id, code, name, description, base_fee, is_active, sort_order,
                       min_order_free_ship, estimated_days
                FROM v_shipping_methods_with_fees 
                WHERE is_active = 1
                ORDER BY sort_order DESC";
        
        try {
            return $this->cache->query($this->db, $sql, [], 600);
        } catch (PDOException $e) {
            return $this->getActiveShippingMethods();
        }
    }

    public function invalidateShippingCache()
    {
        $this->cache->invalidateProducts();
    }
}

if (!function_exists('getShippingService')) {
    function getShippingService()
    {
        return ShippingService::getInstance();
    }
}
