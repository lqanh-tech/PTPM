# FORCE CLEAR CACHE - FIX LỖI CLOUDFLARE

## 🐛 Lỗi đang gặp

Khi click "Gửi yêu cầu", xuất hiện lỗi:
```
JavaScript from "https://bald-uploaded-fwd-actually.trycloudflare.com"
Có lỗi xảy ra
```

## 🔍 NGUYÊN NHÂN

Browser đang cache file JavaScript cũ hoặc đang cố load từ URL Cloudflare cũ đã hết hạn.

## ✅ GIẢI PHÁP - Làm CHÍNH XÁC theo thứ tự:

### Bước 1: ĐÓNG HOÀN TOÀN BROWSER

```
1. Đóng TẤT CẢ tab của browser
2. Đóng browser hoàn toàn (không chỉ minimize)
3. Đợi 5 giây
```

### Bước 2: XÓA CACHE HOÀN TOÀN

**Cách 1: Xóa thủ công (KHUYẾN NGHỊ)**

1. Mở browser mới
2. Nhấn: `Ctrl + Shift + Delete`
3. Chọn:
   - ✅ Cookies and other site data
   - ✅ Cached images and files
4. Time range: **All time** (QUAN TRỌNG)
5. Click: "Clear data"
6. Đợi xong rồi đóng browser lại

**Cách 2: Dùng Incognito/Private Mode**

1. Mở browser
2. Nhấn: `Ctrl + Shift + N` (Chrome) hoặc `Ctrl + Shift + P` (Firefox)
3. Dùng Incognito mode để test

### Bước 3: CHẠY SCRIPT FIX

```bash
docker exec php_ws-web-1 php fix_cloudflare_error_now.php
```

Script này sẽ:
- ✅ Thêm version mới vào tất cả file .js
- ✅ Force browser load lại file mới
- ✅ Kiểm tra BASE_URL

### Bước 4: MỞ LẠI VÀ TEST

1. **Mở browser mới** (hoặc Incognito)

2. **Mở trang test:**
   ```
   https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/customer/test_base_url.php
   ```

3. **Kiểm tra:**
   - ✅ PHP BASE_URL phải đúng
   - ✅ JavaScript window.BASE_URL phải đúng
   - ✅ Click "Test API Call" phải thành công

4. **Mở Console (F12):**
   ```javascript
   console.log(window.BASE_URL);
   // Phải hiển thị: https://bald-uploaded-fwd-actually.trycloudflare.com
   ```

5. **Mở trang support:**
   ```
   https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/customer/support.php
   ```

6. **Test tạo ticket:**
   - Click "Tạo yêu cầu mới"
   - Nhập thông tin
   - Click "Gửi yêu cầu"
   - **KHÔNG được có lỗi**

### Bước 5: KIỂM TRA NETWORK TAB

1. Mở DevTools (F12)
2. Vào tab **Network**
3. Tick vào **"Disable cache"** (QUAN TRỌNG)
4. Refresh trang
5. Kiểm tra:
   - File `support.js` phải có `?v=timestamp`
   - Status phải là **200** (không phải 304)
   - Không có request nào đến URL Cloudflare cũ

## 🔧 NẾU VẪN LỖI - Làm thêm:

### Option 1: Dùng Hard Reload

1. Mở DevTools (F12)
2. **Click giữ** nút Refresh
3. Chọn: **"Empty Cache and Hard Reload"**

### Option 2: Disable Service Workers

1. Mở DevTools (F12)
2. Vào tab **Application**
3. Sidebar: **Service Workers**
4. Click: **"Unregister"** tất cả service workers
5. Refresh lại trang

### Option 3: Clear Site Data

1. Mở DevTools (F12)
2. Vào tab **Application**
3. Sidebar: **Storage**
4. Click: **"Clear site data"**
5. Refresh lại trang

### Option 4: Dùng browser khác

Nếu vẫn lỗi, thử browser khác:
- Chrome → Firefox
- Firefox → Edge
- Edge → Chrome

## 📋 CHECKLIST

- [ ] Đã đóng browser hoàn toàn
- [ ] Đã xóa cache "All time"
- [ ] Đã chạy fix_cloudflare_error_now.php
- [ ] Đã mở browser mới/Incognito
- [ ] test_base_url.php hiển thị đúng
- [ ] Console không có lỗi
- [ ] Network tab: Disable cache đã tick
- [ ] support.js có ?v=timestamp
- [ ] Có thể tạo ticket thành công

## 🎯 KẾT QUẢ MONG ĐỢI

Sau khi làm xong:

✅ Không còn lỗi "JavaScript from ..."
✅ Có thể tạo ticket mới
✅ Có thể gửi tin nhắn
✅ Console không có lỗi màu đỏ
✅ Network tab không có request đến URL cũ

## 💡 LƯU Ý QUAN TRỌNG

1. **PHẢI đóng browser hoàn toàn** trước khi xóa cache
2. **PHẢI chọn "All time"** khi xóa cache
3. **PHẢI tick "Disable cache"** trong DevTools khi develop
4. **NÊN dùng Incognito mode** để test
5. **ĐẢM BẢO Cloudflare tunnel đang chạy**

## 🚀 CÁCH NHANH NHẤT

```bash
# 1. Chạy script fix
docker exec php_ws-web-1 php fix_cloudflare_error_now.php

# 2. Đóng browser hoàn toàn

# 3. Mở Incognito mode (Ctrl+Shift+N)

# 4. Test ngay
```

## 📞 DEBUG COMMANDS

```bash
# Xem BASE_URL hiện tại
docker exec php_ws-web-1 php -r "require 'bootstrap.php'; echo BASE_URL;"

# Test hệ thống
docker exec php_ws-web-1 php test_review_support_system.php

# Force clear JS cache
docker exec php_ws-web-1 php clear_js_cache.php

# Fix tất cả
docker exec php_ws-web-1 php fix_cloudflare_error_now.php
```

## ✨ TÓM TẮT

Lỗi này do **browser cache cứng đầu**. Giải pháp:

1. **Đóng browser hoàn toàn**
2. **Xóa cache "All time"**
3. **Chạy fix script**
4. **Dùng Incognito mode**
5. **Tick "Disable cache" trong DevTools**

Nếu làm đúng 5 bước này, lỗi sẽ hết! 🎉
