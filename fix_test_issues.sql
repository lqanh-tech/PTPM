-- File SQL để sửa các lỗi phát hiện từ test (phiên bản đơn giản)
-- Chạy script này để thêm các cột và bảng còn thiếu

-- 1. Thêm cột email vào bảng user (nếu chưa có)
SET @tbl = 'user';
SET @col = 'email';

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=@tbl AND COLUMN_NAME=@col) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tbl, " ADD COLUMN ", @col, " VARCHAR(255) DEFAULT NULL AFTER username, ADD INDEX idx_email (", @col, ")")
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Tạo bảng user_addresses nếu chưa tồn tại
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `address_type` ENUM('home', 'office', 'other') DEFAULT 'home',
  `recipient_name` VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(20) NOT NULL,
  `province` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NOT NULL,
  `ward` VARCHAR(100) NOT NULL,
  `street_address` TEXT NOT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tạo bảng product_reviews nếu chưa tồn tại
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` TINYINT NOT NULL,
  `comment` TEXT,
  `images` TEXT DEFAULT NULL,
  `is_verified_purchase` TINYINT(1) DEFAULT 0,
  `helpful_count` INT DEFAULT 0,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_product_id` (`product_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_rating` (`rating`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tạo bảng news nếu chưa tồn tại  
CREATE TABLE IF NOT EXISTS `news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(500) NOT NULL,
  `summary` TEXT,
  `content` LONGTEXT,
  `featured_image` VARCHAR(500) DEFAULT NULL,
  `author_id` INT DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `tags` TEXT DEFAULT NULL,
  `published_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_published` TINYINT(1) DEFAULT 1,
  `view_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE INDEX `idx_slug` (`slug`),
  INDEX `idx_published_date` (`published_date`),
  INDEX `idx_is_published` (`is_published`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Thêm dữ liệu mẫu cho news (chỉ nếu bảng trống)
INSERT INTO `news` (`title`, `slug`, `summary`, `content`, `published_date`, `view_count`)
SELECT * FROM (SELECT 
  'Ra mắt iPhone 15 Pro Max' as title,
  'ra-mat-iphone-15-pro-max' as slug,
  'Apple chính thức ra mắt iPhone 15 Pro Max với nhiều tính năng mới' as summary,
  'Nội dung chi tiết về iPhone 15 Pro Max...' as content,
  NOW() as published_date,
  150 as view_count
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `news` WHERE `slug` = 'ra-mat-iphone-15-pro-max');

INSERT INTO `news` (`title`, `slug`, `summary`, `content`, `published_date`, `view_count`)
SELECT * FROM (SELECT 
  'Top 5 điện thoại gaming tốt nhất 2024' as title,
  'top-5-dien-thoai-gaming-2024' as slug,
  'Tổng hợp những chiếc điện thoại gaming mạnh mẽ nhất' as summary,
  'Nội dung chi tiết...' as content,
  NOW() as published_date,
  89 as view_count
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `news` WHERE `slug` = 'top-5-dien-thoai-gaming-2024');

INSERT INTO `news` (`title`, `slug`, `summary`, `content`, `published_date`, `view_count`)
SELECT * FROM (SELECT 
  'Hướng dẫn chọn mua điện thoại phù hợp' as title,
  'huong-dan-chon-mua-dien-thoai' as slug,
  'Những tiêu chí cần lưu ý khi mua điện thoại mới' as summary,
  'Nội dung chi tiết...' as content,
  NOW() as published_date,
  234 as view_count
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `news` WHERE `slug` = 'huong-dan-chon-mua-dien-thoai');

INSERT INTO `news` (`title`, `slug`, `summary`, `content`, `published_date`, `view_count`)
SELECT * FROM (SELECT 
  'Chương trình khuyến mãi tháng 11' as title,
  'chuong-trinh-khuyen-mai-thang-11' as slug,
  'Ưu đãi lớn dành cho khách hàng trong tháng 11' as summary,
  'Nội dung chi tiết...' as content,
  NOW() as published_date,
  67 as view_count
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `news` WHERE `slug` = 'chuong-trinh-khuyen-mai-thang-11');

INSERT INTO `news` (`title`, `slug`, `summary`, `content`, `published_date`, `view_count`)
SELECT * FROM (SELECT 
  'So sánh Android vs iOS 2024' as title,
  'so-sanh-android-vs-ios-2024' as slug,
  'Đánh giá chi tiết về hai hệ điều hành phổ biến' as summary,
  'Nội dung chi tiết...' as content,
  NOW() as published_date,
  112 as view_count
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `news` WHERE `slug` = 'so-sanh-android-vs-ios-2024');
