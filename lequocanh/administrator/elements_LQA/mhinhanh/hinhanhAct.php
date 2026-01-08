<?php
if (session_status() == PHP_SESSION_NONE) {

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();
}
require_once("../mod/database.php");
require_once("../mod/hanghoaCls.php");

require_once __DIR__ . '/../../includes/upload_security.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(dirname(dirname(__FILE__))) . '/upload_errors.log');

$db = Database::getInstance();
$conn = $db->getConnection();

if (!isset($_REQUEST["reqact"]) || $_REQUEST["reqact"] !== "addnew") {
    header('Content-Type: application/json; charset=utf-8');
}

function isAjaxRequest()
{
    return (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) || (
        isset($_GET['ajax']) && $_GET['ajax'] === '1'
    );
}

function deleteImageFile($imagePath)
{
    if ($imagePath) {

        $fullPath = dirname(dirname(dirname(__FILE__))) . '/' . $imagePath;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
    }
    return true;
}

try {
    if (isset($_REQUEST["reqact"])) {
        $requestAction = $_REQUEST["reqact"];
        $hanghoa = new hanghoa();

        switch ($requestAction) {
            case "addnew":
                if (isset($_FILES['files'])) {
                    $files = $_FILES['files'];
                    $totalFiles = count($files['tmp_name']);
                    $successCount = 0;
                    $failedCount = 0;
                    $appliedImages = [];
                    $errorMessages = [];

                    if (empty($files['name'][0])) {
                        $_SESSION['upload_errors'] = ['Vui lòng chọn ít nhất một file hình ảnh để tải lên.'];
                        header('location: ../../index.php?req=hinhanhview&result=nofiles');
                        exit();
                    }

                    $isDocker = (getenv('DOCKER_ENV') !== false) || file_exists('/.dockerenv');
                    $uploadDirAbsolute = $isDocker ? '/var/www/html/administrator/uploads/' : 'D:/PHP_WS/lequocanh/administrator/uploads/';

                    error_log("Upload to directory: " . $uploadDirAbsolute . " in " . ($isDocker ? 'Docker' : 'Windows') . " environment");

                    if (!file_exists($uploadDirAbsolute)) {
                        if (!mkdir($uploadDirAbsolute, 0777, true)) {
                            error_log("Failed to create upload directory: " . $uploadDirAbsolute);
                            $_SESSION['upload_errors'] = ['Không thể tạo thư mục upload. Vui lòng kiểm tra quyền truy cập hoặc tạo thư mục thủ công.'];
                            header('location: ../../index.php?req=hinhanhview&result=notok');
                            exit();
                        } else {
                            chmod($uploadDirAbsolute, 0777);
                        }
                    }

                    if (!is_writable($uploadDirAbsolute)) {
                        error_log("Upload directory is not writable: " . $uploadDirAbsolute);
                        $_SESSION['upload_errors'] = ['Thư mục upload không có quyền ghi. Vui lòng kiểm tra quyền truy cập.'];
                        header('location: ../../index.php?req=hinhanhview&result=notok');
                        exit();
                    }

                    foreach ($files['tmp_name'] as $key => $tmp_name) {
                        if ($files['error'][$key] === 0) {
                            $fileName = $files['name'][$key];
                            $fileType = $files['type'][$key];
                            $fileTmpName = $files['tmp_name'][$key];
                            $fileSize = $files['size'][$key];
                            
                            $singleFile = [
                                'name' => $fileName,
                                'type' => $fileType,
                                'tmp_name' => $fileTmpName,
                                'error' => $files['error'][$key],
                                'size' => $fileSize
                            ];
                            
                            $validation = UploadSecurity::validate($singleFile, 'image');
                            
                            if (!$validation['valid']) {
                                $failedCount++;
                                $errorMsg = "File '{$fileName}': " . $validation['error'];
                                $errorMessages[] = $errorMsg;
                                error_log("Upload security validation failed: " . $errorMsg);
                                continue;
                            }
                            
                            $fileType = $validation['mime_type'];

                            $maxFileSize = 10 * 1024 * 1024;
                            if ($fileSize > $maxFileSize) {
                                $failedCount++;
                                $errorMsg = "File '{$fileName}' vượt quá kích thước cho phép (10MB).";
                                $errorMessages[] = $errorMsg;
                                error_log($errorMsg);
                                continue;
                            }

                            $imageNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);

                            $sqlMatchProduct = "SELECT idhanghoa, tenhanghoa FROM hanghoa WHERE TRIM(tenhanghoa) = TRIM(?)";
                            $stmtMatchProduct = $conn->prepare($sqlMatchProduct);
                            $stmtMatchProduct->execute([$imageNameWithoutExt]);
                            $matchedProduct = $stmtMatchProduct->fetch(PDO::FETCH_ASSOC);

                            if ($matchedProduct && trim($matchedProduct['tenhanghoa']) === trim($imageNameWithoutExt)) {

                                $fileHash = md5_file($fileTmpName);
                                $existingImageId = $hanghoa->CheckImageExistsByHash($fileHash);

                                if ($existingImageId) {

                                    error_log("Ảnh trùng lặp đã được phát hiện, ID hiện tại: " . $existingImageId);

                                    $existingImageInfo = $hanghoa->GetHinhAnhById($existingImageId);

                                    $newFileName = uniqid() . '_' . basename($fileName);
                                    $targetPath = $uploadDirAbsolute . $newFileName;
                                    $relativePath = "administrator/uploads/" . $newFileName;

                                    if (move_uploaded_file($fileTmpName, $targetPath)) {

                                        if (!isset($_SESSION['duplicate_images'])) {
                                            $_SESSION['duplicate_images'] = [];
                                        }

                                        $webRoot = $isDocker ? '/' : '/lequocanh/';
                                        $displayPath = $webRoot . $relativePath;

                                        $_SESSION['duplicate_images'][] = [
                                            'product_id' => $matchedProduct['idhanghoa'],
                                            'product_name' => $matchedProduct['tenhanghoa'],
                                            'existing_image_id' => (int)$existingImageId,
                                            'existing_image_info' => $existingImageInfo,
                                            'new_image_path' => $displayPath,
                                            'new_image_name' => $fileName,
                                            'new_image_type' => $fileType,
                                            'new_image_hash' => $fileHash,
                                            'temp_path' => $targetPath,
                                            'relative_path' => $relativePath,
                                            'upload_timestamp' => time()
                                        ];

                                        $successCount++;
                                    } else {
                                        $failedCount++;
                                        $error = error_get_last();
                                        $errorMsg = "Không thể tải lên file trùng lặp '" . $fileName . "' để xem trước. ";
                                        if ($error) {
                                            $errorMsg .= "Lỗi PHP: " . $error['message'];
                                        }
                                        $errorMessages[] = $errorMsg;
                                        error_log("Failed to move uploaded file for preview: " . $fileName);
                                    }

                                    continue;
                                }

                                $newFileName = uniqid() . '_' . basename($fileName);
                                $targetPath = $uploadDirAbsolute . $newFileName;

                                error_log("Uploading file: " . $fileName);
                                error_log("Target path: " . $targetPath);
                                error_log("File hash: " . $fileHash);

                                if (move_uploaded_file($fileTmpName, $targetPath)) {

                                    $relativePath = "administrator/uploads/" . $newFileName;

                                    if ($hanghoa->ThemHinhAnh($fileName, $fileType, $relativePath, $fileHash)) {
                                        $successCount++;
                                        $lastInsertId = $hanghoa->GetLastInsertId();

                                        $sqlUpdateProduct = "UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?";
                                        $stmtUpdateProduct = $conn->prepare($sqlUpdateProduct);
                                        $stmtUpdateProduct->execute([(int)$lastInsertId, $matchedProduct['idhanghoa']]);

                                        $sqlCheckRelation = "SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?";
                                        $stmtCheckRelation = $conn->prepare($sqlCheckRelation);
                                        $stmtCheckRelation->execute([$matchedProduct['idhanghoa'], (int)$lastInsertId]);

                                        if ($stmtCheckRelation->fetchColumn() == 0) {
                                            $sqlInsertRelation = "INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)";
                                            $stmtInsertRelation = $conn->prepare($sqlInsertRelation);
                                            $stmtInsertRelation->execute([$matchedProduct['idhanghoa'], (int)$lastInsertId]);
                                        }

                                        if (!isset($_SESSION['matched_images'])) {
                                            $_SESSION['matched_images'] = [];
                                        }

                                        $_SESSION['matched_images'][] = [
                                            'product_id' => $matchedProduct['idhanghoa'],
                                            'product_name' => $matchedProduct['tenhanghoa'],
                                            'image_id' => (int)$lastInsertId,
                                            'image_name' => $fileName
                                        ];

                                        if ($hanghoa->ApplyImageToProduct($matchedProduct['idhanghoa'], (int)$lastInsertId)) {
                                            $_SESSION['matched_images'][] = [
                                                'image_name' => $fileName,
                                                'product_name' => $matchedProduct['tenhanghoa'],
                                                'product_id' => $matchedProduct['idhanghoa'],
                                                'image_id' => (int)$lastInsertId,
                                                'auto_applied' => true
                                            ];
                                        }
                                    } else {
                                        $failedCount++;
                                        $errorMsg = "Không thể lưu thông tin ảnh '{$fileName}' vào cơ sở dữ liệu.";
                                        $errorMessages[] = $errorMsg;
                                        error_log($errorMsg);

                                        if (file_exists($targetPath)) {
                                            unlink($targetPath);
                                        }
                                    }
                                } else {
                                    $failedCount++;
                                    $error = error_get_last();
                                    $errorMsg = "Không thể tải lên file '" . $fileName . "'. ";
                                    if ($error) {
                                        $errorMsg .= "Lỗi PHP: " . $error['message'];
                                    }
                                    $errorMessages[] = $errorMsg;
                                    error_log("Failed to move uploaded file: " . $fileName);
                                    error_log("PHP Upload error: " . ($error ? $error['message'] : 'Unknown error'));
                                }
                            } else {
                                $failedCount++;
                                $errorMsg = "Không tìm thấy sản phẩm nào có tên trùng khớp với tên file '{$imageNameWithoutExt}'.";
                                $errorMessages[] = $errorMsg;
                                error_log($errorMsg);
                                continue;
                            }
                        } else {
                            $failedCount++;
                            $errorCode = $files['error'][$key];
                            $errorMsg = "Lỗi tải lên file '" . $files['name'][$key] . "'. ";

                            switch ($errorCode) {
                                case UPLOAD_ERR_INI_SIZE:
                                    $errorMsg .= "File vượt quá kích thước cho phép trong php.ini.";
                                    break;
                                case UPLOAD_ERR_FORM_SIZE:
                                    $errorMsg .= "File vượt quá kích thước cho phép trong form.";
                                    break;
                                case UPLOAD_ERR_PARTIAL:
                                    $errorMsg .= "File chỉ được tải lên một phần.";
                                    break;
                                case UPLOAD_ERR_NO_FILE:
                                    $errorMsg .= "Không có file nào được tải lên.";
                                    break;
                                case UPLOAD_ERR_NO_TMP_DIR:
                                    $errorMsg .= "Thiếu thư mục tạm.";
                                    break;
                                case UPLOAD_ERR_CANT_WRITE:
                                    $errorMsg .= "Không thể ghi file vào đĩa.";
                                    break;
                                case UPLOAD_ERR_EXTENSION:
                                    $errorMsg .= "Tải lên bị chặn bởi extension.";
                                    break;
                                default:
                                    $errorMsg .= "Lỗi không xác định (code: " . $errorCode . ").";
                            }

                            $errorMessages[] = $errorMsg;
                            error_log("File upload error code: " . $errorCode . " for file: " . $files['name'][$key]);
                        }
                    }

                    if (!empty($errorMessages)) {
                        $_SESSION['upload_errors'] = $errorMessages;
                    }

                    if (isset($_SESSION['duplicate_images']) && !empty($_SESSION['duplicate_images'])) {

                        header('location: ../../index.php?req=hinhanhview&result=duplicates&success=' . $successCount . '&failed=' . $failedCount);
                    } else if ($totalFiles > 0 && $failedCount === 0) {

                        header('location: ../../index.php?req=hinhanhview&result=ok&count=' . $successCount);
                    } else if ($successCount > 0) {

                        header('location: ../../index.php?req=hinhanhview&result=partial&success=' . $successCount . '&failed=' . $failedCount);
                    } else {

                        header('location: ../../index.php?req=hinhanhview&result=notok');
                    }
                    exit();
                } else {
                    $_SESSION['upload_errors'] = ['Không có dữ liệu file được gửi. Vui lòng thử lại.'];
                    header('location: ../../index.php?req=hinhanhview&result=nofiles');
                    exit();
                }
                break;

            case "deleteimage":
                if (!isset($_POST["id"])) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Thiếu ID hình ảnh'
                    ]);
                    exit;
                }

                $id = intval($_POST["id"]);

                $products = $hanghoa->GetProductsByImageId($id);

                if (!empty($products)) {
                    $productNames = array_map(function($product) {
                        return $product['tenhanghoa'];
                    }, $products);

                    echo json_encode([
                        'success' => false,
                        'message' => 'Không thể xóa hình ảnh vì đang được sử dụng bởi sản phẩm: ' . implode(', ', $productNames),
                        'inUse' => true,
                        'products' => $products
                    ]);
                    exit;
                }

                $imagePath = $hanghoa->GetImagePath($id);

                deleteImageFile($imagePath);

                $result = $hanghoa->XoaHinhAnh($id);

                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Xóa hình ảnh thành công'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Không thể xóa hình ảnh khỏi database'
                    ]);
                }
                exit;

            case "deletemultiple":

                $jsonData = file_get_contents('php://input');
                $data = json_decode($jsonData, true);

                if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Không có ID hình ảnh nào được cung cấp'
                    ]);
                    exit;
                }

                $ids = array_map('intval', $data['ids']);
                $successCount = 0;
                $failedCount = 0;
                $inUseImages = [];
                $inUseProducts = [];

                foreach ($ids as $id) {
                    $products = $hanghoa->GetProductsByImageId($id);
                    if (!empty($products)) {
                        $inUseImages[] = $id;
                        foreach ($products as $product) {
                            if (!isset($inUseProducts[$id])) {
                                $inUseProducts[$id] = [];
                            }
                            $inUseProducts[$id][] = $product['tenhanghoa'];
                        }
                    }
                }

                if (!empty($inUseImages)) {
                    $inUseMessages = [];
                    foreach ($inUseImages as $id) {
                        $inUseMessages[] = "Hình ảnh ID: " . $id . " đang được sử dụng bởi sản phẩm: " . implode(', ', $inUseProducts[$id]);
                    }

                    echo json_encode([
                        'success' => false,
                        'message' => "Không thể xóa hình ảnh vì một số hình ảnh đang được sử dụng bởi sản phẩm.",
                        'inUse' => true,
                        'inUseImages' => $inUseImages,
                        'inUseDetails' => $inUseMessages
                    ]);
                    exit;
                }

                foreach ($ids as $id) {

                    $imagePath = $hanghoa->GetImagePath($id);

                    deleteImageFile($imagePath);

                    $result = $hanghoa->XoaHinhAnh($id);

                    if ($result) {
                        $successCount++;
                    } else {
                        $failedCount++;
                    }
                }

                if ($successCount > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => "Đã xóa thành công " . $successCount . " hình ảnh.",
                        'successCount' => $successCount,
                        'failedCount' => $failedCount
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => "Không thể xóa hình ảnh. Vui lòng thử lại.",
                        'successCount' => $successCount,
                        'failedCount' => $failedCount
                    ]);
                }
                exit;

            case "resolve_duplicate":

                if (!isAjaxRequest()) {

                    $redirectUrl = $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'ajax=1';
                    header('Location: ' . $redirectUrl);
                    exit();
                }

                if (isset($_GET['action'], $_GET['index'])) {
                    $action = $_GET['action'];
                    $index = (int)$_GET['index'];

                    error_log("resolve_duplicate - Action: " . $action . ", Index: " . $index);
                    error_log("resolve_duplicate - Session duplicate_images exists: " . (isset($_SESSION['duplicate_images']) ? 'Yes' : 'No'));
                    if (isset($_SESSION['duplicate_images'])) {
                        error_log("resolve_duplicate - Session duplicate_images count: " . count($_SESSION['duplicate_images']));
                        error_log("resolve_duplicate - Session duplicate_images keys: " . implode(', ', array_keys($_SESSION['duplicate_images'])));
                    }

                    if (!isset($_SESSION['duplicate_images'])) {
                        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin ảnh trùng lặp trong session']);
                        exit();
                    }

                    if (!isset($_SESSION['duplicate_images'][$index])) {
                        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin ảnh trùng lặp với index: ' . $index]);
                        exit();
                    }

                    $dupInfo = $_SESSION['duplicate_images'][$index];

                    if ($action === 'use_new') {

                        if ($hanghoa->ThemHinhAnh($dupInfo['new_image_name'], $dupInfo['new_image_type'], $dupInfo['relative_path'], $dupInfo['new_image_hash'])) {
                            $newImageId = $hanghoa->GetLastInsertId();

                            $sqlUpdateProduct = "UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?";
                            $stmtUpdateProduct = $conn->prepare($sqlUpdateProduct);
                            $stmtUpdateProduct->execute([(int)$newImageId, $dupInfo['product_id']]);

                            $sqlInsertRelation = "INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)";
                            $stmtInsertRelation = $conn->prepare($sqlInsertRelation);
                            $stmtInsertRelation->execute([$dupInfo['product_id'], (int)$newImageId]);

                            $_SESSION['resolved_images'][] = [
                                'product_name' => $dupInfo['product_name'],
                                'image_name' => $dupInfo['new_image_name'],
                                'action' => 'used_new'
                            ];

                            echo json_encode(['success' => true, 'message' => 'Đã sử dụng ảnh mới']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Không thể lưu ảnh mới vào CSDL']);
                        }
                    } else if ($action === 'use_existing') {

                        $sqlUpdateProduct = "UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?";
                        $stmtUpdateProduct = $conn->prepare($sqlUpdateProduct);
                        $stmtUpdateProduct->execute([(int)$dupInfo['existing_image_id'], $dupInfo['product_id']]);

                        $sqlCheckRelation = "SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?";
                        $stmtCheckRelation = $conn->prepare($sqlCheckRelation);
                        $stmtCheckRelation->execute([$dupInfo['product_id'], (int)$dupInfo['existing_image_id']]);

                        if ($stmtCheckRelation->fetchColumn() == 0) {
                            $sqlInsertRelation = "INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)";
                            $stmtInsertRelation = $conn->prepare($sqlInsertRelation);
                            $stmtInsertRelation->execute([$dupInfo['product_id'], (int)$dupInfo['existing_image_id']]);
                        }

                        if (file_exists($dupInfo['temp_path'])) {
                            unlink($dupInfo['temp_path']);
                        }

                        $_SESSION['resolved_images'][] = [
                            'product_name' => $dupInfo['product_name'],
                            'image_name' => $dupInfo['new_image_name'],
                            'action' => 'used_existing'
                        ];

                        echo json_encode(['success' => true, 'message' => 'Đã sử dụng ảnh hiện có']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
                    }

                    error_log("Trước khi xóa - Session duplicate_images count: " . count($_SESSION['duplicate_images']));
                    error_log("Trước khi xóa - Session duplicate_images keys: " . implode(', ', array_keys($_SESSION['duplicate_images'])));

                    unset($_SESSION['duplicate_images'][$index]);

                    error_log("Sau khi xóa - Session duplicate_images count: " . count($_SESSION['duplicate_images']));
                    if (count($_SESSION['duplicate_images']) > 0) {
                        error_log("Sau khi xóa - Session duplicate_images keys: " . implode(', ', array_keys($_SESSION['duplicate_images'])));
                    }

                    if (empty($_SESSION['duplicate_images']) || count($_SESSION['duplicate_images']) === 0) {
                        error_log("Xóa toàn bộ session duplicate_images vì không còn phần tử nào");
                        $_SESSION['duplicate_images'] = array();
                    } else {

                        $_SESSION['duplicate_images'] = array_values($_SESSION['duplicate_images']);
                        error_log("Sau khi sắp xếp lại - Session duplicate_images count: " . count($_SESSION['duplicate_images']));
                        error_log("Sau khi sắp xếp lại - Session duplicate_images keys: " . implode(', ', array_keys($_SESSION['duplicate_images'])));
                    }

                    exit();
                }

                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']);
                break;

            default:
                throw new Exception("Hành động không hợp lệ");
        }
    } else {
        throw new Exception("Thiếu tham số hành động");
    }
} catch (Exception $e) {
    error_log("Exception in hinhanhAct.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());

    if ($requestAction === "addnew") {
        $_SESSION['upload_errors'] = ['Lỗi hệ thống: ' . $e->getMessage()];
        header("location: ../../index.php?req=hinhanhview&result=notok");
    } else if ($requestAction === "resolve_duplicate") {

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi xử lý ảnh trùng lặp: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
}

if ($requestAction !== "addnew") {
    exit();
}
