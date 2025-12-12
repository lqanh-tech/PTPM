# BÁO CÁO SỬA LỖI HỆ THỐNG EMAIL

## Nguyên nhân không gửi được email

### 1. Vấn đề chính
- **EmailService** trước đây sử dụng hàm `mail()` của PHP
- Hàm `mail()` không hoạt động trong môi trường Docker vì không có mail server cục bộ
- Cấu hình SMTP trong `.env` không được sử dụng

### 2. Vấn đề phụ
- Lỗi collation trong database khi JOIN bảng `don_hang` với `user`
- Thiếu gọi `notifyOrderSuccess` trong một số flow thanh toán

## Cách khắc phục

### 1. Sửa EmailService để sử dụng PHPMailer với SMTP
- Thay thế hàm `mail()` bằng PHPMailer
- Load cấu hình SMTP từ file `.env`
- Sử dụng Gmail SMTP với App Password

### 2. Sửa lỗi collation
- Thêm `COLLATE utf8mb4_general_ci` vào các query JOIN

### 3. Cập nhật các file xử lý thanh toán
- `lequocanh/payment/return.php` - MoMo return handler
- `lequocanh/payment/notify.php` - MoMo IPN callback
- `lequocanh/payment/bank_notify.php` - Bank transfer callback

## Các loại email được gửi

| Sự kiện | Method | Khi nào gửi |
|---------|--------|-------------|
| Đặt hàng thành công | `notifyOrderSuccess` | Khi khách đặt hàng COD/Bank/MoMo |
| Thanh toán xác nhận | `notifyPaymentConfirmed` | Khi thanh toán MoMo/Bank thành công |
| Đơn hàng được duyệt | `notifyOrderApproved` | Khi admin duyệt đơn hoặc tự động duyệt |
| Đơn hàng bị hủy | `notifyOrderCancelled` | Khi admin hủy đơn |

## Cấu hình SMTP trong .env

```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=LQA Shop
```

## Test

Chạy file test để kiểm tra:
- `test_email_full.php` - Test cơ bản
- `test_email_with_db.php` - Test với database

## Kết quả

✅ Tất cả 4 loại email đều gửi thành công qua SMTP Gmail.
