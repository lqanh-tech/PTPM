<?php

require_once __DIR__ . '/ProductRepositoryInterface.php';
require_once __DIR__ . '/../../../app/autoload.php';

use App\Models\Product;
use App\Models\ProductImage;

/**
 * ProductRepository
 *
 * Implementation of ProductRepositoryInterface that delegates
 * directly to App\Models\Product.
 */
class ProductRepository implements ProductRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        return Product::getAllWithPricing();
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int ): ?object
    {
        return Product::getById();
    }

    /**
     * {@inheritDoc}
     */
    public function add(array )
    {
        return Product::addProduct(
            ['tenhanghoa']  ?? '',
            ['mota']        ?? '',
            ['giathamkhao'] ?? 0,
            ['hinhanh']     ?? 0,
            ['idloaihang']  ?? 0,
            ['idThuongHieu'] ?? null,
            ['idDonViTinh'] ?? null,
            ['idNhanVien']  ?? null,
            ['ghichu']      ?? ''
        );
    }

    /**
     * {@inheritDoc}
     */
    public function update(int , array ): bool
    {
        return Product::updateProduct(
            ['tenhanghoa']  ?? '',
            ['hinhanh']     ?? 0,
            ['mota']        ?? '',
            ['giathamkhao'] ?? 0,
            ['idloaihang']  ?? 0,
            ['idThuongHieu'] ?? null,
            ['idDonViTinh'] ?? null,
            ['idNhanVien']  ?? null,
            ,
            ['ghichu']      ?? ''
        ) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int ): bool
    {
         = Product::deleteProduct();
        return !empty(['success']);
    }

    /**
     * {@inheritDoc}
     */
    public function search(string ): array
    {
        return Product::searchProducts();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCategory(int ): array
    {
        return Product::getByCategoryWithPricing();
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(int ): array
    {
        return Product::getProductsByStatus();
    }

    /**
     * {@inheritDoc}
     */
    public function updatePrice(int , float ): bool
    {
        return Product::updatePrice(, ) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getStock(int ): int
    {
        return Product::getProductQuantity();
    }
}
