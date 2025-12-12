-- Thêm các cột cần thiết cho chức năng đổi/trả hàng
-- Chạy file này trong phpMyAdmin hoặc MySQL client

USE sales_management;

-- 1. Thêm cột trạng thái đổi/trả
ALTER TABLE don_hang 
ADD COLUMN IF NOT EXISTS trang_thai_doi_tra ENUM('none', 'requested', 'approved', 'rejected') DEFAULT 'none' COMMENT 'Trạng thái đổi/trả hàng';

-- 2. Thêm cột lý do đổi/trả
ALTER TABLE don_hang 
ADD COLUMN IF NOT EXISTS ly_do_doi_tra TEXT DEFAULT NULL COMMENT 'Lý do đổi/trả từ khách hàng';

-- 3. Thêm cột ngày yêu cầu
ALTER TABLE don_hang 
ADD COLUMN IF NOT EXISTS ngay_yeu_cau_doi_tra DATETIME DEFAULT NULL COMMENT 'Ngày khách hàng yêu cầu đổi/trả';

-- 4. Thêm cột ghi chú admin
ALTER TABLE don_hang 
ADD COLUMN IF NOT EXISTS admin_note TEXT DEFAULT NULL COMMENT 'Ghi chú từ admin khi xử lý';

-- 5. Thêm cột ngày xử lý
ALTER TABLE don_hang 
ADD COLUMN IF NOT EXISTS ngay_xu_ly_doi_tra DATETIME DEFAULT NULL COMMENT 'Ngày admin xử lý yêu cầu';

-- Kiểm tra kết quả
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'sales_management' 
  AND TABLE_NAME = 'don_hang'
  AND COLUMN_NAME IN ('trang_thai_doi_tra', 'ly_do_doi_tra', 'ngay_yeu_cau_doi_tra', 'admin_note', 'ngay_xu_ly_doi_tra')
ORDER BY ORDINAL_POSITION;
