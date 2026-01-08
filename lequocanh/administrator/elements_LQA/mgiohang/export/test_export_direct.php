<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['ADMIN'] = 'admin';

echo "<h2>Test Export Direct</h2>";

echo "<h3>1. Testing Vendor Path</h3>";
$vendorPath = __DIR__ . '/../../../../../vendor/autoload.php';
echo "Vendor path: $vendorPath<br>";

if (file_exists($vendorPath)) {
    echo "✅ Vendor exists<br>";
    require_once $vendorPath;
    echo "✅ Vendor loaded<br>";
} else {
    echo "❌ Vendor NOT found<br>";
    echo "Current dir: " . __DIR__ . "<br>";
    echo "Trying alternative paths...<br>";
    
    $alternatives = [
        __DIR__ . '/../../../../vendor/autoload.php',
        __DIR__ . '/../../../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
    ];
    
    foreach ($alternatives as $alt) {
        if (file_exists($alt)) {
            echo "✅ Found at: $alt<br>";
            require_once $alt;
            break;
        } else {
            echo "❌ Not at: $alt<br>";
        }
    }
}

echo "<h3>2. Testing TCPDF</h3>";
if (class_exists('TCPDF')) {
    echo "✅ TCPDF class exists<br>";
    try {
        $pdf = new TCPDF();
        echo "✅ TCPDF instantiated<br>";
    } catch (Exception $e) {
        echo "❌ TCPDF error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ TCPDF class not found<br>";
}

echo "<h3>3. Testing PhpSpreadsheet</h3>";
if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    echo "✅ PhpSpreadsheet class exists<br>";
    try {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        echo "✅ PhpSpreadsheet instantiated<br>";
    } catch (Exception $e) {
        echo "❌ PhpSpreadsheet error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ PhpSpreadsheet class not found<br>";
}

echo "<h3>4. Testing OrderExporter</h3>";
require_once __DIR__ . '/OrderExporter.php';

try {
    $exporter = new OrderExporter();
    echo "✅ OrderExporter instantiated<br>";
    
    $orders = $exporter->getOrdersList([]);
    echo "✅ Found " . count($orders) . " orders<br>";
    
    if (count($orders) > 0) {
        $order = $orders[0];
        echo "<br>Sample order:<br>";
        echo "- ID: {$order['id']}<br>";
        echo "- Mã: {$order['ma_don_hang_text']}<br>";
        echo "- Khách: {$order['ten_khach_hang']}<br>";
    }
    
} catch (Exception $e) {
    echo "❌ OrderExporter error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>Done!</h3>";
?>
