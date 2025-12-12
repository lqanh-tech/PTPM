<!DOCTYPE html>
<html>
<head>
    <title>Test Sản Phẩm Tương Tự</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1><i class="fas fa-layer-group me-2"></i>Test Hệ Thống Sản Phẩm Tương Tự</h1>
    
    <?php
    require_once 'bootstrap.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

    $db = new Database();
    $hanghoa = new hanghoa($db->getConnection());

    // Get a test product
    $sql = "SELECT * FROM hanghoa ORDER BY idhanghoa ASC LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $testProduct = $stmt->fetch(PDO::FETCH_OBJ);

    if ($testProduct) {
        echo "<div class='alert alert-info'>";
        echo "<h4>Sản phẩm test:</h4>";
        echo "<p><strong>ID:</strong> {$testProduct->idhanghoa}</p>";
        echo "<p><strong>Tên:</strong> {$testProduct->tenhanghoa}</p>";
        echo "<p><strong>Thương hiệu ID:</strong> {$testProduct->idThuongHieu}</p>";
        echo "<p><strong>Giá:</strong> " . number_format($testProduct->giathamkhao) . "₫</p>";
        echo "</div>";

        // Test the system
        echo "<h3><i class='fas fa-search me-2'></i>Kết quả tìm sản phẩm tương tự</h3>";
        
        $relatedProducts = $hanghoa->getRelatedProducts($testProduct->idhanghoa, 6);
        
        if (!empty($relatedProducts)) {
            echo "<div class='alert alert-success'>";
            echo "<i class='fas fa-check-circle me-2'></i>";
            echo "Tìm thấy " . count($relatedProducts) . " sản phẩm tương tự!";
            echo "</div>";
            
            echo "<div class='row'>";
            foreach ($relatedProducts as $rp) {
                $isSameBrand = ($rp->idThuongHieu == $testProduct->idThuongHieu);
                $priceDiff = abs($rp->giathamkhao - $testProduct->giathamkhao);
                $pricePercent = round(($priceDiff / $testProduct->giathamkhao) * 100, 1);
                
                echo "<div class='col-md-6 mb-3'>";
                echo "<div class='card'>";
                echo "<div class='card-body'>";
                echo "<h6 class='card-title'>{$rp->tenhanghoa}</h6>";
                echo "<p class='card-text'>";
                echo "<small class='text-muted'>ID: {$rp->idhanghoa}</small><br>";
                echo "<strong>Giá:</strong> " . number_format($rp->giathamkhao) . "₫";
                if ($priceDiff > 0) {
                    echo " <span class='text-muted'>({$pricePercent}% chênh lệch)</span>";
                }
                echo "<br>";
                echo "<strong>Thương hiệu ID:</strong> {$rp->idThuongHieu}";
                if ($isSameBrand) {
                    echo " <span class='badge bg-primary'>Cùng hãng</span>";
                } else {
                    echo " <span class='badge bg-warning text-dark'>Tầm giá tương tự</span>";
                }
                echo "</p>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
            
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<i class='fas fa-exclamation-triangle me-2'></i>";
            echo "Không tìm thấy sản phẩm tương tự.";
            echo "</div>";
            
            // Debug information
            echo "<h4>Thông tin debug:</h4>";
            
            // Check same brand products
            $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idThuongHieu = ? AND idhanghoa != ? AND trang_thai != 2";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$testProduct->idThuongHieu, $testProduct->idhanghoa]);
            $sameBrand = $stmt->fetch(PDO::FETCH_OBJ);
            echo "<p>Sản phẩm cùng thương hiệu: {$sameBrand->count}</p>";
            
            // Check similar price products
            $priceMin = $testProduct->giathamkhao * 0.7;
            $priceMax = $testProduct->giathamkhao * 1.3;
            $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE giathamkhao BETWEEN ? AND ? AND idhanghoa != ? AND trang_thai != 2";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$priceMin, $priceMax, $testProduct->idhanghoa]);
            $similarPrice = $stmt->fetch(PDO::FETCH_OBJ);
            echo "<p>Sản phẩm tầm giá tương tự (±30%): {$similarPrice->count}</p>";
            
            // Check total products
            $sql = "SELECT COUNT(*) as total FROM hanghoa WHERE idhanghoa != ? AND trang_thai != 2";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$testProduct->idhanghoa]);
            $total = $stmt->fetch(PDO::FETCH_OBJ);
            echo "<p>Tổng sản phẩm khác: {$total->total}</p>";
        }
        
        // Test with multiple products
        echo "<hr><h3><i class='fas fa-flask me-2'></i>Test với nhiều sản phẩm khác</h3>";
        
        $sql = "SELECT * FROM hanghoa ORDER BY RAND() LIMIT 5";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute();
        $randomProducts = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Sản phẩm</th><th>Thương hiệu ID</th><th>Giá</th><th>Sản phẩm tương tự</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($randomProducts as $rp) {
            $related = $hanghoa->getRelatedProducts($rp->idhanghoa, 4);
            echo "<tr>";
            echo "<td><strong>{$rp->tenhanghoa}</strong><br><small>ID: {$rp->idhanghoa}</small></td>";
            echo "<td>{$rp->idThuongHieu}</td>";
            echo "<td>" . number_format($rp->giathamkhao) . "₫</td>";
            echo "<td>";
            if (!empty($related)) {
                echo "<span class='badge bg-success'>" . count($related) . " sản phẩm</span>";
            } else {
                echo "<span class='badge bg-secondary'>Không có</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<i class='fas fa-exclamation-circle me-2'></i>";
        echo "Không có sản phẩm nào trong database để test.";
        echo "</div>";
    }
    ?>
    
    <div class="mt-4">
        <h3><i class='fas fa-info-circle me-2'></i>Logic Hệ Thống Mới</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-tag me-2"></i>Ưu tiên 1: Cùng thương hiệu</h5>
                    </div>
                    <div class="card-body">
                        <p>Tìm sản phẩm cùng thương hiệu (idThuongHieu)</p>
                        <p>Sắp xếp theo độ chênh lệch giá tăng dần</p>
                        <p><strong>Badge:</strong> "Cùng hãng" (màu xanh)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-dollar-sign me-2"></i>Ưu tiên 2: Tầm giá tương tự</h5>
                    </div>
                    <div class="card-body">
                        <p>Tìm sản phẩm trong khoảng ±30% giá</p>
                        <p>Loại trừ sản phẩm đã tìm ở ưu tiên 1</p>
                        <p><strong>Badge:</strong> Không có (mặc định)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <h5><i class="fas fa-lightbulb me-2"></i>Đặc điểm:</h5>
            <ul class="mb-0">
                <li>Đơn giản, dễ hiểu: chỉ 2 tiêu chí chính</li>
                <li>Ưu tiên sản phẩm có hình ảnh</li>
                <li>Loại trừ sản phẩm ngừng bán (trang_thai = 2)</li>
                <li>Không trùng lặp giữa 2 nhóm</li>
                <li>Hiển thị badge "Cùng hãng" rõ ràng</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>