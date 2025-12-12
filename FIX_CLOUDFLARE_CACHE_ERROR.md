# FIX LỖI CLOUDFLARE CACHE

## 🐛 Lỗi đang gặp

```
JavaScript from "https://bald-uploaded-fwd-actually.trycloudflare.com"
Không thể tải chi tiết yêu cầu
```

## 🔍 Nguyên nhân

Browser đang cache file `support.js` cũ với URL Cloudflare cũ. Mặc dù đã cập nhật code, browser vẫn dùng file cached.

## ✅ GIẢI PHÁP - Làm ngay 3 bước này:

### Bước 1: Clear Browser Cache (BẮT BUỘC)

**Cách 1: Hard Refresh**
```
Nhấn: Ctrl + F5
```

**Cách 2: Clear All Cache**
```
1. Nhấn: Ctrl + Shift + Delete
2. Chọn: "Cached images and files"
3. Time range: "All time"
4. Click: "Clear data"
```

**Cách 3: Disable Cache trong DevTools**
```
1. Mở DevTools (F12)
2. Vào tab Network
3. Tick vào "Disable cache"
4. Refresh lại trang
```

### Bước 2: Kiểm tra BASE_URL

Mở trang test:
```
https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/customer/test_base_url.php
```

Kiểm tra:
- ✅ PHP BASE_URL phải là: `https://bald-uploaded-fwd-actually.trycloudflare.com`
- ✅ JavaScript window.BASE_URL phải giống PHP
- ✅ API URL phải dùng domain Cloudflare

### Bước 3: Test trang Support

Mở trang support:
```
https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/customer/support.php
```

Mở Console (F12) và kiểm tra:
```javascript
console.log(window.BASE_URL);
// Phải hiển thị: https://bald-uploaded-fwd-actually.trycloudflare.com
```

## 🔧 Nếu vẫn lỗi - Làm thêm:

### 1. Force Clear JavaScript Cache

Chạy script:
```bash
docker exec php_ws-web-1 php clear_js_cache.php
```

Script này sẽ thêm version parameter vào tất cả file .js để force browser load lại.

### 2. Kiểm tra Network Tab

1. Mở DevTools (F12)
2. Vào tab Network
3. Refresh trang
4. Tìm file `support.js`
5. Kiểm tra:
   - URL phải có `?v=timestamp`
   - Status phải là 200 (không phải 304 cached)

### 3. Kiểm tra Console Logs

Sau khi load trang, Console phải hiển thị:
```
BASE_URL injected: https://bald-uploaded-fwd-actually.trycloudflare.com
getApiUrl: support_tickets.php?action=user_list -> https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/api/support_tickets.php?action=user_list
```

### 4. Test API Call trực tiếp

Trong Console, gõ:
```javascript
fetch(window.BASE_URL + '/lequocanh/api/support_tickets.php?action=user_list')
  .then(r => r.json())
  .then(d => console.log(d))
  .catch(e => console.error(e));
```

Phải trả về JSON response, không phải lỗi CORS.

## 📋 Checklist

- [ ] Đã clear browser cache (Ctrl+F5)
- [ ] window.BASE_URL hiển thị đúng URL Cloudflare
- [ ] Console không có lỗi JavaScript
- [ ] Network tab: support.js load với ?v=timestamp
- [ ] Network tab: API calls dùng URL Cloudflare
- [ ] Không còn lỗi CORS
- [ ] Trang support.php hiển thị danh sách tickets

## 🎯 Kết quả mong đợi

Sau khi làm xong các bước trên:

✅ Trang support load thành công
✅ Hiển thị danh sách tickets
✅ Có thể tạo ticket mới
✅ Có thể chat với admin
✅ Không còn lỗi trong Console
✅ Không còn lỗi CORS

## 💡 Lưu ý quan trọng

1. **Luôn clear cache** sau khi thay đổi code JavaScript
2. **Sử dụng Disable cache** trong DevTools khi đang develop
3. **Kiểm tra Console logs** để debug
4. **Kiểm tra Network tab** để xem requests thực tế
5. **Đảm bảo Cloudflare tunnel đang chạy**

## 🚀 Nếu muốn đổi sang URL Cloudflare mới

```bash
# Cập nhật .env
php update_cloudflare_url.php https://new-url.trycloudflare.com

# Clear cache
docker exec php_ws-web-1 php clear_js_cache.php

# Clear browser cache
Ctrl+F5
```

## 📞 Debug Commands

```bash
# Kiểm tra BASE_URL trong PHP
docker exec php_ws-web-1 php -r "require 'bootstrap.php'; echo BASE_URL;"

# Test injection
docker exec php_ws-web-1 php test_base_url_injection.php

# Clear JS cache
docker exec php_ws-web-1 php clear_js_cache.php
```

## ✨ Tóm tắt

Lỗi này do **browser cache** file JavaScript cũ. Giải pháp:
1. **Clear cache** (Ctrl+F5)
2. **Kiểm tra** window.BASE_URL trong Console
3. **Test** trang test_base_url.php

Nếu vẫn lỗi, chạy `clear_js_cache.php` và clear cache lại.
