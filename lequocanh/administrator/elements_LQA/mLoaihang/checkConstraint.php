<?php
header('Content-Type: application/json');

require_once '../mod/sessionManager.php';
require_once '../mod/loaihangCls.php';

SessionManager::start();

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (isset($_GET['idloaihang'])) {
    try {
        $idloaihang = $_GET['idloaihang'];
        $lh = new loaihang();
        
        $count = $lh->countHanghoaByLoaihang($idloaihang);
        
        if ($count > 0) {

            $products = $lh->getHanghoaByLoaihang($idloaihang);
            $productNames = array_map(function($p) { return $p->tenhanghoa; }, $products);
            
            echo json_encode([
                'success' => false,
                'canDelete' => false,
                'count' => $count,
                'message' => "Không thể xóa loại hàng này vì vẫn có $count sản phẩm đang sử dụng.",
                'products' => $productNames,
                'suggestion' => 'Vui lòng xóa các sản phẩm thuộc loại này trước hoặc chuyển chúng sang loại khác.'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'canDelete' => true,
                'count' => 0,
                'message' => 'Có thể xóa loại hàng này.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu tham số idloaihang'
    ]);
}
?>
