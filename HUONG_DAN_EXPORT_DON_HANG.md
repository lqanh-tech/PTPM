# HƯỚNG DẪN SỬ DỤNG CHỨC NĂNG XUẤT ĐƠN HÀNG

## 📋 Tổng quan

Hệ thống xuất đơn hàng hỗ trợ **3 dạng xuất** với **3 định dạng** khác nhau:

### Dạng xuất:
1. **Xuất tổng hợp** - Danh sách tất cả đơn hàng theo bộ lọc
2. **Xuất chi tiết đơn** - Hóa đơn 1 đơn hàng cụ thể
3. **Xuất hàng loạt** - Nhiều đơn được chọn (checkbox)

### Định dạng:
- 🖨️ **In hóa đơn** (HTML Print)
- 📄 **PDF** (TCPDF)
- 📊 **Excel** (PhpSpreadsheet)

---

## 🚀 Cài đặt

### Bước 1: Cài đặt thư viện PHP

```bash
composer install
```

Hoặc cài thủ công:

```bash
composer require tecnickcom/tcpdf
composer require phpoffice/phpspreadsheet
```

### Bước 2: Kiểm tra cấu trúc file

```
lequocanh/administrator/elements_LQA/mgiohang/
├── export/
│   ├── OrderExporter.php          # Class xử lý dữ liệu
│   ├── export_pdf.php             # Xuất PDF
│   ├── export_excel.php           # Xuất Excel
│   └── print_invoice.php          # Template in hóa đơn
├── js_LQA/
│   └── order_export.js            # JavaScript handler
└── css_LQA/
    └── order_export.css           # Styles
```

### Bước 3: Tích hợp vào trang quản lý đơn hàng

Thêm vào file `giohangView.php` (hoặc trang quản lý đơn hàng):

```php
<!-- Trong <head> -->
<link rel="stylesheet" href="../../css_LQA/order_export.css">

<!-- Trước </body> -->
<script src="../../js_LQA/order_export.js"></script>
```

---

## 💻 Sử dụng

### 1. Thêm Toolbar Export

```html
<!-- Export Toolbar -->
<div class="export-toolbar">
    <div class="export-toolbar-left">
        <!-- Checkbox chọn tất cả -->
        <div class="select-all-container">
            <input type="checkbox" id="select-all-orders">
            <label for="select-all-orders">Chọn tất cả</label>
        </div>
        
        <span class="selected-count" id="selected-count" style="display: none;">
            Đã chọn: <span id="count-number">0</span>
        </span>
    </div>
    
    <div class="export-toolbar-right">
        <!-- Xuất chi tiết các đơn đã chọn -->
        <button class="btn-export btn-export-pdf" id="btn-export-pdf" disabled>
            <i class="fas fa-file-pdf"></i> Xuất PDF
        </button>
        
        <button class="btn-export btn-export-excel" id="btn-export-excel" disabled>
            <i class="fas fa-file-excel"></i> Xuất Excel
        </button>
        
        <!-- Xuất tổng hợp theo bộ lọc -->
        <button class="btn-export btn-export-summary" id="btn-export-summary-pdf">
            <i class="fas fa-file-pdf"></i> Báo cáo PDF
        </button>
        
        <button class="btn-export btn-export-summary" id="btn-export-summary-excel">
            <i class="fas fa-file-excel"></i> Báo cáo Excel
        </button>
    </div>
</div>
```

### 2. Thêm Checkbox vào bảng đơn hàng

```html
<table class="orders-table">
    <thead>
        <tr>
            <th><input type="checkbox" id="select-all-orders"></th>
            <th>ID</th>
            <th>Mã đơn hàng</th>
            <th>Khách hàng</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td class="order-checkbox-cell">
                <input type="checkbox" class="order-checkbox" value="<?= $order['id'] ?>">
            </td>
            <td><?= $order['id'] ?></td>
            <td><?= $order['ma_don_hang_text'] ?></td>
            <td><?= $order['ten_khach_hang'] ?></td>
            <td><?= number_format($order['tong_tien']) ?> đ</td>
            <td><?= $order['trang_thai'] ?></td>
            <td class="order-actions">
                <!-- Nút xem -->
                <button class="btn-action" onclick="viewOrder(<?= $order['id'] ?>)">
                    <i class="fas fa-eye"></i> Xem
                </button>
                
                <!-- Nút in -->
                <button class="btn-action btn-action-print" 
                        onclick="orderExporter.printInvoice(<?= $order['id'] ?>)">
                    <i class="fas fa-print"></i> In
                </button>
                
                <!-- Dropdown export -->
                <div class="dropdown">
                    <button class="btn-action btn-action-export" onclick="toggleExportMenu(<?= $order['id'] ?>)">
                        <i class="fas fa-download"></i> Xuất
                    </button>
                    <div class="export-dropdown-menu" id="export-menu-<?= $order['id'] ?>" style="display: none;">
                        <button class="export-menu-item" onclick="orderExporter.exportSinglePDF(<?= $order['id'] ?>)">
                            <i class="fas fa-file-pdf"></i> Xuất PDF
                        </button>
                        <button class="export-menu-item" onclick="orderExporter.exportSingleExcel(<?= $order['id'] ?>)">
                            <i class="fas fa-file-excel"></i> Xuất Excel
                        </button>
                    </div>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

### 3. Thêm bộ lọc (tùy chọn)

```html
<div class="filter-section">
    <div class="filter-row">
        <div class="filter-group">
            <label>Trạng thái</label>
            <select id="filter-status">
                <option value="">Tất cả</option>
                <option value="pending">Chờ xác nhận</option>
                <option value="processing">Đang xử lý</option>
                <option value="completed">Hoàn thành</option>
                <option value="cancelled">Đã hủy</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Phương thức thanh toán</label>
            <select id="filter-payment">
                <option value="">Tất cả</option>
                <option value="cod">COD</option>
                <option value="momo">MoMo</option>
                <option value="bank">Chuyển khoản</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Từ ngày</label>
            <input type="date" id="filter-date-from">
        </div>
        
        <div class="filter-group">
            <label>Đến ngày</label>
            <input type="date" id="filter-date-to">
        </div>
        
        <div class="filter-group">
            <label>Tìm kiếm</label>
            <input type="text" id="search-input" placeholder="Mã đơn, tên KH, SĐT...">
        </div>
        
        <button class="btn-filter" onclick="applyFilters()">
            <i class="fas fa-filter"></i> Lọc
        </button>
    </div>
</div>
```

---

## 📊 Các loại xuất

### 1. In hóa đơn (Print)

**Sử dụng:**
```javascript
orderExporter.printInvoice(orderId);
```

**Đặc điểm:**
- Mở popup preview trước khi in
- Template HTML đẹp, chuẩn hóa đơn
- Hỗ trợ CSS `@media print`
- Có thể lưu thành PDF từ trình duyệt

### 2. Xuất PDF

**Xuất đơn lẻ:**
```javascript
orderExporter.exportSinglePDF(orderId);
```

**Xuất nhiều đơn:**
```javascript
// Chọn checkbox → Click "Xuất PDF"
orderExporter.exportPDF();
```

**Xuất báo cáo tổng hợp:**
```javascript
orderExporter.exportSummaryPDF();
```

**Đặc điểm:**
- Sử dụng TCPDF
- Hỗ trợ tiếng Việt (font DejaVu Sans)
- Format chuẩn hóa đơn VAT
- Có thể gửi email trực tiếp

### 3. Xuất Excel

**Xuất chi tiết:**
```javascript
orderExporter.exportExcel(); // Các đơn đã chọn
```

**Xuất tổng hợp:**
```javascript
orderExporter.exportSummaryExcel();
```

**Đặc điểm:**
- Sử dụng PhpSpreadsheet
- Sheet 1: Tổng quan
- Sheet 2+: Chi tiết từng đơn
- Format: màu sắc, số tiền, ngày tháng
- Hỗ trợ công thức Excel

---

## 🎨 Tùy chỉnh

### Thay đổi thông tin công ty

Sửa trong `export_pdf.php` và `print_invoice.php`:

```php
// Logo và thông tin công ty
$pdf->Cell(0, 10, 'TÊN CÔNG TY CỦA BẠN', 0, 1, 'C');
$pdf->MultiCell(0, 5, "Địa chỉ: ...\nĐiện thoại: ... | Email: ...", 0, 'C');
```

### Thay đổi màu sắc

Sửa trong `order_export.css`:

```css
.btn-export-pdf {
    background: #dc3545; /* Đỏ */
}

.btn-export-excel {
    background: #28a745; /* Xanh lá */
}
```

### Thêm watermark PDF

Trong `export_pdf.php`:

```php
// Thêm watermark
$pdf->SetAlpha(0.3);
$pdf->SetFont('dejavusans', 'B', 50);
$pdf->SetTextColor(200, 200, 200);
$pdf->Text(50, 150, 'BẢN SAO', 0, false, true, 0, 0, 'C');
$pdf->SetAlpha(1);
```

---

## 🔧 API Endpoints

### Export PDF
```
GET /lequocanh/administrator/elements_LQA/mgiohang/export/export_pdf.php

Parameters:
- type: single|multiple|summary
- order_id: ID đơn hàng (type=single)
- order_ids: Danh sách ID (type=multiple, ngăn cách bởi dấu phẩy)
- status, payment_method, date_from, date_to, search (type=summary)
```

### Export Excel
```
GET /lequocanh/administrator/elements_LQA/mgiohang/export/export_excel.php

Parameters:
- type: detailed|summary
- order_ids: Danh sách ID (type=detailed)
- Các filter tương tự PDF (type=summary)
```

### Print Invoice
```
GET /lequocanh/administrator/elements_LQA/mgiohang/export/print_invoice.php

Parameters:
- order_id: ID đơn hàng
```

---

## 🐛 Xử lý lỗi

### Lỗi: "Class 'TCPDF' not found"

**Giải pháp:**
```bash
composer require tecnickcom/tcpdf
```

### Lỗi: "Class 'PhpOffice\PhpSpreadsheet\Spreadsheet' not found"

**Giải pháp:**
```bash
composer require phpoffice/phpspreadsheet
```

### Lỗi: Font tiếng Việt bị lỗi trong PDF

**Giải pháp:**
Sử dụng font DejaVu Sans (đã có sẵn trong TCPDF):
```php
$pdf->SetFont('dejavusans', '', 10);
```

### Lỗi: File Excel quá lớn

**Giải pháp:**
Giới hạn số lượng đơn hàng:
```php
// Trong OrderExporter.php
$sql .= " LIMIT 1000";
```

---

## 📱 Responsive

Hệ thống tự động responsive trên mobile:
- Toolbar chuyển thành dạng dọc
- Nút export full width
- Bảng có thể scroll ngang

---

## ✅ Checklist triển khai

- [ ] Cài đặt composer dependencies
- [ ] Copy các file export vào đúng thư mục
- [ ] Thêm CSS và JS vào trang quản lý
- [ ] Thêm toolbar export
- [ ] Thêm checkbox vào bảng đơn hàng
- [ ] Thêm nút thao tác (In, Xuất)
- [ ] Test xuất PDF đơn lẻ
- [ ] Test xuất Excel nhiều đơn
- [ ] Test báo cáo tổng hợp
- [ ] Tùy chỉnh thông tin công ty
- [ ] Test trên mobile

---

## 🎯 Tính năng nâng cao (tùy chọn)

### 1. Gửi email hóa đơn

```php
// Trong export_pdf.php
$pdf->Output('invoice.pdf', 'F'); // Lưu file
// Gửi email với attachment
```

### 2. Lưu lịch sử xuất

```sql
CREATE TABLE export_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    export_type VARCHAR(50),
    order_ids TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3. Xuất ZIP nhiều PDF

```php
$zip = new ZipArchive();
$zip->open('invoices.zip', ZipArchive::CREATE);
foreach ($orders as $order) {
    $pdf = generatePDF($order);
    $zip->addFromString("invoice_{$order['id']}.pdf", $pdf);
}
$zip->close();
```

---

## 📞 Hỗ trợ

Nếu gặp vấn đề, kiểm tra:
1. PHP version >= 7.4
2. Extension: php_zip, php_gd, php_xml
3. Quyền ghi file trong thư mục vendor/
4. Session đã được start

---

**Tác giả:** LeQuocAnh Shop Development Team  
**Phiên bản:** 1.0.0  
**Cập nhật:** <?= date('d/m/Y') ?>
