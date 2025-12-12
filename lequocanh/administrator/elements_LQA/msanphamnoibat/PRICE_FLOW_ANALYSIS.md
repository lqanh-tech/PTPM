# 🔍 PHÂN TÍCH FLOW GIÁ KHUYẾN MÃI

## 📊 FLOW HOÀN CHỈNH

```
[1] Sản phẩm có KM
    ↓
[2] Thêm vào giỏ hàng → Lưu giá vào tbl_giohang
    ↓
[3] Xem giỏ hàng → Hiển thị giá
    ↓
[4] Thanh toán → Tạo đơn hàng (don_hang + chi_tiet_don_hang)
    ↓
[5] Báo cáo doanh thu → Tính từ chi_tiet_don_hang
```

---

## 🔧 ĐÃ SỬA

### 1. Giỏ Hàng (giohangCls.php)

**Query cũ:**
```sql
SELECT g.product_id, g.quantity, h.tenhanghoa, h.giathamkhao, h.hinhanh
FROM tbl_giohang g
LEFT JOIN hanghoa h ON g.product_id = h.idhanghoa
```

**Query mới (ĐÃ SỬA):**
```sql
SELECT g.product_id, g.quantity, h.tenhanghoa, 
       COALESCE(h.giakhuyenmai, h.giathamkhao) as giathamkhao,
       h.giakhuyenmai,
       h.hinhanh
FROM tbl_giohang g
LEFT JOIN hanghoa h ON g.product_id = h.idhanghoa
```

**Logic:**
- `COALESCE(h.giakhuyenmai, h.giathamkhao)` → Ưu tiên giá KM
- Nếu `giakhuyenmai` NULL → dùng `giathamkhao`
- Giá này được dùng để tính tổng tiền giỏ hàng

---

## ⚠️ CẦN KIỂM TRA

### 2. Bảng `tbl_giohang`

**Cấu trúc hiện tại:**
```sql
CREATE TABLE tbl_giohang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255),
    session_id VARCHAR(255),
    product_id INT,
    quantity INT
);
```

**Vấn đề:** 
- ❌ KHÔNG lưu giá vào giỏ hàng
- ✅ Lấy giá real-time từ bảng `hanghoa`

**Ưu điểm:**
- Giá luôn cập nhật theo giá hiện tại
- Nếu admin thay đổi giá KM → giỏ hàng tự động cập nhật

**Nhược điểm:**
- Nếu khách hàng thêm vào giỏ với giá KM, sau đó admin xóa KM → giá thay đổi

**Giải pháp đề xuất:**
```sql
-- OPTION 1: Lưu giá vào giỏ hàng (snapshot)
ALTER TABLE tbl_giohang 
ADD COLUMN price_snapshot DECIMAL(15,2) NULL COMMENT 'Giá tại thời điểm thêm vào giỏ';

-- OPTION 2: Giữ nguyên (lấy giá real-time)
-- Không thay đổi gì
```

**Khuyến nghị:** OPTION 2 (giữ nguyên) - Giá luôn chính xác nhất

---

### 3. Bảng `don_hang` và `chi_tiet_don_hang`

**Cần kiểm tra:**
```sql
-- Xem cấu trúc bảng
DESCRIBE don_hang;
DESCRIBE chi_tiet_don_hang;
```

**Cấu trúc mong đợi:**
```sql
CREATE TABLE chi_tiet_don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_don_hang INT,
    ma_san_pham INT,
    so_luong INT,
    don_gia DECIMAL(15,2),  -- ← GIÁ TẠI THỜI ĐIỂM MUA (quan trọng!)
    thanh_tien DECIMAL(15,2)
);
```

**Logic đúng khi tạo đơn hàng:**
```php
// Lấy giá từ giỏ hàng (đã ưu tiên KM)
$cart = $giohang->getCart();

foreach ($cart as $item) {
    // $item['giathamkhao'] đã là giá KM (nếu có)
    $dongia = $item['giathamkhao'];
    $thanhtien = $dongia * $item['quantity'];
    
    // Lưu vào chi_tiet_don_hang
    $sql = "INSERT INTO chi_tiet_don_hang 
            (ma_don_hang, ma_san_pham, so_luong, don_gia, thanh_tien) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt->execute([$ma_don_hang, $item['product_id'], $item['quantity'], $dongia, $thanhtien]);
}
```

**⚠️ QUAN TRỌNG:**
- `don_gia` trong `chi_tiet_don_hang` phải là giá **TẠI THỜI ĐIỂM MUA**
- KHÔNG JOIN lại với bảng `hanghoa` để lấy giá
- Vì giá có thể thay đổi sau khi đặt hàng

---

### 4. Báo Cáo Doanh Thu

**Query ĐÚNG:**
```sql
-- Doanh thu theo sản phẩm
SELECT 
    h.tenhanghoa,
    SUM(ct.so_luong) as total_sold,
    SUM(ct.thanh_tien) as total_revenue,  -- ← Dùng thanh_tien từ chi_tiet_don_hang
    AVG(ct.don_gia) as avg_price
FROM chi_tiet_don_hang ct
JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
JOIN don_hang dh ON ct.ma_don_hang = dh.id
WHERE dh.trang_thai_thanh_toan = 'paid'
GROUP BY ct.ma_san_pham;
```

**Query SAI (TRÁNH):**
```sql
-- ❌ SAI - Không dùng giá hiện tại từ bảng hanghoa
SELECT 
    h.tenhanghoa,
    SUM(ct.so_luong) as total_sold,
    SUM(ct.so_luong * h.giathamkhao) as total_revenue  -- ← SAI!
FROM chi_tiet_don_hang ct
JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa;
```

---

## ✅ CHECKLIST KIỂM TRA

### Giỏ Hàng:
- [x] Query ưu tiên `giakhuyenmai`
- [ ] Test thêm SP có KM vào giỏ
- [ ] Test hiển thị giá đúng
- [ ] Test tính tổng tiền đúng

### Thanh Toán:
- [ ] Kiểm tra cấu trúc bảng `chi_tiet_don_hang`
- [ ] Có cột `don_gia` không?
- [ ] Lưu giá đúng vào `don_gia`
- [ ] Tính `thanh_tien` = `don_gia` × `so_luong`

### Báo Cáo:
- [ ] Dùng `thanh_tien` từ `chi_tiet_don_hang`
- [ ] KHÔNG JOIN với `hanghoa.giathamkhao`
- [ ] Test báo cáo doanh thu chính xác

---

## 🔍 QUERY KIỂM TRA

### 1. Kiểm tra giá trong giỏ hàng
```sql
SELECT 
    g.product_id,
    h.tenhanghoa,
    h.giathamkhao as gia_thuong,
    h.giakhuyenmai as gia_km,
    COALESCE(h.giakhuyenmai, h.giathamkhao) as gia_hien_thi,
    g.quantity,
    COALESCE(h.giakhuyenmai, h.giathamkhao) * g.quantity as tong_tien
FROM tbl_giohang g
LEFT JOIN hanghoa h ON g.product_id = h.idhanghoa
WHERE g.user_id = 'test_user';
```

### 2. Kiểm tra giá trong đơn hàng
```sql
SELECT 
    dh.id as ma_don_hang,
    dh.ngay_dat,
    ct.ma_san_pham,
    h.tenhanghoa,
    ct.don_gia as gia_mua,
    h.giathamkhao as gia_hien_tai,
    ct.so_luong,
    ct.thanh_tien
FROM don_hang dh
JOIN chi_tiet_don_hang ct ON dh.id = ct.ma_don_hang
JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
ORDER BY dh.id DESC
LIMIT 10;
```

### 3. Kiểm tra doanh thu
```sql
SELECT 
    DATE(dh.ngay_dat) as ngay,
    COUNT(DISTINCT dh.id) as so_don_hang,
    SUM(ct.thanh_tien) as doanh_thu,
    SUM(ct.so_luong) as so_san_pham
FROM don_hang dh
JOIN chi_tiet_don_hang ct ON dh.id = ct.ma_don_hang
WHERE dh.trang_thai_thanh_toan = 'paid'
GROUP BY DATE(dh.ngay_dat)
ORDER BY ngay DESC;
```

---

## 🎯 KẾT LUẬN

### Đã sửa:
1. ✅ Giỏ hàng ưu tiên giá khuyến mãi

### Cần kiểm tra:
1. ⏳ Cấu trúc bảng `chi_tiet_don_hang`
2. ⏳ Code tạo đơn hàng
3. ⏳ Code báo cáo doanh thu

### Nguyên tắc vàng:
1. **Giỏ hàng**: Lấy giá real-time (ưu tiên KM)
2. **Đơn hàng**: Lưu giá snapshot vào `don_gia`
3. **Báo cáo**: Dùng `thanh_tien` từ `chi_tiet_don_hang`

---

## 📝 FILES CẦN KIỂM TRA TIẾP

1. `lequocanh/payment/momo_process.php` - Xử lý thanh toán
2. `lequocanh/administrator/elements_LQA/mdonhang/*` - Quản lý đơn hàng
3. `lequocanh/administrator/elements_LQA/mbaocao/*` - Báo cáo doanh thu
