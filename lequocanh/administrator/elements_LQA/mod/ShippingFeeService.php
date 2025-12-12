<?php
/**
 * Shipping Fee Service
 * 
 * Handles calculation of shipping fees based on configuration in database
 * Supports hierarchical fee structure: District > Province > National
 */

require_once __DIR__ . '/database.php';

class ShippingFeeService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Calculate shipping fee based on location and order details
     * 
     * @param int $provinceId Destination province ID
     * @param int $districtId Destination district ID
     * @param float $weight Weight in grams
     * @param float $orderTotal Order total amount (for free ship check)
     * @return array Calculation result
     */
    public function calculateFee($provinceId, $districtId, $weight = 0, $orderTotal = 0)
    {
        // 1. Find applicable fee configuration
        // Priority: District specific > Province specific > National default
        $config = $this->findFeeConfig($provinceId, $districtId);

        if (!$config) {
            // Fallback if absolutely no config found (should not happen if default exists)
            return [
                'success' => true,
                'fee' => 30000, // Hard fallback
                'name' => 'Phí vận chuyển mặc định',
                'is_free' => false
            ];
        }

        // 2. Check for free shipping
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

        // 3. Calculate weight-based fee
        // Convert weight to kg (round up to nearest 0.5kg if needed, but here simple math)
        $weightKg = max(0, ($weight - 1000) / 1000); // Base fee usually covers first 1kg
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

    /**
     * Find the most specific fee configuration
     */
    private function findFeeConfig($provinceId, $districtId)
    {
        // 1. Try District Specific
        $sql = "SELECT * FROM shipping_fees WHERE province_id = ? AND district_id = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$provinceId, $districtId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }

        // 2. Try Province Specific
        $sql = "SELECT * FROM shipping_fees WHERE province_id = ? AND district_id IS NULL AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$provinceId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }

        // 3. Try National Default
        $sql = "SELECT * FROM shipping_fees WHERE province_id IS NULL AND district_id IS NULL AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }

        return null;
    }

    /**
     * Get all active shipping methods
     */
    public function getActiveMethods()
    {
        $sql = "SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
