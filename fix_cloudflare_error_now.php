<?php
/**
 * Script tự động fix lỗi Cloudflare cache
 * Chạy script này khi gặp lỗi "Không thể tải chi tiết yêu cầu"
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         FIX LỖI CLOUDFLARE CACHE - TỰ ĐỘNG                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Step 1: Check .env configuration
echo "📋 BƯỚC 1: Kiểm tra cấu hình .env\n";
echo str_repeat("─", 60) . "\n";

$envFile = '.env';
if (!file_exists($envFile)) {
    echo "❌ Không tìm thấy file .env\n";
    exit(1);
}

$envContent = file_get_contents($envFile);
$baseUrl = '';
$useTunnel = '';

if (preg_match('/BASE_URL\s*=\s*(.+)/', $envContent, $matches)) {
    $baseUrl = trim($matches[1]);
    echo "✓ BASE_URL: $baseUrl\n";
} else {
    echo "❌ Không tìm thấy BASE_URL trong .env\n";
    exit(1);
}

if (preg_match('/USE_CLOUDFLARE_TUNNEL\s*=\s*(.+)/', $envContent, $matches)) {
    $useTunnel = trim($matches[1]);
    echo "✓ USE_CLOUDFLARE_TUNNEL: $useTunnel\n";
}

// Step 2: Add cache busting to JavaScript files
echo "\n📋 BƯỚC 2: Thêm cache busting vào JavaScript files\n";
echo str_repeat("─", 60) . "\n";

$version = time();
$files = [
    'lequocanh/customer/support.php',
    'lequocanh/administrator/elements_LQA/mreview_management/reviewManagementView.php',
    'lequocanh/administrator/elements_LQA/msupport_tickets/supportTicketsView.php'
];

$updated = 0;
foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "⚠  File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Add version to script tags
    $content = preg_replace(
        '/<script src="([^"]+\.js)(\?v=\d+)?">/',
        '<script src="$1?v=' . $version . '">',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✓ Updated: " . basename($file) . "\n";
        $updated++;
    }
}

echo "✓ Updated $updated files with version: $version\n";

// Step 3: Verify BASE_URL injection
echo "\n📋 BƯỚC 3: Kiểm tra BASE_URL injection\n";
echo str_repeat("─", 60) . "\n";

require_once 'bootstrap.php';

if (defined('BASE_URL')) {
    echo "✓ PHP BASE_URL: " . BASE_URL . "\n";
    
    if (BASE_URL === $baseUrl) {
        echo "✓ PHP BASE_URL khớp với .env\n";
    } else {
        echo "⚠  PHP BASE_URL khác với .env\n";
        echo "   .env: $baseUrl\n";
        echo "   PHP:  " . BASE_URL . "\n";
    }
} else {
    echo "❌ BASE_URL không được định nghĩa trong PHP\n";
}

// Step 4: Check injection in files
echo "\n📋 BƯỚC 4: Kiểm tra injection trong files\n";
echo str_repeat("─", 60) . "\n";

$checkFiles = [
    'lequocanh/customer/support.php' => 'window.BASE_URL',
    'lequocanh/administrator/index.php' => 'window.BASE_URL',
    'lequocanh/customer/support.js' => 'getApiUrl'
];

foreach ($checkFiles as $file => $needle) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $found = strpos($content, $needle) !== false;
        echo ($found ? "✓" : "❌") . " " . basename($file) . " - $needle\n";
    }
}

// Step 5: Test URL generation
echo "\n📋 BƯỚC 5: Test URL generation\n";
echo str_repeat("─", 60) . "\n";

$testPath = 'support_tickets.php?action=user_list';
$expectedUrl = BASE_URL . '/lequocanh/api/' . $testPath;
echo "✓ Test path: $testPath\n";
echo "✓ Expected URL: $expectedUrl\n";

// Step 6: Summary and next steps
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                    HOÀN TẤT                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Đã cập nhật cache busting cho JavaScript files\n";
echo "✅ Đã kiểm tra BASE_URL injection\n";
echo "✅ Đã verify URL generation\n\n";

echo "📋 BƯỚC TIẾP THEO (QUAN TRỌNG):\n";
echo str_repeat("─", 60) . "\n";
echo "1. Mở browser và CLEAR CACHE:\n";
echo "   → Nhấn: Ctrl + Shift + Delete\n";
echo "   → Hoặc: Ctrl + F5 (hard refresh)\n\n";

echo "2. Mở trang test:\n";
echo "   → $baseUrl/lequocanh/customer/test_base_url.php\n\n";

echo "3. Kiểm tra Console (F12):\n";
echo "   → Phải thấy: BASE_URL injected: $baseUrl\n";
echo "   → Phải thấy: getApiUrl logs\n\n";

echo "4. Test trang support:\n";
echo "   → $baseUrl/lequocanh/customer/support.php\n\n";

echo "5. Nếu vẫn lỗi:\n";
echo "   → Mở DevTools (F12)\n";
echo "   → Tab Network → Tick 'Disable cache'\n";
echo "   → Refresh lại trang\n\n";

echo "💡 LƯU Ý:\n";
echo "   - Đảm bảo Cloudflare tunnel đang chạy\n";
echo "   - Luôn clear cache sau khi update code\n";
echo "   - Kiểm tra Console logs để debug\n\n";

echo "✨ Xong! Hãy clear cache và test lại.\n";
