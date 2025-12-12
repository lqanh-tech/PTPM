# Hướng Dẫn Cài Đặt & Sử Dụng Hệ Thống Vận Chuyển

## 📦 **Tổng Quan**

Hệ thống vận chuyển tích hợp với GHN API (Giao Hàng Nhanh) cung cấp:
- ✅ Tính phí vận chuyển chính xác theo địa chỉ thực
- ✅ Track & Trace realtime
- ✅ Ước tính thời gian giao hàng
- ✅ Fallback pricing khi API không khả dụng
- ✅ Quản lý địa chỉ người nhận

---

## 🚀 **Bước 1: Cài Đặt Database**

### Chạy SQL Script

Mở MySQL/PhpMyAdmin và chạy file SQL:

```bash
# Option 1: Via Command Line
mysql -u root -p trainingdb < database/create_shipping_tables.sql

# Option 2: Via PhpMyAdmin
## 1. Mở PhpMyAdmin
# 2. Chọn database 'trainingdb'
# 3. Vào tab "SQL"
# 4. Copy nội dung file create_shipping_tables.sql và paste vào
# 5. Click "Go"
```

### Kiểm Tra Kết Quả

Sau khi chạy xong, bạn sẽ có các bảng mới:
- `shipping_methods`
- `shipping_rates`
- `order_shipping_tracking`
- `user_addresses`
- `shipping_config`
- `vietnam_provinces`
- `vietnam_districts`
- `vietnam_wards`

---

## 🔑 **Bước 2: Đăng Ký GHN API (MIỄN PHÍ)**

### 2.1. Tạo Tài Khoản GHN

1. Truy cập: https://khachhang.ghn.vn/dang-ky
2. Đăng ký tài khoản (MIỄN PHÍ)
3. Xác thực email

### 2.2. Lấy API Token

1. Đăng nhập vào https://khachhang.ghn.vn
2. Vào **"Cài đặt"** → **"Cài đặt tài khoản"**
3. Chọn tab **"Tài khoản"**
4. Tìm mục **"Token"** và click **"Copy"**
5. Lưu lại token này

### 2.3. Lấy Shop ID

1. Vào **"Danh sách cửa hàng"**
2. Nếu chưa có, tạo cửa hàng mới
3. Click vào cửa hàng
4. Copy **Shop ID** (số ID ở URL hoặc trong chi tiết shop)

### 2.4 Cấu Hình trong Database

```sql
-- Cập nhật GHN API Token
UPDATE shipping_config 
SET config_value = 'YOUR_GHN_TOKEN_HERE' 
WHERE config_key = 'ghn_api_token';

-- Cập nhật Shop ID
UPDATE shipping_config 
SET config_value = 'YOUR_SHOP_ID_HERE' 
WHERE config_key = 'ghn_shop_id';

-- Bật GHN API
UPDATE shipping_config 
SET config_value = '1' 
WHERE config_key = 'enable_ghn_api';
```

**Thay thế:**
- `YOUR_GHN_TOKEN_HERE` bằng token bạn vừa copy
- `YOUR_SHOP_ID_HERE` bằng Shop ID của bạn

---

## 🌍 **Bước 3: Tải Dữ Liệu Địa Chỉ Việt Nam**

Hệ thống cần tải danh sách Tỉnh/Quận/Phường từ GHN API.

### Option 1: Auto Load (Khuyến nghị)

Khi người dùng lần đầu chọn địa chỉ, hệ thống sẽ tự động tải từ GHN và cache vào database.

### Option 2: Pre-load Data

Tạo file `sync_ghn_addresses.php`:

```php
<?php
require_once 'administrator/elements_LQA/mod/GHNApiCls.php';

$ghn = new GHNApi();

echo "Đang tải danh sách Tỉnh/Thành phố...\n";
$provinces = $ghn->getProvinces(true); // force refresh
if ($provinces['success']) {
    echo "✓ Đã tải " . count($provinces['data']) . " tỉnh/thành phố\n";
    
    // Load tất cả districts (optional, có thể mất thời gian)
    foreach ($provinces['data'] as $province) {
        $provinceId = $province['ProvinceID'] ?? $province['province_id'];
        echo "- Đang tải quận/huyện của " . ($province['ProvinceName'] ?? $province['province_name']) . "...\n";
        $ghn->getDistricts($provinceId, true);
    }
} else {
    echo "✗ Lỗi: " . $provinces['message'] . "\n";
}

echo "\nHoàn tất!\n";
```

Chạy file:
```bash
php sync_ghn_addresses.php
```

---

## 🛠️ **Bước 4: Tích Hợp vào Checkout**

### 4.1. Thay Thế Address Input

Trong file `checkout.php`, tìm phần nhập địa chỉ cũ (dòng 348-360) và thay bằng:

```php
<?php include 'address_selector_component.php'; ?>
```

### 4.2. Cập Nhật Payment Processing

Trong các file xử lý thanh toán (momo_payment.php, cod_payment.php), thêm code lưu shipping:

```php
// After successful payment...
require_once '../mod/ShippingCls.php';

$shipping = new Shipping();
$shippingOrder = $shipping->createShippingOrder([
    'order_id' => $orderId,
    'order_code' => $orderCode,
    'receiver_name' => $_SESSION['receiver_name'] ?? '',
    'receiver_phone' => $_SESSION['receiver_phone'] ?? '',
    'receiver_address' => $_SESSION['full_address'] ?? '',
    'to_province_id' => $_SESSION['to_province_id'] ?? null,
    'to_district_id' => $_SESSION['to_district_id'] ?? null,
    'to_ward_code' => $_SESSION['to_ward_code'] ?? null,
    'weight' => 1000, // Calculate actual weight
    'shipping_fee' => $_SESSION['shipping_fee'] ?? 0,
    'cod_amount' => $paymentMethod === 'COD' ? $totalAmount : 0,
]);

$_SESSION['tracking_number'] = $shippingOrder['tracking_number'] ?? null;
```

---

## 📍 **Bước 5: Thêm Link Tracking**

### 5.1. Trong Order Success Page

```php
<?php if (!empty($_SESSION['tracking_number'])): ?>
    <div class="alert alert-info">
        <h5>Theo dõi đơn hàng của bạn:</h5>
        <a href="administrator/elements_LQA/mgiohang/track_shipment.php?track=<?php echo urlencode($_SESSION['tracking_number']); ?>" 
           class="btn btn-primary">
            <i class="fas fa-shipping-fast"></i> Theo dõi vận chuyển
        </a>
    </div>
<?php endif; ?>
```

### 5.2. Trong Email Xác Nhận

Thêm vào email template:

```html
<p>Mã vận đơn của bạn: <strong>{tracking_number}</strong></p>
<a href="https://yourdomain.com/track_shipment.php?track={tracking_number}">
    Theo dõi đơn hàng tại đây
</a>
```

---

## ✅ **Bước 6: Testing**

### 6.1. Test Tính Phí Shipping

1. Vào trang checkout
2. Chọn:
   - Tỉnh: Hồ Chí Minh
   - Quận: Quận 1
   - Phường: Phường Bến Nghé
3. Kiểm tra:
   - ✓ Phí ship hiển thị
   - ✓ Thời gian dự kiến hiển thị
   - ✓ Tổng tiền cập nhật đúng

### 6.2. Test Tracking

1. Hoàn thành đơn hàng test
2. Lấy tracking number
3. Truy cập: `track_shipment.php?track=TRACKING_NUMBER`
4. Kiểm tra hiển thị timeline

### 6.3. Test Fallback (Không dùng GHN)

```sql
-- Tắt GHN tạm thời
UPDATE shipping_config SET config_value = '0' WHERE config_key = 'enable_ghn_api';
```

Thử tính shipping → Hệ thống phải fallback về tính theo km (5000 VND/km)

---

## 🔧 **Troubleshooting**

### Lỗi: "GHN API not configured"

**Nguyên nhân:** Chưa cấu hình Token hoặc Shop ID

**Giải pháp:**
```sql
SELECT config_key, config_value FROM shipping_config WHERE config_key LIKE 'ghn_%';
```

Kiểm tra xem `ghn_api_token` và `ghn_shop_id` đã có giá trị chưa.

### Lỗi: "No data found" khi load địa chỉ

**Nguyên nhân:** GHN API token không hợp lệ hoặc hết hạn

**Giải pháp:**
1. Kiểm tra token trên GHN dashboard
2. Generate token mới nếu cần
3. Update lại database

### Lỗi: Phí shipping = 0

**Nguyên nhân:** Chưa chọn đầy đủ địa chỉ (thiếu ward)

**Giải pháp:** Đảm bảo user chọn đủ Tỉnh → Quận → Phường trước khi tính phí

---

## 📊 **Monitoring & Maintenance**

### Xem Logs

```sql
-- Xem các đơn hàng shipping gần đây
SELECT * FROM order_shipping_tracking 
ORDER BY created_at DESC 
LIMIT 10;

-- Thống kê shipping methods
SELECT shipping_method_code, COUNT(*) as count 
FROM order_shipping_tracking 
GROUP BY shipping_method_code;
```

### Update Shipping Status (Manual)

```sql
UPDATE order_shipping_tracking 
SET status = 'delivered', 
    actual_delivery = NOW() 
WHERE tracking_number = 'LQA123...';
```

### Refresh Address Cache

```sql
-- Xóa cache cũ (nếu cần)
TRUNCATE vietnam_provinces;
TRUNCATE vietnam_districts;
TRUNCATE vietnam_wards;

-- Hệ thống sẽ tự động tải lại từ GHN
```

---

## 🎯 **Tính Năng Nâng Cao (Tùy Chọn)**

### Auto Sync Tracking từ GHN

Tạo cron job chạy mỗi 30 phút:

```php
<?php
// sync_ghn_tracking.php
require_once 'administrator/elements_LQA/mod/ShippingCls.php';
require_once 'administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();
$shipping = new Shipping();

// Lấy tất cả đơn hàng GHN chưa delivered
$sql = "SELECT * FROM order_shipping_tracking 
        WHERE shipping_method_code = 'GHN' 
        AND status NOT IN ('delivered', 'returned', 'cancelled')";
$stmt = $db->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll();

foreach ($orders as $order) {
    $result = $shipping->trackShipment($order['tracking_number']);
    echo "Updated: " . $order['tracking_number'] . "\n";
    sleep(1); // Avoid rate limit
}
```

Thêm vào crontab:
```bash
*/30 * * * * php /path/to/sync_ghn_tracking.php
```

---

## 📞 **Hỗ Trợ**

- **GHN API Docs:** https://api.ghn.vn/home/docs/detail
- **GHN Support:** 1900 636677
- **Email:** hotro@ghn.vn

---

## 🎉 **Hoàn Tất!**

Hệ thống vận chuyển của bạn đã sẵn sàng sử dụng!

**Checklist cuối cùng:**
- [x] Database đã tạo xong
- [x] GHN Token đã cấu hình
- [x] Address selector đã tích hợp vào checkout
- [x] Tracking page hoạt động
- [x] Email confirmation có tracking link
- [x] Test thành công cả GHN và Fallback

**Lưu ý:** Nếu không muốn dùng GHN, hệ thống vẫn hoạt động hoàn toàn bình thường với Fallback pricing!
