<?php

require_once '../mod/database.php';

$results = [];
$errors = [];

echo "<h1>Kiểm tra hệ thống Marketing Content</h1>";

echo "<h2>1. Kiểm tra Database</h2>";
try {
    $db = Database::getInstance()->getConnection();
    $results[] = "✓ Kết nối database thành công";
} catch (Exception $e) {
    $errors[] = "✗ Lỗi kết nối database: " . $e->getMessage();
    echo "<div class='error'>" . end($errors) . "</div>";
    exit;
}

echo "<h2>2. Kiểm tra các bảng</h2>";
$tables = ['banners', 'news', 'promotions'];
$missingTables = [];

foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $results[] = "✓ Bảng '$table' đã tồn tại";
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $results[] = "&nbsp;&nbsp;&nbsp;→ Số bản ghi: $count";
        } else {
            $missingTables[] = $table;
            $errors[] = "✗ Bảng '$table' chưa tồn tại";
        }
    } catch (Exception $e) {
        $errors[] = "✗ Lỗi kiểm tra bảng '$table': " . $e->getMessage();
    }
}

if (!empty($missingTables)) {
    echo "<div class='warning'>";
    echo "<p>Các bảng sau chưa tồn tại: " . implode(', ', $missingTables) . "</p>";
    if (isset($_GET['create_tables'])) {
        echo "<h3>Đang tạo bảng...</h3>";
        
        $sqlFile = __DIR__ . '/../../../../database/create_banner_news_promotion_tables.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            try {
                $db->exec($sql);
                $results[] = "✓ Đã tạo tất cả các bảng thành công!";
                echo "<div class='success'>✓ Tạo bảng thành công! <a href='?'>Tải lại trang</a></div>";
            } catch (Exception $e) {
                $errors[] = "✗ Lỗi tạo bảng: " . $e->getMessage();
            }
        } else {
            echo "<p>Chạy SQL thủ công:</p>";
            echo "<pre>";
            echo "CREATE TABLE IF NOT EXISTS banners (
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

CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(500),
    author VARCHAR(100),
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

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
);";
            echo "</pre>";
        }
    } else {
        echo "<a href='?create_tables=1' class='btn btn-primary'>Tạo tất cả các bảng</a>";
    }
    echo "</div>";
}

echo "<h2>3. Kiểm tra thư mục Upload</h2>";
$uploadDir = __DIR__ . '/../../../administrator/uploads/';

if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        $results[] = "✓ Đã tạo thư mục uploads";
    } else {
        $errors[] = "✗ Không thể tạo thư mục uploads";
    }
} else {
    $results[] = "✓ Thư mục uploads tồn tại";
}

if (is_writable($uploadDir)) {
    $results[] = "✓ Thư mục có quyền ghi";
} else {
    $errors[] = "✗ Thư mục không có quyền ghi";
}

echo "<h2>4. Kiểm tra Manager Classes</h2>";
$managers = [
    'BannerManager' => '../mod/BannerManager.php',
    'NewsManager' => '../mod/NewsManager.php',
    'PromotionManager' => '../mod/PromotionManager.php'
];

foreach ($managers as $className => $filePath) {
    if (file_exists(__DIR__ . '/' . $filePath)) {
        $results[] = "✓ File $className tồn tại";
        try {
            require_once $filePath;
            $manager = new $className();
            $results[] = "&nbsp;&nbsp;&nbsp;→ Khởi tạo thành công";
        } catch (Exception $e) {
            $errors[] = "✗ Lỗi khởi tạo $className: " . $e->getMessage();
        }
    } else {
        $errors[] = "✗ File $className không tồn tại";
    }
}

echo "<h2>5. Cấu hình PHP Upload</h2>";
$phpConfig = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'file_uploads' => ini_get('file_uploads') ? 'On' : 'Off'
];

foreach ($phpConfig as $key => $value) {
    $results[] = "$key: $value";
}

echo "<h2>Kết quả kiểm tra</h2>";
echo "<div class='results'>";
foreach ($results as $result) {
    echo "<div class='success'>$result</div>";
}
foreach ($errors as $error) {
    echo "<div class='error'>$error</div>";
}
echo "</div>";

echo "<hr>";
echo "<h2>Kết luận</h2>";
if (empty($errors)) {
    echo "<div class='success'><strong>✓ Hệ thống đã sẵn sàng!</strong></div>";
    echo "<p>Bạn có thể bắt đầu sử dụng các chức năng:</p>";
    echo "<ul>";
    echo "<li><a href='banners.php'>Quản lý Banner</a></li>";
    echo "<li><a href='news.php'>Quản lý Tin tức</a></li>";
    echo "<li><a href='promotions.php'>Quản lý Chương trình Ưu đãi</a></li>";
    echo "</ul>";
} else {
    echo "<div class='error'><strong>✗ Có " . count($errors) . " lỗi cần khắc phục</strong></div>";
    echo "<p>Vui lòng xem các lỗi ở trên và khắc phục.</p>";
}

echo "<hr>";
echo "<p><a href='test_banner_upload.php'>Test Upload</a> | <a href='check_banner_setup.php'>Kiểm tra chi tiết Banner</a></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
}
h1 {
    color: #333;
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
}
h2 {
    color: #666;
    margin-top: 30px;
    border-bottom: 2px solid #ddd;
    padding-bottom: 5px;
}
h3 {
    color: #888;
}
.success {
    background: #d4edda;
    color: #155724;
    padding: 8px 12px;
    margin: 5px 0;
    border-radius: 4px;
    border-left: 4px solid #28a745;
}
.error {
    background: #f8d7da;
    color: #721c24;
    padding: 8px 12px;
    margin: 5px 0;
    border-radius: 4px;
    border-left: 4px solid #dc3545;
}
.warning {
    background: #fff3cd;
    color: #856404;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
    border-left: 4px solid #ffc107;
}
.results {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin: 10px 5px;
}
.btn:hover {
    background: #0056b3;
}
.btn-primary {
    background: #007bff;
}
pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
    border: 1px solid #ddd;
}
ul {
    list-style: none;
    padding: 0;
}
ul li {
    padding: 8px 0;
}
ul li a {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}
ul li a:hover {
    text-decoration: underline;
}
</style>
