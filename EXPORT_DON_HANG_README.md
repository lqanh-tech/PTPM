# 📦 HỆ THỐNG XUẤT ĐƠN HÀNG - TỔNG KẾT

## ✅ Đã tạo thành công

Hệ thống xuất đơn hàng hoàn chỉnh với **3 dạng xuất** và **3 định dạng** khác nhau.

---

## 📁 Cấu trúc File đã tạo

```
📦 Project Root
├── 📄 composer.json                                    # Dependencies PHP
├── 📄 HUONG_DAN_EXPORT_DON_HANG.md                    # Hướng dẫn chi tiết
├── 📄 EXPORT_DON_HANG_README.md                       # File này
│
└── 📁 lequocanh/administrator/
    ├── 📁 elements_LQA/mgiohang/
    │   ├── 📁 export/
    │   │   ├── 📄 OrderExporter.php                   # ⭐ Class xử lý dữ liệu
    │   │   ├── 📄 export_pdf.php                      # ⭐ Xuất PDF (TCPDF)
    │   │   ├── 📄 export_excel.php                    # ⭐ Xuất Excel (PhpSpreadsheet)
    │   │   └── 📄 print_invoice.php                   # ⭐ Template in hóa đơn
    │   │
    │   └── 📄 order_management_with_export.php        # 🎯 Demo tích hợp
    │
    ├── 📁 js_LQA/
    │   └── 📄 order_export.js                         # ⭐ JavaScript handler
    │
    └── 📁 css_LQA/
        └── 📄 order_export.css                        # ⭐ Styles
```

---

## 🎯 3 Dạng Xuất

### 1️⃣ Xuất Tổng Hợp (Summary)
**Mục đích:** Báo cáo danh sách tất cả đơn hàng theo bộ lọc

**Nội dung:**
- Danh sách đơn hàng dạng bảng
- Thông tin: Mã đơn, khách hàng, ngày đặt, tổng tiền, trạng thái
- Tổng doanh thu
- Áp dụng bộ lọc: trạng thái, phương thức thanh toán, khoảng thời gian

**Sử dụng:**
```javascript
// PDF
orderExporter.exportSummaryPDF();

// Excel
orderExporter.exportSummaryExcel();
```

**Kết quả:**
- **PDF:** 1 file PDF với bảng tổng hợp
- **Excel:** 1 sheet với danh sách đơn hàng

---

### 2️⃣ Xuất Chi Tiết Đơn (Detailed)
**Mục đích:** Hóa đơn chi tiết 1 hoặc nhiều đơn hàng

**Nội dung:**
- Thông tin công ty
- Thông tin khách hàng
- Danh sách sản phẩm (tên, giá, số lượng, thành tiền)
- Phí vận chuyển
- Tổng tiền
- Phương thức thanh toán

**Sử dụng:**
```javascript
// In hóa đơn (1 đơn)
orderExporter.printInvoice(orderId);

// Xuất PDF (1 hoặc nhiều đơn)
orderExporter.exportSinglePDF(orderId);
orderExporter.exportPDF(); // Các đơn đã chọn

// Xuất Excel (1 hoặc nhiều đơn)
orderExporter.exportSingleExcel(orderId);
orderExporter.exportExcel(); // Các đơn đã chọn
```

**Kết quả:**
- **Print:** Popup HTML preview → In
- **PDF:** 1 file PDF với nhiều trang (mỗi đơn 1 trang)
- **Excel:** 
  - Sheet 1: Tổng quan các đơn
  - Sheet 2+: Chi tiết từng đơn

---

### 3️⃣ Xuất Hàng Loạt (Batch)
**Mục đích:** Xuất nhiều đơn được chọn qua checkbox

**Cách dùng:**
1. Tick checkbox các đơn cần xuất
2. Click "Xuất PDF" hoặc "Xuất Excel"
3. Hệ thống tự động tạo file

**Đặc biệt:**
- Có thể chọn tất cả bằng checkbox "Chọn tất cả"
- Hiển thị số lượng đơn đã chọn
- Nút export tự động enable/disable

---

## 📊 3 Định Dạng

### 🖨️ 1. In Hóa đơn (HTML Print)

**Ưu điểm:**
- Nhanh, không cần thư viện
- Preview trước khi in
- Có thể lưu PDF từ trình duyệt (Ctrl+P → Save as PDF)
- Responsive, đẹp

**Nhược điểm:**
- Phụ thuộc trình duyệt
- Không tự động gửi email

**File:** `print_invoice.php`

**Công nghệ:**
- HTML + CSS
- `@media print` cho print-friendly
- JavaScript `window.print()`

---

### 📄 2. PDF (TCPDF)

**Ưu điểm:**
- File chuẩn, mở được mọi nơi
- Hỗ trợ tiếng Việt tốt (DejaVu Sans)
- Có thể gửi email attachment
- Watermark, security

**Nhược điểm:**
- Cần cài thư viện TCPDF
- Tốn tài nguyên server hơn

**File:** `export_pdf.php`

**Công nghệ:**
- TCPDF library
- Font: DejaVu Sans (Unicode)
- Format: A4, Portrait

**Tính năng:**
- ✅ Hóa đơn chi tiết
- ✅ Báo cáo tổng hợp
- ✅ Xuất hàng loạt (nhiều trang)
- ⚙️ Watermark (có thể thêm)
- ⚙️ Password protect (có thể thêm)

---

### 📊 3. Excel (PhpSpreadsheet)

**Ưu điểm:**
- Dễ chỉnh sửa, tính toán
- Nhiều sheet cho nhiều đơn
- Format đẹp: màu sắc, border, số tiền
- Import vào hệ thống khác dễ dàng

**Nhược điểm:**
- Cần cài thư viện PhpSpreadsheet
- File size lớn hơn PDF

**File:** `export_excel.php`

**Công nghệ:**
- PhpSpreadsheet library
- Format: XLSX (Excel 2007+)

**Cấu trúc:**

**Xuất tổng hợp:**
- 1 sheet duy nhất
- Bảng danh sách đơn hàng
- Tổng doanh thu

**Xuất chi tiết:**
- Sheet 1: Tổng quan (danh sách các đơn)
- Sheet 2+: Chi tiết từng đơn (1 đơn = 1 sheet)

**Tính năng:**
- ✅ Auto-size columns
- ✅ Format tiền tệ (VNĐ)
- ✅ Màu sắc trạng thái
- ✅ Border, header màu
- ✅ Formula (có thể thêm)

---

## 🚀 Cài đặt nhanh

### Bước 1: Cài thư viện

```bash
cd /path/to/project
composer install
```

Hoặc:

```bash
composer require tecnickcom/tcpdf
composer require phpoffice/phpspreadsheet
```

### Bước 2: Tích hợp vào trang quản lý

**Cách 1: Sử dụng file demo có sẵn**

Truy cập:
```
http://localhost/lequocanh/administrator/elements_LQA/mgiohang/order_management_with_export.php
```

**Cách 2: Tích hợp vào trang hiện tại**

Thêm vào `giohangView.php`:

```php
<!-- Trong <head> -->
<link rel="stylesheet" href="../../css_LQA/order_export.css">

<!-- Trước </body> -->
<script src="../../js_LQA/order_export.js"></script>
```

Xem chi tiết trong file `HUONG_DAN_EXPORT_DON_HANG.md`

---

## 📋 Checklist sử dụng

### ✅ Cài đặt
- [ ] Chạy `composer install`
- [ ] Kiểm tra file đã tạo đầy đủ
- [ ] Test truy cập file demo

### ✅ Tích hợp
- [ ] Thêm CSS vào trang quản lý
- [ ] Thêm JavaScript vào trang quản lý
- [ ] Thêm toolbar export
- [ ] Thêm checkbox vào bảng đơn hàng
- [ ] Thêm nút thao tác (In, Xuất)

### ✅ Test chức năng
- [ ] Test in hóa đơn đơn lẻ
- [ ] Test xuất PDF đơn lẻ
- [ ] Test xuất Excel đơn lẻ
- [ ] Test chọn nhiều đơn → Xuất PDF
- [ ] Test chọn nhiều đơn → Xuất Excel
- [ ] Test báo cáo tổng hợp PDF
- [ ] Test báo cáo tổng hợp Excel
- [ ] Test bộ lọc + xuất báo cáo

### ✅ Tùy chỉnh
- [ ] Đổi thông tin công ty
- [ ] Đổi logo (nếu có)
- [ ] Đổi màu sắc theme
- [ ] Test trên mobile

---

## 🎨 Tùy chỉnh

### Thay đổi thông tin công ty

**File:** `export_pdf.php` và `print_invoice.php`

```php
// Tìm và sửa
$pdf->Cell(0, 10, 'TÊN CÔNG TY CỦA BẠN', 0, 1, 'C');
$pdf->MultiCell(0, 5, "Địa chỉ: ...\nĐiện thoại: ...", 0, 'C');
```

### Thêm logo

**PDF:**
```php
$pdf->Image('path/to/logo.png', 15, 10, 30, 0, 'PNG');
```

**HTML Print:**
```html
<img src="path/to/logo.png" alt="Logo" style="width: 100px;">
```

### Thay đổi màu sắc

**File:** `order_export.css`

```css
.btn-export-pdf { background: #dc3545; } /* Đỏ */
.btn-export-excel { background: #28a745; } /* Xanh */
```

---

## 🔧 API Endpoints

### 1. Export PDF
```
GET /lequocanh/administrator/elements_LQA/mgiohang/export/export_pdf.php
```

**Parameters:**

| Tham số | Loại | Mô tả |
|---------|------|-------|
| `type` | string | `single`, `multiple`, `summary` |
| `order_id` | int | ID đơn hàng (type=single) |
| `order_ids` | string | Danh sách ID, ngăn cách bởi dấu phẩy (type=multiple) |
| `status` | string | Lọc theo trạng thái (type=summary) |
| `payment_method` | string | Lọc theo PT thanh toán (type=summary) |
| `date_from` | date | Từ ngày (type=summary) |
| `date_to` | date | Đến ngày (type=summary) |
| `search` | string | Tìm kiếm (type=summary) |

**Ví dụ:**
```
# Xuất PDF đơn lẻ
export_pdf.php?type=single&order_id=123

# Xuất PDF nhiều đơn
export_pdf.php?type=multiple&order_ids=123,124,125

# Báo cáo tổng hợp
export_pdf.php?type=summary&status=completed&date_from=2025-01-01
```

### 2. Export Excel
```
GET /lequocanh/administrator/elements_LQA/mgiohang/export/export_excel.php
```

**Parameters:** Tương tự PDF, nhưng `type` chỉ có `detailed` hoặc `summary`

### 3. Print Invoice
```
GET /lequocanh/administrator/elements_LQA/mgiohang/export/print_invoice.php?order_id=123
```

---

## 🐛 Xử lý lỗi thường gặp

### ❌ Class 'TCPDF' not found

**Nguyên nhân:** Chưa cài TCPDF

**Giải pháp:**
```bash
composer require tecnickcom/tcpdf
```

### ❌ Class 'PhpOffice\PhpSpreadsheet\Spreadsheet' not found

**Nguyên nhân:** Chưa cài PhpSpreadsheet

**Giải pháp:**
```bash
composer require phpoffice/phpspreadsheet
```

### ❌ Font tiếng Việt bị lỗi trong PDF

**Nguyên nhân:** Font không hỗ trợ Unicode

**Giải pháp:** Sử dụng DejaVu Sans (đã có sẵn)
```php
$pdf->SetFont('dejavusans', '', 10);
```

### ❌ File Excel quá lớn / Timeout

**Nguyên nhân:** Xuất quá nhiều đơn

**Giải pháp:** Giới hạn số lượng
```php
$sql .= " LIMIT 500";
```

### ❌ Không download được file

**Nguyên nhân:** Output đã được gửi trước đó

**Giải pháp:** Kiểm tra không có `echo`, `print_r` trước header

---

## 📱 Responsive

Hệ thống tự động responsive:
- Toolbar chuyển dạng dọc trên mobile
- Nút export full width
- Bảng scroll ngang
- Filter stack theo chiều dọc

---

## 🎯 Tính năng nâng cao (có thể thêm)

### 1. Gửi email hóa đơn
```php
$pdf->Output('invoice.pdf', 'F');
// Gửi email với PHPMailer
```

### 2. Watermark PDF
```php
$pdf->SetAlpha(0.3);
$pdf->Text(50, 150, 'BẢN SAO');
```

### 3. Password protect PDF
```php
$pdf->SetProtection(['print'], 'user_pass', 'owner_pass');
```

### 4. Xuất ZIP nhiều PDF
```php
$zip = new ZipArchive();
// Add multiple PDFs
```

### 5. Lưu lịch sử xuất
```sql
CREATE TABLE export_history (...)
```

---

## 📊 So sánh 3 định dạng

| Tiêu chí | Print HTML | PDF | Excel |
|----------|-----------|-----|-------|
| **Tốc độ** | ⚡⚡⚡ Nhanh nhất | ⚡⚡ Trung bình | ⚡ Chậm nhất |
| **Kích thước file** | - | 📦 Nhỏ | 📦📦 Lớn |
| **Chỉnh sửa** | ❌ Không | ❌ Không | ✅ Có |
| **Tiếng Việt** | ✅ Tốt | ✅ Tốt | ✅ Tốt |
| **Gửi email** | ❌ Khó | ✅ Dễ | ✅ Dễ |
| **In ấn** | ✅ Tốt nhất | ✅ Tốt | ⚠️ Cần format |
| **Phân tích dữ liệu** | ❌ Không | ❌ Không | ✅ Tốt nhất |
| **Cài đặt** | ✅ Không cần | ⚙️ Cần TCPDF | ⚙️ Cần PhpSpreadsheet |

**Khuyến nghị:**
- **In nhanh:** Dùng Print HTML
- **Gửi khách hàng:** Dùng PDF
- **Phân tích, báo cáo:** Dùng Excel

---

## 📞 Hỗ trợ

**Yêu cầu hệ thống:**
- PHP >= 7.4
- Extensions: php_zip, php_gd, php_xml, php_mbstring
- Composer
- MySQL/MariaDB

**Kiểm tra:**
```bash
php -v
php -m | grep -E "zip|gd|xml|mbstring"
composer --version
```

---

## 📝 Tóm tắt

✅ **Đã tạo:** 8 files chính  
✅ **3 dạng xuất:** Tổng hợp, Chi tiết, Hàng loạt  
✅ **3 định dạng:** Print, PDF, Excel  
✅ **Tính năng:** Bộ lọc, Checkbox, Responsive  
✅ **Hướng dẫn:** Chi tiết, đầy đủ  

**Bước tiếp theo:**
1. Chạy `composer install`
2. Truy cập file demo
3. Tích hợp vào trang quản lý hiện tại
4. Tùy chỉnh thông tin công ty
5. Test và sử dụng!

---

**Chúc bạn thành công! 🎉**
