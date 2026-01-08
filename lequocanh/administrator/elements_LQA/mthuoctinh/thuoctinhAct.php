<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();
require_once __DIR__ . '/../mod/thuoctinhCls.php';

if (isset($_GET['reqact'])) {
    $requestAction = $_GET['reqact'];
    $lh = new ThuocTinh();

    switch ($requestAction) {
        case 'addnew':
            $tenThuocTinh = isset($_POST['tenThuocTinh']) ? $_POST['tenThuocTinh'] : '';
            $ghiChu = isset($_POST['ghiChu']) ? $_POST['ghiChu'] : '';
            if (empty($_FILES['fileimage']['tmp_name'])) {
                echo "<script>alert('Vui lòng nhập ảnh trước khi thêm loại hàng.'); window.history.back();</script>";
                exit;
            }
            $hinhanh_file = $_FILES['fileimage']['tmp_name'];
            $hinhanh = base64_encode(file_get_contents(addslashes($hinhanh_file)));

            $kq = $lh->thuoctinhAdd($tenThuocTinh,  $ghiChu, $hinhanh);
            header('location: ../../index.php?req=thuoctinhview&result=' . ($kq ? 'ok' : 'notok'));
            break;

        case 'deletethuoctinh':
            $idThuocTinh = isset($_GET['idThuocTinh']) ? $_GET['idThuocTinh'] : null;

            if (!$idThuocTinh) {
                header('location: ../../index.php?req=thuoctinhview&result=error&message=' . urlencode('Thiếu ID thuộc tính'));
                break;
            }

            $thuoctinhInfo = $lh->thuoctinhGetById($idThuocTinh);
            $tenThuocTinh = $thuoctinhInfo ? $thuoctinhInfo->tenThuocTinh : "Không xác định";

            $result = $lh->thuoctinhDelete($idThuocTinh);

            if (is_array($result)) {
                if ($result['success']) {

                    header('location: ../../index.php?req=thuoctinhview&result=ok&message=' . urlencode($result['message']));
                } else {

                    $errorParams = [
                        'result=notok',
                        'error_type=' . urlencode($result['error_type']),
                        'message=' . urlencode($result['message'])
                    ];

                    if (isset($result['related_tables'])) {
                        $errorParams[] = 'related_tables=' . urlencode(json_encode($result['related_tables']));
                    }

                    if (isset($result['suggested_action'])) {
                        $errorParams[] = 'suggested_action=' . urlencode($result['suggested_action']);
                    }

                    header('location: ../../index.php?req=thuoctinhview&' . implode('&', $errorParams));
                }
            } else {

                header('location: ../../index.php?req=thuoctinhview&result=' . ($result ? 'ok' : 'notok'));
            }
            break;

        case 'updatethuoctinh':

            if (ob_get_level()) {
                ob_clean();
            }

            if (class_exists('Logger')) {
                Logger::debug("Processing attribute update request", [
                    'post_data' => $_POST,
                    'files_data' => $_FILES
                ]);
            }

            $idThuocTinh = isset($_POST['idThuocTinh']) ? $_POST['idThuocTinh'] : null;
            $tenThuocTinh = isset($_POST['tenThuocTinh']) ? $_POST['tenThuocTinh'] : '';
            $ghiChu = isset($_POST['ghiChu']) ? $_POST['ghiChu'] : '';

            if (class_exists('Logger')) {
                Logger::debug("Parsed attribute data", [
                    'id' => $idThuocTinh,
                    'name' => $tenThuocTinh,
                    'note' => $ghiChu
                ]);
            }

            if (isset($_FILES['fileimage']) && $_FILES['fileimage']['error'] == 0 && file_exists($_FILES['fileimage']['tmp_name'])) {
                $hinhanh_file = $_FILES['fileimage']['tmp_name'];
                $hinhanh = base64_encode(file_get_contents($hinhanh_file));
            } else {
                $hinhanh = isset($_POST['hinhanh']) ? $_POST['hinhanh'] : '';
            }

            if (!$idThuocTinh) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Thiếu ID thuộc tính'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (empty($tenThuocTinh)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Tên thuộc tính không được để trống'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            try {
                if (class_exists('Logger')) {
                    Logger::info("Updating attribute", [
                        'id' => $idThuocTinh,
                        'name' => $tenThuocTinh,
                        'note' => $ghiChu
                    ]);
                }

                $kq = $lh->thuoctinhUpdate($tenThuocTinh, $ghiChu, $hinhanh, $idThuocTinh);

                if (class_exists('Logger')) {
                    Logger::info("Attribute update result", [
                        'success' => (bool)$kq,
                        'rows_affected' => $kq,
                        'attribute_id' => $idThuocTinh
                    ]);
                }

                header('Content-Type: application/json; charset=utf-8');
                if ($kq) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Cập nhật thuộc tính thành công!',
                        'rows_affected' => $kq
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Không thể cập nhật thuộc tính. Vui lòng thử lại.'
                    ], JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::error("Exception in attribute update", [
                        'error' => $e->getMessage(),
                        'attribute_id' => $idThuocTinh
                    ]);
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;

        default:
            header('location: ../../index.php?req=thuoctinhview');
            break;
    }
} else {
    header('location: ../../index.php?req=thuoctinhview');
}
