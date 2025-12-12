# ✅ KIỂM TRA HOÀN CHỈNH LOGIC GIÁ KHUYẾN MÃI

## 📋 TÓM TẮT HIỆN TRẠNG

### ✅ ĐÃ SỬA
1. **Giỏ hàng** (`giohangCls.php`):
   - Query đã ưu tiên `giakhuyenmai`
   - Dùng `COALESCE(h.giakhuyenmai, h.giathamkhao)`

### ⏳ CẦN KIỂM TRA
1. **Bảng `chi_tiet_don_hang`**: Có cột `don_gia` không?
2. **Code tạo đơn hàng**: Lưu giá đúng không?
3. **Báo cáo doanh thu**: Dùng giá từ đâu?

---

## 🔍 SCRIPT KIỂM TRA DATABASE

### 1. Kiểm tra cấu trúc bảng

```sql
-- Kiểm tra bảng chi_tiet_don_hang
DESCRIBE chi_tiet_don_hang;

-- Kết quả mong đợi:
-- ma_don_hang INT
-- ma_san_pham INT  
-- so_luong INT
-- don_gia DECIMAL(15,2)  ← QUAN TRỌNG!
-- thanh_tien DECIMAL(15,2)
```

### 2. Kiểm tra dữ liệu mẫu

```sql
-- Xem 10 đơn hàng gần nhất
SELECT 
    dh.id,
    dh.ngay_dat,
    ct.ma_san_pham,
    h.tenhanghoa,
    ct.don_gia as gia_luu_trong_don,
    h.giathamkhao as gia_hien_tai,
    h.giakhuyenmai as gia_km_hien_tai,
    ct.so_luong,
    ct.thanh_tien
FROM don_hang dh
JOIN chi_tiet_don_hang ct ON dh.id = ct.ma_don_hang
JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
ORDER BY dh.id DESC
LIMIT 10;
```

### 3. Kiểm tra tính toàn vẹn

```sql
-- Kiểm tra thanh_tien = don_gia * so_luong
SELECT 
    ct.id,
    ct.don_gia,
    ct.so_luong,
    ct.thanh_tien,
    (ct.don_gia * ct.so_luong) as tinh_lai,
    CASE 
        WHEN ct.thanh_tien = (ct.don_gia * ct.so_luong) THEN 'OK'
        ELSE 'SAI'
    END as kiem_tra
FROM chi_tiet_don_hang ct
WHERE ct.thanh_tien != (ct.don_gia * ct.so_luong);
-- Kết quả phải RỖNG
```

---

## 🔧 NẾU THIẾU CỘT `don_gia`

### Thêm cột vào bảng:

```sql
-- Thêm cột don_gia
ALTER TABLE chi_tiet_don_hang 
ADD COLUMN don_gia DECIMAL(15,2) NOT NULL DEFAULT 0 
COMMENT 'Giá tại thời điểm mua' 
AFTER so_luong;

-- Cập nhật dữ liệu cũ (nếu có)
UPDATE chi_tiet_don_hang ct
JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
SET ct.don_gia = COALESCE(h.giakhuyenmai, h.giathamkhao)
WHERE ct.don_gia = 0;

-- Tính lại thanh_tien
UPDATE chi_tiet_don_hang 
SET thanh_tien = don_gia * so_luong
WHERE thanh_tien = 0 OR thanh_tien IS NULL;
```

---

## 📝 CODE MẪU CHUẨN

### 1. Lấy giá từ giỏ hàng (ĐÃ ĐÚNG)

```php
// File: giohangCls.php
$sql = "SELECT g.product_id, g.quantity, h.tenhanghoa, 
       COALESCE(h.giakhuyenmai, h.giathamkhao) as giathamkhao,
       h.giakhuyenmai,
       h.hinhanh
       FROM tbl_giohang g
       LEFT JOIN hanghoa h ON g.product_id = h.idhanghoa
       WHERE g.user_id = ?";
```

### 2. Tạo đơn hàng (CẦN KIỂM TRA)

```php
// File: checkout.php hoặc momo_notify.php
require_once 'giohangCls.php';

$giohang = new GioHang();
$cart = $giohang->getCart();

// Tạo đơn hàng
$sql = "INSERT INTO don_hang (user_id, tong_tien, ngay_dat, trang_thai) 
        VALUES (?, ?, NOW(), 'pending')";
$stmt->execute([$user_id, $tong_tien]);
$ma_don_hang = $db->lastInsertId();

// Tạo chi tiết đơn hàng
foreach ($cart as $item) {
    // $item['giathamkhao'] đã là giá ưu tiên KM
    $don_gia = $item['giathamkhao'];
    $so_luong = $item['quantity'];
    $thanh_tien = $don_gia * $so_luong;
    
    $sql = "INSERT INTO chi_tiet_don_hang 
            (ma_don_hang, ma_san_pham, so_luong, don_gia, thanh_tien) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt->execute([
        $ma_don_hang, 
        $item['product_id'], 
        $so_luong, 
        $don_gia,  // ← Lưu giá tại thời điểm mua
        $thanh_tien
    ]);
}
```

### 3. Báo cáo doanh thu (CẦN KIỂM TRA)

```php
// File: baocao.php
// ĐÚNG ✅ - Dùng thanh_tien từ chi_tiet_don_hang
$sql = "SELECT 
        DATE(dh.ngay_dat) as ngay,
        COUNT(DISTINCT dh.id) as so_don_hang,
        SUM(ct.thanh_tien) as doanh_thu,  -- ← ĐÚNG
        SUM(ct.so_luong) as so_san_pham
        FROM don_hang dh
        JOIN chi_tiet_don_hang ct ON dh.id = ct.ma_don_hang
        WHERE dh.trang_thai_thanh_toan = 'paid'
        GROUP BY DATE(dh.ngay_dat)
        ORDER BY ngay DESC";

// SAI ❌ - KHÔNG dùng giá hiện tại từ hanghoa
$sql = "SELECT 
        SUM(ct.so_luong * h.giathamkhao) as doanh_thu  -- ← SAI!
        FROM chi_tiet_don_hang ct
        JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa";
```

---

## 🎯 KỊCH BẢN TEST

### Test Case 1: Mua sản phẩm có khuyến mãi

**Setup:**
```sql
-- Tạo sản phẩm test
INSERT INTO hanghoa (tenhanghoa, giathamkhao, giakhuyenmai) 
VALUES ('Test Product', 1000000, 800000);
-- Giá gốc: 1,000,000đ
-- Giá KM: 800,000đ
-- Giảm: 20%
```

**Bước test:**
1. Thêm vào giỏ hàng
2. Kiểm tra giá trong giỏ = 800,000đ
3. Thanh toán
4. Kiểm tra `chi_tiet_don_hang.don_gia` = 800,000đ
5. Kiểm tra `chi_tiet_don_hang.thanh_tien` = 800,000đ × số lượng
6. Xóa khuyến mãi: `UPDATE hanghoa SET giakhuyenmai = NULL`
7. Kiểm tra đơn hàng cũ vẫn giữ giá 800,000đ

**Query kiểm tra:**
```sql
SELECT 
    dh.id,
    ct.ma_san_pham,
    ct.don_gia as gia_da_mua,
    h.giathamkhao as gia_hien_tai,
    h.giakhuyenmai as km_hien_tai
FROM don_hang dh
JOIN chi_tiet_don_hang ct ON dh.id = ct.ma_don_hang
JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
WHERE ct.ma_san_pham = [ID_TEST_PRODUCT];
```

### Test Case 2: Thay đổi giá sau khi đặt hàng

**Kịch bản:**
1. Khách A mua sản phẩm giá 1,000,000đ
2. Admin giảm giá còn 800,000đ
3. Khách B mua sản phẩm giá 800,000đ
4. Kiểm tra báo cáo:
   - Đơn A: 1,000,000đ
   - Đơn B: 800,000đ
   - Tổng: 1,800,000đ

---

## ⚠️ CÁC VẤN ĐỀ TIỀM ẨN

### 1. Giỏ hàng lưu lâu

**Vấn đề:**
- Khách thêm SP vào giỏ với giá KM
- Sau 1 tuần, KM hết
- Khách thanh toán → giá đã thay đổi

**Giải pháp:**
- Hiển thị cảnh báo nếu giá thay đổi
- Yêu cầu khách xác nhận lại

```php
// Kiểm tra giá thay đổi
$old_price = $_SESSION['cart_price'][$product_id];
$new_price = getCurrentPrice($product_id);

if ($old_price != $new_price) {
    echo "⚠️ Giá đã thay đổi từ " . number_format($old_price) . "đ 
          thành " . number_format($new_price) . "đ";
}
```

### 2. Đơn giá vs Khuyến mãi

**Vấn đề:**
- Bảng `dongia` cập nhật `giathamkhao`
- Cùng lúc có `giakhuyenmai`
- Giá nào được ưu tiên?

**Giải pháp:**
- Luôn ưu tiên `giakhuyenmai` nếu có
- `COALESCE(giakhuyenmai, giathamkhao)`

### 3. Báo cáo lợi nhuận

**Vấn đề:**
- Cần biết giá vốn để tính lợi nhuận
- Giá bán có thể là giá KM

**Giải pháp:**
```sql
SELECT 
    ct.ma_san_pham,
    ct.don_gia as gia_ban,
    h.gia_von,
    (ct.don_gia - h.gia_von) * ct.so_luong as loi_nhuan
FROM chi_tiet_don_hang ct
JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa;
```

---

## ✅ CHECKLIST CUỐI CÙNG

### Database:
- [ ] Bảng `chi_tiet_don_hang` có cột `don_gia`
- [ ] Bảng `hanghoa` có cột `giakhuyenmai`
- [ ] Test query giỏ hàng ưu tiên KM
- [ ] Test query báo cáo dùng `thanh_tien`

### Code:
- [x] `giohangCls.php` - Query đã sửa
- [ ] `checkout.php` - Lưu `don_gia` đúng
- [ ] `momo_notify.php` - Tạo đơn hàng đúng
- [ ] `baocao.php` - Dùng `thanh_tien`

### Test:
- [ ] Test mua SP có KM
- [ ] Test mua SP không KM
- [ ] Test xóa KM sau khi mua
- [ ] Test báo cáo doanh thu
- [ ] Test báo cáo lợi nhuận

---

## 📞 FILES CẦN KIỂM TRA TIẾP

1. `lequocanh/payment/notify.php` - Xử lý callback MoMo
2. `lequocanh/administrator/elements_LQA/mdonhang/*` - Quản lý đơn hàng
3. `lequocanh/administrator/elements_LQA/mbaocao/*` - Báo cáo
4. `lequocanh/customer/checkout.php` - Trang thanh toán

**Chạy query kiểm tra database trước, sau đó kiểm tra từng file!**
