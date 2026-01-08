<?php

require_once '../mod/database.php';

echo "<h2>Kiểm tra cấu hình Banner</h2>";

echo "<h3>1. Kiểm tra kết nối Database</h3>";
try {
    $db = Database::getInstance()->getConnection();
    echo "✓ Kết nối database thành công<br>";
} catch (Exception $e) {
    echo "✗ Lỗi kết nối database: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>2. Kiểm tra bảng banners</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'banners'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng 'banners' đã tồn tại<br>";
        
        $stmt = $db->query("DESCRIBE banners");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } else {
        echo "✗ Bảng 'banners' chưa tồn tại<br>";
        echo "<p>Bạn cần chạy file SQL: lequocanh/database/create_banner_news_promotion_tables.sql</p>";
        
        echo "<h4>Tạo bảng tự động?</h4>";
        if (isset($_GET['create_table'])) {
            $sql = "CREATE TABLE IF NOT EXISTS banners (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                image_url VARCHAR(500) NOT NULL,
                link_url VARCHAR(500),
                position INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            if ($db->exec($sql) !== false) {
                echo "✓ Tạo bảng 'banners' thành công!<br>";
            } else {
                echo "✗ Lỗi tạo bảng<br>";
            }
        } else {
            echo "<a href='?create_table=1' class='btn btn-primary'>Tạo bảng ngay</a>";
        }
    }
} catch (Exception $e) {
    echo "✗ Lỗi kiểm tra bảng: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Kiểm tra thư mục upload</h3>";
$uploadDir = __DIR__ . '/../../../administrator/uploads/';
echo "Đường dẫn: " . $uploadDir . "<br>";

if (!file_exists($uploadDir)) {
    echo "✗ Thư mục không tồn tại<br>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "✓ Đã tạo thư mục thành công<br>";
    } else {
        echo "✗ Không thể tạo thư mục. Vui lòng tạo thủ công<br>";
    }
} else {
    echo "✓ Thư mục tồn tại<br>";
}

if (is_writable($uploadDir)) {
    echo "✓ Thư mục có quyền ghi<br>";
} else {
    echo "✗ Thư mục không có quyền ghi. Cần chmod 755 hoặc 777<br>";
}

echo "<h3>4. Kiểm tra BannerManager</h3>";
if (file_exists(__DIR__ . '/../mod/BannerManager.php')) {
    echo "✓ File BannerManager.php tồn tại<br>";
    require_once '../mod/BannerManager.php';
    try {
        $bannerManager = new BannerManager();
        echo "✓ Khởi tạo BannerManager thành công<br>";
        
        $banners = $bannerManager->getAllBanners();
        echo "✓ Số lượng banner hiện tại: " . count($banners) . "<br>";
    } catch (Exception $e) {
        echo "✗ Lỗi khởi tạo BannerManager: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ File BannerManager.php không tồn tại<br>";
}

echo "<h3>5. Kiểm tra cấu hình PHP Upload</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";

echo "<hr>";
echo "<h3>Kết luận</h3>";
echo "<p>Nếu tất cả các mục đều có dấu ✓, hệ thống banner đã sẵn sàng.</p>";
echo "<p><a href='banners.php'>Quay lại quản lý Banner</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 20px; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
.btn:hover { background: #0056b3; }
</style>";
?>
