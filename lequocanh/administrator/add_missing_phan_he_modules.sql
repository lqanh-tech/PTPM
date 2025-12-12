-- Script thêm các module phân quyền còn thiếu vào bảng PhanHeQuanLy
-- Chạy script này để đồng bộ các module mới với menu left.php

-- Thêm module Quản lý tài khoản
INSERT IGNORE INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES 
('userview', 'Quản lý tài khoản', 'Quản lý tài khoản người dùng trong hệ thống');

-- Thêm module Gán vai trò người dùng
INSERT IGNORE INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES 
('nguoiDungVaiTroView', 'Gán vai trò người dùng', 'Gán vai trò cho người dùng trong hệ thống');

-- Thêm module Quản lý đơn hàng (tên mới trong menu)
INSERT IGNORE INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES 
('don_hang', 'Quản lý đơn hàng', 'Quản lý đơn đặt hàng của khách');

-- Thêm module Cấu hình thanh toán (tên mới trong menu)
INSERT IGNORE INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES 
('cau_hinh_thanh_toan', 'Cấu hình thanh toán', 'Quản lý cấu hình phương thức thanh toán');

-- Thêm module Quản Lý & Khuyến Mãi SP
INSERT IGNORE INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES 
('quanLySanPhamDacBiet', 'Quản Lý & Khuyến Mãi SP', 'Quản lý sản phẩm đặc biệt, khuyến mãi, nổi bật');

-- Thêm module Nội dung Marketing
INSERT IGNORE INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) VALUES 
('marketing_content', 'Nội dung Marketing', 'Quản lý nội dung marketing, banner, tin tức');

-- Kiểm tra kết quả
SELECT * FROM PhanHeQuanLy ORDER BY tenPhanHe;
