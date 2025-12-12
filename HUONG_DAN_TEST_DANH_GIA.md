# 🌟 HƯỚNG DẪN TEST HỆ THỐNG ĐÁNH GIÁ

## 📋 Tổng quan

Hệ thống đánh giá sản phẩm cho phép khách hàng đánh giá các sản phẩm mà họ đã mua. Widget đánh giá sẽ:
- ✅ Chỉ hiển thị sản phẩm trong đơn hàng đó
- ✅ Cho phép đánh giá từng sản phẩm riêng biệt
- ✅ Hiển thị trạng thái "Đã đánh giá" cho sản phẩm đã đánh giá
- ✅ Đánh giá sẽ hiển thị trên trang sản phẩm

## 🔧 Các thay đổi đã thực hiện

### 1. Sửa API `product_reviews.php`
**File:** `lequocanh/api/product_reviews.php`

**Thay đổi trong hàm `checkReviewed()`:**
```php
// CŨ (SAI):
$productsSql = "SELECT DISTINCT cdh.ma_san_pham, hh.ten_hang_hoa
               FROM chi_tiet_don_hang cdh
               JOIN tbl_hanghoa hh ON cdh.ma_san_pham = hh.id
               WHERE cdh.ma_don_hang = ?";

// MỚI (ĐÚNG):
$productsSql = "SELECT DISTINCT cdh.ma_san_pham, h.tenhanghoa as product_name
               FROM chi_tiet_don_hang cdh
               JOIN hanghoa h ON cdh.ma_san_pham = h.idhanghoa
               WHERE cdh.ma_don_hang = ?";
```

**Lý do:**
- Tên bảng đúng là `hanghoa` (không phải `tbl_hanghoa`)
- Tên cột đúng là `tenhanghoa` (không phải `ten_hang_hoa`)
- Khóa ngoại đúng là `idhanghoa` (không phải `id`)

### 2. Widget đánh giá
**File:** `lequocanh/components/product_review_widget.php`

Widget đã hoạt động đúng, chỉ cần API trả về dữ liệu đúng.

## 🧪 Cách test

### Bước 1: Chạy file test
```
http://localhost:8080/test_review_widget_products.php
```

File này sẽ:
- Hiển thị danh sách đơn hàng đã duyệt
- Hiển thị sản phẩm trong mỗi đơn hàng
- Test API response
- Cung cấp link để xem widget

### Bước 2: Kiểm tra API
Click nút "Test API" để xem response:

**Response mong đợi:**
```json
{
  "success": true,
  "data": {
    "can_review": true,
    "products": [
      {
        "product_id": "123",
        "product_name": "Tên sản phẩm",
        "reviewed": false
      }
    ]
  }
}
```

**Nếu `products` rỗng `[]`:**
- ❌ Có lỗi trong query
- ❌ Bảng `chi_tiet_don_hang` trống
- ❌ JOIN không đúng

### Bước 3: Test widget trực tiếp
1. Click "Xem Widget" ở một đơn hàng
2. Kiểm tra widget có hiển thị:
   - ✅ Danh sách sản phẩm trong đơn hàng
   - ✅ Chọn số sao (1-5)
   - ✅ Ô nhập nhận xét
   - ✅ Nút "Gửi đánh giá"

### Bước 4: Đánh giá sản phẩm
1. Chọn số sao (1-5)
2. Viết nhận xét (tùy chọn)
3. Click "Gửi đánh giá"
4. ✅ Phải thấy thông báo "Cảm ơn bạn đã đánh giá!"
5. ✅ Sản phẩm chuyển sang trạng thái "Đã đánh giá"

### Bước 5: Kiểm tra trên trang sản phẩm
1. Vào trang sản phẩm vừa đánh giá
2. ✅ Phải thấy đánh giá hiển thị
3. ✅ Có badge "Đã mua hàng"
4. ✅ Số sao và nhận xét hiển thị đúng

## 📍 Vị trí hiển thị widget

Widget đánh giá hiển thị ở 3 vị trí:

### 1. Trang hóa đơn (order_invoice.php)
```
URL: /lequocanh/customer/order_invoice.php?order_id=X
Điều kiện: Đơn hàng đã duyệt (approved) hoặc đã thanh toán (paid)
```

### 2. Trang chi tiết đơn hàng (orderDetailView.php)
```
URL: /lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php?id=X
Điều kiện: 
- Chỉ hiển thị cho USER (không phải ADMIN)
- Đơn hàng đã duyệt (approved) hoặc đã thanh toán (paid)
```

### 3. Trang thành công (order_success.php)
```
URL: /lequocanh/administrator/elements_LQA/mgiohang/order_success.php
Điều kiện: Sau khi thanh toán MoMo thành công
```

## 🔍 Debug

### Nếu không thấy sản phẩm trong widget:

#### 1. Kiểm tra console browser (F12)
```javascript
// Xem có lỗi JavaScript không
// Xem API response
```

#### 2. Kiểm tra API trực tiếp
```
http://localhost:8080/lequocanh/api/product_reviews.php?action=check&order_id=X
```

#### 3. Kiểm tra database
```sql
-- Kiểm tra sản phẩm trong đơn hàng
SELECT cdh.*, h.tenhanghoa 
FROM chi_tiet_don_hang cdh
JOIN hanghoa h ON cdh.ma_san_pham = h.idhanghoa
WHERE cdh.ma_don_hang = X;

-- Kiểm tra đánh giá đã có
SELECT * FROM product_reviews 
WHERE ma_don_hang = X;
```

### Nếu không gửi được đánh giá:

#### 1. Kiểm tra session
```php
// Phải có $_SESSION['USER']
var_dump($_SESSION['USER']);
```

#### 2. Kiểm tra trạng thái đơn hàng
```sql
SELECT id, trang_thai, trang_thai_thanh_toan 
FROM don_hang 
WHERE id = X;
```

#### 3. Xem error log
```
tail -f error.log
```

## 📊 Luồng hoạt động

```
1. Khách hàng đặt hàng
   ↓
2. Admin duyệt đơn (hoặc MoMo thanh toán thành công)
   ↓
3. Khách hàng nhận thông báo "Đơn hàng đã được duyệt"
   ↓
4. Click "Xem hóa đơn & Đánh giá"
   ↓
5. Widget hiển thị danh sách sản phẩm trong đơn hàng
   ↓
6. Khách hàng chọn sao và viết nhận xét
   ↓
7. Click "Gửi đánh giá"
   ↓
8. API lưu vào database
   ↓
9. Đánh giá hiển thị trên trang sản phẩm
```

## ✅ Checklist test

- [ ] Widget hiển thị đúng sản phẩm trong đơn hàng
- [ ] Mỗi sản phẩm có form đánh giá riêng
- [ ] Có thể chọn số sao (1-5)
- [ ] Có thể viết nhận xét
- [ ] Gửi đánh giá thành công
- [ ] Sản phẩm đã đánh giá hiển thị badge "Đã đánh giá"
- [ ] Không thể đánh giá lại sản phẩm đã đánh giá
- [ ] Đánh giá hiển thị trên trang sản phẩm
- [ ] Có badge "Đã mua hàng" trên đánh giá
- [ ] Số sao trung bình được cập nhật

## 🎯 Kết luận

Sau khi sửa API, hệ thống đánh giá sẽ:
1. ✅ Hiển thị đúng sản phẩm mà khách hàng đã mua
2. ✅ Cho phép đánh giá từng sản phẩm riêng biệt
3. ✅ Lưu đánh giá vào database
4. ✅ Hiển thị đánh giá trên trang sản phẩm

**Lưu ý:** Nhớ clear browser cache sau khi sửa code!

---

**Files liên quan:**
- `lequocanh/api/product_reviews.php` - API xử lý đánh giá
- `lequocanh/components/product_review_widget.php` - Widget hiển thị form
- `lequocanh/components/product_review_display.php` - Hiển thị đánh giá trên trang sản phẩm
- `test_review_widget_products.php` - File test
- `test_review_widget_single.php` - Test widget đơn lẻ
