# Hướng dẫn tối ưu Cloudflare cho Website

## 🚀 Tổng quan các tối ưu đã thực hiện

### Files đã tạo:
- `lequocanh/cache/CacheManager.php` - Hệ thống cache file-based
- `lequocanh/cache/QueryCache.php` - Cache database queries
- `lequocanh/cache/PageCache.php` - Cache toàn bộ HTML pages
- `lequocanh/includes/optimized_header.php` - Header tối ưu
- `lequocanh/includes/optimized_footer.php` - Footer với deferred scripts
- `lequocanh/public_files/performance.js` - Frontend optimizations
- `lequocanh/sw.js` - Service Worker cho offline support
- `lequocanh/config/performance.php` - Cấu hình performance
- `lequocanh/api/clear_cache.php` - API xóa cache
- `lequocanh/administrator/performance_check.php` - Dashboard kiểm tra

---

## 1. Cấu hình Cloudflare Dashboard

### Speed > Optimization
- [x] Auto Minify: HTML, CSS, JavaScript
- [x] Brotli: ON
- [x] Early Hints: ON
- [x] Rocket Loader: ON (thử nghiệm, tắt nếu lỗi JS)

### Caching > Configuration
- Browser Cache TTL: 1 year
- Crawler Hints: ON

### Page Rules (tạo theo thứ tự)

**Rule 1: Cache Static Assets**
```
URL: *trycloudflare.com/lequocanh/*.css
URL: *trycloudflare.com/lequocanh/*.js
URL: *trycloudflare.com/lequocanh/*.jpg
URL: *trycloudflare.com/lequocanh/*.png
URL: *trycloudflare.com/lequocanh/*.gif
URL: *trycloudflare.com/lequocanh/*.woff*

Settings:
- Cache Level: Cache Everything
- Edge Cache TTL: 1 month
- Browser Cache TTL: 1 year
```

**Rule 2: Bypass Cache for Admin**
```
URL: *trycloudflare.com/lequocanh/administrator/*

Settings:
- Cache Level: Bypass
- Security Level: High
```

**Rule 3: Bypass Cache for API**
```
URL: *trycloudflare.com/lequocanh/api/*

Settings:
- Cache Level: Bypass
```

---

## 2. Sử dụng Cloudflare Tunnel cố định (khuyến nghị)

Thay vì dùng Quick Tunnel (trycloudflare.com), hãy tạo tunnel cố định:

```bash
# Đăng nhập Cloudflare
cloudflared tunnel login

# Tạo tunnel mới
cloudflared tunnel create lequocanh-shop

# Cấu hình DNS
cloudflared tunnel route dns lequocanh-shop shop.yourdomain.com

# Chạy tunnel
cloudflared tunnel run lequocanh-shop
```

---

## 3. Cách sử dụng Cache System

### Cache database queries
```php
require_once 'lequocanh/cache/CacheManager.php';
require_once 'lequocanh/cache/QueryCache.php';

// Cách 1: Sử dụng CacheManager trực tiếp
$cache = CacheManager::getInstance();
$products = $cache->remember('products_list', 300, function() use ($db) {
    return $db->query("SELECT * FROM hanghoa WHERE trangthai = 1")->fetchAll();
});

// Cách 2: Sử dụng helper function
$products = cache()->remember('products_list', 300, function() use ($db) {
    return $db->query("SELECT * FROM hanghoa")->fetchAll();
});

// Cách 3: Sử dụng QueryCache
$queryCache = QueryCache::getInstance();
$products = $queryCache->query($pdo, "SELECT * FROM hanghoa", [], 300);
```

### Cache toàn bộ trang
```php
require_once 'lequocanh/cache/PageCache.php';

// Đầu file
page_cache_start(180); // Cache 3 phút

// ... render page ...

// Cuối file
page_cache_end(180);
```

### Xóa cache khi cập nhật dữ liệu
```php
// Xóa tất cả cache
cache()->clear();

// Xóa cache cụ thể
cache()->delete('products_list');

// Hoặc gọi API
// GET /lequocanh/api/clear_cache.php?action=all
// GET /lequocanh/api/clear_cache.php?action=products
```

---

## 4. Tích hợp vào trang

### Cách 1: Include header/footer tối ưu
```php
<?php
// Đầu file
require_once __DIR__ . '/includes/optimized_header.php';
setOptimizedHeaders(300); // Cache 5 phút
?>

<!-- HTML content -->

<?php
// Cuối file
require_once __DIR__ . '/includes/optimized_footer.php';
?>
```

### Cách 2: Sử dụng viewListLoaihang_cached.php
Thay thế `viewListLoaihang.php` bằng `viewListLoaihang_cached.php` trong index.php

---

## 5. Checklist kiểm tra hiệu suất

- [ ] Truy cập `/lequocanh/administrator/performance_check.php` để xem dashboard
- [ ] Kiểm tra với Chrome DevTools > Network > Disable cache
- [ ] Kiểm tra response headers có `cf-cache-status: HIT`
- [ ] Kiểm tra header `X-Page-Cache: HIT` cho page cache
- [ ] Test với https://www.webpagetest.org
- [ ] Test với https://gtmetrix.com
- [ ] Test với https://pagespeed.web.dev

---

## 6. Các vấn đề thường gặp

### Trang vẫn chậm sau khi cấu hình
1. Xóa cache Cloudflare: Caching > Configuration > Purge Everything
2. Kiểm tra TTFB (Time to First Byte) - nếu cao, vấn đề ở server/database
3. Kiểm tra số lượng requests - giảm bằng cách gộp CSS/JS
4. Chạy SQL indexes: `source optimize_database_indexes.sql`

### Cache không hoạt động
1. Kiểm tra header `Cache-Control` không có `no-cache`
2. Kiểm tra cookies - Cloudflare không cache nếu có cookies
3. Thêm `?v=1` vào URL static assets để bust cache

### Quick Tunnel chậm
- Quick Tunnel (trycloudflare.com) có latency cao hơn tunnel cố định
- Khuyến nghị: Tạo tunnel cố định với domain riêng

---

## 7. Metrics mục tiêu

| Metric | Mục tiêu | Cách đo |
|--------|----------|---------|
| TTFB | < 200ms | Chrome DevTools |
| FCP | < 1.8s | Lighthouse |
| LCP | < 2.5s | Lighthouse |
| CLS | < 0.1 | Lighthouse |
| Cache Hit Rate | > 80% | Cloudflare Analytics |
