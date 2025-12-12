# 📝 HƯỚNG DẪN HỆ THỐNG ĐÁNH GIÁ SẢN PHẨM

## 🎯 Tổng quan

Hệ thống đánh giá sản phẩm cho phép khách hàng:
- ⭐ Đánh giá từ 1-5 sao sau khi mua hàng
- 💬 Viết nhận xét chi tiết về sản phẩm
- ✅ Xác thực "Đã mua hàng" tự động
- 👍 Đánh dấu đánh giá hữu ích
- 📊 Xem thống kê đánh giá trung bình

## 🚀 Cài đặt

### Bước 1: Chạy script cài đặt

```bash
# Truy cập URL sau trong trình duyệt:
http://localhost/setup_product_reviews.php
```

Script sẽ tự động:
- ✅ Tạo bảng `product_reviews` - Lưu đánh giá
- ✅ Tạo bảng `review_images` - Lưu hình ảnh đánh giá (tùy chọn)
- ✅ Tạo bảng `review_helpful` - Lưu lượt hữu ích
- ✅ Tạo view `v_product_review_stats` - Thống kê đánh giá
- ✅ Tạo stored procedure `update_product_rating` - Cập nhật rating
- ✅ Tạo triggers tự động cập nhật rating
- ✅ Thêm cột vào bảng `tbl_hanghoa` và `don_hang`

### Bước 2: Kiểm tra cài đặt

Sau khi chạy script, kiểm tra:

```sql
-- Kiểm tra bảng đã tạo
SHOW TABLES LIKE 'product_reviews';
SHOW TABLES LIKE 'review_helpful';

-- Kiểm tra view
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Kiểm tra cột mới
DESCRIBE tbl_hanghoa;
DESCRIBE don_hang;
```

## 📋 Cấu trúc Database

### Bảng `product_reviews`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| id | INT | ID đánh giá |
| ma_don_hang | INT | ID đơn hàng |
| ma_san_pham | INT | ID sản phẩm |
| ma_nguoi_dung | VARCHAR(50) | ID người dùng |
| rating | TINYINT(1-5) | Số sao đánh giá |
| comment | TEXT | Nhận xét |
| is_verified_purchase | TINYINT(1) | Đã mua hàng |
| is_approved | TINYINT(1) | Đã duyệt |
| helpful_count | INT | Số lượt hữu ích |
| ngay_tao | TIMESTAMP | Ngày tạo |

### View `v_product_review_stats`

Thống kê tự động:
- `total_reviews` - Tổng số đánh giá
- `average_rating` - Đánh giá trung bình
- `five_star` - Số đánh giá 5 sao
- `four_star` - Số đánh giá 4 sao
- `three_star` - Số đánh giá 3 sao
- `two_star` - Số đánh giá 2 sao
- `one_star` - Số đánh giá 1 sao

## 🎨 Tích hợp Frontend

### 1. Hiển thị widget đánh giá trong Order Success

**Đã tự động tích hợp!** Widget sẽ hiển thị khi:
- ✅ Đơn hàng đã thanh toán (`trang_thai_thanh_toan = 'paid'`)
- ✅ Người dùng đã đăng nhập
- ✅ Chưa đánh giá sản phẩm

File: `lequocanh/administrator/elements_LQA/mgiohang/order_success.php`

### 2. Hiển thị đánh giá trong trang sản phẩm

Thêm vào file chi tiết sản phẩm:

```php
<?php
// Trong trang chi tiết sản phẩm
$productId = $_GET['id']; // ID sản phẩm hiện tại
include 'lequocanh/components/product_review_display.php';
?>
```

### 3. Hiển thị rating trên danh sách sản phẩm

```php
<?php
// Lấy rating từ database
$sql = "SELECT average_rating, total_reviews FROM tbl_hanghoa WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$productId]);
$product = $stmt->fetch();
?>

<div class="product-rating">
    <span class="stars">
        <?php 
        $rating = $product['average_rating'];
        for ($i = 1; $i <= 5; $i++) {
            echo $i <= $rating ? '★' : '☆';
        }
        ?>
    </span>
    <span class="count">(<?php echo $product['total_reviews']; ?> đánh giá)</span>
</div>
```

## 🔌 API Endpoints

### 1. Gửi đánh giá mới

```javascript
// POST /lequocanh/api/product_reviews.php
const formData = new FormData();
formData.append('action', 'submit');
formData.append('order_id', orderId);
formData.append('product_id', productId);
formData.append('rating', 5);
formData.append('comment', 'Sản phẩm rất tốt!');

const response = await fetch('/lequocanh/api/product_reviews.php', {
    method: 'POST',
    body: formData
});

const result = await response.json();
// { success: true, data: { review_id: 123, message: "..." } }
```

### 2. Lấy danh sách đánh giá

```javascript
// GET /lequocanh/api/product_reviews.php?action=list&product_id=X&page=1
const response = await fetch(
    `/lequocanh/api/product_reviews.php?action=list&product_id=${productId}&page=1`
);

const result = await response.json();
/*
{
    success: true,
    data: {
        stats: { total_reviews: 10, average_rating: 4.5, ... },
        reviews: [ { id, rating, comment, user_name, ... } ],
        pagination: { page: 1, total: 10, total_pages: 1 }
    }
}
*/
```

### 3. Kiểm tra đã đánh giá chưa

```javascript
// GET /lequocanh/api/product_reviews.php?action=check&order_id=X
const response = await fetch(
    `/lequocanh/api/product_reviews.php?action=check&order_id=${orderId}`
);

const result = await response.json();
/*
{
    success: true,
    data: {
        can_review: true,
        products: [
            { product_id: 1, product_name: "...", reviewed: false },
            { product_id: 2, product_name: "...", reviewed: true }
        ]
    }
}
*/
```

### 4. Đánh dấu hữu ích

```javascript
// POST /lequocanh/api/product_reviews.php
const formData = new FormData();
formData.append('action', 'helpful');
formData.append('review_id', reviewId);

const response = await fetch('/lequocanh/api/product_reviews.php', {
    method: 'POST',
    body: formData
});
```

## 🎯 Luồng hoạt động

### Khi khách hàng mua hàng:

1. **Thanh toán thành công** (COD/MoMo/Bank Transfer)
   - Đơn hàng có `trang_thai_thanh_toan = 'paid'`
   - Chuyển đến trang `order_success.php`

2. **Hiển thị widget đánh giá**
   - Widget tự động load danh sách sản phẩm trong đơn hàng
   - Hiển thị form đánh giá cho từng sản phẩm chưa review

3. **Khách hàng đánh giá**
   - Chọn số sao (1-5)
   - Viết nhận xét (tùy chọn)
   - Click "Gửi đánh giá"

4. **Hệ thống xử lý**
   - Validate: Đã đăng nhập, đã mua hàng, chưa đánh giá
   - Lưu vào database với `is_verified_purchase = 1`
   - Trigger tự động cập nhật `average_rating` của sản phẩm
   - Hiển thị badge "Đã đánh giá"

5. **Hiển thị đánh giá**
   - Đánh giá xuất hiện trong trang chi tiết sản phẩm
   - Có badge "Đã mua hàng" (verified purchase)
   - Người khác có thể đánh dấu "Hữu ích"

## 🔒 Bảo mật & Validation

### Backend validation:

- ✅ Kiểm tra đăng nhập
- ✅ Kiểm tra đơn hàng thuộc về user
- ✅ Kiểm tra đơn hàng đã thanh toán
- ✅ Kiểm tra sản phẩm có trong đơn hàng
- ✅ Kiểm tra chưa đánh giá trước đó
- ✅ Validate rating (1-5)
- ✅ Sanitize comment (XSS protection)

### Frontend validation:

- ✅ Bắt buộc chọn số sao
- ✅ Giới hạn độ dài comment (500 ký tự)
- ✅ Hiển thị character counter
- ✅ Disable button khi đang submit

## 📊 Quản lý đánh giá (Admin)

### Xem tất cả đánh giá:

```sql
SELECT 
    pr.*,
    u.ten as user_name,
    hh.ten_hang_hoa as product_name,
    dh.ma_don_hang_text as order_code
FROM product_reviews pr
LEFT JOIN tbl_user u ON pr.ma_nguoi_dung = u.username
LEFT JOIN tbl_hanghoa hh ON pr.ma_san_pham = hh.id
LEFT JOIN don_hang dh ON pr.ma_don_hang = dh.id
ORDER BY pr.ngay_tao DESC;
```

### Duyệt/Ẩn đánh giá:

```sql
-- Duyệt đánh giá
UPDATE product_reviews SET is_approved = 1 WHERE id = ?;

-- Ẩn đánh giá
UPDATE product_reviews SET is_approved = 0 WHERE id = ?;
```

### Thống kê:

```sql
-- Top sản phẩm được đánh giá cao
SELECT 
    hh.id,
    hh.ten_hang_hoa,
    hh.average_rating,
    hh.total_reviews
FROM tbl_hanghoa hh
WHERE hh.total_reviews > 0
ORDER BY hh.average_rating DESC, hh.total_reviews DESC
LIMIT 10;

-- Đánh giá mới nhất
SELECT * FROM product_reviews 
ORDER BY ngay_tao DESC 
LIMIT 20;
```

## 🎨 Tùy chỉnh giao diện

### Thay đổi màu sắc:

File: `lequocanh/components/product_review_widget.php`

```css
.submit-review-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* Thay đổi gradient theo ý muốn */
}

.star-rating label:hover {
    color: #ffc107; /* Màu sao */
}
```

### Thay đổi số ký tự tối đa:

```html
<textarea maxlength="500"></textarea>
<!-- Thay 500 thành số khác -->
```

## 🐛 Troubleshooting

### Lỗi: "Không thể tải danh sách sản phẩm"

**Nguyên nhân:** API không trả về dữ liệu

**Giải pháp:**
1. Kiểm tra file `lequocanh/api/product_reviews.php` tồn tại
2. Kiểm tra session đã start
3. Xem error log: `error.log`

### Lỗi: "Chỉ có thể đánh giá đơn hàng đã thanh toán"

**Nguyên nhân:** Đơn hàng chưa có `trang_thai_thanh_toan = 'paid'`

**Giải pháp:**
```sql
-- Cập nhật trạng thái thanh toán
UPDATE don_hang 
SET trang_thai_thanh_toan = 'paid' 
WHERE id = ?;
```

### Widget không hiển thị

**Kiểm tra:**
1. Đơn hàng đã thanh toán chưa?
2. User đã đăng nhập chưa?
3. File component tồn tại chưa?

```php
// Debug
var_dump($paymentStatus); // Phải là 'paid'
var_dump($_SESSION['USER']); // Phải có giá trị
```

## 📈 Tính năng nâng cao (Tương lai)

- [ ] Upload hình ảnh đánh giá
- [ ] Reply đánh giá từ admin/seller
- [ ] Filter đánh giá theo số sao
- [ ] Sort đánh giá (mới nhất, hữu ích nhất)
- [ ] Email nhắc nhở đánh giá sau X ngày
- [ ] Tích điểm khi đánh giá
- [ ] Report đánh giá spam/không phù hợp

## 📞 Hỗ trợ

Nếu gặp vấn đề, kiểm tra:
1. File log: `error.log`
2. Browser console (F12)
3. Network tab để xem API response
4. Database có đầy đủ bảng chưa

---

**Phiên bản:** 1.0.0  
**Ngày cập nhật:** 2025-12-05  
**Tương thích:** PHP 7.4+, MySQL 5.7+
