<?php

/**
 * Product Controller
 * Handles product-related requests in admin panel
 */

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Models/Product.php';

class ProductController extends BaseController
{
    /**
     * Display all products
     */
    public function index()
    {
        $this->requireAuth();

        try {
            $products = Product::getAllWithRelations();

            $data = [
                'products' => $products,
                'title' => 'Product Management',
                'success_message' => $this->input('success'),
                'error_message' => $this->input('error')
            ];

            $this->render('admin.products.index', $data);
        } catch (Exception $e) {
            error_log("ProductController::index error: " . $e->getMessage());
            $this->render('admin.products.index', [
                'products' => [],
                'error_message' => 'Error loading products: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show create product form
     */
    public function create()
    {
        $this->requireAuth();

        $data = [
            'title' => 'Add New Product',
            'categories' => $this->getCategories(),
            'brands' => $this->getBrands(),
            'units' => $this->getUnits(),
            'employees' => $this->getEmployees()
        ];

        $this->render('admin.products.create', $data);
    }

    /**
     * Store new product
     */
    public function store()
    {
        $this->requireAuth();

        if (!$this->isPost()) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
            return;
        }

        // Validate input
        $rules = Product::getValidationRules();
        $errors = $this->validate($rules);

        if (!empty($errors)) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Validation failed'));
            return;
        }

        try {
            // Create new product
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

            $product = Product::create($productData);

            if ($product) {
                $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&success=' . urlencode('Product added successfully'));
            } else {
                $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Failed to add product'));
            }
        } catch (Exception $e) {
            error_log("ProductController::store error: " . $e->getMessage());
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Error adding product: ' . $e->getMessage()));
        }
    }

    /**
     * Show edit product form
     */
    public function edit()
    {
        $this->requireAuth();

        $id = $this->input('id');
        if (!$id) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Product ID required'));
            return;
        }

        $product = Product::find($id);
        if (!$product) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Product not found'));
            return;
        }

        $data = [
            'title' => 'Edit Product',
            'product' => $product,
            'categories' => $this->getCategories(),
            'brands' => $this->getBrands(),
            'units' => $this->getUnits(),
            'employees' => $this->getEmployees()
        ];

        $this->render('admin.products.edit', $data);
    }

    /**
     * Update product
     */
    public function update()
    {
        $this->requireAuth();

        if (!$this->isPost()) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview');
            return;
        }

        $id = $this->input('id');
        if (!$id) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Product ID required'));
            return;
        }

        $product = Product::find($id);
        if (!$product) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Product not found'));
            return;
        }

        // Validate input
        $rules = Product::getValidationRules();
        $errors = $this->validate($rules);

        if (!empty($errors)) {
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Validation failed'));
            return;
        }

        try {
            // Update product
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
                $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&success=' . urlencode('Product updated successfully'));
            } else {
                $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Failed to update product'));
            }
        } catch (Exception $e) {
            error_log("ProductController::update error: " . $e->getMessage());
            $this->redirect('/lequocanh/administrator/index.php?req=hanghoaview&error=' . urlencode('Error updating product: ' . $e->getMessage()));
        }
    }

    /**
     * Delete product
     */
    public function delete()
    {
        $this->requireAuth();

        $id = $this->input('id');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'Product ID required'], 400);
            return;
        }

        $product = Product::find($id);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Product not found'], 404);
            return;
        }

        try {
            $result = $product->delete();

            if ($result) {
                $this->json(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to delete product']);
            }
        } catch (Exception $e) {
            error_log("ProductController::delete error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Search products (AJAX)
     */
    public function search()
    {
        $this->requireAuth();

        $keyword = $this->input('q');
        if (empty($keyword)) {
            $this->json(['products' => []]);
            return;
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
            error_log("ProductController::search error: " . $e->getMessage());
            $this->json(['error' => 'Search failed'], 500);
        }
    }

    /**
     * Get product details (AJAX)
     */
    public function show()
    {
        $this->requireAuth();

        $id = $this->input('id');
        if (!$id) {
            $this->json(['error' => 'Product ID required'], 400);
            return;
        }

        $product = Product::find($id);
        if (!$product) {
            $this->json(['error' => 'Product not found'], 404);
            return;
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

    // Helper methods

    private function getCategories()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM loaihang ORDER BY tenloaihang");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getBrands()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM thuonghieu ORDER BY tenTH");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getUnits()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM donvitinh ORDER BY tenDonViTinh");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getEmployees()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM nhanvien ORDER BY tenNV");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
