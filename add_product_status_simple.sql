-- =====================================================
-- MIGRATION: THÊM CỘT TRẠNG THÁI VÀO BẢNG HANGHOA
-- Ngày: 2025-11-26
-- Hướng dẫn: Copy toàn bộ nội dung file này và chạy trong phpMyAdmin
-- URL phpMyAdmin: http://localhost:28888
-- =====================================================

USE sales_management;

-- Bước 1: Thêm cột trangthai vào bảng hanghoa
ALTER TABLE hanghoa 
ADD COLUMN trangthai ENUM('dang_ban', 'ngung_ban', 'het_hang') 
DEFAULT 'dang_ban' 
COMMENT 'Trạng thái: dang_ban=Đang bán, ngung_ban=Ngừng bán, het_hang=Hết hàng'
AFTER noibat;

-- Bước 2: Tạo index cho cột trangthai
CREATE INDEX idx_hanghoa_trangthai ON hanghoa(trangthai);

-- Bước 3: Cập nhật tất cả sản phẩm hiện tại về trạng thái "đang bán"
UPDATE hanghoa 
SET trangthai = 'dang_ban' 
WHERE trangthai IS NULL;

-- Bước 4: Tạo bảng lịch sử thay đổi trạng thái
CREATE TABLE IF NOT EXISTS hanghoa_trangthai_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idhanghoa INT NOT NULL,
    trangthai_cu ENUM('dang_ban', 'ngung_ban', 'het_hang'),
    trangthai_moi ENUM('dang_ban', 'ngung_ban', 'het_hang') NOT NULL,
    ly_do VARCHAR(255),
    nguoi_thay_doi INT,
    ngay_thay_doi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idhanghoa) REFERENCES hanghoa(idhanghoa) ON DELETE CASCADE,
    FOREIGN KEY (nguoi_thay_doi) REFERENCES nhanvien(idNhanVien) ON DELETE SET NULL,
    INDEX idx_idhanghoa (idhanghoa),
    INDEX idx_ngay_thay_doi (ngay_thay_doi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bước 5: Kiểm tra kết quả
SELECT 'Migration completed! Checking results...' AS status;

SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'sales_management' 
  AND TABLE_NAME = 'hanghoa' 
  AND COLUMN_NAME = 'trangthai';

-- Thống kê sản phẩm theo trạng thái
SELECT 
    trangthai,
    COUNT(*) as so_luong,
    CASE 
        WHEN trangthai = 'dang_ban' THEN 'Đang bán'
        WHEN trangthai = 'ngung_ban' THEN 'Ngừng bán'
        WHEN trangthai = 'het_hang' THEN 'Hết hàng'
    END as mo_ta
FROM hanghoa 
GROUP BY trangthai;
