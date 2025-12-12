-- Tạo bảng provinces
CREATE TABLE IF NOT EXISTS provinces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    region VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng districts
CREATE TABLE IF NOT EXISTS districts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    province_id INT NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
    INDEX idx_province (province_id),
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng wards
CREATE TABLE IF NOT EXISTS wards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    district_id INT NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    INDEX idx_district (district_id),
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng shipping_methods
CREATE TABLE IF NOT EXISTS shipping_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    delivery_time VARCHAR(100),
    price_multiplier DECIMAL(5,2) DEFAULT 1.0,
    icon VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng shipping_fees
CREATE TABLE IF NOT EXISTS shipping_fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    province_id INT,
    district_id INT,
    shipping_method_id INT,
    base_fee DECIMAL(12,2) DEFAULT 0,
    weight_fee DECIMAL(12,2) DEFAULT 0,
    min_weight DECIMAL(10,2) DEFAULT 0,
    max_weight DECIMAL(10,2) DEFAULT 100,
    free_shipping_threshold DECIMAL(12,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE SET NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE SET NULL,
    INDEX idx_province (province_id),
    INDEX idx_method (shipping_method_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert dữ liệu mẫu provinces
INSERT INTO provinces (code, name, name_en, region) VALUES
('HN', 'Hà Nội', 'Hanoi', 'Bắc'),
('HCM', 'Hồ Chí Minh', 'Ho Chi Minh', 'Nam'),
('DN', 'Đà Nẵng', 'Da Nang', 'Trung'),
('HP', 'Hải Phòng', 'Hai Phong', 'Bắc'),
('CT', 'Cần Thơ', 'Can Tho', 'Nam')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert dữ liệu mẫu districts
INSERT INTO districts (province_id, code, name, name_en) VALUES
(1, 'HN-HK', 'Hoàn Kiếm', 'Hoan Kiem'),
(1, 'HN-BD', 'Ba Đình', 'Ba Dinh'),
(2, 'HCM-Q1', 'Quận 1', 'District 1'),
(2, 'HCM-Q3', 'Quận 3', 'District 3'),
(3, 'DN-HC', 'Hải Châu', 'Hai Chau')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert dữ liệu mẫu shipping_methods
INSERT INTO shipping_methods (code, name, description, delivery_time, price_multiplier, sort_order) VALUES
('standard', 'Giao hàng tiêu chuẩn', 'Giao hàng trong 3-5 ngày', '3-5 ngày', 1.0, 1),
('express', 'Giao hàng nhanh', 'Giao hàng trong 1-2 ngày', '1-2 ngày', 1.5, 2),
('economy', 'Giao hàng tiết kiệm', 'Giao hàng trong 5-7 ngày', '5-7 ngày', 0.8, 3)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert dữ liệu mẫu shipping_fees
INSERT INTO shipping_fees (name, province_id, shipping_method_id, base_fee, weight_fee, free_shipping_threshold) VALUES
('Phí giao HN - Tiêu chuẩn', 1, 1, 25000, 5000, 500000),
('Phí giao HN - Nhanh', 1, 2, 40000, 8000, 1000000),
('Phí giao HCM - Tiêu chuẩn', 2, 1, 25000, 5000, 500000),
('Phí giao HCM - Nhanh', 2, 2, 40000, 8000, 1000000),
('Phí giao tỉnh - Tiêu chuẩn', NULL, 1, 35000, 7000, 800000);
