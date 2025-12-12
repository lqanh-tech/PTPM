<?php
/**
 * Script tích hợp chức năng Export vào trang quản lý đơn hàng
 * Chạy file này để tự động thêm code export vào orders_v2.php
 */

$ordersFile = __DIR__ . '/lequocanh/administrator/elements_LQA/madmin/orders_v2.php';
$backupFile = $ordersFile . '.backup_' . date('YmdHis');

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Tích hợp Export vào Orders</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Tích hợp Export vào Quản lý Đơn hàng</h1>";

if (!file_exists($ordersFile)) {
    echo "<p class='error'>❌ File orders_v2.php không tồn tại!</p>";
    echo "</div></body></html>";
    exit;
}

// Backup file gốc
copy($ordersFile, $backupFile);
echo "<p class='success'>✅ Đã backup file gốc: " . basename($backupFile) . "</p>";

// Đọc nội dung file
$content = file_get_contents($ordersFile);

// 1. Thêm CSS và JS vào <head>
$cssJsIncludes = "
    <!-- Export CSS -->
    <link rel='stylesheet' href='./css_LQA/order_export.css'>
";

// Tìm vị trí </head> và thêm vào trước đó
if (strpos($content, '</head>') !== false) {
    $content = str_replace('</head>', $cssJsIncludes . "\n</head>", $content);
    echo "<p class='success'>✅ Đã thêm CSS includes</p>";
} else {
    echo "<p class='error'>❌ Không tìm thấy thẻ </head></p>";
}

// 2. Thêm Export Toolbar sau header "Quản lý đơn hàng"
$exportToolbar = "
        <!-- Export Toolbar -->
        <div class='export-toolbar' style='margin-bottom: 20px;'>
            <div class='export-toolbar-left'>
                <div class='select-all-container'>
                    <input type='checkbox' id='select-all-orders'>
                    <label for='select-all-orders'>Chọn tất cả</label>
                </div>
                
                <span class='selected-count' id='selected-count' style='display: none;'>
                    Đã chọn: <span id='count-number'>0</span>
                </span>
            </div>
            
            <div class='export-toolbar-right'>
                <!-- Xuất chi tiết các đơn đã chọn -->
                <button class='btn-export btn-export-pdf' id='btn-export-pdf' disabled>
                    <i class='fas fa-file-pdf'></i> Xuất PDF
                </button>
                
                <button class='btn-export btn-export-excel' id='btn-export-excel' disabled>
                    <i class='fas fa-file-excel'></i> Xuất Excel
                </button>
                
                <!-- Xuất tổng hợp theo bộ lọc -->
                <button class='btn-export btn-export-summary' id='btn-export-summary-pdf'>
                    <i class='fas fa-file-pdf'></i> Báo cáo PDF
                </button>
                
                <button class='btn-export btn-export-summary' id='btn-export-summary-excel'>
                    <i class='fas fa-file-excel'></i> Báo cáo Excel
                </button>
            </div>
        </div>
";

// Tìm vị trí sau "Statistics Cards" và thêm toolbar
$searchPattern = '<!-- Statistics Cards -->';
if (strpos($content, $searchPattern) !== false) {
    $content = str_replace($searchPattern, $exportToolbar . "\n        " . $searchPattern, $content);
    echo "<p class='success'>✅ Đã thêm Export Toolbar</p>";
} else {
    echo "<p class='error'>❌ Không tìm thấy vị trí để thêm toolbar</p>";
}

// 3. Thêm checkbox vào bảng đơn hàng
// Tìm <thead> và thêm cột checkbox
$theadPattern = '<thead>
                            <tr>
                                <th>ID</th>';

$theadReplacement = '<thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="select-all-orders-table">
                                </th>
                                <th>ID</th>';

if (strpos($content, $theadPattern) !== false) {
    $content = str_replace($theadPattern, $theadReplacement, $content);
    echo "<p class='success'>✅ Đã thêm checkbox header vào bảng</p>";
}

// 4. Thêm checkbox vào từng row
// Tìm <td><?php echo $order['id']; ?></td> và thêm checkbox trước đó
$tdPattern = "<td><?php echo " . '$order[\'id\']; ?' . "></td>";
$tdReplacement = "<td class='order-checkbox-cell'>
                                        <input type='checkbox' class='order-checkbox' value='<?php echo " . '$order[\'id\']; ?' . ">'>
                                    </td>
                                    <td><?php echo " . '$order[\'id\']; ?' . "></td>";

if (strpos($content, $tdPattern) !== false) {
    $content = str_replace($tdPattern, $tdReplacement, $content);
    echo "<p class='success'>✅ Đã thêm checkbox vào từng row</p>";
}

// 5. Thêm nút Export vào cột thao tác
// Tìm phần action buttons và thêm nút export
$actionPattern = "<a href='./elements_LQA/mgiohang/orderDetailView.php?id=<?php echo " . '$order[\'id\']; ?' . ">' 
                                           class='action-btn btn btn-sm btn-info'
                                           title='Xem chi tiết'>
                                            <i class='fas fa-eye'></i>
                                        </a>";

$actionReplacement = "<a href='./elements_LQA/mgiohang/orderDetailView.php?id=<?php echo " . '$order[\'id\']; ?' . ">' 
                                           class='action-btn btn btn-sm btn-info'
                                           title='Xem chi tiết'>
                                            <i class='fas fa-eye'></i>
                                        </a>
                                        
                                        <!-- Export buttons -->
                                        <button class='action-btn btn btn-sm btn-secondary' 
                                                onclick='orderExporter.printInvoice(<?php echo " . '$order["id"]; ?' . ">)'
                                                title='In hóa đơn'>
                                            <i class='fas fa-print'></i>
                                        </button>
                                        
                                        <button class='action-btn btn btn-sm btn-danger' 
                                                onclick='orderExporter.exportSinglePDF(<?php echo " . '$order["id"]; ?' . ">)'
                                                title='Xuất PDF'>
                                            <i class='fas fa-file-pdf'></i>
                                        </button>
                                        
                                        <button class='action-btn btn btn-sm btn-success' 
                                                onclick='orderExporter.exportSingleExcel(<?php echo " . '$order["id"]; ?' . ">)'
                                                title='Xuất Excel'>
                                            <i class='fas fa-file-excel'></i>
                                        </button>";

if (strpos($content, $actionPattern) !== false) {
    $content = str_replace($actionPattern, $actionReplacement, $content);
    echo "<p class='success'>✅ Đã thêm nút Export vào cột thao tác</p>";
}

// 6. Thêm JavaScript vào cuối file (trước </body>)
$jsIncludes = "
    <!-- Export JavaScript -->
    <script src='./js_LQA/order_export.js'></script>
    <script>
        // Sync checkbox select-all
        document.getElementById('select-all-orders-table').addEventListener('change', function(e) {
            document.getElementById('select-all-orders').checked = e.target.checked;
            document.getElementById('select-all-orders').dispatchEvent(new Event('change'));
        });
        
        // Update selected count
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('order-checkbox') || e.target.id === 'select-all-orders') {
                const count = document.querySelectorAll('.order-checkbox:checked').length;
                const countDisplay = document.getElementById('selected-count');
                const countNumber = document.getElementById('count-number');
                
                if (count > 0) {
                    countDisplay.style.display = 'block';
                    countNumber.textContent = count;
                } else {
                    countDisplay.style.display = 'none';
                }
            }
        });
    </script>
";

if (strpos($content, '</body>') !== false) {
    $content = str_replace('</body>', $jsIncludes . "\n</body>", $content);
    echo "<p class='success'>✅ Đã thêm JavaScript includes</p>";
}

// Lưu file đã chỉnh sửa
file_put_contents($ordersFile, $content);

echo "<p class='success' style='font-size: 18px; margin-top: 30px;'>✅ HOÀN THÀNH!</p>";
echo "<p class='info'>Chức năng Export đã được tích hợp vào trang quản lý đơn hàng.</p>";

echo "<h3>📋 Các thay đổi đã thực hiện:</h3>";
echo "<ol>
        <li>✅ Thêm CSS order_export.css</li>
        <li>✅ Thêm Export Toolbar với các nút</li>
        <li>✅ Thêm checkbox vào header bảng</li>
        <li>✅ Thêm checkbox vào từng row</li>
        <li>✅ Thêm nút In/Xuất PDF/Xuất Excel vào cột thao tác</li>
        <li>✅ Thêm JavaScript order_export.js</li>
      </ol>";

echo "<h3>🎯 Bước tiếp theo:</h3>";
echo "<ol>
        <li>Truy cập trang quản lý đơn hàng: <a href='lequocanh/administrator/index.php?req=don_hang' class='btn'>Xem trang</a></li>
        <li>Kiểm tra các chức năng export</li>
        <li>Nếu có lỗi, file backup đã được lưu: <code>" . basename($backupFile) . "</code></li>
      </ol>";

echo "<h3>📚 Tính năng đã thêm:</h3>";
echo "<ul>
        <li>🖨️ <strong>In hóa đơn:</strong> Click nút In ở cột thao tác</li>
        <li>📄 <strong>Xuất PDF đơn lẻ:</strong> Click nút PDF ở cột thao tác</li>
        <li>📊 <strong>Xuất Excel đơn lẻ:</strong> Click nút Excel ở cột thao tác</li>
        <li>☑️ <strong>Chọn nhiều đơn:</strong> Tick checkbox → Click 'Xuất PDF' hoặc 'Xuất Excel'</li>
        <li>📋 <strong>Báo cáo tổng hợp:</strong> Click 'Báo cáo PDF' hoặc 'Báo cáo Excel'</li>
      </ul>";

echo "</div>
</body>
</html>";
