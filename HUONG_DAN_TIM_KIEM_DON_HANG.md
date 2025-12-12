# Hướng Dẫn Sử Dụng Chức Năng Tìm Kiếm Đơn Hàng

## Tổng Quan
Chức năng tìm kiếm đơn hàng cho phép Admin tìm kiếm nhanh chóng và chính xác các đơn hàng theo nhiều tiêu chí khác nhau.

## Các Tính Năng Tìm Kiếm

### 1. Tìm Kiếm Cơ Bản (Quick Search)
Ô tìm kiếm chính cho phép tìm theo:
- **Mã đơn hàng**: VD: "ORDER_1764", "1764"
- **Tên khách hàng**: VD: "Nguyễn Văn A", "khachhang"
- **Số điện thoại**: VD: "0912345678"
- **Tên sản phẩm**: VD: "iPhone 15", "Samsung"

**Cách sử dụng:**
1. Nhập từ khóa vào ô tìm kiếm
2. Nhấn Enter hoặc click nút "Tìm"
3. Kết quả sẽ hiển thị ngay lập tức

### 2. Tìm Kiếm Nâng Cao (Advanced Search)

#### 2.1. Tìm Theo Khoảng Thời Gian
- **Từ ngày**: Chọn ngày bắt đầu
- **Đến ngày**: Chọn ngày kết thúc
- Tìm tất cả đơn hàng được tạo trong khoảng thời gian này

**Ví dụ:**
- Tìm đơn hàng trong tháng này: Từ 01/12/2025 đến 31/12/2025
- Tìm đơn hàng hôm nay: Từ 05/12/2025 đến 05/12/2025

#### 2.2. Tìm Theo Khoảng Giá
- **Giá từ**: Giá trị đơn hàng tối thiểu (VD: 100000)
- **Giá đến**: Giá trị đơn hàng tối đa (VD: 5000000)

**Ví dụ:**
- Đơn hàng dưới 500k: Giá từ 0, Giá đến 500000
- Đơn hàng trên 1 triệu: Giá từ 1000000, Giá đến để trống

#### 2.3. Tìm Theo Phương Thức Thanh Toán
Chọn một trong các phương thức:
- **MoMo**: Thanh toán qua ví MoMo
- **COD**: Thanh toán khi nhận hàng
- **Chuyển khoản**: Chuyển khoản ngân hàng

#### 2.4. Tìm Theo Địa Chỉ
Nhập tên tỉnh/thành phố để tìm đơn hàng giao đến khu vực đó:
- VD: "Hà Nội", "TP.HCM", "Đà Nẵng"

### 3. Tìm Kiếm Kết Hợp
Bạn có thể kết hợp nhiều tiêu chí cùng lúc:

**Ví dụ 1:** Tìm đơn hàng MoMo trong tháng này
- Từ khóa: để trống
- Từ ngày: 01/12/2025
- Đến ngày: 31/12/2025
- Phương thức: MoMo

**Ví dụ 2:** Tìm đơn hàng iPhone giá trên 10 triệu ở Hà Nội
- Từ khóa: "iPhone"
- Giá từ: 10000000
- Địa chỉ: "Hà Nội"

## Quản Lý Bộ Lọc

### Active Search Tags
Khi có bộ lọc đang hoạt động, hệ thống hiển thị các tag:
```
Đang lọc: [Từ khóa: "iPhone" ×] [Thời gian: 01/12 → 31/12 ×] [Giá: 100,000₫ - 500,000₫ ×]
```

### Xóa Bộ Lọc
- **Xóa từng tag**: Click vào dấu × trên tag
- **Xóa tất cả**: Click nút "Xóa tất cả"

## Highlight Kết Quả
Từ khóa tìm kiếm sẽ được highlight (đánh dấu vàng) trong kết quả để dễ nhận biết.

## Tips & Tricks

### 1. Tìm Kiếm Nhanh
- Sử dụng phím tắt: Nhấn Enter trong ô tìm kiếm
- Không cần nhập chính xác, hệ thống tìm theo từ khóa gần đúng

### 2. Tìm Kiếm Chính Xác
- Nhập đầy đủ mã đơn hàng: "ORDER_1764842812_8063"
- Sử dụng tìm kiếm nâng cao để thu hẹp kết quả

### 3. Tìm Đơn Hàng Theo Sản Phẩm
- Nhập tên sản phẩm vào ô tìm kiếm cơ bản
- Hệ thống sẽ tìm tất cả đơn hàng có chứa sản phẩm đó

### 4. Báo Cáo Theo Thời Gian
- Sử dụng tìm kiếm theo khoảng thời gian
- Kết hợp với filter trạng thái để xem doanh thu

### 5. Tìm Đơn Hàng Lớn
- Sử dụng bộ lọc giá: Giá từ 5000000 (5 triệu)
- Kết hợp với trạng thái "Đã duyệt" để xem đơn hàng có giá trị

## Kết Hợp Với Filter Tabs

Tìm kiếm hoạt động độc lập với các tab filter:
- **Tất cả**: Tìm trong tất cả đơn hàng
- **Chờ xác nhận**: Tìm trong đơn hàng chờ duyệt
- **Đã duyệt**: Tìm trong đơn hàng đã duyệt
- **Đã hủy**: Tìm trong đơn hàng đã hủy
- **Yêu cầu đổi/trả**: Tìm trong đơn hàng có yêu cầu đổi/trả

**Ví dụ:** 
- Chọn tab "Chờ xác nhận"
- Tìm kiếm "iPhone"
- Kết quả: Tất cả đơn hàng chờ duyệt có chứa iPhone

## Các Trường Hợp Sử Dụng Thực Tế

### Case 1: Khách Hàng Gọi Hỏi Đơn Hàng
**Tình huống:** Khách hàng gọi đến hỏi về đơn hàng nhưng chỉ nhớ số điện thoại

**Giải pháp:**
1. Nhập số điện thoại vào ô tìm kiếm
2. Xem danh sách đơn hàng của khách
3. Click "Xem" để xem chi tiết

### Case 2: Kiểm Tra Doanh Thu Tháng
**Tình huống:** Cần xem doanh thu tháng 11/2025

**Giải pháp:**
1. Click "Tìm kiếm nâng cao"
2. Từ ngày: 01/11/2025
3. Đến ngày: 30/11/2025
4. Chọn tab "Đã duyệt"
5. Xem tổng doanh thu ở card thống kê

### Case 3: Tìm Đơn Hàng Có Vấn Đề
**Tình huống:** Tìm đơn hàng MoMo chưa thanh toán

**Giải pháp:**
1. Chọn tab "Chờ xác nhận"
2. Click "Tìm kiếm nâng cao"
3. Phương thức: MoMo
4. Xem danh sách và xử lý

### Case 4: Phân Tích Theo Khu Vực
**Tình huống:** Xem đơn hàng từ Hà Nội trong tháng

**Giải pháp:**
1. Click "Tìm kiếm nâng cao"
2. Từ ngày: 01/12/2025
3. Đến ngày: 31/12/2025
4. Địa chỉ: "Hà Nội"
5. Xem kết quả

## Lưu Ý Quan Trọng

### Performance
- Tìm kiếm theo sản phẩm có thể chậm hơn nếu có nhiều đơn hàng
- Nên kết hợp với bộ lọc thời gian để tăng tốc độ

### Độ Chính Xác
- Tìm kiếm không phân biệt chữ hoa/thường
- Tìm kiếm theo từ khóa gần đúng (LIKE %keyword%)
- Kết quả được sắp xếp theo thời gian mới nhất

### Bảo Mật
- Chỉ Admin và nhân viên có quyền mới truy cập được
- Tất cả thao tác tìm kiếm đều được log

## Troubleshooting

### Không Tìm Thấy Kết Quả
1. Kiểm tra lại từ khóa (có thể sai chính tả)
2. Thử bỏ bớt điều kiện tìm kiếm
3. Kiểm tra tab filter (có thể đang ở tab sai)
4. Xóa tất cả bộ lọc và thử lại

### Kết Quả Quá Nhiều
1. Thêm điều kiện tìm kiếm nâng cao
2. Thu hẹp khoảng thời gian
3. Chọn tab filter cụ thể

### Tìm Kiếm Chậm
1. Thu hẹp khoảng thời gian tìm kiếm
2. Tránh tìm kiếm quá chung chung (VD: "a", "1")
3. Sử dụng mã đơn hàng chính xác nếu có

## Test Chức Năng

Để test tất cả các tính năng tìm kiếm:
```
http://localhost:8080/test_order_search.php
```

Test này sẽ kiểm tra:
- ✓ Tìm theo mã đơn hàng
- ✓ Tìm theo tên khách hàng
- ✓ Tìm theo tên sản phẩm
- ✓ Tìm theo khoảng thời gian
- ✓ Tìm theo khoảng giá
- ✓ Tìm theo phương thức thanh toán
- ✓ Tìm theo địa chỉ
- ✓ Tìm kiếm kết hợp

## Hỗ Trợ

Nếu gặp vấn đề với chức năng tìm kiếm:
1. Kiểm tra error.log
2. Chạy test_order_search.php để kiểm tra database
3. Xóa cache browser (Ctrl + Shift + Delete)
4. Liên hệ support với thông tin chi tiết

---

**Phiên bản:** 2.0  
**Cập nhật:** 05/12/2025  
**Tác giả:** Kiro AI Assistant
