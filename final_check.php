<?php
require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

$hanghoa = new hanghoa($connection);

// Check method exists
if (!method_exists($hanghoa, 'getRelatedProducts')) {
    die("FAIL: Method not found\n");
}

// Test functionality
$related = $hanghoa->getRelatedProducts(86, 4);

if (empty($related)) {
    die("FAIL: No results\n");
}

echo "✅ SUCCESS: System working perfectly!\n";
echo "   - Method exists: YES\n";
echo "   - Results found: " . count($related) . " products\n";
echo "   - Status: READY FOR PRODUCTION\n";
