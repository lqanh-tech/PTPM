<?php
/**
 * Test: Add-to-cart fix for giohangAct.php
 * Verifies sanitizeInput() is available after including security_config.php
 * Tests 3 product sections: featured, new, hot promotions
 */

declare(strict_types=1);

echo "=== Test Add-to-Cart Fix ===\n\n";

$passed = 0;
$failed = 0;

// ---- Test 1: sanitizeInput function exists after includes ----
echo "[Test 1] sanitizeInput available after giohangAct.php includes\n";
require_once __DIR__ . '/../lequocanh/administrator/elements_LQA/mod/SecurityHelpers.php';
require_once __DIR__ . '/../lequocanh/administrator/elements_LQA/config/security_config.php';
require_once __DIR__ . '/../lequocanh/administrator/elements_LQA/mod/InputValidator.php';

if (function_exists('sanitizeInput')) {
    echo "  PASS: sanitizeInput() function exists\n";
    $passed++;
} else {
    echo "  FAIL: sanitizeInput() function NOT found\n";
    $failed++;
}

// ---- Test 2: sanitizeInput works correctly ----
echo "\n[Test 2] sanitizeInput sanitizes input correctly\n";
$testCases = [
    ['input' => '<script>alert(1)</script>', 'type' => 'text', 'should_not_contain' => '<script>'],
    ['input' => 'hello world', 'type' => 'text', 'should_contain' => 'hello world'],
    ['input' => '123abc', 'type' => 'int', 'should_contain' => '123'],
    ['input' => 'test@email.com', 'type' => 'email', 'should_contain' => 'test@email.com'],
];

$allSanitizeOk = true;
foreach ($testCases as $tc) {
    $result = sanitizeInput($tc['input'], $tc['type']);
    if (isset($tc['should_not_contain'])) {
        if (strpos($result, $tc['should_not_contain']) !== false) {
            echo "  FAIL: '{$tc['input']}' still contains '{$tc['should_not_contain']}' after sanitize\n";
            $allSanitizeOk = false;
        }
    }
    if (isset($tc['should_contain'])) {
        if (strpos($result, $tc['should_contain']) === false) {
            echo "  FAIL: '{$tc['input']}' missing '{$tc['should_contain']}' after sanitize (got: $result)\n";
            $allSanitizeOk = false;
        }
    }
}
if ($allSanitizeOk) {
    echo "  PASS: All sanitizeInput test cases passed\n";
    $passed++;
} else {
    echo "  FAIL: Some sanitizeInput cases failed\n";
    $failed++;
}

// ---- Test 3: giohangAct.php can be parsed without syntax errors ----
echo "\n[Test 3] giohangAct.php syntax check\n";
$giohangActPath = __DIR__ . '/../lequocanh/administrator/elements_LQA/mgiohang/giohangAct.php';
$output = [];
$returnCode = 0;
exec("php -l " . escapeshellarg($giohangActPath) . " 2>&1", $output, $returnCode);
if ($returnCode === 0 && strpos(implode('', $output), 'No syntax errors') !== false) {
    echo "  PASS: giohangAct.php has no syntax errors\n";
    $passed++;
} else {
    echo "  FAIL: giohangAct.php has syntax errors: " . implode("\n", $output) . "\n";
    $failed++;
}

// ---- Test 4: sanitizeInput function exists in giohangAct.php ----
echo "\n[Test 4] sanitizeInput function defined in giohangAct.php\n";
$giohangContent = file_get_contents($giohangActPath);
if (strpos($giohangContent, 'function sanitizeInput') !== false) {
    echo "  PASS: sanitizeInput function is defined\n";
    $passed++;
} else {
    echo "  FAIL: sanitizeInput function NOT defined\n";
    $failed++;
}

// ---- Test 5: no require security_config in giohangAct.php ----
echo "\n[Test 5] No require security_config.php in giohangAct.php\n";
$hasRequireSecurity = preg_match('/require.*security_config\.php/', $giohangContent);
if (!$hasRequireSecurity) {
    echo "  PASS: No require security_config.php (avoids session side effects)\n";
    $passed++;
} else {
    echo "  FAIL: Still requires security_config.php\n";
    $failed++;
}

// ---- Test 6: confirmDeliveryAct.php has sanitizeInput defined ----
echo "\n[Test 6] confirmDeliveryAct.php has sanitizeInput function\n";
$confirmPath = __DIR__ . '/../lequocanh/administrator/elements_LQA/mgiohang/confirmDeliveryAct.php';
$confirmContent = file_get_contents($confirmPath);
if (strpos($confirmContent, 'function sanitizeInput') !== false) {
    echo "  PASS: sanitizeInput function defined\n";
    $passed++;
} else {
    echo "  FAIL: sanitizeInput function NOT defined\n";
    $failed++;
}

// ---- Test 7: orderCancelAct.php has sanitizeInput defined ----
echo "\n[Test 7] orderCancelAct.php has sanitizeInput function\n";
$cancelPath = __DIR__ . '/../lequocanh/administrator/elements_LQA/mgiohang/orderCancelAct.php';
$cancelContent = file_get_contents($cancelPath);
if (strpos($cancelContent, 'function sanitizeInput') !== false) {
    echo "  PASS: sanitizeInput function defined\n";
    $passed++;
} else {
    echo "  FAIL: sanitizeInput function NOT defined\n";
    $failed++;
}

// ---- Test 8: addToCart function in featuredProductsDisplay.php uses fetch API ----
echo "\n[Test 8] featuredProductsDisplay.php addToCart uses fetch API\n";
$displayPath = __DIR__ . '/../lequocanh/components/featuredProductsDisplay.php';
$displayContent = file_get_contents($displayPath);
$hasFetch = strpos($displayContent, "fetch(") !== false;
$hasGiohangAct = strpos($displayContent, "giohangAct.php") !== false;
if ($hasFetch && $hasGiohangAct) {
    echo "  PASS: addToCart uses fetch to giohangAct.php\n";
    $passed++;
} else {
    echo "  FAIL: addToCart missing fetch or giohangAct.php reference\n";
    $failed++;
}

// ---- Test 9: 3 product sections have addToCart buttons ----
echo "\n[Test 9] All 3 sections (featured/new/hot) have addToCart buttons\n";
$sectionChecks = [
    'featured' => strpos($displayContent, 'Sản Phẩm Nổi Bật') !== false,
    'new'      => strpos($displayContent, 'Sản Phẩm Mới') !== false,
    'hot'      => strpos($displayContent, 'Khuyến Mãi Hot') !== false,
];
$allSectionsOk = true;
foreach ($sectionChecks as $name => $found) {
    if (!$found) {
        echo "  FAIL: Section '$name' not found\n";
        $allSectionsOk = false;
    }
}
// Check addToCart calls exist
$addToCartCalls = substr_count($displayContent, 'addToCart(');
if ($addToCartCalls >= 3 && $allSectionsOk) {
    echo "  PASS: All 3 sections found, $addToCartCalls addToCart() calls\n";
    $passed++;
} else {
    echo "  FAIL: Only $addToCartCalls addToCart calls, sections: " . implode(', ', array_keys(array_filter($sectionChecks))) . "\n";
    $failed++;
}

// ---- Summary ----
echo "\n=== Results ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo $failed === 0 ? "ALL TESTS PASSED\n" : "SOME TESTS FAILED\n";

exit($failed > 0 ? 1 : 0);
