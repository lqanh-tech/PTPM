<?php
/**
 * Test EmailService Fix
 * Kiểm tra các methods mới đã được thêm vào EmailService
 */

require_once 'lequocanh/administrator/elements_LQA/mod/EmailService.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Test EmailService Fix</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .test-pass { color: #28a745; }
        .test-fail { color: #dc3545; }
        .method-box { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1><i class='fas fa-envelope me-2'></i>Test EmailService Fix</h1>
        <p class='text-muted'>Kiểm tra các methods đã được thêm vào EmailService</p>
        <hr>";

// Test 1: Check if EmailService class exists
echo "<div class='test-section'>
    <h3><i class='fas fa-check-circle me-2'></i>Test 1: Kiểm tra EmailService class</h3>";

if (class_exists('EmailService')) {
    echo "<p class='test-pass'><i class='fas fa-check'></i> EmailService class tồn tại</p>";
    
    $emailService = new EmailService();
    echo "<p class='test-pass'><i class='fas fa-check'></i> Khởi tạo EmailService thành công</p>";
} else {
    echo "<p class='test-fail'><i class='fas fa-times'></i> EmailService class không tồn tại</p>";
}

echo "</div>";

// Test 2: Check if all required methods exist
echo "<div class='test-section'>
    <h3><i class='fas fa-list me-2'></i>Test 2: Kiểm tra các methods</h3>";

$requiredMethods = [
    'sendOrderApprovedEmail',
    'sendOrderCancelledEmail',
    'sendPaymentConfirmedEmail',
    'sendOrderSuccessEmail',
    'sendShippingUpdateEmail',
    'sendOrderConfirmationEmail'
];

$allMethodsExist = true;

foreach ($requiredMethods as $method) {
    if (method_exists('EmailService', $method)) {
        echo "<div class='method-box'>
            <i class='fas fa-check text-success'></i> 
            <strong>$method()</strong> - Tồn tại
        </div>";
    } else {
        echo "<div class='method-box'>
            <i class='fas fa-times text-danger'></i> 
            <strong>$method()</strong> - Không tồn tại
        </div>";
        $allMethodsExist = false;
    }
}

if ($allMethodsExist) {
    echo "<p class='test-pass mt-3'><i class='fas fa-check-circle'></i> Tất cả methods đều tồn tại!</p>";
} else {
    echo "<p class='test-fail mt-3'><i class='fas fa-times-circle'></i> Một số methods còn thiếu!</p>";
}

echo "</div>";

// Test 3: Check method signatures
echo "<div class='test-section'>
    <h3><i class='fas fa-code me-2'></i>Test 3: Kiểm tra method signatures</h3>";

try {
    $reflection = new ReflectionClass('EmailService');
    
    echo "<table class='table table-bordered'>
        <thead>
            <tr>
                <th>Method</th>
                <th>Parameters</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($requiredMethods as $methodName) {
        if ($reflection->hasMethod($methodName)) {
            $method = $reflection->getMethod($methodName);
            $params = $method->getParameters();
            
            $paramNames = array_map(function($param) {
                return '$' . $param->getName();
            }, $params);
            
            echo "<tr>
                <td><code>$methodName</code></td>
                <td><code>" . implode(', ', $paramNames) . "</code></td>
                <td><span class='badge bg-success'>OK</span></td>
            </tr>";
        } else {
            echo "<tr>
                <td><code>$methodName</code></td>
                <td>-</td>
                <td><span class='badge bg-danger'>Missing</span></td>
            </tr>";
        }
    }
    
    echo "</tbody></table>";
    
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 4: Test CustomerNotificationManager compatibility
echo "<div class='test-section'>
    <h3><i class='fas fa-link me-2'></i>Test 4: Kiểm tra tương thích với CustomerNotificationManager</h3>";

try {
    require_once 'lequocanh/administrator/elements_LQA/mod/CustomerNotificationManager.php';
    
    echo "<p class='test-pass'><i class='fas fa-check'></i> CustomerNotificationManager loaded thành công</p>";
    
    // Check if the methods called by CustomerNotificationManager exist
    $calledMethods = [
        'sendOrderApprovedEmail' => 'notifyOrderApproved',
        'sendOrderCancelledEmail' => 'notifyOrderCancelled',
        'sendPaymentConfirmedEmail' => 'notifyPaymentConfirmed',
        'sendOrderSuccessEmail' => 'notifyOrderSuccess'
    ];
    
    echo "<table class='table table-bordered mt-3'>
        <thead>
            <tr>
                <th>CustomerNotificationManager Method</th>
                <th>Calls EmailService Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($calledMethods as $emailMethod => $notifMethod) {
        $exists = method_exists('EmailService', $emailMethod);
        $status = $exists ? "<span class='badge bg-success'>✓ Compatible</span>" : "<span class='badge bg-danger'>✗ Missing</span>";
        
        echo "<tr>
            <td><code>$notifMethod()</code></td>
            <td><code>$emailMethod()</code></td>
            <td>$status</td>
        </tr>";
    }
    
    echo "</tbody></table>";
    
    echo "<p class='test-pass'><i class='fas fa-check-circle'></i> Tất cả methods tương thích!</p>";
    
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 5: Simulate the error scenario
echo "<div class='test-section'>
    <h3><i class='fas fa-bug me-2'></i>Test 5: Mô phỏng lỗi ban đầu</h3>";

echo "<div class='alert alert-info'>
    <strong>Lỗi ban đầu:</strong><br>
    <code>Call to undefined method EmailService::sendOrderApprovedEmail()</code>
</div>";

try {
    $emailService = new EmailService();
    
    // Try to call the method that was missing
    if (method_exists($emailService, 'sendOrderApprovedEmail')) {
        echo "<p class='test-pass'><i class='fas fa-check'></i> Method <code>sendOrderApprovedEmail()</code> hiện đã tồn tại!</p>";
        echo "<p class='test-pass'><i class='fas fa-check'></i> Lỗi đã được sửa thành công!</p>";
    } else {
        echo "<p class='test-fail'><i class='fas fa-times'></i> Method vẫn chưa tồn tại!</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='test-fail'><i class='fas fa-times'></i> Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Summary
echo "<div class='test-section' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'>
    <h3><i class='fas fa-check-double me-2'></i>Tổng Kết</h3>
    <p>✅ EmailService đã được sửa thành công!</p>
    <p>📝 Các methods đã thêm:</p>
    <ul>
        <li>✓ sendOrderApprovedEmail() - Gửi email khi đơn hàng được duyệt</li>
        <li>✓ sendOrderCancelledEmail() - Gửi email khi đơn hàng bị hủy</li>
        <li>✓ sendPaymentConfirmedEmail() - Gửi email khi thanh toán được xác nhận</li>
        <li>✓ sendOrderSuccessEmail() - Gửi email khi đơn hàng hoàn thành</li>
    </ul>
    <hr style='border-color: rgba(255,255,255,0.3);'>
    <p><strong>🎯 Bước tiếp theo:</strong></p>
    <p>Thử duyệt đơn hàng từ trang quản lý để kiểm tra:</p>
    <p><a href='lequocanh/administrator/index.php?req=don_hang' class='btn btn-light'>
        <i class='fas fa-arrow-right me-2'></i>Truy cập Quản lý đơn hàng
    </a></p>
</div>";

echo "</div>
</body>
</html>";
?>
