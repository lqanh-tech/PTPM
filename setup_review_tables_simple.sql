-- Setup Review Management System - Simple Version

-- 1. Update product_reviews table (check if columns exist first)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_reviews' AND COLUMN_NAME = 'status');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE product_reviews ADD COLUMN status ENUM(''visible'', ''hidden'', ''deleted'') DEFAULT ''visible'' COMMENT ''Trạng thái hiển thị''', 'SELECT ''Column status already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_reviews' AND COLUMN_NAME = 'admin_note');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE product_reviews ADD COLUMN admin_note TEXT NULL COMMENT ''Ghi chú của admin''', 'SELECT ''Column admin_note already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_reviews' AND COLUMN_NAME = 'hidden_at');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE product_reviews ADD COLUMN hidden_at DATETIME NULL COMMENT ''Thời gian ẩn''', 'SELECT ''Column hidden_at already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_reviews' AND COLUMN_NAME = 'hidden_by');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE product_reviews ADD COLUMN hidden_by VARCHAR(50) NULL COMMENT ''Admin ẩn''', 'SELECT ''Column hidden_by already exists''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- 2. Create review_reports table
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

-- 3. Create support_tickets table
CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(20) UNIQUE NOT NULL COMMENT 'Mã ticket',
    `user_id` VARCHAR(50) NOT NULL COMMENT 'ID người dùng',
    `subject` VARCHAR(255) NOT NULL COMMENT 'Tiêu đề',
    `category` ENUM('review_report', 'order_issue', 'product_question', 'other') DEFAULT 'other' COMMENT 'Danh mục',
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'Độ ưu tiên',
    `status` ENUM('open', 'in_progress', 'waiting_user', 'resolved', 'closed') DEFAULT 'open' COMMENT 'Trạng thái',
    `assigned_to` VARCHAR(50) NULL COMMENT 'Admin được gán',
    `related_review_id` INT NULL COMMENT 'ID bình luận liên quan (nếu có)',
    `related_order_id` INT NULL COMMENT 'ID đơn hàng liên quan (nếu có)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `closed_at` DATETIME NULL COMMENT 'Thời gian đóng',
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_assigned` (`assigned_to`),
    INDEX `idx_ticket_number` (`ticket_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ticket hỗ trợ';

-- 4. Create support_messages table
CREATE TABLE IF NOT EXISTS `support_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL COMMENT 'ID ticket',
    `sender_id` VARCHAR(50) NOT NULL COMMENT 'ID người gửi',
    `sender_type` ENUM('user', 'admin') NOT NULL COMMENT 'Loại người gửi',
    `message` TEXT NOT NULL COMMENT 'Nội dung tin nhắn',
    `attachments` JSON NULL COMMENT 'File đính kèm',
    `is_read` TINYINT(1) DEFAULT 0 COMMENT 'Đã đọc chưa',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ticket` (`ticket_id`),
    INDEX `idx_sender` (`sender_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tin nhắn hỗ trợ';

-- 5. Create views
CREATE OR REPLACE VIEW `v_review_management_stats` AS
SELECT 
    COUNT(*) as total_reviews,
    SUM(CASE WHEN status = 'visible' THEN 1 ELSE 0 END) as visible_reviews,
    SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden_reviews,
    SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted_reviews,
    SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_approval,
    AVG(rating) as average_rating,
    COUNT(DISTINCT ma_san_pham) as products_with_reviews,
    COUNT(DISTINCT ma_nguoi_dung) as unique_reviewers
FROM product_reviews
WHERE ngay_tao >= DATE_SUB(NOW(), INTERVAL 30 DAY);

CREATE OR REPLACE VIEW `v_review_reports_list` AS
SELECT 
    rr.*,
    pr.ma_san_pham,
    pr.rating,
    pr.comment,
    pr.status as review_status,
    h.tenhanghoa as product_name,
    rr.reporter_id as reporter_name
FROM review_reports rr
JOIN product_reviews pr ON rr.review_id = pr.id
LEFT JOIN hanghoa h ON pr.ma_san_pham = h.idhanghoa
ORDER BY rr.created_at DESC;

CREATE OR REPLACE VIEW `v_support_tickets_list` AS
SELECT 
    st.*,
    st.user_id as user_name,
    (SELECT COUNT(*) FROM support_messages WHERE ticket_id = st.id) as message_count,
    (SELECT COUNT(*) FROM support_messages WHERE ticket_id = st.id AND is_read = 0 AND sender_type = 'user') as unread_count,
    (SELECT created_at FROM support_messages WHERE ticket_id = st.id ORDER BY created_at DESC LIMIT 1) as last_message_at
FROM support_tickets st
ORDER BY st.updated_at DESC;

SELECT 'Setup completed successfully!' as status;
