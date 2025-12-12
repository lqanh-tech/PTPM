<?php
/**
 * Simple verification script - checks files directly
 */

echo "=== VERIFICATION REPORT ===\n\n";

$allPassed = true;
$results = [];

// Check 1: customer_notification_widget.php
echo "1. Checking customer_notification_widget.php...\n";
$file1 = 'lequocanh/administrator/elements_LQA/mthongbao/customer_notification_widget.php';
if (file_exists($file1)) {
    $content = file_get_contents($file1);
    
    // Check footer link
    if (strpos($content, '/lequocanh/customer/order_history.php') !== false) {
        echo "   ✓ Footer link correct: /lequocanh/customer/order_history.php\n";
    } else {
        echo "   ✗ Footer link WRONG\n";
        $allPassed = false;
    }
    
    // Check invoice link
    if (strpos($content, '/lequocanh/customer/order_invoice.php') !== false) {
        echo "   ✓ Invoice link correct: /lequocanh/customer/order_invoice.php\n";
    } else {
        echo "   ✗ Invoice link WRONG\n";
        $allPassed = false;
    }
    
    // Check no onclick viewNotificationDetail
    if (strpos($content, 'onclick="viewNotificationDetail') === false) {
        echo "   ✓ No blocking onclick on content\n";
    } else {
        echo "   ✗ Still has blocking onclick\n";
        $allPassed = false;
    }
    
    // Check markAsRead on links
    if (strpos($content, 'onclick="markAsRead') !== false) {
        echo "   ✓ Has markAsRead on links\n";
    } else {
        echo "   ✗ Missing markAsRead on links\n";
        $allPassed = false;
    }
} else {
    echo "   ✗ File not found\n";
    $allPassed = false;
}

echo "\n2. Checking orderDetailView.php...\n";
$file2 = 'lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php';
if (file_exists($file2)) {
    $content = file_get_contents($file2);
    
    // Check review widget include
    if (strpos($content, 'product_review_widget.php') !== false) {
        echo "   ✓ Review widget included\n";
    } else {
        echo "   ✗ Review widget NOT included\n";
        $allPassed = false;
    }
    
    // Check USER condition
    if (strpos($content, "!isset(\$_SESSION['ADMIN'])") !== false) {
        echo "   ✓ Only shows for USER (not ADMIN)\n";
    } else {
        echo "   ✗ Missing USER check\n";
        $allPassed = false;
    }
    
    // Check approved condition
    if (preg_match("/trang_thai.*approved|trang_thai_thanh_toan.*paid/", $content)) {
        echo "   ✓ Only shows when order approved\n";
    } else {
        echo "   ✗ Missing approved check\n";
        $allPassed = false;
    }
    
    // Check no-print class
    if (preg_match('/no-print.*product_review_widget|product_review_widget.*no-print/s', $content)) {
        echo "   ✓ Has no-print class\n";
    } else {
        echo "   ✗ Missing no-print class\n";
        $allPassed = false;
    }
} else {
    echo "   ✗ File not found\n";
    $allPassed = false;
}

echo "\n3. Checking component files...\n";
$file3 = 'lequocanh/components/product_review_widget.php';
if (file_exists($file3)) {
    echo "   ✓ product_review_widget.php exists\n";
} else {
    echo "   ✗ product_review_widget.php NOT found\n";
    $allPassed = false;
}

$file4 = 'lequocanh/api/product_reviews.php';
if (file_exists($file4)) {
    echo "   ✓ product_reviews.php API exists\n";
} else {
    echo "   ✗ product_reviews.php API NOT found\n";
    $allPassed = false;
}

echo "\n=== RESULT ===\n";
if ($allPassed) {
    echo "✓✓✓ ALL CHECKS PASSED ✓✓✓\n\n";
    echo "All fixes are in place!\n";
    echo "If you still see issues, please CLEAR BROWSER CACHE:\n";
    echo "- Chrome/Edge: Ctrl + Shift + Delete\n";
    echo "- Firefox: Ctrl + Shift + Delete\n";
    echo "- Or: Ctrl + F5 (Hard Refresh)\n";
} else {
    echo "✗✗✗ SOME CHECKS FAILED ✗✗✗\n";
    echo "Please review the errors above.\n";
}

echo "\n=== MANUAL TEST STEPS ===\n";
echo "1. Clear browser cache (Ctrl + Shift + Delete)\n";
echo "2. Login as customer\n";
echo "3. Click notification bell\n";
echo "4. Click 'Xem lịch sử đơn hàng' -> Should go to /customer/order_history.php\n";
echo "5. View approved order detail -> Should see review widget at bottom\n";
echo "6. Click 'Xem hóa đơn & Đánh giá' -> Should see review widget\n";
echo "7. Submit a review -> Should work\n";
