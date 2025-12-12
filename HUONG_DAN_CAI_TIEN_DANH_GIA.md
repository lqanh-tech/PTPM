# 🎉 CẢI TIẾN HỆ THỐNG ĐÁNH GIÁ SẢN PHẨM

## ✨ Những gì đã cải tiến

### 1. **Luồng đánh giá mới cho Bank Transfer & COD**

#### Trước đây:
- ❌ Chỉ MoMo mới có widget đánh giá ngay sau thanh toán
- ❌ Bank Transfer & COD không có cách đánh giá

#### Bây giờ:
- ✅ **Admin duyệt đơn** → Gửi thông báo cho khách hàng
- ✅ **Thông báo có link** → "Xem hóa đơn & Đánh giá"
- ✅ **Trang hóa đơn mới** → Hiển thị hóa đơn + Widget đánh giá
- ✅ **Áp dụng cho cả 3 phương thức**: MoMo, Bank Transfer, COD

### 2. **Trang hóa đơn mới**

File: `lequocanh/customer/order_invoice.php`

**Tính năng:**
- 📄 Hiển thị hóa đơn đầy đủ (sản phẩm, giá, thuế, phí ship)
- 🖨️ In hóa đơn (Print)
- 📥 Tải PDF
- ⭐ Widget đánh giá sản phẩm (chỉ hiển thị khi đã duyệt)
- 🔒 Bảo mật: Chỉ chủ đơn hàng mới xem được

**URL:** `/lequocanh/customer/order_invoice.php?order_id=123`

### 3. **Thông báo được cải tiến**

File: `lequocanh/administrator/elements_LQA/mthongbao/customer_notification_widget.php`

**Thay đổi:**
- Thông báo "Đơn hàng đã được duyệt" → Nút "Xem hóa đơn & Đánh giá"
- Thông báo khác → Nút "Xem chi tiết đơn hàng"
- Link trực tiếp đến trang hóa đơn

### 4. **Hiển thị đánh giá trên trang sản phẩm**

File: `lequocanh/apart/viewHangHoa.php`

**Đã tích hợp:**
- Component hiển thị đánh giá
- Thống kê rating trung bình
- Danh sách đánh giá từ khách hàng
- Phân trang tự động

## 🔄 Luồng hoạt động mới

### Phương thức MoMo:

```
1. Khách hàng thanh toán MoMo
   ↓
2. Thanh toán thành công → trang order_success.php
   ↓
3. Widget đánh giá hiển thị ngay
   ↓
4. Khách hàng đánh giá sản phẩm
   ↓
5. Đánh giá hiển thị trên trang sản phẩm
```

### Phương thức Bank Transfer & COD:

```
1. Khách hàng đặt hàng (Bank Transfer/COD)
   ↓
2. Đơn hàng ở trạng thái "Chờ duyệt"
   ↓
3. Admin duyệt đơn hàng
   ↓
4. Hệ thống gửi thông báo cho khách hàng
   ├─ Thông báo trong app (có icon chuông)
   └─ Email thông báo
   ↓
5. Khách hàng click "Xem hóa đơn & Đánh giá"
   ↓
6. Trang hóa đơn hiển thị:
   ├─ Thông tin hóa đơn đầy đủ
   ├─ Nút in/tải PDF
   └─ Widget đánh giá sản phẩm
   ↓
7. Khách hàng đánh giá sản phẩm
   ↓
8. Đánh giá hiển thị trên trang sản phẩm
```

## 📁 Files đã thay đổi/tạo mới

### Files mới:
1. ✅ `lequocanh/customer/order_invoice.php` - Trang hóa đơn & đánh giá
2. ✅ `HUONG_DAN_CAI_TIEN_DANH_GIA.md` - Tài liệu này

### Files đã cập nhật:
1. ✅ `lequocanh/administrator/elements_LQA/mthongbao/customer_notification_widget.php`
   - Thêm logic hiển thị nút "Xem hóa đơn & Đánh giá"
   
2. ✅ `lequocanh/administrator/elements_LQA/mod/CustomerNotificationManager.php`
   - Thêm link hóa đơn vào message thông báo
   
3. ✅ `lequocanh/apart/viewHangHoa.php`
   - Tích hợp component hiển thị đánh giá mới

## 🎯 Cách sử dụng

### Cho Admin:

1. **Duyệt đơn hàng:**
   ```
   Admin Panel → Quản lý đơn hàng → Click "Duyệt"
   ```

2. **Hệ thống tự động:**
   - Gửi thông báo cho khách hàng
   - Gửi email thông báo
   - Tạo link xem hóa đơn

### Cho Khách hàng:

1. **Xem thông báo:**
   - Click icon chuông 🔔 trên header
   - Xem thông báo "Đơn hàng đã được duyệt"

2. **Xem hóa đơn & Đánh giá:**
   - Click nút "Xem hóa đơn & Đánh giá"
   - Xem thông tin hóa đơn
   - Đánh giá sản phẩm ngay trên trang

3. **Xem đánh giá sản phẩm:**
   - Vào trang chi tiết sản phẩm
   - Cuộn xuống phần "Đánh giá sản phẩm"
   - Xem rating trung bình và đánh giá từ khách khác

## 🧪 Test hệ thống

### Test 1: Duyệt đơn Bank Transfer

```bash
1. Tạo đơn hàng với phương thức "Chuyển khoản"
2. Admin vào quản lý đơn hàng
3. Click "Duyệt" đơn hàng
4. Kiểm tra:
   ✓ Thông báo xuất hiện cho khách hàng
   ✓ Email được gửi
   ✓ Link hóa đơn hoạt động
   ✓ Widget đánh giá hiển thị
```

### Test 2: Duyệt đơn COD

```bash
1. Tạo đơn hàng với phương thức "COD"
2. Admin vào quản lý đơn hàng
3. Click "Duyệt" đơn hàng
4. Kiểm tra tương tự Test 1
```

### Test 3: Đánh giá sản phẩm

```bash
1. Vào trang hóa đơn (sau khi đơn được duyệt)
2. Cuộn xuống phần đánh giá
3. Chọn số sao (1-5)
4. Viết nhận xét
5. Click "Gửi đánh giá"
6. Kiểm tra:
   ✓ Đánh giá được lưu
   ✓ Hiển thị badge "Đã đánh giá"
   ✓ Đánh giá xuất hiện trên trang sản phẩm
```

### Test 4: Hiển thị đánh giá trên trang sản phẩm

```bash
1. Vào trang chi tiết sản phẩm đã có đánh giá
2. Kiểm tra:
   ✓ Rating trung bình hiển thị
   ✓ Biểu đồ phân bố sao
   ✓ Danh sách đánh giá
   ✓ Badge "Đã mua hàng"
   ✓ Phân trang hoạt động
```

## 🔧 Troubleshooting

### Lỗi: Không thấy nút "Xem hóa đơn & Đánh giá"

**Nguyên nhân:** Thông báo không phải loại `order_approved`

**Giải pháp:**
```sql
-- Kiểm tra loại thông báo
SELECT * FROM customer_notifications WHERE order_id = ? AND user_id = ?;

-- Nếu type không phải 'order_approved', cập nhật:
UPDATE customer_notifications 
SET type = 'order_approved' 
WHERE order_id = ? AND user_id = ?;
```

### Lỗi: Widget đánh giá không hiển thị

**Nguyên nhân:** Đơn hàng chưa được duyệt

**Giải pháp:**
```sql
-- Kiểm tra trạng thái đơn hàng
SELECT trang_thai, trang_thai_thanh_toan FROM don_hang WHERE id = ?;

-- Đơn hàng phải có:
-- trang_thai = 'approved' HOẶC trang_thai_thanh_toan = 'paid'
```

### Lỗi: Đánh giá không hiển thị trên trang sản phẩm

**Nguyên nhân:** Component chưa được include

**Giải pháp:**
```php
// Kiểm tra file viewHangHoa.php có dòng này:
include __DIR__ . '/../components/product_review_display.php';
```

## 📊 Database Schema

### Bảng liên quan:

```sql
-- Bảng đánh giá
product_reviews (
    id, ma_don_hang, ma_san_pham, ma_nguoi_dung,
    rating, comment, is_verified_purchase, is_approved,
    helpful_count, ngay_tao, ngay_cap_nhat
)

-- Bảng thông báo
customer_notifications (
    id, user_id, order_id, type, title, message,
    is_read, read_at, created_at
)

-- Bảng đơn hàng (cột mới)
don_hang (
    ...,
    is_reviewed TINYINT(1) DEFAULT 0,
    review_reminder_sent TINYINT(1) DEFAULT 0
)

-- Bảng sản phẩm (cột mới)
tbl_hanghoa (
    ...,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0
)
```

## 🎨 Tùy chỉnh giao diện

### Thay đổi màu sắc nút:

File: `lequocanh/customer/order_invoice.php`

```css
.btn-print {
    background: #007bff; /* Thay màu xanh */
}

.btn-download {
    background: #28a745; /* Thay màu xanh lá */
}
```

### Thay đổi layout hóa đơn:

```css
.invoice-container {
    max-width: 900px; /* Thay đổi độ rộng */
    padding: 40px; /* Thay đổi padding */
}
```

## 📈 Thống kê

### Xem số lượng đánh giá theo sản phẩm:

```sql
SELECT 
    hh.ten_hang_hoa,
    hh.average_rating,
    hh.total_reviews
FROM tbl_hanghoa hh
WHERE hh.total_reviews > 0
ORDER BY hh.average_rating DESC, hh.total_reviews DESC
LIMIT 10;
```

### Xem đánh giá mới nhất:

```sql
SELECT 
    pr.*,
    u.ten as user_name,
    hh.ten_hang_hoa as product_name
FROM product_reviews pr
LEFT JOIN tbl_user u ON pr.ma_nguoi_dung = u.username
LEFT JOIN tbl_hanghoa hh ON pr.ma_san_pham = hh.id
ORDER BY pr.ngay_tao DESC
LIMIT 20;
```

## 🚀 Tính năng tương lai

- [ ] Upload hình ảnh đánh giá
- [ ] Video review
- [ ] Reply đánh giá từ shop
- [ ] Tích điểm khi đánh giá
- [ ] Email nhắc nhở đánh giá sau X ngày
- [ ] Badge "Top Reviewer"
- [ ] Filter đánh giá theo verified purchase
- [ ] Sort theo hữu ích nhất

---

**Phiên bản:** 2.0.0  
**Ngày cập nhật:** 2025-12-05  
**Tương thích:** PHP 7.4+, MySQL 5.7+
