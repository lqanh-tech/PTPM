<?php
require_once 'administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "========================================\n";
echo "KIỂM TRA HÌNH ẢNH BANNER\n";
echo "========================================\n\n";

// Lấy tất cả banner
$stmt = $db->query("SELECT id, title, image_url FROM banners");
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($banners)) {
    echo "Không có banner nào trong database.\n";
} else {
    foreach ($banners as $banner) {
        echo "ID: {$banner['id']}\n";
        echo "Title: {$banner['title']}\n";
        echo "Image URL: {$banner['image_url']}\n";
        
        // Kiểm tra file có tồn tại không
        $imagePath = '/var/www/html' . $banner['image_url'];
        if (file_exists($imagePath)) {
            echo "✓ File tồn tại: $imagePath\n";
        } else {
            echo "✗ File KHÔNG tồn tại: $imagePath\n";
        }
        echo "---\n";
    }
}

echo "\n========================================\n";
echo "KIỂM TRA THỦ MỤC UPLOADS\n";
echo "========================================\n\n";

$uploadDir = '/var/www/html/lequocanh/administrator/uploads/';
echo "Thư mục: $uploadDir\n";
echo "Tồn tại: " . (file_exists($uploadDir) ? "Yes" : "No") . "\n";
echo "Quyền ghi: " . (is_writable($uploadDir) ? "Yes" : "No") . "\n\n";

echo "Danh sách file trong thư mục:\n";
$files = scandir($uploadDir);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "  - $file\n";
    }
}
