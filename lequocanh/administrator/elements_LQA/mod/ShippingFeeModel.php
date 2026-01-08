<?php

require_once __DIR__ . '/database.php';

class ShippingFeeModel
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function getAll($activeOnly = true)
    {
        $sql = "SELECT * FROM v_shipping_fees_detail";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById($id)
    {
        $sql = "SELECT sf.*, 
                       p.name as province_name, 
                       d.name as district_name,
                       sm.name as shipping_method_name
                FROM shipping_fees sf
                LEFT JOIN provinces p ON sf.province_id = p.id
                LEFT JOIN districts d ON sf.district_id = d.id
                LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
                WHERE sf.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function calculateFee($params)
    {
        $provinceId = $params['province_id'] ?? null;
        $districtId = $params['district_id'] ?? null;
        $weight = $params['weight'] ?? 1;
        $orderValue = $params['order_value'] ?? 0;
        $shippingMethodId = $params['shipping_method_id'] ?? null;

        $sql = "SELECT * FROM shipping_fees 
                WHERE is_active = 1
                AND (province_id IS NULL OR province_id = ?)
                AND (district_id IS NULL OR district_id = ?)
                AND (shipping_method_id IS NULL OR shipping_method_id = ?)
                AND (weight_from IS NULL OR weight_from <= ?)
                AND (weight_to IS NULL OR weight_to >= ?)
                AND (order_value_from IS NULL OR order_value_from <= ?)
                AND (order_value_to IS NULL OR order_value_to >= ?)
                ORDER BY priority DESC, 
                         (CASE WHEN district_id IS NOT NULL THEN 3 
                               WHEN province_id IS NOT NULL THEN 2 
                               ELSE 1 END) DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $provinceId,
            $districtId,
            $shippingMethodId,
            $weight,
            $weight,
            $orderValue,
            $orderValue
        ]);

        $config = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$config) {

            return [
                'base_fee' => 30000,
                'weight_fee' => 0,
                'distance_fee' => 0,
                'total_fee' => 30000,
                'is_free_ship' => false,
                'config' => null,
                'message' => 'Sử dụng phí mặc định'
            ];
        }

        $baseFee = $config->base_fee ?? 0;
        $weightFee = ($config->fee_per_kg ?? 0) * $weight;
        $distanceFee = 0;

        $totalFee = $baseFee + $weightFee + $distanceFee;

        $isFreeShip = false;
        if ($config->min_order_free_ship && $orderValue >= $config->min_order_free_ship) {
            $isFreeShip = true;
            $totalFee = 0;
        }

        if ($shippingMethodId) {
            $methodSql = "SELECT price_multiplier FROM shipping_methods WHERE id = ?";
            $methodStmt = $this->conn->prepare($methodSql);
            $methodStmt->execute([$shippingMethodId]);
            $method = $methodStmt->fetch(PDO::FETCH_OBJ);
            
            if ($method && !$isFreeShip) {
                $totalFee = $totalFee * ($method->price_multiplier ?? 1.0);
            }
        }

        return [
            'base_fee' => $baseFee,
            'weight_fee' => $weightFee,
            'distance_fee' => $distanceFee,
            'total_fee' => round($totalFee, 0),
            'is_free_ship' => $isFreeShip,
            'config' => $config,
            'message' => $isFreeShip ? 'Miễn phí vận chuyển' : 'Phí vận chuyển áp dụng'
        ];
    }

    public function create($data)
    {
        $sql = "INSERT INTO shipping_fees (
                    name, province_id, district_id, shipping_method_id,
                    base_fee, weight_from, weight_to, fee_per_kg,
                    order_value_from, order_value_to, min_order_free_ship,
                    distance_from, distance_to, fee_per_km,
                    priority, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['province_id'] ?? null,
            $data['district_id'] ?? null,
            $data['shipping_method_id'] ?? null,
            $data['base_fee'] ?? 0,
            $data['weight_from'] ?? 0,
            $data['weight_to'] ?? null,
            $data['fee_per_kg'] ?? 0,
            $data['order_value_from'] ?? 0,
            $data['order_value_to'] ?? null,
            $data['min_order_free_ship'] ?? null,
            $data['distance_from'] ?? null,
            $data['distance_to'] ?? null,
            $data['fee_per_km'] ?? 0,
            $data['priority'] ?? 0,
            $data['is_active'] ?? 1
        ]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE shipping_fees SET
                    name = ?, province_id = ?, district_id = ?, shipping_method_id = ?,
                    base_fee = ?, weight_from = ?, weight_to = ?, fee_per_kg = ?,
                    order_value_from = ?, order_value_to = ?, min_order_free_ship = ?,
                    distance_from = ?, distance_to = ?, fee_per_km = ?,
                    priority = ?, is_active = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['province_id'] ?? null,
            $data['district_id'] ?? null,
            $data['shipping_method_id'] ?? null,
            $data['base_fee'] ?? 0,
            $data['weight_from'] ?? 0,
            $data['weight_to'] ?? null,
            $data['fee_per_kg'] ?? 0,
            $data['order_value_from'] ?? 0,
            $data['order_value_to'] ?? null,
            $data['min_order_free_ship'] ?? null,
            $data['distance_from'] ?? null,
            $data['distance_to'] ?? null,
            $data['fee_per_km'] ?? 0,
            $data['priority'] ?? 0,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    public function delete($id)
    {
        $sql = "UPDATE shipping_fees SET is_active = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function getByLocation($provinceId, $districtId = null)
    {
        $sql = "SELECT * FROM shipping_fees 
                WHERE is_active = 1
                AND (province_id IS NULL OR province_id = ?)";
        
        $params = [$provinceId];
        
        if ($districtId) {
            $sql .= " AND (district_id IS NULL OR district_id = ?)";
            $params[] = $districtId;
        }
        
        $sql .= " ORDER BY priority DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
