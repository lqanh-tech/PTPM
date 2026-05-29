# IMPROVEMENT REPORT
Generated: 2026-05-27

## Current Status
- ✅ PHPStan: 0 errors
- ✅ PHPUnit: 88/88 pass
- ✅ Runtime tests: All pass

---

## Priority 1: High Impact Improvements

### 1.1 Add strict_types to remaining files (7 files)
```php
// Add to top of each file:
declare(strict_types=1);
```

Files missing:
- `autoload.php`
- `BaseController.php`
- `EmailService.php`
- `ImageOptimizer.php`
- `LanguageManager.php`
- `lang/en.php`
- `lang/vi.php`

### 1.2 Add return type hints to BaseController methods
```php
// Current:
protected function view($viewName, $data = [])
protected function render($viewName, $data = [])
protected function redirect($url, $statusCode = 302)
protected function json($data, $statusCode = 200)

// Improved:
protected function view(string $viewName, array $data = []): string
protected function render(string $viewName, array $data = []): void
protected function redirect(string $url, int $statusCode = 302): never
protected function json(array $data, int $statusCode = 200): never
```

### 1.3 Add type hints to BaseModel
```php
// Current:
public function __get($key)
public function getKey()

// Improved:
public function __get(string $key): mixed
public function getKey(): int|string|null
```

---

## Priority 2: Code Quality Improvements

### 2.1 Extract constants for magic numbers
```php
// Current:
if ($status == 0) { ... }
if ($quantity > 0) { ... }

// Improved:
const STATUS_INACTIVE = 0;
const MIN_QUANTITY = 0;

if ($status === self::STATUS_INACTIVE) { ... }
if ($quantity > self::MIN_QUANTITY) { ... }
```

### 2.2 Improve error handling in Product model
```php
// Current:
catch (\PDOException $e) {
    error_log("Product::error: " . $e->getMessage());
    return [];
}

// Improved:
catch (\PDOException $e) {
    $context = [
        'method' => __METHOD__,
        'params' => func_get_args(),
        'error' => $e->getMessage(),
    ];
    error_log("Product error: " . json_encode($context));
    return [];
}
```

### 2.3 Add validation to Product::addProduct()
```php
// Add input validation:
if (!is_numeric($giathamkhao) || $giathamkhao < 0) {
    throw new InvalidArgumentException('Price must be a positive number');
}

if (!is_numeric($idloaihang) || $idloaihang <= 0) {
    throw new InvalidArgumentException('Invalid category ID');
}
```

---

## Priority 3: Architecture Improvements

### 3.1 Create Repository Pattern for complex queries
```php
// New file: app/Repositories/ProductRepository.php
class ProductRepository
{
    public function findWithRelations(int $id): ?Product
    public function searchWithFilters(array $filters): array
    public function getFeaturedWithPricing(int $limit): array
}
```

### 3.2 Create Form Request validation
```php
// New file: app/Requests/ProductRequest.php
class ProductRequest
{
    public function rules(): array
    public function messages(): array
    public function validate(): array
}
```

### 3.3 Add caching layer
```php
// In BaseModel:
protected static function cachedFind(int $id, int $ttl = 300): ?static
{
    $cacheKey = static::getTable() . ":{$id}";
    return Cache::remember($cacheKey, $ttl, fn() => static::find($id));
}
```

---

## Priority 4: Testing Improvements

### 4.1 Add unit tests for new methods
```php
// tests/Unit/BaseModelTest.php
public function testWhereWithInvalidColumnThrowsException()
public function testValidateOperatorRejectsInvalidOperators()
public function testWhereMultipleBuildsCorrectQuery()
```

### 4.2 Add integration tests
```php
// tests/Integration/ProductServiceTest.php
public function testCreateProductWithInventory()
public function testDeleteProductWithRelatedData()
public function testSearchProductsByKeyword()
```

### 4.3 Add feature tests
```php
// tests/Feature/ProductControllerTest.php
public function testIndexPageLoadsProducts()
public function testStoreCreatesProduct()
public function testDeleteRemovesProduct()
```

---

## Priority 5: Performance Improvements

### 5.1 Optimize Product::getAllWithPricing()
```php
// Add pagination:
public static function getAllWithPricing(int $page = 1, int $perPage = 20): array
{
    $offset = ($page - 1) * $perPage;
    $sql .= " LIMIT {$perPage} OFFSET {$offset}";
}
```

### 5.2 Add database indexes
```sql
-- Recommended indexes:
CREATE INDEX idx_hanghoa_idloaihang ON hanghoa(idloaihang);
CREATE INDEX idx_hanghoa_trang_thai ON hanghoa(trang_thai);
CREATE INDEX idx_don_hang_ma_nguoi_dung ON don_hang(ma_nguoi_dung);
CREATE INDEX idx_don_hang_trang_thai ON don_hang(trang_thai);
```

### 5.3 Implement query result caching
```php
// In Services:
public function getActiveShippingMethods(): array
{
    return Cache::remember('shipping_methods_active', 3600, function() {
        // ... query
    });
}
```

---

## Priority 6: Security Improvements

### 6.1 Add rate limiting to authentication
```php
// In AuthController:
public function login(): void
{
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!Security::checkRateLimit("login:{$ip}", 5, 300)) {
        $this->json(['error' => 'Too many attempts'], 429);
    }
}
```

### 6.2 Add CSRF protection to all forms
```php
// In views:
<input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">

// In controllers:
if (!Security::validateCSRFToken($this->input('csrf_token'))) {
    $this->json(['error' => 'Invalid CSRF token'], 403);
}
```

### 6.3 Sanitize all output
```php
// In views:
<?= htmlspecialchars($product->tenhanghoa, ENT_QUOTES, 'UTF-8') ?>
```

---

## Quick Wins (Can do now)

### QW1: Fix BaseController types
```bash
# Add return types to BaseController methods
```

### QW2: Add strict_types to files
```bash
# Add declare(strict_types=1) to 7 files
```

### QW3: Improve error logging
```bash
# Add context to error_log calls
```

---

## Summary

| Category | Items | Priority |
|----------|-------|----------|
| Type Safety | 7 files + methods | High |
| Code Quality | 3 improvements | Medium |
| Architecture | 3 patterns | Medium |
| Testing | 3 test types | High |
| Performance | 3 optimizations | Medium |
| Security | 3 hardening | High |

**Recommended next step:** Fix Priority 1 (type safety) - quick win, high impact.
