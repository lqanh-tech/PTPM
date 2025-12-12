# 📘 HƯỚNG DẪN LẤY API TOKEN GHN

**Cập nhật:** 01/12/2025

---

## 🎯 TỔNG QUAN

Hệ thống hiện đang sử dụng **GHN Mock Service** để test. Để sử dụng GHN API thật, bạn cần:
1. Đăng ký tài khoản GHN
2. Lấy API Token
3. Lấy Shop ID
4. Cập nhật file `.env`

---

## 📝 BƯỚC 1: ĐĂNG KÝ TÀI KHOẢN GHN

### 1.1. Truy cập trang đăng ký
🔗 **Link:** https://khachhang.ghn.vn/register

### 1.2. Điền thông tin đăng ký
- **Số điện thoại:** Số điện thoại của bạn
- **Mật khẩu:** Tạo mật khẩu mạnh
- **Họ tên:** Tên đầy đủ
- **Email:** Email để nhận thông báo

### 1.3. Xác thực tài khoản
- Nhận mã OTP qua SMS
- Nhập mã OTP để kích hoạt tài khoản

### 1.4. Hoàn tất đăng ký
- Đăng nhập vào hệ thống
- Hoàn thiện thông tin cá nhân

---

## 🏪 BƯỚC 2: TẠO CỬA HÀNG (SHOP)

### 2.1. Vào menu "Cửa hàng"
- Sau khi đăng nhập, vào menu **"Cửa hàng"** hoặc **"Shop"**
- Click **"Thêm cửa hàng mới"**

### 2.2. Điền thông tin cửa hàng
**Thông tin bắt buộc:**
- **Tên cửa hàng:** Tên shop của bạn
- **Số điện thoại:** Số điện thoại liên hệ
- **Địa chỉ lấy hàng:**
  - Tỉnh/Thành phố
  - Quận/Huyện
  - Phường/Xã
  - Địa chỉ cụ thể

**Thông tin tùy chọn:**
- Email cửa hàng
- Website
- Mô tả

### 2.3. Lưu Shop ID
Sau khi tạo xong, bạn sẽ thấy **Shop ID** (ví dụ: `123456`)

📝 **Lưu ý:** Shop ID này sẽ dùng để cấu hình trong file `.env`

---

## 🔑 BƯỚC 3: LẤY API TOKEN

### 3.1. Vào menu "Cài đặt"
- Click vào **avatar/tên tài khoản** ở góc trên bên phải
- Chọn **"Cài đặt"** hoặc **"Settings"**

### 3.2. Vào tab "API"
- Trong menu Cài đặt, tìm tab **"API"** hoặc **"Tích hợp API"**
- Click vào tab này

### 3.3. Tạo Token mới
- Click nút **"Tạo Token"** hoặc **"Generate Token"**
- Đặt tên cho Token (ví dụ: "Production API")
- Chọn quyền cho Token:
  - ✅ Tính phí vận chuyển
  - ✅ Tạo đơn hàng
  - ✅ Tra cứu đơn hàng
  - ✅ Hủy đơn hàng

### 3.4. Copy Token
- Token sẽ hiển thị **CHỈ MỘT LẦN**
- Copy và lưu lại ngay
- Token có dạng: `a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6`

⚠️ **QUAN TRỌNG:** 
- Token chỉ hiển thị 1 lần duy nhất
- Nếu mất token, phải tạo token mới
- Không chia sẻ token với người khác

---

## ⚙️ BƯỚC 4: CẤU HÌNH FILE .ENV

### 4.1. Mở file `.env`
File `.env` nằm ở thư mục gốc của project

### 4.2. Tìm section GHN
```env
# GHN (Giao Hàng Nhanh) Shipping API Configuration
GHN_API_TOKEN=your_ghn_api_token_here
GHN_SHOP_ID=your_shop_id_here
GHN_API_ENDPOINT=https://dev-online-gateway.ghn.vn/shiip/public-api/v2
```

### 4.3. Cập nhật thông tin
Thay thế các giá trị:

```env
# GHN (Giao Hàng Nhanh) Shipping API Configuration
GHN_API_TOKEN=a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6
GHN_SHOP_ID=123456
GHN_API_ENDPOINT=https://dev-online-gateway.ghn.vn/shiip/public-api/v2
```

**Giải thích:**
- `GHN_API_TOKEN`: Token vừa lấy ở bước 3
- `GHN_SHOP_ID`: Shop ID từ bước 2
- `GHN_API_ENDPOINT`: Giữ nguyên (đây là endpoint test)

### 4.4. Lưu file
- Lưu file `.env`
- Khởi động lại server (nếu đang chạy)

---

## 🧪 BƯỚC 5: KIỂM TRA KẾT NỐI

### 5.1. Chạy test
Mở trình duyệt và truy cập:
```
http://localhost:8080/test_ghn_service.php
```

### 5.2. Kiểm tra kết quả
Nếu thành công, bạn sẽ thấy:
- ✅ Badge **"REAL API"** (màu xanh)
- ✅ Tất cả tests đều PASS
- ✅ Dữ liệu thật từ GHN

Nếu thất bại:
- ❌ Badge **"MOCK MODE"** (màu vàng)
- Kiểm tra lại Token và Shop ID

---

## 🔄 CHUYỂN ĐỔI GIỮA TEST VÀ PRODUCTION

### Test Environment (Dev)
```env
GHN_API_ENDPOINT=https://dev-online-gateway.ghn.vn/shiip/public-api/v2
```

### Production Environment
```env
GHN_API_ENDPOINT=https://online-gateway.ghn.vn/shiip/public-api/v2
```

⚠️ **Lưu ý:**
- Test environment: Miễn phí, dùng để test
- Production environment: Tính phí thật, dùng khi đã sẵn sàng

---

## 📍 CẤU HÌNH VỊ TRÍ CỬA HÀNG

### Lấy District ID và Ward Code

#### Cách 1: Qua API
```php
$ghn = new GHNService();

// Lấy danh sách tỉnh
$provinces = $ghn->getProvinces();

// Lấy danh sách quận (ví dụ: Hà Nội = 201)
$districts = $ghn->getDistricts(201);

// Lấy danh sách phường (ví dụ: Ba Đình = 1001)
$wards = $ghn->getWards(1001);
```

#### Cách 2: Qua Dashboard GHN
1. Vào **"Cửa hàng"** > Chọn cửa hàng
2. Xem thông tin địa chỉ
3. Lưu lại District ID và Ward Code

### Cập nhật trong code
File: `lequocanh/administrator/elements_LQA/mod/GHNService.php`

```php
// Default shop location
private $fromDistrictId = 1001; // Thay bằng District ID của bạn
private $fromWardCode = '10001'; // Thay bằng Ward Code của bạn
```

---

## ❓ TROUBLESHOOTING

### Lỗi: "Invalid Token"
**Nguyên nhân:**
- Token sai hoặc đã hết hạn
- Token chưa được kích hoạt

**Giải pháp:**
1. Kiểm tra lại token trong file `.env`
2. Tạo token mới nếu cần
3. Đảm bảo không có khoảng trắng thừa

### Lỗi: "Shop not found"
**Nguyên nhân:**
- Shop ID sai
- Shop chưa được kích hoạt

**Giải pháp:**
1. Kiểm tra lại Shop ID
2. Đảm bảo shop đã được tạo và kích hoạt
3. Liên hệ support GHN nếu cần

### Lỗi: "District not supported"
**Nguyên nhân:**
- Khu vực không được GHN hỗ trợ
- District ID sai

**Giải pháp:**
1. Kiểm tra danh sách khu vực GHN hỗ trợ
2. Sử dụng API `getDistricts()` để lấy danh sách chính xác

### Hệ thống vẫn dùng Mock
**Nguyên nhân:**
- Token hoặc Shop ID chưa được cập nhật
- File `.env` chưa được load lại

**Giải pháp:**
1. Kiểm tra file `.env` đã lưu chưa
2. Khởi động lại server
3. Clear cache nếu có

---

## 📞 HỖ TRỢ

### GHN Support
- **Hotline:** 1900 636677
- **Email:** hotro@ghn.vn
- **Website:** https://ghn.vn
- **Tài liệu API:** https://api.ghn.vn/home/docs/detail

### Hệ thống của bạn
- **Test GHN:** `http://localhost:8080/test_ghn_service.php`
- **Test Phase 3:** `http://localhost:8080/test_phase3_ghn.php`
- **File config:** `.env`

---

## ✅ CHECKLIST

Trước khi chuyển sang production, đảm bảo:

- [ ] Đã đăng ký tài khoản GHN
- [ ] Đã tạo cửa hàng và có Shop ID
- [ ] Đã lấy API Token
- [ ] Đã cập nhật file `.env`
- [ ] Test thành công với REAL API
- [ ] Đã cấu hình vị trí cửa hàng đúng
- [ ] Đã test tính phí vận chuyển
- [ ] Đã test tạo đơn hàng (nếu cần)

---

**🎉 CHÚC BẠN TÍCH HỢP THÀNH CÔNG!**

Nếu gặp vấn đề, vui lòng tham khảo phần Troubleshooting hoặc liên hệ support.
