<?php
/**
 * Test API endpoint
 */

// Clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
}

require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';
SessionManager::start();
$_SESSION['ADMIN'] = 'admin';

$_GET['action'] = 'list';
$_GET['page'] = 1;
$_GET['status'] = 'all';
$_GET['search'] = '';

echo "Testing API endpoint...\n\n";

ob_start();
include 'lequocanh/api/review_management.php';
$output = ob_get_clean();

echo "Output:\n";
echo $output . "\n\n";

$json = json_decode($output, true);
if ($json) {
    echo "✅ Valid JSON\n";
    echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    
    if ($json['success']) {
        echo "Reviews count: " . count($json['data']['reviews']) . "\n";
        echo "Stats: " . json_encode($json['data']['stats']) . "\n";
    } else {
        echo "Error: " . $json['error'] . "\n";
    }
} else {
    echo "❌ Invalid JSON\n";
}
