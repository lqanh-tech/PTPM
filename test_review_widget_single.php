<?php
/**
 * Test widget đánh giá cho một đơn hàng cụ thể
 */

require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

SessionManager::start();

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    die('Thiếu order_id');
}

// Giả lập đăng nhập nếu chưa đăng nhập
if (!isset($_SESSION['USER'])) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Lấy user của đơn hàng này
    $stmt = $conn->prepare("SELECT ma_nguoi_dung FROM don_hang WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        $_SESSION['USER'] = $order['ma_nguoi_dung'];
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Review Widget - Order #<?php echo $orderId; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; padding: 40px 0; }
        .container { max-width: 900px; }
        .test-header { background: white; padding: 20px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="container">
    <div class="test-header">
        <h2><i class="fas fa-star text-warning"></i> Test Widget Đánh Giá</h2>
        <p class="mb-0">
            <strong>Đơn hàng:</strong> #<?php echo $orderId; ?><br>
            <strong>User:</strong> <?php echo $_SESSION['USER'] ?? 'Chưa đăng nhập'; ?>
        </p>
        <a href="test_review_widget_products.php" class="btn btn-secondary btn-sm mt-2">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
    
    <!-- Widget đánh giá -->
    <?php include 'lequocanh/components/product_review_widget.php'; ?>
    
    <div class="mt-4 p-3 bg-white rounded">
        <h5><i class="fas fa-info-circle"></i> Thông tin debug</h5>
        <p><strong>API Endpoint:</strong> <code>lequocanh/api/product_reviews.php?action=check&order_id=<?php echo $orderId; ?></code></p>
        <button class="btn btn-primary btn-sm" onclick="testAPI()">
            <i class="fas fa-play"></i> Test API
        </button>
        <pre id="apiResult" class="mt-3" style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px; display: none;"></pre>
    </div>
</div>

<script>
async function testAPI() {
    const resultEl = document.getElementById('apiResult');
    resultEl.style.display = 'block';
    resultEl.textContent = 'Loading...';
    
    try {
        const response = await fetch(`lequocanh/api/product_reviews.php?action=check&order_id=<?php echo $orderId; ?>`);
        const data = await response.json();
        resultEl.textContent = JSON.stringify(data, null, 2);
    } catch (error) {
        resultEl.textContent = 'Error: ' + error.message;
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
