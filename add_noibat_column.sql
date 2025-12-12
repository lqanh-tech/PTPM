-- Thêm cột noibat vào bảng hanghoa nếu chưa có
SET @dbname = DATABASE();
SET @tablename = 'hanghoa';
SET @columnname = 'noibat';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " TINYINT(1) DEFAULT 0")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Đánh dấu một số sản phẩm là nổi bật
UPDATE hanghoa SET noibat = 1 WHERE idhanghoa IN (
  SELECT idhanghoa FROM (
    SELECT idhanghoa FROM hanghoa ORDER BY idhanghoa DESC LIMIT 10
  ) AS temp
);
