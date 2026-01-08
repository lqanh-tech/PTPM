<?php

require_once __DIR__ . '/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Kiểm tra và tạo bảng Marketing</h2>";

$tables = [
    'banners' => "CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(500) NOT NULL,
        link_url VARCHAR(500),
        position INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    'news' => "CREATE TABLE IF NOT EXISTS news (
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
    )",

    'promotions' => "CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        discount_percent DECIMAL(5,2),
        start_date DATE,
        end_date DATE,
        is_active BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $name => $sql) {
    try {
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Bảng <strong>$name</strong> đã được tạo/kiểm tra</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Lỗi với bảng <strong>$name</strong>: " . $e->getMessage() . "</p>";
    }
}

$uploadsDir = __DIR__ . '/uploads';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
    echo "<p style='color: green;'>✓ Tạo thư mục uploads</p>";
} else {
    echo "<p style='color: green;'>✓ Thư mục uploads đã tồn tại</p>";
}

echo "<h3>Thiết lập hoàn tất!</h3>";
echo "<p><a href='../../../index.php'>Quay lại trang chủ</a></p>";
