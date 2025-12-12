<!DOCTYPE html>
<html>
<head>
    <title>🧪 Test Hệ Thống Sản Phẩm Liên Quan</title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-section { border-left: 4px solid #007bff; padding-left: 15px; margin: 20px 0; }
        .success { border-color: #28a745; }
        .error { border-color: #dc3545; }
        .warning { border-color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1>🧪 Test Hệ Thống Sản Phẩm Liên Quan</h1>
    <p class="text-muted">Kiểm tra hoạt động của hệ thống "Sản phẩm liên quan" mới</p>
    
    <?php
    $startTime = microtime(true);
    
    try {
        echo "<div class='test-section success'>";
        echo "<h3>📋 Bước 1: Khởi tạo hệ thống</h3>";
        
        require_once 'bootstrap.php';
        require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
        require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

        $db = new Database();
        $hanghoa = new hanghoa($db->getConnection());

        echo "<p>✅ Kết nối database thành công</p>";
        echo "<p>✅ Load class hanghoa thành công</p>";
        echo "</div>";

        // Test 1: Lấy sản phẩm để test
        echo "<div class='test-section'>";
        echo "<h3>📋 Bước 2: Lấy sản phẩm test</h3>";
        
        $sql = "SELECT * FROM hanghoa ORDER BY idhanghoa ASC LIMIT 1";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute();
        $testProduct = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$testProduct) {
            echo "<div class='alert alert-danger'>❌ KHÔNG có sản phẩm nào trong database!</div>";
            exit;
        }

        echo "<div class='alert alert-info'>";
        echo "<h5>🎯 Sản phẩm test:</h5>";
        echo "<p><strong>ID:</strong> {$testProduct->idhanghoa}</p>";
        echo "<p><strong>Tên:</strong> {$testProduct->tenhanghoa}</p>";
        echo "<p><strong>Thương hiệu ID:</strong> {$testProduct->idThuongHieu}</p>";
        echo "<p><strong>Giá:</strong> " . number_format($testProduct->giathamkhao) . "₫</p>";
        echo "<p><strong>Trạng thái:</strong> {$testProduct->trang_thai}</p>";
        echo "</div>";
        echo "</div>";

        // Test 2: Gọi method getRelatedProducts
        echo "<div class='test-section'>";
        echo "<h3>📋 Bước 3: Test method getRelatedProducts</h3>";
        
        $relatedProducts = $hanghoa->getRelatedProducts($testProduct->idhanghoa, 4);
        
        if (empty($relatedProducts)) {
            echo "<div class='alert alert-warning'>";
            echo "<h5>⚠️ Không tìm thấy sản phẩm liên quan!</h5>";
            echo "</div>";
            
            // Debug info
            echo "<h5>🔍 Thông tin debug:</h5>";
            
            // Tổng số sản phẩm
            $sql = "SELECT COUNT(*) as total FROM hanghoa WHERE idhanghoa != ? AND trang_thai != 2";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$testProduct->idhanghoa]);
            $total = $stmt->fetch(PDO::FETCH_OBJ);
            echo "<p>📊 Tổng sản phẩm khả dụng: <strong>{$total->total}</strong></p>";
            
            // Sản phẩm cùng thương hiệu
            if (!empty($testProduct->idThuongHieu)) {
                $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idThuongHieu = ? AND idhanghoa != ? AND trang_thai != 2";
                $stmt = $db->getConnection()->prepare($sql);
                $stmt->execute([$testProduct->idThuongHieu, $testProduct->idhanghoa]);
                $sameBrand = $stmt->fetch(PDO::FETCH_OBJ);
                echo "<p>🏷️ Sản phẩm cùng thương hiệu: <strong>{$sameBrand->count}</strong></p>";
            }
            
            // Sản phẩm tầm giá tương tự
            $priceMin = $testProduct->giathamkhao * 0.7;
            $priceMax = $testProduct->giathamkhao * 1.3;
            $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE giathamkhao BETWEEN ? AND ? AND idhanghoa != ? AND trang_thai != 2";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$priceMin, $priceMax, $testProduct->idhanghoa]);
            $similarPrice = $stmt->fetch(PDO::FETCH_OBJ);
            echo "<p>💰 Sản phẩm tầm giá tương tự (±30%): <strong>{$similarPrice->count}</strong></p>";
            
            if ($total->total == 0) {
                echo "<div class='alert alert-danger'>❌ Database không có sản phẩm nào khác!</div>";
            }
            
        } else {
            echo "<div class='alert alert-success'>";
            echo "<h5>✅ Tìm thấy " . count($relatedProducts) . " sản phẩm liên quan!</h5>";
            echo "</div>";
            
            echo "<div class='row'>";
            foreach ($relatedProducts as $index => $rp) {
                $isSameBrand = ($rp->idThuongHieu == $testProduct->idThuongHieu);
                $priceDiff = abs($rp->giathamkhao - $testProduct->giathamkhao);
                $pricePercent = $testProduct->giathamkhao > 0 ? round(($priceDiff / $testProduct->giathamkhao) * 100, 1) : 0;
                
                echo "<div class='col-md-6 mb-3'>";
                echo "<div class='card " . ($isSameBrand ? "border-success" : "border-info") . "'>";
                echo "<div class='card-header " . ($isSameBrand ? "bg-success text-white" : "bg-info text-white") . "'>";
                echo "<h6 class='mb-0'>" . ($index + 1) . ". {$rp->tenhanghoa}</h6>";
                echo "</div>";
                echo "<div class='card-body'>";
                echo "<p class='mb-1'><strong>ID:</strong> {$rp->idhanghoa}</p>";
                echo "<p class='mb-1'><strong>Thương hiệu:</strong> {$rp->idThuongHieu} ";
                if ($isSameBrand) {
                    echo "<span class='badge bg-success'>Cùng hãng</span>";
                } else {
                    echo "<span class='badge bg-info'>Khác hãng</span>";
                }
                echo "</p>";
                echo "<p class='mb-1'><strong>Giá:</strong> " . number_format($rp->giathamkhao) . "₫";
                if ($priceDiff > 0) {
                    echo " <small class='text-muted'>({$pricePercent}% chênh lệch)</small>";
                }
                echo "</p>";
                echo "<p class='mb-0'><strong>Trạng thái:</strong> {$rp->trang_thai}</p>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        }
        echo "</div>";

        // Test 3: Test với nhiều sản phẩm
        echo "<div class='test-section'>";
        echo "<h3>📋 Bước 4: Test với nhiều sản phẩm</h3>";
        
        $sql = "SELECT * FROM hanghoa ORDER BY RAND() LIMIT 10";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute();
        $randomProducts = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $successCount = 0;
        $totalCount = count($randomProducts);
        
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead class='table-dark'>";
        echo "<tr><th>STT</th><th>Sản phẩm</th><th>Thương hiệu</th><th>Giá</th><th>Kết quả</th></tr>";
        echo "</thead><tbody>";
        
        foreach ($randomProducts as $index => $rp) {
            $related = $hanghoa->getRelatedProducts($rp->idhanghoa, 3);
            $hasResults = !empty($related);
            
            if ($hasResults) $successCount++;
            
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td><strong>{$rp->tenhanghoa}</strong><br><small class='text-muted'>ID: {$rp->idhanghoa}</small></td>";
            echo "<td>{$rp->idThuongHieu}</td>";
            echo "<td>" . number_format($rp->giathamkhao) . "₫</td>";
            echo "<td>";
            if ($hasResults) {
                echo "<span class='badge bg-success'>" . count($related) . " sản phẩm</span>";
            } else {
                echo "<span class='badge bg-danger'>0 sản phẩm</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        echo "</div>";
        
        $successRate = round(($successCount / $totalCount) * 100, 1);
        
        echo "<div class='alert " . ($successRate >= 80 ? "alert-success" : ($successRate >= 50 ? "alert-warning" : "alert-danger")) . "'>";
        echo "<h5>📊 Kết quả tổng quan:</h5>";
        echo "<p><strong>Thành công:</strong> {$successCount}/{$totalCount} sản phẩm</p>";
        echo "<p><strong>Tỷ lệ thành công:</strong> {$successRate}%</p>";
        echo "</div>";
        echo "</div>";

        // Kết luận
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        echo "<div class='test-section " . ($successRate >= 80 ? "success" : ($successRate >= 50 ? "warning" : "error")) . "'>";
        echo "<h3>🎉 Kết luận</h3>";
        
        if ($successRate >= 80) {
            echo "<div class='alert alert-success'>";
            echo "<h5>✅ HỆ THỐNG HOẠT ĐỘNG TỐT!</h5>";
            echo "<p>Hệ thống 'Sản phẩm liên quan' đang hoạt động bình thường với tỷ lệ thành công cao.</p>";
        } elseif ($successRate >= 50) {
            echo "<div class='alert alert-warning'>";
            echo "<h5>⚠️ HỆ THỐNG HOẠT ĐỘNG TRUNG BÌNH</h5>";
            echo "<p>Hệ thống hoạt động nhưng có thể cần tối ưu thêm dữ liệu hoặc logic.</p>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<h5>❌ HỆ THỐNG CẦN KIỂM TRA</h5>";
            echo "<p>Tỷ lệ thành công thấp, cần kiểm tra dữ liệu và logic hệ thống.</p>";
        }
        
        echo "<p><strong>Thời gian thực thi:</strong> {$executionTime}ms</p>";
        echo "</div>";
        echo "</div>";

    } catch (Exception $e) {
        echo "<div class='test-section error'>";
        echo "<h3>❌ Lỗi hệ thống</h3>";
        echo "<div class='alert alert-danger'>";
        echo "<h5>Lỗi: " . $e->getMessage() . "</h5>";
        echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
        echo "</div>";
    }
    ?>
    
    <div class="mt-4 alert alert-info">
        <h5>📝 Thông tin hệ thống:</h5>
        <ul>
            <li><strong>Logic:</strong> Ưu tiên 1: Cùng thương hiệu → Ưu tiên 2: Tầm giá tương tự → Ưu tiên 3: Bất kỳ sản phẩm nào</li>
            <li><strong>Tên hiển thị:</strong> "Sản phẩm liên quan"</li>
            <li><strong>Fallback:</strong> Đảm bảo luôn có kết quả (trừ khi DB trống)</li>
            <li><strong>Loại trừ:</strong> Sản phẩm ngừng bán (trang_thai = 2)</li>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>