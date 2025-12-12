-- Thêm các cột để đánh dấu sản phẩm đặc biệt
ALTER TABLE hanghoa 
ADD COLUMN is_featured TINYINT(1) DEFAULT 0 COMMENT 'Sản phẩm nổi bật',
ADD COLUMN is_new TINYINT(1) DEFAULT 0 COMMENT 'Sản phẩm mới',
ADD COLUMN is_sale TINYINT(1) DEFAULT 0 COMMENT 'Đang khuyến mãi',
ADD COLUMN sale_price DECIMAL(15,2) NULL COMMENT 'Giá khuyến mãi',
ADD COLUMN sale_percent INT NULL COMMENT 'Phần trăm giảm giá',
ADD COLUMN sale_start_date DATETIME NULL COMMENT 'Ngày bắt đầu khuyến mãi',
ADD COLUMN sale_end_date DATETIME NULL COMMENT 'Ngày kết thúc khuyến mãi',
ADD COLUMN view_count INT DEFAULT 0 COMMENT 'Số lượt xem',
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày tạo',
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Ngày cập nhật';

-- Tạo index để tăng tốc query
CREATE INDEX idx_featured ON hanghoa(is_featured);
CREATE INDEX idx_new ON hanghoa(is_new);
CREATE INDEX idx_sale ON hanghoa(is_sale);
CREATE INDEX idx_created_at ON hanghoa(created_at);
CREATE INDEX idx_view_count ON hanghoa(view_count);
