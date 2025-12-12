<?php
/**
 * Recreate product_reviews table với cấu trúc đúng
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "=== RECREATE PRODUCT_REVIEWS TABLE ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Drop bảng cũ
    echo "1. Dropping old tables...\n";
    $conn->exec("DROP TABLE IF EXISTS review_helpful");
    $conn->exec("DROP TABLE IF EXISTS review_images");
    $conn->exec("DROP VIEW IF EXISTS v_product_review_stats");
    $conn->exec("DROP TRIGGER IF EXISTS after_review_insert");
    $conn->exec("DROP TRIGGER IF EXISTS after_review_update");
    $conn->exec("DROP TRIGGER IF EXISTS after_review_delete");
    $conn->exec("DROP PROCEDURE IF EXISTS update_product_rating");
    $conn->exec("DROP TABLE IF EXISTS product_reviews");
    echo "   ✓ Dropped old tables\n\n";
    
    // 2. Tạo bảng mới
    echo "2. Creating new product_reviews table...\n";
    $sql = "CREATE TABLE `product_reviews` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `ma_don_hang` INT NOT NULL COMMENT 'ID đơn hàng',
      `ma_san_pham` INT NOT NULL COMMENT 'ID sản phẩm',
      `ma_nguoi_dung` VARCHAR(50) NOT NULL COMMENT 'ID người dùng',
      `rating` TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5) COMMENT 'Đánh giá từ 1-5 sao',
      `comment` TEXT DEFAULT NULL COMMENT 'Nhận xét của khách hàng',
      `is_verified_purchase` TINYINT(1) DEFAULT 1 COMMENT 'Đã mua hàng xác thực',
      `is_approved` TINYINT(1) DEFAULT 1 COMMENT 'Đã được duyệt hiển thị',
      `helpful_count` INT DEFAULT 0 COMMENT 'Số lượt hữu ích',
      `ngay_tao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `ngay_cap_nhat` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_product` (`ma_san_pham`),
      INDEX `idx_user` (`ma_nguoi_dung`),
      INDEX `idx_rating` (`rating`),
      INDEX `idx_approved` (`is_approved`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Đánh giá sản phẩm từ khách hàng'";
    $conn->exec($sql);
    echo "   ✓ Created product_reviews table\n\n";
    
    // 3. Tạo bảng review_images
    echo "3. Creating review_images table...\n";
    $sql = "CREATE TABLE `review_images` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `review_id` INT NOT NULL COMMENT 'ID đánh giá',
      `image_path` VARCHAR(255) NOT NULL COMMENT 'Đường dẫn hình ảnh',
      `ngay_tao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`review_id`) REFERENCES `product_reviews`(`id`) ON DELETE CASCADE,
      INDEX `idx_review` (`review_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hình ảnh đính kèm đánh giá'";
    $conn->exec($sql);
    echo "   ✓ Created review_images table\n\n";
    
    // 4. Tạo bảng review_helpful
    echo "4. Creating review_helpful table...\n";
    $sql = "CREATE TABLE `review_helpful` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `review_id` INT NOT NULL COMMENT 'ID đánh giá',
      `ma_nguoi_dung` VARCHAR(50) NOT NULL COMMENT 'ID người dùng',
      `ngay_tao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`review_id`) REFERENCES `product_reviews`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_helpful` (`review_id`, `ma_nguoi_dung`),
      INDEX `idx_review` (`review_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lượt đánh giá hữu ích'";
    $conn->exec($sql);
    echo "   ✓ Created review_helpful table\n\n";
    
    // 5. Tạo view
    echo "5. Creating v_product_review_stats view...\n";
    $sql = "CREATE VIEW `v_product_review_stats` AS
    SELECT 
        pr.ma_san_pham,
        COUNT(*) as total_reviews,
        AVG(pr.rating) as average_rating,
        SUM(CASE WHEN pr.rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN pr.rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN pr.rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN pr.rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN pr.rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM product_reviews pr
    WHERE pr.is_approved = 1
    GROUP BY pr.ma_san_pham";
    $conn->exec($sql);
    echo "   ✓ Created view\n\n";
    
    // 6. Kiểm tra cấu trúc
    echo "6. Verifying table structure...\n";
    $cols = $conn->query("SHOW COLUMNS FROM product_reviews")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n✓✓✓ SUCCESS ✓✓✓\n";
    echo "Bảng product_reviews đã được tạo lại với cấu trúc đúng!\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
