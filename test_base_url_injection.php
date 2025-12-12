<?php
/**
 * Test BASE_URL Injection
 * Kiểm tra xem BASE_URL có được inject đúng vào JavaScript không
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "=== TEST BASE_URL INJECTION ===\n\n";

// Test 1: Check .env configuration
echo "1. CHECKING .env CONFIGURATION\n";
$envFile = '.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    
    // Extract BASE_URL
    if (preg_match('/BASE_URL\s*=\s*(.+)/', $envContent, $matches)) {
        $baseUrl = trim($matches[1]);
        echo "   BASE_URL in .env: $baseUrl\n";
    }
    
    // Extract USE_CLOUDFLARE_TUNNEL
    if (preg_match('/USE_CLOUDFLARE_TUNNEL\s*=\s*(.+)/', $envContent, $matches)) {
        $useTunnel = trim($matches[1]);
        echo "   USE_CLOUDFLARE_TUNNEL: $useTunnel\n";
    }
} else {
    echo "   ✗ .env file not found\n";
}

// Test 2: Check if BASE_URL is defined in PHP
echo "\n2. CHECKING PHP BASE_URL CONSTANT\n";
require_once 'bootstrap.php';

if (defined('BASE_URL')) {
    echo "   ✓ BASE_URL is defined: " . BASE_URL . "\n";
} else {
    echo "   ✗ BASE_URL is not defined\n";
}

// Test 3: Check injection in support.php
echo "\n3. CHECKING INJECTION IN support.php\n";
$supportFile = 'lequocanh/customer/support.php';
if (file_exists($supportFile)) {
    $content = file_get_contents($supportFile);
    $hasInjection = strpos($content, 'window.BASE_URL') !== false;
    echo "   " . ($hasInjection ? "✓" : "✗") . " window.BASE_URL injection found\n";
} else {
    echo "   ✗ support.php not found\n";
}

// Test 4: Check injection in admin index.php
echo "\n4. CHECKING INJECTION IN admin/index.php\n";
$adminIndexFile = 'lequocanh/administrator/index.php';
if (file_exists($adminIndexFile)) {
    $content = file_get_contents($adminIndexFile);
    $hasInjection = strpos($content, 'window.BASE_URL') !== false;
    echo "   " . ($hasInjection ? "✓" : "✗") . " window.BASE_URL injection found\n";
} else {
    echo "   ✗ admin/index.php not found\n";
}

// Test 5: Check getApiUrl function in JavaScript files
echo "\n5. CHECKING getApiUrl FUNCTION IN JAVASCRIPT\n";
$jsFiles = [
    'lequocanh/customer/support.js',
    'lequocanh/administrator/elements_LQA/mreview_management/reviewManagementView.php',
    'lequocanh/administrator/elements_LQA/msupport_tickets/supportTicketsView.php'
];

foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $hasGetApiUrl = strpos($content, 'getApiUrl') !== false;
        $hasWindowBaseUrl = strpos($content, 'window.BASE_URL') !== false;
        
        echo "   " . basename($file) . ":\n";
        echo "      " . ($hasGetApiUrl ? "✓" : "✗") . " getApiUrl function\n";
        echo "      " . ($hasWindowBaseUrl ? "✓" : "✗") . " Uses window.BASE_URL\n";
    }
}

// Test 6: Simulate URL generation
echo "\n6. SIMULATING URL GENERATION\n";
$baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost:20080';
$apiPath = 'support_tickets.php?action=user_list';

echo "   Base URL: $baseUrl\n";
echo "   API Path: $apiPath\n";
echo "   Full URL: $baseUrl/lequocanh/api/$apiPath\n";

// Test 7: Check if Cloudflare tunnel is active
echo "\n7. CHECKING CLOUDFLARE TUNNEL STATUS\n";
if (strpos($baseUrl, 'trycloudflare.com') !== false) {
    echo "   ✓ Cloudflare tunnel is ACTIVE\n";
    echo "   URL: $baseUrl\n";
} else if (strpos($baseUrl, 'localhost') !== false) {
    echo "   ℹ Using localhost (tunnel is OFF)\n";
    echo "   URL: $baseUrl\n";
} else {
    echo "   ⚠ Unknown URL type: $baseUrl\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nNEXT STEPS:\n";
echo "1. Mở browser và truy cập trang hỗ trợ\n";
echo "2. Mở Console (F12) và kiểm tra:\n";
echo "   - console.log(window.BASE_URL) - phải hiển thị URL từ .env\n";
echo "3. Kiểm tra Network tab:\n";
echo "   - API calls phải dùng URL từ .env\n";
echo "   - Không còn lỗi CORS\n";
echo "4. Nếu vẫn có lỗi:\n";
echo "   - Clear browser cache (Ctrl+Shift+Delete)\n";
echo "   - Hard refresh (Ctrl+F5)\n";
