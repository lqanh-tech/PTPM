-- =====================================================
-- MOCK DATA FOR SHIPPING TESTING (NO GHN API NEEDED)
-- Insert static Vietnam address data for testing
-- =====================================================

-- Disable GHN API (use fallback only)
UPDATE shipping_config SET config_value = '0' WHERE config_key = 'enable_ghn_api';

-- =====================================================
-- MOCK VIETNAM PROVINCES (Major Cities)
-- =====================================================

INSERT INTO vietnam_provinces (province_id, province_name, province_code, can_update_cod, status) VALUES
(1, 'Hà Nội', 'HN', 1, 1),
(2, 'Hồ Chí Minh', 'SG', 1, 1),
(3, 'Đà Nẵng', 'DN', 1, 1),
(4, 'Hải Phòng', 'HP', 1, 1),
(5, 'Cần Thơ', 'CT', 1, 1),
(6, 'An Giang', 'AG', 1, 1),
(7, 'Bà Rịa - Vũng Tàu', 'VT', 1, 1),
(8, 'Bắc Giang', 'BG', 1, 1),
(9, 'Bắc Kạn', 'BK', 1, 1),
(10, 'Bạc Liêu', 'BL', 1, 1),
(11, 'Bắc Ninh', 'BN', 1, 1),
(12, 'Bến Tre', 'BT', 1, 1),
(13, 'Bình Định', 'BD', 1, 1),
(14, 'Bình Dương', 'BDG', 1, 1),
(15, 'Bình Phước', 'BP', 1, 1),
(16, 'Bình Thuận', 'BTH', 1, 1),
(17, 'Cà Mau', 'CM', 1, 1),
(18, 'Cao Bằng', 'CB', 1, 1),
(19, 'Đắk Lắk', 'DL', 1, 1),
(20, 'Đắk Nông', 'DNO', 1, 1),
(21, 'Điện Biên', 'DB', 1, 1),
(22, 'Đồng Nai', 'DNI', 1, 1),
(23, 'Đồng Tháp', 'DT', 1, 1),
(24, 'Gia Lai', 'GL', 1, 1),
(25, 'Hà Giang', 'HG', 1, 1),
(26, 'Hà Nam', 'HNA', 1, 1),
(27, 'Hà Tĩnh', 'HT', 1, 1),
(28, 'Hải Dương', 'HD', 1, 1),
(29, 'Hậu Giang', 'HGI', 1, 1),
(30, 'Hòa Bình', 'HB', 1, 1),
(31, 'Hưng Yên', 'HY', 1, 1),
(32, 'Khánh Hòa', 'KH', 1, 1),
(33, 'Kiên Giang', 'KG', 1, 1),
(34, 'Kon Tum', 'KT', 1, 1),
(35, 'Lai Châu', 'LC', 1, 1),
(36, 'Lâm Đồng', 'LD', 1, 1),
(37, 'Lạng Sơn', 'LS', 1, 1),
(38, 'Lào Cai', 'LCA', 1, 1),
(39, 'Long An', 'LA', 1, 1),
(40, 'Nam Định', 'ND', 1, 1),
(41, 'Nghệ An', 'NA', 1, 1),
(42, 'Ninh Bình', 'NB', 1, 1),
(43, 'Ninh Thuận', 'NT', 1, 1),
(44, 'Phú Thọ', 'PT', 1, 1),
(45, 'Phú Yên', 'PY', 1, 1),
(46, 'Quảng Bình', 'QB', 1, 1),
(47, 'Quảng Nam', 'QNA', 1, 1),
(48, 'Quảng Ngãi', 'QNG', 1, 1),
(49, 'Quảng Ninh', 'QNI', 1, 1),
(50, 'Quảng Trị', 'QT', 1, 1),
(51, 'Sóc Trăng', 'ST', 1, 1),
(52, 'Sơn La', 'SL', 1, 1),
(53, 'Tây Ninh', 'TN', 1, 1),
(54, 'Thái Bình', 'TB', 1, 1),
(55, 'Thái Nguyên', 'TNG', 1, 1),
(56, 'Thanh Hóa', 'TH', 1, 1),
(57, 'Thừa Thiên Huế', 'TTH', 1, 1),
(58, 'Tiền Giang', 'TG', 1, 1),
(59, 'Trà Vinh', 'TV', 1, 1),
(60, 'Tuyên Quang', 'TQ', 1, 1),
(61, 'Vĩnh Long', 'VL', 1, 1),
(62, 'Vĩnh Phúc', 'VP', 1, 1),
(63, 'Yên Bái', 'YB', 1, 1)
ON DUPLICATE KEY UPDATE province_name=VALUES(province_name);

-- =====================================================
-- MOCK HO CHI MINH DISTRICTS
-- =====================================================

INSERT INTO vietnam_districts (district_id, province_id, district_name, district_code, can_update_cod, status, support_type) VALUES
(101, 2, 'Quận 1', 'Q1', 1, 1, 3),
(102, 2, 'Quận 2', 'Q2', 1, 1, 3),
(103, 2, 'Quận 3', 'Q3', 1, 1, 3),
(104, 2, 'Quận 4', 'Q4', 1, 1, 3),
(105, 2, 'Quận 5', 'Q5', 1, 1, 3),
(106, 2, 'Quận 6', 'Q6', 1, 1, 3),
(107, 2, 'Quận 7', 'Q7', 1, 1, 3),
(108, 2, 'Quận 8', 'Q8', 1, 1, 3),
(109, 2, 'Quận 9', 'Q9', 1, 1, 3),
(110, 2, 'Quận 10', 'Q10', 1, 1, 3),
(111, 2, 'Quận 11', 'Q11', 1, 1, 3),
(112, 2, 'Quận 12', 'Q12', 1, 1, 3),
(113, 2, 'Quận Bình Thạnh', 'QBT', 1, 1, 3),
(114, 2, 'Quận Tân Bình', 'QTB', 1, 1, 3),
(115, 2, 'Quận Tân Phú', 'QTP', 1, 1, 3),
(116, 2, 'Quận Phú Nhuận', 'QPN', 1, 1, 3),
(117, 2, 'Quận Gò Vấp', 'QGV', 1, 1, 3),
(118, 2, 'Quận Bình Tân', 'QBTA', 1, 1, 3),
(119, 2, 'Huyện Củ Chi', 'HCC', 1, 1, 3),
(120, 2, 'Huyện Hóc Môn', 'HHM', 1, 1, 3),
(121, 2, 'Huyện Bình Chánh', 'HBC', 1, 1, 3),
(122, 2, 'Huyện Nhà Bè', 'HNB', 1, 1, 3),
(123, 2, 'Huyện Cần Giờ', 'HCG', 1, 1, 3),
(124, 2, 'Thành phố Thủ Đức', 'TD', 1, 1, 3)
ON DUPLICATE KEY UPDATE district_name=VALUES(district_name);

-- =====================================================
-- MOCK HA NOI DISTRICTS
-- =====================================================

INSERT INTO vietnam_districts (district_id, province_id, district_name, district_code, can_update_cod, status, support_type) VALUES
(201, 1, 'Quận Ba Đình', 'QBD', 1, 1, 3),
(202, 1, 'Quận Hoàn Kiếm', 'QHK', 1, 1, 3),
(203, 1, 'Quận Tây Hồ', 'QTH', 1, 1, 3),
(204, 1, 'Quận Long Biên', 'QLB', 1, 1, 3),
(205, 1, 'Quận Cầu Giấy', 'QCG', 1, 1, 3),
(206, 1, 'Quận Đống Đa', 'QDD', 1, 1, 3),
(207, 1, 'Quận Hai Bà Trưng', 'QHBT', 1, 1, 3),
(208, 1, 'Quận Hoàng Mai', 'QHM', 1, 1, 3),
(209, 1, 'Quận Thanh Xuân', 'QTX', 1, 1, 3),
(210, 1, 'Huyện Sóc Sơn', 'HSS', 1, 1, 3),
(211, 1, 'Huyện Đông Anh', 'HDA', 1, 1, 3),
(212, 1, 'Huyện Gia Lâm', 'HGL', 1, 1, 3)
ON DUPLICATE KEY UPDATE district_name=VALUES(district_name);

-- =====================================================
-- MOCK DA NANG DISTRICTS
-- =====================================================

INSERT INTO vietnam_districts (district_id, province_id, district_name, district_code, can_update_cod, status, support_type) VALUES
(301, 3, 'Quận Hải Châu', 'QHC', 1, 1, 3),
(302, 3, 'Quận Thanh Khê', 'QTK', 1, 1, 3),
(303, 3, 'Quận Sơn Trà', 'QST', 1, 1, 3),
(304, 3, 'Quận Ngũ Hành Sơn', 'QNHS', 1, 1, 3),
(305, 3, 'Quận Liên Chiểu', 'QLC', 1, 1, 3),
(306, 3, 'Quận Cẩm Lệ', 'QCL', 1, 1, 3),
(307, 3, 'Huyện Hòa Vang', 'HHV', 1, 1, 3)
ON DUPLICATE KEY UPDATE district_name=VALUES(district_name);

-- =====================================================
-- MOCK WARDS FOR QUAN 1, HCM
-- =====================================================

INSERT INTO vietnam_wards (ward_code, district_id, ward_name, can_update_cod, status, support_type) VALUES
('10101', 101, 'Phường Bến Nghé', 1, 1, 3),
('10102', 101, 'Phường Bến Thành', 1, 1, 3),
('10103', 101, 'Phường Nguyễn Thái Bình', 1, 1, 3),
('10104', 101, 'Phường Phạm Ngũ Lão', 1, 1, 3),
('10105', 101, 'Phường Cầu Ông Lãnh', 1, 1, 3),
('10106', 101, 'Phường Cô Giang', 1, 1, 3),
('10107', 101, 'Phường Nguyễn Cư Trinh', 1, 1, 3),
('10108', 101, 'Phường Cầu Kho', 1, 1, 3),
('10109', 101, 'Phường Đa Kao', 1, 1, 3),
('10110', 101, 'Phường Tân Định', 1, 1, 3)
ON DUPLICATE KEY UPDATE ward_name=VALUES(ward_name);

-- =====================================================
-- MOCK WARDS FOR QUAN 3, HCM
-- =====================================================

INSERT INTO vietnam_wards (ward_code, district_id, ward_name, can_update_cod, status, support_type) VALUES
('10301', 103, 'Phường 1', 1, 1, 3),
('10302', 103, 'Phường 2', 1, 1, 3),
('10303', 103, 'Phường 3', 1, 1, 3),
('10304', 103, 'Phường 4', 1, 1, 3),
('10305', 103, 'Phường 5', 1, 1, 3),
('10306', 103, 'Phường 6', 1, 1, 3),
('10307', 103, 'Phường 7', 1, 1, 3),
('10308', 103, 'Phường 8', 1, 1, 3),
('10309', 103, 'Phường 9', 1, 1, 3),
('10310', 103, 'Phường 10', 1, 1, 3),
('10311', 103, 'Phường 11', 1, 1, 3),
('10312', 103, 'Phường 12', 1, 1, 3),
('10313', 103, 'Phường 13', 1, 1, 3),
('10314', 103, 'Phường 14', 1, 1, 3)
ON DUPLICATE KEY UPDATE ward_name=VALUES(ward_name);

-- =====================================================
-- MOCK WARDS FOR HOAN KIEM, HA NOI
-- =====================================================

INSERT INTO vietnam_wards (ward_code, district_id, ward_name, can_update_cod, status, support_type) VALUES
('20201', 202, 'Phường Hàng Bạc', 1, 1, 3),
('20202', 202, 'Phường Hàng Bồ', 1, 1, 3),
('20203', 202, 'Phường Hàng Buồm', 1, 1, 3),
('20204', 202, 'Phường Hàng Đào', 1, 1, 3),
('20205', 202, 'Phường Hàng Gai', 1, 1, 3),
('20206', 202, 'Phường Cửa Đông', 1, 1, 3),
('20207', 202, 'Phường Lý Thái Tổ', 1, 1, 3),
('20208', 202, 'Phường Phan Chu Trinh', 1, 1, 3),
('20209', 202, 'Phường Tràng Tiền', 1, 1, 3),
('20210', 202, 'Phường Trần Hưng Đạo', 1, 1, 3)
ON DUPLICATE KEY UPDATE ward_name=VALUES(ward_name);

-- =====================================================
-- UPDATE FALLBACK RATES WITH MORE DATA
-- =====================================================

INSERT INTO shipping_rates (method_id, from_province_name, to_province_name, base_fee, per_km_fee, min_fee, max_fee, estimated_days, is_active)
SELECT 
    (SELECT id FROM shipping_methods WHERE code='MANUAL' LIMIT 1),
    'TP. Hồ Chí Minh',
    'TP. Hồ Chí Minh',
    15000,
    5000,
    15000,
    50000,
    1,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM shipping_rates 
    WHERE from_province_name = 'TP. Hồ Chí Minh' 
    AND to_province_name = 'TP. Hồ Chí Minh'
);

INSERT INTO shipping_rates (method_id, from_province_name, to_province_name, base_fee, per_km_fee, min_fee, max_fee, estimated_days, is_active)
SELECT 
    (SELECT id FROM shipping_methods WHERE code='MANUAL' LIMIT 1),
    'TP. Hồ Chí Minh',
    'Hà Nội',
    35000,
    5000,
    35000,
    120000,
    3,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM shipping_rates 
    WHERE from_province_name = 'TP. Hồ Chí Minh' 
    AND to_province_name = 'Hà Nội'
);

INSERT INTO shipping_rates (method_id, from_province_name, to_province_name, base_fee, per_km_fee, min_fee, max_fee, estimated_days, is_active)
SELECT 
    (SELECT id FROM shipping_methods WHERE code='MANUAL' LIMIT 1),
    'TP. Hồ Chí Minh',
    'Đà Nẵng',
    28000,
    5000,
    28000,
    90000,
    2,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM shipping_rates 
    WHERE from_province_name = 'TP. Hồ Chí Minh' 
    AND to_province_name = 'Đà Nẵng'
);

-- Add more provinces
INSERT INTO shipping_rates (method_id, from_province_name, to_province_name, base_fee, per_km_fee, min_fee, max_fee, estimated_days, is_active)
SELECT 
    (SELECT id FROM shipping_methods WHERE code='MANUAL' LIMIT 1),
    'TP. Hồ Chí Minh',
    'Bình Dương',
    18000,
    5000,
    18000,
    40000,
    1,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM shipping_rates 
    WHERE from_province_name = 'TP. Hồ Chí Minh' 
    AND to_province_name = 'Bình Dương'
);

INSERT INTO shipping_rates (method_id, from_province_name, to_province_name, base_fee, per_km_fee, min_fee, max_fee, estimated_days, is_active)
SELECT 
    (SELECT id FROM shipping_methods WHERE code='MANUAL' LIMIT 1),
    'TP. Hồ Chí Minh',
    'Đồng Nai',
    20000,
    5000,
    20000,
    45000,
    1,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM shipping_rates 
    WHERE from_province_name = 'TP. Hồ Chí Minh' 
    AND to_province_name = 'Đồng Nai'
);

INSERT INTO shipping_rates (method_id, from_province_name, to_province_name, base_fee, per_km_fee, min_fee, max_fee, estimated_days, is_active)
SELECT 
    (SELECT id FROM shipping_methods WHERE code='MANUAL' LIMIT 1),
    'TP. Hồ Chí Minh',
    'Cần Thơ',
    25000,
    5000,
    25000,
    70000,
    2,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM shipping_rates 
    WHERE from_province_name = 'TP. Hồ Chí Minh' 
    AND to_province_name = 'Cần Thơ'
);

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Verify data
SELECT 'Provinces:', COUNT(*) FROM vietnam_provinces;
SELECT 'Districts:', COUNT(*) FROM vietnam_districts;
SELECT 'Wards:', COUNT(*) FROM vietnam_wards;
SELECT 'Shipping Rates:', COUNT(*) FROM shipping_rates;

-- Show sample data
SELECT * FROM vietnam_provinces LIMIT 10;
SELECT * FROM vietnam_districts WHERE province_id = 2 LIMIT 10;
SELECT * FROM vietnam_wards WHERE district_id = 101 LIMIT 10;
SELECT * FROM shipping_config WHERE config_key LIKE '%ghn%' OR config_key LIKE '%fallback%';
