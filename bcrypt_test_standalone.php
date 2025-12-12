<?php

/**
 * Standalone Bcrypt Test Script
 * This script can be run directly with PHP CLI or through a web server
 */

// Simple HTML wrapper for web display
if (php_sapi_name() !== 'cli') {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Bcrypt Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2, h3 { color: #333; }
            hr { margin: 20px 0; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            .info { color: blue; }
        </style>
    </head>
    <body>";
}

echo "<h2>Standalone Bcrypt Test</h2>";
echo "<hr>";

// Check if bcrypt is available
echo "<h3>Test 0: Bcrypt Availability Check</h3>";
if (!function_exists('password_hash') || !defined('PASSWORD_BCRYPT')) {
    echo "<p class='error'>❌ Bcrypt is not available in this PHP installation!</p>";
    if (php_sapi_name() !== 'cli') {
        echo "</body></html>";
    }
    exit(1);
} else {
    echo "<p class='success'>✅ Bcrypt is available</p>";
}
echo "<hr>";

// Test 1: Hash a password
echo "<h3>Test 1: Hash Password</h3>";
$plainPassword = "testpassword123";
echo "<p><strong>Plain Password:</strong> $plainPassword</p>";

// Using PHP's built-in password_hash function
$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
echo "<p><strong>Hashed Password:</strong> $hashedPassword</p>";
echo "<p><strong>Hash Length:</strong> " . strlen($hashedPassword) . " characters</p>";
echo "<hr>";

// Test 2: Verify correct password
echo "<h3>Test 2: Verify Correct Password</h3>";
$isValid = password_verify($plainPassword, $hashedPassword);
echo "<p><strong>Password:</strong> $plainPassword</p>";
echo "<p><strong>Verification Result:</strong> " . ($isValid ? "<span class='success'>✅ VALID</span>" : "<span class='error'>❌ INVALID</span>") . "</p>";
echo "<hr>";

// Test 3: Verify wrong password
echo "<h3>Test 3: Verify Wrong Password</h3>";
$wrongPassword = "wrongpassword";
$isValid = password_verify($wrongPassword, $hashedPassword);
echo "<p><strong>Password:</strong> $wrongPassword</p>";
echo "<p><strong>Verification Result:</strong> " . ($isValid ? "<span class='success'>✅ VALID</span>" : "<span class='error'>❌ INVALID</span>") . "</p>";
echo "<hr>";

// Test 4: Check if rehash is needed
echo "<h3>Test 4: Check if Rehash Needed</h3>";
$needsRehash = password_needs_rehash($hashedPassword, PASSWORD_BCRYPT, ['cost' => 12]);
echo "<p><strong>Needs Rehash:</strong> " . ($needsRehash ? "<span class='warning'>✅ YES</span>" : "<span class='success'>❌ NO</span>") . "</p>";
echo "<hr>";

// Test 5: Hash the same password multiple times
echo "<h3>Test 5: Multiple Hashes of Same Password</h3>";
echo "<p><strong>Password:</strong> samepassword</p>";

$hash1 = password_hash("samepassword", PASSWORD_BCRYPT, ['cost' => 12]);
$hash2 = password_hash("samepassword", PASSWORD_BCRYPT, ['cost' => 12]);
$hash3 = password_hash("samepassword", PASSWORD_BCRYPT, ['cost' => 12]);

echo "<p><strong>Hash 1:</strong> $hash1</p>";
echo "<p><strong>Hash 2:</strong> $hash2</p>";
echo "<p><strong>Hash 3:</strong> $hash3</p>";

$different = ($hash1 !== $hash2 && $hash2 !== $hash3);
echo "<p><strong>All hashes different:</strong> " . ($different ? "<span class='success'>✅ YES (salt working)</span>" : "<span class='error'>❌ NO (salt not working)</span>") . "</p>";

// Verify all hashes work
$verify1 = password_verify("samepassword", $hash1);
$verify2 = password_verify("samepassword", $hash2);
$verify3 = password_verify("samepassword", $hash3);

echo "<p><strong>Verify Hash 1:</strong> " . ($verify1 ? "<span class='success'>✅ VALID</span>" : "<span class='error'>❌ INVALID</span>") . "</p>";
echo "<p><strong>Verify Hash 2:</strong> " . ($verify2 ? "<span class='success'>✅ VALID</span>" : "<span class='error'>❌ INVALID</span>") . "</p>";
echo "<p><strong>Verify Hash 3:</strong> " . ($verify3 ? "<span class='success'>✅ VALID</span>" : "<span class='error'>❌ INVALID</span>") . "</p>";
echo "<hr>";

// Test 6: Performance test
echo "<h3>Test 6: Performance Test</h3>";
$iterations = 5;
$startTime = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    password_hash("testpassword$i", PASSWORD_BCRYPT, ['cost' => 12]);
}

$endTime = microtime(true);
$totalTime = $endTime - $startTime;
$avgTime = $totalTime / $iterations;

echo "<p><strong>Number of hashes:</strong> $iterations</p>";
echo "<p><strong>Total time:</strong> " . number_format($totalTime, 4) . " seconds</p>";
echo "<p><strong>Average:</strong> " . number_format($avgTime, 4) . " seconds/hash</p>";
echo "<p><strong>Performance:</strong> " . ($avgTime < 1.0 ? "<span class='success'>✅ Good performance</span>" : "<span class='warning'>⚠ High cost factor may be needed</span>") . "</p>";
echo "<hr>";

echo "<h3>Conclusion</h3>";
if ($isValid && !$needsRehash && $different && $verify1 && $verify2 && $verify3) {
    echo "<p class='success'>✅ Bcrypt is working correctly!</p>";
} else {
    echo "<p class='error'>❌ There may be issues with Bcrypt implementation.</p>";
}

// Close HTML if not CLI
if (php_sapi_name() !== 'cli') {
    echo "<p><a href='/'>← Back to Home</a></p>";
    echo "</body></html>";
}
