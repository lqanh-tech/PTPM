<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

// Cập nhật comment cho đánh giá
$sql = "UPDATE product_reviews SET comment = 'Sản phẩm rất tốt, chất lượng cao! Giao hàng nhanh, đóng gói cẩn thận. Rất hài lòng với mua hàng này.' WHERE id = 1";
$conn->exec($sql);

echo "✓ Đã cập nhật comment cho đánh giá\n";

// Kiểm tra lại
$sql = "SELECT * FROM product_reviews WHERE id = 1";
$stmt = $conn->query($sql);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Đánh giá sau khi cập nhật:\n";
echo "  - Rating: {$review['rating']} sao\n";
echo "  - Comment: {$review['comment']}\n";