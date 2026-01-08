<?php

try {
    $host = 'mysql';
    $port = '3306';
    $dbname = 'sales_management';
    $username = 'app_user';
    $password = 'app_password';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
} catch (PDOException $e) {
    die("❌ Lỗi kết nối database: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Check - MoMo Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 1400px;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        .status-approved {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        .amount {
            font-weight: bold;
            color: #d82d8b;
        }
        .order-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .order-link:hover {
            text-decoration: underline;
        }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #d82d8b;
        }
        .action-btn {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-database me-2"></i>Database Check - MoMo Orders</h1>
            <p class="text-muted">Kiểm tra các đơn hàng MoMo trong database</p>
        </div>

        <?php

        try {
            $sql = "SELECT 
                        id,
                        ma_don_hang_text,
                        ma_nguoi_dung,
                        tong_tien,
                        thue,
                        phi_van_chuyen,
                        trang_thai,
                        trang_thai_thanh_toan,
                        phuong_thuc_thanh_toan,
                        dia_chi_giao_hang,
                        ngay_tao,
                        ngay_cap_nhat
                    FROM don_hang 
                    WHERE ma_nguoi_dung = 'khachhang'
                    AND phuong_thuc_thanh_toan = 'momo'
                    ORDER BY ngay_tao DESC
                    LIMIT 20";
            
            $stmt = $pdo->query($sql);
            $orders = $stmt->fetchAll();
            
            echo "<div class='info-box'>";
            echo "<h5><i class='fas fa-info-circle me-2'></i>Thông Tin</h5>";
            echo "<p class='mb-2'><strong>Tổng số đơn hàng MoMo:</strong> " . count($orders) . "</p>";
            echo "<p class='mb-0'><strong>User:</strong> khachhang</p>";
            echo "</div>";
            
            if (count($orders) > 0) {
                echo "<div class='table-responsive'>";
                echo "<table class='table table-hover table-striped'>";
                echo "<thead class='table-dark'>";
                echo "<tr>";
                echo "<th>ID</th>";
                echo "<th>Order Code</th>";
                echo "<th>Số Tiền</th>";
                echo "<th>Thuế</th>";
                echo "<th>Phí VC</th>";
                echo "<th>Trạng Thái</th>";
                echo "<th>TT Thanh Toán</th>";
                echo "<th>Ngày Tạo</th>";
                echo "<th>Actions</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                
                foreach ($orders as $order) {
                    $statusClass = '';
                    switch ($order['trang_thai']) {
                        case 'pending':
                            $statusClass = 'status-pending';
                            break;
                        case 'approved':
                            $statusClass = 'status-approved';
                            break;
                        default:
                            $statusClass = 'status-pending';
                    }
                    
                    $paymentStatusClass = '';
                    switch ($order['trang_thai_thanh_toan']) {
                        case 'pending':
                            $paymentStatusClass = 'status-pending';
                            break;
                        case 'paid':
                            $paymentStatusClass = 'status-paid';
                            break;
                        case 'failed':
                            $paymentStatusClass = 'status-failed';
                            break;
                        default:
                            $paymentStatusClass = 'status-pending';
                    }
                    
                    $orderSuccessUrl = "administrator/elements_LQA/mgiohang/order_success.php?order_id=" . $order['id'];
                    
                    echo "<tr>";
                    echo "<td><strong>#{$order['id']}</strong></td>";
                    echo "<td><code>" . htmlspecialchars($order['ma_don_hang_text'] ?? 'N/A') . "</code></td>";
                    echo "<td class='amount'>" . number_format($order['tong_tien']) . " đ</td>";
                    echo "<td>" . number_format($order['thue'] ?? 0) . " đ</td>";
                    echo "<td>" . number_format($order['phi_van_chuyen'] ?? 0) . " đ</td>";
                    echo "<td><span class='status-badge $statusClass'>{$order['trang_thai']}</span></td>";
                    echo "<td><span class='status-badge $paymentStatusClass'>{$order['trang_thai_thanh_toan']}</span></td>";
                    echo "<td>" . date('d/m/Y H:i', strtotime($order['ngay_tao'])) . "</td>";
                    echo "<td>";
                    echo "<a href='$orderSuccessUrl' class='btn btn-sm btn-primary action-btn' target='_blank'>";
                    echo "<i class='fas fa-eye me-1'></i>Xem";
                    echo "</a>";
                    if ($order['trang_thai_thanh_toan'] == 'pending') {
                        echo "<button class='btn btn-sm btn-success action-btn' onclick='updateOrder({$order['id']}, \"paid\")'>";
                        echo "<i class='fas fa-check me-1'></i>Đánh dấu Paid";
                        echo "</button>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                
                echo "</tbody>";
                echo "</table>";
                echo "</div>";
                
                $pendingOrders = array_filter($orders, function($order) {
                    return $order['trang_thai_thanh_toan'] == 'pending';
                });
                
                if (count($pendingOrders) > 0) {
                    echo "<div class='alert alert-warning mt-4'>";
                    echo "<h5><i class='fas fa-exclamation-triangle me-2'></i>Đơn Hàng Pending</h5>";
                    echo "<p>Có <strong>" . count($pendingOrders) . "</strong> đơn hàng đang ở trạng thái pending.</p>";
                    echo "<p class='mb-0'>Nếu bạn đã thanh toán thành công, vui lòng click nút \"Đánh dấu Paid\" hoặc chạy script SQL.</p>";
                    echo "</div>";
                    
                    echo "<div class='info-box mt-3'>";
                    echo "<h5><i class='fas fa-code me-2'></i>SQL Commands để Update</h5>";
                    foreach ($pendingOrders as $order) {
                        echo "<div class='mt-2'>";
                        echo "<p class='mb-1'><strong>Đơn hàng #{$order['id']}</strong> - " . number_format($order['tong_tien']) . " đ</p>";
                        echo "<pre class='bg-light p-2 rounded'><code>";
                        echo "UPDATE don_hang \n";
                        echo "SET trang_thai_thanh_toan = 'paid',\n";
                        echo "    trang_thai = 'approved',\n";
                        echo "    ngay_cap_nhat = NOW()\n";
                        echo "WHERE id = {$order['id']};";
                        echo "</code></pre>";
                        echo "</div>";
                    }
                    echo "</div>";
                }
                
            } else {
                echo "<div class='alert alert-info'>";
                echo "<h5><i class='fas fa-info-circle me-2'></i>Không Tìm Thấy Đơn Hàng</h5>";
                echo "<p class='mb-0'>Không có đơn hàng MoMo nào trong database cho user <strong>khachhang</strong>.</p>";
                echo "</div>";
            }
            
            echo "<hr class='my-4'>";
            echo "<h4><i class='fas fa-shopping-cart me-2'></i>Giỏ Hàng</h4>";
            
            $cartSql = "SELECT COUNT(*) as count FROM tbl_giohang WHERE user_id = 'khachhang'";
            $cartStmt = $pdo->query($cartSql);
            $cartCount = $cartStmt->fetch()['count'];
            
            echo "<div class='alert alert-" . ($cartCount > 0 ? 'warning' : 'success') . "'>";
            echo "<p class='mb-0'><strong>Số sản phẩm trong giỏ:</strong> $cartCount</p>";
            if ($cartCount > 0) {
                echo "<p class='mb-0 mt-2'><em>Lưu ý: Nếu đã thanh toán thành công, giỏ hàng nên được xóa.</em></p>";
                echo "<button class='btn btn-sm btn-danger mt-2' onclick='clearCart()'>";
                echo "<i class='fas fa-trash me-1'></i>Xóa Giỏ Hàng";
                echo "</button>";
            }
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>";
            echo "<h5><i class='fas fa-exclamation-circle me-2'></i>Lỗi Database</h5>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>

        <div class="mt-4 text-center">
            <a href="test_momo_payment.php" class="btn btn-primary">
                <i class="fas fa-credit-card me-2"></i>Test MoMo Payment
            </a>
            <button class="btn btn-success" onclick="location.reload()">
                <i class="fas fa-sync me-2"></i>Refresh
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateOrder(orderId, status) {
            if (confirm('Bạn có chắc muốn cập nhật đơn hàng #' + orderId + ' thành ' + status + '?')) {

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const orderIdInput = document.createElement('input');
                orderIdInput.type = 'hidden';
                orderIdInput.name = 'update_order_id';
                orderIdInput.value = orderId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'update_status';
                statusInput.value = status;
                
                form.appendChild(orderIdInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function clearCart() {
            if (confirm('Bạn có chắc muốn xóa tất cả sản phẩm trong giỏ hàng?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const clearInput = document.createElement('input');
                clearInput.type = 'hidden';
                clearInput.name = 'clear_cart';
                clearInput.value = '1';
                
                form.appendChild(clearInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        if (isset($_POST['update_order_id']) && isset($_POST['update_status'])) {
            $orderId = intval($_POST['update_order_id']);
            $status = $_POST['update_status'];
            
            $updateSql = "UPDATE don_hang 
                         SET trang_thai_thanh_toan = ?,
                             trang_thai = 'approved',
                             ngay_cap_nhat = NOW()
                         WHERE id = ?";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([$status, $orderId]);
            
            $deleteSql = "DELETE FROM tbl_giohang WHERE user_id = 'khachhang'";
            $pdo->exec($deleteSql);
            
            echo "<script>alert('Đã cập nhật đơn hàng #$orderId!'); location.reload();</script>";
        }
        
        if (isset($_POST['clear_cart'])) {
            $deleteSql = "DELETE FROM tbl_giohang WHERE user_id = 'khachhang'";
            $pdo->exec($deleteSql);
            
            echo "<script>alert('Đã xóa giỏ hàng!'); location.reload();</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>
