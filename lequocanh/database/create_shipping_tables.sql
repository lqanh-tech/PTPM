-- =====================================================
-- SHIPPING SYSTEM DATABASE SCHEMA
-- Created: 2025-11-20
-- Purpose: Complete shipping system with GHN integration
-- =====================================================

-- Table 1: Shipping Methods
-- Stores available shipping providers (GHN, GHTK, VNPost, etc)
CREATE TABLE IF NOT EXISTS shipping_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Provider code: GHN, GHTK, VNPOST',
    name VARCHAR(100) NOT NULL COMMENT 'Display name',
    logo_url VARCHAR(255) DEFAULT NULL COMMENT 'Logo image URL',
    api_endpoint VARCHAR(255) DEFAULT NULL COMMENT 'API base URL',
    api_token TEXT DEFAULT NULL COMMENT 'API authentication token',
    shop_id VARCHAR(50) DEFAULT NULL COMMENT 'Shop ID for the provider',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
    priority INT DEFAULT 100 COMMENT 'Display order (lower = higher priority)',
    supports_tracking TINYINT(1) DEFAULT 1 COMMENT '1=Supports tracking, 0=No tracking',
    supports_cod TINYINT(1) DEFAULT 1 COMMENT '1=Supports COD, 0=No COD',
    config_json JSON DEFAULT NULL COMMENT 'Additional configuration',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Shipping methods and providers';

-- Table 2: Shipping Rates (Fallback Pricing)
-- Stores fallback pricing when API is unavailable
CREATE TABLE IF NOT EXISTS shipping_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_id INT NOT NULL COMMENT 'Reference to shipping_methods',
    from_province_id INT DEFAULT NULL COMMENT 'Source province ID (GHN format)',
    from_province_name VARCHAR(100) DEFAULT NULL COMMENT 'Source province name',
    to_province_id INT DEFAULT NULL COMMENT 'Destination province ID',
    to_province_name VARCHAR(100) DEFAULT NULL COMMENT 'Destination province name',
    to_district_id INT DEFAULT NULL COMMENT 'Destination district ID',
    base_fee DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Base shipping fee',
    per_km_fee DECIMAL(12,2) NOT NULL DEFAULT 5000 COMMENT 'Fee per kilometer',
    min_fee DECIMAL(12,2) NOT NULL DEFAULT 15000 COMMENT 'Minimum shipping fee',
    max_fee DECIMAL(12,2) DEFAULT NULL COMMENT 'Maximum shipping fee (NULL = no limit)',
    estimated_days INT DEFAULT 3 COMMENT 'Estimated delivery days',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE,
    INDEX idx_method (method_id),
    INDEX idx_from_province (from_province_id),
    INDEX idx_to_province (to_province_id),
    INDEX idx_to_district (to_district_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Fallback shipping rates when API unavailable';

-- Table 3: User Addresses
-- Stores customer delivery addresses
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'User ID (NULL for guest)',
    session_id VARCHAR(100) DEFAULT NULL COMMENT 'Session ID for guest users',
    full_name VARCHAR(100) NOT NULL COMMENT 'Receiver name',
    phone VARCHAR(20) NOT NULL COMMENT 'Contact phone',
    email VARCHAR(100) DEFAULT NULL COMMENT 'Contact email',
    province_id INT DEFAULT NULL COMMENT 'Province ID (GHN format)',
    province_name VARCHAR(100) NOT NULL COMMENT 'Province name',
    district_id INT DEFAULT NULL COMMENT 'District ID (GHN format)',
    district_name VARCHAR(100) NOT NULL COMMENT 'District name',
    ward_code VARCHAR(20) DEFAULT NULL COMMENT 'Ward code (GHN format)',
    ward_name VARCHAR(100) DEFAULT NULL COMMENT 'Ward name',
    address_detail TEXT NOT NULL COMMENT 'Detailed address',
    address_full TEXT NOT NULL COMMENT 'Full concatenated address',
    latitude DECIMAL(10, 8) DEFAULT NULL COMMENT 'GPS latitude',
    longitude DECIMAL(11, 8) DEFAULT NULL COMMENT 'GPS longitude',
    is_default TINYINT(1) DEFAULT 0 COMMENT '1=Default address, 0=Not default',
    address_type ENUM('home', 'office', 'other') DEFAULT 'home' COMMENT 'Address type',
    note TEXT DEFAULT NULL COMMENT 'Delivery notes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_default (is_default),
    INDEX idx_province (province_id),
    INDEX idx_district (district_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer delivery addresses';

-- Table 4: Order Shipping Tracking
-- Stores shipping information and tracking for orders
CREATE TABLE IF NOT EXISTS order_shipping_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL COMMENT 'Order ID',
    order_code VARCHAR(100) NOT NULL COMMENT 'Order code from checkout',
    shipping_method_id INT DEFAULT NULL COMMENT 'Reference to shipping_methods',
    shipping_method_code VARCHAR(20) DEFAULT 'MANUAL' COMMENT 'GHN, GHTK, or MANUAL',
    tracking_number VARCHAR(100) DEFAULT NULL COMMENT 'Internal tracking number',
    carrier_order_code VARCHAR(100) DEFAULT NULL COMMENT 'Shipping provider order code (from GHN)',
    
    -- Shipping details
    shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Shipping fee charged',
    insurance_fee DECIMAL(12,2) DEFAULT 0 COMMENT 'Insurance fee',
    cod_amount DECIMAL(12,2) DEFAULT 0 COMMENT 'COD collection amount',
    total_fee DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Total shipping + insurance',
    
    -- Address information
    from_address TEXT DEFAULT NULL COMMENT 'Sender address',
    to_name VARCHAR(100) NOT NULL COMMENT 'Receiver name',
    to_phone VARCHAR(20) NOT NULL COMMENT 'Receiver phone',
    to_address TEXT NOT NULL COMMENT 'Full delivery address',
    to_province_id INT DEFAULT NULL,
    to_district_id INT DEFAULT NULL,
    to_ward_code VARCHAR(20) DEFAULT NULL,
    
    -- Distance & Time
    distance_km DECIMAL(10,2) DEFAULT NULL COMMENT 'Distance in kilometers',
    estimated_delivery DATETIME DEFAULT NULL COMMENT 'Estimated delivery time',
    actual_delivery DATETIME DEFAULT NULL COMMENT 'Actual delivery time',
    estimated_days INT DEFAULT NULL COMMENT 'Estimated delivery days',
    
    -- Tracking status
    status VARCHAR(50) DEFAULT 'pending' COMMENT 'Current shipping status',
    current_location TEXT DEFAULT NULL COMMENT 'Current package location',
    
    -- History & Events
    tracking_history JSON DEFAULT NULL COMMENT 'JSON array of tracking events',
    last_sync_at DATETIME DEFAULT NULL COMMENT 'Last sync with shipping provider',
    
    -- Weight & Dimensions
    weight INT DEFAULT 1000 COMMENT 'Package weight in grams',
    length INT DEFAULT NULL COMMENT 'Package length in cm',
    width INT DEFAULT NULL COMMENT 'Package width in cm',
    height INT DEFAULT NULL COMMENT 'Package height in cm',
    
    -- Additional info
    note TEXT DEFAULT NULL COMMENT 'Delivery notes',
    customer_note TEXT DEFAULT NULL COMMENT 'Customer notes',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_order_code (order_code),
    INDEX idx_tracking_number (tracking_number),
    INDEX idx_carrier_code (carrier_order_code),
    INDEX idx_status (status),
    INDEX idx_method (shipping_method_id),
    INDEX idx_estimated_delivery (estimated_delivery)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Order shipping tracking information';

-- Table 5: Shipping Config
-- Stores system shipping configuration
CREATE TABLE IF NOT EXISTS shipping_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE COMMENT 'Configuration key',
    config_value TEXT DEFAULT NULL COMMENT 'Configuration value',
    config_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string' COMMENT 'Value type',
    category VARCHAR(50) DEFAULT 'general' COMMENT 'Config category',
    description TEXT DEFAULT NULL COMMENT 'Configuration description',
    is_encrypted TINYINT(1) DEFAULT 0 COMMENT '1=Encrypted value, 0=Plain text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (config_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Shipping system configuration';

-- Table 6: Vietnam Address Data (for GHN API compatibility)
-- Stores cached province/district/ward data from GHN
CREATE TABLE IF NOT EXISTS vietnam_provinces (
    province_id INT PRIMARY KEY COMMENT 'Province ID from GHN',
    province_name VARCHAR(100) NOT NULL COMMENT 'Province name',
    province_code VARCHAR(20) DEFAULT NULL COMMENT 'Province code',
    name_extensions JSON DEFAULT NULL COMMENT 'Alternative names',
    can_update_cod TINYINT(1) DEFAULT 1,
    status INT DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (province_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Vietnam provinces from GHN API';

CREATE TABLE IF NOT EXISTS vietnam_districts (
    district_id INT PRIMARY KEY COMMENT 'District ID from GHN',
    province_id INT NOT NULL COMMENT 'Parent province ID',
    district_name VARCHAR(100) NOT NULL COMMENT 'District name',
    district_code VARCHAR(20) DEFAULT NULL,
    name_extensions JSON DEFAULT NULL,
    can_update_cod TINYINT(1) DEFAULT 1,
    status INT DEFAULT 1,
    support_type INT DEFAULT 3,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (province_id) REFERENCES vietnam_provinces(province_id) ON DELETE CASCADE,
    INDEX idx_province (province_id),
    INDEX idx_name (district_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Vietnam districts from GHN API';

CREATE TABLE IF NOT EXISTS vietnam_wards (
    ward_code VARCHAR(20) PRIMARY KEY COMMENT 'Ward code from GHN',
    district_id INT NOT NULL COMMENT 'Parent district ID',
    ward_name VARCHAR(100) NOT NULL COMMENT 'Ward name',
    name_extensions JSON DEFAULT NULL,
    can_update_cod TINYINT(1) DEFAULT 1,
    status INT DEFAULT 1,
    support_type INT DEFAULT 3,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES vietnam_districts(district_id) ON DELETE CASCADE,
    INDEX idx_district (district_id),
    INDEX idx_name (ward_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Vietnam wards from GHN API';

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default shipping method (GHN)
INSERT INTO shipping_methods (code, name, logo_url, api_endpoint, is_active, priority, supports_tracking, supports_cod, config_json)
VALUES 
('GHN', 'Giao Hàng Nhanh', 'https://ghn.vn/favicon.ico', 'https://online-gateway.ghn.vn/shiip/public-api/v2', 1, 1, 1, 1, '{"service_type_id": 2}'),
('MANUAL', 'Tự vận chuyển', NULL, NULL, 1, 99, 0, 1, NULL)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert default shipping config
INSERT INTO shipping_config (config_key, config_value, config_type, category, description)
VALUES 
('ghn_api_token', '', 'string', 'ghn', 'GHN API Token from https://khachhang.ghn.vn'),
('ghn_shop_id', '', 'string', 'ghn', 'GHN Shop ID'),
('ghn_from_district_id', '1542', 'number', 'ghn', 'Default sender district ID (TP.HCM - Quận 1)'),
('shop_address', 'Số 1 Đường ABC, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', 'string', 'general', 'Shop default address'),
('shop_latitude', '10.7721', 'number', 'general', 'Shop GPS latitude'),
('shop_longitude', '106.6983', 'number', 'general', 'Shop GPS longitude'),
('fallback_per_km_fee', '5000', 'number', 'fallback', 'Fallback fee per kilometer when API fails'),
('fallback_min_fee', '15000', 'number', 'fallback', 'Minimum shipping fee for fallback'),
('default_package_weight', '1000', 'number', 'general', 'Default package weight in grams'),
('enable_ghn_api', '1', 'boolean', 'ghn', 'Enable GHN API integration (1=Yes, 0=No)'),
('auto_create_shipping_order', '0', 'boolean', 'ghn', 'Auto create shipping order after payment (1=Yes, 0=No)')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Insert default fallback rates for major cities
INSERT INTO shipping_rates (method_id, from_province_name, to_province_name, base_fee, per_km_fee, min_fee, max_fee, estimated_days)
VALUES 
((SELECT id FROM shipping_methods WHERE code='MANUAL' LIMIT 1), 'TP. Hồ Chí Minh', 'TP. Hồ Chí Minh', 15000, 5000, 15000, 50000, 1),
((SELECT id FROM shipping_methods WHERE code='MANUAL' LIMIT 1), 'TP. Hồ Chí Minh', 'Hà Nội', 30000, 5000, 30000, 100000, 3),
((SELECT id FROM shipping_methods WHERE code='MANUAL' LIMIT 1), 'TP. Hồ Chí Minh', 'Đà Nẵng', 25000, 5000, 25000, 80000, 2)
ON DUPLICATE KEY UPDATE base_fee=VALUES(base_fee);

-- =====================================================
-- ADD COMMENTS
-- =====================================================

ALTER TABLE shipping_methods COMMENT = 'Available shipping providers and their configurations';
ALTER TABLE shipping_rates COMMENT = 'Fallback pricing rates when API is unavailable';
ALTER TABLE user_addresses COMMENT = 'Customer delivery addresses with GHN-compatible format';
ALTER TABLE order_shipping_tracking COMMENT = 'Complete shipping tracking information for orders';
ALTER TABLE shipping_config COMMENT = 'System-wide shipping configuration settings';
ALTER TABLE vietnam_provinces COMMENT = 'Cached province data from GHN API';
ALTER TABLE vietnam_districts COMMENT = 'Cached district data from GHN API';
ALTER TABLE vietnam_wards COMMENT = 'Cached ward data from GHN API';
