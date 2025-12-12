<?php
/**
 * Verify notification footer đã bị xóa
 */

echo "=== VERIFY NOTIFICATION FOOTER REMOVED ===\n\n";

$file = 'lequocanh/administrator/elements_LQA/mthongbao/customer_notification_widget.php';
$content = file_get_contents($file);

$tests = [
    'Không có notification-footer HTML' => [
        'check' => strpos($content, '<div class="notification-footer">') === false,
        'pass' => 'Đã xóa',
        'fail' => 'Vẫn còn'
    ],
    'Không có link "Xem lịch sử đơn hàng"' => [
        'check' => strpos($content, 'Xem lịch sử đơn hàng') === false,
        'pass' => 'Đã xóa',
        'fail' => 'Vẫn còn'
    ],
    'Không có CSS .notification-footer' => [
        'check' => strpos($content, '.notification-footer {') === false,
        'pass' => 'Đã xóa',
        'fail' => 'Vẫn còn'
    ],
    'Vẫn có notification-list' => [
        'check' => strpos($content, 'notification-list') !== false,
        'pass' => 'OK',
        'fail' => 'Bị xóa nhầm'
    ],
    'Vẫn có notification-header' => [
        'check' => strpos($content, 'notification-header') !== false,
        'pass' => 'OK',
        'fail' => 'Bị xóa nhầm'
    ]
];

$allPassed = true;
foreach ($tests as $name => $test) {
    if ($test['check']) {
        echo "✓ $name: {$test['pass']}\n";
    } else {
        echo "✗ $name: {$test['fail']}\n";
        $allPassed = false;
    }
}

echo "\n=== KẾT QUẢ ===\n";
if ($allPassed) {
    echo "✓✓✓ TẤT CẢ OK ✓✓✓\n";
    echo "Nút 'Xem tất cả' đã được xóa hoàn toàn!\n";
    echo "Dropdown thông báo chỉ còn:\n";
    echo "  - Header với 'Đánh dấu tất cả đã đọc'\n";
    echo "  - Danh sách thông báo\n";
    echo "  - Không có footer\n";
} else {
    echo "✗✗✗ CÓ LỖI ✗✗✗\n";
}
