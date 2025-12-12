# HƯỚNG DẪN CẬP NHẬT CLOUDFLARE TUNNEL URL

## 🚀 Cách nhanh nhất

### Bước 1: Chạy Cloudflare Tunnel
```bash
.\cloudflared.exe tunnel --url http://localhost:20080
```

Bạn sẽ thấy output như:
```
Your quick Tunnel has been created! Visit it at:
https://abc-def-xyz.trycloudflare.com
```

### Bước 2: Copy URL và chạy script
```bash
php update_cloudflare_url.php https://abc-def-xyz.trycloudflare.com
```

### Bước 3: Clear cache và test
- Nhấn **Ctrl+Shift+Delete** để xóa cache
- Hoặc **Ctrl+F5** để hard refresh
- Mở Console (F12) và gõ: `console.log(window.BASE_URL)`

## 📝 Cách thủ công

### Bước 1: Mở file `.env`

### Bước 2: Tìm và sửa dòng BASE_URL
```env
# Cũ
BASE_URL=https://old-url.trycloudflare.com

# Mới
BASE_URL=https://new-url.trycloudflare.com
```

### Bước 3: Đảm bảo USE_CLOUDFLARE_TUNNEL=true
```env
USE_CLOUDFLARE_TUNNEL=true
```

### Bước 4: Lưu file và clear cache

## ✅ Kiểm tra

### 1. Trong Browser Console (F12):
```javascript
console.log(window.BASE_URL);
// Phải hiển thị URL mới
```

### 2. Trong Network tab:
- Mở trang hỗ trợ
- Kiểm tra API calls
- URL phải là: `https://new-url.trycloudflare.com/lequocanh/api/...`

### 3. Test các trang:
- ✅ User Support: `https://new-url.trycloudflare.com/lequocanh/customer/support.php`
- ✅ Admin: `https://new-url.trycloudflare.com/lequocanh/administrator/`
- ✅ Review Management: Admin → Quản lý bình luận
- ✅ Support Tickets: Admin → Hỗ trợ khách hàng

## 🔧 Troubleshooting

### Vẫn thấy URL cũ?
```bash
# Clear cache
Ctrl+Shift+Delete

# Hard refresh
Ctrl+F5

# Kiểm tra .env đã lưu chưa
cat .env | grep BASE_URL
```

### Lỗi "Không thể tải chi tiết yêu cầu"?
- Đảm bảo tunnel đang chạy
- Kiểm tra URL trong .env đúng chưa
- Clear cache và refresh lại

### window.BASE_URL undefined?
- Kiểm tra file đã inject script chưa
- Clear cache và refresh
- Kiểm tra Console có lỗi JavaScript không

## 📚 Tài liệu chi tiết

Xem file `BAO_CAO_SUA_LOI_CLOUDFLARE_URL.md` để biết thêm chi tiết về cách hệ thống hoạt động.
