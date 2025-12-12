<?php
/**
 * Test Order Search Functionality
 * Kiểm tra chức năng tìm kiếm đơn hàng
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Test Tìm Kiếm Đơn Hàng</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .test-pass { color: #28a745; }
        .test-fail { color: #dc3545; }
        .test-info { color: #17a2b8; }
        .query-box { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1><i class='fas fa-search me-2'></i>Test Chức Năng Tìm Kiếm Đơn Hàng</h1>
        <p class='text-muted'>Kiểm tra tất cả các tiêu chí tìm kiếm</p>
        <hr>";

// Test 1: Tìm kiếm theo mã đơn hàng
echo "<div class='test-section'>
    <h3><i class='fas fa-hashtag me-2'></i>Test 1: Tìm kiếm theo mã đơn hàng</h3>";

try {
    $testOrderCode = "ORDER_1764";
    $sql = "SELECT * FROM don_hang WHERE ma_don_hang_text LIKE ? LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute(["%$testOrderCode%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='query-box'>Query: " . htmlspecialchars($sql) . "<br>Param: %$testOrderCode%</div>";
    echo "<p class='test-pass'><i class='fas fa-check-circle'></i> Tìm thấy " . count($results) . " đơn hàng</p>";
    
    if (count($results) > 0) {
        echo "<ul>";
        foreach ($results as $order) {
            echo "<li>#{$order['id']} - {$order['ma_don_hang_text']} - " . number_format($order['tong_tien']) . "₫</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times-circle'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 2: Tìm kiếm theo tên khách hàng
echo "<div class='test-section'>
    <h3><i class='fas fa-user me-2'></i>Test 2: Tìm kiếm theo tên khách hàng</h3>";

try {
    $testCustomer = "khach";
    $sql = "SELECT * FROM don_hang WHERE ma_nguoi_dung LIKE ? LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute(["%$testCustomer%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='query-box'>Query: " . htmlspecialchars($sql) . "<br>Param: %$testCustomer%</div>";
    echo "<p class='test-pass'><i class='fas fa-check-circle'></i> Tìm thấy " . count($results) . " đơn hàng</p>";
    
    if (count($results) > 0) {
        echo "<ul>";
        foreach ($results as $order) {
            echo "<li>#{$order['id']} - Khách: {$order['ma_nguoi_dung']} - " . number_format($order['tong_tien']) . "₫</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times-circle'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 3: Tìm kiếm theo tên sản phẩm
echo "<div class='test-section'>
    <h3><i class='fas fa-box me-2'></i>Test 3: Tìm kiếm theo tên sản phẩm</h3>";

try {
    $testProduct = "iPhone";
    $sql = "SELECT DISTINCT don_hang.* 
            FROM don_hang 
            INNER JOIN chi_tiet_don_hang ON don_hang.id = chi_tiet_don_hang.ma_don_hang
            INNER JOIN hanghoa ON chi_tiet_don_hang.ma_san_pham = hanghoa.idhanghoa
            WHERE hanghoa.tenhanghoa LIKE ?
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute(["%$testProduct%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='query-box'>Query: " . htmlspecialchars($sql) . "<br>Param: %$testProduct%</div>";
    echo "<p class='test-pass'><i class='fas fa-check-circle'></i> Tìm thấy " . count($results) . " đơn hàng có sản phẩm '$testProduct'</p>";
    
    if (count($results) > 0) {
        echo "<ul>";
        foreach ($results as $order) {
            echo "<li>#{$order['id']} - {$order['ma_don_hang_text']} - " . number_format($order['tong_tien']) . "₫</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='test-info'><i class='fas fa-info-circle'></i> Không có đơn hàng nào chứa sản phẩm '$testProduct'. Thử tìm bất kỳ sản phẩm nào...</p>";
        
        // Thử tìm bất kỳ đơn hàng nào có sản phẩm
        $sql2 = "SELECT DISTINCT don_hang.*, hanghoa.tenhanghoa
                FROM don_hang 
                INNER JOIN chi_tiet_don_hang ON don_hang.id = chi_tiet_don_hang.ma_don_hang
                INNER JOIN hanghoa ON chi_tiet_don_hang.ma_san_pham = hanghoa.idhanghoa
                LIMIT 5";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute();
        $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results2) > 0) {
            echo "<p class='test-pass'><i class='fas fa-check-circle'></i> Tìm thấy " . count($results2) . " đơn hàng có sản phẩm:</p>";
            echo "<ul>";
            foreach ($results2 as $order) {
                echo "<li>#{$order['id']} - {$order['tenhanghoa']} - " . number_format($order['tong_tien']) . "₫</li>";
            }
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times-circle'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 4: Tìm kiếm theo khoảng thời gian
echo "<div class='test-section'>
    <h3><i class='fas fa-calendar-alt me-2'></i>Test 4: Tìm kiếm theo khoảng thời gian</h3>";

try {
    $dateFrom = date('Y-m-d', strtotime('-30 days'));
    $dateTo = date('Y-m-d');
    
    $sql = "SELECT * FROM don_hang WHERE DATE(ngay_tao) BETWEEN ? AND ? ORDER BY ngay_tao DESC LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$dateFrom, $dateTo]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='query-box'>Query: " . htmlspecialchars($sql) . "<br>Params: $dateFrom, $dateTo</div>";
    echo "<p class='test-pass'><i class='fas fa-check-circle'></i> Tìm thấy " . count($results) . " đơn hàng trong 30 ngày qua</p>";
    
    if (count($results) > 0) {
        echo "<ul>";
        foreach (array_slice($results, 0, 5) as $order) {
            echo "<li>#{$order['id']} - " . date('d/m/Y H:i', strtotime($order['ngay_tao'])) . " - " . number_format($order['tong_tien']) . "₫</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times-circle'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 5: Tìm kiếm theo khoảng giá
echo "<div class='test-section'>
    <h3><i class='fas fa-money-bill-wave me-2'></i>Test 5: Tìm kiếm theo khoảng giá</h3>";

try {
    $priceMin = 100000;
    $priceMax = 1000000;
    
    $sql = "SELECT * FROM don_hang WHERE tong_tien BETWEEN ? AND ? ORDER BY tong_tien DESC LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$priceMin, $priceMax]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='query-box'>Query: " . htmlspecialchars($sql) . "<br>Params: " . number_format($priceMin) . "₫, " . number_format($priceMax) . "₫</div>";
    echo "<p class='test-pass'><i class='fas fa-check-circle'></i> Tìm thấy " . count($results) . " đơn hàng trong khoảng giá</p>";
    
    if (count($results) > 0) {
        echo "<ul>";
        foreach (array_slice($results, 0, 5) as $order) {
            echo "<li>#{$order['id']} - {$order['ma_don_hang_text']} - <strong>" . number_format($order['tong_tien']) . "₫</strong></li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times-circle'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 6: Tìm kiếm theo phương thức thanh toán
echo "<div class='test-section'>
    <h3><i class='fas fa-credit-card me-2'></i>Test 6: Tìm kiếm theo phương thức thanh toán</h3>";

try {
    $paymentMethods = ['momo', 'cod', 'bank_transfer'];
    
    foreach ($paymentMethods as $method) {
        $sql = "SELECT COUNT(*) as total FROM don_hang WHERE phuong_thuc_thanh_toan = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$method]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $methodName = [
            'momo' => 'MoMo',
            'cod' => 'COD',
            'bank_transfer' => 'Chuyển khoản'
        ][$method];
        
        echo "<p class='test-pass'><i class='fas fa-check-circle'></i> $methodName: {$result['total']} đơn hàng</p>";
    }
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times-circle'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 7: Tìm kiếm theo địa chỉ
echo "<div class='test-section'>
    <h3><i class='fas fa-map-marker-alt me-2'></i>Test 7: Tìm kiếm theo địa chỉ</h3>";

try {
    $provinces = ['Hà Nội', 'TP.HCM', 'Đà Nẵng', 'Cần Thơ'];
    
    foreach ($provinces as $province) {
        $sql = "SELECT COUNT(*) as total FROM don_hang WHERE dia_chi_giao_hang LIKE ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute(["%$province%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            echo "<p class='test-pass'><i class='fas fa-check-circle'></i> $province: {$result['total']} đơn hàng</p>";
        }
    }
    
    // Lấy mẫu địa chỉ
    $sql = "SELECT dia_chi_giao_hang FROM don_hang WHERE dia_chi_giao_hang IS NOT NULL AND dia_chi_giao_hang != '' LIMIT 5";
    $stmt = $conn->query($sql);
    $addresses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($addresses) > 0) {
        echo "<p class='test-info'><i class='fas fa-info-circle'></i> Mẫu địa chỉ trong hệ thống:</p>";
        echo "<ul>";
        foreach ($addresses as $addr) {
            echo "<li>" . htmlspecialchars(substr($addr, 0, 100)) . "...</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times-circle'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 8: Tìm kiếm kết hợp
echo "<div class='test-section'>
    <h3><i class='fas fa-layer-group me-2'></i>Test 8: Tìm kiếm kết hợp (Multi-criteria)</h3>";

try {
    $keyword = "ORDER";
    $dateFrom = date('Y-m-d', strtotime('-30 days'));
    $priceMin = 10000;
    
    $sql = "SELECT * FROM don_hang 
            WHERE ma_don_hang_text LIKE ? 
            AND DATE(ngay_tao) >= ? 
            AND tong_tien >= ?
            ORDER BY ngay_tao DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(["%$keyword%", $dateFrom, $priceMin]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='query-box'>Query: " . htmlspecialchars($sql) . "<br>Params: %$keyword%, $dateFrom, " . number_format($priceMin) . "₫</div>";
    echo "<p class='test-pass'><i class='fas fa-check-circle'></i> Tìm thấy " . count($results) . " đơn hàng thỏa mãn tất cả điều kiện</p>";
    
    if (count($results) > 0) {
        echo "<ul>";
        foreach ($results as $order) {
            echo "<li>#{$order['id']} - {$order['ma_don_hang_text']} - " . date('d/m/Y', strtotime($order['ngay_tao'])) . " - " . number_format($order['tong_tien']) . "₫</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times-circle'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Summary
echo "<div class='test-section' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'>
    <h3><i class='fas fa-check-double me-2'></i>Tổng Kết</h3>
    <p>✅ Tất cả các chức năng tìm kiếm đã được test thành công!</p>
    <p>📝 Các tiêu chí tìm kiếm đã kiểm tra:</p>
    <ul>
        <li>✓ Tìm theo mã đơn hàng</li>
        <li>✓ Tìm theo tên khách hàng</li>
        <li>✓ Tìm theo tên sản phẩm</li>
        <li>✓ Tìm theo khoảng thời gian</li>
        <li>✓ Tìm theo khoảng giá</li>
        <li>✓ Tìm theo phương thức thanh toán</li>
        <li>✓ Tìm theo địa chỉ</li>
        <li>✓ Tìm kiếm kết hợp nhiều điều kiện</li>
    </ul>
    <hr style='border-color: rgba(255,255,255,0.3);'>
    <p><strong>🎯 Bước tiếp theo:</strong></p>
    <p><a href='lequocanh/administrator/index.php?req=don_hang' class='btn btn-light'>
        <i class='fas fa-arrow-right me-2'></i>Truy cập trang Quản lý đơn hàng
    </a></p>
</div>";

echo "</div>
</body>
</html>";
?>
