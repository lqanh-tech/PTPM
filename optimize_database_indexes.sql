-- Tối ưu database indexes cho hiệu suất
-- Chạy file này một lần để thêm indexes

-- Index cho bảng hanghoa (sản phẩm)
ALTER TABLE hanghoa ADD INDEX idx_trangthai (trangthai);
ALTER TABLE hanghoa ADD INDEX idx_maloaihang (maloaihang);
ALTER TABLE hanghoa ADD INDEX idx_noibat (noibat);
ALTER TABLE hanghoa ADD INDEX idx_created_at (created_at);

-- Index cho bảng đơn hàng
ALTER TABLE donhang ADD INDEX idx_ngaydat (ngaydat);
ALTER TABLE donhang ADD INDEX idx_trangthai (trangthai);
ALTER TABLE donhang ADD INDEX idx_makhachhang (makhachhang);

-- Index cho bảng chi tiết đơn hàng
ALTER TABLE chitietdonhang ADD INDEX idx_madonhang (madonhang);
ALTER TABLE chitietdonhang ADD INDEX idx_mahanghoa (mahanghoa);

-- Index cho bảng đánh giá
ALTER TABLE product_reviews ADD INDEX idx_product_id (product_id);
ALTER TABLE product_reviews ADD INDEX idx_status (status);
ALTER TABLE product_reviews ADD INDEX idx_rating (rating);

-- Index cho bảng khách hàng
ALTER TABLE khachhang ADD INDEX idx_email (email);
ALTER TABLE khachhang ADD INDEX idx_sodienthoai (sodienthoai);

-- Analyze tables sau khi thêm indexes
ANALYZE TABLE hanghoa;
ANALYZE TABLE donhang;
ANALYZE TABLE chitietdonhang;
ANALYZE TABLE product_reviews;
ANALYZE TABLE khachhang;
