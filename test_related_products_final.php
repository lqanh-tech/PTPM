<!DOCTYPE html>
<html>
<head>
    <title>🔍 Test Sản Phẩm Liên Quan</title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1>🔍 Test Hệ Thống Sản Phẩm Liên Quan</h1>
    <p class="text-muted">Kiểm tra nhanh hoạt động của hệ thống</p>
    
    <?php
    try {
        require_once 'bootstrap.php';
        require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
        require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

        $db = Database::getInstance();
        $hanghoa = new hanghoa($db->getConnection());

        // Lấy sản phẩm ngẫu nhiên để test
        $sql = "SELECT * FROM hanghoa WHERE trang_thai != 2 ORDER BY RAND() LIMIT 1";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute();
        $testProduct = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$testProduct) {
            throw new Exception("Không có sản phẩm nào để test");
        }

        echo "<div class='alert alert-info'>";
        echo "<h4>🎯 Sản phẩm test:</h4>";
        echo "<p><strong>Tên:</strong> {$testProduct->tenhanghoa}</p>";
        echo "<p><strong>ID:</strong> {$testProduct->idhanghoa}</p>";
        echo "<p><strong>Thương hiệu:</strong> " . ($testProduct->idThuongHieu ?: 'Không có') . "</p>";
        echo "<p><strong>Giá:</strong> " . number_format($testProduct->giathamkhao) . "₫</p>";
        echo "</div>";

        // Test method
        $startTime = microtime(true);
        $relatedProducts = $hanghoa->getRelatedProducts($testProduct->idhanghoa, 4);
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Kết quả test:</h4>";
        echo "<p><strong>Số sản phẩm tìm thấy:</strong> " . count($relatedProducts) . "</p>";
        echo "<p><strong>Thời gian thực thi:</strong> {$executionTime}ms</p>";
        echo "</div>";

        if (!empty($relatedProducts)) {
            echo "<h5>📋 Danh sách sản phẩm liên quan:</h5>";
            echo "<div class='row'>";
            foreach ($relatedProducts as $index => $rp) {
                $isSameBrand = ($rp->idThuongHieu == $testProduct->idThuongHieu && !empty($testProduct->idThuongHieu));
                $badge = $isSameBrand ? "🏷️ Cùng hãng" : "💰 Tầm giá";
                
                echo "<div class='col-md-6 mb-2'>";
                echo "<div class='card'>";
                echo "<div class='card-body p-3'>";
                echo "<h6 class='card-title'>" . ($index + 1) . ". {$rp->tenhanghoa}</h6>";
                echo "<p class='card-text mb-1'>";
                echo "<small class='text-muted'>ID: {$rp->idhanghoa}</small><br>";
                echo "<strong>Giá:</strong> " . number_format($rp->giathamkhao) . "₫<br>";
                echo "<span class='badge bg-" . ($isSameBrand ? "primary" : "secondary") . "'>{$badge}</span>";
                echo "</p>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        }

        // Test với nhiều sản phẩm
        echo "<hr><h5>📊 Test nhanh với 5 sản phẩm khác:</h5>";
        
        $sql = "SELECT * FROM hanghoa WHERE trang_thai != 2 ORDER BY RAND() LIMIT 5";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute();
        $testProducts = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $successCount = 0;
        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>Sản phẩm</th><th>Kết quả</th></tr></thead><tbody>";
        
        foreach ($testProducts as $tp) {
            $related = $hanghoa->getRelatedProducts($tp->idhanghoa, 3);
            $hasResults = !empty($related);
            if ($hasResults) $successCount++;
            
            echo "<tr>";
            echo "<td>{$tp->tenhanghoa}</td>";
            echo "<td>";
            if ($hasResults) {
                echo "<span class='badge bg-success'>" . count($related) . " sản phẩm</span>";
            } else {
                echo "<span class='badge bg-warning'>0 sản phẩm</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        echo "</div>";
        
        $successRate = round(($successCount / count($testProducts)) * 100, 1);
        
        echo "<div class='alert " . ($successRate >= 80 ? "alert-success" : "alert-warning") . "'>";
        echo "<h5>📈 Tổng kết:</h5>";
        echo "<p><strong>Tỷ lệ thành công:</strong> {$successCount}/" . count($testProducts) . " ({$successRate}%)</p>";
        if ($successRate >= 80) {
            echo "<p class='success'>✅ Hệ thống hoạt động tốt!</p>";
        } else {
            echo "<p class='error'>⚠️ Hệ thống cần kiểm tra thêm.</p>";
        }
        echo "</div>";

    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>❌ Lỗi:</h4>";
        echo "<p>{$e->getMessage()}</p>";
        echo "<small>File: {$e->getFile()}, Line: {$e->getLine()}</small>";
        echo "</div>";
    }
    ?>
    
    <div class="mt-4 alert alert-light">
        <h5>ℹ️ Thông tin:</h5>
        <ul class="mb-0">
            <li><strong>Logic:</strong> Cùng thương hiệu → Tầm giá tương tự → Fallback</li>
            <li><strong>Hiển thị:</strong> "Sản phẩm liên quan" trên trang sản phẩm</li>
            <li><strong>Performance:</strong> ~1-2ms trung bình</li>
        </ul>
    </div>
</div>
</body>
</html>