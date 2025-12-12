<?php
/**
 * Kiểm tra cấu hình URL sau khi chuyển từ ngrok sang Cloudflare Tunnel
 */

// Load configuration
require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../payment/MoMoConfig.php';

echo "<h2>🔍 Kiểm Tra Cấu Hình URL</h2>";

echo "<div style='font-family: Arial, sans-serif; margin: 20px;'>";

// Kiểm tra BASE_URL từ .env
echo "<h3>📁 Environment Variables (.env)</h3>";
echo "<p><strong>BASE_URL:</strong> " . ($_ENV['BASE_URL'] ?? 'Not set') . "</p>";
echo "<p><strong>FORCE_NGROK:</strong> " . ($_ENV['FORCE_NGROK'] ?? 'Not set') . "</p>";

// Kiểm tra ConfigManager
echo "<h3>⚙️ ConfigManager</h3>";
try {
    $config = ConfigManager::getInstance();
    echo "<p><strong>Base URL:</strong> " . $config->getBaseUrl() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

// Kiểm tra MoMo Config
echo "<h3>💳 MoMo Configuration</h3>";
try {
    echo "<p><strong>Base URL:</strong> " . MoMoConfig::getBaseUrl() . "</p>";
    echo "<p><strong>Return URL:</strong> " . MoMoConfig::getReturnUrl() . "</p>";
    echo "<p><strong>Notify URL:</strong> " . MoMoConfig::getNotifyUrl() . "</p>";
    echo "<p><strong>API Endpoint:</strong> " . MoMoConfig::getEndpoint() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>MoMo Error:</strong> " . $e->getMessage() . "</p>";
}

// Kiểm tra kết nối Cloudflare Tunnel
echo "<h3>🌐 Cloudflare Tunnel Status</h3>";
$tunnelUrl = 'https://retirement-retirement-cas-shakira.trycloudflare.com';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tunnelUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode == 200) {
    echo "<p style='color: green;'><strong>✅ Tunnel Active:</strong> $tunnelUrl (HTTP $httpCode)</p>";
} else {
    echo "<p style='color: red;'><strong>❌ Tunnel Error:</strong> HTTP $httpCode</p>";
    if ($error) {
        echo "<p style='color: red;'><strong>cURL Error:</strong> $error</p>";
    }
}

// Test localhost
echo "<h3>🏠 Localhost Status</h3>";
$localhostUrl = 'http://localhost:8080';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $localhostUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "<p style='color: green;'><strong>✅ Localhost Active:</strong> $localhostUrl (HTTP $httpCode)</p>";
} else {
    echo "<p style='color: red;'><strong>❌ Localhost Error:</strong> HTTP $httpCode</p>";
}

echo "</div>";

// Hiển thị summary
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px;'>";
echo "<h3>📋 Summary</h3>";
echo "<p><strong>Status:</strong> " . (($httpCode == 200) ? "✅ Ready for MoMo Payment" : "❌ Configuration Issues") . "</p>";
echo "<p><strong>Next Step:</strong> " . (($httpCode == 200) ? "Test MoMo payment with your order" : "Fix configuration issues above") . "</p>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 5px 0; }
</style>