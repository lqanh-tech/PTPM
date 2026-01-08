<?php
// Security includes
require_once __DIR__ . '/../mod/SecurityHelpers.php';
require_once __DIR__ . '/../mod/InputValidator.php';


require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

ob_start();

SessionManager::start();
require_once '../../elements_LQA/mod/giohangCls.php';
require_once '../../elements_LQA/mod/mtonkhoCls.php';
require_once '../../elements_LQA/mod/hanghoaCls.php';

$giohang = new GioHang();

if (!$giohang->canUseCart()) {

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        ob_clean();
        header('Content-Type: application/json');
        if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng giỏ hàng', 'redirect' => '../../userLogin.php']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Quản trị viên không có quyền sử dụng giỏ hàng', 'redirect' => '../../index.php']);
        }
        exit();
    } else {

        if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? '../../../index.php';
            header('Location: ../../userLogin.php');
        } else {
            header('Location: ../../index.php');
        }
        exit();
    }
}

if (class_exists('Logger')) {
    Logger::debug("Processing cart action", [
        'session' => $_SESSION,
        'get' => $_GET,
        'script' => $_SERVER['SCRIPT_NAME']
    ]);
}

$tonkho = new MTonKho();

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    Logger::info("Cart action requested", ['action' => $action]);

    $productId = isset($_GET['productId']) ? (int)$_GET['productId'] : null;
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;

    switch ($action) {
        case 'add':
            if (isset($_GET['productId']) && isset($_GET['quantity'])) {
                $productId = $_GET['productId'];
                $quantity = $_GET['quantity'];

                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

                $hanghoa = new hanghoa();
                $productStatus = $hanghoa->getProductStatusValue($productId);

                if ($productStatus == 2) {

                    if ($isAjax) {
                        ob_clean();
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Sản phẩm này đã ngừng bán!']);
                        exit();
                    }
                    $_SESSION['cart_error'] = 'Sản phẩm này đã ngừng bán!';
                    $referrer = $_SERVER['HTTP_REFERER'] ?? '../../../index.php';
                    header('Location: ' . $referrer);
                    exit();
                } elseif ($productStatus == 3) {

                    if ($isAjax) {
                        ob_clean();
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Sản phẩm này đã hết hàng!']);
                        exit();
                    }
                    $_SESSION['cart_error'] = 'Sản phẩm này đã hết hàng!';
                    $referrer = $_SERVER['HTTP_REFERER'] ?? '../../../index.php';
                    header('Location: ' . $referrer);
                    exit();
                }

                $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($productId);

                $currentCart = $giohang->getCart();
                $currentQuantity = 0;

                foreach ($currentCart as $item) {
                    if ($item['product_id'] == $productId) {
                        $currentQuantity = $item['quantity'];
                        break;
                    }
                }

                $totalQuantity = $currentQuantity + $quantity;

                if (!$tonkhoInfo || $tonkhoInfo->soLuong == 0) {

                    if ($isAjax) {
                        ob_clean();
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng!']);
                        exit();
                    }
                    $_SESSION['cart_error'] = 'Sản phẩm đã hết hàng!';
                    $referrer = $_SERVER['HTTP_REFERER'] ?? '../../../index.php';
                    header('Location: ' . $referrer);
                    exit();
                } elseif ($totalQuantity > $tonkhoInfo->soLuong) {

                    if ($isAjax) {
                        ob_clean();
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Số lượng tồn kho chỉ còn ' . $tonkhoInfo->soLuong . ' sản phẩm!',
                            'available' => $tonkhoInfo->soLuong
                        ]);
                        exit();
                    }
                    $_SESSION['cart_error'] = 'Số lượng tồn kho chỉ còn ' . $tonkhoInfo->soLuong . ' sản phẩm!';
                    $referrer = $_SERVER['HTTP_REFERER'] ?? '../../../index.php';
                    header('Location: ' . $referrer);
                    exit();
                } else {

                    $result = $giohang->addToCart($productId, $quantity);

                    if ($isAjax) {

                        ob_clean();
                        header('Content-Type: application/json');
                        if ($result) {
                            echo json_encode([
                                'success' => true, 
                                'message' => 'Đã thêm sản phẩm vào giỏ hàng!',
                                'cartCount' => $giohang->getCartItemCount()
                            ]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Không thể thêm vào giỏ hàng!']);
                        }
                        exit();
                    }

                    $referrer = $_SERVER['HTTP_REFERER'] ?? '../../../index.php';

                    if (strpos($referrer, 'administrator') !== false && strpos($referrer, 'administrator/elements_LQA/mgiohang') === false) {

                        header('Location: ../mgiohang/giohangView.php');
                    } else {

                        header('Location: cart_redirect.php?referrer=' . urlencode($referrer));
                    }
                    exit();
                }
            }
            break;

        case 'clear':
            $giohang->clearCart();
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();

        case 'removeSelected':

            $data = json_decode(file_get_contents('php://input'), true);

            if (isset($data['productIds']) && is_array($data['productIds'])) {
                foreach ($data['productIds'] as $productId) {
                    $giohang->removeFromCart((int)$productId);
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit();
            }
            break;

        default:
            $_SESSION['error'] = 'Hành động không hợp lệ.';
            break;
    }
}

exit();
