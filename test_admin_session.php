<?php
/**
 * Test Admin Session
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';
SessionManager::start();

echo "<h1>Test Admin Session</h1>";

echo "<h2>Current Session:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "ADMIN: " . ($_SESSION['ADMIN'] ?? 'NOT SET') . "\n";
echo "USER: " . ($_SESSION['USER'] ?? 'NOT SET') . "\n";
echo "</pre>";

if (isset($_SESSION['ADMIN'])) {
    echo "<p style='color:green'>✓ Admin session is active</p>";
    echo "<p>You can access the Review Management page.</p>";
} else {
    echo "<p style='color:red'>✗ Admin session is NOT active</p>";
    echo "<p>Please login to admin panel first.</p>";
    echo "<p><a href='lequocanh/administrator/index.php'>Go to Admin Login</a></p>";
}

echo "<h2>Test API Call:</h2>";
if (isset($_SESSION['ADMIN'])) {
    // Test API
    $_GET['action'] = 'list';
    $_GET['page'] = 1;
    $_GET['status'] = 'all';
    $_GET['search'] = '';
    
    ob_start();
    include 'lequocanh/api/review_management.php';
    $output = ob_get_clean();
    
    $result = json_decode($output, true);
    if ($result && $result['success']) {
        echo "<p style='color:green'>✓ API working! Found " . count($result['data']['reviews']) . " reviews</p>";
    } else {
        echo "<p style='color:red'>✗ API Error: " . ($result['error'] ?? 'Unknown') . "</p>";
    }
} else {
    echo "<p>Login first to test API</p>";
}
?>
