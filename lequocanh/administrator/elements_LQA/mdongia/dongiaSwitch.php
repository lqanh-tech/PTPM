<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/dongiaCls.php';

SessionManager::start();

if (!isset($_SESSION['ADMIN'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

function sendJsonResponse($success, $message = '', $data = null)
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success, 
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    $dg = new Dongia();
    
    switch ($action) {
        case 'switch_price':
            $idDonGia = $input['idDonGia'] ?? '';
            
            if (empty($idDonGia)) {
                sendJsonResponse(false, 'ID đơn giá không hợp lệ');
            }
            
            $dongiaInfo = $dg->DongiaGetbyId($idDonGia);
            if (!$dongiaInfo) {
                sendJsonResponse(false, 'Không tìm thấy đơn giá');
            }
            
            $impact = $dg->checkPriceImpact($dongiaInfo->idHangHoa, $dongiaInfo->giaBan);
            
            $result = $dg->DongiaSwitchActive($idDonGia);
            
            if ($result) {
                sendJsonResponse(true, 'Chuyển đổi đơn giá thành công', [
                    'new_price' => number_format($dongiaInfo->giaBan, 0, ',', '.'),
                    'impact' => $impact
                ]);
            } else {
                sendJsonResponse(false, 'Chuyển đổi đơn giá thất bại');
            }
            break;
            
        case 'get_price_history':
            $idHangHoa = $input['idHangHoa'] ?? '';
            
            if (empty($idHangHoa)) {
                sendJsonResponse(false, 'ID hàng hóa không hợp lệ');
            }
            
            $history = $dg->getPriceHistory($idHangHoa, 10);
            sendJsonResponse(true, 'Lấy lịch sử giá thành công', $history);
            break;
            
        case 'check_price_impact':
            $idHangHoa = $input['idHangHoa'] ?? '';
            $newPrice = $input['newPrice'] ?? 0;
            
            if (empty($idHangHoa) || $newPrice <= 0) {
                sendJsonResponse(false, 'Thông tin không hợp lệ');
            }
            
            $impact = $dg->checkPriceImpact($idHangHoa, $newPrice);
            sendJsonResponse(true, 'Kiểm tra tác động thành công', $impact);
            break;
            
        case 'get_product_prices':
            $idHangHoa = $input['idHangHoa'] ?? '';
            
            if (empty($idHangHoa)) {
                sendJsonResponse(false, 'ID hàng hóa không hợp lệ');
            }
            
            $prices = $dg->DongiaGetbyIdHanghoa($idHangHoa);
            sendJsonResponse(true, 'Lấy danh sách giá thành công', $prices);
            break;
            
        default:
            sendJsonResponse(false, 'Hành động không hợp lệ');
    }
} else {
    sendJsonResponse(false, 'Phương thức không được hỗ trợ');
}