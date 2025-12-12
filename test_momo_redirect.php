<?php
/**
 * Test MoMo Redirect - Kiểm tra chức năng chuyển hướng sau thanh toán
 */

echo "<h2>Test MoMo Redirect</h2>";

// Simulate successful payment callback
$testParams = [
    'partnerCode' => 'MOMO',
    'orderId' => 'ORDER' . time(),
    'requestId' => 'REQ' . time(),
    'amount' => '100000',
    'orderInfo' => 'Test Order',
    'orderType' => 'momo_wallet',
    'transId' => '123456789',
    'resultCode' => '0',
    'message' => 'Successful',
    'payType' => 'qr',
    'responseTime' => time() * 1000,
    'extraData' => urlencode(json_encode([
        'order_code' => 'TEST123',
        'user_id' => '1',
        'shipping_address' => 'Test Address'
    ])),
    'signature' => 'test_signature'
];

$queryString = http_build_query($testParams);
$returnUrl = "http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/momo_return.php?" . $queryString;

echo "<p><strong>Test URL:</strong></p>";
echo "<p><a href='{$returnUrl}' target='_blank' class='btn btn-primary'>Click để test redirect</a></p>";
echo "<p><small>URL: {$returnUrl}</small></p>";

echo "<hr>";
echo "<h3>Hướng dẫn test:</h3>";
echo "<ol>";
echo "<li>Click vào link trên</li>";
echo "<li>Kiểm tra xem có chuyển hướng đến trang order_success.php không</li>";
echo "<li>Mở Console (F12) để xem log</li>";
echo "<li>Kiểm tra error.log để xem có lỗi không</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Checklist:</h3>";
echo "<ul>";
echo "<li>✓ JavaScript redirect được thực thi</li>";
echo "<li>✓ Session được set đúng</li>";
echo "<li>✓ Database được cập nhật</li>";
echo "<li>✓ Chuyển hướng đến order_success.php</li>";
echo "</ul>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test MoMo Redirect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
</body>
</html>
