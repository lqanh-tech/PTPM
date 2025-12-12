<?php
/**
 * Test Export System
 * Kiểm tra hệ thống xuất đơn hàng
 */

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Test Export System</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .test-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #3498db; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Test Hệ Thống Xuất Đơn Hàng</h1>";

// Test 1: Kiểm tra PHP Extensions
echo "<div class='test-section'>
        <h2>1. Kiểm tra PHP Extensions</h2>";

$required_extensions = ['gd', 'zip', 'xml', 'mbstring'];
$all_ok = true;

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✅ Extension '$ext' đã được cài đặt</p>";
    } else {
        echo "<p class='error'>❌ Extension '$ext' chưa được cài đặt</p>";
        $all_ok = false;
    }
}

echo "</div>";

// Test 2: Kiểm tra Composer Packages
echo "<div class='test-section'>
        <h2>2. Kiểm tra Composer Packages</h2>";

$autoload_file = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload_file)) {
    echo "<p class='success'>✅ Composer autoload tồn tại</p>";
    require_once $autoload_file;
    
    // Check TCPDF
    if (class_exists('TCPDF')) {
        echo "<p class='success'>✅ TCPDF đã được cài đặt</p>";
    } else {
        echo "<p class='error'>❌ TCPDF chưa được cài đặt</p>";
        $all_ok = false;
    }
    
    // Check PhpSpreadsheet
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        echo "<p class='success'>✅ PhpSpreadsheet đã được cài đặt</p>";
    } else {
        echo "<p class='error'>❌ PhpSpreadsheet chưa được cài đặt</p>";
        $all_ok = false;
    }
} else {
    echo "<p class='error'>❌ Composer autoload không tồn tại. Chạy: composer install</p>";
    $all_ok = false;
}

echo "</div>";

// Test 3: Kiểm tra Files
echo "<div class='test-section'>
        <h2>3. Kiểm tra Files Đã Tạo</h2>";

$required_files = [
    'lequocanh/administrator/elements_LQA/mgiohang/export/OrderExporter.php',
    'lequocanh/administrator/elements_LQA/mgiohang/export/export_pdf.php',
    'lequocanh/administrator/elements_LQA/mgiohang/export/export_excel.php',
    'lequocanh/administrator/elements_LQA/mgiohang/export/print_invoice.php',
    'lequocanh/administrator/js_LQA/order_export.js',
    'lequocanh/administrator/css_LQA/order_export.css',
    'lequocanh/administrator/elements_LQA/mgiohang/order_management_with_export.php'
];

foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p class='success'>✅ $file</p>";
    } else {
        echo "<p class='error'>❌ $file không tồn tại</p>";
        $all_ok = false;
    }
}

echo "</div>";

// Test 4: Test TCPDF
if (class_exists('TCPDF')) {
    echo "<div class='test-section'>
            <h2>4. Test TCPDF</h2>";
    
    try {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Test');
        $pdf->SetAuthor('Test');
        $pdf->SetTitle('Test PDF');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 10, 'Test tiếng Việt: Xin chào!', 0, 1);
        
        echo "<p class='success'>✅ TCPDF hoạt động tốt</p>";
        echo "<p class='info'>📄 Có thể tạo PDF với font tiếng Việt</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Lỗi TCPDF: " . $e->getMessage() . "</p>";
        $all_ok = false;
    }
    
    echo "</div>";
}

// Test 5: Test PhpSpreadsheet
if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    echo "<div class='test-section'>
            <h2>5. Test PhpSpreadsheet</h2>";
    
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Test tiếng Việt');
        $sheet->setCellValue('A2', 'Xin chào!');
        
        echo "<p class='success'>✅ PhpSpreadsheet hoạt động tốt</p>";
        echo "<p class='info'>📊 Có thể tạo Excel với tiếng Việt</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Lỗi PhpSpreadsheet: " . $e->getMessage() . "</p>";
        $all_ok = false;
    }
    
    echo "</div>";
}

// Test 6: Kiểm tra Database Connection
echo "<div class='test-section'>
        <h2>6. Kiểm tra Database Connection</h2>";

try {
    require_once __DIR__ . '/lequocanh/database/db_config.php';
    $config = require __DIR__ . '/lequocanh/database/db_config.php';
    
    $dsn = "mysql:host={$config['host']};dbname=sales_management;charset=utf8mb4";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ Kết nối database thành công</p>";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES LIKE 'don_hang'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Bảng 'don_hang' tồn tại</p>";
        
        // Count orders
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM don_hang");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='info'>📊 Tổng số đơn hàng: {$result['total']}</p>";
    } else {
        echo "<p class='error'>❌ Bảng 'don_hang' không tồn tại</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi database: " . $e->getMessage() . "</p>";
    $all_ok = false;
}

echo "</div>";

// Summary
echo "<div class='test-section' style='border-left-color: " . ($all_ok ? '#28a745' : '#dc3545') . ";'>
        <h2>📋 Tổng Kết</h2>";

if ($all_ok) {
    echo "<p class='success' style='font-size: 18px;'>✅ Tất cả kiểm tra đều PASS!</p>";
    echo "<p class='info'>Hệ thống xuất đơn hàng đã sẵn sàng sử dụng.</p>";
    
    echo "<h3>🎯 Bước tiếp theo:</h3>";
    echo "<ol>
            <li>Truy cập trang demo: <a href='lequocanh/administrator/elements_LQA/mgiohang/order_management_with_export.php' class='btn'>Xem Demo</a></li>
            <li>Tích hợp vào trang quản lý đơn hàng hiện tại</li>
            <li>Tùy chỉnh thông tin công ty trong file export_pdf.php và print_invoice.php</li>
          </ol>";
} else {
    echo "<p class='error' style='font-size: 18px;'>❌ Có lỗi xảy ra!</p>";
    echo "<p>Vui lòng kiểm tra lại các bước cài đặt.</p>";
}

echo "</div>";

// Links
echo "<div class='test-section'>
        <h2>📚 Tài Liệu</h2>
        <ul>
            <li><a href='HUONG_DAN_EXPORT_DON_HANG.md' target='_blank'>Hướng dẫn sử dụng chi tiết</a></li>
            <li><a href='EXPORT_DON_HANG_README.md' target='_blank'>README tổng kết</a></li>
            <li><a href='lequocanh/administrator/elements_LQA/mgiohang/order_management_with_export.php' target='_blank'>Trang demo</a></li>
        </ul>
      </div>";

echo "</div>
</body>
</html>";
