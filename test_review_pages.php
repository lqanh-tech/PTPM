<?php
echo "Testing Review Management Pages...\n\n";

// Test 1: Check routing
echo "1. Checking routing in center.php...\n";
$centerContent = file_get_contents('lequocanh/administrator/elements_LQA/center.php');
if (strpos($centerContent, 'review_management') !== false) {
    echo "   ✅ review_management route exists\n";
} else {
    echo "   ❌ review_management route missing\n";
}

if (strpos($centerContent, 'support_tickets') !== false) {
    echo "   ✅ support_tickets route exists\n";
} else {
    echo "   ❌ support_tickets route missing\n";
}

// Test 2: Check files exist
echo "\n2. Checking files exist...\n";
$files = [
    'lequocanh/administrator/elements_LQA/mreview_management/reviewManagementView.php',
    'lequocanh/administrator/elements_LQA/msupport_tickets/supportTicketsView.php',
    'lequocanh/customer/support.php',
    'lequocanh/customer/support.js',
    'lequocanh/api/review_management.php',
    'lequocanh/api/support_tickets.php',
    'lequocanh/api/report_review.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file MISSING\n";
    }
}

// Test 3: Check API paths
echo "\n3. Checking API paths...\n";
$reviewMgmt = file_get_contents('lequocanh/administrator/elements_LQA/mreview_management/reviewManagementView.php');
if (strpos($reviewMgmt, '../api/review_management.php') !== false) {
    echo "   ✅ Review management uses correct API path\n";
} else {
    echo "   ❌ Review management API path incorrect\n";
}

$supportTickets = file_get_contents('lequocanh/administrator/elements_LQA/msupport_tickets/supportTicketsView.php');
if (strpos($supportTickets, '../api/support_tickets.php') !== false) {
    echo "   ✅ Support tickets uses correct API path\n";
} else {
    echo "   ❌ Support tickets API path incorrect\n";
}

// Test 4: Check support button in index
echo "\n4. Checking support button in main page...\n";
$indexContent = file_get_contents('lequocanh/index.php');
if (strpos($indexContent, 'customer/support.php') !== false) {
    echo "   ✅ Support button exists in header\n";
} else {
    echo "   ❌ Support button missing\n";
}

if (strpos($indexContent, 'pulse-animation') !== false) {
    echo "   ✅ Animation CSS exists\n";
} else {
    echo "   ❌ Animation CSS missing\n";
}

echo "\n✅ All tests completed!\n";
echo "\nAccess URLs:\n";
echo "- Admin Review Management: /lequocanh/administrator/index.php?req=review_management\n";
echo "- Admin Support Tickets: /lequocanh/administrator/index.php?req=support_tickets\n";
echo "- User Support Page: /lequocanh/customer/support.php\n";
