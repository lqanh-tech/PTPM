<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Helpers\CsrfProtection;
use App\Middleware\ApiMiddleware;
use App\Models\Product;
use App\Models\Order;
use App\Models\Cart;
use App\Services\CategoryService;
use App\Services\JwtService;
use App\Services\OrderService;
use App\Services\UserRateLimiter;
use App\Helpers\ApiDocumentation;
use Exception;

/**
 * API Controller for RESTful endpoints
 */
class ApiController extends BaseController
{
    use CsrfProtection;

    private UserRateLimiter $rateLimiter;
    private ?array $jsonBody = null;

    public function __construct()
    {
        parent::__construct();
        $this->rateLimiter = new UserRateLimiter();
        $this->jsonBody = ApiMiddleware::getJsonBody();
    }

    /**
     * Get input value from query, POST, or JSON body.
     */
    protected function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            $json = $this->jsonBody ?? [];
            return array_merge($_GET, $_POST, $json);
        }

        // Check GET first, then POST, then JSON body
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($this->jsonBody[$key])) {
            return $this->jsonBody[$key];
        }

        return $default;
    }

    /**
     * Send standardized error response.
     */
    protected function error(string $message, int $code = 400, ?array $errors = null): never
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        $this->json($response, $code);
    }

    /**
     * Send standardized success response.
     */
    protected function success(mixed $data = null, string $message = 'OK', int $code = 200): never
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->json($response, $code);
    }

    /**
     * Set RFC 5988 Link header for pagination.
     *
     * @param string $basePath Base URL path (e.g., '/api/v1/products')
     * @param int $currentPage Current page number
     * @param int $lastPage Last page number
     * @param int $perPage Items per page
     */
    protected function setPaginationHeaders(string $basePath, int $currentPage, int $lastPage, int $perPage): void
    {
        $links = [];

        // First page
        if ($currentPage > 1) {
            $links[] = "<{$basePath}?page=1&per_page={$perPage}>; rel=\"first\"";
        }

        // Previous page
        if ($currentPage > 1) {
            $links[] = "<{$basePath}?page=" . ($currentPage - 1) . "&per_page={$perPage}>; rel=\"prev\"";
        }

        // Next page
        if ($currentPage < $lastPage) {
            $links[] = "<{$basePath}?page=" . ($currentPage + 1) . "&per_page={$perPage}>; rel=\"next\"";
        }

        // Last page
        if ($currentPage < $lastPage) {
            $links[] = "<{$basePath}?page={$lastPage}&per_page={$perPage}>; rel=\"last\"";
        }

        if (!empty($links)) {
            header('Link: ' . implode(', ', $links));
        }
    }

    /**
     * GET /api/products
     * List products with pagination and filters.
     */
    public function products(): void
    {
        $this->checkRateLimit('products', 120, 60);
        try {
            $page = (int) ($this->input('page') ?? 1);
            $perPage = (int) ($this->input('per_page') ?? 20);
            $categoryId = $this->input('category_id');
            $search = $this->input('search');
            $sortBy = $this->input('sort') ?? 'tenhanghoa';
            $sortDir = $this->input('dir') ?? 'ASC';

            // Limit per_page
            $perPage = min(max($perPage, 1), 100);

            // Build conditions
            $conditions = [];
            if ($categoryId) {
                $conditions['idloaihang'] = $categoryId;
            }

            // Get paginated results
            $result = Product::paginate($page, $perPage, $conditions);

            // Format response
            $products = array_map(function ($product) {
                return [
                    'id' => $product->idhanghoa,
                    'name' => $product->tenhanghoa,
                    'description' => $product->mota,
                    'price' => (float) $product->giathamkhao,
                    'sale_price' => $product->giakhuyenmai ? (float) $product->giakhuyenmai : null,
                    'image_url' => $product->getImageUrl(),
                    'category_id' => $product->idloaihang,
                    'in_stock' => $product->isInStock(),
                    'created_at' => $product->created_at,
                ];
            }, $result['data']);

            // Set pagination Link header (RFC 5988)
            $this->setPaginationHeaders(
                '/api/v1/products',
                (int) $result['current_page'],
                (int) $result['last_page'],
                (int) $result['per_page']
            );

            $this->json([
                'success' => true,
                'data' => $products,
                'pagination' => [
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'per_page' => $result['per_page'],
                    'total' => $result['total'],
                    'from' => $result['from'],
                    'to' => $result['to'],
                ],
            ]);
        } catch (Exception $e) {
            Logger::error('ApiController::products', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * GET /api/products/{id}
     * Get single product details.
     */
    public function product(): void
    {
        $this->checkRateLimit('product', 120, 60);
        try {
            $id = $this->input('id');
            if (!$id) {
                $this->json(['success' => false, 'message' => 'Product ID required'], 400);
            }

            $product = Product::find($id);
            if (!$product) {
                $this->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            $this->json([
                'success' => true,
                'data' => [
                    'id' => $product->idhanghoa,
                    'name' => $product->tenhanghoa,
                    'description' => $product->mota,
                    'price' => (float) $product->giathamkhao,
                    'sale_price' => $product->giakhuyenmai ? (float) $product->giakhuyenmai : null,
                    'image_url' => $product->getImageUrl(),
                    'category' => $product->getCategory(),
                    'brand' => $product->getBrand(),
                    'stock' => $product->getStock(),
                    'in_stock' => $product->isInStock(),
                    'created_at' => $product->created_at ?? null,
                ],
            ]);
        } catch (Exception $e) {
            Logger::error('ApiController::product', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * POST /api/v1/products
     * Create new product (admin only).
     */
    public function productCreate(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('product_create', 30, 60);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'POST required'], 405);
        }

        $name = trim((string) $this->input('tenhanghoa'));
        $price = $this->input('giathamkhao');

        if (empty($name) || $price === null) {
            $this->json(['success' => false, 'message' => 'Name and price required'], 400);
        }

        try {
            $data = [
                'tenhanghoa' => $name,
                'mota' => $this->input('mota') ?? '',
                'giathamkhao' => (float) $price,
                'giakhuyenmai' => $this->input('giakhuyenmai') ? (float) $this->input('giakhuyenmai') : null,
                'idloaihang' => $this->input('idloaihang') ? (int) $this->input('idloaihang') : null,
                'idThuongHieu' => $this->input('idThuongHieu') ? (int) $this->input('idThuongHieu') : null,
                'idDonViTinh' => $this->input('idDonViTinh') ? (int) $this->input('idDonViTinh') : null,
                'ghichu' => $this->input('ghichu') ?? '',
                'trang_thai' => Product::STATUS_ACTIVE,
            ];

            $product = Product::create($data);

            $this->json([
                'success' => true,
                'data' => [
                    'id' => $product->idhanghoa,
                    'name' => $product->tenhanghoa,
                    'price' => (float) $product->giathamkhao,
                ],
            ], 201);
        } catch (Exception $e) {
            Logger::error('ApiController::productCreate', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * PUT /api/v1/products/{id}
     * Update product (admin only).
     */
    public function productUpdate(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('product_update', 30, 60);

        $id = $this->input('id');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'Product ID required'], 400);
        }

        try {
            $product = Product::find($id);
            if (!$product) {
                $this->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            $allowedFields = ['tenhanghoa', 'mota', 'giathamkhao', 'giakhuyenmai', 'idloaihang', 'idThuongHieu', 'idDonViTinh', 'ghichu', 'trang_thai'];
            $updateData = [];

            foreach ($allowedFields as $field) {
                $value = $this->input($field);
                if ($value !== null) {
                    $updateData[$field] = $field === 'giathamkhao' || $field === 'giakhuyenmai' ? (float) $value : $value;
                }
            }

            if (empty($updateData)) {
                $this->json(['success' => false, 'message' => 'No fields to update'], 400);
            }

            foreach ($updateData as $key => $value) {
                $product->$key = $value;
            }
            $product->save();

            $this->json([
                'success' => true,
                'data' => [
                    'id' => $product->idhanghoa,
                    'name' => $product->tenhanghoa,
                    'price' => (float) $product->giathamkhao,
                ],
            ]);
        } catch (Exception $e) {
            Logger::error('ApiController::productUpdate', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * DELETE /api/v1/products/{id}
     * Delete product (admin only).
     */
    public function productDelete(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('product_delete', 10, 60);

        $id = $this->input('id');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'Product ID required'], 400);
        }

        try {
            $product = Product::find($id);
            if (!$product) {
                $this->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            $product->delete();

            $this->json(['success' => true, 'message' => 'Product deleted']);
        } catch (Exception $e) {
            Logger::error('ApiController::productDelete', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * GET /api/categories
     * List all categories.
     */
    public function categories(): void
    {
        $this->checkRateLimit('categories', 120, 60);
        try {
            $categories = CategoryService::getInstance()->getCategoriesWithProductCount();

            $this->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (Exception $e) {
            Logger::error('ApiController::categories', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * GET /api/orders
     * List orders for authenticated user.
     */
    public function orders(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('orders', 30, 60);

        try {
            $userId = $this->getUser();
            $page = (int) ($this->input('page') ?? 1);
            $perPage = (int) ($this->input('per_page') ?? 20);

            $offset = ($page - 1) * $perPage;
            $orders = OrderService::getInstance()->getOrdersByUserId($userId, $perPage, $offset);
            $total = OrderService::getInstance()->getOrderCount($userId);

            $this->json([
                'success' => true,
                'data' => $orders,
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => (int) ceil($total / $perPage),
                    'per_page' => $perPage,
                    'total' => $total,
                ],
            ]);
        } catch (Exception $e) {
            Logger::error('ApiController::orders', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * GET /api/orders/{id}
     * Get single order details.
     */
    public function order(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('order', 30, 60);

        try {
            $id = $this->input('id');
            if (!$id) {
                $this->json(['success' => false, 'message' => 'Order ID required'], 400);
            }

            $order = OrderService::getInstance()->getOrderById($id);
            if (!$order) {
                $this->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            // Check ownership
            if ($order->ma_nguoi_dung !== $this->getUser()) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $items = OrderService::getInstance()->getOrderDetails($id);

            $this->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'items' => $items,
                ],
            ]);
        } catch (Exception $e) {
            Logger::error('ApiController::order', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * POST /api/v1/orders
     * Create new order.
     */
    public function orderCreate(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('order_create', 10, 60);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'POST required'], 405);
        }

        $hoTen = trim((string) $this->input('ho_ten'));
        $soDienThoai = trim((string) $this->input('so_dien_thoai'));
        $diaChi = trim((string) $this->input('dia_chi_giao_hang'));
        $items = $this->input('items');

        // Validate required fields
        $errors = [];
        if (empty($hoTen)) {
            $errors[] = 'ho_ten required';
        }
        if (empty($soDienThoai)) {
            $errors[] = 'so_dien_thoai required';
        }
        if (empty($diaChi)) {
            $errors[] = 'dia_chi_giao_hang required';
        }
        if (!is_array($items) || empty($items)) {
            $errors[] = 'items array required';
        }

        if (!empty($errors)) {
            $this->json(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
        }

        try {
            $userId = (int) $this->getUser();

            // Calculate total from items
            $totalAmount = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $productId = $item['product_id'] ?? 0;
                $quantity = (int) ($item['quantity'] ?? 1);

                if (!$productId || $quantity < 1) {
                    $this->json(['success' => false, 'message' => 'Invalid item: product_id and quantity required'], 400);
                }

                $product = Product::find($productId);
                if (!$product) {
                    $this->json(['success' => false, 'message' => "Product {$productId} not found"], 404);
                }

                $price = $product->giakhuyenmai && $product->giakhuyenmai > 0
                    ? (float) $product->giakhuyenmai
                    : (float) $product->giathamkhao;

                $totalAmount += $price * $quantity;
                $orderItems[] = [
                    'ma_san_pham' => (int) $productId,
                    'so_luong' => $quantity,
                    'gia' => $price,
                ];
            }

            // Create order
            $orderData = [
                'ma_nguoi_dung' => $userId,
                'ho_ten' => $hoTen,
                'so_dien_thoai' => $soDienThoai,
                'email' => trim((string) $this->input('email')),
                'dia_chi_giao_hang' => $diaChi,
                'ghi_chu' => $this->input('ghi_chu') ?? '',
                'tong_tien' => $totalAmount,
                'phuong_thuc_thanh_toan' => $this->input('phuong_thuc_thanh_toan') ?? 'cod',
                'shipping_method' => $this->input('shipping_method') ?? 'standard',
                'phi_van_chuyen' => (float) ($this->input('phi_van_chuyen') ?? 0),
            ];

            $orderId = OrderService::getInstance()->createOrder($orderData);

            // Add order items
            foreach ($orderItems as $item) {
                OrderService::getInstance()->addOrderItem($orderId, $item);
            }

            // Clear user's cart after successful order
            Cart::clearForUser($userId);

            $this->json([
                'success' => true,
                'data' => [
                    'order_id' => $orderId,
                    'total' => $totalAmount,
                    'status' => 'pending',
                ],
            ], 201);
        } catch (Exception $e) {
            Logger::error('ApiController::orderCreate', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * GET /api/search
     * Search products.
     */
    public function search(): void
    {
        $this->checkRateLimit('search', 60, 60);
        try {
            $keyword = $this->input('q');
            if (empty($keyword)) {
                $this->json(['success' => false, 'message' => 'Search keyword required'], 400);
            }

            $page = (int) ($this->input('page') ?? 1);
            $perPage = (int) ($this->input('per_page') ?? 20);

            $products = Product::searchProducts($keyword);

            // Manual pagination for search results
            $total = count($products);
            $offset = ($page - 1) * $perPage;
            $paginated = array_slice($products, $offset, $perPage);

            $results = array_map(function ($product) {
                return [
                    'id' => $product->idhanghoa,
                    'name' => $product->tenhanghoa,
                    'price' => (float) $product->giathamkhao,
                    'sale_price' => $product->giakhuyenmai ? (float) $product->giakhuyenmai : null,
                    'image_url' => $product->getImageUrl(),
                    'category_id' => $product->idloaihang,
                ];
            }, $paginated);

            $this->json([
                'success' => true,
                'data' => $results,
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => (int) ceil($total / $perPage),
                    'per_page' => $perPage,
                    'total' => $total,
                ],
            ]);
        } catch (Exception $e) {
            Logger::error('ApiController::search', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * GET /api/stats
     * Get dashboard statistics (admin only).
     */
    public function stats(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('stats', 30, 60);

        try {
            $stats = [
                'products' => Product::count(),
                'orders' => OrderService::getInstance()->getOrderCount(),
                'categories' => count(CategoryService::getInstance()->getAllCategories()),
            ];

            $this->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            Logger::error('ApiController::stats', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * GET /api/docs
     * Get API documentation.
     */
    public function docs(): void
    {
        $format = $this->input('format') ?? 'html';

        $docs = new ApiDocumentation();

        if ($format === 'json') {
            header('Content-Type: application/json');
            echo $docs->toJson();
        } else {
            header('Content-Type: text/html; charset=UTF-8');
            echo $docs->toHtml();
        }
        exit;
    }

    /**
     * POST /api/v1/auth/login
     * Authenticate user and create session.
     */
    public function login(): void
    {
        $this->checkRateLimit('login', 10, 300);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'POST required'], 405);
        }

        $username = $this->input('username');
        $password = $this->input('password');

        if (empty($username) || empty($password)) {
            $this->json(['success' => false, 'message' => 'Username and password required'], 400);
        }

        try {
            $db = \Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT iduser, username, hoten, email, password, setlock FROM user WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $this->json(['success' => false, 'message' => 'Invalid credentials'], 401);
            }

            // Auto-activate account if needed
            if ($user['setlock'] != 1) {
                $db->prepare("UPDATE user SET setlock = 1 WHERE iduser = ?")->execute([$user['iduser']]);
            }

            $_SESSION['USER'] = $user['username'];

            // Generate JWT tokens
            $jwt = new JwtService();
            $tokenPayload = [
                'sub' => (int) $user['iduser'],
                'username' => $user['username'],
                'name' => $user['hoten'],
            ];

            $accessToken = $jwt->encode($tokenPayload, 3600); // 1 hour
            $refreshToken = $jwt->encodeRefresh($tokenPayload, 604800); // 7 days

            $this->json([
                'success' => true,
                'data' => [
                    'id' => $user['iduser'],
                    'username' => $user['username'],
                    'name' => $user['hoten'],
                    'email' => $user['email'],
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                ],
            ]);
        } catch (\Exception $e) {
            Logger::error('ApiController::login', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * POST /api/v1/auth/register
     * Register new user.
     */
    public function register(): void
    {
        $this->checkRateLimit('register', 5, 3600);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'POST required'], 405);
        }

        $username = trim((string) $this->input('username'));
        $password = $this->input('password');
        $email = trim((string) $this->input('email'));
        $hoten = trim((string) $this->input('name'));

        // Validate
        $errors = [];
        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }
        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (!empty($errors)) {
            $this->json(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
        }

        try {
            $db = \Database::getInstance()->getConnection();

            // Check duplicate username
            $stmt = $db->prepare("SELECT iduser FROM user WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $this->json(['success' => false, 'message' => 'Username already exists'], 409);
            }

            // Check duplicate email
            if (!empty($email)) {
                $stmt = $db->prepare("SELECT iduser FROM user WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $this->json(['success' => false, 'message' => 'Email already exists'], 409);
                }
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $db->prepare("INSERT INTO user (username, password, hoten, email, setlock) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$username, $hashedPassword, $hoten, $email ?: null]);

            $userId = $db->lastInsertId();
            $_SESSION['USER'] = $username;

            $this->json([
                'success' => true,
                'data' => [
                    'id' => $userId,
                    'username' => $username,
                    'name' => $hoten,
                    'email' => $email,
                ],
            ], 201);
        } catch (\Exception $e) {
            Logger::error('ApiController::register', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * POST /api/v1/auth/logout
     * Destroy user session.
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();

        $this->json(['success' => true, 'message' => 'Logged out']);
    }

    /**
     * GET /api/v1/auth/me
     * Get current authenticated user info.
     */
    public function me(): void
    {
        $user = $this->getAuthUser();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $this->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * GET /api/v1/auth/refresh
     * Refresh JWT access token.
     */
    public function refreshToken(): void
    {
        $token = JwtService::extractFromHeader();
        if (!$token) {
            $this->json(['success' => false, 'message' => 'Refresh token required'], 400);
        }

        try {
            $jwt = new JwtService();
            $payload = $jwt->decode($token);

            if (($payload['type'] ?? '') !== 'refresh') {
                $this->json(['success' => false, 'message' => 'Invalid token type'], 400);
            }

            $newToken = $jwt->encode([
                'sub' => $payload['sub'],
                'username' => $payload['username'],
                'name' => $payload['name'] ?? '',
            ], 3600);

            $this->json([
                'success' => true,
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                ],
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Invalid or expired refresh token'], 401);
        }
    }

    /**
     * Get authenticated user from session or JWT token.
     */
    private function getAuthUser(): ?array
    {
        // Try session first
        if ($this->isAuthenticated()) {
            $user = \App\Services\UserService::getInstance()->getUserByUsername((string) $this->getUser());
            if ($user) {
                return [
                    'id' => $user->iduser,
                    'username' => $user->username,
                    'name' => $user->hoten,
                    'email' => $user->email,
                    'phone' => $user->dienthoai,
                    'address' => $user->diachi,
                ];
            }
        }

        // Try JWT token
        $token = JwtService::extractFromHeader();
        if ($token) {
            try {
                $jwt = new JwtService();
                $payload = $jwt->decode($token);

                $user = \App\Services\UserService::getInstance()->getUserById((int) $payload['sub']);
                if ($user) {
                    return [
                        'id' => $user->iduser,
                        'username' => $user->username,
                        'name' => $user->hoten,
                        'email' => $user->email,
                        'phone' => $user->dienthoai,
                        'address' => $user->diachi,
                    ];
                }
            } catch (\Exception $e) {
                // Token invalid, fall through
            }
        }

        return null;
    }

    /**
     * GET /api/v1/cart
     * Get cart items for authenticated user.
     */
    public function cart(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('cart', 60, 60);

        try {
            $userId = (int) $this->getUser();
            $items = Cart::findByUser($userId);

            $cartItems = [];
            foreach ($items as $item) {
                $product = $item->product();
                $cartItems[] = [
                    'id' => $item->idgiohang,
                    'product_id' => $item->idhanghoa,
                    'product_name' => $product ? $product->tenhanghoa : null,
                    'product_image' => $product ? $product->getImageUrl() : null,
                    'price' => $product ? (float) $product->giathamkhao : 0,
                    'sale_price' => $product && $product->giakhuyenmai ? (float) $product->giakhuyenmai : null,
                    'quantity' => (int) $item->soluong,
                    'added_at' => $item->ngaythem,
                ];
            }

            $this->json([
                'success' => true,
                'data' => $cartItems,
                'total_items' => count($cartItems),
            ]);
        } catch (\Exception $e) {
            Logger::error('ApiController::cart', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * POST /api/v1/cart
     * Add item to cart.
     */
    public function cartAdd(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('cart_add', 30, 60);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'POST required'], 405);
        }

        $productId = $this->input('product_id');
        $quantity = (int) ($this->input('quantity') ?? 1);

        if (!$productId) {
            $this->json(['success' => false, 'message' => 'product_id required'], 400);
        }

        if ($quantity < 1) {
            $quantity = 1;
        }

        try {
            $product = Product::find($productId);
            if (!$product) {
                $this->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            $userId = (int) $this->getUser();
            $cartItem = Cart::addOrUpdate($userId, (int) $productId, $quantity);

            $this->json([
                'success' => true,
                'data' => [
                    'id' => $cartItem->idgiohang,
                    'product_id' => $cartItem->idhanghoa,
                    'quantity' => (int) $cartItem->soluong,
                ],
            ], 201);
        } catch (\Exception $e) {
            Logger::error('ApiController::cartAdd', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * PUT /api/v1/cart/{id}
     * Update cart item quantity.
     */
    public function cartUpdate(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('cart_update', 30, 60);

        $id = $this->input('id');
        $quantity = (int) $this->input('quantity');

        if (!$id || $quantity < 1) {
            $this->json(['success' => false, 'message' => 'Valid id and quantity required'], 400);
        }

        try {
            $cartItem = Cart::find($id);
            if (!$cartItem) {
                $this->json(['success' => false, 'message' => 'Cart item not found'], 404);
            }

            // Check ownership
            if ((int) $cartItem->iduser !== (int) $this->getUser()) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $cartItem->soluong = $quantity;
            $cartItem->save();

            $this->json([
                'success' => true,
                'data' => [
                    'id' => $cartItem->idgiohang,
                    'quantity' => (int) $cartItem->soluong,
                ],
            ]);
        } catch (\Exception $e) {
            Logger::error('ApiController::cartUpdate', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * DELETE /api/v1/cart/{id}
     * Remove item from cart.
     */
    public function cartRemove(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('cart_remove', 30, 60);

        $id = $this->input('id');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'Cart item ID required'], 400);
        }

        try {
            $cartItem = Cart::find($id);
            if (!$cartItem) {
                $this->json(['success' => false, 'message' => 'Cart item not found'], 404);
            }

            if ((int) $cartItem->iduser !== (int) $this->getUser()) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $cartItem->delete();

            $this->json(['success' => true, 'message' => 'Item removed']);
        } catch (\Exception $e) {
            Logger::error('ApiController::cartRemove', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * DELETE /api/v1/cart
     * Clear all cart items for user.
     */
    public function cartClear(): void
    {
        $this->requireAuth();
        $this->checkRateLimit('cart_clear', 10, 60);

        try {
            $userId = (int) $this->getUser();
            Cart::clearForUser($userId);

            $this->json(['success' => true, 'message' => 'Cart cleared']);
        } catch (\Exception $e) {
            Logger::error('ApiController::cartClear', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Check rate limit for current request.
     */
    private function checkRateLimit(string $action, int $maxAttempts = 60, int $timeWindow = 60): bool
    {
        $userId = $this->getUser() ?? $this->getClientIp();

        if (!$this->rateLimiter->check($action, $userId, $maxAttempts, $timeWindow)) {
            $retryAfter = $this->rateLimiter->retryAfter($action, $userId, $timeWindow);
            header('Retry-After: ' . $retryAfter);
            $this->json([
                'success' => false,
                'message' => 'Rate limit exceeded',
                'retry_after' => $retryAfter,
            ], 429);
        }

        // Add rate limit headers
        $headers = $this->rateLimiter->getHeaders($action, $userId, $maxAttempts, $timeWindow);
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        return true;
    }
}
