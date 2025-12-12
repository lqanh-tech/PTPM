<?php
/**
 * Script cập nhật Cloudflare Tunnel URL
 * Sử dụng: php update_cloudflare_url.php <new_url>
 * Ví dụ: php update_cloudflare_url.php https://new-tunnel.trycloudflare.com
 */

if ($argc < 2) {
    echo "❌ Thiếu URL!\n\n";
    echo "Cách sử dụng:\n";
    echo "  php update_cloudflare_url.php <cloudflare_url>\n\n";
    echo "Ví dụ:\n";
    echo "  php update_cloudflare_url.php https://abc-def-ghi.trycloudflare.com\n\n";
    exit(1);
}

$newUrl = trim($argv[1]);

// Validate URL
if (!filter_var($newUrl, FILTER_VALIDATE_URL)) {
    echo "❌ URL không hợp lệ: $newUrl\n";
    exit(1);
}

// Check if it's a Cloudflare tunnel URL
if (strpos($newUrl, 'trycloudflare.com') === false) {
    echo "⚠️  Cảnh báo: URL không phải là Cloudflare tunnel\n";
    echo "URL: $newUrl\n";
    echo "Bạn có chắc muốn tiếp tục? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) != 'y') {
        echo "Đã hủy.\n";
        exit(0);
    }
}

echo "🔄 Đang cập nhật Cloudflare Tunnel URL...\n\n";

// Update .env file
$envFile = '.env';
if (!file_exists($envFile)) {
    echo "❌ Không tìm thấy file .env\n";
    exit(1);
}

$envContent = file_get_contents($envFile);
$oldUrl = '';

// Extract old URL
if (preg_match('/BASE_URL\s*=\s*(.+)/', $envContent, $matches)) {
    $oldUrl = trim($matches[1]);
}

// Replace BASE_URL
$envContent = preg_replace(
    '/BASE_URL\s*=\s*.+/',
    "BASE_URL=$newUrl",
    $envContent
);

// Ensure USE_CLOUDFLARE_TUNNEL is true
$envContent = preg_replace(
    '/USE_CLOUDFLARE_TUNNEL\s*=\s*.+/',
    'USE_CLOUDFLARE_TUNNEL=true',
    $envContent
);

// Write back to file
file_put_contents($envFile, $envContent);

echo "✅ Đã cập nhật .env\n";
echo "   Old URL: $oldUrl\n";
echo "   New URL: $newUrl\n\n";

// Test if the new URL is accessible
echo "🔍 Đang kiểm tra URL mới...\n";
$ch = curl_init($newUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 400) {
    echo "✅ URL mới hoạt động! (HTTP $httpCode)\n\n";
} else {
    echo "⚠️  Không thể kết nối đến URL mới (HTTP $httpCode)\n";
    echo "   Hãy đảm bảo Cloudflare tunnel đang chạy\n\n";
}

// Show next steps
echo "📋 BƯỚC TIẾP THEO:\n";
echo "1. Đảm bảo Cloudflare tunnel đang chạy:\n";
echo "   .\\cloudflared.exe tunnel --url http://localhost:20080\n\n";
echo "2. Clear browser cache:\n";
echo "   - Nhấn Ctrl+Shift+Delete\n";
echo "   - Hoặc Ctrl+F5 để hard refresh\n\n";
echo "3. Kiểm tra trong browser Console (F12):\n";
echo "   console.log(window.BASE_URL)\n";
echo "   // Phải hiển thị: $newUrl\n\n";
echo "4. Test các trang:\n";
echo "   - User: $newUrl/lequocanh/customer/support.php\n";
echo "   - Admin: $newUrl/lequocanh/administrator/\n\n";

echo "✨ Hoàn tất!\n";
