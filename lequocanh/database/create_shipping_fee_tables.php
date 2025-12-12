<?php
/**
 * Create Shipping Fee Tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database config
if (file_exists(__DIR__ . '/db_config.php')) {
    $dbConfig = require __DIR__ . '/db_config.php';
} else {
    // Fallback
    $dbConfig = ['host' => 'mysql', 'user' => 'root', 'pass' => 'pw'];
}

$dsn = "mysql:host={$dbConfig['host']};dbname=sales_management;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to database.\n";
    
    // 1. Create shipping_methods table
    $sql = "
    CREATE TABLE IF NOT EXISTS shipping_methods (
        id INT PRIMARY KEY AUTO_INCREMENT,
        code VARCHAR(50) UNIQUE,
        name VARCHAR(100),
        description TEXT,
        delivery_time VARCHAR(100) COMMENT 'Thời gian giao hàng',
        price_multiplier DECIMAL(5,2) DEFAULT 1.0 COMMENT 'Hệ số nhân giá',
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "Created table: shipping_methods\n";
    
    // 2. Create shipping_fees table
    $sql = "
    CREATE TABLE IF NOT EXISTS shipping_fees (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) COMMENT 'Tên cấu hình',
        province_id INT,
        district_id INT,
        base_fee DECIMAL(15,2) DEFAULT 0 COMMENT 'Phí cơ bản',
        fee_per_kg DECIMAL(15,2) DEFAULT 0 COMMENT 'Phí theo kg',
        min_order_free_ship DECIMAL(15,2) DEFAULT 0 COMMENT 'Đơn hàng tối thiểu miễn phí ship',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
        FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "Created table: shipping_fees\n";
    
    // 3. Insert default shipping methods
    $methods = [
        ['standard', 'Giao hàng tiêu chuẩn', 'Giao hàng trong 3-5 ngày', '3-5 ngày', 1.0, 1],
        ['express', 'Giao hàng nhanh', 'Giao hàng trong 1-2 ngày', '1-2 ngày', 1.5, 2],
        ['economy', 'Giao hàng tiết kiệm', 'Giao hàng trong 5-7 ngày', '5-7 ngày', 0.8, 3]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO shipping_methods (code, name, description, delivery_time, price_multiplier, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($methods as $m) {
        $stmt->execute($m);
    }
    echo "Inserted default shipping methods.\n";
    
    // 4. Insert default shipping fee (National flat rate)
    // Base fee: 30,000 VND, Free ship for orders > 500,000 VND
    $stmt = $pdo->prepare("INSERT IGNORE INTO shipping_fees (name, base_fee, fee_per_kg, min_order_free_ship) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Mặc định toàn quốc', 30000, 5000, 500000]);
    echo "Inserted default shipping fee configuration.\n";
    
    echo "Done.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
