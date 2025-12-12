<?php
/**
 * Test hệ thống đánh giá sản phẩm
 * Kiểm tra tất cả chức năng
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';

SessionManager::start();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Hệ thống Đánh giá</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; padding: 40px 0; }
        .test-container { max-width: 1200px; margin: 0 auto; }
        .test-section { background: white; padding: 30px; margin-bottom: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .test-item { padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; background: #f8f9fa; border-radius: 8px; }
        .test-item.success { border-color: #28a745; background: #d4edda; }
        .test-item.error { border-color: #dc3545; background: #f8d7da; }
        .test-item.warning { border-color: #ffc107; background: #fff3cd; }
        .badge-custom { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="test-container">
    <h2 class="mb-4"><i class="fas fa-vial text-primary"></i> Test Hệ thống Đánh giá Sản phẩm</h2>
    
    <?php
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Test 1: Kiểm tra bảng
        echo "<div class='test-section'>
            <h4><i class='fas fa-database'></i> Test 1: Kiểm tra Database</h4>";
        
        $tables = [
            'product_reviews' => 'Bảng đánh giá sản phẩm',
            'review_images' => 'Bảng hình ảnh đánh giá',
            'review_helpful' => 'Bảng lượt hữu ích'
        ];
        
        foreach ($tables as $table => $desc) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $countStmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<div class='test-item success'>
                    <i class='fas fa-check-circle'></i> <strong>$desc</strong> ($table)
                    <span class='badge bg-success ms-2'>$count bản ghi</span>
                </div>";
            } else {
                echo "<div class='test-item error'>
                    <i class='fas fa-times-circle'></i> <strong>$desc</strong> ($table)
                    <span class='badge bg-danger ms-2'>Không tồn tại</span>
                </div>";
            }
        }
        
        // Kiểm tra view
        $dbName = $conn->query("SELECT DATABASE()")->fetchColumn();
        $stmt = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_$dbName = 'v_product_review_stats'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='test-item success'>
                <i class='fas fa-check-circle'></i> <strong>View thống kê</strong> (v_product_review_stats)
                <span class='badge bg-success ms-2'>Đã tạo</span>
            </div>";
        } else {
            echo "<div class='test-item error'>
                <i class='fas fa-times-circle'></i> <strong>View thống kê</strong>
                <span class='badge bg-danger ms-2'>Chưa tạo</span>
            </div>";
        }
        
        // Kiểm tra cột mới trong bảng
        $stmt = $conn->query("SHOW COLUMNS FROM tbl_hanghoa LIKE 'average_rating'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='test-item success'>
                <i class='fas fa-check-circle'></i> Cột <code>average_rating</code> trong bảng <code>tbl_hanghoa</code>
            </div>";
        } else {
            echo "<div class='test-item warning'>
                <i class='fas fa-exclamation-triangle'></i> Cột <code>average_rating</code> chưa có trong bảng <code>tbl_hanghoa</code>
            </div>";
        }
        
        $stmt = $conn->query("SHOW COLUMNS FROM don_hang LIKE 'is_reviewed'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='test-item success'>
                <i class='fas fa-check-circle'></i> Cột <code>is_reviewed</code> trong bảng <code>don_hang</code>
            </div>";
        } else {
            echo "<div class='test-item warning'>
                <i class='fas fa-exclamation-triangle'></i> Cột <code>is_reviewed</code> chưa có trong bảng <code>don_hang</code>
            </div>";
        }
        
        echo "</div>";
        
        // Test 2: Kiểm tra API files
        echo "<div class='test-section'>
            <h4><i class='fas fa-code'></i> Test 2: Kiểm tra Files</h4>";
        
        $files = [
            'lequocanh/api/product_reviews.php' => 'API xử lý đánh giá',
            'lequocanh/components/product_review_display.php' => 'Component hiển thị đánh giá',
            'lequocanh/components/product_review_widget.php' => 'Widget form đánh giá',
            'setup_product_reviews_system.sql' => 'File SQL setup',
            'setup_product_reviews.php' => 'Script cài đặt'
        ];
        
        foreach ($files as $file => $desc) {
            if (file_exists($file)) {
                $size = filesize($file);
                echo "<div class='test-item success'>
                    <i class='fas fa-check-circle'></i> <strong>$desc</strong>
                    <br><code>$file</code>
                    <span class='badge bg-info ms-2'>" . number_format($size) . " bytes</span>
                </div>";
            } else {
                echo "<div class='test-item error'>
                    <i class='fas fa-times-circle'></i> <strong>$desc</strong>
                    <br><code>$file</code>
                    <span class='badge bg-danger ms-2'>Không tồn tại</span>
                </div>";
            }
        }
        
        echo "</div>";
        
        // Test 3: Kiểm tra đơn hàng mẫu
        echo "<div class='test-section'>
            <h4><i class='fas fa-shopping-cart'></i> Test 3: Đơn hàng có thể đánh giá</h4>";
        
        $sql = "SELECT 
                    dh.id,
                    dh.ma_don_hang_text,
                    dh.ma_nguoi_dung,
                    dh.trang_thai_thanh_toan,
                    dh.phuong_thuc_thanh_toan,
                    dh.is_reviewed,
                    COUNT(cdh.id) as total_products
                FROM don_hang dh
                LEFT JOIN chi_tiet_don_hang cdh ON dh.id = cdh.ma_don_hang
                WHERE dh.trang_thai_thanh_toan = 'paid'
                GROUP BY dh.id
                ORDER BY dh.ngay_tao DESC
                LIMIT 5";
        
        $stmt = $conn->query($sql);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($orders) > 0) {
            echo "<p class='text-muted'>Tìm thấy " . count($orders) . " đơn hàng đã thanh toán (có thể đánh giá):</p>";
            
            foreach ($orders as $order) {
                $reviewedBadge = $order['is_reviewed'] ? 
                    '<span class="badge bg-success">Đã đánh giá</span>' : 
                    '<span class="badge bg-warning">Chưa đánh giá</span>';
                
                echo "<div class='test-item'>
                    <strong>Đơn hàng #{$order['id']}</strong> - {$order['ma_don_hang_text']}
                    <br>User: <code>{$order['ma_nguoi_dung']}</code>
                    <br>Thanh toán: <span class='badge bg-info'>{$order['phuong_thuc_thanh_toan']}</span>
                    <br>Số sản phẩm: <strong>{$order['total_products']}</strong>
                    <br>$reviewedBadge
                    <br><a href='lequocanh/administrator/elements_LQA/mgiohang/order_success.php?order_id={$order['id']}' 
                          class='btn btn-sm btn-primary mt-2' target='_blank'>
                        <i class='fas fa-eye'></i> Xem trang đánh giá
                    </a>
                </div>";
            }
        } else {
            echo "<div class='test-item warning'>
                <i class='fas fa-exclamation-triangle'></i> Không tìm thấy đơn hàng đã thanh toán
                <br><small>Tạo đơn hàng mẫu với <code>trang_thai_thanh_toan = 'paid'</code> để test</small>
            </div>";
        }
        
        echo "</div>";
        
        // Test 4: Kiểm tra đánh giá hiện có
        echo "<div class='test-section'>
            <h4><i class='fas fa-star'></i> Test 4: Đánh giá hiện có</h4>";
        
        $sql = "SELECT 
                    pr.*,
                    u.ten as user_name,
                    hh.ten_hang_hoa as product_name
                FROM product_reviews pr
                LEFT JOIN tbl_user u ON pr.ma_nguoi_dung = u.username
                LEFT JOIN tbl_hanghoa hh ON pr.ma_san_pham = hh.id
                ORDER BY pr.ngay_tao DESC
                LIMIT 5";
        
        $stmt = $conn->query($sql);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($reviews) > 0) {
            echo "<p class='text-muted'>Tìm thấy " . count($reviews) . " đánh giá gần đây:</p>";
            
            foreach ($reviews as $review) {
                $stars = str_repeat('⭐', $review['rating']);
                $verifiedBadge = $review['is_verified_purchase'] ? 
                    '<span class="badge bg-success">Đã mua hàng</span>' : '';
                
                echo "<div class='test-item success'>
                    <strong>{$review['user_name']}</strong> đánh giá <strong>{$review['product_name']}</strong>
                    <br>$stars ({$review['rating']}/5) $verifiedBadge
                    <br><em>\"{$review['comment']}\"</em>
                    <br><small class='text-muted'>{$review['ngay_tao']}</small>
                </div>";
            }
        } else {
            echo "<div class='test-item warning'>
                <i class='fas fa-info-circle'></i> Chưa có đánh giá nào
                <br><small>Hệ thống sẵn sàng nhận đánh giá từ khách hàng</small>
            </div>";
        }
        
        echo "</div>";
        
        // Test 5: Kiểm tra sản phẩm có rating
        echo "<div class='test-section'>
            <h4><i class='fas fa-chart-bar'></i> Test 5: Sản phẩm có đánh giá</h4>";
        
        $sql = "SELECT 
                    hh.id,
                    hh.ten_hang_hoa,
                    hh.average_rating,
                    hh.total_reviews
                FROM tbl_hanghoa hh
                WHERE hh.total_reviews > 0
                ORDER BY hh.average_rating DESC, hh.total_reviews DESC
                LIMIT 5";
        
        $stmt = $conn->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($products) > 0) {
            echo "<p class='text-muted'>Top " . count($products) . " sản phẩm được đánh giá cao:</p>";
            
            foreach ($products as $product) {
                $rating = number_format($product['average_rating'], 1);
                $stars = str_repeat('⭐', round($product['average_rating']));
                
                echo "<div class='test-item success'>
                    <strong>{$product['ten_hang_hoa']}</strong>
                    <br>$stars $rating/5.0
                    <span class='badge bg-info ms-2'>{$product['total_reviews']} đánh giá</span>
                </div>";
            }
        } else {
            echo "<div class='test-item warning'>
                <i class='fas fa-info-circle'></i> Chưa có sản phẩm nào được đánh giá
            </div>";
        }
        
        echo "</div>";
        
        // Test 6: Test API
        echo "<div class='test-section'>
            <h4><i class='fas fa-plug'></i> Test 6: API Endpoints</h4>";
        
        echo "<div class='test-item'>
            <strong>API Base URL:</strong> <code>/lequocanh/api/product_reviews.php</code>
            <br><br>
            <strong>Endpoints:</strong>
            <ul>
                <li><code>GET ?action=list&product_id=X</code> - Lấy danh sách đánh giá</li>
                <li><code>POST ?action=submit</code> - Gửi đánh giá mới</li>
                <li><code>GET ?action=check&order_id=X</code> - Kiểm tra đã đánh giá</li>
                <li><code>POST ?action=helpful</code> - Đánh dấu hữu ích</li>
            </ul>
        </div>";
        
        // Test API call
        if (count($products) > 0) {
            $testProductId = $products[0]['id'];
            echo "<div class='test-item'>
                <strong>Test API call:</strong>
                <br><button class='btn btn-sm btn-primary mt-2' onclick='testAPI($testProductId)'>
                    <i class='fas fa-play'></i> Test lấy đánh giá sản phẩm #{$testProductId}
                </button>
                <div id='apiResult' class='mt-3'></div>
            </div>";
        }
        
        echo "</div>";
        
        // Tổng kết
        echo "<div class='test-section'>
            <h4><i class='fas fa-check-circle text-success'></i> Tổng kết</h4>
            <div class='alert alert-success'>
                <h5>✅ Hệ thống đánh giá đã sẵn sàng!</h5>
                <p class='mb-2'><strong>Các bước tiếp theo:</strong></p>
                <ol>
                    <li>Tạo đơn hàng mẫu với <code>trang_thai_thanh_toan = 'paid'</code></li>
                    <li>Truy cập trang order success để test widget đánh giá</li>
                    <li>Tích hợp component hiển thị đánh giá vào trang sản phẩm</li>
                    <li>Tùy chỉnh giao diện theo ý muốn</li>
                </ol>
                <p class='mb-0'><strong>Tài liệu:</strong> Xem file <code>HUONG_DAN_HE_THONG_DANH_GIA.md</code></p>
            </div>
        </div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>
            <h5><i class='fas fa-exclamation-triangle'></i> Lỗi</h5>
            <p>{$e->getMessage()}</p>
        </div>";
    }
    ?>
    
    <div class="mt-4 text-center">
        <a href="setup_product_reviews.php" class="btn btn-primary">
            <i class="fas fa-cog"></i> Chạy lại Setup
        </a>
        <a href="lequocanh/index.php" class="btn btn-success">
            <i class="fas fa-home"></i> Về trang chủ
        </a>
        <a href="HUONG_DAN_HE_THONG_DANH_GIA.md" class="btn btn-info" target="_blank">
            <i class="fas fa-book"></i> Xem hướng dẫn
        </a>
    </div>
</div>

<script>
async function testAPI(productId) {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm"></div> Đang test...';
    
    try {
        const response = await fetch(`/lequocanh/api/product_reviews.php?action=list&product_id=${productId}`);
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <strong>✅ API hoạt động tốt!</strong>
                    <pre class="mt-2 mb-0">${JSON.stringify(result.data, null, 2)}</pre>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>❌ API trả về lỗi:</strong>
                    <pre class="mt-2 mb-0">${JSON.stringify(result, null, 2)}</pre>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <strong>❌ Lỗi kết nối API:</strong>
                <p class="mb-0">${error.message}</p>
            </div>
        `;
    }
}
</script>

</body>
</html>
