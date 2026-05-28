<?php

declare(strict_types=1);

namespace App\Services;

use Database;
use PDO;

class ShippingService
{
    private static ?self $instance = null;
    private PDO $db;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __clone() {}

    /**
     * Get all active shipping methods.
     */
    public function getActiveShippingMethods(): array
    {
        $sql = "SELECT id, code, name, description, price_multiplier, is_active, sort_order,
                       price_multiplier
                FROM shipping_methods 
                WHERE is_active = 1
                ORDER BY sort_order ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get shipping method by code.
     */
    public function getShippingMethodByCode(string $code): ?object
    {
        $sql = "SELECT id, code, name, description, price_multiplier, is_active, sort_order,
                       price_multiplier
                FROM shipping_methods 
                WHERE code = ? AND is_active = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Get shipping method by ID.
     */
    public function getShippingMethodById(int $id): ?object
    {
        $sql = "SELECT id, code, name, description, price_multiplier, is_active, sort_order,
                       price_multiplier
                FROM shipping_methods 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Get shipping fee rules for a method.
     */
    public function getShippingFees(int $methodId): array
    {
        $sql = "SELECT id, shipping_method_id, min_weight, max_weight, fee, 
                       min_order_value, max_order_value, priority, is_active
                FROM shipping_fees 
                WHERE shipping_method_id = ? AND is_active = 1
                ORDER BY priority DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$methodId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Calculate shipping fee for an order.
     */
    public function calculateShippingFee(string $methodCode, float $orderTotal, float $weight = 0): float
    {
        $method = $this->getShippingMethodByCode($methodCode);
        if (!$method) {
            return 0;
        }

        // Check for free shipping threshold
        if ($method->price_multiplier > 0 && $orderTotal >= $method->price_multiplier) {
            return 0;
        }

        // Check fee rules
        $fees = $this->getShippingFees((int) $method->id);
        foreach ($fees as $fee) {
            $weightMatch = ($weight >= $fee->min_weight && $weight <= $fee->max_weight);
            $orderMatch = ($orderTotal >= $fee->min_order_value && $orderTotal <= $fee->max_order_value);

            if ($weightMatch && $orderMatch) {
                return (float) $fee->fee;
            }
        }

        // Default to base fee
        return (float) ($method->price_multiplier ?? 0);
    }

    /**
     * Get all shipping methods with their fee rules.
     */
    public function getShippingMethodsWithFees(): array
    {
        try {
            $sql = "SELECT id, code, name, description, price_multiplier, is_active, sort_order,
                           price_multiplier
                    FROM v_shipping_methods_with_fees 
                    WHERE is_active = 1
                    ORDER BY sort_order ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            // View may not exist, fallback to regular methods
            return $this->getActiveShippingMethods();
        }
    }

    /**
     * Create shipping method.
     */
    public function createMethod(array $data): int
    {
        $sql = "INSERT INTO shipping_methods (code, name, description, price_multiplier, is_active, 
                                              sort_order, price_multiplier)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['code'],
            $data['name'],
            $data['description'] ?? '',
            $data['price_multiplier'] ?? 0,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0,
            $data['price_multiplier'] ?? 0,
            
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update shipping method.
     */
    public function updateMethod(int $methodId, array $data): bool
    {
        $allowedFields = ['name', 'description', 'price_multiplier', 'is_active', 'sort_order', 'price_multiplier', 'estimated_days'];
        $updateFields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateFields[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $params[] = $methodId;
        $sql = "UPDATE shipping_methods SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Add shipping fee rule.
     */
    public function addFeeRule(array $data): int
    {
        $sql = "INSERT INTO shipping_fees (shipping_method_id, min_weight, max_weight, fee,
                                           min_order_value, max_order_value, priority, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['shipping_method_id'],
            $data['min_weight'] ?? 0,
            $data['max_weight'] ?? 999999,
            $data['fee'],
            $data['min_order_value'] ?? 0,
            $data['max_order_value'] ?? 999999999,
            $data['priority'] ?? 0,
            $data['is_active'] ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get shipping method count.
     */
    public function getMethodCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM shipping_methods";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return (int) ($result->count ?? 0);
    }
}

if (!function_exists('getShippingService')) {
    function getShippingService(): ShippingService
    {
        return ShippingService::getInstance();
    }
}
