<?php

$files = [
    '../payment/admin_dashboard.php',
    '../customer/order_history.php',
    './elements_LQA/mUser/userUpdate.php',
    './elements_LQA/mthuonghieu/thuonghieuUpdate.php',
    './elements_LQA/mthuoctinh/thuoctinhView.php',
    './signUp.php',
    './quick_login.php',
    './performance_dashboard.php',
    './elements_LQA/msanphamnoibat/manageFeaturedView.php',
    './elements_LQA/mnhatkyhoatdong/thongKeNhanVienCaiThien.php',
    './elements_LQA/mnhanvien/nhanvienView.php',
    './elements_LQA/mnhanvien/nhanvienUpdate.php',
    './elements_LQA/mhinhanh/hinhanhView.php',
    './elements_LQA/mhanghoa/hanghoaView.php',
    './elements_LQA/mhanghoa/hanghoaUpdate.php',
];

$replacements = [

    [
        'pattern' => "/alert\s*\(\s*['\"]([^'\"]*)['\"]\\s*\)/",
        'replacement' => "Toast.error('$1')"
    ],

    [
        'pattern' => '/alert\s*\(\s*"([^"]*)"\s*\)/',
        'replacement' => 'Toast.error("$1")'
    ],

    [
        'pattern' => "/alert\s*\(\s*'✅([^']*)'\s*\)/",
        'replacement' => "Toast.success('$1')"
    ],
    [
        'pattern' => "/alert\s*\(\s*'❌([^']*)'\s*\)/",
        'replacement' => "Toast.error('$1')"
    ],
    [
        'pattern' => "/alert\s*\(\s*'⚠([^']*)'\s*\)/",
        'replacement' => "Toast.warning('$1')"
    ],
];

$stats = [
    'total_files' => 0,
    'updated_files' => 0,
    'total_replacements' => 0,
    'errors' => []
];

echo "=== BẮT ĐẦU THAY THẾ ALERT ===\n\n";

foreach ($files as $file) {
    $stats['total_files']++;
    $fullPath = __DIR__ . '/' . $file;
    
    if (!file_exists($fullPath)) {
        $stats['errors'][] = "File không tồn tại: $file";
        echo "❌ File không tồn tại: $file\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $originalContent = $content;
    $fileReplacements = 0;
    
    $hasToastCSS = strpos($content, 'toast-notification.css') !== false;
    $hasToastJS = strpos($content, 'toast-notification.js') !== false;
    
    if (!$hasToastCSS && !$hasToastJS) {

        if (preg_match('/<\/head>/i', $content)) {
            $content = preg_replace(
                '/<\/head>/i',
                '<link rel="stylesheet" href="css_LQA/toast-notification.css">' . "\n</head>",
                $content,
                1
            );
        }
        
        if (preg_match('/<script/i', $content)) {
            $content = preg_replace(
                '/<script/i',
                '<script src="js_LQA/toast-notification.js"></script>' . "\n<script",
                $content,
                1
            );
        } elseif (preg_match('/<\/body>/i', $content)) {
            $content = preg_replace(
                '/<\/body>/i',
                '<script src="js_LQA/toast-notification.js"></script>' . "\n</body>",
                $content,
                1
            );
        }
    }
    
    foreach ($replacements as $replacement) {
        $count = 0;
        $content = preg_replace(
            $replacement['pattern'],
            $replacement['replacement'],
            $content,
            -1,
            $count
        );
        $fileReplacements += $count;
    }
    
    if ($content !== $originalContent) {

        $backupPath = $fullPath . '.backup';
        copy($fullPath, $backupPath);
        
        file_put_contents($fullPath, $content);
        
        $stats['updated_files']++;
        $stats['total_replacements'] += $fileReplacements;
        
        echo "✅ Đã cập nhật: $file ($fileReplacements thay thế)\n";
        echo "   Backup: $file.backup\n";
    } else {
        echo "⏭️  Bỏ qua: $file (không có thay đổi)\n";
    }
}

echo "\n=== KẾT QUẢ ===\n";
echo "Tổng số file: {$stats['total_files']}\n";
echo "File đã cập nhật: {$stats['updated_files']}\n";
echo "Tổng số thay thế: {$stats['total_replacements']}\n";

if (!empty($stats['errors'])) {
    echo "\n=== LỖI ===\n";
    foreach ($stats['errors'] as $error) {
        echo "❌ $error\n";
    }
}

echo "\n=== LƯU Ý ===\n";
echo "1. File backup đã được tạo với đuôi .backup\n";
echo "2. Vui lòng kiểm tra các file đã cập nhật\n";
echo "3. Một số alert phức tạp có thể cần chỉnh sửa thủ công\n";
echo "4. Đảm bảo đường dẫn css_LQA/toast-notification.css và js_LQA/toast-notification.js đúng\n";
?>
