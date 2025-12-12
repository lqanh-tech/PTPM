<?php
/**
 * Script để force clear JavaScript cache
 * Thêm version parameter vào tất cả script tags
 */

echo "=== CLEARING JAVASCRIPT CACHE ===\n\n";

$version = time();
echo "New version: $version\n\n";

$files = [
    'lequocanh/customer/support.php',
    'lequocanh/administrator/elements_LQA/mreview_management/reviewManagementView.php',
    'lequocanh/administrator/elements_LQA/msupport_tickets/supportTicketsView.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "✗ File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Replace support.js with versioned URL
    $content = preg_replace(
        '/<script src="support\.js(\?v=\d+)?">/',
        '<script src="support.js?v=' . $version . '">',
        $content
    );
    
    // Replace any other .js files with versioned URLs
    $content = preg_replace(
        '/<script src="([^"]+\.js)(\?v=\d+)?">/',
        '<script src="$1?v=' . $version . '">',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✓ Updated: $file\n";
    } else {
        echo "- No changes: $file\n";
    }
}

echo "\n=== DONE ===\n";
echo "\nNEXT STEPS:\n";
echo "1. Hard refresh browser: Ctrl+F5\n";
echo "2. Or clear cache: Ctrl+Shift+Delete\n";
echo "3. Test page: https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/customer/test_base_url.php\n";
