<?php
/**
 * Test widget đánh giá sau khi sửa
 */

require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

SessionManager::start();

// Giả lập đăng nhập
if (!isset($_SESSION['USER'])) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT username FROM user LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['USER'] = $user['username'];
    }
}

// Lấy đơn hàng đã duyệt
$db = Database::getInstance();
$conn = $db->getConnection();
$sql = "SELECT * FROM don_hang 
        WHERE (trang_thai = 'approved' OR trang_thai_thanh_toan = 'paid')
        ORDER BY ngay_tao DESC 
        LIMIT 1";
$stmt = $conn->query($sql);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Không tìm thấy đơn hàng đã duyệt để test');
}

$orderId = $order['id'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Widget Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; padding: 40px 0; }
        .container { max-width: 900px; }
        .test-header { 
            background: white; 
            padding: 20px; 
            margin-bottom: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .debug-info {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="test-header">
        <h2><i class="fas fa-bug text-warning"></i> Test Widget Fix</h2>
        <p class="mb-0">
            <strong>Đơn hàng:</strong> #<?php echo $orderId; ?> - <?php echo $order['ma_don_hang_text']; ?><br>
            <strong>User:</strong> <?php echo $_SESSION['USER']; ?><br>
            <strong>Trạng thái:</strong> <?php echo $order['trang_thai']; ?><br>
            <strong>Thanh toán:</strong> <?php echo $order['trang_thai_thanh_toan']; ?>
        </p>
    </div>
    
    <div class="alert alert-info">
        <h5><i class="fas fa-info-circle"></i> Các thay đổi đã thực hiện:</h5>
        <ul class="mb-0">
            <li>✅ Xóa nút "Xem tất cả" trong giohangView.php</li>
            <li>✅ Sửa API path trong widget: <code>../api/</code> → <code>/lequocanh/api/</code></li>
            <li>✅ Recreate bảng product_reviews với cấu trúc đúng</li>
        </ul>
    </div>
    
    <!-- Widget đánh giá -->
    <div class="card">
        <div class="card-body">
            <?php include 'lequocanh/components/product_review_widget.php'; ?>
        </div>
    </div>
    
    <div class="debug-info">
        <h5>Debug Info:</h5>
        <p><strong>API URL:</strong> /lequocanh/api/product_reviews.php?action=check&order_id=<?php echo $orderId; ?></p>
        <button class="btn btn-primary btn-sm" onclick="testAPI()">
            <i class="fas fa-play"></i> Test API Direct
        </button>
        <pre id="apiResult" style="margin-top: 15px; display: none;"></pre>
    </div>
    
    <div class="mt-4">
        <a href="lequocanh/administrator/elements_LQA/mgiohang/giohangView.php" 
           class="btn btn-success" target="_blank">
            <i class="fas fa-shopping-cart"></i> Xem trang giỏ hàng (đã xóa nút "Xem tất cả")
        </a>
        <a href="lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php?id=<?php echo $orderId; ?>" 
           class="btn btn-primary" target="_blank">
            <i class="fas fa-eye"></i> Xem chi tiết đơn hàng
        </a>
    </div>
</div>

<script>
async function testAPI() {
    const resultEl = document.getElementById('apiResult');
    resultEl.style.display = 'block';
    resultEl.textContent = 'Loading...';
    
    try {
        const response = await fetch('/lequocanh/api/product_reviews.php?action=check&order_id=<?php echo $orderId; ?>');
        const data = await response.json();
        resultEl.textContent = JSON.stringify(data, null, 2);
        
        if (data.success && data.data.products && data.data.products.length > 0) {
            resultEl.style.background = '#d4edda';
            resultEl.style.color = '#155724';
        } else {
            resultEl.style.background = '#f8d7da';
            resultEl.style.color = '#721c24';
        }
    } catch (error) {
        resultEl.textContent = 'Error: ' + error.message;
        resultEl.style.background = '#f8d7da';
        resultEl.style.color = '#721c24';
    }
}

// Auto test API on load
setTimeout(testAPI, 1000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>