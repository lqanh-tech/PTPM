<?php
/**
 * Test Review Management API with Admin Session
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

SessionManager::start();

// Set admin session
$_SESSION['ADMIN'] = 'admin';

echo "<h1>Test Review Management API</h1>";
echo "<p>Admin session set: " . $_SESSION['ADMIN'] . "</p>";

// Simulate API call
$_GET['action'] = 'list';
$_GET['page'] = 1;
$_GET['status'] = 'all';
$_GET['search'] = '';

ob_start();
include 'lequocanh/api/review_management.php';
$output = ob_get_clean();

echo "<h2>API Response:</h2>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

$result = json_decode($output, true);
if ($result && $result['success']) {
    echo "<h2 style='color:green'>✓ API working correctly!</h2>";
    echo "<p>Total reviews: " . ($result['data']['stats']['total_reviews'] ?? 0) . "</p>";
    echo "<p>Reviews returned: " . count($result['data']['reviews'] ?? []) . "</p>";
} else {
    echo "<h2 style='color:red'>✗ API Error</h2>";
    echo "<p>Error: " . ($result['error'] ?? 'Unknown error') . "</p>";
}
?>
