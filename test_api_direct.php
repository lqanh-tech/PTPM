<?php
/**
 * Test API directly without cache
 */

// Clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
}

require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';

SessionManager::start();
$_SESSION['ADMIN'] = 'admin';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "Testing API query directly...\n\n";

$page = 1;
$limit = 20;
$offset = 0;
$status = 'all';

$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = "pr.status = ?";
    $params[] = $status;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT 
            pr.*,
            h.tenhanghoa as product_name,
            h.hinhanh as product_image,
            pr.ma_nguoi_dung as user_name,
            (SELECT COUNT(*) FROM review_reports WHERE review_id = pr.id AND status = 'pending') as report_count
        FROM product_reviews pr
        LEFT JOIN hanghoa h ON pr.ma_san_pham = h.idhanghoa
        {$whereClause}
        ORDER BY pr.ngay_tao DESC
        LIMIT " . intval($limit) . " OFFSET " . intval($offset);

echo "SQL: " . $sql . "\n\n";
echo "Params: " . json_encode($params) . "\n\n";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query successful!\n";
    echo "Found " . count($reviews) . " reviews\n\n";
    
    if (!empty($reviews)) {
        echo "First review:\n";
        print_r($reviews[0]);
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
