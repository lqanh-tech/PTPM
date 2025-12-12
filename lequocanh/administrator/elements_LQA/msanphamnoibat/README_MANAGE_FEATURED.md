# 📋 Hướng Dẫn Quản Lý Sản Phẩm Đặc Biệt

## 🔗 URL Truy Cập
```
/administrator/?req=manageFeatured
```

---

## 🎯 3 Tab Quản Lý

### 1. ⭐ Sản Phẩm Nổi Bật
**URL:** `?req=manageFeatured&tab=featured`

**Chức năng:**
- Xem danh sách sản phẩm đã đánh dấu nổi bật (`is_featured = 1`)
- Tự động đánh dấu theo tiêu chí:
  - **Điểm tổng hợp** (Khuyến nghị): 40% doanh số + 30% view + 20% mới + 10% KM
  - **Bán chạy nhất**: Top doanh số
  - **Xem nhiều nhất**: Top lượt xem
- Bỏ đánh dấu sản phẩm

**Badge hiển thị:**
- Màu: Tím gradient (#667eea → #764ba2)
- Icon: ⭐
- Text: "Nổi bật"

---

### 2. ✨ Sản Phẩm Mới
**URL:** `?req=manageFeatured&tab=new`

**Chức năng:**
- Tự động hiển thị sản phẩm tạo trong 30 ngày
- Không cần đánh dấu thủ công
- Hiển thị số ngày đã tạo

**Badge hiển thị:**
- Màu: Hồng gradient (#f093fb → #f5576c)
- Icon: ✨
- Text: "Mới"

**Logic:**
```sql
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
```

---

### 3. 🔥 Khuyến Mãi
**URL:** `?req=manageFeatured&tab=sale`

**Chức năng:**
- Xem danh sách sản phẩm đang khuyến mãi
- Thêm/Sửa/Xóa khuyến mãi
- Hiển thị % giảm giá
- Hiển thị giá gốc và giá khuyến mãi

**Badge hiển thị:**
- Badge đỏ góc trái: **-X%**
- Badge vàng góc phải: 🔥 "Sale"

**⚠️ LƯU Ý QUAN TRỌNG VỀ GIÁ:**

#### Cấu trúc database:
```sql
giagoc         -- Giá gốc (KHÔNG BAO GIỜ thay đổi khi có KM)
giakhuyenmai   -- Giá khuyến mãi (NULL = không KM)
```

#### Quy tắc xử lý giá:

1. **Khi THÊM khuyến mãi:**
   ```sql
   UPDATE hanghoa 
   SET giakhuyenmai = [giá mới]
   WHERE idhanghoa = ?
   -- GIỮ NGUYÊN giagoc
   ```

2. **Khi XÓA khuyến mãi:**
   ```sql
   UPDATE hanghoa 
   SET giakhuyenmai = NULL
   WHERE idhanghoa = ?
   -- GIỮ NGUYÊN giagoc
   ```

3. **Hiển thị giá:**
   ```php
   if ($product->giakhuyenmai && $product->giakhuyenmai < $product->giagoc) {
       // Có khuyến mãi
       echo "Giá KM: " . $product->giakhuyenmai;
       echo "Giá gốc: " . $product->giagoc; // Gạch ngang
   } else {
       // Không khuyến mãi
       echo "Giá: " . $product->giagoc;
   }
   ```

4. **Tính % giảm:**
   ```php
   $discount = round((($giagoc - $giakhuyenmai) / $giagoc) * 100);
   ```

---

## 🔧 API Endpoints

### Xóa khuyến mãi
```javascript
POST ?req=removePromotion
Body: idhanghoa=123

Response:
{
  "success": true,
  "message": "Đã xóa khuyến mãi. Giá gốc được giữ nguyên."
}
```

### Toggle sản phẩm nổi bật
```php
POST ?req=manageFeatured
Body: 
  action=toggle_featured
  idhanghoa=123
  current_status=1
```

### Tự động đánh dấu nổi bật
```php
POST ?req=manageFeatured
Body:
  action=auto_mark_featured
  criteria=by_score
  limit=20
```

---

## 📊 Ưu Tiên Hiển Thị Badge

Khi 1 sản phẩm có nhiều điều kiện:

1. **Khuyến mãi** (cao nhất)
   - Hiển thị: -X% + 🔥 Sale
   
2. **Nổi bật**
   - Hiển thị: ⭐ Nổi bật
   
3. **Mới**
   - Hiển thị: ✨ Mới

**Logic:**
```php
if ($hasDiscount) {
    // Badge Sale + % giảm
} elseif ($isFeatured) {
    // Badge Nổi bật
} elseif ($isNew) {
    // Badge Mới
}
```

---

## ✅ Checklist Sử Dụng

### Sản Phẩm Nổi Bật
- [ ] Chọn tiêu chí tự động
- [ ] Nhập số lượng sản phẩm
- [ ] Click "Áp dụng"
- [ ] Kiểm tra danh sách đã đánh dấu
- [ ] Test hiển thị trên trang chủ

### Sản Phẩm Mới
- [ ] Kiểm tra cột `created_at` có giá trị
- [ ] Sản phẩm < 30 ngày tự động hiển thị
- [ ] Không cần thao tác gì

### Khuyến Mãi
- [ ] **QUAN TRỌNG:** Kiểm tra `giagoc` trước khi thêm KM
- [ ] Nhập `giakhuyenmai` < `giagoc`
- [ ] Kiểm tra % giảm giá hiển thị đúng
- [ ] Test giá hiển thị trên frontend
- [ ] Khi hết KM: Xóa (set `giakhuyenmai = NULL`)
- [ ] **KHÔNG BAO GIỜ** thay đổi `giagoc`

---

## 🐛 Troubleshooting

### Sản phẩm không hiển thị badge?
1. Kiểm tra database:
   - Nổi bật: `is_featured = 1`
   - Mới: `created_at >= NOW() - 30 days`
   - KM: `giakhuyenmai IS NOT NULL AND giakhuyenmai < giagoc`

2. Clear cache trình duyệt

3. Kiểm tra file `viewListLoaihang.php` đã có code badge

### Giá hiển thị sai?
1. Kiểm tra `giagoc` không bị thay đổi
2. Kiểm tra `giakhuyenmai` có giá trị hợp lệ
3. Xem log SQL query

### Badge không đúng màu?
1. Kiểm tra CSS đã load
2. Clear cache CSS
3. Kiểm tra class name: `badge-featured`, `badge-new`, `badge-sale`

---

## 📝 Notes

- Trang tự động reload sau khi thao tác
- Có thông báo success/error
- Responsive trên mobile
- Hỗ trợ bulk operations (tự động đánh dấu nhiều SP)
