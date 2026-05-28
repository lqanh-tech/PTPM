<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Helpers\CsrfProtection;
use App\Models\Product;
use App\Models\Order;
use App\Models\Cart;
use App\Services\CategoryService;
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

    public function __construct()
    {
        parent::__construct();
        $this->rateLimiter = new UserRateLimiter();
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
            error_log("ApiController::products error: " . $e->getMessage());
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
            error_log("ApiController::product error: " . $e->getMessage());
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
            error_log("ApiController::categories error: " . $e->getMessage());
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
            error_log("ApiController::orders error: " . $e->getMessage());
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
            error_log("ApiController::order error: " . $e->getMessage());
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
            error_log("ApiController::search error: " . $e->getMessage());
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
            error_log("ApiController::stats error: " . $e->getMessage());
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

            $this->json([
                'success' => true,
                'data' => [
                    'id' => $user['iduser'],
                    'username' => $user['username'],
                    'name' => $user['hoten'],
                    'email' => $user['email'],
                ],
            ]);
        } catch (\Exception $e) {
            error_log("ApiController::login error: " . $e->getMessage());
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
            error_log("ApiController::register error: " . $e->getMessage());
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
        if (!$this->isAuthenticated()) {
            $this->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        try {
            $user = \App\Services\UserService::getInstance()->getUserByUsername((string) $this->getUser());
            if (!$user) {
                $this->json(['success' => false, 'message' => 'User not found'], 404);
            }

            $this->json([
                'success' => true,
                'data' => [
                    'id' => $user->iduser,
                    'username' => $user->username,
                    'name' => $user->hoten,
                    'email' => $user->email,
                    'phone' => $user->dienthoai,
                    'address' => $user->diachi,
                ],
            ]);
        } catch (\Exception $e) {
            error_log("ApiController::me error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
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
            error_log("ApiController::cart error: " . $e->getMessage());
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
            error_log("ApiController::cartAdd error: " . $e->getMessage());
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
            error_log("ApiController::cartUpdate error: " . $e->getMessage());
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
            error_log("ApiController::cartRemove error: " . $e->getMessage());
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
            error_log("ApiController::cartClear error: " . $e->getMessage());
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
