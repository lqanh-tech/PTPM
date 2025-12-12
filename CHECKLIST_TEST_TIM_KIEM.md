# Checklist Test Chức Năng Tìm Kiếm Đơn Hàng

## Chuẩn Bị Test
- [ ] Đăng nhập vào trang Admin
- [ ] Truy cập: `http://localhost:8080/lequocanh/administrator/index.php?req=don_hang`
- [ ] Kiểm tra trang load thành công
- [ ] Kiểm tra có hiển thị ô tìm kiếm

## Test 1: Giao Diện Tìm Kiếm

### 1.1. Ô Tìm Kiếm Cơ Bản
- [ ] Ô tìm kiếm hiển thị đúng vị trí (trên filter tabs)
- [ ] Placeholder text hiển thị: "🔍 Tìm kiếm theo mã đơn hàng, tên khách hàng..."
- [ ] Nút "Tìm" hiển thị bên phải ô input
- [ ] Có link "Tìm kiếm nâng cao" bên dưới

### 1.2. Tìm Kiếm Nâng Cao
- [ ] Click "Tìm kiếm nâng cao" → Form mở ra
- [ ] Hiển thị đầy đủ các trường:
  - [ ] Từ ngày
  - [ ] Đến ngày
  - [ ] Giá từ
  - [ ] Giá đến
  - [ ] Phương thức thanh toán (dropdown)
  - [ ] Tỉnh/Thành phố
  - [ ] Nút "Tìm kiếm"
- [ ] Click lại "Tìm kiếm nâng cao" → Form đóng lại

### 1.3. Responsive Design
- [ ] Giao diện hiển thị tốt trên desktop
- [ ] Các trường input có kích thước phù hợp
- [ ] Màu sắc và font chữ dễ đọc

## Test 2: Tìm Kiếm Cơ Bản

### 2.1. Tìm Theo Mã Đơn Hàng
- [ ] Nhập mã đơn hàng đầy đủ (VD: ORDER_1764842812_8063)
- [ ] Nhấn Enter hoặc click "Tìm"
- [ ] Kết quả hiển thị đúng đơn hàng
- [ ] Mã đơn hàng được highlight (đánh dấu vàng)

### 2.2. Tìm Theo Mã Đơn Hàng Một Phần
- [ ] Nhập một phần mã (VD: "1764")
- [ ] Kết quả hiển thị tất cả đơn hàng có chứa "1764"
- [ ] Từ khóa được highlight trong kết quả

### 2.3. Tìm Theo Tên Khách Hàng
- [ ] Nhập tên khách hàng (VD: "khachhang")
- [ ] Kết quả hiển thị đơn hàng của khách đó
- [ ] Tên khách hàng được highlight

### 2.4. Tìm Theo Số Điện Thoại
- [ ] Nhập số điện thoại (VD: "0912345678")
- [ ] Kết quả hiển thị đơn hàng có số điện thoại đó
- [ ] Số điện thoại được highlight (nếu hiển thị)

### 2.5. Tìm Theo Tên Sản Phẩm
- [ ] Nhập tên sản phẩm (VD: "iPhone")
- [ ] Kết quả hiển thị tất cả đơn hàng có chứa sản phẩm đó
- [ ] Kiểm tra chi tiết đơn hàng có đúng sản phẩm

### 2.6. Tìm Kiếm Không Có Kết Quả
- [ ] Nhập từ khóa không tồn tại (VD: "xyz123abc")
- [ ] Hiển thị thông báo "Không có đơn hàng nào"
- [ ] Không có lỗi JavaScript

## Test 3: Tìm Kiếm Nâng Cao

### 3.1. Tìm Theo Khoảng Thời Gian
- [ ] Chọn "Từ ngày": 01/12/2025
- [ ] Chọn "Đến ngày": 05/12/2025
- [ ] Click "Tìm kiếm"
- [ ] Kết quả chỉ hiển thị đơn hàng trong khoảng thời gian này
- [ ] Kiểm tra ngày tạo của các đơn hàng

### 3.2. Tìm Theo Khoảng Giá
- [ ] Nhập "Giá từ": 100000
- [ ] Nhập "Giá đến": 1000000
- [ ] Click "Tìm kiếm"
- [ ] Kết quả chỉ hiển thị đơn hàng trong khoảng giá này
- [ ] Kiểm tra tổng tiền của các đơn hàng

### 3.3. Tìm Theo Phương Thức Thanh Toán
- [ ] Chọn "MoMo" trong dropdown
- [ ] Click "Tìm kiếm"
- [ ] Kết quả chỉ hiển thị đơn hàng thanh toán MoMo
- [ ] Kiểm tra badge phương thức thanh toán

### 3.4. Tìm Theo Địa Chỉ
- [ ] Nhập "Hà Nội" vào trường Tỉnh/TP
- [ ] Click "Tìm kiếm"
- [ ] Kết quả hiển thị đơn hàng giao đến Hà Nội
- [ ] Kiểm tra địa chỉ giao hàng

### 3.5. Tìm Kiếm Kết Hợp
- [ ] Nhập từ khóa: "ORDER"
- [ ] Chọn thời gian: 30 ngày qua
- [ ] Chọn giá từ: 10000
- [ ] Chọn phương thức: MoMo
- [ ] Click "Tìm kiếm"
- [ ] Kết quả thỏa mãn TẤT CẢ điều kiện

## Test 4: Active Search Tags

### 4.1. Hiển Thị Tags
- [ ] Thực hiện tìm kiếm với từ khóa "iPhone"
- [ ] Tag hiển thị: "Từ khóa: iPhone [×]"
- [ ] Thêm bộ lọc thời gian
- [ ] Tag mới xuất hiện: "Thời gian: ... → ... [×]"

### 4.2. Xóa Từng Tag
- [ ] Click vào dấu × trên tag "Từ khóa"
- [ ] Tag biến mất
- [ ] Kết quả tự động cập nhật
- [ ] URL thay đổi (không có param 'search')

### 4.3. Xóa Tất Cả Tags
- [ ] Thực hiện tìm kiếm với nhiều điều kiện
- [ ] Click nút "Xóa tất cả"
- [ ] Tất cả tags biến mất
- [ ] Trang reload về trạng thái ban đầu
- [ ] Hiển thị tất cả đơn hàng

## Test 5: Kết Hợp Với Filter Tabs

### 5.1. Tìm Kiếm + Tab "Chờ xác nhận"
- [ ] Click tab "Chờ xác nhận"
- [ ] Nhập từ khóa tìm kiếm
- [ ] Kết quả chỉ hiển thị đơn hàng chờ xác nhận

### 5.2. Tìm Kiếm + Tab "Đã duyệt"
- [ ] Click tab "Đã duyệt"
- [ ] Thực hiện tìm kiếm
- [ ] Kết quả chỉ hiển thị đơn hàng đã duyệt

### 5.3. Chuyển Tab Khi Đang Tìm Kiếm
- [ ] Thực hiện tìm kiếm
- [ ] Chuyển sang tab khác
- [ ] Bộ lọc tìm kiếm vẫn được giữ nguyên
- [ ] Kết quả cập nhật theo tab mới

## Test 6: Performance & UX

### 6.1. Tốc Độ Tìm Kiếm
- [ ] Tìm kiếm cơ bản: < 1 giây
- [ ] Tìm kiếm theo sản phẩm: < 2 giây
- [ ] Tìm kiếm kết hợp: < 2 giây
- [ ] Không có lag khi nhập liệu

### 6.2. Loading Indicator
- [ ] Click "Tìm kiếm"
- [ ] Nút hiển thị "Đang tìm..." với spinner
- [ ] Nút bị disable trong lúc tìm
- [ ] Sau khi có kết quả, nút trở về bình thường

### 6.3. Keyboard Shortcuts
- [ ] Nhấn Enter trong ô tìm kiếm → Submit form
- [ ] Không cần click chuột vào nút "Tìm"

### 6.4. Browser Back/Forward
- [ ] Thực hiện tìm kiếm
- [ ] Nhấn nút Back của browser
- [ ] Trang quay về trạng thái trước đó
- [ ] Nhấn Forward → Quay lại kết quả tìm kiếm

## Test 7: Edge Cases

### 7.1. Ký Tự Đặc Biệt
- [ ] Nhập ký tự đặc biệt: `!@#$%^&*()`
- [ ] Không có lỗi SQL injection
- [ ] Kết quả trả về an toàn

### 7.2. Khoảng Trắng
- [ ] Nhập nhiều khoảng trắng: "  ORDER  "
- [ ] Hệ thống tự động trim
- [ ] Tìm kiếm hoạt động bình thường

### 7.3. Số Âm
- [ ] Nhập giá âm: -1000
- [ ] Hệ thống xử lý đúng (không crash)

### 7.4. Ngày Không Hợp Lệ
- [ ] Chọn "Từ ngày" > "Đến ngày"
- [ ] Hệ thống vẫn hoạt động (hoặc hiển thị cảnh báo)

### 7.5. Tìm Kiếm Rỗng
- [ ] Không nhập gì, click "Tìm"
- [ ] Hiển thị tất cả đơn hàng
- [ ] Không có lỗi

## Test 8: Tương Thích Trình Duyệt

### 8.1. Chrome
- [ ] Tất cả chức năng hoạt động
- [ ] Giao diện hiển thị đúng
- [ ] Không có lỗi console

### 8.2. Firefox
- [ ] Tất cả chức năng hoạt động
- [ ] Giao diện hiển thị đúng
- [ ] Không có lỗi console

### 8.3. Edge
- [ ] Tất cả chức năng hoạt động
- [ ] Giao diện hiển thị đúng
- [ ] Không có lỗi console

## Test 9: Không Ảnh Hưởng Chức Năng Cũ

### 9.1. Thống Kê Cards
- [ ] Cards thống kê vẫn hiển thị đúng
- [ ] Số liệu chính xác
- [ ] Hover effect hoạt động

### 9.2. Filter Tabs
- [ ] Tất cả tabs hoạt động bình thường
- [ ] Số lượng hiển thị đúng
- [ ] Active state đúng

### 9.3. Bảng Đơn Hàng
- [ ] Bảng hiển thị đầy đủ cột
- [ ] Dữ liệu hiển thị chính xác
- [ ] Hover effect hoạt động

### 9.4. Các Nút Thao Tác
- [ ] Nút "Xem" hoạt động
- [ ] Nút "Duyệt" hoạt động
- [ ] Nút "Hủy" hoạt động
- [ ] Nút "Duyệt đổi/trả" hoạt động

### 9.5. Nút "Làm mới"
- [ ] Click "Làm mới"
- [ ] Trang reload
- [ ] Bộ lọc tìm kiếm bị xóa (nếu có)

## Test 10: Error Handling

### 10.1. Database Error
- [ ] Tắt database
- [ ] Thực hiện tìm kiếm
- [ ] Hiển thị thông báo lỗi thân thiện
- [ ] Không crash trang

### 10.2. Timeout
- [ ] Tìm kiếm với điều kiện phức tạp
- [ ] Nếu quá lâu, có thông báo
- [ ] Có thể cancel được

### 10.3. Session Expired
- [ ] Để trang idle lâu
- [ ] Thực hiện tìm kiếm
- [ ] Redirect về login (nếu session hết hạn)

## Kết Quả Test

### Tổng Số Test Cases: ~100+

**Passed:** _____ / _____  
**Failed:** _____ / _____  
**Skipped:** _____ / _____

### Bugs Phát Hiện
1. _______________________________________________
2. _______________________________________________
3. _______________________________________________

### Ghi Chú
_______________________________________________
_______________________________________________
_______________________________________________

### Người Test
- **Tên:** _____________________
- **Ngày:** _____________________
- **Thời gian:** _____________________

### Kết Luận
- [ ] ✅ PASS - Sẵn sàng deploy
- [ ] ⚠️ PASS WITH ISSUES - Cần fix bugs nhỏ
- [ ] ❌ FAIL - Cần fix bugs nghiêm trọng

---

**Lưu ý:** Đánh dấu [x] vào checkbox khi test pass
