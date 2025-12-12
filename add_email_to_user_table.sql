-- Migration: Thêm cột email vào bảng user
-- Ngày tạo: 2025-11-29
-- Mục đích: Hỗ trợ gửi email thông báo khi thanh toán thành công

-- Kiểm tra và thêm cột email nếu chưa tồn tại
SET @dbname = DATABASE();
SET @tablename = 'user';
SET @columnname = 'email';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(255) NULL AFTER dienthoai')
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Thêm index cho email để tăng tốc độ tìm kiếm
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = 'idx_email')
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD INDEX idx_email (', @columnname, ')')
));

PREPARE addIndexIfNotExists FROM @preparedStatement;
EXECUTE addIndexIfNotExists;
DEALLOCATE PREPARE addIndexIfNotExists;

-- Hiển thị kết quả
SELECT 'Migration hoàn tất: Đã thêm cột email vào bảng user' AS status;
