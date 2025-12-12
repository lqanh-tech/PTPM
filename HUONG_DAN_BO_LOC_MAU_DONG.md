# Hướng dẫn Bộ lọc Màu sắc Động

## 🎯 Tổng quan

Hệ thống bộ lọc màu sắc động tự động nhận diện và hiển thị các màu sắc có trong database, thay vì hardcode như trước.

## ✅ Đã hoàn thành

### 1. API lấy màu sắc động
**File:** `lequocanh/administrator/elements_LQA/mod/getAvailableColors.php`

**Chức năng:**
- Tự động tìm thuộc tính "Màu sắc" trong database
- Lấy danh sách màu sắc có sản phẩm
- Mapping màu sắc sang tiếng Anh và CSS class
- Trả về JSON với thông tin đầy đủ

**Response mẫu:**
```json
{
  "success": true,
  "color_attribute_id": 26,
  "colors": [
    {
      "value": "Đỏ",
      "display": "Đỏ",
      "en": "red",
      "css_class": "color-red",
      "count": 5
    },
    {
      "value": "Xanh dương",
      "display": "Xanh dương",
      "en": "blue",
      "css_class": "color-blue",
      "count": 3
    }
  ],
  "total": 2
}
```

### 2. Frontend tự động render
**File:** `lequocanh/apart/viewListLoaihang.php`

**Thay đổi:**
- Xóa hardcode 12 màu cố định
- Thay bằng container động: `<div id="colorFilterContainer">`
- Hiển thị loading state khi đang tải

### 3. JavaScript load màu động
**File:** `lequocanh/public_files/product_filter.js`

**Thêm function:**
- `loadDynamicColors()`: Load màu từ API
- Tự động render checkbox cho mỗi màu
- Hiển thị tooltip với số lượng sản phẩm
- Restore trạng thái từ URL

### 4. CSS hỗ trợ
**File:** `lequocanh/public_files/product_filter.css`

**Thêm:**
- Loading state animation
- Hỗ trợ 14 màu chuẩn
- Responsive design

## 📋 Cách sử dụng

### Bước 1: Thêm màu sắc cho sản phẩm

1. Truy cập trang quản trị:
   ```
   http://localhost:20080/lequocanh/administrator/
   ```
   Hoặc qua Cloudflare tunnel:
   ```
   https://bald-uploaded-fwd-actually.trycloudflare.com/lequocanh/administrator/
   ```

2. Đăng nhập admin

3. Vào **"Quản lý thuộc tính hàng hóa"**

4. Chọn sản phẩm cần thêm màu

5. Chọn thuộc tính: **"Màu sắc"** (ID: 26)

6. Nhập tên màu (sử dụng tên chuẩn):
   - Đỏ
   - Xanh dương
   - Xanh lá
   - Vàng
   - Cam
   - Tím
   - Hồng
   - Đen
   - Trắng
   - Xám
   - Nâu
   - Bạc

7. Nhấn **"Tạo mới"**

### Bước 2: Kiểm tra bộ lọc

1. Truy cập trang sản phẩm:
   ```
   http://localhost:20080/lequocanh/
   ```

2. Bộ lọc màu sắc sẽ tự động hiển thị các màu có sản phẩm

3. Hover vào màu để xem số lượng sản phẩm

4. Click chọn màu để lọc

## 🎨 Mapping màu sắc

| Tiếng Việt | Tiếng Anh | CSS Class | Mã màu |
|------------|-----------|-----------|--------|
| Đỏ | red | color-red | #dc3545 |
| Xanh dương | blue | color-blue | #007bff |
| Xanh lá | green | color-green | #28a745 |
| Vàng | yellow | color-yellow | #ffc107 |
| Cam | orange | color-orange | #fd7e14 |
| Tím | purple | color-purple | #6f42c1 |
| Hồng | pink | color-pink | #e83e8c |
| Đen | black | color-black | #212529 |
| Trắng | white | color-white | #ffffff |
| Xám | gray | color-gray | #6c757d |
| Nâu | brown | color-brown | #8b4513 |
| Bạc | silver | color-silver | #c0c0c0 |

## 🔧 Test API

### Test qua command line:
```bash
docker-compose exec web php /var/www/html/lequocanh/administrator/elements_LQA/mod/getAvailableColors.php
```

### Test qua browser:
```
http://localhost:20080/lequocanh/administrator/elements_LQA/mod/getAvailableColors.php
```

### Test với file HTML:
```
http://localhost:20080/test_dynamic_colors.html
```

## 📊 Ví dụ thêm màu cho sản phẩm

### Ví dụ 1: iPhone 15
```
Hàng hóa: iPhone 15
Thuộc tính: Màu sắc
Tên thuộc tính HH: Đen
→ Lưu
```

### Ví dụ 2: Thêm nhiều màu
```
iPhone 15 - Màu sắc - Đen
iPhone 15 - Màu sắc - Trắng
iPhone 15 - Màu sắc - Xanh dương
```

### Ví dụ 3: Sản phẩm khác
```
Samsung Galaxy S24 - Màu sắc - Tím
Samsung Galaxy S24 - Màu sắc - Xám
Laptop Dell XPS - Màu sắc - Bạc
```

## 🚀 Tính năng

### 1. Tự động nhận diện
- Chỉ hiển thị màu có sản phẩm
- Tự động ẩn màu không có sản phẩm
- Cập nhật real-time khi thêm/xóa sản phẩm

### 2. Hiển thị thông minh
- Tooltip hiển thị số lượng sản phẩm
- Loading state khi đang tải
- Error handling khi API lỗi

### 3. Tương thích
- Hoạt động với bộ lọc hiện tại
- Lưu trạng thái vào URL
- Restore khi reload trang

### 4. Performance
- Cache API response
- Lazy loading
- Debounce filter requests

## 🐛 Troubleshooting

### Lỗi: Không hiển thị màu nào
**Nguyên nhân:** Chưa có sản phẩm nào được gán màu sắc

**Giải pháp:**
1. Vào trang quản trị
2. Thêm màu sắc cho ít nhất 1 sản phẩm
3. Reload trang

### Lỗi: API trả về lỗi
**Nguyên nhân:** Database connection hoặc SQL error

**Giải pháp:**
1. Kiểm tra Docker containers đang chạy:
   ```bash
   docker-compose ps
   ```
2. Kiểm tra logs:
   ```bash
   docker-compose logs web
   ```
3. Test API trực tiếp

### Lỗi: Màu không đúng
**Nguyên nhân:** Tên màu không chuẩn

**Giải pháp:**
- Sử dụng tên màu chuẩn (xem bảng mapping)
- Viết hoa chữ cái đầu
- Không dùng ký tự đặc biệt

### Lỗi: Loading mãi không xong
**Nguyên nhân:** API không response

**Giải pháp:**
1. Kiểm tra network trong DevTools
2. Kiểm tra đường dẫn API
3. Kiểm tra CORS settings

## 📝 Query kiểm tra dữ liệu

### Kiểm tra thuộc tính màu sắc:
```sql
SELECT * FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%';
```

### Kiểm tra màu sắc của sản phẩm:
```sql
SELECT 
    h.tenhanghoa, 
    tt.tenThuocTinhHH as mau_sac
FROM thuoctinhhh tt
JOIN hanghoa h ON tt.idhanghoa = h.idhanghoa
WHERE tt.idThuocTinh = 26
ORDER BY h.tenhanghoa;
```

### Đếm số sản phẩm theo màu:
```sql
SELECT 
    tenThuocTinhHH as mau_sac, 
    COUNT(*) as so_luong
FROM thuoctinhhh
WHERE idThuocTinh = 26
GROUP BY tenThuocTinhHH
ORDER BY so_luong DESC;
```

## 🎯 Kết quả mong đợi

Sau khi thêm màu sắc cho sản phẩm:

1. **Bộ lọc tự động hiển thị** các màu có sản phẩm
2. **Tooltip hiển thị** số lượng sản phẩm mỗi màu
3. **Click chọn màu** để lọc sản phẩm
4. **URL tự động cập nhật** với filter parameters
5. **Reload trang** vẫn giữ trạng thái filter

## 📸 Screenshots

### Trước (Hardcode):
- 12 màu cố định
- Hiển thị cả màu không có sản phẩm
- Không biết số lượng sản phẩm

### Sau (Dynamic):
- Chỉ hiển thị màu có sản phẩm
- Tooltip hiển thị số lượng
- Tự động cập nhật khi thêm/xóa

## 🔄 Workflow hoàn chỉnh

```
1. Admin thêm sản phẩm mới
   ↓
2. Admin gán màu sắc cho sản phẩm
   ↓
3. API tự động nhận diện màu mới
   ↓
4. Frontend tự động hiển thị màu mới
   ↓
5. User có thể lọc theo màu mới
```

## 💡 Tips

1. **Sử dụng tên màu chuẩn** để mapping tự động
2. **Thêm nhiều màu** cho cùng sản phẩm nếu có nhiều phiên bản
3. **Kiểm tra API** trước khi test frontend
4. **Clear cache** nếu không thấy cập nhật
5. **Sử dụng DevTools** để debug

## 📞 Hỗ trợ

Nếu gặp vấn đề:
1. Kiểm tra file log: `error.log`
2. Test API trực tiếp
3. Kiểm tra database connection
4. Xem console trong DevTools

---

**Tác giả:** Kiro AI Assistant  
**Ngày tạo:** 2025-12-05  
**Phiên bản:** 2.0 - Dynamic Color Filter
