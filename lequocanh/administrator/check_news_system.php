<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Kiểm tra Hệ Thống Thêm Tin Tức</h1>";

echo "<h2>1. Kiểm tra Database Connection</h2>";
try {
    require_once __DIR__ . '/elements_LQA/mod/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>✓ Database connection OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. Kiểm tra Bảng News</h2>";
try {
    $result = $db->query("SHOW TABLES LIKE 'news'");
    if ($result->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Bảng news tồn tại</p>";
    } else {
        echo "<p style='color: red;'>✗ Bảng news không tồn tại!</p>";
        echo "<p>Vui lòng chạy SQL để tạo bảng:</p>";
        echo "<pre>
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        </pre>";
    }

    $columns = $db->query("DESCRIBE news")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Các cột:</p>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Kiểm tra NewsManager Class</h2>";
try {
    require_once __DIR__ . '/elements_LQA/mod/NewsManager.php';
    $newsManager = new NewsManager();
    echo "<p style='color: green;'>✓ NewsManager loaded OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>4. Test Thêm Tin Tức</h2>";

$testTitle = "Test News " . date('Y-m-d H:i:s');
$testContent = "Đây là nội dung test để kiểm tra tính năng thêm tin tức";

echo "<p><strong>Thêm:</strong></p>";
echo "<ul>";
echo "<li>Title: " . htmlspecialchars($testTitle) . "</li>";
echo "<li>Content: " . htmlspecialchars($testContent) . "</li>";
echo "<li>Author: Admin</li>";
echo "<li>Published: No</li>";
echo "</ul>";

$result = $newsManager->addNews($testTitle, $testContent, '', 'Admin', 0);

if ($result) {
    echo "<p style='color: green;'><strong>✓ Thêm tin tức thành công!</strong></p>";

    $stmt = $db->prepare("SELECT * FROM news WHERE title = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$testTitle]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($news) {
        echo "<p><strong>Dữ liệu vừa thêm:</strong></p>";
        echo "<table border='1' cellpadding='5'>";
        foreach ($news as $k => $v) {
            echo "<tr><td><strong>" . htmlspecialchars($k) . "</strong></td><td>" . htmlspecialchars($v ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'><strong>✗ Thêm tin tức thất bại!</strong></p>";
}

echo "<h2>5. Danh Sách Tin Tức Trong Database</h2>";
try {
    $stmt = $db->query("SELECT * FROM news ORDER BY id DESC LIMIT 10");
    $allNews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($allNews)) {
        echo "<p style='color: orange;'>⚠ Chưa có tin tức nào</p>";
    } else {
        echo "<p>Có " . count($allNews) . " tin tức</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr style='background: #ccc;'><th>ID</th><th>Title</th><th>Author</th><th>Published</th><th>Created</th></tr>";
        foreach ($allNews as $news) {
            echo "<tr>";
            echo "<td>" . $news['id'] . "</td>";
            echo "<td>" . htmlspecialchars($news['title']) . "</td>";
            echo "<td>" . $news['author'] . "</td>";
            echo "<td>" . ($news['is_published'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $news['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Log Lỗi PHP (Error Log)</h2>";
$logFile = __DIR__ . '/../../../../logs/error.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto;'>";
    echo implode("", $lastLines);
    echo "</pre>";
} else {
    echo "<p>Log file not found: $logFile</p>";
}

echo "<hr>";
echo "<p><a href='elements_LQA/madmin/marketing_content.php?tab=news'>← Quay lại Marketing</a></p>";
