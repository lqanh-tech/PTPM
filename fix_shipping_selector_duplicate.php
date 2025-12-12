<?php
/**
 * Fix duplicate shipping methods trong shipping_method_selector_v2.php
 */

$file = 'lequocanh/administrator/elements_LQA/mgiohang/shipping_method_selector_v2.php';

echo "=== FIX DUPLICATE SHIPPING METHODS ===\n\n";

if (!file_exists($file)) {
    die("❌ File không tồn tại: $file\n");
}

// Đọc nội dung file
$content = file_get_contents($file);

// Backup file gốc
$backupFile = $file . '.backup_' . date('YmdHis');
file_put_contents($backupFile, $content);
echo "✅ Đã backup file gốc: $backupFile\n\n";

// Tìm và thay thế query
$oldQuery = 'GROUP BY sm.id
    ORDER BY sm.sort_order DESC';

$newQuery = 'GROUP BY sm.id, sm.code, sm.name, sm.description, sm.delivery_time, sm.price_multiplier, sm.is_active, sm.sort_order, sm.created_at, sm.updated_at
    ORDER BY sm.sort_order DESC';

if (strpos($content, $oldQuery) !== false) {
    $content = str_replace($oldQuery, $newQuery, $content);
    echo "✅ Đã cập nhật GROUP BY clause\n";
} else {
    echo "⚠️ Không tìm thấy query cần fix\n";
}

// Thêm code loại bỏ duplicate sau khi fetch
$searchPattern = '$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);';
$replacement = '$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Loại bỏ duplicate nếu có
$uniqueMethods = [];
$seenCodes = [];
foreach ($shippingMethods as $method) {
    if (!in_array($method[\'code\'], $seenCodes)) {
        $uniqueMethods[] = $method;
        $seenCodes[] = $method[\'code\'];
    }
}
$shippingMethods = $uniqueMethods;
unset($uniqueMethods, $seenCodes);';

if (strpos($content, $searchPattern) !== false && strpos($content, 'Loại bỏ duplicate nếu có') === false) {
    $content = str_replace($searchPattern, $replacement, $content);
    echo "✅ Đã thêm code loại bỏ duplicate\n";
} else {
    echo "⚠️ Code loại bỏ duplicate đã tồn tại hoặc không tìm thấy vị trí\n";
}

// Ghi lại file
file_put_contents($file, $content);

echo "\n✅ HOÀN THÀNH!\n";
echo "File đã được cập nhật: $file\n";
echo "Backup: $backupFile\n\n";

echo "Hãy refresh trang checkout để xem kết quả.\n";
