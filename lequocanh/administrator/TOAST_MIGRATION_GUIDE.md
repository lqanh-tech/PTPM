# Hướng dẫn chuyển đổi từ Alert sang Toast Notification

## 1. Thêm vào file HTML/PHP

Thêm vào phần `<head>` hoặc trước thẻ `</body>`:

```html
<!-- Toast Notification CSS -->
<link rel="stylesheet" href="css_LQA/toast-notification.css">

<!-- Toast Notification JS -->
<script src="js_LQA/toast-notification.js"></script>
```

## 2. Cách sử dụng

### Thay thế alert() cơ bản:

**Trước:**
```javascript
alert('Vui lòng điền đầy đủ thông tin đăng nhập');
```

**Sau:**
```javascript
Toast.error('Vui lòng điền đầy đủ thông tin đăng nhập');
// hoặc
showToast('Vui lòng điền đầy đủ thông tin đăng nhập', 'error');
```

### Các loại thông báo:

```javascript
// Thành công (màu xanh lá)
Toast.success('✅ Đã thêm vào giỏ hàng!');

// Lỗi (màu đỏ)
Toast.error('❌ Có lỗi xảy ra!');

// Cảnh báo (màu vàng)
Toast.warning('⚠ Vui lòng kiểm tra lại thông tin');

// Thông tin (màu xanh dương)
Toast.info('ℹ Đang xử lý...');
```

### Tùy chỉnh thời gian hiển thị:

```javascript
// Hiển thị trong 5 giây (mặc định là 3 giây)
Toast.success('Thành công!', 5000);
```

## 3. Ví dụ chuyển đổi cụ thể

### Ví dụ 1: Form validation
```javascript
// Trước
if (!username || !password) {
    alert('Vui lòng điền đầy đủ thông tin đăng nhập');
    return false;
}

// Sau
if (!username || !password) {
    Toast.error('Vui lòng điền đầy đủ thông tin đăng nhập');
    return false;
}
```

### Ví dụ 2: AJAX success
```javascript
// Trước
.then(data => {
    if (data.success) {
        alert('✅ Đã thêm vào giỏ hàng!');
        location.reload();
    } else {
        alert('❌ Có lỗi xảy ra!');
    }
})

// Sau
.then(data => {
    if (data.success) {
        Toast.success('Đã thêm vào giỏ hàng!');
        setTimeout(() => location.reload(), 1000);
    } else {
        Toast.error('Có lỗi xảy ra!');
    }
})
```

### Ví dụ 3: PHP echo alert
```php
// Trước
echo "<script>alert('Đăng nhập thành công!'); window.location.href = '?req=dashboard';</script>";

// Sau
echo "<script>
    Toast.success('Đăng nhập thành công!');
    setTimeout(() => { window.location.href = '?req=dashboard'; }, 1500);
</script>";
```

## 4. Tính năng

- ✅ Hiển thị đẹp mắt, hiện đại
- ✅ Tự động đóng sau thời gian cài đặt
- ✅ Có thể đóng thủ công bằng nút X
- ✅ Hỗ trợ nhiều toast cùng lúc
- ✅ Animation mượt mà
- ✅ Responsive trên mobile
- ✅ 4 loại: success, error, warning, info

## 5. Danh sách file cần cập nhật

Các file có sử dụng alert() cần được cập nhật:

1. `lequocanh/administrator/userLogin.php`
2. `lequocanh/administrator/signUp.php`
3. `lequocanh/administrator/js_LQA/jscript.js`
4. `lequocanh/payment/admin_dashboard.php`
5. `lequocanh/customer/order_history.php`
6. `lequocanh/components/featuredProductsDisplay.php`
7. Và nhiều file khác...

## 6. Lưu ý

- Nếu cần giữ hành vi blocking (chặn tương tác) như alert(), sử dụng kết hợp với modal
- Với confirm(), cần sử dụng modal riêng (có thể tạo thêm nếu cần)
- Toast phù hợp cho thông báo ngắn, không cần phản hồi từ người dùng
