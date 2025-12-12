-- Test Search Queries for Order Management
-- Kiểm tra các câu query tìm kiếm đơn hàng

-- 1. Tìm theo mã đơn hàng
SELECT * FROM don_hang 
WHERE ma_don_hang_text LIKE '%ORDER_1764%' 
LIMIT 5;

-- 2. Tìm theo tên khách hàng
SELECT * FROM don_hang 
WHERE ma_nguoi_dung LIKE '%khach%' 
LIMIT 5;

-- 3. Tìm theo tên sản phẩm (JOIN với chi_tiet_don_hang và tbl_sanpham)
SELECT DISTINCT don_hang.* 
FROM don_hang 
INNER JOIN chi_tiet_don_hang ON don_hang.id = chi_tiet_don_hang.ma_don_hang
INNER JOIN tbl_sanpham ON chi_tiet_don_hang.ma_san_pham = tbl_sanpham.ma_san_pham
WHERE tbl_sanpham.ten_san_pham LIKE '%iPhone%'
LIMIT 5;

-- 4. Tìm theo khoảng thời gian (30 ngày qua)
SELECT * FROM don_hang 
WHERE DATE(ngay_tao) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
ORDER BY ngay_tao DESC
LIMIT 10;

-- 5. Tìm theo khoảng giá
SELECT * FROM don_hang 
WHERE tong_tien BETWEEN 100000 AND 1000000
ORDER BY tong_tien DESC
LIMIT 10;

-- 6. Tìm theo phương thức thanh toán
SELECT phuong_thuc_thanh_toan, COUNT(*) as total, SUM(tong_tien) as revenue
FROM don_hang
GROUP BY phuong_thuc_thanh_toan;

-- 7. Tìm theo địa chỉ (Hà Nội)
SELECT * FROM don_hang 
WHERE dia_chi_giao_hang LIKE '%Hà Nội%'
LIMIT 5;

-- 8. Tìm kiếm kết hợp (Multi-criteria)
SELECT * FROM don_hang 
WHERE ma_don_hang_text LIKE '%ORDER%'
AND DATE(ngay_tao) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
AND tong_tien >= 10000
AND phuong_thuc_thanh_toan = 'momo'
ORDER BY ngay_tao DESC
LIMIT 5;

-- 9. Kiểm tra index performance
EXPLAIN SELECT * FROM don_hang 
WHERE ma_don_hang_text LIKE '%ORDER_1764%';

-- 10. Thống kê tổng quan
SELECT 
    COUNT(*) as total_orders,
    COUNT(CASE WHEN trang_thai = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN trang_thai = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN trang_thai = 'cancelled' THEN 1 END) as cancelled,
    SUM(CASE WHEN trang_thai = 'approved' THEN tong_tien ELSE 0 END) as total_revenue
FROM don_hang;
