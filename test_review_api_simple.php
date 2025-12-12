<?php
/**
 * Test API đánh giá - Kiểm tra xem có lấy được sản phẩm không
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "=== TEST API ĐÁNH GIÁ ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Lấy đơn hàng đã duyệt
    echo "1. Tìm đơn hàng đã duyệt...\n";
    $sql = "SELECT * FROM don_hang 
            WHERE (trang_thai = 'approved' OR trang_thai_thanh_toan = 'paid')
            ORDER BY ngay_tao DESC 
            LIMIT 1";
    $stmt = $conn->query($sql);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "   ✗ Không tìm thấy đơn hàng đã duyệt\n";
        exit(1);
    }
    
    echo "   ✓ Tìm thấy đơn hàng #{$order['id']} - {$order['ma_don_hang_text']}\n";
    echo "     User: {$order['ma_nguoi_dung']}\n";
    echo "     Trạng thái: {$order['trang_thai']}\n";
    echo "     Thanh toán: {$order['trang_thai_thanh_toan']}\n\n";
    
    // 2. Lấy sản phẩm trong đơn hàng (query CŨ - SAI)
    echo "2. Test query CŨ (có thể sai)...\n";
    try {
        $oldSql = "SELECT DISTINCT cdh.ma_san_pham, hh.ten_hang_hoa
                   FROM chi_tiet_don_hang cdh
                   JOIN tbl_hanghoa hh ON cdh.ma_san_pham = hh.id
                   WHERE cdh.ma_don_hang = ?";
        $stmt = $conn->prepare($oldSql);
        $stmt->execute([$order['id']]);
        $oldProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   ✓ Query CŨ OK - Tìm thấy " . count($oldProducts) . " sản phẩm\n";
        if (!empty($oldProducts)) {
            foreach ($oldProducts as $p) {
                echo "     - {$p['ten_hang_hoa']} (ID: {$p['ma_san_pham']})\n";
            }
        }
    } catch (Exception $e) {
        echo "   ✗ Query CŨ LỖI: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 3. Lấy sản phẩm trong đơn hàng (query MỚI - ĐÚNG)
    echo "3. Test query MỚI (đã sửa)...\n";
    try {
        $newSql = "SELECT DISTINCT cdh.ma_san_pham, h.tenhanghoa as product_name
                   FROM chi_tiet_don_hang cdh
                   JOIN hanghoa h ON cdh.ma_san_pham = h.idhanghoa
                   WHERE cdh.ma_don_hang = ?";
        $stmt = $conn->prepare($newSql);
        $stmt->execute([$order['id']]);
        $newProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($newProducts)) {
            echo "   ✗ Query MỚI không tìm thấy sản phẩm\n";
        } else {
            echo "   ✓ Query MỚI OK - Tìm thấy " . count($newProducts) . " sản phẩm\n";
            foreach ($newProducts as $p) {
                echo "     - {$p['product_name']} (ID: {$p['ma_san_pham']})\n";
            }
        }
    } catch (Exception $e) {
        echo "   ✗ Query MỚI LỖI: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 4. Kiểm tra đã đánh giá chưa
    if (!empty($newProducts)) {
        echo "4. Kiểm tra trạng thái đánh giá...\n";
        foreach ($newProducts as $product) {
            $checkSql = "SELECT id FROM product_reviews 
                        WHERE ma_don_hang = ? AND ma_san_pham = ? AND ma_nguoi_dung = ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->execute([$order['id'], $product['ma_san_pham'], $order['ma_nguoi_dung']]);
            $reviewed = $stmt->fetch() ? true : false;
            
            $status = $reviewed ? "✓ Đã đánh giá" : "○ Chưa đánh giá";
            echo "   $status - {$product['product_name']}\n";
        }
        echo "\n";
    }
    
    // 5. Tạo response giống API
    echo "5. API Response (giống thật)...\n";
    $reviewStatus = [];
    if (!empty($newProducts)) {
        foreach ($newProducts as $product) {
            $checkSql = "SELECT id FROM product_reviews 
                        WHERE ma_don_hang = ? AND ma_san_pham = ? AND ma_nguoi_dung = ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->execute([$order['id'], $product['ma_san_pham'], $order['ma_nguoi_dung']]);
            
            $reviewStatus[] = [
                'product_id' => $product['ma_san_pham'],
                'product_name' => $product['product_name'],
                'reviewed' => $stmt->fetch() ? true : false
            ];
        }
    }
    
    $apiResponse = [
        'success' => true,
        'data' => [
            'can_review' => true,
            'products' => $reviewStatus
        ]
    ];
    
    echo json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 6. Kết luận
    echo "=== KẾT LUẬN ===\n";
    if (empty($reviewStatus)) {
        echo "✗ THẤT BẠI: Không tìm thấy sản phẩm nào\n";
        echo "  Nguyên nhân có thể:\n";
        echo "  - Bảng chi_tiet_don_hang trống\n";
        echo "  - JOIN không đúng\n";
        echo "  - Tên bảng/cột sai\n";
    } else {
        echo "✓ THÀNH CÔNG: Tìm thấy " . count($reviewStatus) . " sản phẩm\n";
        echo "  Widget sẽ hiển thị đúng danh sách sản phẩm\n";
        echo "  Khách hàng có thể đánh giá từng sản phẩm\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ LỖI NGHIÊM TRỌNG:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
