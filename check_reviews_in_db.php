<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== KIỂM TRA ĐÁNH GIÁ TRONG DATABASE ===\n\n";

// Kiểm tra có đánh giá nào không
$sql = "SELECT COUNT(*) as count FROM product_reviews";
$stmt = $conn->query($sql);
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "Tổng số đánh giá: $count\n\n";

if ($count > 0) {
    // Lấy đánh giá mẫu
    $sql = "SELECT * FROM product_reviews ORDER BY ngay_tao DESC LIMIT 3";
    $stmt = $conn->query($sql);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Đánh giá mẫu:\n";
    foreach ($reviews as $review) {
        echo "  - ID: {$review['id']}, Sản phẩm: {$review['ma_san_pham']}, User: {$review['ma_nguoi_dung']}, Rating: {$review['rating']}\n";
        echo "    Comment: {$review['comment']}\n";
        echo "    Ngày: {$review['ngay_tao']}\n\n";
    }
    
    // Kiểm tra sản phẩm iPhone 13 Pro (ID: 143)
    $sql = "SELECT * FROM product_reviews WHERE ma_san_pham = 143";
    $stmt = $conn->query($sql);
    $iphone_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Đánh giá cho iPhone 13 Pro (ID: 143): " . count($iphone_reviews) . "\n";
    foreach ($iphone_reviews as $review) {
        echo "  - Rating: {$review['rating']}, Comment: {$review['comment']}\n";
    }
} else {
    echo "Chưa có đánh giá nào trong database!\n";
    echo "Cần tạo đánh giá mẫu...\n\n";
    
    // Tạo đánh giá mẫu cho iPhone 13 Pro
    $insertSql = "INSERT INTO product_reviews (ma_don_hang, ma_san_pham, ma_nguoi_dung, rating, comment, is_verified_purchase, is_approved) 
                  VALUES (66, 143, 'khachhang', 5, 'Sản phẩm rất tốt, chất lượng cao!', 1, 1)";
    $conn->exec($insertSql);
    
    echo "✓ Đã tạo đánh giá mẫu cho iPhone 13 Pro\n";
    echo "  - Rating: 5 sao\n";
    echo "  - Comment: Sản phẩm rất tốt, chất lượng cao!\n";
    echo "  - User: khachhang\n";
    echo "  - Đơn hàng: 66\n";
}

// Test API response
echo "\n=== TEST API RESPONSE ===\n";
$apiUrl = "http://localhost:20080/lequocanh/api/product_reviews.php?action=list&product_id=143";
echo "API URL: $apiUrl\n";

// Simulate API call
$sql = "SELECT 
            pr.*,
            pr.ma_nguoi_dung as user_name
        FROM product_reviews pr
        WHERE pr.ma_san_pham = 143 AND pr.is_approved = 1
        ORDER BY pr.ngay_tao DESC";
$stmt = $conn->query($sql);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$statsSql = "SELECT * FROM v_product_review_stats WHERE ma_san_pham = 143";
$stmt = $conn->query($statsSql);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$apiResponse = [
    'success' => true,
    'data' => [
        'stats' => $stats ?: [
            'total_reviews' => 0,
            'average_rating' => 0,
            'five_star' => 0,
            'four_star' => 0,
            'three_star' => 0,
            'two_star' => 0,
            'one_star' => 0
        ],
        'reviews' => $reviews,
        'pagination' => [
            'page' => 1,
            'limit' => 10,
            'total' => count($reviews),
            'total_pages' => 1
        ]
    ]
];

echo json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);