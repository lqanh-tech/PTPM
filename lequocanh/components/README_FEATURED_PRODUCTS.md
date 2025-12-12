# 🎯 Hướng Dẫn Hiển Thị Sản Phẩm Nổi Bật

## 📋 Tổng Quan

Component này hiển thị **3 phần chính** trên trang chủ:

1. **Sản Phẩm Nổi Bật** ⭐ - Badge màu tím gradient
2. **Sản Phẩm Mới** ✨ - Badge màu hồng gradient  
3. **Khuyến Mãi Hot** 🔥 - Badge màu vàng + % giảm giá

---

## 🎨 Cách Nhận Diện Trên Trang Mua Hàng

### 1. Sản Phẩm Nổi Bật
```
┌─────────────────────┐
│  [⭐ Nổi bật]       │ ← Badge tím gradient góc phải
│                     │
│   [Hình ảnh SP]     │
│                     │
└─────────────────────┘
```
- **Badge**: Màu tím gradient (667eea → 764ba2)
- **Icon**: ⭐ Star
- **Text**: "Nổi bật"
- **Tiêu chí**: Sản phẩm có `is_featured = 1` (được admin đánh dấu tự động)

### 2. Sản Phẩm Mới
```
┌─────────────────────┐
│  [✨ Mới]           │ ← Badge hồng gradient góc phải
│                     │
│   [Hình ảnh SP]     │
│                     │
└─────────────────────┘
```
- **Badge**: Màu hồng gradient (f093fb → f5576c)
- **Icon**: ✨ Sparkles
- **Text**: "Mới"
- **Tiêu chí**: Sản phẩm được tạo trong 30 ngày gần đây

### 3. Khuyến Mãi Hot
```
┌─────────────────────┐
│ [-30%]  [🔥 Sale]   │ ← 2 badges: % giảm (trái) + Sale (phải)
│                     │
│   [Hình ảnh SP]     │
│                     │
└─────────────────────┘
```
- **Badge giảm giá**: Màu đỏ (#e74c3c) góc trái
- **Badge Sale**: Màu vàng gradient (fa709a → fee140) góc phải
- **Icon**: 🔥 Fire
- **Text**: "Sale" + "-%"
- **Tiêu chí**: Có giá khuyến mãi < giá gốc

---

## 🚀 Cách Sử Dụng

### Cách 1: Include trực tiếp vào trang chủ

```php
<?php
// Trong file index.php hoặc home.php
include __DIR__ . '/components/featuredProductsDisplay.php';
?>
```

### Cách 2: Include vào layout

```php
<?php
// Trong file layout/main.php
if ($page == 'home') {
    include __DIR__ . '/../components/featuredProductsDisplay.php';
}
?>
```

---

## 🔧 Tùy Chỉnh

### Thay đổi số lượng sản phẩm hiển thị

```php
// Mặc định: 8 sản phẩm mỗi section
$featuredProducts = $featuredDisplay->getFeaturedProducts(12); // Hiển thị 12
$newProducts = $featuredDisplay->getNewProducts(6);           // Hiển thị 6
$promotionProducts = $featuredDisplay->getPromotionProducts(10); // Hiển thị 10
```

### Thay đổi màu sắc badge

```css
/* Trong file featuredProductsDisplay.php */
.badge-featured {
    background: linear-gradient(135deg, #YOUR_COLOR_1 0%, #YOUR_COLOR_2 100%);
}
```

---

## 📊 Logic Phân Loại

### Sản Phẩm Nổi Bật
- Được đánh dấu tự động bởi admin qua trang `/administrator/?p=msanphamnoibat`
- Tiêu chí tự động:
  - **Bán chạy**: Top doanh số
  - **Xem nhiều**: Top lượt xem
  - **Điểm tổng hợp**: 40% doanh số + 30% view + 20% mới + 10% KM
  - **Trending**: Tăng trưởng nhanh 7 ngày
  - **Margin cao**: Lợi nhuận tốt

### Sản Phẩm Mới
- Tự động: `created_at >= NOW() - 30 ngày`
- Không cần admin đánh dấu

### Khuyến Mãi
- Tự động: Có `giakhuyenmai` < `giagoc`
- Hiển thị % giảm giá
- Sắp xếp theo % giảm cao nhất

---

## 🎯 Ví Dụ Hiển Thị

```
┌──────────────────────────────────────────────────────┐
│           🌟 SẢN PHẨM NỔI BẬT 🌟                     │
│   Những sản phẩm được yêu thích và bán chạy nhất     │
├──────────────────────────────────────────────────────┤
│                                                       │
│  [SP1]    [SP2]    [SP3]    [SP4]                   │
│  ⭐Nổi bật ⭐Nổi bật ⭐Nổi bật ⭐Nổi bật              │
│                                                       │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│           ✨ SẢN PHẨM MỚI ✨                         │
│        Những sản phẩm mới nhất vừa ra mắt            │
├──────────────────────────────────────────────────────┤
│                                                       │
│  [SP1]    [SP2]    [SP3]    [SP4]                   │
│  ✨Mới    ✨Mới    ✨Mới    ✨Mới                    │
│                                                       │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│           🔥 KHUYẾN MÃI HOT 🔥                       │
│         Giảm giá sốc - Số lượng có hạn               │
├──────────────────────────────────────────────────────┤
│                                                       │
│  [SP1]      [SP2]      [SP3]      [SP4]             │
│  -30% 🔥    -25% 🔥    -40% 🔥    -15% 🔥           │
│                                                       │
└──────────────────────────────────────────────────────┘
```

---

## 📱 Responsive Design

Component tự động responsive:
- **Desktop**: 4 sản phẩm/hàng
- **Tablet**: 3 sản phẩm/hàng
- **Mobile**: 1-2 sản phẩm/hàng

---

## ✅ Checklist Triển Khai

- [ ] Include component vào trang chủ
- [ ] Kiểm tra database có cột `is_featured` trong bảng `hanghoa`
- [ ] Chạy trang admin để đánh dấu sản phẩm nổi bật
- [ ] Test hiển thị trên desktop/mobile
- [ ] Kiểm tra chức năng "Thêm vào giỏ hàng"
- [ ] Verify link "Xem chi tiết" hoạt động

---

## 🐛 Troubleshooting

### Không hiển thị sản phẩm nổi bật?
→ Vào admin `/administrator/?p=msanphamnoibat` để đánh dấu

### Không hiển thị sản phẩm mới?
→ Kiểm tra cột `created_at` trong database có giá trị

### Không hiển thị khuyến mãi?
→ Đảm bảo `giakhuyenmai` < `giagoc` và không NULL

### Badge không hiển thị đúng?
→ Kiểm tra Font Awesome đã được load: `<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">`
