# ⚠️ CẢNH BÁO QUAN TRỌNG VỀ LOGIC GIÁ

## 🔴 CẤU TRÚC GIÁ TRONG HỆ THỐNG

### Bảng `hanghoa`:
```sql
giagoc         -- Giá gốc ban đầu (GIÁ NIÊM YẾT)
giathamkhao    -- Giá tham khảo (được cập nhật từ bảng dongia)
giakhuyenmai   -- Giá khuyến mãi (NULL = không KM)
```

### Bảng `dongia`:
```sql
giaBan         -- Giá bán thực tế
apDung         -- 1 = đang áp dụng, 0 = không áp dụng
ngayApDung     -- Ngày bắt đầu
ngayKetThuc    -- Ngày kết thúc
```

---

## 🎯 LOGIC GIÁ HIỆN TẠI

### 1. Giá Thông Thường (Không KM)
```
Giá hiển thị = giathamkhao (từ bảng dongia)
```

**Flow:**
1. Admin thêm đơn giá mới trong bảng `dongia`
2. `dongiaCls.php` tự động cập nhật `giathamkhao` trong `hanghoa`
3. Frontend hiển thị `giathamkhao`

### 2. Giá Khuyến Mãi
```
Giá hiển thị = giakhuyenmai (nếu có)
Giá gốc gạch ngang = giagoc
```

**Flow:**
1. Admin set `giakhuyenmai` trong bảng `hanghoa`
2. `giagoc` GIỮ NGUYÊN (để hiển thị giá gạch ngang)
3. `giathamkhao` KHÔNG THAY ĐỔI
4. Frontend ưu tiên hiển thị `giakhuyenmai`

---

## ⚠️ VẤN ĐỀ XUNG ĐỘT

### Khi có cả Đơn Giá VÀ Khuyến Mãi:

**Trường hợp 1: Thêm đơn giá mới khi đang có KM**
```php
// NGUY HIỂM! 
dongiaCls->DongiaAdd() 
→ Cập nhật giathamkhao
→ GHI ĐÈ giá khuyến mãi!
```

**Trường hợp 2: Thêm KM khi đang có đơn giá**
```php
// AN TOÀN
UPDATE hanghoa SET giakhuyenmai = 500000
→ Không ảnh hưởng giathamkhao
→ Frontend ưu tiên giakhuyenmai
```

---

## ✅ GIẢI PHÁP AN TOÀN

### Quy Tắc Vàng:

1. **KHÔNG BAO GIỜ** thay đổi `giagoc`
2. **Đơn giá** chỉ cập nhật `giathamkhao`
3. **Khuyến mãi** chỉ cập nhật `giakhuyenmai`
4. **Ưu tiên hiển thị**: `giakhuyenmai` > `giathamkhao` > `giagoc`

### Code Frontend An Toàn:

```php
function getDisplayPrice($product) {
    // Ưu tiên 1: Giá khuyến mãi
    if ($product->giakhuyenmai && $product->giakhuyenmai > 0) {
        return [
            'price' => $product->giakhuyenmai,
            'original' => $product->giagoc,
            'discount' => round((($product->giagoc - $product->giakhuyenmai) / $product->giagoc) * 100)
        ];
    }
    
    // Ưu tiên 2: Giá tham khảo (từ đơn giá)
    if ($product->giathamkhao && $product->giathamkhao > 0) {
        return [
            'price' => $product->giathamkhao,
            'original' => null,
            'discount' => 0
        ];
    }
    
    // Ưu tiên 3: Giá gốc
    return [
        'price' => $product->giagoc,
        'original' => null,
        'discount' => 0
    ];
}
```

### Code Thanh Toán An Toàn:

```php
function getCheckoutPrice($idhanghoa) {
    $product = getProduct($idhanghoa);
    
    // Ưu tiên giá khuyến mãi
    if ($product->giakhuyenmai && $product->giakhuyenmai > 0) {
        return $product->giakhuyenmai;
    }
    
    // Nếu không có KM, dùng giá tham khảo
    if ($product->giathamkhao && $product->giathamkhao > 0) {
        return $product->giathamkhao;
    }
    
    // Fallback: giá gốc
    return $product->giagoc;
}
```

---

## 🔧 CÁCH THÊM KHUYẾN MÃI AN TOÀN

### Bước 1: Kiểm tra giá hiện tại
```php
$product = getProduct($idhanghoa);
$currentPrice = $product->giathamkhao ?: $product->giagoc;
```

### Bước 2: Validate giá khuyến mãi
```php
if ($giakhuyenmai >= $currentPrice) {
    throw new Exception("Giá khuyến mãi phải nhỏ hơn giá hiện tại!");
}

if ($giakhuyenmai <= 0) {
    throw new Exception("Giá khuyến mãi không hợp lệ!");
}
```

### Bước 3: Cập nhật CHÍNH XÁC
```php
// ĐÚNG ✅
$stmt = $db->prepare("UPDATE hanghoa SET giakhuyenmai = ? WHERE idhanghoa = ?");
$stmt->execute([$giakhuyenmai, $idhanghoa]);

// SAI ❌ - KHÔNG làm thế này!
$stmt = $db->prepare("UPDATE hanghoa SET giagoc = ?, giakhuyenmai = ? WHERE idhanghoa = ?");
```

### Bước 4: Xóa khuyến mãi
```php
// ĐÚNG ✅
$stmt = $db->prepare("UPDATE hanghoa SET giakhuyenmai = NULL WHERE idhanghoa = ?");
$stmt->execute([$idhanghoa]);

// Giá sẽ tự động quay về giathamkhao
```

---

## 📊 KIỂM TRA TÍNH TOÀN VẸN DỮ LIỆU

### Query kiểm tra:
```sql
-- Kiểm tra sản phẩm có giá khuyến mãi >= giá gốc (SAI!)
SELECT idhanghoa, tenhanghoa, giagoc, giakhuyenmai
FROM hanghoa
WHERE giakhuyenmai IS NOT NULL 
  AND giakhuyenmai >= giagoc;

-- Kiểm tra sản phẩm có giá âm (SAI!)
SELECT idhanghoa, tenhanghoa, giagoc, giathamkhao, giakhuyenmai
FROM hanghoa
WHERE giagoc < 0 OR giathamkhao < 0 OR giakhuyenmai < 0;

-- Kiểm tra sản phẩm không có giá nào
SELECT idhanghoa, tenhanghoa
FROM hanghoa
WHERE (giagoc IS NULL OR giagoc = 0)
  AND (giathamkhao IS NULL OR giathamkhao = 0);
```

---

## 🚨 CHECKLIST TRƯỚC KHI THÊM KHUYẾN MÃI

- [ ] Kiểm tra `giagoc` có giá trị hợp lệ
- [ ] Kiểm tra `giathamkhao` (nếu có đơn giá)
- [ ] Validate `giakhuyenmai < giagoc`
- [ ] Validate `giakhuyenmai > 0`
- [ ] Test hiển thị trên frontend
- [ ] Test thanh toán với giá KM
- [ ] Test xóa KM (giá quay về bình thường)
- [ ] Kiểm tra báo cáo doanh thu

---

## 📝 LOG THAY ĐỔI GIÁ

Mọi thay đổi giá nên được log:

```php
function logPriceChange($idhanghoa, $oldPrice, $newPrice, $type) {
    $log = [
        'idhanghoa' => $idhanghoa,
        'old_price' => $oldPrice,
        'new_price' => $newPrice,
        'change_type' => $type, // 'dongia', 'khuyenmai', 'remove_km'
        'changed_by' => $_SESSION['ADMIN'],
        'changed_at' => date('Y-m-d H:i:s')
    ];
    
    // Lưu vào database hoặc file log
    error_log("PRICE_CHANGE: " . json_encode($log));
}
```

---

## 🎯 KẾT LUẬN

**3 Nguyên Tắc Vàng:**

1. **Đơn giá** → Cập nhật `giathamkhao`
2. **Khuyến mãi** → Cập nhật `giakhuyenmai`
3. **Giá gốc** → KHÔNG BAO GIỜ thay đổi

**Ưu tiên hiển thị:**
```
giakhuyenmai (nếu có) > giathamkhao > giagoc
```

**Khi xóa khuyến mãi:**
```
SET giakhuyenmai = NULL
→ Giá tự động quay về giathamkhao
```
