<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Product;
use App\Presenters\ProductPresenter;

class ProductTest extends TestCase
{
    // ─── Constants ────────────────────────────────────────────────

    public function testStatusConstants(): void
    {
        $this->assertEquals(1, Product::STATUS_ACTIVE);
        $this->assertEquals(2, Product::STATUS_DISCONTINUED);
        $this->assertEquals(3, Product::STATUS_OUT_OF_STOCK);
    }

    // ─── Table Configuration ──────────────────────────────────────

    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('hanghoa', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('idhanghoa', $property->getValue());
    }

    public function testTimestampsDisabled(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue());
    }

    // ─── Fillable Attributes ──────────────────────────────────────

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('tenhanghoa', $fillable);
        $this->assertContains('mota', $fillable);
        $this->assertContains('giathamkhao', $fillable);
        $this->assertContains('giakhuyenmai', $fillable);
        $this->assertContains('hinhanh', $fillable);
        $this->assertContains('idloaihang', $fillable);
        $this->assertContains('idThuongHieu', $fillable);
        $this->assertContains('idDonViTinh', $fillable);
        $this->assertContains('idNhanVien', $fillable);
        $this->assertContains('ghichu', $fillable);
        $this->assertContains('trang_thai', $fillable);
    }

    // ─── Static Methods Exist ─────────────────────────────────────

    public function testGetAllWithPricingMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getAllWithPricing'));
    }

    public function testGetByIdMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getById'));
    }

    public function testGetByCategoryWithPricingMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getByCategoryWithPricing'));
    }

    public function testSearchProductsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'searchProducts'));
    }

    public function testFilterProductsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'filterProducts'));
    }

    public function testGetFilterOptionsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getFilterOptions'));
    }

    public function testGetRelatedProductsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getRelatedProducts'));
    }

    public function testAddProductMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'addProduct'));
    }

    public function testUpdateProductMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'updateProduct'));
    }

    public function testDeleteProductMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'deleteProduct'));
    }

    public function testUpdatePriceMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'updatePrice'));
    }

    // ─── Status Methods ───────────────────────────────────────────

    public function testGetProductStatusMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getProductStatus'));
    }

    public function testGetProductStatusValueMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getProductStatusValue'));
    }

    public function testUpdateProductStatusMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'updateProductStatus'));
    }

    public function testGetProductsByStatusMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getProductsByStatus'));
    }

    public function testGetDiscontinuedProductsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getDiscontinuedProducts'));
    }

    public function testGetOutOfStockProductsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getOutOfStockProducts'));
    }

    // ─── Status Display Methods ───────────────────────────────────

    public function testGetStatusCssClassReturnsCorrectClasses(): void
    {
        $this->assertEquals('status-active', ProductPresenter::cssClass('Đang bán'));
        $this->assertEquals('status-discontinued', ProductPresenter::cssClass('Ngừng bán'));
        $this->assertEquals('status-outofstock', ProductPresenter::cssClass('Hết hàng'));
        $this->assertEquals('status-unknown', ProductPresenter::cssClass('Unknown'));
    }

    public function testGetStatusColorReturnsCorrectColors(): void
    {
        $this->assertEquals('#27ae60', ProductPresenter::color('Đang bán'));
        $this->assertEquals('#e74c3c', ProductPresenter::color('Ngừng bán'));
        $this->assertEquals('#95a5a6', ProductPresenter::color('Hết hàng'));
        $this->assertEquals('#34495e', ProductPresenter::color('Unknown'));
    }

    // ─── Reference Methods ────────────────────────────────────────

    public function testGetAllThuongHieuMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getAllThuongHieu'));
    }

    public function testGetAllDonViTinhMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getAllDonViTinh'));
    }

    public function testGetAllNhanVienMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getAllNhanVien'));
    }

    public function testGetThuongHieuByIdMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getThuongHieuById'));
    }

    // ─── Related Data Check ───────────────────────────────────────

    public function testCheckRelatedDataMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'checkRelatedData'));
    }

    // ─── Featured/New/Sale Methods ────────────────────────────────

    public function testGetFeaturedProductsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getFeaturedProducts'));
    }

    public function testGetNewProductsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getNewProducts'));
    }

    public function testGetSaleProductsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getSaleProducts'));
    }

    public function testGetMostViewedProductsMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getMostViewedProducts'));
    }

    // ─── Instance Methods ─────────────────────────────────────────

    public function testGetImageUrlMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getImageUrl'));
    }

    public function testGetFormattedPriceMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getFormattedPrice'));
    }

    public function testHasImageMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'hasImage'));
    }

    public function testGetCategoryMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getCategory'));
    }

    public function testGetBrandMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getBrand'));
    }

    public function testGetStockMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getStock'));
    }

    public function testIsInStockMethodExists(): void
    {
        $this->assertTrue(method_exists(Product::class, 'isInStock'));
    }

    // ─── Validation Rules ─────────────────────────────────────────

    public function testGetValidationRulesReturnsArray(): void
    {
        $rules = Product::getValidationRules();
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('tenhanghoa', $rules);
        $this->assertArrayHasKey('giathamkhao', $rules);
        $this->assertArrayHasKey('idloaihang', $rules);
        $this->assertArrayHasKey('mota', $rules);
    }
}
