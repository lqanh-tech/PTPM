<?php
/**
 * Capture HTML output từ shipping_method_selector_v2.php
 */
session_start();
$_SESSION['cart_weight'] = 2.5;
$_SESSION['cart_total'] = 500000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

ob_start();
include 'lequocanh/administrator/elements_LQA/mgiohang/shipping_method_selector_v2.php';
$html = ob_get_clean();

echo "=== HTML OUTPUT FROM shipping_method_selector_v2.php ===\n\n";

// Đếm số lần xuất hiện của "Giao hàng tiêu chuẩn"
$count = substr_count($html, 'Giao hàng tiêu chuẩn');
echo "Số lần 'Giao hàng tiêu chuẩn' xuất hiện: {$count}\n\n";

// Đếm số <tr> trong tbody
preg_match_all('/<tr[^>]*class="shipping-method-row"/', $html, $matches);
$trCount = count($matches[0]);
echo "Số <tr> với class 'shipping-method-row': {$trCount}\n\n";

// Tìm tất cả DEBUG comments
preg_match_all('/<!-- DEBUG: (.*?) -->/', $html, $debugMatches);
if (count($debugMatches[1]) > 0) {
    echo "DEBUG COMMENTS:\n";
    foreach ($debugMatches[1] as $debug) {
        echo "  - {$debug}\n";
    }
    echo "\n";
}

// Lấy tất cả các radio button values
preg_match_all('/name="shipping_method"[^>]*value="([^"]+)"/', $html, $radioMatches);
if (count($radioMatches[1]) > 0) {
    echo "RADIO BUTTON VALUES:\n";
    foreach ($radioMatches[1] as $idx => $value) {
        echo "  " . ($idx + 1) . ". {$value}\n";
    }
    echo "\n";
}

// Kiểm tra duplicate
$values = $radioMatches[1];
$uniqueValues = array_unique($values);
if (count($values) !== count($uniqueValues)) {
    echo "⚠️ PHÁT HIỆN DUPLICATE TRONG HTML OUTPUT!\n";
    $duplicates = array_diff_assoc($values, $uniqueValues);
    echo "Các giá trị bị trùng: " . implode(', ', $duplicates) . "\n";
} else {
    echo "✅ Không có duplicate trong HTML output\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Nếu số <tr> > 4, nghĩa là có vấn đề trong PHP rendering\n";
echo "Nếu số <tr> = 4 nhưng trình duyệt vẫn hiển thị trùng, vấn đề là JavaScript\n";
