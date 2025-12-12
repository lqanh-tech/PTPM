-- Thêm cột giakhuyenmai vào bảng hanghoa
-- Chạy script này để hỗ trợ chức năng khuyến mãi

-- Kiểm tra và thêm cột giakhuyenmai
ALTER TABLE hanghoa 
ADD COLUMN IF NOT EXISTS giakhuyenmai DECIMAL(15,2) NULL 
COMMENT 'Giá khuyến mãi (NULL = không có KM)' 
AFTER giathamkhao;

-- Kiểm tra kết quả
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'sales_management' 
  AND TABLE_NAME = 'hanghoa' 
  AND COLUMN_NAME IN ('giathamkhao', 'giakhuyenmai');
