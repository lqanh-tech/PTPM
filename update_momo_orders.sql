-- ================================================================
-- SQL Script: Cập nhật đơn hàng MoMo đã thanh toán thành công
-- ================================================================
-- 
-- THANH TOÁN #1:
-- - Order ID (MoMo): ORDER_1763727552_6090
-- - Original Order Code: ORDER17637273804078
-- - Transaction ID: 4613543296
-- - Amount: 44,980,000 VNĐ
-- - User: khachhang
-- - Status: PAID
--
-- THANH TOÁN #2 (MỚI NHẤT):
-- - Order ID (MoMo): ORDER_1763728128_3569
-- - Original Order Code: ORDER17637281249267
-- - Transaction ID: 4613489868
-- - Amount: 29,990,000 VNĐ
-- - User: khachhang
-- - Status: PAID
--
-- ================================================================

-- 1. KIỂM TRA CÁC ĐơN HÀNG PENDING CỦA USER
SELECT 
    id,
    ma_don_hang_text,
    ma_nguoi_dung,
    tong_tien,
    trang_thai,
    trang_thai_thanh_toan,
    phuong_thuc_thanh_toan,
    ngay_tao,
    ngay_cap_nhat
FROM don_hang 
WHERE ma_nguoi_dung = 'khachhang'
AND phuong_thuc_thanh_toan = 'momo'
ORDER BY ngay_tao DESC
LIMIT 10;

-- ================================================================
-- 2. CẬP NHẬT ĐƠN HÀNG THỨ NHẤT (44,980,000 VNĐ)
-- ================================================================
-- Tìm đơn hàng pending gần nhất với số tiền 44,980,000
UPDATE don_hang 
SET 
    trang_thai_thanh_toan = 'paid',
    trang_thai = 'approved',
    ma_don_hang_text = 'ORDER_1763727552_6090',
    ngay_cap_nhat = NOW()
WHERE ma_nguoi_dung = 'khachhang'
AND phuong_thuc_thanh_toan = 'momo'
AND trang_thai_thanh_toan = 'pending'
AND tong_tien = 44980000
ORDER BY ngay_tao DESC
LIMIT 1;

-- ================================================================
-- 3. CẬP NHẬT ĐƠN HÀNG THỨ HAI (29,990,000 VNĐ) - MỚI NHẤT
-- ================================================================
-- Tìm đơn hàng pending gần nhất với số tiền 29,990,000
UPDATE don_hang 
SET 
    trang_thai_thanh_toan = 'paid',
    trang_thai = 'approved',
    ma_don_hang_text = 'ORDER_1763728128_3569',
    ngay_cap_nhat = NOW()
WHERE ma_nguoi_dung = 'khachhang'
AND phuong_thuc_thanh_toan = 'momo'
AND trang_thai_thanh_toan = 'pending'
AND tong_tien = 29990000
ORDER BY ngay_tao DESC
LIMIT 1;

-- ================================================================
-- 4. XÓA GIỎ HÀNG CỦA USER (nếu chưa xóa)
-- ================================================================
DELETE FROM tbl_giohang WHERE user_id = 'khachhang';

-- ================================================================
-- 5. KIỂM TRA KẾT QUẢ SAU KHI CẬP NHẬT
-- ================================================================
SELECT 
    id,
    ma_don_hang_text AS 'Order Code',
    ma_nguoi_dung AS 'User',
    tong_tien AS 'Amount (VNĐ)',
    trang_thai AS 'Status',
    trang_thai_thanh_toan AS 'Payment Status',
    ngay_tao AS 'Created',
    ngay_cap_nhat AS 'Updated'
FROM don_hang 
WHERE ma_nguoi_dung = 'khachhang'
AND phuong_thuc_thanh_toan = 'momo'
ORDER BY ngay_tao DESC;

-- ================================================================
-- 6. LẤY ID ĐƠN HÀNG ĐỂ XEM CHI TIẾT
-- ================================================================
-- Sau khi chạy query trên, lấy ID của đơn hàng và truy cập:
-- http://localhost:20080/lequocanh/administrator/elements_LQA/mgiohang/order_success.php?order_id=<ID>
-- hoặc
-- https://angeles-chair-autumn-untitled.trycloudflare.com/administrator/elements_LQA/mgiohang/order_success.php?order_id=<ID>

-- ================================================================
-- NOTES:
-- - Chạy từng query một để kiểm tra kết quả
-- - Nếu không tìm thấy đơn hàng pending, có thể đơn hàng chưa được tạo
-- - Trong trường hợp đó, cần kiểm tra log file để debug
-- ================================================================
