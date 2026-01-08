<?php

require_once __DIR__ . '/database.php';

class ShippingMethod
{
    private $db;
    
    const METHOD_STANDARD = 'standard';
    const METHOD_EXPRESS = 'express';
    const METHOD_PICKUP = 'pickup';
    const METHOD_GHN = 'ghn';
    
    private $defaultMethods = [
        'standard' => [
            'code' => 'standard',
            'name' => 'Giao hàng tiêu chuẩn',
            'description' => 'Giao hàng trong 3-5 ngày làm việc',
            'base_fee' => 25000,
            'estimated_days_min' => 3,
            'estimated_days_max' => 5,
            'icon' => 'fa-truck',
            'is_active' => true,
            'sort_order' => 1
        ],
        'express' => [
            'code' => 'express',
            'name' => 'Giao hàng nhanh',
            'description' => 'Giao hàng trong 1-2 ngày làm việc',
            'base_fee' => 45000,
            'estimated_days_min' => 1,
            'estimated_days_max' => 2,
            'icon' => 'fa-shipping-fast',
            'is_active' => true,
            'sort_order' => 2
        ],
        'pickup' => [
            'code' => 'pickup',
            'name' => 'Lấy tại cửa hàng',
            'description' => 'Đến lấy hàng tại cửa hàng - Miễn phí',
            'base_fee' => 0,
            'estimated_days_min' => 0,
            'estimated_days_max' => 1,
            'icon' => 'fa-store',
            'is_active' => true,
            'sort_order' => 3
        ],
        'ghn' => [
            'code' => 'ghn',
            'name' => 'Giao Hàng Nhanh (GHN)',
            'description' => 'Vận chuyển qua đối tác GHN',
            'base_fee' => 0,
            'estimated_days_min' => 1,
            'estimated_days_max' => 3,
            'icon' => 'fa-box',
            'is_active' => true,
            'sort_order' => 4
        ]
    ];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS shipping_methods (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                base_fee DECIMAL(15,2) DEFAULT 0,
                per_km_fee DECIMAL(15,2) DEFAULT 0,
                free_shipping_threshold DECIMAL(15,2) DEFAULT 0,
                estimated_days_min INT DEFAULT 1,
                estimated_days_max INT DEFAULT 5,
                icon VARCHAR(50) DEFAULT 'fa-truck',
                is_active TINYINT(1) DEFAULT 1,
                requires_address TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->exec($sql);
            
            $this->seedDefaultMethods();
            
        } catch (PDOException $e) {
            error_log("Error creating shipping_methods table: " . $e->getMessage());
        }
    }

    private function seedDefaultMethods()
    {
        try {
            foreach ($this->defaultMethods as $method) {
                $checkSql = "SELECT id FROM shipping_methods WHERE code = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$method['code']]);
                
                if (!$checkStmt->fetch()) {
                    $insertSql = "INSERT INTO shipping_methods 
                        (code, name, description, base_fee, estimated_days_min, estimated_days_max, icon, is_active, requires_address, sort_order)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insertStmt = $this->db->prepare($insertSql);
                    $insertStmt->execute([
                        $method['code'],
                        $method['name'],
                        $method['description'],
                        $method['base_fee'],
                        $method['estimated_days_min'],
                        $method['estimated_days_max'],
                        $method['icon'],
                        $method['is_active'] ? 1 : 0,
                        $method['code'] !== 'pickup' ? 1 : 0,
                        $method['sort_order']
                    ]);
                }
            }
        } catch (PDOException $e) {
            error_log("Error seeding shipping methods: " . $e->getMessage());
        }
    }

    public function getActiveMethods()
    {
        try {
            $sql = "SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting shipping methods: " . $e->getMessage());
            return array_values($this->defaultMethods);
        }
    }

    public function getMethodByCode($code)
    {
        try {
            $sql = "SELECT * FROM shipping_methods WHERE code = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$code]);
            $method = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $method ?: ($this->defaultMethods[$code] ?? null);
        } catch (PDOException $e) {
            error_log("Error getting shipping method: " . $e->getMessage());
            return $this->defaultMethods[$code] ?? null;
        }
    }

    public function calculateFee($methodCode, $params = [])
    {
        $method = $this->getMethodByCode($methodCode);
        
        if (!$method) {
            return [
                'success' => false,
                'message' => 'Phương thức vận chuyển không hợp lệ'
            ];
        }

        $baseFee = floatval($method['base_fee']);
        $totalFee = $baseFee;
        $freeShippingThreshold = floatval($method['free_shipping_threshold'] ?? 0);
        $orderTotal = floatval($params['order_total'] ?? 0);

        $isFreeShipping = false;
        if ($freeShippingThreshold > 0 && $orderTotal >= $freeShippingThreshold) {
            $isFreeShipping = true;
            $totalFee = 0;
        }

        if ($methodCode === self::METHOD_PICKUP) {
            $totalFee = 0;
            $isFreeShipping = true;
        }

        if (!$isFreeShipping && isset($params['distance_km']) && floatval($method['per_km_fee']) > 0) {
            $totalFee += floatval($params['distance_km']) * floatval($method['per_km_fee']);
        }

        return [
            'success' => true,
            'method_code' => $methodCode,
            'method_name' => $method['name'],
            'base_fee' => $baseFee,
            'total_fee' => round($totalFee),
            'total_fee_formatted' => number_format(round($totalFee), 0, ',', '.') . ' ₫',
            'is_free_shipping' => $isFreeShipping,
            'estimated_days_min' => $method['estimated_days_min'],
            'estimated_days_max' => $method['estimated_days_max'],
            'estimated_delivery' => $this->getEstimatedDeliveryDate($method),
            'requires_address' => $method['requires_address'] ?? true
        ];
    }

    private function getEstimatedDeliveryDate($method)
    {
        $minDays = intval($method['estimated_days_min']);
        $maxDays = intval($method['estimated_days_max']);
        
        $minDate = date('d/m/Y', strtotime("+{$minDays} weekdays"));
        $maxDate = date('d/m/Y', strtotime("+{$maxDays} weekdays"));
        
        if ($minDays === $maxDays) {
            return $minDate;
        }
        
        return "{$minDate} - {$maxDate}";
    }

    public function getPickupStoreInfo()
    {
        return [
            'name' => 'Cửa hàng LQA',
            'address' => '123 Đường ABC, Quận 1, TP.HCM',
            'phone' => '0123 456 789',
            'working_hours' => '8:00 - 21:00 (Thứ 2 - Chủ nhật)',
            'map_url' => 'https://maps.google.com/?q=10.7721,106.6983'
        ];
    }

    public function updateMethod($code, $data)
    {
        try {
            $sql = "UPDATE shipping_methods SET 
                    name = ?, description = ?, base_fee = ?, 
                    per_km_fee = ?, free_shipping_threshold = ?,
                    estimated_days_min = ?, estimated_days_max = ?,
                    is_active = ?, sort_order = ?, updated_at = NOW()
                    WHERE code = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['description'],
                $data['base_fee'],
                $data['per_km_fee'] ?? 0,
                $data['free_shipping_threshold'] ?? 0,
                $data['estimated_days_min'],
                $data['estimated_days_max'],
                $data['is_active'] ? 1 : 0,
                $data['sort_order'] ?? 0,
                $code
            ]);
        } catch (PDOException $e) {
            error_log("Error updating shipping method: " . $e->getMessage());
            return false;
        }
    }

    public function toggleMethod($code, $isActive)
    {
        try {
            $sql = "UPDATE shipping_methods SET is_active = ? WHERE code = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$isActive ? 1 : 0, $code]);
        } catch (PDOException $e) {
            error_log("Error toggling shipping method: " . $e->getMessage());
            return false;
        }
    }
}
