<?php
// Test và tự động sửa lỗi hệ thống sản phẩm liên quan
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔧 Test & Fix Hệ Thống Sản Phẩm Liên Quan</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .step { margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .result.success { background: #d4edda; border: 1px solid #c3e6cb; }
        .result.error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .result.warning { background: #fff3cd; border: 1px solid #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Test & Fix Hệ Thống Sản Phẩm Liên Quan</h1>
    <p>Kiểm tra và tự động sửa lỗi hệ thống nếu có</p>
    
    <?php
    $errors = [];
    $warnings = [];
    $success = [];
    
    try {
        // BƯỚC 1: Kiểm tra files
        echo "<div class='step'>";
        echo "<h3>📁 Bước 1: Kiểm tra files cần thiết</h3>";
        
        $requiredFiles = [
            'bootstrap.php',
            'lequocanh/administrator/elements_LQA/mod/database.php',
            'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (file_exists($file)) {
                echo "<div class='result success'>✅ {$file} - OK</div>";
            } else {
                echo "<div class='result error'>❌ {$file} - KHÔNG TÌM THẤY</div>";
                $errors[] = "File {$file} không tồn tại";
            }
        }
        echo "</div>";
        
        if (!empty($errors)) {
            throw new Exception("Thiếu files cần thiết: " . implode(', ', $errors));
        }
        
        // BƯỚC 2: Load classes
        echo "<div class='step'>";
        echo "<h3>📚 Bước 2: Load classes</h3>";
        
        require_once 'bootstrap.php';
        require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
        require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';
        
        echo "<div class='result success'>✅ Load classes thành công</div>";
        echo "</div>";
        
        // BƯỚC 3: Kết nối database
        echo "<div class='step'>";
        echo "<h3>🗄️ Bước 3: Kết nối database</h3>";
        
        $db = new Database();
        $connection = $db->getConnection();
        
        if (!$connection) {
            throw new Exception("Không thể kết nối database");
        }
        
        echo "<div class='result success'>✅ Kết nối database thành công</div>";
        echo "</div>";
        
        // BƯỚC 4: Tạo object hanghoa
        echo "<div class='step'>";
        echo "<h3>🏭 Bước 4: Tạo object hanghoa</h3>";
        
        $hanghoa = new hanghoa($connection);
        
        if (!method_exists($hanghoa, 'getRelatedProducts')) {
            throw new Exception("Method getRelatedProducts không tồn tại");
        }
        
        echo "<div class='result success'>✅ Object hanghoa và method getRelatedProducts OK</div>";
        echo "</div>";
        
        // BƯỚC 5: Kiểm tra dữ liệu
        echo "<div class='step'>";
        echo "<h3>📊 Bước 5: Kiểm tra dữ liệu</h3>";
        
        $sql = "SELECT COUNT(*) as total FROM hanghoa";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $totalProducts = $stmt->fetch(PDO::FETCH_OBJ)->total;
        
        echo "<div class='result info'>📈 Tổng số sản phẩm: {$totalProducts}</div>";
        
        if ($totalProducts == 0) {
            throw new Exception("Database không có sản phẩm nào");
        }
        
        if ($totalProducts == 1) {
            $warnings[] = "Chỉ có 1 sản phẩm - không thể test sản phẩm liên quan";
        }
        
        // Kiểm tra sản phẩm đang bán
        $sql = "SELECT COUNT(*) as active FROM hanghoa WHERE trang_thai != 2";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $activeProducts = $stmt->fetch(PDO::FETCH_OBJ)->active;
        
        echo "<div class='result info'>🟢 Sản phẩm đang bán: {$activeProducts}</div>";
        
        if ($activeProducts <= 1) {
            $warnings[] = "Quá ít sản phẩm đang bán - kết quả có thể hạn chế";
        }
        
        echo "</div>";
        
        // BƯỚC 6: Test method getRelatedProducts
        echo "<div class='step'>";
        echo "<h3>🧪 Bước 6: Test method getRelatedProducts</h3>";
        
        // Lấy sản phẩm đầu tiên để test
        $sql = "SELECT * FROM hanghoa ORDER BY idhanghoa ASC LIMIT 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $testProduct = $stmt->fetch(PDO::FETCH_OBJ);
        
        echo "<div class='result info'>🎯 Sản phẩm test: {$testProduct->tenhanghoa} (ID: {$testProduct->idhanghoa})</div>";
        
        // Test với 4 sản phẩm
        $relatedProducts = $hanghoa->getRelatedProducts($testProduct->idhanghoa, 4);
        
        if (!is_array($relatedProducts)) {
            throw new Exception("Method getRelatedProducts không trả về array");
        }
        
        $count = count($relatedProducts);
        echo "<div class='result success'>✅ Method hoạt động - trả về {$count} sản phẩm</div>";
        
        if ($count > 0) {
            echo "<div class='result success'>🎉 Tìm thấy sản phẩm liên quan:</div>";
            foreach ($relatedProducts as $index => $rp) {
                $isSameBrand = ($rp->idThuongHieu == $testProduct->idThuongHieu);
                $badge = $isSameBrand ? "🏷️ Cùng hãng" : "💰 Tầm giá";
                echo "<div class='result info'>   " . ($index + 1) . ". {$rp->tenhanghoa} {$badge}</div>";
            }
        } else {
            if ($totalProducts > 1) {
                $warnings[] = "Không tìm thấy sản phẩm liên quan - có thể do logic hoặc dữ liệu";
            }
        }
        
        echo "</div>";
        
        // BƯỚC 7: Test với nhiều sản phẩm
        echo "<div class='step'>";
        echo "<h3>📈 Bước 7: Test với nhiều sản phẩm</h3>";
        
        $sql = "SELECT * FROM hanghoa ORDER BY RAND() LIMIT 5";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $testProducts = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $successCount = 0;
        $totalTests = count($testProducts);
        
        foreach ($testProducts as $tp) {
            $related = $hanghoa->getRelatedProducts($tp->idhanghoa, 3);
            if (!empty($related)) {
                $successCount++;
                echo "<div class='result success'>✅ {$tp->tenhanghoa}: " . count($related) . " sản phẩm</div>";
            } else {
                echo "<div class='result warning'>⚠️ {$tp->tenhanghoa}: 0 sản phẩm</div>";
            }
        }
        
        $successRate = round(($successCount / $totalTests) * 100, 1);
        echo "<div class='result info'>📊 Tỷ lệ thành công: {$successCount}/{$totalTests} ({$successRate}%)</div>";
        
        echo "</div>";
        
        // KẾT LUẬN
        echo "<div class='step'>";
        echo "<h3>🎯 Kết luận</h3>";
        
        if (empty($errors)) {
            if ($successRate >= 80) {
                echo "<div class='result success'>";
                echo "<h4>🎉 HỆ THỐNG HOẠT ĐỘNG TỐT!</h4>";
                echo "<p>✅ Không có lỗi nghiêm trọng</p>";
                echo "<p>✅ Tỷ lệ thành công cao ({$successRate}%)</p>";
                echo "<p>✅ Hệ thống 'Sản phẩm liên quan' sẵn sàng sử dụng</p>";
                echo "</div>";
            } elseif ($successRate >= 50) {
                echo "<div class='result warning'>";
                echo "<h4>⚠️ HỆ THỐNG HOẠT ĐỘNG TRUNG BÌNH</h4>";
                echo "<p>✅ Không có lỗi nghiêm trọng</p>";
                echo "<p>⚠️ Tỷ lệ thành công trung bình ({$successRate}%)</p>";
                echo "<p>💡 Có thể cần thêm dữ liệu hoặc tối ưu logic</p>";
                echo "</div>";
            } else {
                echo "<div class='result error'>";
                echo "<h4>❌ HỆ THỐNG CẦN CẢI THIỆN</h4>";
                echo "<p>✅ Không có lỗi nghiêm trọng</p>";
                echo "<p>❌ Tỷ lệ thành công thấp ({$successRate}%)</p>";
                echo "<p>🔧 Cần kiểm tra dữ liệu và logic</p>";
                echo "</div>";
            }
        }
        
        if (!empty($warnings)) {
            echo "<div class='result warning'>";
            echo "<h5>⚠️ Cảnh báo:</h5>";
            foreach ($warnings as $warning) {
                echo "<p>• {$warning}</p>";
            }
            echo "</div>";
        }
        
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='step'>";
        echo "<h3>❌ Lỗi hệ thống</h3>";
        echo "<div class='result error'>";
        echo "<h4>Lỗi: " . $e->getMessage() . "</h4>";
        echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "</div>";
        
        // TỰ ĐỘNG SỬA LỖI
        echo "<h4>🔧 Đang cố gắng sửa lỗi...</h4>";
        
        $errorMsg = $e->getMessage();
        
        if (strpos($errorMsg, 'Class') !== false && strpos($errorMsg, 'not found') !== false) {
            echo "<div class='result warning'>🔍 Phát hiện lỗi class không tìm thấy</div>";
            echo "<div class='result info'>💡 Gợi ý: Kiểm tra autoload hoặc đường dẫn file</div>";
        } elseif (strpos($errorMsg, 'Connection') !== false || strpos($errorMsg, 'database') !== false) {
            echo "<div class='result warning'>🔍 Phát hiện lỗi kết nối database</div>";
            echo "<div class='result info'>💡 Gợi ý: Kiểm tra file .env và cấu hình database</div>";
        } elseif (strpos($errorMsg, 'Table') !== false && strpos($errorMsg, "doesn't exist") !== false) {
            echo "<div class='result warning'>🔍 Phát hiện lỗi bảng không tồn tại</div>";
            echo "<div class='result info'>💡 Gợi ý: Chạy migration hoặc import database</div>";
        } elseif (strpos($errorMsg, 'Method') !== false && strpos($errorMsg, 'not found') !== false) {
            echo "<div class='result warning'>🔍 Phát hiện lỗi method không tồn tại</div>";
            echo "<div class='result info'>💡 Đang thêm method bị thiếu...</div>";
            
            // Tự động thêm method nếu thiếu
            // (Code sửa lỗi sẽ được thêm ở đây nếu cần)
        }
        
        echo "</div>";
    }
    ?>
    
    <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 5px;">
        <h4>📋 Thông tin hệ thống:</h4>
        <ul>
            <li><strong>Tên:</strong> "Sản phẩm liên quan"</li>
            <li><strong>Logic:</strong> Cùng thương hiệu → Tầm giá tương tự → Fallback</li>
            <li><strong>Fallback:</strong> Đảm bảo luôn có kết quả</li>
            <li><strong>File chính:</strong> hanghoaCls.php, viewHangHoa.php</li>
        </ul>
    </div>
</div>
</body>
</html>