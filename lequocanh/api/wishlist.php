<?php
/**
 * Wishlist API
 * 
 * POST /api/wishlist.php?action=toggle - Toggle yêu thích
 * GET /api/wishlist.php?action=list - Lấy danh sách yêu thích
 * GET /api/wishlist.php?action=count - Đếm số lượng
 * GET /api/wishlist.php?action=check&product_id=X - Kiểm tra sản phẩm
 */

session_start();
require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

use App\Models\Wishlist;

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

$userId = (int) $_SESSION['USER'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'toggle':
        // CSRF protection
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!verify_csrf_token($token)) {
                echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
                exit();
            }
        }

        $productId = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product ID không hợp lệ']);
            exit();
        }

        $result = Wishlist::toggle($userId, $productId);
        echo json_encode($result);
        break;

    case 'list':
        $items = Wishlist::getByUser($userId);
        echo json_encode(['success' => true, 'items' => $items]);
        break;

    case 'count':
        $count = Wishlist::countForUser($userId);
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    case 'check':
        $productId = intval($_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product ID không hợp lệ']);
            exit();
        }

        $isWishlisted = Wishlist::isWishlisted($userId, $productId);
        echo json_encode(['success' => true, 'is_wishlisted' => $isWishlisted]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}