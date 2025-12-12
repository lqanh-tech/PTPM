<?php
/**
 * Comprehensive Email Notification Test Script
 * Tests all email types: order success, approved, cancelled, payment, return request, return approved
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Email Notification Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
h2 { color: #667eea; margin-top: 30px; }
.test-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
.success { background: #d4edda; border-left-color: #28a745; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border-left-color: #dc3545; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border-left-color: #17a2b8; padding: 15px; margin: 10px 0; border-radius: 5px; }
.email-list { background: white; padding: 15px; margin: 10px 0; border: 1px solid #dee2e6; border-radius: 5px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";
echo "</head><body>";
echo "<div class='container'>";
echo "<h1>📧 Comprehensive Email Notification Test</h1>";
echo "<p>Testing all email notifications for the system</p>";

// Load required files
require_once __DIR__ . '/administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/EmailService.php';

try {
    $emailService = new EmailService();
    $db = Database::getInstance()->getConnection();
    
    echo "<div class='success'><strong>✅ EmailService initialized successfully</strong></div>";
    
    // Get test user (khachhang)
    $userSql = "SELECT username, email, hoten FROM user WHERE username = 'khachhang'";
    $userStmt = $db->prepare($userSql);
    $userStmt->execute();
    $testUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser || empty($testUser['email'])) {
        throw new Exception("Test user 'khachhang' not found or has no email");
    }
    
    echo "<div class='info'>";
    echo "<strong>Test User:</strong> {$testUser['hoten']} ({$testUser['username']})<br>";
    echo "<strong>Email:</strong> {$testUser['email']}";
    echo "</div>";
    
    // Get latest order for testing
    $orderSql = "SELECT * FROM don_hang ORDER BY id DESC LIMIT 1";
    $orderStmt = $db->prepare($orderSql);
    $orderStmt->execute();
    $testOrder = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testOrder) {
        throw new Exception("No orders found in database for testing");
    }
    
    echo "<div class='info'>";
    echo "<strong>Test Order:</strong> #{$testOrder['ma_don_hang_text']} (ID: {$testOrder['id']})<br>";
    echo "<strong>Total:</strong> " . number_format($testOrder['tong_tien'], 0, ',', '.') . " đ<br>";
    echo "<strong>Status:</strong> {$testOrder['trang_thai']}";
    echo "</div>";
    
    $orderId = $testOrder['id'];
    $testEmail = $testUser['email'];
    
    echo "<hr>";
    
    // Test 1: Order Success Email
    echo "<div class='test-section'>";
    echo "<h2>1️⃣ Order Success Email</h2>";
    echo "<p>Testing order confirmation email...</p>";
    
    try {
        $result1 = $emailService->sendOrderSuccessEmail($orderId, $testEmail);
        if ($result1) {
            echo "<div class='success'>✅ SUCCESS: Order success email sent to $testEmail</div>";
        } else {
            echo "<div class='error'>❌ FAILED: Could not send order success email</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    // Test 2: Order Approved Email
    echo "<div class='test-section'>";
    echo "<h2>2️⃣ Order Approved Email (Bank Transfer Approved)</h2>";
    echo "<p>Testing email when admin approves order...</p>";
    
    try {
        $result2 = $emailService->sendOrderApprovedEmail($orderId, $testEmail);
        if ($result2) {
            echo "<div class='success'>✅ SUCCESS: Order approved email sent to $testEmail</div>";
        } else {
            echo "<div class='error'>❌ FAILED: Could not send order approved email</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    // Test 3: Payment Confirmed Email
    echo "<div class='test-section'>";
    echo "<h2>3️⃣ Payment Confirmed Email</h2>";
    echo "<p>Testing payment confirmation email...</p>";
    
    try {
        $result3 = $emailService->sendPaymentConfirmedEmail($orderId, $testEmail);
        if ($result3) {
            echo "<div class='success'>✅ SUCCESS: Payment confirmed email sent to $testEmail</div>";
        } else {
            echo "<div class='error'>❌ FAILED: Could not send payment confirmed email</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    // Test 4: Order Cancelled Email
    echo "<div class='test-section'>";
    echo "<h2>4️⃣ Order Cancelled Email</h2>";
    echo "<p>Testing order cancellation email...</p>";
    
    $cancelReason = "Sản phẩm tạm thời hết hàng";
    
    try {
        $result4 = $emailService->sendOrderCancelledEmail($orderId, $testEmail, $cancelReason);
        if ($result4) {
            echo "<div class='success'>✅ SUCCESS: Order cancelled email sent to $testEmail</div>";
            echo "<div class='info'><strong>Reason:</strong> $cancelReason</div>";
        } else {
            echo "<div class='error'>❌ FAILED: Could not send order cancelled email</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    // Test 5: Return Request Email (if method exists)
    echo "<div class='test-section'>";
    echo "<h2>5️⃣ Return Request Email</h2>";
    echo "<p>Testing return/exchange request email...</p>";
    
    if (method_exists($emailService, 'sendReturnRequestEmail')) {
        try {
            $returnReason = "Sản phẩm không đúng màu như mô tả";
            $result5 = $emailService->sendReturnRequestEmail($orderId, $testEmail, $returnReason);
            if ($result5) {
                echo "<div class='success'>✅ SUCCESS: Return request email sent to $testEmail</div>";
                echo "<div class='info'><strong>Reason:</strong> $returnReason</div>";
            } else {
                echo "<div class='error'>❌ FAILED: Could not send return request email</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='info'>⚠️ NOTE: sendReturnRequestEmail method not implemented yet</div>";
        echo "<div class='info'>This email would be sent when user requests to return/exchange an order</div>";
    }
    echo "</div>";
    
    // Test 6: Return Approved Email (if method exists)
    echo "<div class='test-section'>";
    echo "<h2>6️⃣ Return Approved Email</h2>";
    echo "<p>Testing email when admin approves return request...</p>";
    
    if (method_exists($emailService, 'sendReturnApprovedEmail')) {
        try {
            $result6 = $emailService->sendReturnApprovedEmail($orderId, $testEmail);
            if ($result6) {
                echo "<div class='success'>✅ SUCCESS: Return approved email sent to $testEmail</div>";
            } else {
                echo "<div class='error'>❌ FAILED: Could not send return approved email</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='info'>⚠️ NOTE: sendReturnApprovedEmail method not implemented yet</div>";
        echo "<div class='info'>This email would be sent when admin approves the return/exchange request</div>";
    }
    echo "</div>";
    
    // Summary
    echo "<hr>";
    echo "<h2>📊 Test Summary</h2>";
    echo "<div class='email-list'>";
    echo "<h3>Emails that should have been sent to: <code>$testEmail</code></h3>";
    echo "<ol>";
    echo "<li>✅ Order Success Confirmation</li>";
    echo "<li>✅ Order Approved (for bank transfer orders)</li>";
    echo "<li>✅ Payment Confirmed</li>";
    echo "<li>❌ Order Cancelled (with reason)</li>";
    echo "<li>" . (method_exists($emailService, 'sendReturnRequestEmail') ? "✅" : "⚠️") . " Return/Exchange Request</li>";
    echo "<li>" . (method_exists($emailService, 'sendReturnApprovedEmail') ? "✅" : "⚠️") . " Return Request Approved</li>";
    echo "</ol>";
    echo "<p><strong>Please check your inbox (and spam folder) at:</strong> <code>$testEmail</code></p>";
    echo "</div>";
    
    // Error log preview
    echo "<h2>📋 Recent Error Logs</h2>";
    $errorLog = __DIR__ . '/error.log';
    if (file_exists($errorLog)) {
        $lines = file($errorLog);
        $recentLines = array_slice($lines, -30);
        
        echo "<div style='background: #f8f9fa; padding: 15px; max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px;'>";
        echo "<pre style='margin: 0; font-size: 12px;'>";
        foreach ($recentLines as $line) {
            if (stripos($line, 'email') !== false || stripos($line, 'smtp') !== false) {
                echo "<strong style='color: #007bff;'>" . htmlspecialchars($line) . "</strong>";
            } else {
                echo htmlspecialchars($line);
            }
        }
        echo "</pre>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ FATAL ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666; margin-top: 30px;'>";
echo "<strong>Test completed at:</strong> " . date('Y-m-d H:i:s');
echo "</p>";

echo "</div></body></html>";
?>
