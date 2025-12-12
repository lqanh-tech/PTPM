<?php
/**
 * Create v_shipping_fees_detail View
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔧 Đang tạo View v_shipping_fees_detail...\n\n";
    
    // Drop view if exists
    $db->exec("DROP VIEW IF EXISTS v_shipping_fees_detail");
    echo "✅ Xóa view cũ (nếu có)\n";
    
    // Create view
    $createViewSQL = "
    CREATE VIEW v_shipping_fees_detail AS
    SELECT 
        sf.id,
        sf.name,
        p.name AS province_name,
        p.code AS province_code,
        d.name AS district_name,
        d.code AS district_code,
        sm.name AS shipping_method_name,
        sm.code AS shipping_method_code,
        sf.base_fee,
        sf.weight_from,
        sf.weight_to,
        sf.fee_per_kg,
        sf.order_value_from,
        sf.order_value_to,
        sf.min_order_free_ship,
        sf.distance_from,
        sf.distance_to,
        sf.fee_per_km,
        sf.priority,
        sf.is_active,
        sf.created_at,
        sf.updated_at
    FROM shipping_fees sf
    LEFT JOIN provinces p ON sf.province_id = p.id
    LEFT JOIN districts d ON sf.district_id = d.id
    LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
    WHERE sf.is_active = 1
    ORDER BY sf.priority DESC, sf.id ASC
    ";
    
    $db->exec($createViewSQL);
    echo "✅ Tạo view v_shipping_fees_detail thành công!\n\n";
    
    // Test view
    echo "📊 Kiểm tra view:\n";
    $result = $db->query("SELECT * FROM v_shipping_fees_detail LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($result) > 0) {
        echo "✅ View hoạt động tốt, có " . count($result) . " bản ghi\n\n";
        
        echo "Dữ liệu mẫu:\n";
        foreach ($result as $row) {
            echo "  - {$row['name']} | ";
            echo "Tỉnh: " . ($row['province_name'] ?? 'Tất cả') . " | ";
            echo "Phương thức: " . ($row['shipping_method_name'] ?? 'Tất cả') . " | ";
            echo "Phí: " . number_format($row['base_fee'], 0, ',', '.') . "₫ | ";
            echo "Ưu tiên: {$row['priority']}\n";
        }
    } else {
        echo "⚠️  View không có dữ liệu\n";
    }
    
    // Also create v_shipping_zones_detail
    echo "\n🔧 Đang tạo View v_shipping_zones_detail...\n";
    
    $db->exec("DROP VIEW IF EXISTS v_shipping_zones_detail");
    
    $createZonesViewSQL = "
    CREATE VIEW v_shipping_zones_detail AS
    SELECT 
        sz.id,
        sz.name AS zone_name,
        p.name AS province_name,
        p.code AS province_code,
        d.name AS district_name,
        d.code AS district_code,
        sz.is_supported,
        sz.delivery_time_min,
        sz.delivery_time_max,
        sz.note,
        sz.is_active,
        sz.created_at,
        sz.updated_at
    FROM shipping_zones sz
    LEFT JOIN provinces p ON sz.province_id = p.id
    LEFT JOIN districts d ON sz.district_id = d.id
    WHERE sz.is_active = 1
    ORDER BY p.name ASC, d.name ASC
    ";
    
    $db->exec($createZonesViewSQL);
    echo "✅ Tạo view v_shipping_zones_detail thành công!\n\n";
    
    echo "✅ HOÀN THÀNH! Tất cả views đã được tạo.\n";
    
} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    exit(1);
}
