# BÁO CÁO SỬA LỖI CLOUDFLARE TUNNEL URL

## 🐛 VẤN ĐỀ

Khi sử dụng Cloudflare tunnel, JavaScript cố tải script từ URL tunnel cũ, gây ra lỗi:
```
JavaScript from "https://bald-uploaded-fwd-actually.trycloudflare.com"
Không thể tải chi tiết yêu cầu
```

## 🔍 NGUYÊN NHÂN

1. **URL Cloudflare thay đổi mỗi lần chạy tunnel mới**
2. **JavaScript sử dụng relative paths** (`../api/...`) không hoạt động đúng với tunnel
3. **Không có cơ chế tự động cập nhật URL** từ file `.env`

## ✅ GIẢI PHÁP ĐÃ TRIỂN KHAI

### 1. Inject BASE_URL từ PHP vào JavaScript

**File: `lequocanh/customer/support.php`**
```php
<script>
    // Inject BASE_URL from PHP to JavaScript
    window.BASE_URL = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : 'http://localhost:20080', '/'); ?>';
</script>
<script src="support.js"></script>
```

**File: `lequocanh/administrator/index.php`**
```php
<!-- Inject BASE_URL from PHP to JavaScript -->
<script>
    window.BASE_URL = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : 'http://localhost:20080', '/'); ?>';
</script>
```

### 2. Tạo hàm getApiUrl() trong JavaScript

**Tất cả các file JavaScript đã được cập nhật:**
- `lequocanh/customer/support.js`
- `lequocanh/administrator/elements_LQA/mreview_management/reviewManagementView.php`
- `lequocanh/administrator/elements_LQA/msupport_tickets/supportTicketsView.php`

```javascript
// Get base URL for API calls
const getApiUrl = (path) => {
    if (window.BASE_URL) {
        return `${window.BASE_URL}/lequocanh/api/${path}`;
    }
    return `../api/${path}`;
};

// Sử dụng
const response = await fetch(getApiUrl('support_tickets.php?action=user_list'));
```

### 3. Cấu hình .env

**File: `.env`**
```env
USE_CLOUDFLARE_TUNNEL=true
BASE_URL=https://bald-uploaded-fwd-actually.trycloudflare.com
```

## 🎯 CÁCH HOẠT ĐỘNG

### Khi chạy Cloudflare Tunnel:

1. **Cập nhật `.env`:**
   ```env
   USE_CLOUDFLARE_TUNNEL=true
   BASE_URL=https://your-new-tunnel-url.trycloudflare.com
   ```

2. **PHP đọc BASE_URL từ .env** qua `bootstrap.php` và `ConfigManager`

3. **PHP inject BASE_URL vào JavaScript:**
   ```javascript
   window.BASE_URL = 'https://your-new-tunnel-url.trycloudflare.com';
   ```

4. **JavaScript sử dụng getApiUrl():**
   ```javascript
   // Tự động tạo URL đúng
   getApiUrl('support_tickets.php') 
   // → https://your-new-tunnel-url.trycloudflare.com/lequocanh/api/support_tickets.php
   ```

### Khi chạy localhost:

1. **Cập nhật `.env`:**
   ```env
   USE_CLOUDFLARE_TUNNEL=false
   BASE_URL=http://localhost:20080
   ```

2. **Hệ thống tự động chuyển sang localhost**

## 📋 KIỂM TRA

### 1. Chạy test tự động:
```bash
docker exec php_ws-web-1 php test_base_url_injection.php
```

### 2. Kiểm tra trong Browser:

**Mở Console (F12):**
```javascript
console.log(window.BASE_URL);
// Phải hiển thị: https://bald-uploaded-fwd-actually.trycloudflare.com
```

**Kiểm tra Network tab:**
- API calls phải dùng URL từ .env
- Không còn lỗi CORS
- Không còn lỗi "Không thể tải chi tiết yêu cầu"

### 3. Test các trang:

**User Side:**
- http://localhost:20080/lequocanh/customer/support.php
- Hoặc: https://your-tunnel-url.trycloudflare.com/lequocanh/customer/support.php

**Admin Side:**
- http://localhost:20080/lequocanh/administrator/?req=review_management
- http://localhost:20080/lequocanh/administrator/?req=support_tickets

## 🔄 QUY TRÌNH CẬP NHẬT TUNNEL MỚI

### Bước 1: Chạy Cloudflare Tunnel
```bash
.\cloudflared.exe tunnel --url http://localhost:20080
```

### Bước 2: Copy URL mới
```
Your quick Tunnel has been created! Visit it at:
https://new-tunnel-url.trycloudflare.com
```

### Bước 3: Cập nhật .env
```env
BASE_URL=https://new-tunnel-url.trycloudflare.com
```

### Bước 4: Clear cache và test
- Clear browser cache (Ctrl+Shift+Delete)
- Hard refresh (Ctrl+F5)
- Kiểm tra `console.log(window.BASE_URL)` trong Console

## ✨ LỢI ÍCH

✅ **Tự động cập nhật URL** - Chỉ cần thay đổi `.env`
✅ **Không còn lỗi CORS** - Tất cả requests dùng cùng domain
✅ **Dễ bảo trì** - Chỉ cần sửa 1 chỗ (`.env`)
✅ **Hỗ trợ cả localhost và tunnel** - Tự động chuyển đổi
✅ **Không cần hardcode URL** - Tất cả đều dynamic

## 📁 FILES ĐÃ THAY ĐỔI

1. **lequocanh/customer/support.php** - Thêm inject BASE_URL
2. **lequocanh/customer/support.js** - Thêm getApiUrl(), sử dụng window.BASE_URL
3. **lequocanh/administrator/index.php** - Thêm inject BASE_URL
4. **lequocanh/administrator/elements_LQA/mreview_management/reviewManagementView.php** - Thêm getApiUrl()
5. **lequocanh/administrator/elements_LQA/msupport_tickets/supportTicketsView.php** - Thêm getApiUrl()
6. **.env** - Cấu hình USE_CLOUDFLARE_TUNNEL và BASE_URL

## 🎓 LƯU Ý

1. **Luôn cập nhật .env** khi có tunnel URL mới
2. **Clear cache** sau khi thay đổi .env
3. **Kiểm tra Console** để đảm bảo window.BASE_URL đúng
4. **Kiểm tra Network tab** để đảm bảo API calls dùng URL đúng

## 🔧 TROUBLESHOOTING

### Vẫn thấy lỗi CORS?
- Clear browser cache (Ctrl+Shift+Delete)
- Hard refresh (Ctrl+F5)
- Kiểm tra `.env` có đúng URL không

### window.BASE_URL undefined?
- Kiểm tra file có inject script không
- Kiểm tra `bootstrap.php` đã load chưa
- Kiểm tra Console có lỗi JavaScript không

### API calls vẫn dùng URL cũ?
- Clear browser cache
- Kiểm tra getApiUrl() có được gọi không
- Kiểm tra Network tab để xem URL thực tế

## ✅ KẾT LUẬN

Hệ thống đã được cấu hình để tự động lấy BASE_URL từ file `.env` và inject vào JavaScript. Mỗi khi có Cloudflare tunnel URL mới, chỉ cần cập nhật file `.env` là tất cả các trang sẽ tự động sử dụng URL mới.
