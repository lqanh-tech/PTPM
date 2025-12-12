# CẬP NHẬT CHECKOUT - HIỂN THỊ PHÍ ĐỘNG

**Ngày:** 01/12/2025  
**Trạng thái:** ✅ HOÀN THÀNH

---

## 🎯 VẤN ĐỀ

Checkout hiện tại:
- ❌ Hiển thị phí HARD-CODE (25,000₫, 45,000₫)
- ❌ KHÔNG tính phí từ database
- ❌ KHÔNG hiển thị điều kiện miễn phí
- ❌ KHÔNG hiển thị chi tiết tính phí

---

## ✅ GIẢI PHÁP

Tạo `shipping_method_selector_v2.php`:
- ✅ Tính phí ĐỘNG từ function `calculate_shipping_fee()`
- ✅ Hiển thị điều kiện miễn phí rõ ràng
- ✅ Hiển thị chi tiết tính phí (collapse)
- ✅ Responsive design đẹp

---

## 📊 SO SÁNH TRƯỚC/SAU

### TRƯỚC (shipping_method_selector.php)

```php
// Lấy phí từ base_fee cố định
$method['base_fee']  // Hard-code

// Hiển thị
<span>25,000₫</span>  // Không rõ tại sao
```

**Vấn đề:**
- Phí không thay đổi theo địa chỉ
- Không tính theo trọng lượng
- Không kiểm tra điều kiện miễn phí

---

### SAU (shipping_method_selector_v2.php)

```php
// Tính phí ĐỘNG
$stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
$stmt->execute([
    $method['id'],      // Phương thức
    $provinceId,        // Tỉnh khách chọn
    $districtId,        // Quận khách chọn
    $cartWeight,        // Trọng lượng giỏ hàng
    $cartValue          // Giá trị giỏ hàng
]);

// Hiển thị
<div class="fee-amount">
    <?php if ($method['is_free']): ?>
        Miễn phí
        <div class="fee-condition">
            Đơn ≥ 500,000₫
        </div>
    <?php else: ?>
        25,000₫
        <div class="fee-condition">
            Miễn phí từ 500,000₫
        </div>
    <?php endif; ?>
</div>

// Chi tiết (collapse)
<div class="fee-details">
    Phí cơ bản: 25,000₫
    Phí theo kg: 0₫ (2kg × 0₫)
    Tổng: 25,000₫
    Miễn phí: -25,000₫ (≥500k)
    ─────────────────
    Phí cuối: 0₫
</div>
```

**Ưu điểm:**
- ✅ Phí chính xác theo địa chỉ
- ✅ Tính theo trọng lượng
- ✅ Kiểm tra miễn phí tự động
- ✅ Hiển thị rõ ràng, minh bạch

---

## 🎨 GIAO DIỆN MỚI

### Hiển thị phương thức

```
┌─────────────────────────────────────────────────────┐
│ ☑️  [Icon]  Giao hàng tiêu chuẩn          Miễn phí  │
│             Giao hàng trong 3-5 ngày                │
│             ⏰ 3-5 ngày (ngày làm việc)              │
│             🎁 Đơn ≥ 500,000₫                        │
│                                                     │
│             [▼ Chi tiết]                            │
└─────────────────────────────────────────────────────┘
```

### Khi click "Chi tiết"

```
┌─────────────────────────────────────────────────────┐
│ ☑️  [Icon]  Giao hàng tiêu chuẩn          Miễn phí  │
│             Giao hàng trong 3-5 ngày                │
│             ⏰ 3-5 ngày (ngày làm việc)              │
│             🎁 Đơn ≥ 500,000₫                        │
│                                                     │
│   ┌─────────────────────────────────────────────┐  │
│   │ 📄 Chi tiết tính phí:                       │  │
│   │                                             │  │
│   │ Phí cơ bản:           25,000₫              │  │
│   │ Phí theo trọng lượng:      0₫              │  │
│   │                      (2kg × 0₫)            │  │
│   │ ─────────────────────────────              │  │
│   │ Tổng phí:             25,000₫              │  │
│   │                                             │  │
│   │ 🎁 Miễn phí vì đơn hàng 600,000₫ ≥ 500k   │  │
│   │                                             │  │
│   │ ┌─────────────────────────────────────┐   │  │
│   │ │ Phí cuối cùng:          Miễn phí    │   │  │
│   │ └─────────────────────────────────────┘   │  │
│   └─────────────────────────────────────────────┘  │
│                                                     │
│             [▲ Ẩn chi tiết]                         │
└─────────────────────────────────────────────────────┘
```

---

## 🔧 CÁC THAY ĐỔI

### 1. File mới: `shipping_method_selector_v2.php`

**Tính năng:**
- Lấy thông tin giỏ hàng từ session
- Tính phí cho TỪNG phương thức
- Hiển thị phí động
- Hiển thị điều kiện miễn phí
- Chi tiết tính phí (collapse)
- Responsive design

**Session cần có:**
```php
$_SESSION['cart_weight']  // Tổng trọng lượng (kg)
$_SESSION['cart_total']   // Tổng giá trị (VNĐ)
$_SESSION['province_id']  // Tỉnh đã chọn
$_SESSION['district_id']  // Quận đã chọn
```

---

### 2. Cập nhật `checkout.php`

```php
// TRƯỚC
<?php include 'shipping_method_selector.php'; ?>

// SAU
<?php include 'shipping_method_selector_v2.php'; ?>
```

---

## 📋 CHECKLIST TRIỂN KHAI

### Bước 1: Đảm bảo session có dữ liệu
- [ ] `$_SESSION['cart_weight']` - Tính từ sản phẩm
- [ ] `$_SESSION['cart_total']` - Tổng giá trị giỏ
- [ ] `$_SESSION['province_id']` - Từ address selector
- [ ] `$_SESSION['district_id']` - Từ address selector

### Bước 2: Test tính phí
- [ ] Test với đơn < ngưỡng miễn phí
- [ ] Test với đơn ≥ ngưỡng miễn phí
- [ ] Test với nhiều địa chỉ khác nhau
- [ ] Test với nhiều trọng lượng khác nhau

### Bước 3: Test giao diện
- [ ] Hiển thị đúng trên desktop
- [ ] Hiển thị đúng trên mobile
- [ ] Click chọn phương thức hoạt động
- [ ] Toggle chi tiết hoạt động
- [ ] Cập nhật tổng tiền đúng

---

## 🎓 VÍ DỤ CỤ THỂ

### Trường hợp 1: Đơn đủ điều kiện miễn phí

**Input:**
- Giỏ hàng: 600,000₫
- Trọng lượng: 2kg
- Địa chỉ: Quận 1, TP.HCM

**Output:**
```
☑️ Giao hàng tiêu chuẩn          Miễn phí
   Giao hàng trong 3-5 ngày
   ⏰ 3-5 ngày (ngày làm việc)
   🎁 Đơn ≥ 500,000₫
   
   [Chi tiết ▼]
   Phí cơ bản: 25,000₫
   Phí theo kg: 0₫
   Tổng: 25,000₫
   Miễn phí: -25,000₫ (≥500k)
   ─────────────────
   Phí cuối: 0₫
```

---

### Trường hợp 2: Đơn chưa đủ miễn phí

**Input:**
- Giỏ hàng: 300,000₫
- Trọng lượng: 2kg
- Địa chỉ: Quận 1, TP.HCM

**Output:**
```
☑️ Giao hàng tiêu chuẩn          25,000₫
   Giao hàng trong 3-5 ngày
   ⏰ 3-5 ngày (ngày làm việc)
   Miễn phí từ 500,000₫
   
   [Chi tiết ▼]
   Phí cơ bản: 25,000₫
   Phí theo kg: 0₫
   Tổng: 25,000₫
   ─────────────────
   Phí cuối: 25,000₫
```

---

### Trường hợp 3: Đơn nặng

**Input:**
- Giỏ hàng: 300,000₫
- Trọng lượng: 7kg
- Địa chỉ: Quận 1, TP.HCM

**Output:**
```
☑️ Giao hàng tiêu chuẩn          86,000₫
   Giao hàng trong 3-5 ngày
   ⏰ 3-5 ngày (ngày làm việc)
   Miễn phí từ 500,000₫
   
   [Chi tiết ▼]
   Phí cơ bản: 30,000₫
   Phí theo kg: 56,000₫ (7kg × 8,000₫)
   Tổng: 86,000₫
   ─────────────────
   Phí cuối: 86,000₫
```

---

## ✅ KẾT QUẢ

### Trước:
- ❌ Phí hard-code
- ❌ Không rõ ràng
- ❌ Không minh bạch

### Sau:
- ✅ Phí tính động
- ✅ Rõ ràng, chi tiết
- ✅ Minh bạch 100%
- ✅ Khách hiểu rõ tại sao phải trả phí
- ✅ Khách biết cách để được miễn phí

---

**Files đã tạo:**
1. `shipping_method_selector_v2.php` - Component mới
2. `CAP_NHAT_CHECKOUT_HIEN_THI_PHI.md` - Tài liệu này

**Files đã sửa:**
1. `checkout.php` - Đổi include sang v2

**Trạng thái:** ✅ SẴN SÀNG TEST
