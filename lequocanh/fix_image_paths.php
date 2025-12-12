<?php
/**
 * Sửa đường dẫn ảnh trong database
 */

require_once 'administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "========================================\n";
echo "SỬA ĐƯỜNG DẪN ẢNH\n";
echo "========================================\n\n";

// Sửa banners
echo "1. Sửa đường dẫn Banner...\n";
$stmt = $db->query("SELECT id, image_url FROM banners WHERE image_url NOT LIKE '/lequocanh/%'");
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($banners as $banner) {
    $oldPath = $banner['image_url'];
    $newPath = '/lequocanh' . $oldPath;
    
    $updateStmt = $db->prepare("UPDATE banners SET image_url = ? WHERE id = ?");
    $updateStmt->execute([$newPath, $banner['id']]);
    
    echo "  ID {$banner['id']}: $oldPath -> $newPath\n";
}
echo "  ✓ Đã sửa " . count($banners) . " banner\n\n";

// Sửa news
echo "2. Sửa đường dẫn News...\n";
$stmt = $db->query("SELECT id, image_url FROM news WHERE image_url IS NOT NULL AND image_url != '' AND image_url NOT LIKE '/lequocanh/%'");
$newsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($newsList as $news) {
    $oldPath = $news['image_url'];
    $newPath = '/lequocanh' . $oldPath;
    
    $updateStmt = $db->prepare("UPDATE news SET image_url = ? WHERE id = ?");
    $updateStmt->execute([$newPath, $news['id']]);
    
    echo "  ID {$news['id']}: $oldPath -> $newPath\n";
}
echo "  ✓ Đã sửa " . count($newsList) . " tin tức\n\n";

echo "========================================\n";
echo "✅ HOÀN THÀNH!\n";
echo "========================================\n\n";

// Kiểm tra lại
echo "Kiểm tra lại đường dẫn:\n";
$stmt = $db->query("SELECT id, title, image_url FROM banners");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Banner #{$row['id']}: {$row['image_url']}\n";
}
