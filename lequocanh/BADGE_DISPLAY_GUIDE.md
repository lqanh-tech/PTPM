# 🏷️ Hướng Dẫn Hiển Thị Badge Sản Phẩm

## 📋 Tổng Quan

Hệ thống có 2 phiên bản widget:

1. **`featuredProducts.php`** - Phiên bản cơ bản
   - Badge: Nổi bật, Mới, Khuyến mãi
   
2. **`featuredProductsEnhanced.php`** - Phiên bản nâng cao ⭐ KHUYẾN NGHỊ
   - Badge: Bán chạy, Xem nhiều, Trending, Nổi bật, Mới, Khuyến mãi
   - Stats: Lượt xem, Đã bán
   - Animation: Pulse effect cho badge bán chạy

## 🎨 Các Loại Badge

### 1. 🔥 BÁN CHẠY (Best Seller)
- **Màu:** Đỏ gradient với animation pulse
- **Điều kiện:** Top 10 sản phẩm bán nhiều nhất
- **Vị trí:** Góc trên bên phải
- **Icon:** 🔥

### 2. 👁️ XEM NHIỀU (Most Viewed)
- **Màu:** Đỏ cam gradient
- **Điều kiện:** Top 10 sản phẩm xem nhiều nhất
- **Vị trí:** Góc trên bên phải
- **Icon:** 👁️

### 3. ✨ MỚI (New)
- **Màu:** Xanh lá gradient
- **Điều kiện:** Sản phẩm mới (< 30 ngày) hoặc được đánh dấu
- **Vị trí:** Góc trên bên phải
- **Icon:** ✨

### 4. 🎁 KHUYẾN MÃI (Sale)
- **Màu:** Đỏ gradient
- **Hiển thị:** % giảm giá (ví dụ: -30%)
- **Điều kiện:** Đang có giá khuyến mãi
- **Vị trí:** Góc trên bên phải

### 5. ⭐ NỔI BẬT (Featured)
- **Màu:** Vàng cam gradient
- **Điều kiện:** Được admin đánh dấu nổi bật
- **Vị trí:** Góc trên bên phải
- **Icon:** ⭐

### 6. 📊 STATS (Thống kê)
- **Lượt xem:** Icon mắt + số lượt
- **Đã bán:** Icon giỏ hàng + số lượng
- **Vị trí:** Góc dưới bên trái (trên ảnh)
- **Background:** Đen trong suốt

## 🚀 Cách Sử Dụng

### Cách 1: Thay Thế Widget Cũ

Mở file `lequocanh/index.php`, tìm dòng:
```php
<?php include __DIR__ . '/apart/featuredProducts.php'; ?>
```

Thay bằng:
```php
<?php include __DIR__ . '/apart/featuredProductsEnhanced.php'; ?>
```

### Cách 2: Giữ Cả 2 Phiên Bản

```php
<!-- Dùng phiên bản nâng cao -->
<?php include __DIR__ . '/apart/featuredProductsEnhanced.php'; ?>

<!-- Hoặc dùng phiên bản cơ bản -->
<?php // include __DIR__ . '/apart/featuredProducts.php'; ?>
```

## 🎯 Logic Hiển Thị Badge

### Ưu Tiên Hiển Thị (từ trên xuống)

1. **Bán chạy** (nếu trong top 10 bán nhiều)
2. **Xem nhiều** (nếu trong top 10 xem nhiều)
3. **Mới** (nếu is_new = 1)
4. **Khuyến mãi** (nếu có sale_price)

### Code Logic

```php
// Kiểm tra bán chạy
<?php if (isBestSeller($product->idhanghoa, $topSalesIds)): ?>
<span class="product-badge badge-bestseller">🔥 BÁN CHẠY</span>
<?php endif; ?>

// Kiểm tra xem nhiều
<?php if (isMostViewed($product->idhanghoa, $topViewsIds)): ?>
<span class="product-badge badge-hot">👁️ XEM NHIỀU</span>
<?php endif; ?>

// Kiểm tra mới
<?php if ($product->is_new ?? 0): ?>
<span class="product-badge badge-new">✨ MỚI</span>
<?php endif; ?>

// Kiểm tra khuyến mãi
<?php if ($product->discount_percent > 0): ?>
<span class="product-badge badge-sale">-<?= $product->discount_percent ?>%</span>
<?php endif; ?>
```

## 🎨 Tùy Chỉnh Giao Diện

### Thay Đổi Màu Badge

File: `featuredProductsEnhanced.php`

```css
/* Bán chạy - Đỏ */
.badge-bestseller {
    background: linear-gradient(135deg, #e74c3c, #ff6b6b);
}

/* Xem nhiều - Cam */
.badge-hot {
    background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
}

/* Mới - Xanh lá */
.badge-new {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
}

/* Khuyến mãi - Đỏ đậm */
.badge-sale {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}
```

### Thay Đổi Vị Trí Badge

```css
/* Badge ở góc trên phải (mặc định) */
.product-badges {
    position: absolute;
    top: 10px;
    right: 10px;
}

/* Chuyển sang góc trên trái */
.product-badges {
    position: absolute;
    top: 10px;
    left: 10px;
}
```

### Thay Đổi Animation

```css
/* Pulse animation cho bán chạy */
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* Thêm animation khác */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.badge-bestseller {
    animation: shake 0.5s infinite;
}
```

## 📊 Hiển Thị Stats

### Stats Hiện Tại

```php
<!-- Lượt xem -->
<span class="stat-badge">
    <i class="fas fa-eye"></i>
    <?= number_format($product->view_count) ?>
</span>

<!-- Đã bán -->
<span class="stat-badge">
    <i class="fas fa-shopping-cart"></i>
    <?= number_format($product->total_sold) ?>
</span>
```

### Thêm Stats Mới

```php
<!-- Đánh giá -->
<span class="stat-badge">
    <i class="fas fa-star"></i>
    <?= number_format($product->rating, 1) ?>
</span>

<!-- Yêu thích -->
<span class="stat-badge">
    <i class="fas fa-heart"></i>
    <?= number_format($product->favorites) ?>
</span>
```

## 🔧 Tùy Chỉnh Nâng Cao

### Thay Đổi Số Lượng Top Products

File: `featuredProductsEnhanced.php`, line ~12

```php
// Mặc định: Top 20
$topSales = $autoMgr->getTopProducts('sales', 20);
$topViews = $autoMgr->getTopProducts('views', 20);

// Thay đổi thành Top 30
$topSales = $autoMgr->getTopProducts('sales', 30);
$topViews = $autoMgr->getTopProducts('views', 30);
```

### Thay Đổi Điều Kiện Badge

```php
// Mặc định: Top 10
function isBestSeller($productId, $topSalesIds) {
    return in_array($productId, array_slice($topSalesIds, 0, 10));
}

// Thay đổi thành Top 5
function isBestSeller($productId, $topSalesIds) {
    return in_array($productId, array_slice($topSalesIds, 0, 5));
}
```

### Thêm Badge Trending

```php
// Lấy trending products
$trendingProducts = $autoMgr->getTopProducts('trending', 20);
$trendingIds = array_column($trendingProducts, 'idhanghoa');

// Hiển thị badge
<?php if (in_array($product->idhanghoa, array_slice($trendingIds, 0, 10))): ?>
<span class="product-badge badge-trending">🚀 TRENDING</span>
<?php endif; ?>

// CSS
.badge-trending {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
}
```

## 📱 Responsive Design

Widget đã responsive:
- **Desktop:** 4 cột
- **Tablet:** 3 cột  
- **Mobile:** 2 cột

Badge tự động điều chỉnh kích thước.

## 🎯 Best Practices

### DO ✅
- Hiển thị tối đa 3-4 badge mỗi sản phẩm
- Ưu tiên badge quan trọng nhất (Bán chạy > Xem nhiều > Mới)
- Sử dụng icon để dễ nhận biết
- Animation nhẹ nhàng, không quá nhiều

### DON'T ❌
- Hiển thị quá nhiều badge (rối mắt)
- Animation quá mạnh (gây khó chịu)
- Badge quá lớn (che mất sản phẩm)
- Màu sắc không phù hợp với brand

## 🔍 Demo & Test

### Test Badge Hiển Thị

1. Đánh dấu sản phẩm nổi bật trong admin
2. Truy cập trang chủ
3. Kiểm tra badge hiển thị đúng

### Test Stats

1. Track view một số sản phẩm
2. Tạo đơn hàng test
3. Kiểm tra stats hiển thị

## 📈 Kết Quả Mong Đợi

- ✅ Khách hàng dễ nhận biết sản phẩm hot
- ✅ Tăng trust (social proof)
- ✅ Tăng conversion rate
- ✅ Giao diện chuyên nghiệp

---

**Chúc bạn thành công! 🎨**

**File widget:** `lequocanh/apart/featuredProductsEnhanced.php`
