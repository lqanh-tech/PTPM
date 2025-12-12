-- Thêm cột giakhuyenmai vào bảng hanghoa
-- File này để tương thích với logic giá hiện tại ở trang mua hàng

-- Kiểm tra và thêm cột giakhuyenmai nếu chưa có
ALTER TABLE hanghoa 
ADD COLUMN IF NOT EXISTS giakhuyenmai DECIMAL(15,2) NULL COMMENT 'Giá khuyến mãi - Giá sau khi giảm' 
AFTER giathamkhao;

-- Tạo index để tăng tốc query
CREATE INDEX IF NOT EXISTS idx_giakhuyenmai ON hanghoa(giakhuyenmai);

-- Lưu ý quan trọng:
-- 1. giathamkhao: Giá gốc - KHÔNG BAO GIỜ thay đổi khi có khuyến mãi
-- 2. giakhuyenmai: Giá khuyến mãi - NULL = không có khuyến mãi
-- 3. Khi thêm KM: SET giakhuyenmai = [giá mới], GIỮ NGUYÊN giathamkhao
-- 4. Khi xóa KM: SET giakhuyenmai = NULL, GIỮ NGUYÊN giathamkhao
-- 5. Hiển thị: Nếu giakhuyenmai IS NOT NULL thì hiển thị giá KM + gạch ngang giá gốc
