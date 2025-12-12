# 🎫 Hướng Dẫn Hệ Thống Mã Giảm Giá (Coupon)

## Tổng Quan

Hệ thống mã giảm giá (coupon) cho phép:
- **Admin**: Tạo, quản lý các mã giảm giá với nhiều điều kiện
- **Khách hàng**: Nhập mã giảm giá khi thanh toán để được giảm giá
- **Đơn hàng**: Lưu trữ và hiển thị thông tin coupon đã áp dụng

## Cài Đặt

### Bước 1: Setup Database
Truy cập URL sau để tạo bảng và dữ liệu mẫu:
```
http://localhost/lequocanh/database/setup_coupon_system.php
```

Hoặc chạy SQL thủ công từ file:
```
lequocanh/database/create_coupon_tables.sql
```

### Bước 2: Kiểm tra
Truy cập trang test:
```
http://localhost/test_coupon_system.php
```

## Sử Dụng

### Cho Admin

#### Truy cập quản lý Coupon:
```
Admin Panel > Mã giảm giá (Coupon)
```
Hoặc URL: `http://localhost/lequocanh/administrator/index.php?req=coupon`

#### Tạo mã giảm giá mới:
1. Nhập **Mã coupon** (VD: SALE10, FREESHIP)
2. Chọn **Loại giảm**:
   - `Giảm %`: Giảm theo phần trăm đơn hàng
   - `Giảm tiền`: Giảm số tiền cố định
3. Nhập **Giá trị** (% hoặc VNĐ)
4. Cấu hình điều kiện:
   - **Giảm tối đa**: Giới hạn số tiền giảm (cho loại %)
   - **Đơn tối thiểu**: Giá trị đơn hàng tối thiểu
   - **Số lượt dùng**: Tổng số lần có thể sử dụng
   - **Lượt/người**: Số lần mỗi user được dùng
5. Đặt **Thời gian hiệu lực**
6. Click **Tạo mã**

### Cho Khách Hàng

#### Áp dụng mã giảm giá khi thanh toán:
1. Thêm sản phẩm vào giỏ hàng
2. Tiến hành thanh toán
3. Tại trang checkout, tìm phần **"Mã giảm giá"**
4. Nhập mã coupon và click **"Áp dụng"**
5. Nếu hợp lệ, số tiền giảm sẽ hiển thị trong tổng đơn hàng
6. Hoàn tất thanh toán

## Cấu Trúc Database

### Bảng `coupons`
| Cột | Mô tả |
|-----|-------|
| code | Mã coupon (unique) |
| name | Tên hiển thị |
| discount_type | 'percent' hoặc 'fixed' |
| discount_value | Giá trị giảm |
| max_discount | Giảm tối đa (cho %) |
| min_order_value | Đơn tối thiểu |
| usage_limit | Số lượt dùng tối đa |
| usage_count | Số lượt đã dùng |
| usage_per_user | Lượt/người |
| start_date | Ngày bắt đầu |
| end_date | Ngày kết thúc |
| is_active | Trạng thái |

### Bảng `coupon_usage`
Lưu lịch sử sử dụng coupon:
- coupon_id
- user_id
- order_id
- discount_amount
- used_at

### Cột mới trong `don_hang`
- `coupon_code`: Mã coupon đã áp dụng
- `coupon_discount`: Số tiền được giảm

## API Endpoints

### Validate Coupon
```
POST /lequocanh/administrator/elements_LQA/mgiohang/coupon_api.php?action=validate
Body: { "code": "SALE10", "order_total": 500000 }
```

### Apply Coupon (lưu vào session)
```
POST /lequocanh/administrator/elements_LQA/mgiohang/coupon_api.php?action=apply
Body: { "code": "SALE10", "order_total": 500000 }
```

### Remove Coupon
```
POST /lequocanh/administrator/elements_LQA/mgiohang/coupon_api.php?action=remove
```

### Get Available Coupons
```
GET /lequocanh/administrator/elements_LQA/mgiohang/coupon_api.php?action=available
```

## Mã Mẫu

Sau khi setup, hệ thống sẽ có các mã mẫu:

| Mã | Loại | Giá trị | Điều kiện |
|----|------|---------|-----------|
| SALE10 | Giảm % | 10% (max 100k) | Đơn từ 200k |
| GIAM50K | Giảm tiền | 50.000đ | Đơn từ 500k |
| NEWUSER20 | Giảm % | 20% (max 200k) | Đơn từ 100k, 1 lần/user |
| FREESHIP | Giảm tiền | 30.000đ | Đơn từ 300k |

## Files Quan Trọng

```
lequocanh/
├── administrator/elements_LQA/
│   ├── mod/
│   │   └── CouponCls.php          # Class xử lý coupon
│   ├── mcoupon/
│   │   └── couponView.php         # Trang quản lý coupon (admin)
│   ├── mgiohang/
│   │   ├── coupon_api.php         # API xử lý coupon
│   │   ├── coupon_input_component.php  # Component nhập mã
│   │   ├── checkout.php           # Trang thanh toán (đã tích hợp)
│   │   ├── payment_confirm.php    # Xử lý thanh toán (đã tích hợp)
│   │   └── orderDetailView.php    # Chi tiết đơn hàng (đã tích hợp)
│   ├── madmin/
│   │   ├── orders.php             # Quản lý đơn hàng (đã tích hợp)
│   │   └── print_invoice.php      # In hóa đơn (đã tích hợp)
│   ├── left.php                   # Menu (đã thêm link coupon)
│   └── center.php                 # Router (đã thêm case coupon)
└── database/
    ├── create_coupon_tables.sql   # SQL tạo bảng
    └── setup_coupon_system.php    # Script setup
```

## Lưu Ý Quan Trọng

1. **Validate lại khi thanh toán**: Coupon được validate lại trong `payment_confirm.php` để đảm bảo vẫn hợp lệ
2. **Tính toán tổng tiền**: Tổng tiền = Tạm tính + VAT + Phí ship - Coupon
3. **Lưu trữ đầy đủ**: Thông tin coupon được lưu trong đơn hàng để tra cứu sau
4. **Hiển thị nhất quán**: Coupon hiển thị ở checkout, chi tiết đơn hàng, và hóa đơn in

## Troubleshooting

### Mã không hoạt động?
- Kiểm tra trạng thái `is_active`
- Kiểm tra thời gian hiệu lực
- Kiểm tra số lượt còn lại
- Kiểm tra giá trị đơn hàng tối thiểu

### Không thấy menu Coupon?
- Đảm bảo đăng nhập với tài khoản Admin
- Kiểm tra file `left.php` và `center.php` đã được cập nhật

### Lỗi database?
- Chạy lại `setup_coupon_system.php`
- Kiểm tra các cột `coupon_code`, `coupon_discount` trong bảng `don_hang`
