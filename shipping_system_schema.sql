-- =====================================================
-- HỆ THỐNG QUẢN LÝ VẬN CHUYỂN & KHU VỰC
-- Phiên bản: 1.0
-- Ngày: 01/12/2025
-- Mô tả: Hoàn toàn miễn phí, có thể mở rộng
-- =====================================================

-- 1. BẢNG TỈNH/THÀNH PHỐ
CREATE TABLE IF NOT EXISTS provinces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Mã tỉnh/thành',
    name VARCHAR(100) NOT NULL COMMENT 'Tên tiếng Việt',
    name_en VARCHAR(100) COMMENT 'Tên tiếng Anh',
    region VARCHAR(50) COMMENT 'Miền: Bắc/Trung/Nam',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1: Hoạt động, 0: Không hoạt động',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách tỉnh/thành phố Việt Nam';

-- 2. BẢNG QUẬN/HUYỆN
CREATE TABLE IF NOT EXISTS districts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    province_id INT NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Mã quận/huyện',
    name VARCHAR(100) NOT NULL COMMENT 'Tên tiếng Việt',
    name_en VARCHAR(100) COMMENT 'Tên tiếng Anh',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
    INDEX idx_province (province_id),
    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách quận/huyện';

-- 3. BẢNG PHƯỜNG/XÃ
CREATE TABLE IF NOT EXISTS wards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    district_id INT NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Mã phường/xã',
    name VARCHAR(100) NOT NULL COMMENT 'Tên tiếng Việt',
    name_en VARCHAR(100) COMMENT 'Tên tiếng Anh',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    INDEX idx_district (district_id),
    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách phường/xã';

-- 4. BẢNG KHU VỰC GIAO HÀNG (Shipping Zones)
CREATE TABLE IF NOT EXISTS shipping_zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL COMMENT 'Tên khu vực',
    province_id INT,
    district_id INT,
    is_supported TINYINT(1) DEFAULT 1 COMMENT '1: Hỗ trợ giao hàng, 0: Không hỗ trợ',
    delivery_time_min INT DEFAULT 24 COMMENT 'Thời gian giao tối thiểu (giờ)',
    delivery_time_max INT DEFAULT 72 COMMENT 'Thời gian giao tối đa (giờ)',
    note TEXT COMMENT 'Ghi chú',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    INDEX idx_province (province_id),
    INDEX idx_district (district_id),
    INDEX idx_supported (is_supported)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Khu vực giao hàng được hỗ trợ';

-- 5. BẢNG PHƯƠNG THỨC VẬN CHUYỂN
CREATE TABLE IF NOT EXISTS shipping_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã phương thức: standard, express, economy',
    name VARCHAR(100) NOT NULL COMMENT 'Tên hiển thị',
    description TEXT COMMENT 'Mô tả chi tiết',
    delivery_time VARCHAR(100) COMMENT 'Thời gian giao hàng ước tính',
    price_multiplier DECIMAL(5,2) DEFAULT 1.0 COMMENT 'Hệ số nhân giá (1.0 = chuẩn, 1.5 = nhanh)',
    icon VARCHAR(100) COMMENT 'Icon/logo',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0 COMMENT 'Thứ tự hiển thị',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phương thức vận chuyển';

-- 6. BẢNG CẤU HÌNH PHÍ VẬN CHUYỂN
CREATE TABLE IF NOT EXISTS shipping_fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL COMMENT 'Tên cấu hình',
    province_id INT COMMENT 'Áp dụng cho tỉnh (NULL = tất cả)',
    district_id INT COMMENT 'Áp dụng cho quận (NULL = tất cả)',
    shipping_method_id INT COMMENT 'Phương thức vận chuyển',
    
    -- Phí cơ bản
    base_fee DECIMAL(15,2) DEFAULT 0 COMMENT 'Phí cơ bản (VNĐ)',
    
    -- Phí theo trọng lượng
    weight_from DECIMAL(10,2) DEFAULT 0 COMMENT 'Từ kg',
    weight_to DECIMAL(10,2) COMMENT 'Đến kg (NULL = không giới hạn)',
    fee_per_kg DECIMAL(15,2) DEFAULT 0 COMMENT 'Phí mỗi kg (VNĐ)',
    
    -- Phí theo giá trị đơn hàng
    order_value_from DECIMAL(15,2) DEFAULT 0 COMMENT 'Từ giá trị đơn hàng',
    order_value_to DECIMAL(15,2) COMMENT 'Đến giá trị (NULL = không giới hạn)',
    
    -- Miễn phí vận chuyển
    min_order_free_ship DECIMAL(15,2) COMMENT 'Đơn hàng tối thiểu miễn phí ship',
    
    -- Phí theo khoảng cách (tùy chọn, để mở rộng sau)
    distance_from INT COMMENT 'Từ km',
    distance_to INT COMMENT 'Đến km',
    fee_per_km DECIMAL(15,2) DEFAULT 0 COMMENT 'Phí mỗi km',
    
    priority INT DEFAULT 0 COMMENT 'Độ ưu tiên (số cao hơn = ưu tiên hơn)',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE,
    
    INDEX idx_province (province_id),
    INDEX idx_district (district_id),
    INDEX idx_method (shipping_method_id),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấu hình phí vận chuyển';

-- 7. BẢNG LỊCH SỬ VẬN CHUYỂN (Tracking)
CREATE TABLE IF NOT EXISTS shipment_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    tracking_code VARCHAR(100) COMMENT 'Mã vận đơn từ đơn vị vận chuyển',
    carrier VARCHAR(50) COMMENT 'Đơn vị vận chuyển: internal, ghn, ghtk, viettelpost',
    status VARCHAR(50) NOT NULL COMMENT 'Trạng thái: pending, picking, shipping, delivered, failed, returned',
    status_description TEXT COMMENT 'Mô tả chi tiết',
    location VARCHAR(255) COMMENT 'Vị trí hiện tại',
    note TEXT COMMENT 'Ghi chú',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES don_hang(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_tracking (tracking_code),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử theo dõi vận chuyển';

-- 8. CẬP NHẬT BẢNG ĐON_HANG (Thêm các cột mới nếu chưa có)
ALTER TABLE don_hang 
ADD COLUMN IF NOT EXISTS province_id INT COMMENT 'ID tỉnh/thành' AFTER dia_chi_giao_hang,
ADD COLUMN IF NOT EXISTS district_id INT COMMENT 'ID quận/huyện' AFTER province_id,
ADD COLUMN IF NOT EXISTS ward_id INT COMMENT 'ID phường/xã' AFTER district_id,
ADD COLUMN IF NOT EXISTS shipping_method_id INT COMMENT 'ID phương thức vận chuyển' AFTER ward_id,
ADD COLUMN IF NOT EXISTS shipping_weight DECIMAL(10,2) COMMENT 'Trọng lượng (kg)' AFTER shipping_method_id,
ADD COLUMN IF NOT EXISTS tracking_code VARCHAR(100) COMMENT 'Mã vận đơn' AFTER shipping_weight,
ADD COLUMN IF NOT EXISTS carrier VARCHAR(50) COMMENT 'Đơn vị vận chuyển' AFTER tracking_code,
ADD COLUMN IF NOT EXISTS shipping_status VARCHAR(50) DEFAULT 'pending' COMMENT 'Trạng thái vận chuyển' AFTER carrier;

-- Thêm foreign keys cho don_hang
ALTER TABLE don_hang 
ADD CONSTRAINT fk_order_province FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_order_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_order_ward FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_order_shipping_method FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE SET NULL;

-- 9. THÊM DỮ LIỆU MẪU - PHƯƠNG THỨC VẬN CHUYỂN
INSERT INTO shipping_methods (code, name, description, delivery_time, price_multiplier, sort_order) VALUES
('standard', 'Giao hàng tiêu chuẩn', 'Giao hàng trong 3-5 ngày làm việc', '3-5 ngày', 1.0, 1),
('express', 'Giao hàng nhanh', 'Giao hàng trong 1-2 ngày làm việc', '1-2 ngày', 1.5, 2),
('economy', 'Giao hàng tiết kiệm', 'Giao hàng trong 5-7 ngày làm việc', '5-7 ngày', 0.8, 3)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 10. THÊM DỮ LIỆU MẪU - TỈNH/THÀNH PHỐ (63 tỉnh thành Việt Nam)
INSERT INTO provinces (code, name, name_en, region) VALUES
('HN', 'Hà Nội', 'Hanoi', 'Bắc'),
('HCM', 'Hồ Chí Minh', 'Ho Chi Minh', 'Nam'),
('DN', 'Đà Nẵng', 'Da Nang', 'Trung'),
('HP', 'Hải Phòng', 'Hai Phong', 'Bắc'),
('CT', 'Cần Thơ', 'Can Tho', 'Nam'),
('AG', 'An Giang', 'An Giang', 'Nam'),
('BR', 'Bà Rịa - Vũng Tàu', 'Ba Ria - Vung Tau', 'Nam'),
('BG', 'Bắc Giang', 'Bac Giang', 'Bắc'),
('BK', 'Bắc Kạn', 'Bac Kan', 'Bắc'),
('BL', 'Bạc Liêu', 'Bac Lieu', 'Nam'),
('BN', 'Bắc Ninh', 'Bac Ninh', 'Bắc'),
('BTH', 'Bến Tre', 'Ben Tre', 'Nam'),
('BD', 'Bình Định', 'Binh Dinh', 'Trung'),
('BDG', 'Bình Dương', 'Binh Duong', 'Nam'),
('BP', 'Bình Phước', 'Binh Phuoc', 'Nam'),
('BTN', 'Bình Thuận', 'Binh Thuan', 'Nam'),
('CM', 'Cà Mau', 'Ca Mau', 'Nam'),
('CB', 'Cao Bằng', 'Cao Bang', 'Bắc'),
('DL', 'Đắk Lắk', 'Dak Lak', 'Trung'),
('DNO', 'Đắk Nông', 'Dak Nong', 'Trung'),
('DB', 'Điện Biên', 'Dien Bien', 'Bắc'),
('DN2', 'Đồng Nai', 'Dong Nai', 'Nam'),
('DT', 'Đồng Tháp', 'Dong Thap', 'Nam'),
('GL', 'Gia Lai', 'Gia Lai', 'Trung'),
('HG', 'Hà Giang', 'Ha Giang', 'Bắc'),
('HNM', 'Hà Nam', 'Ha Nam', 'Bắc'),
('HT', 'Hà Tĩnh', 'Ha Tinh', 'Trung'),
('HD', 'Hải Dương', 'Hai Duong', 'Bắc'),
('HU', 'Hậu Giang', 'Hau Giang', 'Nam'),
('HB', 'Hòa Bình', 'Hoa Binh', 'Bắc'),
('HY', 'Hưng Yên', 'Hung Yen', 'Bắc'),
('KH', 'Khánh Hòa', 'Khanh Hoa', 'Trung'),
('KG', 'Kiên Giang', 'Kien Giang', 'Nam'),
('KT', 'Kon Tum', 'Kon Tum', 'Trung'),
('LC', 'Lai Châu', 'Lai Chau', 'Bắc'),
('LD', 'Lâm Đồng', 'Lam Dong', 'Trung'),
('LS', 'Lạng Sơn', 'Lang Son', 'Bắc'),
('LC2', 'Lào Cai', 'Lao Cai', 'Bắc'),
('LA', 'Long An', 'Long An', 'Nam'),
('ND', 'Nam Định', 'Nam Dinh', 'Bắc'),
('NA', 'Nghệ An', 'Nghe An', 'Trung'),
('NB', 'Ninh Bình', 'Ninh Binh', 'Bắc'),
('NT', 'Ninh Thuận', 'Ninh Thuan', 'Trung'),
('PT', 'Phú Thọ', 'Phu Tho', 'Bắc'),
('PY', 'Phú Yên', 'Phu Yen', 'Trung'),
('QB', 'Quảng Bình', 'Quang Binh', 'Trung'),
('QN', 'Quảng Nam', 'Quang Nam', 'Trung'),
('QNG', 'Quảng Ngãi', 'Quang Ngai', 'Trung'),
('QNI', 'Quảng Ninh', 'Quang Ninh', 'Bắc'),
('QT', 'Quảng Trị', 'Quang Tri', 'Trung'),
('ST', 'Sóc Trăng', 'Soc Trang', 'Nam'),
('SL', 'Sơn La', 'Son La', 'Bắc'),
('TN', 'Tây Ninh', 'Tay Ninh', 'Nam'),
('TB', 'Thái Bình', 'Thai Binh', 'Bắc'),
('TNG', 'Thái Nguyên', 'Thai Nguyen', 'Bắc'),
('TH', 'Thanh Hóa', 'Thanh Hoa', 'Trung'),
('TTH', 'Thừa Thiên Huế', 'Thua Thien Hue', 'Trung'),
('TG', 'Tiền Giang', 'Tien Giang', 'Nam'),
('TV', 'Trà Vinh', 'Tra Vinh', 'Nam'),
('TQ', 'Tuyên Quang', 'Tuyen Quang', 'Bắc'),
('VL', 'Vĩnh Long', 'Vinh Long', 'Nam'),
('VP', 'Vĩnh Phúc', 'Vinh Phuc', 'Bắc'),
('YB', 'Yên Bái', 'Yen Bai', 'Bắc')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 11. THÊM CẤU HÌNH PHÍ MẶC ĐỊNH
INSERT INTO shipping_fees (name, base_fee, weight_from, weight_to, fee_per_kg, min_order_free_ship, shipping_method_id, priority) VALUES
('Phí cơ bản nội thành', 30000, 0, 1, 0, 500000, 1, 10),
('Phí cơ bản ngoại thành', 50000, 0, 1, 0, 1000000, 1, 5),
('Phí theo trọng lượng 1-5kg', 30000, 1, 5, 10000, NULL, 1, 8),
('Phí theo trọng lượng >5kg', 30000, 5, NULL, 8000, NULL, 1, 7)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 12. TẠO VIEW ĐỂ QUERY DỄ DÀNG
CREATE OR REPLACE VIEW v_shipping_zones_detail AS
SELECT 
    sz.id,
    sz.name AS zone_name,
    p.name AS province_name,
    d.name AS district_name,
    sz.is_supported,
    sz.delivery_time_min,
    sz.delivery_time_max,
    sz.note,
    sz.is_active
FROM shipping_zones sz
LEFT JOIN provinces p ON sz.province_id = p.id
LEFT JOIN districts d ON sz.district_id = d.id
WHERE sz.is_active = 1;

CREATE OR REPLACE VIEW v_shipping_fees_detail AS
SELECT 
    sf.id,
    sf.name,
    p.name AS province_name,
    d.name AS district_name,
    sm.name AS shipping_method_name,
    sf.base_fee,
    sf.fee_per_kg,
    sf.min_order_free_ship,
    sf.priority,
    sf.is_active
FROM shipping_fees sf
LEFT JOIN provinces p ON sf.province_id = p.id
LEFT JOIN districts d ON sf.district_id = d.id
LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
WHERE sf.is_active = 1
ORDER BY sf.priority DESC;

-- KẾT THÚC SCHEMA
