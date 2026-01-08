<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();
require_once '../mod/hanghoaCls.php';
$hanghoa = new hanghoa();

$nhatKyHelperPaths = [
    __DIR__ . '/../mnhatkyhoatdong/nhatKyHoatDongHelper.php',
    __DIR__ . '/../../elements_LQA/mnhatkyhoatdong/nhatKyHoatDongHelper.php',
    __DIR__ . '/../../../administrator/elements_LQA/mnhatkyhoatdong/nhatKyHoatDongHelper.php'
];

$foundNhatKyHelper = false;
foreach ($nhatKyHelperPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $foundNhatKyHelper = true;
        break;
    }
}

if (!$foundNhatKyHelper) {
    error_log("Không thể tìm thấy file nhatKyHoatDongHelper.php");
}

$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
if (isset($_REQUEST['reqact'])) {
    $requestAction = $_REQUEST['reqact'];
    switch ($requestAction) {
        case 'addnew':
            $tenhanghoa = $_REQUEST['tenhanghoa'];
            $mota = $_REQUEST['mota'];
            $giathamkhao = $_REQUEST['giathamkhao'];
            $id_hinhanh = isset($_REQUEST['id_hinhanh']) ? $_REQUEST['id_hinhanh'] : 0;
            $idloaihang = $_REQUEST['idloaihang'];
            $idThuongHieu = isset($_REQUEST['idThuongHieu']) ? $_REQUEST['idThuongHieu'] : '';
            $idDonViTinh = isset($_REQUEST['idDonViTinh']) ? $_REQUEST['idDonViTinh'] : '';
            $idNhanVien = isset($_REQUEST['idNhanVien']) ? $_REQUEST['idNhanVien'] : '';
            $ghichu = isset($_REQUEST['ghichu']) ? $_REQUEST['ghichu'] : '';

            $log_file = __DIR__ . '/hanghoa_debug.log';

            $log_data = date('Y-m-d H:i:s') . " - Thêm hàng hóa mới:\n";
            $log_data .= "tenhanghoa: $tenhanghoa\n";
            $log_data .= "mota: $mota\n";
            $log_data .= "giathamkhao: $giathamkhao\n";
            $log_data .= "id_hinhanh: $id_hinhanh\n";
            $log_data .= "idloaihang: $idloaihang\n";
            $log_data .= "idThuongHieu: " . ($idThuongHieu ?: "NULL") . "\n";
            $log_data .= "idDonViTinh: " . ($idDonViTinh ?: "NULL") . "\n";
            $log_data .= "idNhanVien: " . ($idNhanVien ?: "NULL") . "\n";
            file_put_contents($log_file, $log_data, FILE_APPEND);

            try {

                $result = $hanghoa->HanghoaAdd($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu);

                $log_result = date('Y-m-d H:i:s') . " - Kết quả thêm hàng hóa: " . ($result ? "thành công" : "thất bại") . "\n";
                file_put_contents($log_file, $log_result, FILE_APPEND);

                if ($result) {

                    if (is_numeric($result) && $result > 0) {
                        $log_success = date('Y-m-d H:i:s') . " - Thêm hàng hóa thành công với ID: $result\n";
                        file_put_contents($log_file, $log_success, FILE_APPEND);

                        if ($foundNhatKyHelper && !empty($username)) {
                            ghiNhatKyThemMoi($username, 'hàng hóa', $result, "Thêm hàng hóa mới: $tenhanghoa");
                        }
                    }
                    header('location: ../../index.php?req=hanghoaview&result=ok');
                } else {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Thêm hàng hóa thất bại, không có lỗi cụ thể\n", FILE_APPEND);
                    header('location: ../../index.php?req=hanghoaview&result=notok');
                }
            } catch (Exception $e) {

                $log_error = date('Y-m-d H:i:s') . " - Lỗi: " . $e->getMessage() . "\n";
                file_put_contents($log_file, $log_error, FILE_APPEND);

                header('location: ../../index.php?req=hanghoaview&result=notok&error=' . urlencode($e->getMessage()));
            }
            break;

        case 'deletehanghoa':
            $idhanghoa = $_REQUEST['idhanghoa'];

            $hanghoaInfo = $hanghoa->HanghoaGetbyId($idhanghoa);
            $tenhanghoa = $hanghoaInfo ? $hanghoaInfo->tenhanghoa : "Không xác định";

            $result = $hanghoa->HanghoaDelete($idhanghoa);

            if (is_array($result)) {
                if ($result['success']) {

                    if ($foundNhatKyHelper && !empty($username)) {
                        ghiNhatKyXoa($username, 'hàng hóa', $idhanghoa, "Xóa hàng hóa: $tenhanghoa");
                    }
                    header('location: ../../index.php?req=hanghoaview&result=ok&message=' . urlencode($result['message']));
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

                    header('location: ../../index.php?req=hanghoaview&' . implode('&', $errorParams));
                }
            } else {

                if ($result) {
                    if ($foundNhatKyHelper && !empty($username)) {
                        ghiNhatKyXoa($username, 'hàng hóa', $idhanghoa, "Xóa hàng hóa: $tenhanghoa");
                    }
                    header('location: ../../index.php?req=hanghoaview&result=ok');
                } else {
                    header('location: ../../index.php?req=hanghoaview&result=notok');
                }
            }
            break;

        case 'updatehanghoa':
            $idhanghoa = $_REQUEST['idhanghoa'];
            $tenhanghoa = $_REQUEST['tenhanghoa'];
            $mota = $_REQUEST['mota'];
            $giathamkhao = $_REQUEST['giathamkhao'];
            $id_hinhanh = isset($_REQUEST['id_hinhanh']) ? $_REQUEST['id_hinhanh'] : 0;
            $idloaihang = $_REQUEST['idloaihang'];
            $idThuongHieu = isset($_REQUEST['idThuongHieu']) ? $_REQUEST['idThuongHieu'] : '';
            $idDonViTinh = isset($_REQUEST['idDonViTinh']) ? $_REQUEST['idDonViTinh'] : '';
            $idNhanVien = isset($_REQUEST['idNhanVien']) ? $_REQUEST['idNhanVien'] : '';
            $ghichu = isset($_REQUEST['ghichu']) ? $_REQUEST['ghichu'] : '';
            $trang_thai = isset($_REQUEST['trang_thai']) ? (int)$_REQUEST['trang_thai'] : 1;

            $debug_log = isset($_REQUEST['debug_log']) && $_REQUEST['debug_log'] === 'true';
            if ($debug_log) {
                $log_file = __DIR__ . '/debug_log.txt';
                $log_data = date('Y-m-d H:i:s') . " - Hanghoa Update request:\n";
                $log_data .= "idhanghoa: $idhanghoa\n";
                $log_data .= "tenhanghoa: $tenhanghoa\n";
                $log_data .= "trang_thai: $trang_thai\n";
                $log_data .= "POST: " . print_r($_POST, true) . "\n";
                $log_data .= "GET: " . print_r($_GET, true) . "\n";
                $log_data .= "REQUEST: " . print_r($_REQUEST, true) . "\n";
                $log_data .= "--------------------------------------\n";
                file_put_contents($log_file, $log_data, FILE_APPEND);
            }

            try {

                $productUpdateResult = $hanghoa->HanghoaUpdate($tenhanghoa, $id_hinhanh, $mota, $giathamkhao, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $idhanghoa, $ghichu);

                if ($debug_log) {
                    $log_data = date('Y-m-d H:i:s') . " - Product update result: " . ($productUpdateResult ? "Success (rows: $productUpdateResult)" : "No rows affected") . "\n";
                    file_put_contents(__DIR__ . '/debug_log.txt', $log_data, FILE_APPEND);
                }

                $statusUpdateResult = $hanghoa->updateProductStatus($idhanghoa, $trang_thai);

                if ($debug_log) {
                    $log_data = date('Y-m-d H:i:s') . " - Status update result: " . ($statusUpdateResult ? "Success" : "Failed") . "\n";
                    file_put_contents(__DIR__ . '/debug_log.txt', $log_data, FILE_APPEND);
                }

                $finalResult = $productUpdateResult > 0 || $statusUpdateResult;

                if ($debug_log) {
                    $log_data = date('Y-m-d H:i:s') . " - Final result: " . ($finalResult ? "Success" : "Failed") . "\n";
                    file_put_contents(__DIR__ . '/debug_log.txt', $log_data, FILE_APPEND);
                }

                if ($finalResult && $foundNhatKyHelper && !empty($username)) {
                    ghiNhatKyCapNhat($username, 'hàng hóa', $idhanghoa, "Cập nhật hàng hóa: $tenhanghoa");
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $finalResult ? true : false,
                    'message' => $finalResult ? 'Cập nhật hàng hóa thành công!' : 'Cập nhật thất bại!'
                ]);
                exit;
            } catch (Exception $e) {
                if ($debug_log) {
                    $log_data = date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n";
                    file_put_contents(__DIR__ . '/debug_log.txt', $log_data, FILE_APPEND);
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage()
                ]);
                exit;
            }
            break;

        case 'applyimage':
            if (isset($_GET['idhanghoa']) && isset($_GET['id_hinhanh'])) {
                $idhanghoa = intval($_GET['idhanghoa']);
                $id_hinhanh = intval($_GET['id_hinhanh']);

                if ($hanghoa->ApplyImageToProduct($idhanghoa, $id_hinhanh)) {

                    header("location: ../../index.php?req=hanghoaview&result=ok&msg=image_applied");
                } else {

                    header("location: ../../index.php?req=hanghoaview&result=notok&msg=image_not_applied");
                }
            } else {
                header("location: ../../index.php?req=hanghoaview&result=notok");
            }
            break;

        case 'applyallimages':
            if (isset($_GET['matches'])) {
                $matches = json_decode(urldecode($_GET['matches']), true);

                if (empty($matches)) {
                    header("location: ../../index.php?req=hanghoaview&result=notok&msg=no_matches");
                    break;
                }

                $successCount = 0;

                foreach ($matches as $match) {
                    if ($hanghoa->ApplyImageToProduct($match['product_id'], $match['image_id'])) {
                        $successCount++;
                    }
                }

                if ($successCount > 0) {
                    if ($successCount == count($matches)) {

                        header("location: ../../index.php?req=hanghoaview&result=ok&msg=all_images_applied&count=" . $successCount);
                    } else {

                        header("location: ../../index.php?req=hanghoaview&result=notok&msg=some_images_not_applied");
                    }
                } else {

                    header("location: ../../index.php?req=hanghoaview&result=notok&msg=no_images_applied");
                }
            } else {
                header("location: ../../index.php?req=hanghoaview&result=notok");
            }
            break;

        case "remove_mismatched_images":

            $count = $hanghoa->RemoveAllMismatchedImages();

            if ($count === false) {

                header("location: ../../index.php?req=hanghoaview&result=notok&msg=remove_failed");
            } else if ($count > 0) {

                header("location: ../../index.php?req=hanghoaview&result=ok&msg=removed_mismatched&count=" . $count);
            } else {

                header("location: ../../index.php?req=hanghoaview&result=notok&msg=no_images_removed");
            }
            break;

        case "remove_image":

            if (isset($_GET['idhanghoa'])) {
                $idhanghoa = intval($_GET['idhanghoa']);
                $result = $hanghoa->RemoveImageFromProduct($idhanghoa);

                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

                if ($isAjax) {

                    header('Content-Type: application/json');
                    if ($result) {
                        http_response_code(200);
                        echo json_encode(['success' => true, 'message' => 'Đã xóa hình ảnh thành công']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Không thể xóa hình ảnh']);
                    }
                    exit;
                } else {

                    if ($result) {

                        header("location: ../../index.php?req=hanghoaview&result=ok&msg=image_removed");
                    } else {

                        header("location: ../../index.php?req=hanghoaview&result=notok&msg=image_removal_failed");
                    }
                }
            } else {
                if (
                    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
                ) {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Thiếu ID hàng hóa']);
                    exit;
                } else {
                    header("location: ../../index.php?req=hanghoaview&result=notok");
                }
            }
            break;

        default:
            header('location:../../index.php?req=hanghoaview');
            break;
    }
}
