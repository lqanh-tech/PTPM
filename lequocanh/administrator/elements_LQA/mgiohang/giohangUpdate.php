<?php
session_start();
require_once '../../elements_LQA/mod/giohangCls.php';
require_once '../../elements_LQA/mod/mtonkhoCls.php';

$giohang = new GioHang();
$tonkho = new MTonKho();

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['productId']) && isset($data['quantity'])) {
    $productId = (int)$data['productId'];
    $quantity = (int)$data['quantity'];

    if ($quantity < 1) {
        $response = [
            'success' => false,
            'message' => 'Số lượng không hợp lệ!'
        ];
    } else {

        $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($productId);

        if (!$tonkhoInfo || $tonkhoInfo->soLuong == 0) {

            $response = [
                'success' => false,
                'message' => 'Sản phẩm đã hết hàng!',
                'outOfStock' => true
            ];
        } elseif ($quantity > $tonkhoInfo->soLuong) {

            $response = [
                'success' => false,
                'message' => 'Số lượng tồn kho chỉ còn ' . $tonkhoInfo->soLuong . ' sản phẩm!',
                'availableQuantity' => $tonkhoInfo->soLuong
            ];
        } else {

            $result = $giohang->updateQuantity($productId, $quantity);
            $response = [
                'success' => $result,
                'message' => $result ? 'Cập nhật thành công' : 'Cập nhật thất bại'
            ];
        }
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ!'
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
