# 🔧 BÁO CÁO SỬA LỖI TỔNG TIỀN VÀ THANH TOÁN MOMO

## 📋 Vấn đề phát hiện

### 1. Lỗi tổng tiền sai (Đơn hàng #70)
- **Hiện tượng**: Tổng tiền hiển thị 62,326,000đ thay vì 57,128,000đ
- **Chênh lệch**: 5,198,000đ (đúng bằng VAT 10%)
- **Nguyên nhân**: Khi cập nhật phí vận chuyển, session `total_amount` bị ghi đè mà **không trừ coupon discount**

### 2. Lỗi MoMo vượt giới hạn
- **Hiện tượng**: "Số tiền không hợp lệ (1,000 - 50,000,000 VND)"
- **Nguyên nhân**: Tổng tiền 57,128,000đ > 50,000,000đ (giới hạn MoMo)

---

## ✅ Các file đã sửa

### 1. `lequocanh/administrator/elements_LQA/mgiohang/update_shipping_session.php`
**Vấn đề**: Không trừ coupon khi tính tổng tiền mới
```php
// TRƯỚC (SAI)
$newTotal = $subtotal + $vatAmount + $shippingFee;

// SAU (ĐÚNG)
$couponDiscount = $_SESSION['coupon_discount'] ?? 0;
$newTotal = $subtotal + $vatAmount + $shippingFee - $couponDiscount;
```

### 2. `lequocanh/administrator/elements_LQA/mgiohang/calculate_shipping_api.php`
**Vấn đề**: Không trừ coupon khi tính tổng tiền
```php
// TRƯỚC (SAI)
$totalAmount = $subtotal + $vatAmount + $shippingFee;

// SAU (ĐÚNG)
$couponDiscount = $_SESSION['coupon_discount'] ?? 0;
$totalAmount = $subtotal + $vatAmount + $shippingFee - $couponDiscount;
```

### 3. `lequocanh/administrator/elements_LQA/mgiohang/momo_payment.php`
**Cải tiến**:
- Thêm lưu `coupon_code`, `coupon_discount` vào database
- Thêm lưu `shipping_method`, `shipping_method_name`, `estimated_delivery`
- Cải thiện thông báo lỗi khi vượt giới hạn MoMo

### 4. `lequocanh/administrator/elements_LQA/mgiohang/checkout.php`
**Cải tiến**: Sửa sessionStorage để tính đúng coupon discount

---

## 🔧 Công thức tính tổng tiền đúng

```
Tổng tiền = Tiền hàng (Subtotal) + VAT + Phí vận chuyển - Coupon Discount
```

---

## 📝 Hướng dẫn sửa đơn hàng cũ

### Bước 1: Chạy script kiểm tra
Truy cập: `http://your-domain/fix_order_total_calculation.php`

Script sẽ:
1. Liệt kê tất cả đơn hàng có thông tin thuế/phí
2. So sánh tổng tiền trong DB với tổng tiền tính đúng
3. Hiển thị các đơn hàng có lỗi

### Bước 2: Sửa đơn hàng
Thêm `?fix=1` vào URL để sửa tự động:
`http://your-domain/fix_order_total_calculation.php?fix=1`

---

## ⚠️ Lưu ý về giới hạn MoMo

MoMo có giới hạn thanh toán:
- **Tối thiểu**: 1,000 VND
- **Tối đa**: 50,000,000 VND

Với đơn hàng vượt 50 triệu, hệ thống sẽ hiển thị thông báo:
> "Số tiền X VND vượt quá giới hạn MoMo (tối đa 50,000,000 VND). Vui lòng chọn phương thức thanh toán khác như Chuyển khoản ngân hàng hoặc COD."

---

## 🧪 Kiểm tra sau khi sửa

1. **Test áp dụng coupon**: Kiểm tra tổng tiền có trừ đúng coupon không
2. **Test thay đổi phương thức vận chuyển**: Kiểm tra tổng tiền có cập nhật đúng không
3. **Test thanh toán MoMo**: Với đơn hàng < 50 triệu
4. **Test thanh toán Bank Transfer/COD**: Với đơn hàng > 50 triệu

---

## 📅 Ngày sửa: 06/12/2025
