<?php
/**
 * Test hiển thị shipping methods sau khi fix
 */
session_start();

// Giả lập session
$_SESSION['cart_weight'] = 2.5;
$_SESSION['cart_total'] = 500000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Shipping Methods Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="fas fa-vial"></i> Test Shipping Methods Display</h1>
        
        <div class="alert-info">
            <strong>Thông tin test:</strong><br>
            - Trọng lượng giỏ hàng: <?php echo $_SESSION['cart_weight']; ?> kg<br>
            - Giá trị đơn hàng: <?php echo number_format($_SESSION['cart_total'], 0, ',', '.'); ?> ₫<br>
            - Tỉnh/Thành: ID <?php echo $_SESSION['province_id']; ?><br>
            - Quận/Huyện: ID <?php echo $_SESSION['district_id']; ?>
        </div>

        <hr>

        <?php 
        // Include shipping selector đã fix
        include 'lequocanh/administrator/elements_LQA/mgiohang/shipping_method_selector_v2.php'; 
        ?>

        <hr>

        <div class="mt-4">
            <h3>✅ Kết quả:</h3>
            <p>Nếu bạn thấy <strong>4 phương thức vận chuyển</strong> và <strong>KHÔNG có phương thức nào bị trùng</strong>, nghĩa là đã fix thành công!</p>
            <ul>
                <li>Lấy tại cửa hàng</li>
                <li>Giao hàng nhanh</li>
                <li>Giao hàng tiêu chuẩn</li>
                <li>Giao Hàng Nhanh (GHN)</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
