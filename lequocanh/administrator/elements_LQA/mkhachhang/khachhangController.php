<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'khachhangSimple.php';

$khachHangObj = new KhachHang();

$action = isset($_GET['act']) ? $_GET['act'] : '';
$customers = [];

try {
    switch ($action) {
        case 'add':

            include 'khachhangAdd.php';
            exit;
            
        case 'edit':

            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $customer = $khachHangObj->getById($id);
            
            if (!$customer) {
                $_SESSION['error_message'] = 'Không tìm thấy khách hàng!';
                header('Location: ?req=khachhangview');
                exit;
            }
            
            include 'khachhangEdit.php';
            exit;
            
        case 'detail':

            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $customer = $khachHangObj->getById($id);
            
            if (!$customer) {
                $_SESSION['error_message'] = 'Không tìm thấy khách hàng!';
                header('Location: ?req=khachhangview');
                exit;
            }
            
            $orderHistory = $khachHangObj->getOrderHistory($customer['username']);
            
            $purchasedProducts = $khachHangObj->getPurchasedProducts($customer['username']);
            
            include 'khachhangDetail.php';
            exit;
            
        case 'delete':

            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $result = $khachHangObj->delete($id);
            
            if ($result) {
                $_SESSION['success_message'] = 'Xóa khách hàng thành công!';
            } else {
                $_SESSION['error_message'] = 'Xóa khách hàng thất bại!';
            }
            
            header('Location: ?req=khachhangview');
            exit;
            
        default:

            $searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
            $searchField = isset($_GET['field']) ? $_GET['field'] : 'all';
            
            if (!empty($searchKeyword)) {
                $customers = $khachHangObj->search($searchKeyword, $searchField);
            } else {

                $customers = $khachHangObj->getAll();
            }
            
            include 'khachhangView.php';
            break;
    }
} catch (Exception $e) {

    $_SESSION['error_message'] = 'Đã xảy ra lỗi: ' . $e->getMessage();
    
    if (strpos($e->getMessage(), 'database') !== false) {
        include 'khachhangDemo.php';
    } else {
        include 'khachhangView.php';
    }
}
?>
