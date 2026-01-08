<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Setup Bảng News</h2>";

echo "<h3>Bước 1: Kiểm tra bảng cũ</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'news'");
    if ($stmt->rowCount() > 0) {
        echo "<p>Bảng news đã tồn tại. Kiểm tra cấu trúc...</p>";

        $stmt = $db->query("DESCRIBE news");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }

        if (!isset($columns['slug'])) {
            echo "<p style='color: orange;'>⚠ Cột slug không tồn tại. Thêm cột...</p>";
            $db->exec("ALTER TABLE news ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title");
            echo "<p style='color: green;'>✓ Đã thêm cột slug</p>";
        }

        if (!isset($columns['published_date'])) {
            echo "<p style='color: orange;'>⚠ Cột published_date không tồn tại. Thêm cột...</p>";
            $db->exec("ALTER TABLE news ADD COLUMN published_date TIMESTAMP NULL AFTER is_published");
            echo "<p style='color: green;'>✓ Đã thêm cột published_date</p>";
        }

        if (!isset($columns['featured_image'])) {
            echo "<p style='color: orange;'>⚠ Cột featured_image không tồn tại. Thêm cột...</p>";
            $db->exec("ALTER TABLE news ADD COLUMN featured_image VARCHAR(500) AFTER content");
            echo "<p style='color: green;'>✓ Đã thêm cột featured_image</p>";
        }

        if (!isset($columns['summary'])) {
            echo "<p style='color: orange;'>⚠ Cột summary không tồn tại. Thêm cột...</p>";
            $db->exec("ALTER TABLE news ADD COLUMN summary TEXT AFTER slug");
            echo "<p style='color: green;'>✓ Đã thêm cột summary</p>";
        }
    } else {
        echo "<p>Bảng news không tồn tại. Tạo bảng mới...</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}

echo "<h3>Bước 2: Tạo bảng news</h3>";
$sql = "CREATE TABLE IF NOT EXISTS news (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->exec($sql);
    echo "<p style='color: green;'>✓ Bảng news đã được tạo/kiểm tra</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Lỗi: " . $e->getMessage() . "</p>";
}

echo "<h3>Bước 3: Cấu trúc bảng news</h3>";
try {
    $stmt = $db->query("DESCRIBE news");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr style='background: #ccc;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Lỗi: " . $e->getMessage() . "</p>";
}

echo "<h3>Bước 4: Test thêm tin tức</h3>";
require_once __DIR__ . '/../../mod/NewsManager.php';
$newsManager = new NewsManager();

$testTitle = "Test Tin Tức " . date('Y-m-d H:i:s');
$testContent = "Nội dung test";

echo "<p>Thêm: Title = '" . $testTitle . "'</p>";

$result = $newsManager->addNews($testTitle, $testContent, '', 'Admin', 0);

if ($result) {
    echo "<p style='color: green;'>✓ Thêm tin tức thành công!</p>";
} else {
    echo "<p style='color: red;'>✗ Thêm tin tức thất bại!</p>";
}

echo "<hr>";
echo "<p><a href='marketing_content.php?tab=news'>← Quay lại Quản lý Marketing</a></p>";
