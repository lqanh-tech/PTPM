-- =====================================================
-- HỆ THỐNG QUẢN LÝ MÃ COUPON (GIẢM GIÁ)
-- =====================================================

-- Bảng lưu trữ mã coupon
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã coupon (VD: SALE10, FREESHIP)',
    name VARCHAR(255) NOT NULL COMMENT 'Tên mã giảm giá',
    description TEXT COMMENT 'Mô tả chi tiết',
    
    -- Loại giảm giá
    discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent' COMMENT 'percent: giảm %, fixed: giảm tiền cố định',
    discount_value DECIMAL(15,2) NOT NULL COMMENT 'Giá trị giảm (% hoặc VNĐ)',
    
    -- Giới hạn giảm giá
    max_discount DECIMAL(15,2) DEFAULT NULL COMMENT 'Giảm tối đa (chỉ áp dụng cho loại percent)',
    min_order_value DECIMAL(15,2) DEFAULT 0 COMMENT 'Giá trị đơn hàng tối thiểu để áp dụng',
    
    -- Số lượng sử dụng
    usage_limit INT DEFAULT NULL COMMENT 'Số lần sử dụng tối đa (NULL = không giới hạn)',
    usage_count INT DEFAULT 0 COMMENT 'Số lần đã sử dụng',
    usage_per_user INT DEFAULT 1 COMMENT 'Số lần mỗi user được dùng',
    
    -- Thời gian hiệu lực
    start_date DATETIME DEFAULT NULL COMMENT 'Ngày bắt đầu hiệu lực',
    end_date DATETIME DEFAULT NULL COMMENT 'Ngày hết hạn',
    
    -- Trạng thái
    is_active TINYINT(1) DEFAULT 1 COMMENT '1: Hoạt động, 0: Tạm dừng',
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(50) DEFAULT NULL COMMENT 'Admin tạo mã',
    
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lịch sử sử dụng coupon
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL COMMENT 'Username người dùng',
    order_id INT NOT NULL COMMENT 'ID đơn hàng',
    discount_amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền được giảm',
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES don_hang(id) ON DELETE CASCADE,
    
    INDEX idx_coupon (coupon_id),
    INDEX idx_user (user_id),
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm các cột coupon vào bảng don_hang (nếu chưa có)
-- Chạy riêng nếu bảng don_hang đã tồn tại

-- ALTER TABLE don_hang ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL COMMENT 'Mã coupon đã áp dụng';
-- ALTER TABLE don_hang ADD COLUMN coupon_discount DECIMAL(15,2) DEFAULT 0 COMMENT 'Số tiền được giảm từ coupon';

-- =====================================================
-- DỮ LIỆU MẪU
-- =====================================================

-- Mã giảm 10%
INSERT INTO coupons (code, name, description, discount_type, discount_value, max_discount, min_order_value, usage_limit, start_date, end_date, is_active)
VALUES ('SALE10', 'Giảm 10%', 'Giảm 10% cho đơn hàng từ 200.000đ, tối đa 100.000đ', 'percent', 10, 100000, 200000, 100, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1);

-- Mã giảm 50.000đ
INSERT INTO coupons (code, name, description, discount_type, discount_value, max_discount, min_order_value, usage_limit, start_date, end_date, is_active)
VALUES ('GIAM50K', 'Giảm 50.000đ', 'Giảm 50.000đ cho đơn hàng từ 500.000đ', 'fixed', 50000, NULL, 500000, 50, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1);

-- Mã giảm 20% cho khách mới
INSERT INTO coupons (code, name, description, discount_type, discount_value, max_discount, min_order_value, usage_limit, usage_per_user, start_date, end_date, is_active)
VALUES ('NEWUSER20', 'Khách mới giảm 20%', 'Giảm 20% cho khách hàng mới, tối đa 200.000đ', 'percent', 20, 200000, 100000, NULL, 1, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1);

-- Mã freeship (giảm 30.000đ phí ship)
INSERT INTO coupons (code, name, description, discount_type, discount_value, max_discount, min_order_value, usage_limit, start_date, end_date, is_active)
VALUES ('FREESHIP', 'Miễn phí vận chuyển', 'Giảm 30.000đ phí vận chuyển cho đơn từ 300.000đ', 'fixed', 30000, NULL, 300000, 200, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), 1);
