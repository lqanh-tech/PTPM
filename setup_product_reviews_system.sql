-- =====================================================
-- HỆ THỐNG ĐÁNH GIÁ SẢN PHẨM
-- Tạo bảng lưu trữ đánh giá và rating của khách hàng
-- =====================================================

-- Bảng đánh giá sản phẩm
CREATE TABLE IF NOT EXISTS `product_reviews` (
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
  FOREIGN KEY (`ma_don_hang`) REFERENCES `don_hang`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ma_san_pham`) REFERENCES `tbl_hanghoa`(`id`) ON DELETE CASCADE,
  INDEX `idx_product` (`ma_san_pham`),
  INDEX `idx_user` (`ma_nguoi_dung`),
  INDEX `idx_rating` (`rating`),
  INDEX `idx_approved` (`is_approved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Đánh giá sản phẩm từ khách hàng';

-- Bảng lưu trữ hình ảnh đánh giá (optional)
CREATE TABLE IF NOT EXISTS `review_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `review_id` INT NOT NULL COMMENT 'ID đánh giá',
  `image_path` VARCHAR(255) NOT NULL COMMENT 'Đường dẫn hình ảnh',
  `ngay_tao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`review_id`) REFERENCES `product_reviews`(`id`) ON DELETE CASCADE,
  INDEX `idx_review` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hình ảnh đính kèm đánh giá';

-- Bảng lưu trữ lượt đánh giá hữu ích
CREATE TABLE IF NOT EXISTS `review_helpful` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `review_id` INT NOT NULL COMMENT 'ID đánh giá',
  `ma_nguoi_dung` VARCHAR(50) NOT NULL COMMENT 'ID người dùng',
  `ngay_tao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`review_id`) REFERENCES `product_reviews`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_helpful` (`review_id`, `ma_nguoi_dung`),
  INDEX `idx_review` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lượt đánh giá hữu ích';

-- Thêm cột đánh giá trung bình vào bảng sản phẩm (nếu chưa có)
ALTER TABLE `tbl_hanghoa` 
ADD COLUMN IF NOT EXISTS `average_rating` DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Đánh giá trung bình',
ADD COLUMN IF NOT EXISTS `total_reviews` INT DEFAULT 0 COMMENT 'Tổng số đánh giá',
ADD INDEX IF NOT EXISTS `idx_rating` (`average_rating`);

-- Thêm cột đánh giá vào bảng đơn hàng (đánh dấu đã đánh giá)
ALTER TABLE `don_hang`
ADD COLUMN IF NOT EXISTS `is_reviewed` TINYINT(1) DEFAULT 0 COMMENT 'Đã đánh giá',
ADD COLUMN IF NOT EXISTS `review_reminder_sent` TINYINT(1) DEFAULT 0 COMMENT 'Đã gửi nhắc đánh giá';

-- View để lấy thống kê đánh giá theo sản phẩm
CREATE OR REPLACE VIEW `v_product_review_stats` AS
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
GROUP BY pr.ma_san_pham;

-- Stored Procedure: Cập nhật rating trung bình của sản phẩm
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `update_product_rating`(IN product_id INT)
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE review_count INT;
    
    -- Tính rating trung bình và số lượng đánh giá
    SELECT 
        COALESCE(AVG(rating), 0),
        COUNT(*)
    INTO avg_rating, review_count
    FROM product_reviews
    WHERE ma_san_pham = product_id AND is_approved = 1;
    
    -- Cập nhật vào bảng sản phẩm
    UPDATE tbl_hanghoa
    SET 
        average_rating = avg_rating,
        total_reviews = review_count
    WHERE id = product_id;
END$$

DELIMITER ;

-- Trigger: Tự động cập nhật rating khi có đánh giá mới
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `after_review_insert` 
AFTER INSERT ON `product_reviews`
FOR EACH ROW
BEGIN
    CALL update_product_rating(NEW.ma_san_pham);
END$$

CREATE TRIGGER IF NOT EXISTS `after_review_update` 
AFTER UPDATE ON `product_reviews`
FOR EACH ROW
BEGIN
    CALL update_product_rating(NEW.ma_san_pham);
END$$

CREATE TRIGGER IF NOT EXISTS `after_review_delete` 
AFTER DELETE ON `product_reviews`
FOR EACH ROW
BEGIN
    CALL update_product_rating(OLD.ma_san_pham);
END$$

DELIMITER ;
