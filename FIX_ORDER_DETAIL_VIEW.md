# Fix: Nút "Xem" Chi Tiết Đơn Hàng

## Vấn Đề

### Mô Tả
Khi click nút "Xem" trong cột "Thao tác" của bảng quản lý đơn hàng, không có gì xảy ra hoặc trang không chuyển đến trang chi tiết.

### Nguyên Nhân
Nút "Xem" đang link đến `?req=don_hang&action=view&id=...` nhưng:
1. Không có xử lý cho `action=view` trong `orders_v2.php`
2. Không có trang chi tiết đơn hàng riêng

## Giải Pháp

### Approach: Modal Popup
Thay vì chuyển sang trang mới, sử dụng **Bootstrap Modal** để hiển thị chi tiết đơn hàng ngay trên trang hiện tại.

**Ưu điểm:**
- ✅ Không cần reload trang
- ✅ UX tốt hơn (nhanh, mượt)
- ✅ Dễ quay lại danh sách
- ✅ Có thể xem nhiều đơn hàng liên tiếp

### Các Thay Đổi

#### 1. Thay Đổi Nút "Xem"
**Before:**
```php
<a href="?req=don_hang&action=view&id=<?php echo $order['id']; ?>" 
   class="action-btn btn btn-sm btn-info">
    <i class="fas fa-eye"></i> Xem
</a>
```

**After:**
```php
<button type="button" 
        class="action-btn btn btn-sm btn-info" 
        onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
    <i class="fas fa-eye"></i> Xem
</button>
```

#### 2. Thêm Modal HTML
Thêm modal vào cuối file `orders_v2.php`:
```html
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi Tiết Đơn Hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>
```

#### 3. Thêm JavaScript
```javascript
function viewOrderDetail(orderId) {
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
    modal.show();
    
    // Load order details via AJAX
    fetch('elements_LQA/madmin/get_order_detail.php?id=' + orderId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailContent').innerHTML = html;
        })
        .catch(error => {
            // Handle error
        });
}
```

#### 4. Tạo API Endpoint
**File mới:** `lequocanh/administrator/elements_LQA/madmin/get_order_detail.php`

**Chức năng:**
- Nhận `id` từ query string
- Lấy thông tin đơn hàng từ database
- Lấy danh sách sản phẩm
- Render HTML chi tiết
- Return HTML cho AJAX

## Chi Tiết Trang Chi Tiết Đơn Hàng

### Layout
```
┌─────────────────────────────────────────────────────┐
│  Thông Tin Đơn Hàng    │  Địa Chỉ Giao Hàng        │
│  - Mã đơn hàng         │  - Địa chỉ đầy đủ         │
│  - Khách hàng          │  - Ghi chú                │
│  - Ngày đặt            │  - Thông tin đổi/trả      │
│  - Trạng thái          │                           │
│  - Thanh toán          │                           │
├─────────────────────────────────────────────────────┤
│  Sản Phẩm Đã Đặt                                   │
│  ┌─────────────────────────────────────────────┐   │
│  │ [IMG] Tên sản phẩm          x2    100,000₫ │   │
│  └─────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────┐   │
│  │ [IMG] Tên sản phẩm          x1     50,000₫ │   │
│  └─────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────┤
│  Tổng Tiền                                         │
│  - Tổng tiền hàng:              250,000₫          │
│  - Thuế VAT (8%):                20,000₫          │
│  - Phí vận chuyển:               30,000₫          │
│  ─────────────────────────────────────────────     │
│  TỔNG THANH TOÁN:               300,000₫          │
└─────────────────────────────────────────────────────┘
```

### Thông Tin Hiển Thị

#### Section 1: Thông Tin Đơn Hàng
- Mã đơn hàng (ma_don_hang_text)
- Khách hàng (ma_nguoi_dung)
- Ngày đặt (ngay_tao)
- Trạng thái đơn hàng (trang_thai)
  - pending → Badge vàng "Chờ xác nhận"
  - approved → Badge xanh "Đã duyệt"
  - cancelled → Badge đỏ "Đã hủy"
- Phương thức thanh toán (phuong_thuc_thanh_toan)
  - momo → Badge xanh dương "MoMo"
  - cod → Badge xanh lá "COD"
  - bank_transfer → Badge xanh nước biển "Chuyển khoản"
- Trạng thái thanh toán (trang_thai_thanh_toan)
  - paid → Badge xanh "Đã thanh toán"
  - pending → Badge vàng "Chờ thanh toán"
  - failed → Badge đỏ "Thất bại"

#### Section 2: Địa Chỉ Giao Hàng
- Địa chỉ đầy đủ (dia_chi_giao_hang)
- Ghi chú (ghi_chu) - nếu có
- Thông tin đổi/trả (nếu có)
  - Trạng thái đổi/trả
  - Lý do đổi/trả

#### Section 3: Sản Phẩm
Mỗi sản phẩm hiển thị:
- Hình ảnh (hinhanh)
- Tên sản phẩm (tenhanghoa)
- Đơn giá (gia)
- Số lượng (so_luong)
- Thành tiền (gia × so_luong)

#### Section 4: Tổng Tiền
- Tổng tiền hàng (subtotal)
- Thuế VAT 8% (thue)
- Phí vận chuyển (phi_van_chuyen)
- **TỔNG THANH TOÁN** (tong_tien)

### Styling

#### Colors
- Primary: #667eea (Gradient header)
- Success: #28a745 (Approved status)
- Warning: #ffc107 (Pending status)
- Danger: #dc3545 (Cancelled status)
- Info: #17a2b8 (Return status)

#### Components
- **Order Detail Section:** Background #f8f9fa, rounded corners
- **Product Item:** White background, shadow, rounded
- **Total Section:** Gradient background (purple), white text
- **Badges:** Bootstrap badges với màu phù hợp

## Files Đã Tạo/Sửa

### 1. orders_v2.php
**Location:** `lequocanh/administrator/elements_LQA/madmin/orders_v2.php`

**Changes:**
- Thay link thành button với onclick
- Thêm modal HTML
- Thêm JavaScript function `viewOrderDetail()`

**Lines changed:** ~50 dòng

### 2. get_order_detail.php (NEW)
**Location:** `lequocanh/administrator/elements_LQA/madmin/get_order_detail.php`

**Purpose:** API endpoint để lấy chi tiết đơn hàng

**Features:**
- Session validation
- Database query
- HTML rendering
- Error handling
- Responsive design

**Lines:** ~400 dòng

## Flow Hoạt Động

```
1. User click nút "Xem"
   ↓
2. JavaScript: viewOrderDetail(orderId)
   ↓
3. Show modal với loading spinner
   ↓
4. AJAX request: get_order_detail.php?id=...
   ↓
5. get_order_detail.php:
   - Validate session
   - Get order from database
   - Get order items
   - Calculate totals
   - Render HTML
   ↓
6. Return HTML to JavaScript
   ↓
7. JavaScript: Update modal content
   ↓
8. User sees order details in modal
```

## Security

### Implemented
- ✅ Session validation
- ✅ Input validation (orderId)
- ✅ Prepared statements (SQL injection prevention)
- ✅ HTML escaping (XSS prevention)
- ✅ Permission check (ADMIN or USER)

### Best Practices
- Type casting: `(int)$_GET['id']`
- htmlspecialchars() for all output
- PDO prepared statements
- Error logging (không expose ra user)

## Testing

### Test Cases

#### Test 1: Click Nút "Xem"
1. Truy cập trang quản lý đơn hàng
2. Click nút "Xem" ở bất kỳ đơn hàng nào
3. **Expected:** Modal hiển thị với loading spinner

#### Test 2: Load Chi Tiết
1. Sau khi modal hiển thị
2. Đợi 1-2 giây
3. **Expected:** Chi tiết đơn hàng hiển thị đầy đủ

#### Test 3: Kiểm Tra Thông Tin
1. So sánh thông tin trong modal với database
2. **Expected:** Tất cả thông tin chính xác

#### Test 4: Đóng Modal
1. Click nút X hoặc click outside modal
2. **Expected:** Modal đóng, quay lại danh sách

#### Test 5: Xem Nhiều Đơn Hàng
1. Xem đơn hàng A
2. Đóng modal
3. Xem đơn hàng B
4. **Expected:** Mỗi lần hiển thị đúng thông tin

#### Test 6: Error Handling
1. Xem đơn hàng không tồn tại (sửa URL)
2. **Expected:** Hiển thị thông báo lỗi

### Cách Test

#### Test Thủ Công
```
1. Truy cập: http://localhost:8080/lequocanh/administrator/index.php?req=don_hang
2. Click nút "Xem" (icon mắt màu xanh)
3. Kiểm tra modal hiển thị
4. Kiểm tra thông tin đầy đủ
5. Đóng modal và thử lại với đơn hàng khác
```

#### Test API Trực Tiếp
```
http://localhost:8080/lequocanh/administrator/elements_LQA/madmin/get_order_detail.php?id=60
```

#### Test Console
```javascript
// Mở Console (F12) và chạy:
viewOrderDetail(60);
```

## Troubleshooting

### Modal Không Hiển Thị
**Nguyên nhân:** Bootstrap JS chưa load
**Giải pháp:** Kiểm tra console, đảm bảo Bootstrap JS được load

### Nội Dung Không Load
**Nguyên nhân:** 
- AJAX request failed
- get_order_detail.php có lỗi
- Session expired

**Giải pháp:**
1. Mở Console (F12) → Network tab
2. Kiểm tra request đến get_order_detail.php
3. Xem response có lỗi không
4. Kiểm tra error.log

### Hình Ảnh Không Hiển Thị
**Nguyên nhân:** Path hình ảnh sai

**Giải pháp:**
- Kiểm tra path: `../../public_files/images/`
- Đảm bảo có fallback image: `no-image.png`
- Sử dụng `onerror` attribute

### Thông Tin Không Chính Xác
**Nguyên nhân:** 
- Database không đồng bộ
- Query sai

**Giải pháp:**
1. Kiểm tra database
2. Kiểm tra SQL query trong get_order_detail.php
3. Xem error.log

## Future Enhancements

### Phase 2
- [ ] In hóa đơn (Print invoice)
- [ ] Export PDF
- [ ] Gửi email hóa đơn cho khách
- [ ] Timeline trạng thái đơn hàng
- [ ] Tracking vận chuyển

### Phase 3
- [ ] Edit đơn hàng trong modal
- [ ] Thêm ghi chú nội bộ
- [ ] Upload hình ảnh đóng gói
- [ ] Chat với khách hàng

## Summary

### Vấn Đề
❌ Nút "Xem" không hoạt động

### Giải Pháp
✅ Sử dụng Bootstrap Modal + AJAX để hiển thị chi tiết

### Kết Quả
✅ Click "Xem" → Modal hiển thị chi tiết đơn hàng  
✅ UX tốt, nhanh, không reload trang  
✅ Hiển thị đầy đủ thông tin  
✅ Responsive, đẹp mắt

### Files
- `orders_v2.php` - Thêm modal và JavaScript
- `get_order_detail.php` - API endpoint (NEW)

---

**Status:** ✅ FIXED  
**Date:** 05/12/2025  
**Version:** 2.2  
**Author:** Kiro AI Assistant
