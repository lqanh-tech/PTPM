# Hướng Dẫn Triển Khai Bcrypt Password Hashing

## Tổng Quan

Dự án đã được cập nhật để sử dụng thuật toán **Bcrypt** để hash password thay vì lưu plain text. Đây là một cải tiến bảo mật quan trọng.

## Các File Đã Được Tạo/Cập Nhật

### 1. File Mới
- **`PasswordHelper.php`**: Class helper để xử lý hash và verify password
  - `PasswordHelper::hash($password)` - Hash password
  - `PasswordHelper::verify($password, $hash)` - Verify password
  - `PasswordHelper::needsRehash($hash)` - Kiểm tra cần rehash
  - `PasswordHelper::isPlainText($password)` - Kiểm tra plain text

### 2. File Đã Cập Nhật
- **`userCls.php`**: Tất cả các method liên quan đến password đã được cập nhật:
  - `UserAdd()` - Hash password khi tạo user mới
  - `UserCheckLogin()` - Verify password với Bcrypt (hỗ trợ auto-migration)
  - `UserUpdate()` - Hash password khi update
  - `UserSetPassword()` - Hash password khi đổi mật khẩu
  - `UserChangePassword()` - Verify và hash password mới

### 3. Script Migration
- **`migrate_passwords.php`**: Script để migration password hiện có từ plain text sang Bcrypt

## Cách Sử Dụng

### Bước 1: Chạy Migration (Quan Trọng!)

Truy cập URL sau để migration tất cả password hiện có:

```
http://localhost:8081/administrator/migrate_passwords.php
```

Script này sẽ:
- Quét tất cả user trong database
- Tìm các password còn là plain text
- Hash lại bằng Bcrypt
- Báo cáo kết quả

**Lưu ý:** Chỉ cần chạy một lần duy nhất!

### Bước 2: Kiểm Tra

Sau khi migration, thử đăng nhập với các tài khoản hiện có để đảm bảo mọi thứ hoạt động bình thường.

### Bước 3: Đăng Ký User Mới

Khi đăng ký user mới qua `signUp.php`, password sẽ tự động được hash bằng Bcrypt.

## Tính Năng Đặc Biệt

### Auto-Migration Khi Đăng Nhập

Nếu bạn bỏ qua bước migration, hệ thống vẫn hoạt động! Khi user đăng nhập:
1. Hệ thống kiểm tra password trong DB có phải plain text không
2. Nếu là plain text, so sánh trực tiếp
3. Sau khi verify thành công, tự động hash lại và cập nhật vào DB

Điều này đảm bảo migration diễn ra mượt mà mà không làm gián đoạn người dùng.

### Auto-Rehash

Nếu bạn thay đổi cost factor trong tương lai, hệ thống sẽ tự động rehash password khi user đăng nhập.

## Thông Tin Kỹ Thuật

### Bcrypt Configuration
- **Algorithm**: PASSWORD_BCRYPT
- **Cost Factor**: 12 (cân bằng giữa bảo mật và hiệu suất)
- **Hash Length**: 60 ký tự
- **Format**: `$2y$12$...`

### Ví Dụ Hash
```
Plain text: "mypassword123"
Bcrypt hash: "$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIeWIvJ3jm"
```

### Tại Sao Bcrypt?
1. **Chống Brute Force**: Cost factor làm chậm quá trình hash
2. **Salt Tự Động**: Mỗi password có salt riêng
3. **Chuẩn Công Nghiệp**: Được khuyến nghị bởi OWASP
4. **Tương Thích**: Hỗ trợ sẵn trong PHP 5.5+

## Bảo Mật

### Những Gì Đã Cải Thiện
✅ Password không còn lưu dạng plain text  
✅ Không thể reverse engineer password từ hash  
✅ Mỗi password có salt riêng  
✅ Chống rainbow table attacks  
✅ Chống timing attacks  

### Những Gì Cần Lưu Ý
⚠️ Không thể khôi phục password cũ (chỉ có thể reset)  
⚠️ Hash mất thời gian hơn plain text (nhưng đáng giá!)  
⚠️ Cần backup database trước khi migration  

## Troubleshooting

### Lỗi: "Call to undefined function password_hash()"
**Nguyên nhân**: PHP version < 5.5  
**Giải pháp**: Upgrade PHP hoặc sử dụng password_compat library

### Lỗi: "Cannot modify header information"
**Nguyên nhân**: Output đã được gửi trước khi redirect  
**Giải pháp**: Đã xử lý bằng `ob_start()` trong userAct.php

### User không đăng nhập được sau migration
**Nguyên nhân**: Có thể do lỗi trong quá trình migration  
**Giải pháp**: 
1. Kiểm tra log trong error.log
2. Chạy lại migrate_passwords.php
3. Hoặc reset password cho user đó

## Testing

### Test Case 1: Đăng Ký User Mới
```php
// Password sẽ tự động được hash
$user->UserAdd('testuser', 'password123', ...);
// DB sẽ lưu: $2y$12$...
```

### Test Case 2: Đăng Nhập
```php
// Verify password với hash
$user->UserCheckLogin('testuser', 'password123');
// Returns: true nếu password đúng
```

### Test Case 3: Đổi Password
```php
// Verify password cũ và hash password mới
$user->UserChangePassword($iduser, 'oldpass', 'newpass');
```

## Kết Luận

Việc triển khai Bcrypt đã hoàn tất và hệ thống của bạn giờ đây an toàn hơn rất nhiều. Tất cả password mới sẽ tự động được hash, và password cũ sẽ được migration khi user đăng nhập.

**Khuyến nghị**: Chạy script migration ngay để đảm bảo tất cả password được bảo vệ.

---
*Tài liệu này được tạo tự động bởi Kiro AI Assistant*
