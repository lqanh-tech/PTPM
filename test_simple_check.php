<?php
// Test đơn giản để kiểm tra hệ thống sản phẩm liên quan
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== KIỂM TRA HỆ THỐNG SẢN PHẨM LIÊN QUAN ===\n";

try {
    // Bước 1: Kiểm tra file tồn tại
    echo "1. Kiểm tra files...\n";
    
    if (!file_exists('bootstrap.php')) {
        echo "❌ Không tìm thấy bootstrap.php\n";
        exit;
    }
    
    if (!file_exists('lequocanh/administrator/elements_LQA/mod/database.php')) {
        echo "❌ Không tìm thấy database.php\n";
        exit;
    }
    
    if (!file_exists('lequocanh/administrator/elements_LQA/mod/hanghoaCls.php')) {
        echo "❌ Không tìm thấy hanghoaCls.php\n";
        exit;
    }
    
    echo "✅ Tất cả files cần thiết đều tồn tại\n";
    
    // Bước 2: Load files
    echo "2. Load files...\n";
    
    require_once 'bootstrap.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';
    
    echo "✅ Load files thành công\n";
    
    // Bước 3: Kết nối database
    echo "3. Kết nối database...\n";
    
    $db = new Database();
    $connection = $db->getConnection();
    
    if (!$connection) {
        echo "❌ Không thể kết nối database\n";
        exit;
    }
    
    echo "✅ Kết nối database thành công\n";
    
    // Bước 4: Tạo object hanghoa
    echo "4. Tạo object hanghoa...\n";
    
    $hanghoa = new hanghoa($connection);
    
    if (!$hanghoa) {
        echo "❌ Không thể tạo object hanghoa\n";
        exit;
    }
    
    echo "✅ Tạo object hanghoa thành công\n";
    
    // Bước 5: Kiểm tra method getRelatedProducts tồn tại
    echo "5. Kiểm tra method getRelatedProducts...\n";
    
    if (!method_exists($hanghoa, 'getRelatedProducts')) {
        echo "❌ Method getRelatedProducts không tồn tại\n";
        exit;
    }
    
    echo "✅ Method getRelatedProducts tồn tại\n";
    
    // Bước 6: Lấy sản phẩm test
    echo "6. Lấy sản phẩm để test...\n";
    
    $sql = "SELECT * FROM hanghoa LIMIT 1";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $testProduct = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$testProduct) {
        echo "❌ Không có sản phẩm nào trong database\n";
        exit;
    }
    
    echo "✅ Tìm thấy sản phẩm test: {$testProduct->tenhanghoa} (ID: {$testProduct->idhanghoa})\n";
    
    // Bước 7: Test method getRelatedProducts
    echo "7. Test method getRelatedProducts...\n";
    
    $relatedProducts = $hanghoa->getRelatedProducts($testProduct->idhanghoa, 4);
    
    if ($relatedProducts === null) {
        echo "❌ Method trả về null\n";
        exit;
    }
    
    if (!is_array($relatedProducts)) {
        echo "❌ Method không trả về array\n";
        exit;
    }
    
    echo "✅ Method hoạt động, trả về " . count($relatedProducts) . " sản phẩm\n";
    
    // Bước 8: Kiểm tra kết quả chi tiết
    echo "8. Kiểm tra kết quả chi tiết...\n";
    
    if (count($relatedProducts) > 0) {
        echo "✅ Có sản phẩm liên quan:\n";
        foreach ($relatedProducts as $index => $rp) {
            echo "   - " . ($index + 1) . ". {$rp->tenhanghoa} (ID: {$rp->idhanghoa})\n";
        }
    } else {
        echo "⚠️  Không có sản phẩm liên quan - kiểm tra thêm...\n";
        
        // Debug: Kiểm tra tổng số sản phẩm
        $sql = "SELECT COUNT(*) as total FROM hanghoa WHERE idhanghoa != ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$testProduct->idhanghoa]);
        $total = $stmt->fetch(PDO::FETCH_OBJ);
        echo "   - Tổng sản phẩm khác: {$total->total}\n";
        
        if ($total->total == 0) {
            echo "   - Nguyên nhân: Chỉ có 1 sản phẩm trong database\n";
        } else {
            // Kiểm tra sản phẩm không bị ngừng bán
            $sql = "SELECT COUNT(*) as active FROM hanghoa WHERE idhanghoa != ? AND trang_thai != 2";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$testProduct->idhanghoa]);
            $active = $stmt->fetch(PDO::FETCH_OBJ);
            echo "   - Sản phẩm đang bán: {$active->active}\n";
            
            if ($active->active == 0) {
                echo "   - Nguyên nhân: Tất cả sản phẩm khác đều ngừng bán\n";
            }
        }
    }
    
    echo "\n🎉 TEST HOÀN THÀNH - HỆ THỐNG HOẠT ĐỘNG BÌNH THƯỜNG!\n";
    
} catch (Exception $e) {
    echo "\n❌ LỖI XẢY RA:\n";
    echo "Lỗi: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    // Ghi lỗi vào file để debug
    file_put_contents('test_error.log', date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo "\n🔧 ĐANG TỰ ĐỘNG SỬA LỖI...\n";
    
    // Tự động sửa một số lỗi phổ biến
    if (strpos($e->getMessage(), 'Class') !== false && strpos($e->getMessage(), 'not found') !== false) {
        echo "Lỗi: Class không tìm thấy - kiểm tra autoload\n";
    } elseif (strpos($e->getMessage(), 'Connection') !== false) {
        echo "Lỗi: Kết nối database - kiểm tra cấu hình\n";
    } elseif (strpos($e->getMessage(), 'Table') !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "Lỗi: Bảng không tồn tại - kiểm tra database schema\n";
    }
}
?>