-- Tối ưu database indexes cho hiệu suất

-- Index cho bảng đơn hàng
ALTER TABLE don_hang ADD INDEX idx_ngay_tao (ngay_tao);
ALTER TABLE don_hang ADD INDEX idx_trang_thai (trang_thai);
ALTER TABLE don_hang ADD INDEX idx_ma_nguoi_dung (ma_nguoi_dung);

-- Index cho bảng chi tiết đơn hàng
ALTER TABLE chi_tiet_don_hang ADD INDEX idx_ma_don_hang (ma_don_hang);
ALTER TABLE chi_tiet_don_hang ADD INDEX idx_ma_san_pham (ma_san_pham);

-- Index cho bảng đánh giá
ALTER TABLE product_reviews ADD INDEX idx_product_id (product_id);
ALTER TABLE product_reviews ADD INDEX idx_status (status);
ALTER TABLE product_reviews ADD INDEX idx_rating (rating);

-- Index cho bảng khách hàng
ALTER TABLE khachhang ADD INDEX idx_email (email);
ALTER TABLE khachhang ADD INDEX idx_sodienthoai (sodienthoai);

-- Analyze tables
ANALYZE TABLE hanghoa;
ANALYZE TABLE don_hang;
ANALYZE TABLE chi_tiet_don_hang;
ANALYZE TABLE product_reviews;
ANALYZE TABLE khachhang;
