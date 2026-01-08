<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Email Service Direct Test</h1>\n";
echo "<p>Testing EmailService with SMTP configuration...</p>\n";

require_once __DIR__ . '/administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/EmailService.php';

echo "<h2>Step 1: Initialize EmailService</h2>\n";
try {
    $emailService = new EmailService();
    echo "✅ EmailService initialized successfully<br>\n";
} catch (Exception $e) {
    echo "❌ Failed to initialize EmailService: " . $e->getMessage() . "<br>\n";
    exit;
}

echo "<h2>Step 2: Check .env Configuration</h2>\n";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "✅ .env file found<br>\n";
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $config = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        if (strpos($key, 'MAIL_') === 0) {

            if ($key == 'MAIL_PASSWORD') {
                $config[$key] = '***HIDDEN***';
            } else {
                $config[$key] = $value;
            }
        }
    }
    
    echo "<pre>";
    print_r($config);
    echo "</pre>";
    
    if (empty($config['MAIL_HOST'])) {
        echo "⚠️ WARNING: MAIL_HOST not configured in .env<br>\n";
    }
} else {
    echo "❌ .env file not found at: $envFile<br>\n";
}

echo "<h2>Step 3: Get Test Order Data</h2>\n";
try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM don_hang ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "❌ No orders found in database<br>\n";
        echo "<p>Creating a test scenario with fake order data...</p>\n";
        $orderId = 999999;
        $testEmail = 'quocanh5758@gmail.com';
    } else {
        echo "✅ Found order: #{$order['ma_don_hang_text']} (ID: {$order['id']})<br>\n";
        $orderId = $order['id'];
        
        $userSql = "SELECT email, hoten FROM user WHERE username = ?";
        $userStmt = $db->prepare($userSql);
        $userStmt->execute([$order['ma_nguoi_dung']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && !empty($user['email'])) {
            $testEmail = $user['email'];
            echo "✅ User email: $testEmail<br>\n";
        } else {
            echo "⚠️ User has no email, using default from .env<br>\n";
            $testEmail = 'quocanh5758@gmail.com';
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>\n";
    exit;
}

echo "<h2>Step 4: Send Test Email</h2>\n";
echo "<p>Sending test email to: <strong>$testEmail</strong></p>\n";
echo "<p>This may take a few seconds...</p>\n";
flush();

try {
    $result = $emailService->sendOrderSuccessEmail($orderId, $testEmail);
    
    if ($result) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>\n";
        echo "<h3 style='color: #155724;'>✅ SUCCESS!</h3>\n";
        echo "<p>Email sent successfully to: <strong>$testEmail</strong></p>\n";
        echo "<p>Please check your inbox (and spam folder) for the order confirmation email.</p>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>\n";
        echo "<h3 style='color: #721c24;'>❌ FAILED</h3>\n";
        echo "<p>Failed to send email to: <strong>$testEmail</strong></p>\n";
        echo "<p>Check the error log below for details.</p>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>\n";
    echo "<h3 style='color: #721c24;'>❌ EXCEPTION</h3>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>\n";
}

echo "<h2>Step 5: Check Error Logs</h2>\n";
$errorLog = __DIR__ . '/error.log';
if (file_exists($errorLog)) {
    echo "<p>Reading last 50 lines from error.log...</p>\n";
    $lines = file($errorLog);
    $recentLines = array_slice($lines, -50);
    
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; max-height: 400px; overflow-y: auto;'>\n";
    echo "<pre style='margin: 0; font-size: 12px;'>";
    foreach ($recentLines as $line) {

        if (stripos($line, 'email') !== false || stripos($line, 'smtp') !== false) {
            echo "<strong style='color: #007bff;'>" . htmlspecialchars($line) . "</strong>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>\n";
    echo "</div>\n";
} else {
    echo "<p>No error.log file found</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Test completed at: " . date('Y-m-d H:i:s') . "</strong></p>\n";
?>
