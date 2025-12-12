<?php
/**
 * Test Shipping Access
 * File test để kiểm tra quyền truy cập shipping modules
 */

session_start();

echo "<h1>Test Shipping Access</h1>";
echo "<hr>";

echo "<h2>Session Information:</h2>";
echo "<pre>";
echo "SESSION['USER']: " . (isset($_SESSION['USER']) ? $_SESSION['USER'] : 'NOT SET') . "\n";
echo "SESSION['ADMIN']: " . (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : 'NOT SET') . "\n";
echo "SESSION['role']: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "\n";
echo "\nAll SESSION data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h2>File Existence Check:</h2>";
$files = [
    'shipping_config' => __DIR__ . '/elements_LQA/madmin/shipping_config.php',
    'shipping_dashboard' => __DIR__ . '/elements_LQA/madmin/shipping_dashboard.php',
    'shipping_report' => __DIR__ . '/elements_LQA/madmin/shipping_report.php',
];

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    $icon = $exists ? '✅' : '❌';
    echo "$icon <strong>$name</strong>: " . ($exists ? 'EXISTS' : 'NOT FOUND') . "<br>";
    echo "   Path: $path<br>";
}

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='index.php?req=shipping_config'>Shipping Config</a></li>";
echo "<li><a href='index.php?req=shipping_dashboard'>Shipping Dashboard</a></li>";
echo "<li><a href='index.php?req=shipping_report'>Shipping Report</a></li>";
echo "</ul>";

echo "<h2>Direct File Access Test:</h2>";
echo "<p>Trying to include shipping_config.php directly...</p>";

try {
    ob_start();
    require __DIR__ . '/elements_LQA/madmin/shipping_config.php';
    $output = ob_get_clean();
    echo "<div style='border: 1px solid green; padding: 10px; background: #e8f5e9;'>";
    echo "<strong>✅ File loaded successfully!</strong><br>";
    echo "Output length: " . strlen($output) . " bytes";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='border: 1px solid red; padding: 10px; background: #ffebee;'>";
    echo "<strong>❌ Error loading file:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}
