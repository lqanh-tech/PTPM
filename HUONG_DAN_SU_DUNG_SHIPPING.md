# 🚚 HƯỚNG DẪN SỬ DỤNG HỆ THỐNG QUẢN LÝ VẬN CHUYỂN

## 📋 MỤC LỤC
1. [Cài đặt](#cài-đặt)
2. [Cấu trúc hệ thống](#cấu-trúc-hệ-thống)
3. [Sử dụng Models](#sử-dụng-models)
4. [API Endpoints](#api-endpoints)
5. [Tích hợp vào Checkout](#tích-hợp-vào-checkout)
6. [Mở rộng hệ thống](#mở-rộng-hệ-thống)

---

## 🚀 CÀI ĐẶT

### Bước 1: Chạy script cài đặt
Truy cập: `http://localhost:20080/setup_shipping_system.php`

Script sẽ tự động:
- ✅ Tạo 7 bảng database
- ✅ Thêm 63 tỉnh/thành Việt Nam
- ✅ Thêm 3 phương thức vận chuyển mặc định
- ✅ Thêm cấu hình phí mẫu

### Bước 2: Kiểm tra hệ thống
Truy cập: `http://localhost:20080/check_shipping_system.php`

---

## 🏗️ CẤU TRÚC HỆ THỐNG

### Database Schema
```
provinces (Tỉnh/Thành phố)
├── districts (Quận/Huyện)
│   └── wards (Phường/Xã)
│
shipping_methods (Phương thức vận chuyển)
│
shipping_fees (Cấu hình phí)
│
shipping_zones (Khu vực giao hàng)
│
shipment_tracking (Lịch sử vận chuyển)
│
don_hang (Đơn hàng - đã cập nhật)
```

### Models (MVC Pattern)
```
lequocanh/administrator/elements_LQA/mod/
├── ProvinceModel.php          # Quản lý tỉnh/thành
├── DistrictModel.php          # Quản lý quận/huyện
├── WardModel.php              # Quản lý phường/xã
├── ShippingMethodModel.php    # Quản lý phương thức vận chuyển
├── ShippingFeeModel.php       # Quản lý phí vận chuyển
└── ShipmentTrackingModel.php  # Theo dõi vận chuyển
```

---

## 💻 SỬ DỤNG MODELS

### 1. ProvinceModel - Quản lý Tỉnh/Thành

```php
require_once 'lequocanh/administrator/elements_LQA/mod/ProvinceModel.php';

$provinceModel = new ProvinceModel();

// Lấy tất cả tỉnh/thành
$provinces = $provinceModel->getAll();

// Lấy theo miền
$northProvinces = $provinceModel->getByRegion('Bắc');

// Tìm kiếm
$results = $provinceModel->search('Hà Nội');

// Thêm mới
$provinceModel->create([
    'code' => 'HN',
    'name' => 'Hà Nội',
    'name_en' => 'Hanoi',
    'region' => 'Bắc'
]);
```

### 2. DistrictModel - Quản lý Quận/Huyện

```php
require_once 'lequocanh/administrator/elements_LQA/mod/DistrictModel.php';

$districtModel = new DistrictModel();

// Lấy quận/huyện theo tỉnh
$districts = $districtModel->getByProvinceId(1); // 1 = Hà Nội

// Thêm mới
$districtModel->create([
    'province_id' => 1,
    'code' => 'HBT',
    'name' => 'Hoàn Kiếm',
    'name_en' => 'Hoan Kiem'
]);
```

### 3. ShippingFeeModel - Tính phí vận chuyển

```php
require_once 'lequocanh/administrator/elements_LQA/mod/ShippingFeeModel.php';

$feeModel = new ShippingFeeModel();

// Tính phí vận chuyển
$result = $feeModel->calculateFee([
    'province_id' => 1,        // Hà Nội
    'district_id' => 5,        // Hoàn Kiếm
    'weight' => 2.5,           // 2.5 kg
    'order_value' => 500000,   // 500,000 VNĐ
    'shipping_method_id' => 1  // Tiêu chuẩn
]);

echo "Phí cơ bản: " . number_format($result['base_fee']) . " đ\n";
echo "Phí theo trọng lượng: " . number_format($result['weight_fee']) . " đ\n";
echo "Tổng phí: " . number_format($result['total_fee']) . " đ\n";
echo "Miễn phí ship: " . ($result['is_free_ship'] ? 'Có' : 'Không') . "\n";
```

### 4. ShipmentTrackingModel - Theo dõi vận chuyển

```php
require_once 'lequocanh/administrator/elements_LQA/mod/ShipmentTrackingModel.php';

$trackingModel = new ShipmentTrackingModel();

// Thêm tracking mới
$trackingModel->addTracking(
    123,                                    // Order ID
    ShipmentTrackingModel::STATUS_SHIPPING, // Trạng thái
    'Đơn hàng đang trên đường giao',       // Mô tả
    'Bưu cục Hà Nội',                      // Vị trí
    'GHN123456',                           // Mã vận đơn
    'ghn'                                  // Đơn vị vận chuyển
);

// Lấy lịch sử tracking
$history = $trackingModel->getByOrderId(123);

// Cập nhật trạng thái đơn hàng
$trackingModel->updateOrderShippingStatus(
    123,
    ShipmentTrackingModel::STATUS_DELIVERED,
    'Giao hàng thành công'
);
```

---

## 🔌 API ENDPOINTS (Cần tạo)

### 1. Lấy danh sách tỉnh/thành
```
GET /api/provinces
Response: [
    {"id": 1, "code": "HN", "name": "Hà Nội", "region": "Bắc"},
    ...
]
```

### 2. Lấy quận/huyện theo tỉnh
```
GET /api/districts?province_id=1
Response: [
    {"id": 1, "name": "Hoàn Kiếm", "province_id": 1},
    ...
]
```

### 3. Lấy phường/xã theo quận
```
GET /api/wards?district_id=1
Response: [
    {"id": 1, "name": "Hàng Bạc", "district_id": 1},
    ...
]
```

### 4. Tính phí vận chuyển
```
POST /api/calculate-shipping-fee
Body: {
    "province_id": 1,
    "district_id": 5,
    "weight": 2.5,
    "order_value": 500000,
    "shipping_method_id": 1
}
Response: {
    "base_fee": 30000,
    "weight_fee": 25000,
    "total_fee": 55000,
    "is_free_ship": false
}
```

### 5. Tracking đơn hàng
```
GET /api/track-order?tracking_code=GHN123456
Response: [
    {
        "status": "delivered",
        "description": "Giao hàng thành công",
        "location": "Hà Nội",
        "created_at": "2025-12-01 14:30:00"
    },
    ...
]
```

---

## 🛒 TÍCH HỢP VÀO CHECKOUT

### Bước 1: Thêm Address Selector vào form checkout

```php
<!-- File: checkout.php -->
<div class="form-group">
    <label>Tỉnh/Thành phố</label>
    <select id="province" name="province_id" class="form-control" required>
        <option value="">-- Chọn tỉnh/thành --</option>
        <?php
        $provinceModel = new ProvinceModel();
        $provinces = $provinceModel->getAll();
        foreach ($provinces as $province) {
            echo "<option value='{$province->id}'>{$province->name}</option>";
        }
        ?>
    </select>
</div>

<div class="form-group">
    <label>Quận/Huyện</label>
    <select id="district" name="district_id" class="form-control" required>
        <option value="">-- Chọn quận/huyện --</option>
    </select>
</div>

<div class="form-group">
    <label>Phường/Xã</label>
    <select id="ward" name="ward_id" class="form-control" required>
        <option value="">-- Chọn phường/xã --</option>
    </select>
</div>

<div class="form-group">
    <label>Phương thức vận chuyển</label>
    <select id="shipping_method" name="shipping_method_id" class="form-control" required>
        <?php
        $methodModel = new ShippingMethodModel();
        $methods = $methodModel->getAll();
        foreach ($methods as $method) {
            echo "<option value='{$method->id}' data-multiplier='{$method->price_multiplier}'>";
            echo "{$method->name} - {$method->delivery_time}";
            echo "</option>";
        }
        ?>
    </select>
</div>

<div class="shipping-fee-display">
    <strong>Phí vận chuyển:</strong> <span id="shipping-fee">0 đ</span>
</div>
```

### Bước 2: JavaScript xử lý

```javascript
// File: checkout.js
$(document).ready(function() {
    // Load quận/huyện khi chọn tỉnh
    $('#province').change(function() {
        const provinceId = $(this).val();
        $('#district').html('<option value="">-- Chọn quận/huyện --</option>');
        $('#ward').html('<option value="">-- Chọn phường/xã --</option>');
        
        if (provinceId) {
            $.get('/api/districts', {province_id: provinceId}, function(data) {
                data.forEach(district => {
                    $('#district').append(`<option value="${district.id}">${district.name}</option>`);
                });
            });
        }
        
        calculateShippingFee();
    });
    
    // Load phường/xã khi chọn quận
    $('#district').change(function() {
        const districtId = $(this).val();
        $('#ward').html('<option value="">-- Chọn phường/xã --</option>');
        
        if (districtId) {
            $.get('/api/wards', {district_id: districtId}, function(data) {
                data.forEach(ward => {
                    $('#ward').append(`<option value="${ward.id}">${ward.name}</option>`);
                });
            });
        }
        
        calculateShippingFee();
    });
    
    // Tính phí khi thay đổi
    $('#ward, #shipping_method').change(calculateShippingFee);
    
    function calculateShippingFee() {
        const provinceId = $('#province').val();
        const districtId = $('#district').val();
        const wardId = $('#ward').val();
        const shippingMethodId = $('#shipping_method').val();
        const orderValue = parseFloat($('#order_total').val() || 0);
        const weight = parseFloat($('#total_weight').val() || 1);
        
        if (!provinceId || !districtId) {
            $('#shipping-fee').text('0 đ');
            return;
        }
        
        $.post('/api/calculate-shipping-fee', {
            province_id: provinceId,
            district_id: districtId,
            ward_id: wardId,
            shipping_method_id: shippingMethodId,
            order_value: orderValue,
            weight: weight
        }, function(result) {
            const fee = result.is_free_ship ? 0 : result.total_fee;
            $('#shipping-fee').text(formatMoney(fee) + ' đ');
            $('#shipping_fee_input').val(fee);
            
            if (result.is_free_ship) {
                $('#shipping-fee').append(' <span class="badge badge-success">MIỄN PHÍ</span>');
            }
            
            updateTotalAmount();
        });
    }
    
    function formatMoney(amount) {
        return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
});
```

---

## 🔧 MỞ RỘNG HỆ THỐNG

### 1. Tích hợp GHN API (Miễn phí)

```php
// File: GHNService.php
class GHNService
{
    private $apiToken;
    private $shopId;
    private $endpoint;
    
    public function __construct()
    {
        $this->apiToken = $_ENV['GHN_API_TOKEN'];
        $this->shopId = $_ENV['GHN_SHOP_ID'];
        $this->endpoint = $_ENV['GHN_API_ENDPOINT'];
    }
    
    public function calculateFee($params)
    {
        // Gọi API GHN để tính phí
        $url = $this->endpoint . '/shipping-order/fee';
        
        $data = [
            'service_type_id' => 2,
            'to_district_id' => $params['district_id'],
            'to_ward_code' => $params['ward_code'],
            'weight' => $params['weight'] * 1000, // Convert to gram
            'insurance_value' => $params['order_value']
        ];
        
        $response = $this->callAPI('POST', $url, $data);
        return $response['data']['total'] ?? 0;
    }
    
    public function createOrder($orderData)
    {
        // Tạo đơn vận chuyển trên GHN
        $url = $this->endpoint . '/shipping-order/create';
        return $this->callAPI('POST', $url, $orderData);
    }
    
    private function callAPI($method, $url, $data = [])
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Token: ' . $this->apiToken,
            'ShopId: ' . $this->shopId,
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
```

### 2. Thêm phí bổ sung

```sql
-- Thêm cột vào bảng shipping_fees
ALTER TABLE shipping_fees 
ADD COLUMN packaging_fee DECIMAL(15,2) DEFAULT 0 COMMENT 'Phí đóng gói',
ADD COLUMN insurance_fee_percent DECIMAL(5,2) DEFAULT 0 COMMENT 'Phí bảo hiểm (%)',
ADD COLUMN cod_fee_percent DECIMAL(5,2) DEFAULT 0 COMMENT 'Phí COD (%)';
```

### 3. Thêm khu vực đặc biệt

```php
// Thêm phụ phí cho vùng xa, hải đảo
$feeModel->create([
    'name' => 'Phụ phí vùng xa',
    'province_id' => 15, // Cà Mau
    'base_fee' => 50000,
    'fee_per_kg' => 15000,
    'priority' => 20
]);
```

---

## ✅ CHECKLIST TRIỂN KHAI

- [x] Tạo database schema
- [x] Tạo Models (MVC)
- [ ] Tạo Controllers
- [ ] Tạo Views (Admin)
- [ ] Tạo API Endpoints
- [ ] Tích hợp vào Checkout
- [ ] Thêm quận/huyện, phường/xã
- [ ] Test tính phí
- [ ] Tích hợp GHN (tùy chọn)
- [ ] Tạo trang tracking công khai

---

## 📞 HỖ TRỢ

Hệ thống này hoàn toàn **MIỄN PHÍ** và có thể mở rộng không giới hạn.

**Tính năng sẵn có:**
✅ Quản lý 63 tỉnh/thành Việt Nam  
✅ Cấu hình phí linh hoạt  
✅ Nhiều phương thức vận chuyển  
✅ Tracking vận chuyển  
✅ Miễn phí ship theo điều kiện  
✅ Tính phí theo trọng lượng  
✅ Sẵn sàng tích hợp API bên thứ 3  

**Có thể mở rộng:**
🔧 Tích hợp GHN, GHTK, Viettel Post  
🔧 Phí bảo hiểm, đóng gói  
🔧 Tính phí theo khoảng cách  
🔧 Dashboard báo cáo  
🔧 Thông báo tự động  

---

**Phát triển bởi:** Kiro AI  
**Ngày:** 01/12/2025  
**Phiên bản:** 1.0
