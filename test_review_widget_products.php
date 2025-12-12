<?php
/**
 * Test widget đánh giá - Kiểm tra hiển thị sản phẩm
 */

require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

SessionManager::start();

// Giả lập đăng nhập
if (!isset($_SESSION['USER'])) {
    // Lấy user đầu tiên
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT username FROM user LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['USER'] = $user['username'];
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Review Widget - Products Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; padding: 40px 0; }
        .test-container { max-width: 1200px; margin: 0 auto; }
        .test-card { background: white; padding: 30px; margin-bottom: 20px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; text-align: center; }
        .order-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .order-item:hover { background: #f8f9fa; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px; overflow-x: auto; }
        .badge-custom { padding: 8px 16px; border-radius: 20px; }
    </style>
</head>
<body>
<div class="test-container">
    <div class="test-header">
        <h1><i class="fas fa-vial"></i> TEST REVIEW WIDGET</h1>
        <p class="mb-0">Kiểm tra hiển thị sản phẩm trong widget đánh giá</p>
    </div>
    
    <?php
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Lấy đơn hàng đã duyệt
        $sql = "SELECT * FROM don_hang 
                WHERE (trang_thai = 'approved' OR trang_thai_thanh_toan = 'paid')
                ORDER BY ngay_tao DESC 
                LIMIT 5";
        $stmt = $conn->query($sql);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($orders)) {
            echo "<div class='test-card'>
                <div class='alert alert-warning'>
                    <i class='fas fa-exclamation-triangle'></i> 
                    Không tìm thấy đơn hàng đã duyệt để test
                </div>
            </div>";
        } else {
            echo "<div class='test-card'>
                <h3><i class='fas fa-shopping-cart'></i> Đơn hàng có thể test</h3>
                <p class='text-muted'>Click vào đơn hàng để xem widget đánh giá</p>";
            
            foreach ($orders as $order) {
                // Lấy sản phẩm trong đơn hàng
                $productsSql = "SELECT cdh.*, h.tenhanghoa 
                               FROM chi_tiet_don_hang cdh
                               JOIN hanghoa h ON cdh.ma_san_pham = h.idhanghoa
                               WHERE cdh.ma_don_hang = ?";
                $stmt = $conn->prepare($productsSql);
                $stmt->execute([$order['id']]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<div class='order-item'>
                    <div class='d-flex justify-content-between align-items-start'>
                        <div>
                            <h5>Đơn hàng #{$order['id']} - {$order['ma_don_hang_text']}</h5>
                            <p class='mb-1'>
                                <strong>Khách hàng:</strong> {$order['ma_nguoi_dung']}<br>
                                <strong>Trạng thái:</strong> 
                                <span class='badge bg-success'>{$order['trang_thai']}</span>
                                <span class='badge bg-info'>{$order['trang_thai_thanh_toan']}</span><br>
                                <strong>Ngày tạo:</strong> {$order['ngay_tao']}<br>
                                <strong>Số sản phẩm:</strong> " . count($products) . "
                            </p>
                            <div class='mt-2'>
                                <strong>Sản phẩm:</strong>
                                <ul class='mb-0'>";
                
                foreach ($products as $product) {
                    echo "<li>{$product['tenhanghoa']} (ID: {$product['ma_san_pham']}) - SL: {$product['so_luong']}</li>";
                }
                
                echo "      </ul>
                            </div>
                        </div>
                        <div>
                            <a href='test_review_widget_single.php?order_id={$order['id']}' 
                               class='btn btn-primary' target='_blank'>
                                <i class='fas fa-eye'></i> Xem Widget
                            </a>
                        </div>
                    </div>
                </div>";
            }
            
            echo "</div>";
        }
        
        // Test API trực tiếp
        echo "<div class='test-card'>
            <h3><i class='fas fa-code'></i> Test API Response</h3>
            <p class='text-muted'>Kiểm tra response từ API check</p>";
        
        if (!empty($orders)) {
            $testOrder = $orders[0];
            $orderId = $testOrder['id'];
            
            echo "<p><strong>Test với đơn hàng #{$orderId}</strong></p>";
            
            // Simulate API call
            $productsSql = "SELECT DISTINCT cdh.ma_san_pham, h.tenhanghoa as product_name
                           FROM chi_tiet_don_hang cdh
                           JOIN hanghoa h ON cdh.ma_san_pham = h.idhanghoa
                           WHERE cdh.ma_don_hang = ?";
            $stmt = $conn->prepare($productsSql);
            $stmt->execute([$orderId]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $reviewStatus = [];
            foreach ($products as $product) {
                $checkSql = "SELECT id FROM product_reviews 
                            WHERE ma_don_hang = ? AND ma_san_pham = ? AND ma_nguoi_dung = ?";
                $stmt = $conn->prepare($checkSql);
                $stmt->execute([$orderId, $product['ma_san_pham'], $_SESSION['USER']]);
                
                $reviewStatus[] = [
                    'product_id' => $product['ma_san_pham'],
                    'product_name' => $product['product_name'],
                    'reviewed' => $stmt->fetch() ? true : false
                ];
            }
            
            $apiResponse = [
                'success' => true,
                'data' => [
                    'can_review' => true,
                    'products' => $reviewStatus
                ]
            ];
            
            echo "<pre>" . json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            
            if (empty($reviewStatus)) {
                echo "<div class='alert alert-danger'>
                    <i class='fas fa-exclamation-circle'></i> 
                    <strong>LỖI:</strong> Không tìm thấy sản phẩm nào trong đơn hàng!
                    <br>Có thể do:
                    <ul class='mb-0 mt-2'>
                        <li>Bảng chi_tiet_don_hang trống</li>
                        <li>JOIN với bảng hanghoa không đúng</li>
                        <li>Tên cột không khớp</li>
                    </ul>
                </div>";
            } else {
                echo "<div class='alert alert-success'>
                    <i class='fas fa-check-circle'></i> 
                    <strong>OK:</strong> Tìm thấy " . count($reviewStatus) . " sản phẩm
                </div>";
            }
        }
        
        echo "</div>";
        
        // Hướng dẫn test
        echo "<div class='test-card'>
            <h3><i class='fas fa-clipboard-check'></i> Hướng dẫn test</h3>
            <ol>
                <li>Click nút 'Xem Widget' ở đơn hàng bất kỳ</li>
                <li>Kiểm tra xem widget có hiển thị danh sách sản phẩm không</li>
                <li>Mỗi sản phẩm phải có:
                    <ul>
                        <li>Tên sản phẩm</li>
                        <li>Chọn số sao (1-5)</li>
                        <li>Ô nhập nhận xét</li>
                        <li>Nút 'Gửi đánh giá'</li>
                    </ul>
                </li>
                <li>Thử đánh giá một sản phẩm</li>
                <li>Sau khi đánh giá, sản phẩm đó phải hiển thị 'Đã đánh giá'</li>
                <li>Vào trang sản phẩm để xem đánh giá có hiển thị không</li>
            </ol>
        </div>";
        
    } catch (Exception $e) {
        echo "<div class='test-card'>
            <div class='alert alert-danger'>
                <h5><i class='fas fa-exclamation-triangle'></i> Lỗi</h5>
                <p>{$e->getMessage()}</p>
                <pre>" . $e->getTraceAsString() . "</pre>
            </div>
        </div>";
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
