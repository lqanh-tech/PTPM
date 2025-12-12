# Debug Bộ Lọc Màu Sắc

## ✅ Đã hoàn thành

1. ✅ Sửa dữ liệu database - tách "Đen, Tím, Trắng, Vàng" thành 4 records riêng
2. ✅ API trả về đúng 4 màu: Đen, Tím, Trắng, Vàng
3. ✅ JavaScript đã được cập nhật với logging
4. ✅ HTML container đã có sẵn

## 🔍 Các bước kiểm tra

### Bước 1: Test API trực tiếp

**Truy cập:**
```
http://localhost:20080/lequocanh/administrator/elements_LQA/mod/getAvailableColors.php
```

**Kết quả mong đợi:**
```json
{
  "success": true,
  "color_attribute_id": 26,
  "colors": [
    {
      "value": "Tím",
      "display": "Tím",
      "en": "purple",
      "css_class": "color-purple",
      "count": 1
    },
    {
      "value": "Trắng",
      "display": "Trắng",
      "en": "white",
      "css_class": "color-white",
      "count": 1
    },
    {
      "value": "Vàng",
      "display": "Vàng",
      "en": "yellow",
      "css_class": "color-yellow",
      "count": 1
    },
    {
      "value": "Đen",
      "display": "Đen",
      "en": "black",
      "css_class": "color-black",
      "count": 1
    }
  ],
  "total": 4
}
```

### Bước 2: Test trang demo

**Truy cập:**
```
http://localhost:20080/lequocanh/test_color_api.html
```

**Kiểm tra:**
- [ ] API response hiển thị đúng
- [ ] Bộ lọc màu hiển thị 4 màu
- [ ] Mỗi màu có preview đúng
- [ ] Hover vào màu có tooltip

### Bước 3: Test trang chính

**Truy cập:**
```
http://localhost:20080/lequocanh/
```

**QUAN TRỌNG: Clear cache trước!**
- Nhấn: **Ctrl + Shift + R** (hoặc Ctrl + F5)
- Hoặc: F12 → Network tab → Check "Disable cache"

**Kiểm tra:**
1. Mở Console (F12)
2. Tìm logs:
   ```
   Loading dynamic colors...
   Fetching from: ./administrator/elements_LQA/mod/getAvailableColors.php
   Response status: 200
   Color data: {success: true, ...}
   ```
3. Kiểm tra bộ lọc màu có hiển thị không

### Bước 4: Kiểm tra Network

**Trong DevTools:**
1. Mở tab **Network**
2. Filter: **XHR** hoặc **Fetch**
3. Reload trang
4. Tìm request: `getAvailableColors.php`
5. Kiểm tra:
   - Status: 200 OK
   - Response: JSON với 4 màu

## 🐛 Các lỗi thường gặp

### Lỗi 1: Bộ lọc không hiển thị

**Nguyên nhân:** JavaScript bị cache

**Giải pháp:**
```
1. Hard refresh: Ctrl + Shift + R
2. Clear cache: F12 → Application → Clear storage
3. Hoặc thêm version vào script:
   <script src="public_files/product_filter.js?v=2"></script>
```

### Lỗi 2: API trả về 404

**Nguyên nhân:** Đường dẫn sai

**Giải pháp:**
```javascript
// Kiểm tra đường dẫn trong console
console.log(window.location.href);
// Nếu đang ở: http://localhost:20080/lequocanh/
// Thì đường dẫn API phải là: ./administrator/elements_LQA/mod/getAvailableColors.php
```

### Lỗi 3: Container not found

**Nguyên nhân:** HTML chưa load hoặc ID sai

**Giải pháp:**
```javascript
// Test trong console:
document.getElementById('colorFilterContainer')
// Phải trả về element, không phải null
```

### Lỗi 4: CORS error

**Nguyên nhân:** API không cho phép fetch từ origin khác

**Giải pháp:**
```php
// Thêm vào đầu getAvailableColors.php:
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
```

### Lỗi 5: JSON parse error

**Nguyên nhân:** API trả về HTML thay vì JSON (có thể do error)

**Giải pháp:**
```
1. Truy cập API trực tiếp trong browser
2. Kiểm tra có error PHP không
3. Xem response trong Network tab
```

## 📋 Checklist đầy đủ

### Database:
- [x] Thuộc tính "Màu sắc" tồn tại (ID: 26)
- [x] Sản phẩm có màu sắc (4 màu: Đen, Tím, Trắng, Vàng)
- [x] Không có record nào có nhiều màu trong 1 string

### API:
- [x] File `getAvailableColors.php` tồn tại
- [x] API trả về JSON đúng format
- [x] API trả về 4 màu
- [x] Mỗi màu có đầy đủ: value, display, en, css_class, count

### Frontend HTML:
- [x] Container `colorFilterContainer` tồn tại
- [x] Container có class `color-options`
- [x] Loading state hiển thị ban đầu

### Frontend JavaScript:
- [x] File `product_filter.js` được include
- [x] Class `ProductFilter` được khởi tạo
- [x] Function `loadDynamicColors()` được gọi
- [x] Có logging trong console

### Frontend CSS:
- [x] File `product_filter.css` được include
- [x] Có styles cho `.color-option`
- [x] Có styles cho `.color-swatch`
- [x] Có styles cho 12 màu chuẩn

## 🔧 Debug Commands

### Kiểm tra dữ liệu:
```bash
docker-compose exec -T web php /var/www/html/check_color_data.php
```

### Test API:
```bash
docker-compose exec -T web php /var/www/html/lequocanh/administrator/elements_LQA/mod/getAvailableColors.php
```

### Xem logs:
```bash
docker-compose logs web | grep -i color
```

## 💡 Quick Fix

Nếu vẫn không hiển thị, thử cách này:

### 1. Thêm version vào script

**File:** `lequocanh/apart/viewListLoaihang.php`

Tìm dòng:
```html
<script src="public_files/product_filter.js"></script>
```

Sửa thành:
```html
<script src="public_files/product_filter.js?v=<?php echo time(); ?>"></script>
```

### 2. Force reload trong JavaScript

Thêm vào đầu `loadDynamicColors()`:
```javascript
const timestamp = new Date().getTime();
const apiUrl = `./administrator/elements_LQA/mod/getAvailableColors.php?t=${timestamp}`;
```

### 3. Test trực tiếp trong Console

Mở Console và chạy:
```javascript
fetch('./administrator/elements_LQA/mod/getAvailableColors.php')
  .then(r => r.json())
  .then(d => console.log(d))
  .catch(e => console.error(e));
```

## 📞 Nếu vẫn không được

Gửi cho tôi:
1. Screenshot Console (F12)
2. Screenshot Network tab
3. Screenshot bộ lọc màu (hoặc chỗ nó nên hiển thị)
4. Kết quả test API trực tiếp

---

**Cập nhật:** 2025-12-05
**Status:** API hoạt động ✅ | Frontend cần kiểm tra cache
