-- Add missing columns to product_reviews table for review management

-- Add status column if not exists
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_reviews' AND COLUMN_NAME = 'status');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE product_reviews ADD COLUMN status ENUM(''visible'', ''hidden'', ''deleted'') DEFAULT ''visible'' COMMENT ''Trạng thái hiển thị''', 
    'SELECT ''Column status already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add admin_note column if not exists
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_reviews' AND COLUMN_NAME = 'admin_note');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE product_reviews ADD COLUMN admin_note TEXT NULL COMMENT ''Ghi chú của admin''', 
    'SELECT ''Column admin_note already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add hidden_at column if not exists
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_reviews' AND COLUMN_NAME = 'hidden_at');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE product_reviews ADD COLUMN hidden_at DATETIME NULL COMMENT ''Thời gian ẩn''', 
    'SELECT ''Column hidden_at already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add hidden_by column if not exists
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_reviews' AND COLUMN_NAME = 'hidden_by');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE product_reviews ADD COLUMN hidden_by VARCHAR(50) NULL COMMENT ''Admin ẩn''', 
    'SELECT ''Column hidden_by already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing reviews to have 'visible' status if null
UPDATE product_reviews SET status = 'visible' WHERE status IS NULL;

-- Create review_reports table if not exists
CREATE TABLE IF NOT EXISTS `review_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `review_id` INT NOT NULL COMMENT 'ID bình luận bị khiếu nại',
    `reporter_id` VARCHAR(50) NOT NULL COMMENT 'ID người khiếu nại',
    `reason` ENUM('spam', 'offensive', 'fake', 'inappropriate', 'other') NOT NULL COMMENT 'Lý do khiếu nại',
    `description` TEXT NULL COMMENT 'Mô tả chi tiết',
    `status` ENUM('pending', 'reviewing', 'resolved', 'rejected') DEFAULT 'pending' COMMENT 'Trạng thái xử lý',
    `admin_response` TEXT NULL COMMENT 'Phản hồi của admin',
    `resolved_by` VARCHAR(50) NULL COMMENT 'Admin xử lý',
    `resolved_at` DATETIME NULL COMMENT 'Thời gian xử lý',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_review` (`review_id`),
    INDEX `idx_reporter` (`reporter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Khiếu nại bình luận';

SELECT 'Review management columns added successfully!' as status;
