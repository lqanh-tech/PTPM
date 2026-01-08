<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/giohangCls.php';

SessionManager::start();

$security = ApiSecurityMiddleware::getInstance();
$security->handle('cart');

class CartAPI {
    private $giohang;
    
    public function __construct() {
        $this->giohang = new GioHang();
    }
    
    public function addToCart() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $productId = $_POST['idhanghoa'] ?? $_POST['product_id'] ?? null;
            $quantity = intval($_POST['soluong'] ?? $_POST['quantity'] ?? 1);
            
            if (!$productId) {
                return $this->error('Thiếu mã sản phẩm');
            }
            
            if ($quantity < 1) {
                $quantity = 1;
            }
            
            $result = $this->giohang->addToCart($productId, $quantity);
            
            if ($result) {
                $cartCount = $this->giohang->getCartItemCount();
                return $this->success([
                    'message' => 'Đã thêm vào giỏ hàng',
                    'cart_count' => $cartCount
                ]);
            } else {
                return $this->error('Không thể thêm vào giỏ hàng');
            }
            
        } catch (Exception $e) {
            error_log("Add to cart error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function removeFromCart() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $productId = $_POST['product_id'] ?? $_GET['product_id'] ?? null;
            
            if (!$productId) {
                return $this->error('Thiếu mã sản phẩm');
            }
            
            $result = $this->giohang->removeFromCart($productId);
            
            if ($result) {
                $cartCount = $this->giohang->getCartItemCount();
                return $this->success([
                    'message' => 'Đã xóa khỏi giỏ hàng',
                    'cart_count' => $cartCount
                ]);
            } else {
                return $this->error('Không thể xóa khỏi giỏ hàng');
            }
            
        } catch (Exception $e) {
            error_log("Remove from cart error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function updateQuantity() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $productId = $_POST['product_id'] ?? null;
            $quantity = intval($_POST['quantity'] ?? 0);
            
            if (!$productId) {
                return $this->error('Thiếu mã sản phẩm');
            }
            
            $result = $this->giohang->updateQuantity($productId, $quantity);
            
            if ($result) {
                $cartCount = $this->giohang->getCartItemCount();
                return $this->success([
                    'message' => 'Đã cập nhật giỏ hàng',
                    'cart_count' => $cartCount
                ]);
            } else {
                return $this->error('Không thể cập nhật giỏ hàng');
            }
            
        } catch (Exception $e) {
            error_log("Update cart error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function getCount() {
        try {
            $count = $this->giohang->getCartItemCount();
            return $this->success(['count' => $count]);
        } catch (Exception $e) {
            error_log("Get cart count error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    private function success($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    private function error($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$api = new CartAPI();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $api->addToCart();
        break;
    case 'remove':
        $api->removeFromCart();
        break;
    case 'update':
        $api->updateQuantity();
        break;
    case 'count':
        $api->getCount();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
