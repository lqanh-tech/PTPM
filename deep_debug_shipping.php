<?php
/**
 * Deep debug - Tìm chính xác nguyên nhân duplicate
 */
session_start();
$_SESSION['cart_weight'] = 2.5;
$_SESSION['cart_total'] = 500000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();

echo "=== DEEP DEBUG - TÌM NGUYÊN NHÂN DUPLICATE ===\n\n";

// Test 1: Kiểm tra bảng shipping_methods
echo "TEST 1: Kiểm tra bảng shipping_methods\n";
echo str_repeat("-", 80) . "\n";
$stmt = $db->query("SELECT id, code, name, sort_order FROM shipping_methods WHERE is_active = 1 ORDER BY id");
$directMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Số bản ghi trong bảng: " . count($directMethods) . "\n";
foreach ($directMethods as $m) {
    echo "  ID:{$m['id']} Code:{$m['code']} Name:{$m['name']} Sort:{$m['sort_order']}\n";
}

// Test 2: Kiểm tra query với JOIN
echo "\nTEST 2: Query với LEFT JOIN shipping_fees\n";
echo str_repeat("-", 80) . "\n";
$stmt = $db->query("
    SELECT 
        sm.id, sm.code, sm.name, sm.sort_order,
        COUNT(DISTINCT sf.id) as fee_count
    FROM shipping_methods sm
    LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
    WHERE sm.is_active = 1
    GROUP BY sm.id, sm.code, sm.name, sm.sort_order
    ORDER BY sm.sort_order DESC
");
$joinMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Số bản ghi sau JOIN: " . count($joinMethods) . "\n";
foreach ($joinMethods as $m) {
    echo "  ID:{$m['id']} Code:{$m['code']} Name:{$m['name']} Sort:{$m['sort_order']} Fees:{$m['fee_count']}\n";
}

// Test 3: Kiểm tra có bản ghi nào có cùng code không
echo "\nTEST 3: Kiểm tra duplicate code\n";
echo str_repeat("-", 80) . "\n";
$stmt = $db->query("
    SELECT code, COUNT(*) as count 
    FROM shipping_methods 
    WHERE is_active = 1 
    GROUP BY code 
    HAVING COUNT(*) > 1
");
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($duplicates) > 0) {
    echo "⚠️ PHÁT HIỆN DUPLICATE TRONG DATABASE!\n";
    foreach ($duplicates as $dup) {
        echo "  Code '{$dup['code']}' xuất hiện {$dup['count']} lần\n";
    }
} else {
    echo "✅ Không có duplicate trong database\n";
}

// Test 4: Đọc file shipping_method_selector_v2.php và đếm số lần foreach
echo "\nTEST 4: Kiểm tra file PHP\n";
echo str_repeat("-", 80) . "\n";
$fileContent = file_get_contents('lequocanh/administrator/elements_LQA/mgiohang/shipping_method_selector_v2.php');
$foreachCount = substr_count($fileContent, 'foreach ($shippingMethods as');
echo "Số lần foreach trong file: {$foreachCount}\n";
if ($foreachCount > 1) {
    echo "⚠️ CÓ NHIỀU HƠN 1 FOREACH - ĐÂY CÓ THỂ LÀ NGUYÊN NHÂN!\n";
}

// Test 5: Kiểm tra xem có include/require file này nhiều lần không
echo "\nTEST 5: Kiểm tra checkout.php\n";
echo str_repeat("-", 80) . "\n";
$checkoutContent = file_get_contents('lequocanh/administrator/elements_LQA/mgiohang/checkout.php');
$includeCount = substr_count($checkoutContent, 'shipping_method_selector');
echo "Số lần include shipping_method_selector: {$includeCount}\n";
if ($includeCount > 1) {
    echo "⚠️ FILE ĐƯỢC INCLUDE NHIỀU HƠN 1 LẦN!\n";
    
    // Tìm vị trí
    preg_match_all('/include.*shipping_method_selector.*\.php/', $checkoutContent, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[0] as $match) {
        $line = substr_count(substr($checkoutContent, 0, $match[1]), "\n") + 1;
        echo "  - Dòng {$line}: {$match[0]}\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "KẾT LUẬN:\n";
echo str_repeat("=", 80) . "\n";

if (count($duplicates) > 0) {
    echo "❌ Vấn đề: DATABASE có bản ghi trùng\n";
    echo "Giải pháp: Xóa bản ghi trùng trong database\n";
} elseif ($foreachCount > 1) {
    echo "❌ Vấn đề: File PHP có nhiều foreach loop\n";
    echo "Giải pháp: Xóa foreach thừa\n";
} elseif ($includeCount > 1) {
    echo "❌ Vấn đề: File được include nhiều lần\n";
    echo "Giải pháp: Xóa include thừa\n";
} else {
    echo "❓ Vấn đề: Có thể do JavaScript hoặc cache\n";
    echo "Giải pháp: Kiểm tra JavaScript và clear cache\n";
}
