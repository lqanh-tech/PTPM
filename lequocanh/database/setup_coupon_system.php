<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

echo "<h2>🎫 Setup Hệ Thống Mã Giảm Giá (Coupon)</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h3>1. Tạo bảng coupons...</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã coupon (VD: SALE10, FREESHIP)',
        name VARCHAR(255) NOT NULL COMMENT 'Tên mã giảm giá',
        description TEXT COMMENT 'Mô tả chi tiết',
        
        discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent' COMMENT 'percent: giảm %, fixed: giảm tiền cố định',
        discount_value DECIMAL(15,2) NOT NULL COMMENT 'Giá trị giảm (% hoặc VNĐ)',
        
        max_discount DECIMAL(15,2) DEFAULT NULL COMMENT 'Giảm tối đa (chỉ áp dụng cho loại percent)',
        min_order_value DECIMAL(15,2) DEFAULT 0 COMMENT 'Giá trị đơn hàng tối thiểu để áp dụng',
        
        usage_limit INT DEFAULT NULL COMMENT 'Số lần sử dụng tối đa (NULL = không giới hạn)',
        usage_count INT DEFAULT 0 COMMENT 'Số lần đã sử dụng',
        usage_per_user INT DEFAULT 1 COMMENT 'Số lần mỗi user được dùng',
        
        start_date DATETIME DEFAULT NULL COMMENT 'Ngày bắt đầu hiệu lực',
        end_date DATETIME DEFAULT NULL COMMENT 'Ngày hết hạn',
        
        is_active TINYINT(1) DEFAULT 1 COMMENT '1: Hoạt động, 0: Tạm dừng',
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by VARCHAR(50) DEFAULT NULL COMMENT 'Admin tạo mã',
        
        INDEX idx_code (code),
        INDEX idx_active (is_active),
        INDEX idx_dates (start_date, end_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color:green'>✅ Bảng coupons đã được tạo!</p>";
    
    echo "<h3>2. Tạo bảng coupon_usage...</h3>";
    
    $sql2 = "CREATE TABLE IF NOT EXISTS coupon_usage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coupon_id INT NOT NULL,
        user_id VARCHAR(50) NOT NULL COMMENT 'Username người dùng',
        order_id INT NOT NULL COMMENT 'ID đơn hàng',
        discount_amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền được giảm',
        used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_coupon (coupon_id),
        INDEX idx_user (user_id),
        INDEX idx_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql2);
    echo "<p style='color:green'>✅ Bảng coupon_usage đã được tạo!</p>";
    
    echo "<h3>3. Thêm cột coupon vào bảng don_hang...</h3>";
    
    $columns = ['coupon_code', 'coupon_discount'];
    foreach ($columns as $column) {
        $check = $conn->query("SHOW COLUMNS FROM don_hang LIKE '$column'");
        if ($check->rowCount() == 0) {
            if ($column == 'coupon_code') {
                $conn->exec("ALTER TABLE don_hang ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL COMMENT 'Mã coupon đã áp dụng'");
            } else {
                $conn->exec("ALTER TABLE don_hang ADD COLUMN coupon_discount DECIMAL(15,2) DEFAULT 0 COMMENT 'Số tiền được giảm từ coupon'");
            }
            echo "<p style='color:green'>✅ Đã thêm cột $column vào bảng don_hang!</p>";
        } else {
            echo "<p style='color:blue'>ℹ️ Cột $column đã tồn tại.</p>";
        }
    }
    
    echo "<h3>4. Thêm dữ liệu mẫu...</h3>";
    
    $sampleCoupons = [
        [
            'code' => 'SALE10',
            'name' => 'Giảm 10%',
            'description' => 'Giảm 10% cho đơn hàng từ 200.000đ, tối đa 100.000đ',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'max_discount' => 100000,
            'min_order_value' => 200000,
            'usage_limit' => 100
        ],
        [
            'code' => 'GIAM50K',
            'name' => 'Giảm 50.000đ',
            'description' => 'Giảm 50.000đ cho đơn hàng từ 500.000đ',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'max_discount' => null,
            'min_order_value' => 500000,
            'usage_limit' => 50
        ],
        [
            'code' => 'NEWUSER20',
            'name' => 'Khách mới giảm 20%',
            'description' => 'Giảm 20% cho khách hàng mới, tối đa 200.000đ',
            'discount_type' => 'percent',
            'discount_value' => 20,
            'max_discount' => 200000,
            'min_order_value' => 100000,
            'usage_limit' => null
        ],
        [
            'code' => 'FREESHIP',
            'name' => 'Miễn phí vận chuyển',
            'description' => 'Giảm 30.000đ phí vận chuyển cho đơn từ 300.000đ',
            'discount_type' => 'fixed',
            'discount_value' => 30000,
            'max_discount' => null,
            'min_order_value' => 300000,
            'usage_limit' => 200
        ]
    ];
    
    $insertSql = "INSERT IGNORE INTO coupons (code, name, description, discount_type, discount_value, max_discount, min_order_value, usage_limit, start_date, end_date, is_active, created_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1, 'admin')";
    
    $stmt = $conn->prepare($insertSql);
    
    foreach ($sampleCoupons as $coupon) {
        try {
            $stmt->execute([
                $coupon['code'],
                $coupon['name'],
                $coupon['description'],
                $coupon['discount_type'],
                $coupon['discount_value'],
                $coupon['max_discount'],
                $coupon['min_order_value'],
                $coupon['usage_limit']
            ]);
            echo "<p style='color:green'>✅ Đã thêm mã: {$coupon['code']}</p>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "<p style='color:blue'>ℹ️ Mã {$coupon['code']} đã tồn tại.</p>";
            } else {
                throw $e;
            }
        }
    }
    
    echo "<h3>5. Danh sách mã giảm giá hiện có:</h3>";
    
    $coupons = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #28a745; color: white;'>
            <th>Mã</th>
            <th>Tên</th>
            <th>Loại</th>
            <th>Giá trị</th>
            <th>Đơn tối thiểu</th>
            <th>Đã dùng</th>
            <th>Trạng thái</th>
          </tr>";
    
    foreach ($coupons as $c) {
        $status = $c['is_active'] ? '<span style="color:green">✅ Hoạt động</span>' : '<span style="color:red">❌ Tắt</span>';
        $value = $c['discount_type'] == 'percent' 
            ? $c['discount_value'] . '%' . ($c['max_discount'] ? ' (max ' . number_format($c['max_discount']) . 'đ)' : '')
            : number_format($c['discount_value']) . 'đ';
        
        echo "<tr>
                <td><strong>{$c['code']}</strong></td>
                <td>{$c['name']}</td>
                <td>" . ($c['discount_type'] == 'percent' ? 'Giảm %' : 'Giảm tiền') . "</td>
                <td>{$value}</td>
                <td>" . number_format($c['min_order_value']) . "đ</td>
                <td>{$c['usage_count']}" . ($c['usage_limit'] ? "/{$c['usage_limit']}" : '') . "</td>
                <td>{$status}</td>
              </tr>";
    }
    echo "</table>";
    
    echo "<br><h3 style='color:green'>🎉 Setup hoàn tất!</h3>";
    echo "<p>Bạn có thể quản lý mã giảm giá tại: <a href='../administrator/index.php?req=coupon'>Admin > Mã giảm giá</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>
