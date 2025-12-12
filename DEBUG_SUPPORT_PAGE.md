# DEBUG TRANG HỖ TRỢ - "Không thể tải dữ liệu"

## 🐛 Lỗi hiện tại

Trang support hiển thị "Không thể tải dữ liệu" thay vì danh sách tickets.

## 🔍 NGUYÊN NHÂN CÓ THỂ

1. **Chưa đăng nhập** - Trang yêu cầu đăng nhập
2. **Browser cache** - Vẫn dùng JavaScript cũ
3. **API error** - Lỗi khi gọi API
4. **CORS error** - Lỗi cross-origin
5. **Session expired** - Session hết hạn

## ✅ CÁCH DEBUG - Làm từng bước:

### Bước 1: Kiểm tra đăng nhập

**Mở trang chính:**
```
https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/
```

**Đăng nhập với tài khoản user** (không phải admin)

**Sau đó mở lại trang support:**
```
https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/customer/support.php
```

### Bước 2: Clear cache (QUAN TRỌNG)

```
Nhấn: Ctrl + Shift + Delete
Chọn: "Cached images and files"
Click: "Clear data"
```

Hoặc:
```
Nhấn: Ctrl + F5 (hard refresh)
```

### Bước 3: Mở Console và kiểm tra

**Mở DevTools:**
```
Nhấn F12
```

**Vào tab Console, kiểm tra:**

1. **BASE_URL có đúng không?**
   ```javascript
   console.log(window.BASE_URL);
   // Phải hiển thị: https://bald-uploaded-fwd-actually.trycloudflare.com
   ```

2. **Có lỗi JavaScript không?**
   - Tìm dòng màu đỏ (errors)
   - Đọc message để biết lỗi gì

3. **API calls có đúng URL không?**
   - Xem logs: `getApiUrl: ... -> ...`
   - URL phải dùng domain Cloudflare

### Bước 4: Kiểm tra Network tab

**Vào tab Network:**

1. **Refresh trang**

2. **Tìm request `support_tickets.php`:**
   - Click vào request đó
   - Xem tab "Headers"
   - Xem tab "Response"

3. **Kiểm tra:**
   - Status code: Phải là 200 (không phải 401, 403, 500)
   - Response: Phải là JSON `{"success": ...}`
   - Nếu lỗi 401/403: Chưa đăng nhập hoặc session hết hạn
   - Nếu lỗi 500: Lỗi server

### Bước 5: Test với trang debug

**Mở trang test đơn giản:**
```
https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/customer/support_simple.php
```

Trang này sẽ hiển thị:
- ✓ Session status
- ✓ BASE_URL value
- ✓ API test button
- ✓ Load tickets button

**Click các button để test:**
1. "Test API Call" - Xem API có hoạt động không
2. "Load Tickets" - Xem có load được tickets không

### Bước 6: Kiểm tra debug info

**Mở trang debug:**
```
https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/customer/debug_support.php
```

Sẽ hiển thị JSON với thông tin:
```json
{
  "session_started": true/false,
  "user_logged_in": true/false,
  "user_id": "...",
  "base_url_defined": true/false,
  "base_url_value": "...",
  "ticket_count": ...
}
```

## 🔧 GIẢI PHÁP THEO TỪNG TRƯỜNG HỢP

### Trường hợp 1: "user_logged_in": false

**Nguyên nhân:** Chưa đăng nhập

**Giải pháp:**
1. Đăng nhập tại: `https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/`
2. Sau đó mở lại trang support

### Trường hợp 2: Console có lỗi CORS

**Nguyên nhân:** API calls dùng sai domain

**Giải pháp:**
1. Clear cache: Ctrl+F5
2. Kiểm tra `window.BASE_URL` trong Console
3. Nếu sai, chạy: `docker exec php_ws-web-1 php fix_cloudflare_error_now.php`

### Trường hợp 3: API trả về error 401/403

**Nguyên nhân:** Session hết hạn

**Giải pháp:**
1. Đăng xuất
2. Đăng nhập lại
3. Mở lại trang support

### Trường hợp 4: API trả về error 500

**Nguyên nhân:** Lỗi server (SQL, PHP)

**Giải pháp:**
1. Kiểm tra logs: `docker logs php_ws-web-1`
2. Kiểm tra database có bị lỗi không
3. Chạy test: `docker exec php_ws-web-1 php test_review_support_system.php`

### Trường hợp 5: "ticket_count": 0

**Nguyên nhân:** User chưa có ticket nào

**Giải pháp:**
- Đây là bình thường
- Click "Tạo yêu cầu mới" để tạo ticket đầu tiên

## 📋 CHECKLIST DEBUG

- [ ] Đã đăng nhập với tài khoản user
- [ ] Đã clear browser cache (Ctrl+F5)
- [ ] Console không có lỗi màu đỏ
- [ ] window.BASE_URL hiển thị đúng URL Cloudflare
- [ ] Network tab: support_tickets.php trả về status 200
- [ ] Network tab: Response là JSON hợp lệ
- [ ] Trang support_simple.php hoạt động
- [ ] Trang debug_support.php hiển thị user_logged_in: true

## 🎯 KẾT QUẢ MONG ĐỢI

Sau khi debug xong:

✅ Trang support load thành công
✅ Hiển thị "Yêu cầu của bạn" (có thể trống nếu chưa có ticket)
✅ Có nút "Tạo yêu cầu mới"
✅ Console không có lỗi
✅ Có thể tạo ticket mới

## 💡 TIPS

1. **Luôn đăng nhập trước** khi test trang support
2. **Luôn clear cache** sau khi update code
3. **Kiểm tra Console** để xem lỗi chi tiết
4. **Kiểm tra Network tab** để xem API requests
5. **Dùng trang test** để debug dễ hơn

## 📞 COMMANDS HỮU ÍCH

```bash
# Fix lỗi cache
docker exec php_ws-web-1 php fix_cloudflare_error_now.php

# Test hệ thống
docker exec php_ws-web-1 php test_review_support_system.php

# Xem logs
docker logs php_ws-web-1 --tail 50

# Clear JS cache
docker exec php_ws-web-1 php clear_js_cache.php
```
