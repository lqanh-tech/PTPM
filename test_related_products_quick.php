<!DOCTYPE html>
<html>
<head>
    <title>Test Sản Phẩm Liên Quan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>🔍 Test Sản Phẩm Liên Quan</h1>
    
    <?php
    require_once 'bootstrap.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

    $db = new Database();
    $hanghoa = new hanghoa($db->getConnection());

    // Test với sản phẩm đầu tiên
    $sql = "SELECT * FROM hanghoa ORDER BY idhanghoa ASC LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $testProduct = $stmt->fetch(PDO::FETCH_OBJ);

    if ($testProduct) {
        echo "<div class='alert alert-info'>";
        echo "<h4>🧪 Sản phẩm test:</h4>";
        echo "<p><strong>ID:</strong> {$testProduct->idhanghoa}</p>";
        echo "<p><strong>Tên:</strong> {$testProduct->tenhanghoa}</p>";
        echo "<p><strong>Thương hiệu ID:</strong> {$testProduct->idThuongHieu}</p>";
        echo "<p><strong>Giá:</strong> " . number_format($testProduct->giathamkhao) . "₫</p>";
        echo "</div>";

        // Test hệ thống
        echo "<h3>📋 Kết quả:</h3>";
        
        $relatedProducts = $hanghoa->getRelatedProducts($testProduct->idhanghoa, 4);
        
        if (!empty($relatedProducts)) {
            echo "<div class='alert alert-success'>";
            echo "✅ <strong>THÀNH CÔNG!</strong> Tìm thấy " . count($relatedProducts) . " sản phẩm liên quan";
            echo "</div>";
            
            echo "<div class='row'>";
            foreach ($relatedProducts as $index => $rp) {
                $isSameBrand = ($rp->idThuongHieu == $testProduct->idThuongHieu);
                $priceDiff = abs($rp->giathamkhao - $testProduct->giathamkhao);
                $pricePercent = $testProduct->giathamkhao > 0 ? round(($priceDiff / $testProduct->giathamkhao) * 100, 1) : 0;
                
                echo "<div class='col-md-6 mb-3'>";
                echo "<div class='card " . ($isSameBrand ? "border-primary" : "border-secondary") . "'>";
                echo "<div class='card-header " . ($isSameBrand ? "bg-primary text-white" : "bg-light") . "'>";
                echo "<h6 class='mb-0'>" . ($index + 1) . ". {$rp->tenhanghoa}</h6>";
                echo "</div>";
                echo "<div class='card-body'>";
                echo "<p class='mb-1'><strong>ID:</strong> {$rp->idhanghoa}</p>";
                echo "<p class='mb-1'><strong>Giá:</strong> " . number_format($rp->giathamkhao) . "₫";
                if ($priceDiff > 0) {
                    echo " <small class='text-muted'>({$pricePercent}% chênh lệch)</small>";
                }
                echo "</p>";
                echo "<p class='mb-0'><strong>Thương hiệu:</strong> {$rp->idThuongHieu} ";
                if ($isSameBrand) {
                    echo "<span class='badge bg-primary'>Cùng hãng</span>";
                } else {
                    echo "<span class='badge bg-secondary'>Khác hãng</span>";
                }
                echo "</p>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
            
        } else {
            echo "<div class='alert alert-danger'>";
            echo "❌ <strong>LỖI!</strong> Không tìm thấy sản phẩm liên quan nào";
            echo "</div>";
            
            // Debug info
            echo "<h4>🔍 Debug Info:</h4>";
            
            // Tổng số sản phẩm
            $sql = "SELECT COUNT(*) as total FROM hanghoa WHERE idhanghoa != ? AND trang_thai != 2";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$testProduct->idhanghoa]);
            $total = $stmt->fetch(PDO::FETCH_OBJ);
            echo "<p>📊 Tổng sản phẩm khả dụng: <strong>{$total->total}</strong></p>";
            
            if ($total->total == 0) {
                echo "<div class='alert alert-warning'>";
                echo "⚠️ Không có sản phẩm nào khác trong database hoặc tất cả đều bị ngừng bán (trang_thai = 2)";
                echo "</div>";
            }
        }
        
        // Test với nhiều sản phẩm
        echo "<hr><h3>🎯 Test nhanh với 5 sản phẩm khác:</h3>";
        
        $sql = "SELECT * FROM hanghoa ORDER BY RAND() LIMIT 5";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute();
        $randomProducts = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead class='table-dark'>";
        echo "<tr><th>Sản phẩm</th><th>Thương hiệu</th><th>Giá</th><th>Sản phẩm liên quan</th></tr>";
        echo "</thead><tbody>";
        
        foreach ($randomProducts as $rp) {
            $related = $hanghoa->getRelatedProducts($rp->idhanghoa, 3);
            echo "<tr>";
            echo "<td><strong>{$rp->tenhanghoa}</strong><br><small class='text-muted'>ID: {$rp->idhanghoa}</small></td>";
            echo "<td>{$rp->idThuongHieu}</td>";
            echo "<td>" . number_format($rp->giathamkhao) . "₫</td>";
            echo "<td>";
            if (!empty($related)) {
                echo "<span class='badge bg-success'>" . count($related) . " sản phẩm</span>";
            } else {
                echo "<span class='badge bg-danger'>0 sản phẩm</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        echo "</div>";
        
    } else {
        echo "<div class='alert alert-danger'>";
        echo "❌ Không có sản phẩm nào trong database để test!";
        echo "</div>";
    }
    ?>
    
    <div class="mt-4 alert alert-info">
        <h5>📝 Logic hệ thống:</h5>
        <ol>
            <li><strong>Ưu tiên 1:</strong> Sản phẩm cùng thương hiệu</li>
            <li><strong>Ưu tiên 2:</strong> Sản phẩm tầm giá tương tự (±30%)</li>
            <li><strong>Ưu tiên 3:</strong> Bất kỳ sản phẩm nào khác (fallback)</li>
        </ol>
        <p class="mb-0"><strong>Đảm bảo:</strong> Luôn có sản phẩm hiển thị (trừ khi database trống)</p>
    </div>
</div>
</body>
</html>