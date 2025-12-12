# Hướng dẫn khởi động Cloudflare Tunnel

## Vấn đề hiện tại
- Cloudflare tunnel đang chạy nhưng domain không truy cập được
- URL: `tennis-manhattan-mothers-wrapped.trycloudflare.com` không hoạt động
- Cần khởi động lại tunnel để lấy domain mới

## Cách khởi động tunnel

### Bước 1: Dừng tunnel hiện tại
```powershell
# Tìm process cloudflared
Get-Process | Where-Object {$_.ProcessName -like "*cloudflare*"}

# Dừng process (thay PID bằng ID thực tế)
Stop-Process -Id 21044 -Force
```

### Bước 2: Khởi động tunnel mới
```powershell
# Di chuyển đến thư mục project
cd D:\PHP_WS

# Khởi động tunnel (chọn 1 trong 2 cách)

# Cách 1: Tunnel đơn giản (quick tunnel)
cloudflared tunnel --url http://localhost:18080

# Cách 2: Tunnel với tên cụ thể
cloudflared tunnel --url http://localhost:18080/lequocanh
```

### Bước 3: Lấy URL mới
Sau khi chạy lệnh trên, terminal sẽ hiển thị URL mới, ví dụ:
```
Your quick Tunnel has been created! Visit it at:
https://random-words-here.trycloudflare.com
```

### Bước 4: Cập nhật URL trong MoMoConfig.php
Mở file `lequocanh/payment/MoMoConfig.php` và cập nhật:

```php
public static function getBaseUrl()
{
    // Thay URL mới vào đây
    return 'https://NEW-TUNNEL-URL.trycloudflare.com';
}
```

## Giải pháp thay thế: Test trên localhost

Nếu không muốn dùng tunnel, có thể test trực tiếp trên localhost:

### Bước 1: Truy cập trang test
```
http://localhost:18080/lequocanh/payment/test_return.php
```

### Bước 2: Chọn đơn hàng có sẵn
- Trang test sẽ hiển thị danh sách đơn hàng gần đây
- Click nút "Test" bên cạnh đơn hàng để test redirect

### Bước 3: Hoặc tạo đơn hàng mới
1. Thêm sản phẩm vào giỏ hàng
2. Chọn thanh toán chuyển khoản hoặc COD
3. Hoàn tất đơn hàng
4. Dùng trang test để giả lập thanh toán MoMo thành công

## Kiểm tra kết quả

Sau khi test, kiểm tra:
1. ✅ Có redirect đến `order_success.php` không?
2. ✅ Trạng thái đơn hàng đã chuyển thành `paid` và `approved` chưa?
3. ✅ Giỏ hàng đã được xóa chưa?

## Lệnh SQL để kiểm tra

```sql
-- Kiểm tra đơn hàng
SELECT * FROM don_hang ORDER BY id DESC LIMIT 5;

-- Kiểm tra giỏ hàng
SELECT * FROM tbl_giohang WHERE user_id = 'khachhang';

-- Cập nhật thủ công nếu cần
UPDATE don_hang 
SET trang_thai_thanh_toan = 'paid', trang_thai = 'approved' 
WHERE id = 123;
```

## Lưu ý quan trọng

⚠️ **Cloudflare Quick Tunnel:**
- Domain thay đổi mỗi khi khởi động lại
- Chỉ dùng cho test, không dùng cho production
- Có thể bị giới hạn request

⚠️ **MoMo Test Environment:**
- Chỉ hoạt động với domain public (không dùng localhost)
- Cần tunnel hoặc ngrok để test
- Hoặc dùng trang test_return.php để giả lập

## Troubleshooting

### Tunnel không khởi động được
```powershell
# Kiểm tra port 18080 có đang chạy không
netstat -ano | findstr :18080

# Khởi động Apache nếu chưa chạy
# Mở XAMPP Control Panel và Start Apache
```

### Domain tunnel không truy cập được
- Đợi 1-2 phút sau khi khởi động tunnel
- Thử refresh browser
- Kiểm tra firewall có block không

### Redirect không hoạt động
- Kiểm tra file `return.php` có lỗi syntax không
- Xem PHP error log
- Dùng trang test để debug
