<?php
/**
 * Fix shipping_fees table - Add missing columns
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔧 Đang sửa bảng shipping_fees...\n\n";
    
    // Check existing columns first
    $existingCols = $db->query('SHOW COLUMNS FROM shipping_fees')->fetchAll(PDO::FETCH_COLUMN);
    
    // Add missing columns
    $alterQueries = [
        ['shipping_method_id', "ALTER TABLE shipping_fees ADD COLUMN shipping_method_id INT COMMENT 'Phương thức vận chuyển' AFTER district_id"],
        ['weight_from', "ALTER TABLE shipping_fees ADD COLUMN weight_from DECIMAL(10,2) DEFAULT 0 COMMENT 'Từ kg' AFTER base_fee"],
        ['weight_to', "ALTER TABLE shipping_fees ADD COLUMN weight_to DECIMAL(10,2) COMMENT 'Đến kg (NULL = không giới hạn)' AFTER weight_from"],
        ['order_value_from', "ALTER TABLE shipping_fees ADD COLUMN order_value_from DECIMAL(15,2) DEFAULT 0 COMMENT 'Từ giá trị đơn hàng' AFTER fee_per_kg"],
        ['order_value_to', "ALTER TABLE shipping_fees ADD COLUMN order_value_to DECIMAL(15,2) COMMENT 'Đến giá trị (NULL = không giới hạn)' AFTER order_value_from"],
        ['distance_from', "ALTER TABLE shipping_fees ADD COLUMN distance_from INT COMMENT 'Từ km' AFTER min_order_free_ship"],
        ['distance_to', "ALTER TABLE shipping_fees ADD COLUMN distance_to INT COMMENT 'Đến km' AFTER distance_from"],
        ['fee_per_km', "ALTER TABLE shipping_fees ADD COLUMN fee_per_km DECIMAL(15,2) DEFAULT 0 COMMENT 'Phí mỗi km' AFTER distance_to"],
        ['priority', "ALTER TABLE shipping_fees ADD COLUMN priority INT DEFAULT 0 COMMENT 'Độ ưu tiên (số cao hơn = ưu tiên hơn)' AFTER fee_per_km"]
    ];
    
    foreach ($alterQueries as list($colName, $query)) {
        if (in_array($colName, $existingCols)) {
            echo "⚠️  Cột '$colName' đã tồn tại, bỏ qua\n";
            continue;
        }
        
        try {
            $db->exec($query);
            echo "✅ Thêm cột '$colName'\n";
        } catch (Exception $e) {
            echo "❌ Lỗi khi thêm '$colName': " . $e->getMessage() . "\n";
        }
    }
    
    // Add foreign key if not exists
    try {
        $db->exec("ALTER TABLE shipping_fees ADD CONSTRAINT fk_shipping_fee_method FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE");
        echo "✅ Thêm foreign key shipping_method_id\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠️  Foreign key đã tồn tại\n";
        } else {
            echo "⚠️  Không thể thêm foreign key: " . $e->getMessage() . "\n";
        }
    }
    
    // Add indexes
    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_shipping_fee_method ON shipping_fees(shipping_method_id)");
        echo "✅ Thêm index shipping_method_id\n";
    } catch (Exception $e) {
        echo "⚠️  Index có thể đã tồn tại\n";
    }
    
    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_shipping_fee_priority ON shipping_fees(priority)");
        echo "✅ Thêm index priority\n";
    } catch (Exception $e) {
        echo "⚠️  Index có thể đã tồn tại\n";
    }
    
    // Insert sample data
    echo "\n📝 Thêm dữ liệu mẫu...\n";
    
    $sampleData = [
        ['Phí cơ bản nội thành', null, null, 1, 30000, 0, 1, 0, 0, null, 500000, null, null, 0, 10],
        ['Phí cơ bản ngoại thành', null, null, 1, 50000, 0, 1, 0, 0, null, 1000000, null, null, 0, 5],
        ['Phí theo trọng lượng 1-5kg', null, null, 1, 30000, 1, 5, 10000, 0, null, null, null, null, 0, 8],
        ['Phí theo trọng lượng >5kg', null, null, 1, 30000, 5, null, 8000, 0, null, null, null, null, 0, 7]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO shipping_fees 
        (name, province_id, district_id, shipping_method_id, base_fee, weight_from, weight_to, 
         fee_per_kg, order_value_from, order_value_to, min_order_free_ship, distance_from, distance_to, fee_per_km, priority)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE name=VALUES(name)
    ");
    
    foreach ($sampleData as $data) {
        try {
            $stmt->execute($data);
            echo "✅ Thêm: {$data[0]}\n";
        } catch (Exception $e) {
            echo "⚠️  Có thể đã tồn tại: {$data[0]}\n";
        }
    }
    
    echo "\n✅ HOÀN THÀNH! Bảng shipping_fees đã được cập nhật.\n";
    echo "\n📊 Kiểm tra lại cấu trúc bảng:\n";
    
    $cols = $db->query('SHOW COLUMNS FROM shipping_fees')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    exit(1);
}
