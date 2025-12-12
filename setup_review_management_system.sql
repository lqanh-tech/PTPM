-- =====================================================
-- HỆ THỐNG QUẢN LÝ BÌNH LUẬN VÀ KHIẾU NẠI
-- =====================================================

-- 1. Thêm cột trạng thái cho bảng product_reviews
ALTER TABLE `product_reviews` 
ADD COLUMN IF NOT EXISTS `status` ENUM('visible', 'hidden', 'deleted') DEFAULT 'visible' COMMENT 'Trạng thái hiển thị',
ADD COLUMN IF NOT EXISTS `admin_note` TEXT NULL COMMENT 'Ghi chú của admin',
ADD COLUMN IF NOT EXISTS `hidden_at` DATETIME NULL COMMENT 'Thời gian ẩn',
ADD COLUMN IF NOT EXISTS `hidden_by` VARCHAR(50) NULL COMMENT 'Admin ẩn',
ADD INDEX `idx_status` (`status`),
ADD INDEX `idx_product_status` (`ma_san_pham`, `status`);

-- 2. Bảng khiếu nại bình luận
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
    FOREIGN KEY (`review_id`) REFERENCES `product_reviews`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`),
    INDEX `idx_review` (`review_id`),
    INDEX `idx_reporter` (`reporter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Khiếu nại bình luận';

-- 3. Bảng tin nhắn hỗ trợ (chat giữa user và admin)
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

-- 4. Bảng tin nhắn trong ticket
CREATE TABLE IF NOT EXISTS `support_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL COMMENT 'ID ticket',
    `sender_id` VARCHAR(50) NOT NULL COMMENT 'ID người gửi',
    `sender_type` ENUM('user', 'admin') NOT NULL COMMENT 'Loại người gửi',
    `message` TEXT NOT NULL COMMENT 'Nội dung tin nhắn',
    `attachments` JSON NULL COMMENT 'File đính kèm',
    `is_read` TINYINT(1) DEFAULT 0 COMMENT 'Đã đọc chưa',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
    INDEX `idx_ticket` (`ticket_id`),
    INDEX `idx_sender` (`sender_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tin nhắn hỗ trợ';

-- 5. View thống kê bình luận cho admin
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

-- 6. View danh sách khiếu nại
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

-- 7. View danh sách ticket hỗ trợ
CREATE OR REPLACE VIEW `v_support_tickets_list` AS
SELECT 
    st.*,
    st.user_id as user_name,
    (SELECT COUNT(*) FROM support_messages WHERE ticket_id = st.id) as message_count,
    (SELECT COUNT(*) FROM support_messages WHERE ticket_id = st.id AND is_read = 0 AND sender_type = 'user') as unread_count,
    (SELECT created_at FROM support_messages WHERE ticket_id = st.id ORDER BY created_at DESC LIMIT 1) as last_message_at
FROM support_tickets st
ORDER BY st.updated_at DESC;

-- 8. Trigger tự động tạo ticket number
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_support_ticket_number`
BEFORE INSERT ON `support_tickets`
FOR EACH ROW
BEGIN
    IF NEW.ticket_number IS NULL OR NEW.ticket_number = '' THEN
        SET NEW.ticket_number = CONCAT('TK', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    END IF;
END//
DELIMITER ;

-- 9. Trigger cập nhật trạng thái ticket khi có tin nhắn mới
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_update_ticket_on_message`
AFTER INSERT ON `support_messages`
FOR EACH ROW
BEGIN
    UPDATE support_tickets 
    SET updated_at = NOW(),
        status = CASE 
            WHEN NEW.sender_type = 'user' AND status = 'waiting_user' THEN 'in_progress'
            WHEN NEW.sender_type = 'admin' AND status = 'open' THEN 'in_progress'
            ELSE status
        END
    WHERE id = NEW.ticket_id;
END//
DELIMITER ;

-- 10. Thêm dữ liệu mẫu cho testing
INSERT INTO `review_reports` (`review_id`, `reporter_id`, `reason`, `description`, `status`) 
SELECT 
    id,
    'test_user',
    'spam',
    'Đây là bình luận spam test',
    'pending'
FROM product_reviews 
LIMIT 1
ON DUPLICATE KEY UPDATE id=id;

-- Hoàn thành
SELECT 'Setup completed successfully!' as status;
