-- Migration: Thêm cột trạng thái vào bảng hanghoa
-- Ngày tạo: 2025-11-26
-- Mô tả: Thêm cột trangthai với các giá trị: dang_ban, ngung_ban, het_hang

-- Kiểm tra và thêm cột trangthai nếu chưa tồn tại
ALTER TABLE hanghoa 
ADD COLUMN IF NOT EXISTS trangthai ENUM('dang_ban', 'ngung_ban', 'het_hang') 
DEFAULT 'dang_ban' 
COMMENT 'Trạng thái sản phẩm: dang_ban=Đang bán, ngung_ban=Ngừng bán, het_hang=Hết hàng'
AFTER noibat;

-- Tạo index cho cột trangthai để tối ưu query
CREATE INDEX IF NOT EXISTS idx_hanghoa_trangthai ON hanghoa(trangthai);

-- Cập nhật tất cả sản phẩm hiện tại về trạng thái "đang bán" (mặc định)
UPDATE hanghoa 
SET trangthai = 'dang_ban' 
WHERE trangthai IS NULL;

-- Tạo bảng lịch sử thay đổi trạng thái (để tracking)
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

-- Tạo trigger để tự động log thay đổi trạng thái
DELIMITER $$

DROP TRIGGER IF EXISTS hanghoa_trangthai_log$$

CREATE TRIGGER hanghoa_trangthai_log
AFTER UPDATE ON hanghoa
FOR EACH ROW
BEGIN
    IF OLD.trangthai != NEW.trangthai THEN
        INSERT INTO hanghoa_trangthai_history (idhanghoa, trangthai_cu, trangthai_moi, ly_do)
        VALUES (NEW.idhanghoa, OLD.trangthai, NEW.trangthai, 'Cập nhật từ hệ thống');
    END IF;
END$$

DELIMITER ;

-- Thông báo hoàn thành
SELECT 'Migration completed successfully! Column trangthai added to hanghoa table.' AS status;
