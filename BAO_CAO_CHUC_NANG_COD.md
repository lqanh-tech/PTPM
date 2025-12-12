# BÁO CÁO CHỨC NĂNG XÁC NHẬN GIAO HÀNG COD

## Tổng quan

Đã thêm chức năng xác nhận giao hàng và thanh toán cho đơn hàng COD (Cash On Delivery).

## Các trạng thái đơn hàng COD

| Trạng thái | Mô tả | Ai thực hiện |
|------------|-------|--------------|
| `pending` | Chờ xác nhận | Khách đặt hàng |
| `approved` | Đã duyệt, đang giao | Admin duyệt |
| `delivered` | Đã giao hàng | Admin xác nhận giao |
| `completed` | Hoàn tất | Khách/Admin xác nhận |
| `cancelled` | Đã hủy | Admin hủy |

## Luồng xử lý

### Phía Admin:
1. **Duyệt đơn** (`pending` → `approved`)
2. **Xác nhận đã giao** (`approved` → `delivered`) - Tùy chọn
3. **Hoàn tất đơn** (`approved/delivered` → `completed`) - Đánh dấu đã thanh toán

### Phía Khách hàng:
1. Khi đơn ở trạng thái `approved` hoặc `delivered`
2. Click **"Xác nhận đã nhận hàng & thanh toán"**
3. Đơn chuyển sang `completed` và `trang_thai_thanh_toan = paid`

## Files đã thay đổi

### Tạo mới:
- `lequocanh/administrator/elements_LQA/mgiohang/confirmDeliveryAct.php` - Xử lý xác nhận giao hàng

### Cập nhật:
- `lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php` - Thêm nút xác nhận cho khách
- `lequocanh/administrator/elements_LQA/madmin/orders_v2.php` - Thêm action và nút cho admin
- `lequocanh/administrator/elements_LQA/madmin/orders.php` - Thêm action và nút cho admin

### Database:
Đã thêm các cột mới vào bảng `don_hang`:
- `ngay_giao_hang` - Ngày giao hàng
- `ngay_nhan_hang` - Ngày khách xác nhận nhận hàng
- `ghi_chu_admin` - Ghi chú của admin

## Giao diện

### Khách hàng (orderDetailView.php):
- Hiển thị trạng thái chi tiết (Đã duyệt, Đã giao, Hoàn tất)
- Hiển thị trạng thái thanh toán (Đã thanh toán / Chưa thanh toán)
- Nút "Xác nhận đã nhận hàng & thanh toán" khi đơn đã duyệt/giao

### Admin (orders.php, orders_v2.php):
- Badge trạng thái mới: Đã giao (primary), Hoàn tất (success)
- Badge thanh toán COD: Đã TT / Chưa TT
- Nút "Đã giao" - Xác nhận đã giao hàng
- Nút "Hoàn tất" - Xác nhận đơn hoàn tất và thanh toán

## Thông báo

Khi có thay đổi trạng thái, hệ thống sẽ:
1. Gửi thông báo trong app cho khách hàng
2. Gửi email thông báo (nếu có email)

## Lưu ý

- Chức năng này chỉ áp dụng cho đơn hàng COD
- Đơn MoMo/Bank Transfer tự động hoàn tất khi thanh toán thành công
- Không ảnh hưởng đến các chức năng khác (đổi/trả, đánh giá, v.v.)
