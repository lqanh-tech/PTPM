<?php
// Script để thiết lập database
// Bao gồm file quản lý cấu hình để load biến môi trường
require_once 'config/ConfigManager.php';

// Khởi tạo ConfigManager để đảm bảo biến môi trường được load
$configManager = ConfigManager::getInstance();

// Bao gồm file database từ module
require_once 'administrator/elements_LQA/mod/database.php';

echo "Đang thiết lập database...\n";

try {
    // Kết nối đến database
    $db = Database::getInstance()->getConnection();
    
    // Tạo bảng news nếu chưa tồn tại
    $sql = "
    CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE,
        summary TEXT,
        content TEXT NOT NULL,
        featured_image VARCHAR(500),
        author VARCHAR(100) DEFAULT 'Admin',
        is_published BOOLEAN DEFAULT FALSE,
        published_date TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    ";
    
    $db->exec($sql);
    echo "✓ Bảng 'news' đã được tạo hoặc đã tồn tại.\n";
    
    // Tạo bảng banners nếu chưa tồn tại
    $bannersSql = "
    CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(500) NOT NULL,
        link_url VARCHAR(500),
        position INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    ";
    
    $db->exec($bannersSql);
    echo "✓ Bảng 'banners' đã được tạo hoặc đã tồn tại.\n";
    
    // Tạo bảng promotions nếu chưa tồn tại
    $promotionsSql = "
    CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        discount_percent DECIMAL(5,2),
        start_date DATE,
        end_date DATE,
        is_active BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    ";
    
    $db->exec($promotionsSql);
    echo "✓ Bảng 'promotions' đã được tạo hoặc đã tồn tại.\n";
    
    echo "\nThiết lập database hoàn tất!\n";
    
} catch (Exception $e) {
    echo "Lỗi khi thiết lập database: " . $e->getMessage() . "\n";
}