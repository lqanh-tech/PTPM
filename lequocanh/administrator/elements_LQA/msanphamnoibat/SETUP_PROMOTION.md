# 🔧 HƯỚNG DẪN THIẾT LẬP CHỨC NĂNG KHUYẾN MÃI

## ⚠️ QUAN TRỌNG: Thêm Cột Database

Để sử dụng chức năng khuyến mãi, bạn cần thêm cột `giakhuyenmai` vào bảng `hanghoa`.

---

## 📝 Cách 1: Qua phpMyAdmin

1. Truy cập: http://localhost:28888
2. Đăng nhập: root / root
3. Chọn database: `sales_management`
4. Chọn bảng: `hanghoa`
5. Click tab "Structure"
6. Click "Add column" sau cột `giathamkhao`
7. Nhập thông tin:
   - **Name**: `giakhuyenmai`
   - **Type**: `DECIMAL`
   - **Length/Values**: `15,2`
   - **Null**: ✅ Checked
   - **Comment**: `Giá khuyến mãi (NULL = không có KM)`
8. Click "Save"

---

## 📝 Cách 2: Chạy SQL Script

### Bước 1: Truy cập MySQL
```bash
docker exec -it php_ws-mysql-1 mysql -uroot -proot sales_management
```

### Bước 2: Chạy lệnh SQL
```sql
ALTER TABLE hanghoa 
ADD COLUMN giakhuyenmai DECIMAL(15,2) NULL 
COMMENT 'Giá khuyến mãi (NULL = không có KM)' 
AFTER giathamkhao;
```

### Bước 3: Kiểm tra
```sql
DESCRIBE hanghoa;
```

Kết quả mong đợi:
```
+---------------+---------------+------+-----+---------+----------------+
| Field         | Type          | Null | Key | Default | Extra          |
+---------------+---------------+------+-----+---------+----------------+
| idhanghoa     | int(11)       | NO   | PRI | NULL    | auto_increment |
| tenhanghoa    | varchar(255)  | YES  |     | NULL    |                |
| giathamkhao   | decimal(15,2) | YES  |     | NULL    |                |
| giakhuyenmai  | decimal(15,2) | YES  |     | NULL    |                | ← MỚI
| ...           | ...           | ...  | ... | ...     | ...            |
+---------------+---------------+------+-----+---------+----------------+
```

---

## 📝 Cách 3: Import File SQL

1. Mở file: `add_promotion_column.sql`
2. Copy nội dung
3. Vào phpMyAdmin → SQL tab
4. Paste và Execute

---

## ✅ Sau Khi Thêm Cột

### 1. Cập nhật Query trong manageFeaturedView.php

Uncomment phần query khuyến mãi:

```php
} else {
    // Sản phẩm khuyến mãi
    $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.giakhuyenmai, h.created_at,
            th.tenTH as ten_thuonghieu,
            ROUND(((h.giathamkhao - h.giakhuyenmai) / h.giathamkhao * 100), 0) as discount_percent,
            h.hinhanh as image
            FROM hanghoa h
            LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
            WHERE h.giakhuyenmai IS NOT NULL 
              AND h.giakhuyenmai > 0
              AND h.giakhuyenmai < h.giathamkhao
            ORDER BY discount_percent DESC";
}
```

### 2. Test Thêm Khuyến Mãi

```sql
-- Test: Thêm khuyến mãi cho 1 sản phẩm
UPDATE hanghoa 
SET giakhuyenmai = 500000 
WHERE idhanghoa = 1 AND giathamkhao > 500000;

-- Kiểm tra
SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai,
       ROUND(((giathamkhao - giakhuyenmai) / giathamkhao * 100), 0) as discount_percent
FROM hanghoa 
WHERE giakhuyenmai IS NOT NULL;
```

### 3. Test Xóa Khuyến Mãi

```sql
-- Xóa khuyến mãi
UPDATE hanghoa 
SET giakhuyenmai = NULL 
WHERE idhanghoa = 1;
```

---

## 🔍 Kiểm Tra Tính Toàn Vẹn Dữ Liệu

```sql
-- Kiểm tra giá khuyến mãi >= giá gốc (SAI!)
SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai
FROM hanghoa
WHERE giakhuyenmai IS NOT NULL 
  AND giakhuyenmai >= giathamkhao;
-- Kết quả phải RỖNG

-- Kiểm tra giá âm
SELECT idhanghoa, tenhanghoa, giathamkhao, giakhuyenmai
FROM hanghoa
WHERE giathamkhao < 0 OR giakhuyenmai < 0;
-- Kết quả phải RỖNG
```

---

## 📊 Cấu Trúc Giá Sau Khi Setup

```
giathamkhao  → Giá bán thông thường (từ bảng dongia)
giakhuyenmai → Giá khuyến mãi (NULL = không KM)
```

### Ưu tiên hiển thị:
```
1. giakhuyenmai (nếu có và < giathamkhao)
2. giathamkhao (giá thường)
```

---

## ⚠️ LƯU Ý

1. **KHÔNG BAO GIỜ** để `giakhuyenmai >= giathamkhao`
2. **KHÔNG BAO GIỜ** để giá âm
3. Khi xóa KM: `SET giakhuyenmai = NULL`
4. Giá hiển thị ưu tiên: `giakhuyenmai` > `giathamkhao`

---

## 🐛 Troubleshooting

### Lỗi: Column already exists
```
Cột đã tồn tại, bỏ qua bước này
```

### Lỗi: Access denied
```bash
# Dùng root user
docker exec -it php_ws-mysql-1 mysql -uroot -proot sales_management
```

### Lỗi: Table doesn't exist
```
Kiểm tra tên database và table đúng chưa
```

---

## ✅ Checklist

- [ ] Thêm cột `giakhuyenmai` vào bảng `hanghoa`
- [ ] Kiểm tra cột đã tồn tại: `DESCRIBE hanghoa`
- [ ] Test thêm khuyến mãi cho 1 sản phẩm
- [ ] Test hiển thị trên trang quản trị
- [ ] Test hiển thị trên frontend
- [ ] Test xóa khuyến mãi
- [ ] Kiểm tra tính toàn vẹn dữ liệu
