# LUỒNG HOẠT ĐỘNG PHÍ VẬN CHUYỂN

## 📊 TỔNG QUAN

Khi khách hàng mua hàng, hệ thống sẽ:
1. Lấy thông tin đơn hàng (địa chỉ, trọng lượng, giá trị)
2. Tìm các phương thức vận chuyển phù hợp
3. Tính phí cho từng phương thức
4. Hiển thị cho khách chọn
5. Lưu phí đã chọn vào đơn hàng

---

## 🔄 LUỒNG CHI TIẾT

### BƯỚC 1: Khách hàng vào trang Checkout

```
Khách hàng → Giỏ hàng → Thanh toán
```

**Thông tin có sẵn:**
- Sản phẩm trong giỏ
- Tổng giá trị đơn hàng
- Tổng trọng lượng (từ sản phẩm)

---

### BƯỚC 2: Khách nhập địa chỉ giao hàng

```html
┌─────────────────────────────────────┐
│ 📍 Địa chỉ giao hàng                │
├─────────────────────────────────────┤
│ Tỉnh/TP:    [Hồ Chí Minh ▼]        │
│ Quận/Huyện: [Quận 1 ▼]              │
│ Phường/Xã:  [Phường Bến Nghé ▼]     │
│ Địa chỉ:    [123 Nguyễn Huệ]       │
└─────────────────────────────────────┘
```

**Khi khách chọn xong địa chỉ:**
→ Hệ thống gọi API tính phí

---

### BƯỚC 3: Hệ thống tính phí vận chuyển

#### 3.1. Lấy danh sách phương thức

```sql
SELECT * FROM v_shipping_methods_with_fees 
WHERE is_active = 1
ORDER BY sort_order DESC
```

**Kết quả:**
- Giao hàng tiêu chuẩn (standard)
- Giao hàng nhanh (express)
- Lấy tại cửa hàng (pickup)
- GHN (ghn)

---

#### 3.2. Tính phí cho TỪNG phương thức

**Công thức:**
```sql
SELECT calculate_shipping_fee(
    method_id,        -- ID phương thức
    province_id,      -- Tỉnh/TP khách chọn
    district_id,      -- Quận/Huyện khách chọn
    weight,           -- Tổng trọng lượng đơn hàng
    order_value       -- Tổng giá trị đơn hàng
) as fee
```

**Logic bên trong function:**

```
1. TÌM cấu hình phí phù hợp:
   - Khớp với province_id (hoặc NULL = áp dụng tất cả)
   - Khớp với district_id (hoặc NULL = áp dụng tất cả)
   - Khớp với weight (trong khoảng weight_from → weight_to)
   - Khớp với order_value (trong khoảng order_value_from → order_value_to)
   - Lấy cấu hình có priority CAO NHẤT

2. TÍNH phí:
   fee = base_fee + (weight × fee_per_kg)

3. KIỂM TRA miễn phí:
   NẾU order_value >= min_order_free_ship:
       fee = 0 (MIỄN PHÍ)

4. TRẢ VỀ phí cuối cùng
```

---

#### 3.3. Ví dụ cụ thể

**Đơn hàng:**
- Địa chỉ: Quận 1, TP.HCM
- Trọng lượng: 2kg
- Giá trị: 600,000₫

**Tính phí cho "Giao hàng tiêu chuẩn":**

```
Bước 1: Tìm cấu hình phí
→ Tìm thấy: "Phí cơ bản nội thành"
  - base_fee = 25,000₫
  - fee_per_kg = 0₫
  - min_order_free_ship = 500,000₫
  - priority = 10

Bước 2: Tính phí
→ fee = 25,000 + (2 × 0) = 25,000₫

Bước 3: Kiểm tra miễn phí
→ 600,000₫ >= 500,000₫ → MIỄN PHÍ!
→ fee = 0₫

Kết quả: 0₫ (Miễn phí)
```

**Tính phí cho "Giao hàng nhanh":**

```
Bước 1: Tìm cấu hình phí
→ Tìm thấy: "Phí giao hàng nhanh"
  - base_fee = 45,000₫
  - fee_per_kg = 0₫
  - min_order_free_ship = NULL (không miễn phí)
  - priority = 15

Bước 2: Tính phí
→ fee = 45,000 + (2 × 0) = 45,000₫

Bước 3: Kiểm tra miễn phí
→ Không có điều kiện miễn phí

Kết quả: 45,000₫
```

---

### BƯỚC 4: Hiển thị cho khách hàng

```html
┌─────────────────────────────────────────────────────┐
│ 🚚 Phương thức vận chuyển                           │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ☑️ Giao hàng tiêu chuẩn                             │
│    Giao hàng trong 3-5 ngày làm việc               │
│    ⏰ 3-5 ngày                                      │
│                                                     │
│    💰 25,000₫ → Miễn phí                           │
│    ℹ️ Miễn phí khi đơn hàng ≥ 500,000₫             │
│    ✅ Đơn của bạn đã đủ điều kiện miễn phí!        │
│                                                     │
│    [Chi tiết ▼]                                    │
│    ├─ Phí cơ bản: 25,000₫                          │
│    ├─ Phí theo kg: 0₫ (2kg × 0₫)                   │
│    ├─ Tổng: 25,000₫                                │
│    └─ Miễn phí: -25,000₫ (≥500k)                   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ○ Giao hàng nhanh                                  │
│    Giao hàng trong 1-2 ngày làm việc               │
│    ⏰ 1-2 ngày                                      │
│                                                     │
│    💰 45,000₫                                       │
│                                                     │
│    [Chi tiết ▼]                                    │
│    ├─ Phí cơ bản: 45,000₫                          │
│    ├─ Phí theo kg: 0₫ (2kg × 0₫)                   │
│    └─ Tổng: 45,000₫                                │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ○ Lấy tại cửa hàng                                 │
│    Đến lấy hàng tại cửa hàng                       │
│    ⏰ 0-1 ngày                                      │
│                                                     │
│    💰 Miễn phí                                      │
│    ℹ️ Áp dụng tại TP.HCM, Hà Nội, Đà Nẵng          │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

### BƯỚC 5: Khách chọn phương thức

Khách click chọn → Hệ thống lưu:
- `shipping_method_id` = ID phương thức
- `shipping_fee` = Phí đã tính
- `shipping_method_name` = Tên phương thức

---

### BƯỚC 6: Tính tổng đơn hàng

```
Tạm tính:        600,000₫
Thuế (10%):       60,000₫
Phí vận chuyển:        0₫ (Miễn phí)
─────────────────────────
TỔNG CỘNG:       660,000₫
```

---

### BƯỚC 7: Khách thanh toán

Khi khách bấm "Đặt hàng":

```sql
INSERT INTO don_hang (
    ma_don_hang,
    tong_tien,
    phi_van_chuyen,
    shipping_method_id,
    shipping_method_name,
    dia_chi_giao_hang,
    province_id,
    district_id,
    ward_id,
    ...
) VALUES (
    'DH001',
    660000,
    0,
    1,
    'Giao hàng tiêu chuẩn',
    '123 Nguyễn Huệ, Phường Bến Nghé, Quận 1',
    1,
    1,
    1,
    ...
)
```

---

## 🎯 CÁC TRƯỜNG HỢP ĐẶC BIỆT

### Trường hợp 1: Đơn hàng nặng (>5kg)

**Đơn hàng:**
- Trọng lượng: 7kg
- Giá trị: 300,000₫

**Cấu hình phí:**
```
Phí theo trọng lượng >5kg:
- base_fee = 30,000₫
- fee_per_kg = 8,000₫
- weight_from = 5kg
- weight_to = NULL (không giới hạn)
```

**Tính phí:**
```
fee = 30,000 + (7 × 8,000) = 86,000₫
```

---

### Trường hợp 2: Giao hàng ngoại thành

**Đơn hàng:**
- Địa chỉ: Huyện Củ Chi, TP.HCM
- Trọng lượng: 1kg
- Giá trị: 300,000₫

**Cấu hình phí:**
```
Phí cơ bản ngoại thành:
- base_fee = 50,000₫
- fee_per_kg = 0₫
- min_order_free_ship = 1,000,000₫
- district_id = 5 (Củ Chi)
```

**Tính phí:**
```
fee = 50,000 + (1 × 0) = 50,000₫
Không đủ điều kiện miễn phí (300k < 1tr)
→ Phí cuối: 50,000₫
```

---

### Trường hợp 3: Nhiều cấu hình phí cùng lúc

**Đơn hàng:**
- Địa chỉ: Quận 1, TP.HCM
- Trọng lượng: 3kg
- Giá trị: 400,000₫

**Các cấu hình phí có thể áp dụng:**

```
1. Phí cơ bản nội thành (priority = 10)
   - base_fee = 25,000₫
   - fee_per_kg = 0₫
   - min_order_free_ship = 500,000₫

2. Phí theo trọng lượng 1-5kg (priority = 8)
   - base_fee = 30,000₫
   - fee_per_kg = 10,000₫
   - weight_from = 1kg
   - weight_to = 5kg
```

**Hệ thống chọn:**
→ Chọn cấu hình có **priority CAO NHẤT** = 10
→ Áp dụng "Phí cơ bản nội thành"
→ fee = 25,000₫

---

## 🔍 DEBUG & KIỂM TRA

### Cách kiểm tra phí đang tính đúng không:

**1. Trong Admin:**
- Vào "Cấu hình vận chuyển"
- Click nút "Xem trước trên checkout"
- Nhập thông tin giống đơn hàng thật
- Xem kết quả

**2. Trong Database:**
```sql
-- Kiểm tra phí cho đơn hàng cụ thể
SELECT calculate_shipping_fee(
    1,          -- method_id (1 = standard)
    1,          -- province_id (1 = HCM)
    1,          -- district_id (1 = Quận 1)
    2.0,        -- weight (2kg)
    600000      -- order_value (600k)
) as fee;

-- Kết quả: 0 (miễn phí vì ≥500k)
```

**3. Xem log tính phí:**
```sql
-- Xem cấu hình nào được áp dụng
SELECT 
    sf.*,
    sm.name as method_name
FROM shipping_fees sf
JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
WHERE sm.id = 1
AND (sf.province_id IS NULL OR sf.province_id = 1)
AND (sf.district_id IS NULL OR sf.district_id = 1)
AND (sf.weight_from IS NULL OR 2 >= sf.weight_from)
AND (sf.weight_to IS NULL OR 2 <= sf.weight_to)
ORDER BY sf.priority DESC
LIMIT 1;
```

---

## ⚠️ LƯU Ý QUAN TRỌNG

### 1. Priority (Độ ưu tiên)
- Số càng **CAO** càng được ưu tiên
- Nếu có nhiều cấu hình phù hợp → Chọn priority cao nhất
- Ví dụ: priority 10 > priority 8

### 2. NULL = Áp dụng tất cả
- `province_id = NULL` → Áp dụng cho TẤT CẢ tỉnh
- `district_id = NULL` → Áp dụng cho TẤT CẢ quận
- `weight_to = NULL` → Không giới hạn trọng lượng trên

### 3. Miễn phí ship
- Chỉ miễn phí khi `order_value >= min_order_free_ship`
- Nếu `min_order_free_ship = NULL` → KHÔNG BAO GIỜ miễn phí
- Nếu `min_order_free_ship = 0` → LUÔN LUÔN miễn phí

### 4. Cập nhật real-time
- Khi khách thay đổi địa chỉ → Tính lại phí
- Khi khách thêm/bớt sản phẩm → Tính lại phí
- Khi admin thay đổi cấu hình → Áp dụng ngay cho đơn mới

---

## 📊 SƠ ĐỒ TỔNG QUAN

```
Khách hàng mua hàng
        ↓
Nhập địa chỉ giao hàng
        ↓
Hệ thống lấy:
- province_id
- district_id  
- weight (từ sản phẩm)
- order_value (tổng giá trị)
        ↓
Với MỖI phương thức vận chuyển:
        ↓
Gọi calculate_shipping_fee()
        ↓
Function tìm cấu hình phù hợp:
- Khớp địa chỉ
- Khớp trọng lượng
- Khớp giá trị đơn
- Priority cao nhất
        ↓
Tính phí:
fee = base_fee + (weight × fee_per_kg)
        ↓
Kiểm tra miễn phí:
NẾU order_value >= min_order_free_ship
    → fee = 0
        ↓
Trả về phí cuối cùng
        ↓
Hiển thị cho khách hàng
        ↓
Khách chọn phương thức
        ↓
Lưu vào đơn hàng
        ↓
Hoàn tất
```

---

## 🎓 KẾT LUẬN

### Ưu điểm của hệ thống:
✅ Linh hoạt - Cấu hình phí theo nhiều tiêu chí  
✅ Chính xác - Tính phí tự động từ database  
✅ Minh bạch - Khách thấy rõ cách tính phí  
✅ Dễ quản lý - Admin dễ dàng thay đổi cấu hình  

### Cách hoạt động:
1. Khách nhập địa chỉ
2. Hệ thống tính phí cho TẤT CẢ phương thức
3. Hiển thị cho khách chọn
4. Lưu phí đã chọn vào đơn hàng

### Công thức tính:
```
PHÍ = base_fee + (weight × fee_per_kg)

NẾU order_value >= min_order_free_ship:
    PHÍ = 0 (MIỄN PHÍ)
```

---

**Tài liệu:** Luồng hoạt động phí vận chuyển  
**Ngày:** 01/12/2025  
**Người viết:** Kiro AI
