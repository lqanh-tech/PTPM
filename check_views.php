<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Check views
$stmt = $conn->query("SHOW TABLES LIKE 'v_%'");
$views = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "=== VIEWS IN DATABASE ===\n";
foreach ($views as $view) {
    echo "- $view\n";
}

// Check if required views exist
$requiredViews = [
    'v_review_management_stats',
    'v_review_reports_list',
    'v_support_tickets_list'
];

echo "\n=== CHECKING REQUIRED VIEWS ===\n";
foreach ($requiredViews as $view) {
    $exists = in_array($view, $views);
    echo "$view: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
}

// Test API endpoint
echo "\n=== TESTING API ENDPOINT ===\n";
$url = 'http://localhost/lequocanh/api/review_management.php?action=list';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n";
