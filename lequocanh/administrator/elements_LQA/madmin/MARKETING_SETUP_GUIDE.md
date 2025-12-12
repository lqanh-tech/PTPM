# Hướng dẫn cài đặt và sử dụng Marketing Content

## Tổng quan

Hệ thống Marketing Content bao gồm 3 module:
1. **Banner** - Quản lý banner/slide trên trang chủ
2. **Tin tức (News)** - Quản lý bài viết tin tức
3. **Chương trình Ưu đãi (Promotions)** - Quản lý các chương trình khuyến mãi

## Cài đặt nhanh

### Bước 1: Kiểm tra hệ thống

Truy cập: `http://your-domain/administrator/elements_LQA/madmin/check_all_marketing.php`

Script này sẽ tự động:
- ✓ Kiểm tra kết nối database
- ✓ Kiểm tra các bảng (banners, news, promotions)
- ✓ Kiểm tra thư mục uploads
- ✓ Kiểm tra các Manager class
- ✓ Hiển thị cấu hình PHP

### Bước 2: Tạo bảng database

Nếu các bảng chưa tồn tại, click nút **"Tạo tất cả các bảng"** trong trang kiểm tra.

Hoặc chạy SQL thủ công:

```sql
-- Chạy file này trong phpMyAdmin
lequocanh/database/create_banner_news_promotion_tables.sql
```

### Bước 3: Kiểm tra quyền thư mục

Đảm bảo thư mục `administrator/uploads/` có quyền ghi:

**Linux/Mac:**
```bash
chmod 755 administrator/uploads
```

**Windows:** 
- Click chuột phải > Properties > Security > Full Control

### Bước 4: Bắt đầu sử dụng

Truy cập các trang quản lý:
- Banner: `madmin/banners.php`
- Tin tức: `madmin/news.php`
- Ưu đãi: `madmin/promotions.php`

## Chi tiết từng module

### 1. Quản lý Banner

**File:** `madmin/banners.php`

**Chức năng:**
- Thêm/sửa/xóa banner
- Upload ảnh banner
- Sắp xếp thứ tự hiển thị
- Bật/tắt hiển thị

**Yêu cầu:**
- Ảnh banner là **bắt buộc**
- Định dạng: JPG, PNG, GIF
- Kích thước khuyến nghị: 1920x600px

**Lỗi thường gặp:**
- "Vui lòng chọn ảnh banner" → Chưa chọn file ảnh
- "Không thể upload ảnh" → Kiểm tra quyền thư mục uploads/
- "Không thể thêm banner vào database" → Bảng banners chưa được tạo

### 2. Quản lý Tin tức

**File:** `madmin/news.php`

**Chức năng:**
- Thêm/sửa/xóa tin tức
- Upload ảnh đại diện (không bắt buộc)
- Soạn thảo nội dung với CKEditor
- Xuất bản/ẩn tin tức

**Yêu cầu:**
- Tiêu đề: bắt buộc
- Nội dung: bắt buộc
- Ảnh: không bắt buộc
- Tác giả: mặc định "Admin"

**Lỗi thường gặp:**
- "Tiêu đề không được để trống" → Nhập tiêu đề
- "Nội dung không được để trống" → Nhập nội dung
- "Không thể thêm tin tức vào database" → Bảng news chưa được tạo

### 3. Quản lý Chương trình Ưu đãi

**File:** `madmin/promotions.php`

**Chức năng:**
- Thêm/sửa/xóa chương trình ưu đãi
- Thiết lập phần trăm giảm giá
- Thiết lập thời gian hiệu lực
- Kích hoạt/vô hiệu hóa

**Yêu cầu:**
- Tiêu đề: bắt buộc
- Phần trăm giảm giá: 0-100%
- Ngày bắt đầu: bắt buộc
- Ngày kết thúc: phải sau ngày bắt đầu

**Lỗi thường gặp:**
- "Phần trăm giảm giá phải từ 0 đến 100" → Nhập giá trị hợp lệ
- "Ngày kết thúc phải sau ngày bắt đầu" → Kiểm tra lại ngày tháng
- "Không thể thêm chương trình ưu đãi" → Bảng promotions chưa được tạo

## Cấu trúc Database

### Bảng: banners
```sql
- id: INT (Primary Key)
- title: VARCHAR(255) - Tiêu đề
- description: TEXT - Mô tả
- image_url: VARCHAR(500) - Đường dẫn ảnh
- link_url: VARCHAR(500) - Liên kết
- position: INT - Thứ tự hiển thị
- is_active: BOOLEAN - Trạng thái
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

### Bảng: news
```sql
- id: INT (Primary Key)
- title: VARCHAR(255) - Tiêu đề
- content: TEXT - Nội dung
- image_url: VARCHAR(500) - Ảnh đại diện
- author: VARCHAR(100) - Tác giả
- is_published: BOOLEAN - Đã xuất bản
- published_at: TIMESTAMP - Ngày xuất bản
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

### Bảng: promotions
```sql
- id: INT (Primary Key)
- title: VARCHAR(255) - Tiêu đề
- description: TEXT - Mô tả
- discount_percent: DECIMAL(5,2) - % giảm giá
- start_date: DATE - Ngày bắt đầu
- end_date: DATE - Ngày kết thúc
- is_active: BOOLEAN - Trạng thái
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

## Các file liên quan

### PHP Files
- `madmin/banners.php` - Giao diện quản lý banner
- `madmin/news.php` - Giao diện quản lý tin tức
- `madmin/promotions.php` - Giao diện quản lý ưu đãi
- `mod/BannerManager.php` - Class xử lý banner
- `mod/NewsManager.php` - Class xử lý tin tức
- `mod/PromotionManager.php` - Class xử lý ưu đãi

### Utility Files
- `madmin/check_all_marketing.php` - Kiểm tra tổng hợp
- `madmin/check_banner_setup.php` - Kiểm tra chi tiết banner
- `madmin/test_banner_upload.php` - Test upload ảnh
- `madmin/BANNER_TROUBLESHOOTING.md` - Hướng dẫn khắc phục lỗi banner

### Database
- `database/create_banner_news_promotion_tables.sql` - SQL tạo bảng

## Khắc phục sự cố

### Lỗi upload ảnh

1. Kiểm tra thư mục uploads:
```bash
ls -la administrator/uploads/
```

2. Cấp quyền nếu cần:
```bash
chmod 755 administrator/uploads/
```

3. Kiểm tra cấu hình PHP:
```ini
upload_max_filesize = 10M
post_max_size = 10M
file_uploads = On
```

### Lỗi database

1. Kiểm tra bảng đã tồn tại:
```sql
SHOW TABLES LIKE 'banners';
SHOW TABLES LIKE 'news';
SHOW TABLES LIKE 'promotions';
```

2. Tạo lại bảng nếu cần:
```sql
-- Chạy file SQL
source lequocanh/database/create_banner_news_promotion_tables.sql
```

### Xem log lỗi

Kiểm tra các file log:
- `error.log` (root directory)
- `lequocanh/logs/application.log`
- PHP error log (tùy cấu hình server)

## Tích hợp vào Frontend

### Hiển thị Banner
```php
<?php
require_once 'administrator/elements_LQA/mod/BannerManager.php';
$bannerManager = new BannerManager();
$banners = $bannerManager->getActiveBanners();

foreach ($banners as $banner) {
    echo '<div class="banner">';
    echo '<img src="' . htmlspecialchars($banner['image_url']) . '" alt="' . htmlspecialchars($banner['title']) . '">';
    echo '</div>';
}
?>
```

### Hiển thị Tin tức
```php
<?php
require_once 'administrator/elements_LQA/mod/NewsManager.php';
$newsManager = new NewsManager();
$newsList = $newsManager->getPublishedNews(5); // Lấy 5 tin mới nhất

foreach ($newsList as $news) {
    echo '<article>';
    echo '<h3>' . htmlspecialchars($news['title']) . '</h3>';
    echo '<p>' . htmlspecialchars(substr($news['content'], 0, 200)) . '...</p>';
    echo '</article>';
}
?>
```

### Hiển thị Ưu đãi
```php
<?php
require_once 'administrator/elements_LQA/mod/PromotionManager.php';
$promotionManager = new PromotionManager();
$promotions = $promotionManager->getActivePromotions();

foreach ($promotions as $promo) {
    echo '<div class="promotion">';
    echo '<h4>' . htmlspecialchars($promo['title']) . '</h4>';
    echo '<span class="discount">' . $promo['discount_percent'] . '% OFF</span>';
    echo '</div>';
}
?>
```

## Hỗ trợ

Nếu gặp vấn đề:
1. Chạy `check_all_marketing.php` để kiểm tra
2. Xem file `BANNER_TROUBLESHOOTING.md` để khắc phục lỗi
3. Kiểm tra log lỗi
4. Đảm bảo đã tạo đầy đủ các bảng database
5. Kiểm tra quyền thư mục uploads/
