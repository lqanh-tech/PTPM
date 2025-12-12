# Hướng Dẫn Debug Vấn Đề Redirect Sau Thanh Toán MoMo

## Vấn Đề
Sau khi thanh toán MoMo thành công, trang hiển thị "Đang chuyển đến trang hóa đơn..." nhưng không tự động chuyển hướng.

## Nguyên Nhân Có Thể
1. JavaScript không được thực thi
2. Session không được set đúng
3. Output buffering chặn JavaScript
4. Lỗi trong quá trình xử lý database
5. Browser cache hoặc cookie bị lỗi

## Giải Pháp Đã Áp Dụng

### 1. Cải Thiện JavaScript Redirect
- Thêm biến `redirectUrl` global để quản lý URL redirect
- Thêm fallback timeout nếu không có URL sau 5 giây
- Thêm console.log để debug
- Giảm thời gian chờ từ 2000ms xuống 1500ms

### 2. Thêm Output Flush
- Thêm `ob_flush()` và `flush()` sau mỗi echo JavaScript
- Đảm bảo JavaScript được gửi đến browser ngay lập tức

### 3. Cải Thiện Error Handling
- Thêm logging chi tiết trong momo_return.php
- Thêm kiểm tra order tồn tại trong order_success.php
- Cho phép truy cập order_success.php với order_id (không bắt buộc session)

### 4. Thêm Progress Bar
- Hiển thị progress bar khi đang chuyển hướng
- Cải thiện UX để người dùng biết hệ thống đang xử lý

## Cách Test

### Test 1: Sử dụng File Test
```bash
# Mở browser và truy cập:
http://localhost:8080/test_momo_redirect.php
```

### Test 2: Kiểm Tra Console
1. Mở DevTools (F12)
2. Vào tab Console
3. Thực hiện thanh toán MoMo
4. Kiểm tra các log:
   - "Order processed successfully. Redirect URL set: ..."
   - "Redirecting to: ..."

### Test 3: Kiểm Tra Error Log
```bash
# Xem error.log
tail -f error.log

# Hoặc trên Windows
type error.log
```

Tìm các dòng log:
- "MoMo Return Callback: ..."
- "MoMo Return - Order updated successfully: ..."
- "Order Success - Order loaded successfully: ..."

### Test 4: Kiểm Tra Database
```sql
-- Kiểm tra đơn hàng có được cập nhật không
SELECT id, ma_don_hang_text, trang_thai, trang_thai_thanh_toan, ngay_cap_nhat
FROM don_hang
ORDER BY ngay_cap_nhat DESC
LIMIT 5;
```

## Các Bước Debug Nếu Vẫn Không Hoạt Động

### Bước 1: Kiểm Tra JavaScript
Mở Console và chạy:
```javascript
console.log('redirectUrl:', redirectUrl);
console.log('Can redirect:', typeof redirectUrl !== 'undefined');
```

### Bước 2: Kiểm Tra Session
Thêm vào đầu order_success.php:
```php
error_log("Session data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));
```

### Bước 3: Test Redirect Thủ Công
Sau khi thanh toán, copy URL và thêm `&debug=1`:
```
http://...momo_return.php?...&debug=1
```

### Bước 4: Kiểm Tra Browser
- Xóa cache: Ctrl + Shift + Delete
- Xóa cookies cho localhost
- Thử browser khác (Chrome, Firefox, Edge)
- Tắt extensions có thể chặn redirect

### Bước 5: Kiểm Tra PHP Settings
```php
// Thêm vào đầu momo_return.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('output_buffering', 'Off');
```

## Giải Pháp Tạm Thời

Nếu vẫn không hoạt động, thêm nút "Xem Hóa Đơn" thủ công:

```php
<?php if ($resultCode == '0' && isset($dbOrderId)): ?>
    <div class="text-center mt-3">
        <a href="order_success.php?order_id=<?php echo $dbOrderId; ?>" class="btn btn-success btn-lg">
            <i class="fas fa-file-invoice"></i> Xem Hóa Đơn
        </a>
    </div>
<?php endif; ?>
```

## Files Đã Sửa
1. `lequocanh/administrator/elements_LQA/mgiohang/momo_return.php`
   - Cải thiện JavaScript redirect
   - Thêm output flush
   - Thêm error handling

2. `lequocanh/administrator/elements_LQA/mgiohang/order_success.php`
   - Cho phép truy cập với order_id only
   - Thêm kiểm tra order tồn tại
   - Thêm logging chi tiết

## Liên Hệ Support
Nếu vấn đề vẫn tiếp diễn, cung cấp:
1. Screenshot của trang momo_return.php
2. Console log (F12 > Console)
3. Nội dung error.log
4. Thông tin browser và OS
