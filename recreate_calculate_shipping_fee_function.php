<?php
/**
 * Tạo lại function calculate_shipping_fee với logic đúng
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "🔧 Đang tạo lại function calculate_shipping_fee...\n\n";

// Drop function cũ nếu có
try {
    $db->exec("DROP FUNCTION IF EXISTS calculate_shipping_fee");
    echo "✅ Đã xóa function cũ\n";
} catch (Exception $e) {
    echo "⚠️  Không thể xóa function cũ: " . $e->getMessage() . "\n";
}

// Tạo function mới
$createFunctionSQL = "
CREATE FUNCTION calculate_shipping_fee(
    p_method_id INT,
    p_province_id INT,
    p_district_id INT,
    p_weight DECIMAL(10,2),
    p_order_value DECIMAL(15,2)
)
RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN
    DECLARE v_base_fee DECIMAL(15,2) DEFAULT 0;
    DECLARE v_fee_per_kg DECIMAL(15,2) DEFAULT 0;
    DECLARE v_min_free_ship DECIMAL(15,2) DEFAULT 0;
    DECLARE v_price_multiplier DECIMAL(5,2) DEFAULT 1.0;
    DECLARE v_final_fee DECIMAL(15,2) DEFAULT 0;
    
    -- Lấy hệ số nhân giá từ shipping_methods
    SELECT price_multiplier INTO v_price_multiplier
    FROM shipping_methods
    WHERE id = p_method_id
    LIMIT 1;
    
    -- Lấy cấu hình phí phù hợp nhất (priority cao nhất)
    SELECT 
        base_fee,
        fee_per_kg,
        min_order_free_ship
    INTO 
        v_base_fee,
        v_fee_per_kg,
        v_min_free_ship
    FROM shipping_fees
    WHERE shipping_method_id = p_method_id
      AND is_active = 1
      AND (province_id IS NULL OR province_id = p_province_id)
      AND (district_id IS NULL OR district_id = p_district_id)
      AND (weight_from IS NULL OR weight_from <= p_weight)
      AND (weight_to IS NULL OR weight_to >= p_weight)
      AND (order_value_from IS NULL OR order_value_from <= p_order_value)
      AND (order_value_to IS NULL OR order_value_to >= p_order_value)
    ORDER BY priority DESC
    LIMIT 1;
    
    -- Tính phí
    SET v_final_fee = v_base_fee + (p_weight * v_fee_per_kg);
    
    -- Áp dụng hệ số nhân
    SET v_final_fee = v_final_fee * v_price_multiplier;
    
    -- Kiểm tra miễn phí ship
    IF v_min_free_ship > 0 AND p_order_value >= v_min_free_ship THEN
        SET v_final_fee = 0;
    END IF;
    
    RETURN v_final_fee;
END
";

try {
    $db->exec($createFunctionSQL);
    echo "✅ Đã tạo function calculate_shipping_fee thành công!\n\n";
    
    // Test function
    echo "🧪 Test function với giỏ hàng: 2.5kg, 500,000đ\n\n";
    
    $stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order DESC");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($methods as $m) {
        $stmt = $db->prepare("SELECT calculate_shipping_fee(?, 1, 1, 2.5, 500000) as fee");
        $stmt->execute([$m['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $fee = $result['fee'];
        
        echo "  {$m['name']}: " . number_format($fee, 0, ',', '.') . " đ\n";
    }
    
    echo "\n✅ HOÀN THÀNH!\n";
    
} catch (Exception $e) {
    echo "❌ Lỗi khi tạo function: " . $e->getMessage() . "\n";
    exit(1);
}
