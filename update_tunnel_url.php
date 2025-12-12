<?php
/**
 * Script tự động cập nhật Cloudflare Tunnel URL
 * Chạy script này mỗi khi có URL tunnel mới
 */

if ($argc < 2) {
    echo "Usage: php update_tunnel_url.php <new_tunnel_url>\n";
    echo "Example: php update_tunnel_url.php https://abc-xyz-123.trycloudflare.com\n";
    exit(1);
}

$newUrl = $argv[1];

// Validate URL
if (!filter_var($newUrl, FILTER_VALIDATE_URL)) {
    echo "Error: Invalid URL format\n";
    exit(1);
}

echo "🔄 Updating Cloudflare Tunnel URL to: $newUrl\n";

// Files to update
$filesToUpdate = [
    '.env' => [
        'pattern' => '/BASE_URL=https:\/\/[^\\s]+/',
        'replacement' => "BASE_URL=$newUrl"
    ],
    'lequocanh/payment/MoMoConfig.php' => [
        'pattern' => '/return \'https:\/\/[^\']+\.trycloudflare\.com\';/',
        'replacement' => "return '$newUrl';"
    ],
    'lequocanh/config/ConfigManager.php' => [
        'pattern' => '/\$_ENV\[\'BASE_URL\'\] \?\? \'https:\/\/[^\']+\'/',
        'replacement' => "\$_ENV['BASE_URL'] ?? '$newUrl'"
    ],
    'lequocanh/config/app.php' => [
        'pattern' => '/\'base\' => \$_ENV\[\'BASE_URL\'\] \?\? \'https:\/\/[^\']+\'/',
        'replacement' => "'base' => \$_ENV['BASE_URL'] ?? '$newUrl'"
    ]
];

$updatedFiles = 0;

foreach ($filesToUpdate as $file => $config) {
    if (!file_exists($file)) {
        echo "⚠️  File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $newContent = preg_replace($config['pattern'], $config['replacement'], $content);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "✅ Updated: $file\n";
        $updatedFiles++;
    } else {
        echo "ℹ️  No changes needed: $file\n";
    }
}

echo "\n🎉 Update completed! Updated $updatedFiles files.\n";
echo "📝 New URLs:\n";
echo "   - Return URL: $newUrl/lequocanh/administrator/elements_LQA/mgiohang/momo_return.php\n";
echo "   - Notify URL: $newUrl/lequocanh/payment/notify.php\n";
echo "\n🧪 Test MoMo payment: http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/checkout.php\n";
?>