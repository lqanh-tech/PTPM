<?php
/**
 * Test Support System Complete - Via Docker
 * Test toàn bộ hệ thống support qua Docker
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         TEST HỆ THỐNG HỖ TRỢ - TOÀN DIỆN                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$allPassed = true;

// Test 1: Database Connection
echo "📋 TEST 1: DATABASE CONNECTION\n";
echo str_repeat("─", 60) . "\n";
try {
    $stmt = $conn->query("SELECT 1");
    echo "✓ Database connected\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 2: Check Tables
echo "\n📋 TEST 2: CHECK TABLES\n";
echo str_repeat("─", 60) . "\n";
$requiredTables = [
    'support_tickets',
    'support_messages',
    'product_reviews',
    'review_reports'
];

foreach ($requiredTables as $table) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "✓ $table: $count records\n";
    } catch (Exception $e) {
        echo "✗ $table: " . $e->getMessage() . "\n";
        $allPassed = false;
    }
}

// Test 3: Check Views
echo "\n📋 TEST 3: CHECK VIEWS\n";
echo str_repeat("─", 60) . "\n";
$requiredViews = [
    'v_support_tickets_list',
    'v_review_management_stats',
    'v_review_reports_list'
];

foreach ($requiredViews as $view) {
    try {
        $stmt = $conn->query("SELECT * FROM $view LIMIT 1");
        echo "✓ $view exists\n";
    } catch (Exception $e) {
        echo "✗ $view: " . $e->getMessage() . "\n";
        $allPassed = false;
    }
}

// Test 4: Check Users
echo "\n📋 TEST 4: CHECK USERS\n";
echo str_repeat("─", 60) . "\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM user");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Total users: $userCount\n";
    
    if ($userCount > 0) {
        $stmt = $conn->query("SELECT iduser, tenuser FROM user LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  Sample users:\n";
        foreach ($users as $user) {
            echo "  - {$user['iduser']}: {$user['tenuser']}\n";
        }
    }
} catch (Exception $e) {
    echo "✗ User table error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 5: Test API Endpoints (Simulated)
echo "\n📋 TEST 5: TEST API ENDPOINTS (SIMULATED)\n";
echo str_repeat("─", 60) . "\n";

// Simulate API call to support_tickets.php
echo "Testing: support_tickets.php?action=user_list\n";
try {
    // Get a real user ID
    $stmt = $conn->query("SELECT iduser FROM user LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        $userId = $testUser['iduser'];
        echo "  Using test user: $userId\n";
        
        // Simulate the API query
        $sql = "SELECT * FROM v_support_tickets_list WHERE user_id = ? LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✓ API query successful: " . count($tickets) . " tickets found\n";
    } else {
        echo "⚠  No users found for testing\n";
    }
} catch (Exception $e) {
    echo "✗ API simulation error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 6: Check File Structure
echo "\n📋 TEST 6: CHECK FILE STRUCTURE\n";
echo str_repeat("─", 60) . "\n";
$requiredFiles = [
    'lequocanh/customer/support.php',
    'lequocanh/customer/support.js',
    'lequocanh/api/support_tickets.php',
    'lequocanh/api/review_management.php',
    'lequocanh/administrator/elements_LQA/mreview_management/reviewManagementView.php',
    'lequocanh/administrator/elements_LQA/msupport_tickets/supportTicketsView.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✓ $file (" . number_format($size) . " bytes)\n";
    } else {
        echo "✗ $file NOT FOUND\n";
        $allPassed = false;
    }
}

// Test 7: Check BASE_URL Configuration
echo "\n📋 TEST 7: CHECK BASE_URL CONFIGURATION\n";
echo str_repeat("─", 60) . "\n";

// Load bootstrap to get BASE_URL
require_once 'bootstrap.php';

if (defined('BASE_URL')) {
    echo "✓ BASE_URL defined: " . BASE_URL . "\n";
    
    if (strpos(BASE_URL, 'trycloudflare.com') !== false) {
        echo "✓ Using Cloudflare tunnel\n";
    } else if (strpos(BASE_URL, 'localhost') !== false) {
        echo "✓ Using localhost\n";
    }
} else {
    echo "✗ BASE_URL not defined\n";
    $allPassed = false;
}

// Test 8: Check JavaScript Injection
echo "\n📋 TEST 8: CHECK JAVASCRIPT INJECTION\n";
echo str_repeat("─", 60) . "\n";

$jsFiles = [
    'lequocanh/customer/support.php' => 'window.BASE_URL',
    'lequocanh/administrator/index.php' => 'window.BASE_URL',
    'lequocanh/customer/support.js' => 'getApiUrl'
];

foreach ($jsFiles as $file => $needle) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $found = strpos($content, $needle) !== false;
        
        if ($found) {
            echo "✓ " . basename($file) . " contains '$needle'\n";
        } else {
            echo "✗ " . basename($file) . " missing '$needle'\n";
            $allPassed = false;
        }
    }
}

// Test 9: Test SQL Queries
echo "\n📋 TEST 9: TEST SQL QUERIES\n";
echo str_repeat("─", 60) . "\n";

// Test pagination query
try {
    $page = 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT * FROM support_tickets LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    $stmt = $conn->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✓ Pagination query works: " . count($results) . " results\n";
} catch (Exception $e) {
    echo "✗ Pagination query error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 10: Simulate Full API Flow
echo "\n📋 TEST 10: SIMULATE FULL API FLOW\n";
echo str_repeat("─", 60) . "\n";

try {
    // Get a user
    $stmt = $conn->query("SELECT iduser FROM user LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userId = $user['iduser'];
        
        // Step 1: Get user tickets
        $sql = "SELECT * FROM v_support_tickets_list WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ Step 1: Get tickets - Found " . count($tickets) . " tickets\n";
        
        // Step 2: If tickets exist, get messages
        if (count($tickets) > 0) {
            $ticketId = $tickets[0]['id'];
            $sql = "SELECT * FROM support_messages WHERE ticket_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$ticketId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "✓ Step 2: Get messages - Found " . count($messages) . " messages\n";
        } else {
            echo "ℹ  Step 2: No tickets to test messages\n";
        }
        
        // Step 3: Test stats
        $sql = "SELECT * FROM v_review_management_stats";
        $stmt = $conn->query($sql);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Step 3: Get stats - Total reviews: " . ($stats['total_reviews'] ?? 0) . "\n";
        
    } else {
        echo "⚠  No users found for full flow test\n";
    }
} catch (Exception $e) {
    echo "✗ Full flow error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 11: Check Cache Busting
echo "\n📋 TEST 11: CHECK CACHE BUSTING\n";
echo str_repeat("─", 60) . "\n";

$supportPhp = file_get_contents('lequocanh/customer/support.php');
if (preg_match('/support\.js\?v=\d+/', $supportPhp)) {
    echo "✓ Cache busting enabled (support.js?v=timestamp)\n";
} else {
    echo "⚠  Cache busting not found (may cause cache issues)\n";
}

// Summary
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                    KẾT QUẢ TEST                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if ($allPassed) {
    echo "✅ TẤT CẢ TESTS ĐỀU PASS!\n\n";
    echo "Hệ thống hoạt động bình thường. Nếu vẫn gặp lỗi trong browser:\n";
    echo "1. Clear browser cache (Ctrl+Shift+Delete)\n";
    echo "2. Hard refresh (Ctrl+F5)\n";
    echo "3. Đảm bảo đã đăng nhập\n";
    echo "4. Kiểm tra Console (F12) để xem lỗi chi tiết\n";
} else {
    echo "❌ CÓ MỘT SỐ TESTS FAILED!\n\n";
    echo "Hãy kiểm tra các lỗi ở trên và sửa chúng.\n";
}

echo "\n📋 NEXT STEPS:\n";
echo str_repeat("─", 60) . "\n";
echo "1. Nếu tests pass, test trong browser:\n";
echo "   → " . (defined('BASE_URL') ? BASE_URL : 'http://localhost:20080') . "/lequocanh/customer/support_simple.php\n\n";
echo "2. Đăng nhập và test trang chính:\n";
echo "   → " . (defined('BASE_URL') ? BASE_URL : 'http://localhost:20080') . "/lequocanh/customer/support.php\n\n";
echo "3. Nếu vẫn lỗi, kiểm tra Console logs (F12)\n\n";

echo "✨ Test hoàn tất!\n";
