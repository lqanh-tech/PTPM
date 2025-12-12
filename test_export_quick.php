<?php
/**
 * Quick Export Test
 */

session_start();
$_SESSION['ADMIN'] = 'admin';

echo "<h2>Test Export Quick</h2>";

// Test 1: Kiểm tra đường dẫn vendor
echo "<h3>Test 1: Vendor Path</h3>";
$vendorPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendorPath)) {
    echo "✅ Vendor path OK: $vendorPath<br>";
    require_once $vendorPath;
    
    if (class_exists('TCPDF')) {
        echo "✅ TCPDF loaded<br>";
    } else {
        echo "❌ TCPDF not found<br>";
    }
    
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        echo "✅ PhpSpreadsheet loaded<br>";
    } else {
        echo "❌ PhpSpreadsheet not found<br>";
    }
} else {
    echo "❌ Vendor not found at: $vendorPath<br>";
}

// Test 2: Kiểm tra OrderExporter
echo "<h3>Test 2: OrderExporter</h3>";
$exporterPath = __DIR__ . '/lequocanh/administrator/elements_LQA/mgiohang/export/OrderExporter.php';
if (file_exists($exporterPath)) {
    echo "✅ OrderExporter.php exists<br>";
    require_once $exporterPath;
    
    try {
        $exporter = new OrderExporter();
        echo "✅ OrderExporter instantiated<br>";
        
        $orders = $exporter->getOrdersList([]);
        echo "✅ getOrdersList() works - Found " . count($orders) . " orders<br>";
        
        if (count($orders) > 0) {
            $firstOrder = $orders[0];
            echo "<br><strong>First order:</strong><br>";
            echo "- ID: {$firstOrder['id']}<br>";
            echo "- Mã: {$firstOrder['ma_don_hang_text']}<br>";
            echo "- Khách hàng: {$firstOrder['ten_khach_hang']}<br>";
            echo "- Tổng tiền: " . number_format($firstOrder['tong_tien']) . "đ<br>";
            
            // Test get detail
            $detail = $exporter->getOrderDetails($firstOrder['id']);
            if ($detail && isset($detail['items'])) {
                echo "✅ getOrderDetails() works - " . count($detail['items']) . " items<br>";
            } else {
                echo "❌ getOrderDetails() failed<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ OrderExporter.php not found<br>";
}

// Test 3: Test export URL
echo "<h3>Test 3: Export URLs</h3>";
echo "<a href='./lequocanh/administrator/elements_LQA/mgiohang/export/export_pdf.php?type=summary' target='_blank'>Test PDF Export</a><br>";
echo "<a href='./lequocanh/administrator/elements_LQA/mgiohang/export/export_excel.php?type=summary' target='_blank'>Test Excel Export</a><br>";

echo "<h3>Done!</h3>";
?>
