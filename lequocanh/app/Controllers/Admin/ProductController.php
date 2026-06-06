<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Helpers\CsrfProtection;
use App\Models\Product;
use App\Services\CategoryService;
use Exception;

class ProductController extends BaseController
{
    use CsrfProtection;

    /**
     * Display product list with pagination.
     */
    public function index(): void
    {
        $this->requireAuth();

        try {
            $page = (int) ($this->input('page') ?? 1);
            $perPage = (int) ($this->input('per_page') ?? 20);
            $search = $this->input('search');
            $categoryId = $this->input('category_id');

            // Build conditions
            $conditions = [];
            if ($categoryId) {
                $conditions['idloaihang'] = $categoryId;
            }

            // Get paginated results
            $result = Product::paginate($page, $perPage, $conditions);

            // Apply search filter if needed
            if ($search) {
                $result['data'] = array_filter($result['data'], function ($product) use ($search) {
                    return stripos($product->tenhanghoa, $search) !== false;
                });
            }

            $data = [
                'products' => $result['data'],
                'pagination' => [
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'per_page' => $result['per_page'],
                    'total' => $result['total'],
                    'from' => $result['from'],
                    'to' => $result['to'],
                ],
                'title' => 'Product Management',
                'categories' => $this->getCategories(),
                'filters' => [
                    'search' => $search,
                    'category_id' => $categoryId,
                    'per_page' => $perPage,
                ],
                'success_message' => $this->getFlash('success'),
                'error_message' => $this->getFlash('error'),
            ];

            $this->render('admin.products.index', $data);
        } catch (Exception $e) {
            Logger::error('ProductController::index', ['error' => $e->getMessage()]);
            $this->render('admin.products.index', [
                'products' => [],
                'pagination' => [],
                'error_message' => 'Error loading products: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show create product form.
     */
    public function create(): void
    {
        $this->requireAuth();

        $data = [
            'title' => 'Add New Product',
            'categories' => $this->getCategories(),
            'brands' => $this->getBrands(),
            'units' => $this->getUnits(),
            'employees' => $this->getEmployees(),
            'csrf_token' => \App\Helpers\CsrfHelper::token(),
        ];

        $this->render('admin.products.create', $data);
    }

    /**
     * Store new product.
     */
    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        if (!$this->isPost()) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }

        $rules = Product::getValidationRules();
        $errors = $this->validate($rules);

        if (!empty($errors)) {
            $this->flash('error', 'Validation failed: ' . implode(', ', array_map(fn($e) => $e[0], $errors)));
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }

        try {
            $productData = [
                'tenhanghoa' => $this->input('tenhanghoa'),
                'mota' => $this->input('mota', ''),
                'giathamkhao' => $this->input('giathamkhao'),
                'hinhanh' => $this->input('hinhanh', 0),
                'idloaihang' => $this->input('idloaihang'),
                'idThuongHieu' => $this->input('idThuongHieu') ?: null,
                'idDonViTinh' => $this->input('idDonViTinh') ?: null,
                'idNhanVien' => $this->input('idNhanVien') ?: null,
                'ghichu' => $this->input('ghichu', '')
            ];

            Product::create($productData);
            $this->flash('success', 'Product added successfully');
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        } catch (Exception $e) {
            Logger::error('ProductController::store', ['error' => $e->getMessage()]);
            $this->flash('error', 'Error adding product: ' . $e->getMessage());
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }
    }

    /**
     * Show edit product form.
     */
    public function edit(): void
    {
        $this->requireAuth();

        $id = $this->input('id');
        if (!$id) {
            $this->flash('error', 'Product ID required');
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }

        $product = Product::find($id);
        if (!$product) {
            $this->flash('error', 'Product not found');
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }

        $data = [
            'title' => 'Edit Product',
            'product' => $product,
            'categories' => $this->getCategories(),
            'brands' => $this->getBrands(),
            'units' => $this->getUnits(),
            'employees' => $this->getEmployees(),
            'csrf_token' => \App\Helpers\CsrfHelper::token(),
        ];

        $this->render('admin.products.edit', $data);
    }

    /**
     * Update product.
     */
    public function update(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        if (!$this->isPost()) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }

        $id = $this->input('id');
        if (!$id) {
            $this->flash('error', 'Product ID required');
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }

        $product = Product::find($id);
        if (!$product) {
            $this->flash('error', 'Product not found');
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }

        $rules = Product::getValidationRules();
        $errors = $this->validate($rules);

        if (!empty($errors)) {
            $this->flash('error', 'Validation failed: ' . implode(', ', array_map(fn($e) => $e[0], $errors)));
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }

        try {
            $product->tenhanghoa = $this->input('tenhanghoa');
            $product->mota = $this->input('mota', '');
            $product->giathamkhao = $this->input('giathamkhao');
            $product->hinhanh = $this->input('hinhanh', 0);
            $product->idloaihang = $this->input('idloaihang');
            $product->idThuongHieu = $this->input('idThuongHieu') ?: null;
            $product->idDonViTinh = $this->input('idDonViTinh') ?: null;
            $product->idNhanVien = $this->input('idNhanVien') ?: null;
            $product->ghichu = $this->input('ghichu', '');

            if ($product->save()) {
                $this->flash('success', 'Product updated successfully');
            } else {
                $this->flash('error', 'Failed to update product');
            }
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        } catch (Exception $e) {
            Logger::error('ProductController::update', ['error' => $e->getMessage()]);
            $this->flash('error', 'Error updating product: ' . $e->getMessage());
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }
    }

    /**
     * Delete product (AJAX).
     */
    public function delete(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $id = $this->input('id');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'Product ID required'], 400);
        }

        $product = Product::find($id);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        try {
            $result = $product->delete();

            if ($result) {
                $this->json(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to delete product']);
            }
        } catch (Exception $e) {
            Logger::error('ProductController::delete', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search products (AJAX).
     */
    public function search(): void
    {
        $this->requireAuth();

        $keyword = $this->input('q');
        if (empty($keyword)) {
            $this->json(['products' => []]);
        }

        try {
            $products = Product::search($keyword);
            $results = [];

            foreach ($products as $product) {
                $results[] = [
                    'id' => $product->idhanghoa,
                    'name' => $product->tenhanghoa,
                    'price' => $product->getFormattedPrice(),
                    'image_url' => $product->getImageUrl(),
                    'category' => $product->getCategory(),
                    'in_stock' => $product->isInStock()
                ];
            }

            $this->json(['products' => $results]);
        } catch (Exception $e) {
            Logger::error('ProductController::search', ['error' => $e->getMessage()]);
            $this->json(['error' => 'Search failed'], 500);
        }
    }

    /**
     * Get product details (AJAX).
     */
    public function show(): void
    {
        $this->requireAuth();

        $id = $this->input('id');
        if (!$id) {
            $this->json(['error' => 'Product ID required'], 400);
        }

        $product = Product::find($id);
        if (!$product) {
            $this->json(['error' => 'Product not found'], 404);
        }

        $data = [
            'product' => $product->toArray(),
            'category' => $product->getCategory(),
            'brand' => $product->getBrand(),
            'stock' => $product->getStock(),
            'image_url' => $product->getImageUrl(),
            'formatted_price' => $product->getFormattedPrice()
        ];

        $this->json($data);
    }

    /**
     * Bulk delete products (AJAX).
     */
    public function bulkDelete(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $ids = $this->input('ids');
        if (empty($ids) || !is_array($ids)) {
            $this->json(['success' => false, 'message' => 'Product IDs required'], 400);
        }

        try {
            $result = Product::bulkDelete($ids);

            $this->json([
                'success' => $result['success'],
                'message' => $result['success']
                    ? "Deleted {$result['deleted']} products"
                    : 'Some products could not be deleted',
                'deleted' => $result['deleted'],
                'errors' => $result['errors'],
            ]);
        } catch (Exception $e) {
            Logger::error('ProductController::bulkDelete', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Export products (CSV).
     */
    public function export(): void
    {
        $this->requireAuth();

        try {
            $products = Product::getAllWithPricing();

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($output, ['ID', 'Tên hàng hóa', 'Giá tham khảo', 'Giá khuyến mãi', 'Danh mục', 'Trạng thái']);

            // Data
            foreach ($products as $product) {
                fputcsv($output, [
                    $product->idhanghoa,
                    $product->tenhanghoa,
                    $product->giathamkhao,
                    $product->giakhuyenmai,
                    $product->ten_loaihang ?? '',
                    $product->trang_thai ?? '',
                ]);
            }

            fclose($output);
            exit;
        } catch (Exception $e) {
            Logger::error('ProductController::export', ['error' => $e->getMessage()]);
            $this->flash('error', 'Export failed: ' . $e->getMessage());
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
        }
    }

    // ── Private helpers ──

    private function getCategories(): array
    {
        return CategoryService::getInstance()->getAllCategories();
    }

    private function getBrands(): array
    {
        return Product::getAllThuongHieu();
    }

    private function getUnits(): array
    {
        return Product::getAllDonViTinh();
    }

    private function getEmployees(): array
    {
        return Product::getAllNhanVien();
    }
}
