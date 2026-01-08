<?php

require_once __DIR__ . '/database.php';

class ShippingFeeService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function calculateFee($provinceId, $districtId, $weight = 0, $orderTotal = 0)
    {

        $config = $this->findFeeConfig($provinceId, $districtId);

        if (!$config) {

            return [
                'success' => true,
                'fee' => 30000,
                'name' => 'Phí vận chuyển mặc định',
                'is_free' => false
            ];
        }

        error_log("Shipping Calc: OrderTotal=$orderTotal, MinFree=" . $config['min_order_free_ship']);
        if ($config['min_order_free_ship'] > 0 && $orderTotal >= $config['min_order_free_ship']) {
            return [
                'success' => true,
                'fee' => 0,
                'name' => $config['name'],
                'is_free' => true,
                'message' => 'Miễn phí vận chuyển cho đơn hàng trên ' . number_format($config['min_order_free_ship']) . 'đ'
            ];
        }

        $weightKg = max(0, ($weight - 1000) / 1000);
        $weightFee = 0;
        
        if ($weightKg > 0 && $config['fee_per_kg'] > 0) {
            $weightFee = ceil($weightKg) * $config['fee_per_kg'];
        }

        $totalFee = $config['base_fee'] + $weightFee;

        return [
            'success' => true,
            'fee' => $totalFee,
            'base_fee' => $config['base_fee'],
            'weight_fee' => $weightFee,
            'name' => $config['name'],
            'is_free' => false
        ];
    }

    private function findFeeConfig($provinceId, $districtId)
    {

        $sql = "SELECT * FROM shipping_fees WHERE province_id = ? AND district_id = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$provinceId, $districtId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }

        $sql = "SELECT * FROM shipping_fees WHERE province_id = ? AND district_id IS NULL AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$provinceId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }

        $sql = "SELECT * FROM shipping_fees WHERE province_id IS NULL AND district_id IS NULL AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }

        return null;
    }

    public function getActiveMethods()
    {
        $sql = "SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
