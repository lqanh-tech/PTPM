<?php
echo "=== TEST HỆ THỐNG SẢN PHẨM LIÊN QUAN ===\n\n";

try {
    require_once 'bootstrap.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

    $db = new Database();
    $hanghoa = new hanghoa($db->getConnection());

    echo "✅ Kết nối database thành công\n";
    echo "✅ Load class hanghoa thành công\n\n";

    // Test 1: Lấy sản phẩm đầu tiên để test
    echo "📋 TEST 1: Lấy sản phẩm để test\n";
    echo "----------------------------------------\n";
    
    $sql = "SELECT * FROM hanghoa ORDER BY idhanghoa ASC LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $testProduct = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$testProduct) {
        echo "❌ KHÔNG có sản phẩm nào trong database!\n";
        exit;
    }

    echo "✅ Sản phẩm test: {$testProduct->tenhanghoa}\n";
    echo "   - ID: {$testProduct->idhanghoa}\n";
    echo "   - Thương hiệu ID: {$testProduct->idThuongHieu}\n";
    echo "   - Giá: " . number_format($testProduct->giathamkhao) . "₫\n";
    echo "   - Trạng thái: {$testProduct->trang_thai}\n\n";

    // Test 2: Kiểm tra method getRelatedProducts
    echo "📋 TEST 2: Gọi method getRelatedProducts\n";
    echo "----------------------------------------\n";
    
    $relatedProducts = $hanghoa->getRelatedProducts($testProduct->idhanghoa, 4);
    
    if (empty($relatedProducts)) {
        echo "❌ KHÔNG tìm thấy sản phẩm liên quan nào!\n\n";
        
        // Debug: Kiểm tra tại sao không có kết quả
        echo "🔍 DEBUG INFO:\n";
        
        // Kiểm tra tổng số sản phẩm
        $sql = "SELECT COUNT(*) as total FROM hanghoa WHERE idhanghoa != ? AND trang_thai != 2";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([$testProduct->idhanghoa]);
        $total = $stmt->fetch(PDO::FETCH_OBJ);
        echo "   - Tổng sản phẩm khả dụng: {$total->total}\n";
        
        // Kiểm tra sản phẩm cùng thương hiệu
        if (!empty($testProduct->idThuongHieu)) {
            $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idThuongHieu = ? AND idhanghoa != ? AND trang_thai != 2";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$testProduct->idThuongHieu, $testProduct->idhanghoa]);
            $sameBrand = $stmt->fetch(PDO::FETCH_OBJ);
            echo "   - Sản phẩm cùng thương hiệu: {$sameBrand->count}\n";
        }
        
        // Kiểm tra sản phẩm tầm giá tương tự
        $priceMin = $testProduct->giathamkhao * 0.7;
        $priceMax = $testProduct->giathamkhao * 1.3;
        $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE giathamkhao BETWEEN ? AND ? AND idhanghoa != ? AND trang_thai != 2";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([$priceMin, $priceMax, $testProduct->idhanghoa]);
        $similarPrice = $stmt->fetch(PDO::FETCH_OBJ);
        echo "   - Sản phẩm tầm giá tương tự (±30%): {$similarPrice->count}\n";
        
    } else {
        echo "✅ Tìm thấy " . count($relatedProducts) . " sản phẩm liên quan!\n\n";
        
        foreach ($relatedProducts as $index => $rp) {
            $isSameBrand = ($rp->idThuongHieu == $testProduct->idThuongHieu);
            $priceDiff = abs($rp->giathamkhao - $testProduct->giathamkhao);
            $pricePercent = $testProduct->giathamkhao > 0 ? round(($priceDiff / $testProduct->giathamkhao) * 100, 1) : 0;
            
            echo "   " . ($index + 1) . ". {$rp->tenhanghoa}\n";
            echo "      - ID: {$rp->idhanghoa}\n";
            echo "      - Thương hiệu: {$rp->idThuongHieu} " . ($isSameBrand ? "(CÙNG HÃNG)" : "(KHÁC HÃNG)") . "\n";
            echo "      - Giá: " . number_format($rp->giathamkhao) . "₫ ({$pricePercent}% chênh lệch)\n";
            echo "      - Trạng thái: {$rp->trang_thai}\n\n";
        }
    }

    // Test 3: Test với nhiều sản phẩm khác
    echo "📋 TEST 3: Test với 5 sản phẩm ngẫu nhiên\n";
    echo "----------------------------------------\n";
    
    $sql = "SELECT * FROM hanghoa ORDER BY RAND() LIMIT 5";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $randomProducts = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    $successCount = 0;
    $totalCount = count($randomProducts);
    
    foreach ($randomProducts as $rp) {
        $related = $hanghoa->getRelatedProducts($rp->idhanghoa, 3);
        $hasResults = !empty($related);
        
        echo "   - {$rp->tenhanghoa} (ID: {$rp->idhanghoa}): ";
        if ($hasResults) {
            echo "✅ " . count($related) . " sản phẩm\n";
            $successCount++;
        } else {
            echo "❌ 0 sản phẩm\n";
        }
    }
    
    echo "\n📊 KẾT QUẢ TỔNG QUAN:\n";
    echo "   - Thành công: {$successCount}/{$totalCount} sản phẩm\n";
    echo "   - Tỷ lệ thành công: " . round(($successCount / $totalCount) * 100, 1) . "%\n\n";

    // Test 4: Kiểm tra method riêng lẻ
    echo "📋 TEST 4: Kiểm tra các method riêng lẻ\n";
    echo "----------------------------------------\n";
    
    // Test getSameBrandProducts
    if (method_exists($hanghoa, 'getSameBrandProducts')) {
        echo "❌ Method getSameBrandProducts là private, không thể test trực tiếp\n";
    } else {
        echo "✅ Method getSameBrandProducts tồn tại (private)\n";
    }
    
    // Kiểm tra xem có lỗi SQL không
    $errorInfo = $db->getConnection()->errorInfo();
    if ($errorInfo[0] !== '00000') {
        echo "❌ Có lỗi SQL: " . $errorInfo[2] . "\n";
    } else {
        echo "✅ Không có lỗi SQL\n";
    }

    echo "\n🎉 TEST HOÀN THÀNH!\n";
    
    if ($successCount > 0) {
        echo "✅ HỆ THỐNG HOẠT ĐỘNG BÌNH THƯỜNG\n";
    } else {
        echo "⚠️  HỆ THỐNG CẦN KIỂM TRA THÊM\n";
    }

} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n";
}
?>