# Security Hardening & Core Models Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix critical security vulnerabilities and create missing core models (Order, Customer, Cart, Payment) for the LeQuocAnh Shop e-commerce application.

**Architecture:** Security fixes applied to existing files (database.php, GHNApiCls.php, MoMoPayment.php, CSP headers). New models follow existing BaseModel pattern with PDO prepared statements.

**Tech Stack:** PHP 7.4+, PDO, MySQL 8.0, existing BaseModel ORM

---

## Phase 1: Security Fixes (P0 - Critical)

### Task 1: Fix Hardcoded Database Credentials

**Files:**
- Modify: `lequocanh/administrator/elements_LQA/mod/database.php`

**Problem:** 10 hardcoded fallback credentials in connection array. Attacker knows all possible passwords.

- [ ] **Step 1: Remove hardcoded fallback connections**

Replace the entire `$connectionConfigs` array with single env-only connection:

```php
// BEFORE (database.php lines ~30-45):
$connectionConfigs = [
    ['host' => $servername, 'port' => $port, 'user' => $username, 'pass' => $password, 'dbname' => $dbname],
    ['host' => 'mysql', 'port' => 3306, 'user' => 'root', 'pass' => 'root', 'dbname' => $dbname],
    // ... 8 more hardcoded entries
];

// AFTER:
$connectionConfigs = [
    ['host' => $servername, 'port' => $port, 'user' => $username, 'pass' => $password, 'dbname' => $dbname],
];
```

- [ ] **Step 2: Remove default password fallbacks**

```php
// BEFORE (database.php line ~30):
$password = $_ENV['DB_PASSWORD'] ?? $config['section']['password'] ?? 'pw';

// AFTER:
$password = $_ENV['DB_PASSWORD'] ?? $config['section']['password'] ?? '';
```

Remove `'pw'` default - if no password configured, fail explicitly.

- [ ] **Step 3: Fix DatabaseOptimized.php**

```php
// BEFORE (DatabaseOptimized.php line 21):
$password = $_ENV['DB_PASSWORD'] ?? 'pw';

// AFTER:
$password = $_ENV['DB_PASSWORD'] ?? '';
```

- [ ] **Step 4: Verify .env has no real credentials exposed**

Ensure `.env` is in `.gitignore` and `.env.example` has placeholders only.

- [ ] **Step 5: Commit**

```bash
git add lequocanh/administrator/elements_LQA/mod/database.php lequocanh/administrator/elements_LQA/mod/DatabaseOptimized.php
git commit -m "security: remove hardcoded database credentials, use env-only"
```

---

### Task 2: Fix SSL Verification Disabled

**Files:**
- Modify: `lequocanh/administrator/elements_LQA/mod/GHNApiCls.php:409`
- Modify: `lequocanh/payment/MoMoPayment.php:49`

- [ ] **Step 1: Fix GHNApiCls.php**

```php
// BEFORE (line 409):
CURLOPT_SSL_VERIFYPEER => false,

// AFTER:
CURLOPT_SSL_VERIFYPEER => true,
CURLOPT_SSL_VERIFYHOST => 2,
```

- [ ] **Step 2: Fix MoMoPayment.php**

```php
// BEFORE (line 49):
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// AFTER:
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
```

- [ ] **Step 3: Commit**

```bash
git add lequocanh/administrator/elements_LQA/mod/GHNApiCls.php lequocanh/payment/MoMoPayment.php
git commit -m "security: enable SSL verification for GHN and MoMo APIs"
```

---

### Task 3: Fix CSP unsafe-inline/unsafe-eval

**Files:**
- Modify: `auto_prepend.php:7`
- Modify: `lequocanh/administrator/elements_LQA/config/security_config.php:62`
- Modify: `lequocanh/administrator/elements_LQA/security/advancedSecurity.php:82-83`
- Modify: `lequocanh/api/middleware/ApiSecurityMiddleware.php:82`
- Modify: `security.php:25-26`

- [ ] **Step 1: Fix auto_prepend.php**

```php
// BEFORE (line 7):
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https:; img-src 'self' data: https:; font-src 'self' data: https:;");

// AFTER:
header("Content-Security-Policy: default-src 'self' https:; script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' data: https:;");
```

- [ ] **Step 2: Fix security_config.php**

```php
// BEFORE (line 62):
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;");

// AFTER:
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;");
```

- [ ] **Step 3: Fix advancedSecurity.php**

```php
// BEFORE (lines 82-83):
"script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
"style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .

// AFTER:
"script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
"style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
```

- [ ] **Step 4: Fix ApiSecurityMiddleware.php**

```php
// BEFORE (line 82):
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https:; img-src 'self' data: https:; font-src 'self' data: https:;");

// AFTER:
header("Content-Security-Policy: default-src 'self' https:; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' data: https:;");
```

- [ ] **Step 5: Fix security.php**

```php
// BEFORE (lines 25-26):
"script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com",
"style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",

// AFTER:
"script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com",
"style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com",
```

- [ ] **Step 6: Commit**

```bash
git add auto_prepend.php lequocanh/administrator/elements_LQA/config/security_config.php lequocanh/administrator/elements_LQA/security/advancedSecurity.php lequocanh/api/middleware/ApiSecurityMiddleware.php security.php
git commit -m "security: remove unsafe-inline and unsafe-eval from CSP headers"
```

---

### Task 4: Add Input Sanitization Helper

**Files:**
- Create: `lequocanh/app/Helpers/Input.php`

- [ ] **Step 1: Create Input helper class**

```php
<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Centralized input sanitization helper.
 * Use this instead of directly accessing $_GET/$_POST/$_REQUEST.
 */
class Input
{
    /**
     * Get sanitized GET parameter.
     */
    public static function get(string $key, $default = null)
    {
        return self::sanitize($_GET[$key] ?? $default);
    }

    /**
     * Get sanitized POST parameter.
     */
    public static function post(string $key, $default = null)
    {
        return self::sanitize($_POST[$key] ?? $default);
    }

    /**
     * Get sanitized parameter from GET or POST (POST takes precedence).
     */
    public static function input(string $key, $default = null)
    {
        return self::sanitize($_POST[$key] ?? $_GET[$key] ?? $default);
    }

    /**
     * Get all GET parameters, sanitized.
     */
    public static function allGet(): array
    {
        return array_map([self::class, 'sanitize'], $_GET);
    }

    /**
     * Get all POST parameters, sanitized.
     */
    public static function allPost(): array
    {
        return array_map([self::class, 'sanitize'], $_POST);
    }

    /**
     * Get raw (unsanitized) value - use only when you need to preserve formatting.
     * Always sanitize before using in SQL queries.
     */
    public static function raw(string $key, string $source = 'input', $default = null)
    {
        switch ($source) {
            case 'get': return $_GET[$key] ?? $default;
            case 'post': return $_POST[$key] ?? $default;
            default: return $_POST[$key] ?? $_GET[$key] ?? $default;
        }
    }

    /**
     * Sanitize a single value.
     */
    public static function sanitize($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        if (!is_string($value)) {
            return $value;
        }
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get integer parameter.
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = self::input($key, $default);
        return filter_var($value, FILTER_VALIDATE_INT) ?: $default;
    }

    /**
     * Get float parameter.
     */
    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::input($key, $default);
        return filter_var($value, FILTER_VALIDATE_FLOAT) ?: $default;
    }

    /**
     * Get email parameter, validated.
     */
    public static function email(string $key, $default = null): ?string
    {
        $value = self::input($key, $default);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ?: $default;
    }

    /**
     * Check if request method is POST.
     */
    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    /**
     * Check if request method is GET.
     */
    public static function isGet(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
    }
}
```

- [ ] **Step 2: Add autoload for Helpers namespace**

Check if `composer.json` already has `App\\Helpers\\` in autoload. If not, add it:

```json
"autoload": {
    "psr-4": {
        "App\\": "lequocanh/app/",
        "App\\Helpers\\": "lequocanh/app/Helpers/"
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add lequocanh/app/Helpers/Input.php composer.json
git commit -m "feat: add Input helper for centralized sanitization"
```

---

## Phase 2: Core Models (P1 - High Priority)

### Task 5: Create Customer/User Model

**Files:**
- Create: `lequocanh/app/Models/Customer.php`

- [ ] **Step 1: Create Customer model**

```php
<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Customer model representing registered users.
 * Maps to 'user' table in legacy database.
 */
class Customer extends BaseModel
{
    protected static $table = 'user';
    protected static $primaryKey = 'iduser';
    protected static $timestamps = false;
    protected static $fillable = [
        'username',
        'hoten',
        'email',
        'sodienthoai',
        'diachi',
        'avatar_url',
        'auth_provider',
        'google_id',
        'facebook_id',
    ];
    protected static $hidden = [
        'password',
    ];

    /**
     * Find customer by email.
     */
    public static function findByEmail(string $email): ?self
    {
        $results = self::where('email', '=', $email);
        return $results[0] ?? null;
    }

    /**
     * Find customer by username.
     */
    public static function findByUsername(string $username): ?self
    {
        $results = self::where('username', '=', $username);
        return $results[0] ?? null;
    }

    /**
     * Find customer by OAuth provider ID.
     */
    public static function findByProvider(string $provider, string $providerId): ?self
    {
        $field = $provider . '_id';
        $results = self::where($field, '=', $providerId);
        return $results[0] ?? null;
    }

    /**
     * Get customer's orders.
     */
    public function orders(): array
    {
        return Order::findByCustomer((int)$this->getKey());
    }

    /**
     * Get display name (hoten or username).
     */
    public function getDisplayName(): string
    {
        return $this->hoten ?: $this->username;
    }

    /**
     * Check if customer has password set.
     */
    public function hasPassword(): bool
    {
        return !empty($this->password);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add lequocanh/app/Models/Customer.php
git commit -m "feat: add Customer model"
```

---

### Task 6: Create Order Model

**Files:**
- Create: `lequocanh/app/Models/Order.php`

- [ ] **Step 1: Create Order model**

```php
<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Order model for e-commerce orders.
 * Maps to 'donhang' table in legacy database.
 */
class Order extends BaseModel
{
    protected static $table = 'donhang';
    protected static $primaryKey = 'iddonhang';
    protected static $timestamps = false;
    protected static $fillable = [
        'iduser',
        'hoten',
        'sodienthoai',
        'email',
        'diachi',
        'ghichu',
        'tongtien',
        'trangthai',
        'phuongthucthanhtoan',
        'ngaydat',
        'ngaygiao',
        'magiamgia',
        'phi_ship',
    ];

    // Order status constants
    const STATUS_PENDING = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_SHIPPING = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_CANCELLED = 4;
    const STATUS_RETURNED = 5;

    /**
     * Find orders by customer ID.
     *
     * @return Order[]
     */
    public static function findByCustomer(int $customerId): array
    {
        return self::where('iduser', '=', $customerId);
    }

    /**
     * Find orders by status.
     *
     * @return Order[]
     */
    public static function findByStatus(int $status): array
    {
        return self::where('trangthai', '=', $status);
    }

    /**
     * Get order items.
     *
     * @return OrderItem[]
     */
    public function items(): array
    {
        return OrderItem::findByOrder((int)$this->getKey());
    }

    /**
     * Get customer who placed this order.
     */
    public function customer(): ?Customer
    {
        return Customer::find((int)$this->iduser);
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Chờ xác nhận',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_SHIPPING => 'Đang giao hàng',
            self::STATUS_DELIVERED => 'Đã giao hàng',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_RETURNED => 'Đã trả hàng',
        ];
        return $labels[(int)$this->trangthai] ?? 'Không xác định';
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array((int)$this->trangthai, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
        ]);
    }

    /**
     * Get formatted total with currency.
     */
    public function getFormattedTotal(): string
    {
        return number_format((float)$this->tongtien, 0, ',', '.') . 'đ';
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add lequocanh/app/Models/Order.php
git commit -m "feat: add Order model"
```

---

### Task 7: Create OrderItem Model

**Files:**
- Create: `lequocanh/app/Models/OrderItem.php`

- [ ] **Step 1: Create OrderItem model**

```php
<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Order item model representing line items in an order.
 * Maps to 'donhang_chitiet' or 'order_items' table.
 */
class OrderItem extends BaseModel
{
    protected static $table = 'donhang_chitiet';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;
    protected static $fillable = [
        'iddonhang',
        'idhanghoa',
        'tenhanghoa',
        'soluong',
        'dongia',
        'thanhtien',
        'mausac',
        'kichco',
    ];

    /**
     * Find all items for a specific order.
     *
     * @return OrderItem[]
     */
    public static function findByOrder(int $orderId): array
    {
        return self::where('iddonhang', '=', $orderId);
    }

    /**
     * Get the product associated with this item.
     */
    public function product(): ?Product
    {
        return Product::find((int)$this->idhanghoa);
    }

    /**
     * Get the parent order.
     */
    public function order(): ?Order
    {
        return Order::find((int)$this->iddonhang);
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPrice(): string
    {
        return number_format((float)$this->dongia, 0, ',', '.') . 'đ';
    }

    /**
     * Get formatted subtotal.
     */
    public function getFormattedSubtotal(): string
    {
        return number_format((float)$this->thanhtien, 0, ',', '.') . 'đ';
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add lequocanh/app/Models/OrderItem.php
git commit -m "feat: add OrderItem model"
```

---

### Task 8: Create Cart Model

**Files:**
- Create: `lequocanh/app/Models/Cart.php`

- [ ] **Step 1: Create Cart model**

```php
<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Cart model for shopping cart functionality.
 * Maps to 'giohang' table in legacy database.
 */
class Cart extends BaseModel
{
    protected static $table = 'giohang';
    protected static $primaryKey = 'idgiohang';
    protected static $timestamps = false;
    protected static $fillable = [
        'iduser',
        'idhanghoa',
        'soluong',
        'ngaythem',
    ];

    /**
     * Find cart items for a user.
     *
     * @return Cart[]
     */
    public static function findByUser(int $userId): array
    {
        return self::where('iduser', '=', $userId);
    }

    /**
     * Find specific cart item for user and product.
     */
    public static function findItem(int $userId, int $productId): ?self
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM " . static::$table . " WHERE iduser = ? AND idhanghoa = ? LIMIT 1"
        );
        $stmt->execute([$userId, $productId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $model = new static($row);
        $model->exists = true;
        return $model;
    }

    /**
     * Get the product in this cart item.
     */
    public function product(): ?Product
    {
        return Product::find((int)$this->idhanghoa);
    }

    /**
     * Get cart item count for a user.
     */
    public static function countForUser(int $userId): int
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM " . static::$table . " WHERE iduser = ?"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Clear all items for a user.
     */
    public static function clearForUser(int $userId): bool
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "DELETE FROM " . static::$table . " WHERE iduser = ?"
        );
        return $stmt->execute([$userId]);
    }

    /**
     * Add item to cart (or update quantity if exists).
     */
    public static function addOrUpdate(int $userId, int $productId, int $quantity = 1): self
    {
        $existing = self::findItem($userId, $productId);

        if ($existing) {
            $existing->soluong = (int)$existing->soluong + $quantity;
            $existing->save();
            return $existing;
        }

        return self::create([
            'iduser' => $userId,
            'idhanghoa' => $productId,
            'soluong' => $quantity,
            'ngaythem' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add lequocanh/app/Models/Cart.php
git commit -m "feat: add Cart model"
```

---

### Task 9: Create Payment Model

**Files:**
- Create: `lequocanh/app/Models/Payment.php`

- [ ] **Step 1: Create Payment model**

```php
<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Payment model for tracking payment transactions.
 * Maps to 'thanhtoan' table.
 */
class Payment extends BaseModel
{
    protected static $table = 'thanhtoan';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;
    protected static $fillable = [
        'iddonhang',
        'phuongthuc',
        'sotien',
        'trangthai',
        'transaction_id',
        'ngaythanhtoan',
        'ghichu',
    ];

    // Payment status constants
    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_FAILED = 2;
    const STATUS_REFUNDED = 3;

    // Payment method constants
    const METHOD_COD = 'cod';
    const METHOD_MOMO = 'momo';
    const METHOD_BANK = 'bank';
    const METHOD_VNPAY = 'vnpay';

    /**
     * Find payment by order ID.
     */
    public static function findByOrder(int $orderId): ?self
    {
        $results = self::where('iddonhang', '=', $orderId);
        return $results[0] ?? null;
    }

    /**
     * Find payments by status.
     *
     * @return Payment[]
     */
    public static function findByStatus(int $status): array
    {
        return self::where('trangthai', '=', $status);
    }

    /**
     * Get the order associated with this payment.
     */
    public function order(): ?Order
    {
        return Order::find((int)$this->iddonhang);
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Chờ thanh toán',
            self::STATUS_COMPLETED => 'Đã thanh toán',
            self::STATUS_FAILED => 'Thanh toán thất bại',
            self::STATUS_REFUNDED => 'Đã hoàn tiền',
        ];
        return $labels[(int)$this->trangthai] ?? 'Không xác định';
    }

    /**
     * Get human-readable method label.
     */
    public function getMethodLabel(): string
    {
        $labels = [
            self::METHOD_COD => 'Thanh toán khi nhận hàng',
            self::METHOD_MOMO => 'Ví MoMo',
            self::METHOD_BANK => 'Chuyển khoản ngân hàng',
            self::METHOD_VNPAY => 'VNPay',
        ];
        return $labels[$this->phuongthuc] ?? $this->phuongthuc;
    }

    /**
     * Mark payment as completed.
     */
    public function markCompleted(string $transactionId = ''): bool
    {
        $this->trangthai = self::STATUS_COMPLETED;
        $this->ngaythanhtoan = date('Y-m-d H:i:s');
        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }
        return $this->save();
    }

    /**
     * Mark payment as failed.
     */
    public function markFailed(string $reason = ''): bool
    {
        $this->trangthai = self::STATUS_FAILED;
        if ($reason) {
            $this->ghichu = $reason;
        }
        return $this->save();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add lequocanh/app/Models/Payment.php
git commit -m "feat: add Payment model"
```

---

## Phase 3: BaseController Input Integration (P2)

### Task 10: Update BaseController to Use Input Helper

**Files:**
- Modify: `lequocanh/app/Controllers/BaseController.php`

- [ ] **Step 1: Add import and update input() method**

```php
// Add at top of file:
use App\Helpers\Input;

// Update input() method in BaseController:
protected function input($key = null, $default = null)
{
    if ($key === null) {
        return Input::allPost() + Input::allGet();
    }
    return Input::input($key, $default);
}
```

- [ ] **Step 2: Commit**

```bash
git add lequocanh/app/Controllers/BaseController.php
git commit -m "refactor: use Input helper in BaseController"
```

---

## Summary

| Task | Priority | Status |
|------|----------|--------|
| 1. Fix hardcoded credentials | P0 Critical | ⬜ |
| 2. Fix SSL verification | P0 Critical | ⬜ |
| 3. Fix CSP unsafe-inline/eval | P0 Critical | ⬜ |
| 4. Add Input helper | P0 High | ⬜ |
| 5. Customer model | P1 High | ⬜ |
| 6. Order model | P1 High | ⬜ |
| 7. OrderItem model | P1 High | ⬜ |
| 8. Cart model | P1 High | ⬜ |
| 9. Payment model | P1 High | ⬜ |
| 10. BaseController update | P2 Medium | ⬜ |
