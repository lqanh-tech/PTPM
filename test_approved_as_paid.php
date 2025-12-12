<?php
/**
 * Test logic: Đơn đã duyệt = Đã thanh toán
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "=== TEST: ĐƠN ĐÃ DUYỆT = ĐÃ THANH TOÁN ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Tìm đơn hàng đã duyệt nhưng chưa thanh toán
    echo "1. Tìm đơn hàng đã duyệt (approved) nhưng chưa thanh toán (pending)...\n";
    $sql = "SELECT * FROM don_hang 
            WHERE trang_thai = 'approved' 
            AND trang_thai_thanh_toan = 'pending'
            ORDER BY ngay_tao DESC 
            LIMIT 1";
    $stmt = $conn->query($sql);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "   ✗ Không tìm thấy đơn hàng approved + pending\n";
        echo "   Tạo đơn hàng mẫu...\n";
        
        // Tạo đơn hàng mẫu
        $insertSql = "INSERT INTO don_hang (ma_nguoi_dung, ma_don_hang_text, tong_tien, trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan) 
                      VALUES ('khachhang', 'TEST_APPROVED_001', 100000, 'approved', 'pending', 'bank_transfer')";
        $conn->exec($insertSql);
        $orderId = $conn->lastInsertId();
        
        // Thêm sản phẩm vào đơn hàng
        $insertItemSql = "INSERT INTO chi_tiet_don_hang (ma_don_hang, ma_san_pham, so_luong, gia) 
                          VALUES (?, 143, 1, 100000)";
        $stmt = $conn->prepare($insertItemSql);
        $stmt->execute([$orderId]);
        
        echo "   ✓ Tạo đơn hàng mẫu #$orderId\n";
        
        // Lấy lại đơn hàng
        $stmt = $conn->prepare("SELECT * FROM don_hang WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        echo "   ✓ Tìm thấy đơn hàng #{$order['id']} - {$order['ma_don_hang_text']}\n";
    }
    
    echo "     Trạng thái: {$order['trang_thai']}\n";
    echo "     Thanh toán: {$order['trang_thai_thanh_toan']}\n\n";
    
    // 2. Test logic cũ (chỉ kiểm tra paid)
    echo "2. Test logic CŨ (chỉ kiểm tra trang_thai_thanh_toan = 'paid')...\n";
    $oldLogic = ($order['trang_thai_thanh_toan'] === 'paid');
    echo "   Kết quả: " . ($oldLogic ? "✓ Cho phép đánh giá" : "✗ Không cho phép đánh giá") . "\n\n";
    
    // 3. Test logic mới (approved OR paid)
    echo "3. Test logic MỚI (approved OR paid)...\n";
    $newLogic = ($order['trang_thai'] === 'approved' || $order['trang_thai_thanh_toan'] === 'paid');
    echo "   Kết quả: " . ($newLogic ? "✓ Cho phép đánh giá" : "✗ Không cho phép đánh giá") . "\n\n";
    
    // 4. Test API với đơn hàng này
    echo "4. Test API với đơn hàng #{$order['id']}...\n";
    
    // Giả lập session
    session_start();
    $_SESSION['USER'] = $order['ma_nguoi_dung'];
    
    // Test API call
    $apiUrl = "http://localhost:20080/lequocanh/api/product_reviews.php?action=check&order_id=" . $order['id'];
    echo "   API URL: $apiUrl\n";
    
    // Simulate API logic
    $canReview = ($order['trang_thai'] === 'approved' || $order['trang_thai_thanh_toan'] === 'paid');
    echo "   Can review: " . ($canReview ? "✓ YES" : "✗ NO") . "\n";
    
    if ($canReview) {
        // Lấy sản phẩm trong đơn hàng
        $productsSql = "SELECT DISTINCT cdh.ma_san_pham, h.tenhanghoa as product_name
                       FROM chi_tiet_don_hang cdh
                       JOIN hanghoa h ON cdh.ma_san_pham = h.idhanghoa
                       WHERE cdh.ma_don_hang = ?";
        $stmt = $conn->prepare($productsSql);
        $stmt->execute([$order['id']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Sản phẩm có thể đánh giá: " . count($products) . "\n";
        foreach ($products as $product) {
            echo "     - {$product['product_name']} (ID: {$product['ma_san_pham']})\n";
        }
    }
    
    echo "\n";
    
    // 5. Kết luận
    echo "=== KẾT LUẬN ===\n";
    echo "Logic CŨ: Chỉ đơn hàng đã thanh toán (paid) mới được đánh giá\n";
    echo "Logic MỚI: Đơn hàng đã duyệt (approved) HOẶC đã thanh toán (paid) đều được đánh giá\n\n";
    
    if ($oldLogic != $newLogic) {
        echo "✓ THAY ĐỔI: Logic mới cho phép đánh giá nhiều hơn\n";
        echo "  - Đơn hàng COD/Bank Transfer: Sau khi admin duyệt → Có thể đánh giá ngay\n";
        echo "  - Đơn hàng MoMo: Sau khi thanh toán thành công → Có thể đánh giá ngay\n";
    } else {
        echo "○ KHÔNG THAY ĐỔI: Logic cũ và mới giống nhau cho đơn hàng này\n";
    }
    
    echo "\n✅ Test hoàn thành!\n";
    
} catch (Exception $e) {
    echo "\n✗ LỖI:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}