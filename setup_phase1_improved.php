<?php
/**
 * Setup Phase 1 - Improved Version
 * Chạy từng câu lệnh SQL một cách an toàn
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Phase 1</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:#27ae60;background:#d5f4e6;padding:10px;margin:5px 0;border-radius:5px;}";
echo ".error{color:#c0392b;background:#fadbd8;padding:10px;margin:5px 0;border-radius:5px;}";
echo ".info{color:#2980b9;background:#d6eaf8;padding:10px;margin:5px 0;border-radius:5px;}";
echo "h2{color:#2c3e50;margin-top:20px;}</style></head><body>";

echo "<h1>🚀 Setup Phase 1: Shipping System</h1>";

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $results = [];
    
    // 1. Create provinces table
    echo "<h2>1. Tạo bảng provinces</h2>";
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS provinces (
            id INT PRIMARY KEY AUTO_INCREMENT,
            code VARCHAR(10) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            name_en VARCHAR(100),
            region VARCHAR(50),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_code (code),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<div class='success'>✅ Tạo bảng provinces thành công</div>";
        $results[] = ['table' => 'provinces', 'status' => 'success'];
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Lỗi: " . $e->getMessage() . "</div>";
        $results[] = ['table' => 'provinces', 'status' => 'error', 'message' => $e->getMessage()];
    }
    
    // 2. Create districts table
    echo "<h2>2. Tạo bảng districts</h2>";
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS districts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            province_id INT NOT NULL,
            code VARCHAR(10) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            name_en VARCHAR(100),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
            INDEX idx_province (province_id),
            INDEX idx_code (code),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<div class='success'>✅ Tạo bảng districts thành công</div>";
        $results[] = ['table' => 'districts', 'status' => 'success'];
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Lỗi: " . $e->getMessage() . "</div>";
        $results[] = ['table' => 'districts', 'status' => 'error'];
    }
    
    // 3. Create wards table
    echo "<h2>3. Tạo bảng wards</h2>";
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS wards (
            id INT PRIMARY KEY AUTO_INCREMENT,
            district_id INT NOT NULL,
            code VARCHAR(10) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            name_en VARCHAR(100),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
            INDEX idx_district (district_id),
            INDEX idx_code (code),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<div class='success'>✅ Tạo bảng wards thành công</div>";
        $results[] = ['table' => 'wards', 'status' => 'success'];
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Lỗi: " . $e->getMessage() . "</div>";
        $results[] = ['table' => 'wards', 'status' => 'error'];
    }
    
    // 4. Create shipping_zones table
    echo "<h2>4. Tạo bảng shipping_zones</h2>";
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS shipping_zones (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            province_id INT,
            district_id INT,
            is_supported TINYINT(1) DEFAULT 1,
            delivery_time_min INT DEFAULT 24,
            delivery_time_max INT DEFAULT 72,
            note TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
            FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
            INDEX idx_province (province_id),
            INDEX idx_district (district_id),
            INDEX idx_supported (is_supported)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<div class='success'>✅ Tạo bảng shipping_zones thành công</div>";
        $results[] = ['table' => 'shipping_zones', 'status' => 'success'];
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Lỗi: " . $e->getMessage() . "</div>";
        $results[] = ['table' => 'shipping_zones', 'status' => 'error'];
    }
    
    // 5. Insert provinces data
    echo "<h2>5. Import dữ liệu 63 tỉnh/thành Việt Nam</h2>";
    try {
        $provinces = [
            ['HN', 'Hà Nội', 'Hanoi', 'Bắc'],
            ['HCM', 'Hồ Chí Minh', 'Ho Chi Minh', 'Nam'],
            ['DN', 'Đà Nẵng', 'Da Nang', 'Trung'],
            ['HP', 'Hải Phòng', 'Hai Phong', 'Bắc'],
            ['CT', 'Cần Thơ', 'Can Tho', 'Nam'],
            ['AG', 'An Giang', 'An Giang', 'Nam'],
            ['BR', 'Bà Rịa - Vũng Tàu', 'Ba Ria - Vung Tau', 'Nam'],
            ['BG', 'Bắc Giang', 'Bac Giang', 'Bắc'],
            ['BK', 'Bắc Kạn', 'Bac Kan', 'Bắc'],
            ['BL', 'Bạc Liêu', 'Bac Lieu', 'Nam'],
            ['BN', 'Bắc Ninh', 'Bac Ninh', 'Bắc'],
            ['BTH', 'Bến Tre', 'Ben Tre', 'Nam'],
            ['BD', 'Bình Định', 'Binh Dinh', 'Trung'],
            ['BDG', 'Bình Dương', 'Binh Duong', 'Nam'],
            ['BP', 'Bình Phước', 'Binh Phuoc', 'Nam'],
            ['BTN', 'Bình Thuận', 'Binh Thuan', 'Nam'],
            ['CM', 'Cà Mau', 'Ca Mau', 'Nam'],
            ['CB', 'Cao Bằng', 'Cao Bang', 'Bắc'],
            ['DL', 'Đắk Lắk', 'Dak Lak', 'Trung'],
            ['DNO', 'Đắk Nông', 'Dak Nong', 'Trung'],
            ['DB', 'Điện Biên', 'Dien Bien', 'Bắc'],
            ['DN2', 'Đồng Nai', 'Dong Nai', 'Nam'],
            ['DT', 'Đồng Tháp', 'Dong Thap', 'Nam'],
            ['GL', 'Gia Lai', 'Gia Lai', 'Trung'],
            ['HG', 'Hà Giang', 'Ha Giang', 'Bắc'],
            ['HNM', 'Hà Nam', 'Ha Nam', 'Bắc'],
            ['HT', 'Hà Tĩnh', 'Ha Tinh', 'Trung'],
            ['HD', 'Hải Dương', 'Hai Duong', 'Bắc'],
            ['HU', 'Hậu Giang', 'Hau Giang', 'Nam'],
            ['HB', 'Hòa Bình', 'Hoa Binh', 'Bắc'],
            ['HY', 'Hưng Yên', 'Hung Yen', 'Bắc'],
            ['KH', 'Khánh Hòa', 'Khanh Hoa', 'Trung'],
            ['KG', 'Kiên Giang', 'Kien Giang', 'Nam'],
            ['KT', 'Kon Tum', 'Kon Tum', 'Trung'],
            ['LC', 'Lai Châu', 'Lai Chau', 'Bắc'],
            ['LD', 'Lâm Đồng', 'Lam Dong', 'Trung'],
            ['LS', 'Lạng Sơn', 'Lang Son', 'Bắc'],
            ['LC2', 'Lào Cai', 'Lao Cai', 'Bắc'],
            ['LA', 'Long An', 'Long An', 'Nam'],
            ['ND', 'Nam Định', 'Nam Dinh', 'Bắc'],
            ['NA', 'Nghệ An', 'Nghe An', 'Trung'],
            ['NB', 'Ninh Bình', 'Ninh Binh', 'Bắc'],
            ['NT', 'Ninh Thuận', 'Ninh Thuan', 'Trung'],
            ['PT', 'Phú Thọ', 'Phu Tho', 'Bắc'],
            ['PY', 'Phú Yên', 'Phu Yen', 'Trung'],
            ['QB', 'Quảng Bình', 'Quang Binh', 'Trung'],
            ['QN', 'Quảng Nam', 'Quang Nam', 'Trung'],
            ['QNG', 'Quảng Ngãi', 'Quang Ngai', 'Trung'],
            ['QNI', 'Quảng Ninh', 'Quang Ninh', 'Bắc'],
            ['QT', 'Quảng Trị', 'Quang Tri', 'Trung'],
            ['ST', 'Sóc Trăng', 'Soc Trang', 'Nam'],
            ['SL', 'Sơn La', 'Son La', 'Bắc'],
            ['TN', 'Tây Ninh', 'Tay Ninh', 'Nam'],
            ['TB', 'Thái Bình', 'Thai Binh', 'Bắc'],
            ['TNG', 'Thái Nguyên', 'Thai Nguyen', 'Bắc'],
            ['TH', 'Thanh Hóa', 'Thanh Hoa', 'Trung'],
            ['TTH', 'Thừa Thiên Huế', 'Thua Thien Hue', 'Trung'],
            ['TG', 'Tiền Giang', 'Tien Giang', 'Nam'],
            ['TV', 'Trà Vinh', 'Tra Vinh', 'Nam'],
            ['TQ', 'Tuyên Quang', 'Tuyen Quang', 'Bắc'],
            ['VL', 'Vĩnh Long', 'Vinh Long', 'Nam'],
            ['VP', 'Vĩnh Phúc', 'Vinh Phuc', 'Bắc'],
            ['YB', 'Yên Bái', 'Yen Bai', 'Bắc']
        ];
        
        $stmt = $db->prepare("INSERT INTO provinces (code, name, name_en, region) VALUES (?, ?, ?, ?) 
                             ON DUPLICATE KEY UPDATE name=VALUES(name)");
        
        $count = 0;
        foreach ($provinces as $p) {
            $stmt->execute($p);
            $count++;
        }
        
        echo "<div class='success'>✅ Import thành công $count tỉnh/thành</div>";
        $results[] = ['action' => 'import_provinces', 'status' => 'success', 'count' => $count];
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Lỗi: " . $e->getMessage() . "</div>";
        $results[] = ['action' => 'import_provinces', 'status' => 'error'];
    }
    
    // Summary
    echo "<h2>📊 Tổng kết</h2>";
    $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
    $totalCount = count($results);
    
    echo "<div class='info'>";
    echo "<strong>Kết quả:</strong> $successCount/$totalCount thành công<br>";
    echo "<strong>Trạng thái:</strong> " . ($successCount === $totalCount ? "✅ HOÀN THÀNH" : "⚠️ CÓ LỖI") . "<br>";
    echo "</div>";
    
    echo "<div style='margin-top:20px;'>";
    echo "<a href='test_phase1_shipping.php' style='display:inline-block;padding:10px 20px;background:#3498db;color:white;text-decoration:none;border-radius:5px;'>🧪 Chạy Test Phase 1</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><h2>❌ LỖI NGHIÊM TRỌNG</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
}

echo "</body></html>";
?>
