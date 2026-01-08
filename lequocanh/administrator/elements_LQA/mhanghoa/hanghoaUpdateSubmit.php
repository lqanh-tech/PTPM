<?php
require_once '../mod/hanghoaCls.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$log_file = __DIR__ . '/update_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);
file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "GET: " . print_r($_GET, true) . "\n", FILE_APPEND);

if (!isset($_POST['idhanghoa']) || empty($_POST['idhanghoa'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu ID hàng hóa'
    ]);
    file_put_contents($log_file, "Error: Missing idhanghoa\n", FILE_APPEND);
    exit;
}

if (!isset($_POST['tenhanghoa']) || empty($_POST['tenhanghoa'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu tên hàng hóa'
    ]);
    file_put_contents($log_file, "Error: Missing tenhanghoa\n", FILE_APPEND);
    exit;
}

$idhanghoa = $_POST['idhanghoa'];
$tenhanghoa = $_POST['tenhanghoa'];
$mota = $_POST['mota'] ?? '';
$giathamkhao = $_POST['giathamkhao'] ?? 0;
$id_hinhanh = $_POST['id_hinhanh'] ?? '';
$idloaihang = $_POST['idloaihang'] ?? '';
$idThuongHieu = $_POST['idThuongHieu'] ?? '';
$idDonViTinh = $_POST['idDonViTinh'] ?? '';
$idNhanVien = $_POST['idNhanVien'] ?? '';
$trang_thai = $_POST['trang_thai'] ?? 1;

file_put_contents($log_file, "Processing data:\n", FILE_APPEND);
file_put_contents($log_file, "idhanghoa: $idhanghoa\n", FILE_APPEND);
file_put_contents($log_file, "tenhanghoa: $tenhanghoa\n", FILE_APPEND);
file_put_contents($log_file, "mota: $mota\n", FILE_APPEND);
file_put_contents($log_file, "giathamkhao: $giathamkhao\n", FILE_APPEND);
file_put_contents($log_file, "id_hinhanh: $id_hinhanh\n", FILE_APPEND);
file_put_contents($log_file, "idloaihang: $idloaihang\n", FILE_APPEND);
file_put_contents($log_file, "idThuongHieu: $idThuongHieu\n", FILE_APPEND);
file_put_contents($log_file, "idDonViTinh: $idDonViTinh\n", FILE_APPEND);
file_put_contents($log_file, "idNhanVien: $idNhanVien\n", FILE_APPEND);
file_put_contents($log_file, "trang_thai: $trang_thai\n", FILE_APPEND);

try {
    $hanghoa = new hanghoa();
    $result = $hanghoa->HanghoaUpdate(
        $tenhanghoa,
        $id_hinhanh,
        $mota,
        $giathamkhao,
        $idloaihang,
        $idThuongHieu,
        $idDonViTinh,
        $idNhanVien,
        $idhanghoa
    );

    file_put_contents($log_file, "Update result: " . ($result ? "Success" : "Failed") . "\n", FILE_APPEND);

    if ($trang_thai) {
        $statusResult = $hanghoa->updateProductStatus($idhanghoa, $trang_thai);
        file_put_contents($log_file, "Status update result: " . ($statusResult ? "Success" : "Failed") . "\n", FILE_APPEND);
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật hàng hóa thành công!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Cập nhật hàng hóa thất bại! Vui lòng thử lại.'
        ]);
    }
} catch (Exception $e) {
    file_put_contents($log_file, "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}

file_put_contents($log_file, "--------------------------------------\n", FILE_APPEND);
