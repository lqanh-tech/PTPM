<?php
/**
 * Debug API trực tiếp
 */

// Simulate API call
$_GET['action'] = 'list';
$_GET['product_id'] = '143';
$_GET['page'] = '1';

// Capture output
ob_start();
include 'lequocanh/api/product_reviews.php';
$output = ob_get_clean();

echo "=== API OUTPUT ===\n";
echo $output;
echo "\n\n=== ANALYSIS ===\n";

// Check if it's valid JSON
$json = json_decode($output, true);
if ($json === null) {
    echo "❌ NOT VALID JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
} else {
    echo "✅ VALID JSON\n";
    if ($json['success']) {
        echo "✅ SUCCESS: true\n";
        echo "Stats: " . json_encode($json['data']['stats'], JSON_PRETTY_PRINT) . "\n";
        echo "Reviews count: " . count($json['data']['reviews']) . "\n";
    } else {
        echo "❌ SUCCESS: false\n";
        echo "Error: " . $json['error'] . "\n";
    }
}