<?php
ob_start();

// Image optimization function
function optimizeImage($imageBinary, $maxWidth = 1200, $maxHeight = 1200, $quality = 85) {
    $image = imagecreatefromstring($imageBinary);
    if (!$image) return $imageBinary;
    
    $origWidth = imagesx($image);
    $origHeight = imagesy($image);
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
    if ($ratio >= 1) {
        // Image is smaller than max, return original
        imagedestroy($image);
        return $imageBinary;
    }
    
    $newWidth = (int)($origWidth * $ratio);
    $newHeight = (int)($origHeight * $ratio);
    
    // Resize
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    
    // Output
    ob_start();
    imagejpeg($resized, null, $quality);
    $optimized = ob_get_clean();
    
    imagedestroy($image);
    imagedestroy($resized);
    
    return $optimized ?: $imageBinary;
}

if (session_status() == PHP_SESSION_NONE) {

    require_once __DIR__ . '/../mod/sessionManager.php';
    require_once __DIR__ . '/../config/logger_config.php';

    SessionManager::start();
}
require_once __DIR__ . '/../../../includes/csrf_helper.php';
require_once("../mod/database.php");
require_once __DIR__ . '/../../../app/autoload.php';

use App\Models\ProductImage;

require_once __DIR__ . '/../../../includes/upload_security.php';

// Verify CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    die('CSRF token validation failed');
}

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
        $requestAction = sanitizeInput($_REQUEST['reqact'] ?? '', 'text');
        switch ($requestAction) {
            case "addnew":
                if (isset($_FILES['fileHinhanh'])) {
                    $files = $_FILES['fileHinhanh'];
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
                                continue;
                            }

                            $fileType = $validation['mime_type'];
                            $maxFileSize = 10 * 1024 * 1024;
                            if ($fileSize > $maxFileSize) {
                                $failedCount++;
                                $errorMessages[] = "File '{$fileName}' vượt quá 10MB.";
                                continue;
                            }

                            $imageNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
                            $autoMatch = sanitizeInput($_POST['auto_match'] ?? '', 'text') === '1';

                            if (!$autoMatch) {
                                $failedCount++;
                                $errorMessages[] = "Tự động khớp bị tắt cho file '{$fileName}'. Bỏ qua.";
                                continue;
                            }

                            $matchedProduct = null;
                            $imageNameLower = mb_strtolower(trim($imageNameWithoutExt), 'UTF-8');

                            // Bước 1: So khớp chính xác (không phân biệt hoa thường)
                            $sqlExact = "SELECT idhanghoa, tenhanghoa FROM hanghoa WHERE LOWER(TRIM(tenhanghoa)) = LOWER(TRIM(?)) LIMIT 1";
                            $stmtExact = $conn->prepare($sqlExact);
                            $stmtExact->execute([$imageNameWithoutExt]);
                            $matchedProduct = $stmtExact->fetch(PDO::FETCH_ASSOC);

                            // Bước 2: Nếu không khớp chính xác, tìm khớp một phần
                            if (!$matchedProduct) {
                                $sqlPartial = "SELECT idhanghoa, tenhanghoa FROM hanghoa";
                                $stmtPartial = $conn->prepare($sqlPartial);
                                $stmtPartial->execute();
                                $allProducts = $stmtPartial->fetchAll(PDO::FETCH_ASSOC);

                                $bestMatch = null;
                                $bestScore = 0;

                                foreach ($allProducts as $product) {
                                    $productNameLower = mb_strtolower(trim($product['tenhanghoa']), 'UTF-8');

                                    // Bỏ qua nếu tên sản phẩm quá ngắn (tránh match sai)
                                    if (mb_strlen($productNameLower, 'UTF-8') < 3) {
                                        continue;
                                    }

                                    // Điểm ưu tiên: khớp chính xác > tên file chứa tên SP > tên SP chứa tên file
                                    if ($productNameLower === $imageNameLower) {
                                        $score = 1000 + mb_strlen($productNameLower, 'UTF-8');
                                    } elseif (mb_strpos($imageNameLower, $productNameLower, 0, 'UTF-8') !== false) {
                                        $score = 500 + mb_strlen($productNameLower, 'UTF-8');
                                    } elseif (mb_strpos($productNameLower, $imageNameLower, 0, 'UTF-8') !== false) {
                                        $score = 100 + mb_strlen($productNameLower, 'UTF-8');
                                    } else {
                                        continue;
                                    }

                                    // Ưu tiên tên dài hơn (cụ thể hơn)
                                    if ($score > $bestScore) {
                                        $bestScore = $score;
                                        $bestMatch = $product;
                                    }
                                }

                                $matchedProduct = $bestMatch;
                            }

                            if ($matchedProduct) {
                                $fileHash = md5_file($fileTmpName);
                                $existingImageId = ProductImage::existsByHash($fileHash);
                                $imageBinary = file_get_contents($fileTmpName);
                                
                                // Optimize image before storing
                                $imageBinary = optimizeImage($imageBinary);

                                if ($existingImageId) {
                                    // Duplicate detected
                                    $existingImageInfo = ProductImage::getById((int)$existingImageId);
                                    if (!isset($_SESSION['duplicate_images']))
                                        $_SESSION['duplicate_images'] = [];

                                    $_SESSION['duplicate_images'][] = [
                                        'product_id' => $matchedProduct['idhanghoa'],
                                        'product_name' => $matchedProduct['tenhanghoa'],
                                        'existing_image_id' => (int) $existingImageId,
                                        'existing_image_info' => $existingImageInfo,
                                        'new_image_data' => 'data:' . $fileType . ';base64,' . base64_encode($imageBinary),
                                        'new_image_binary' => $imageBinary,
                                        'new_image_name' => $fileName,
                                        'new_image_type' => $fileType,
                                        'new_image_hash' => $fileHash,
                                        'upload_timestamp' => time()
                                    ];
                                    $successCount++;
                                } else {
                                    // Normal new image - Store in DB only
                                    $virtualPath = "db_storage/" . $fileName;
                                    if (ProductImage::create($fileName, $fileType, $virtualPath, $fileHash, $imageBinary)) {
                                        $lastInsertId = (int) ProductImage::getLastInsertId();
                                        ProductImage::applyToProduct((int)$matchedProduct['idhanghoa'], $lastInsertId);

                                        if (!isset($_SESSION['matched_images']))
                                            $_SESSION['matched_images'] = [];
                                        $_SESSION['matched_images'][] = [
                                            'product_id' => $matchedProduct['idhanghoa'],
                                            'product_name' => $matchedProduct['tenhanghoa'],
                                            'image_id' => $lastInsertId,
                                            'image_name' => $fileName
                                        ];
                                        $successCount++;
                                    } else {
                                        $failedCount++;
                                        $errorMessages[] = "Lỗi lưu database cho '{$fileName}'.";
                                    }
                                }
                            } else {
                                $failedCount++;
                                $errorMessages[] = "Không tìm thấy sản phẩm khớp với '{$imageNameWithoutExt}'. Tên file phải chứa tên sản phẩm.";
                            }
                        } else {
                            $failedCount++;
                            $errorMessages[] = "Lỗi tải lên file '{$files['name'][$key]}' (Code: {$files['error'][$key]}).";
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

                $id = sanitizeInput($_POST['id'] ?? '', 'int');

                $products = ProductImage::getProductsByImageId($id);

                if (!empty($products)) {
                    $productNames = array_map(function ($product) {
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

                $imagePath = ProductImage::getPath($id);

                deleteImageFile($imagePath);

                $result = ProductImage::delete($id);

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
                    $products = ProductImage::getProductsByImageId($id);
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

                    $imagePath = ProductImage::getPath($id);

                    deleteImageFile($imagePath);

                    $result = ProductImage::delete($id);

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

                if (isset($_POST['action'], $_POST['index'])) {
                    $action = sanitizeInput($_POST['action'] ?? '', 'text');
                    $index = sanitizeInput($_POST['index'] ?? '', 'int');

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

                        $imageBinary = isset($dupInfo['new_image_binary']) ? $dupInfo['new_image_binary'] : null;
                        $virtualPath = "db_storage/" . $dupInfo['new_image_name'];
                        if (ProductImage::create($dupInfo['new_image_name'], $dupInfo['new_image_type'], $virtualPath, $dupInfo['new_image_hash'], $imageBinary)) {
                            $newImageId = ProductImage::getLastInsertId();

                            $sqlUpdateProduct = "UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?";
                            $stmtUpdateProduct = $conn->prepare($sqlUpdateProduct);
                            $stmtUpdateProduct->execute([(int) $newImageId, $dupInfo['product_id']]);

                            $sqlInsertRelation = "INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)";
                            $stmtInsertRelation = $conn->prepare($sqlInsertRelation);
                            $stmtInsertRelation->execute([$dupInfo['product_id'], (int) $newImageId]);

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
                        $stmtUpdateProduct->execute([(int) $dupInfo['existing_image_id'], $dupInfo['product_id']]);

                        $sqlCheckRelation = "SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?";
                        $stmtCheckRelation = $conn->prepare($sqlCheckRelation);
                        $stmtCheckRelation->execute([$dupInfo['product_id'], (int) $dupInfo['existing_image_id']]);

                        if ($stmtCheckRelation->fetchColumn() == 0) {
                            $sqlInsertRelation = "INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)";
                            $stmtInsertRelation = $conn->prepare($sqlInsertRelation);
                            $stmtInsertRelation->execute([$dupInfo['product_id'], (int) $dupInfo['existing_image_id']]);
                        }

                        // Không còn sử dụng file tạm trên disk


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
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi xử lý ảnh trùng lặp: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } else {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
}

if ($requestAction !== "addnew") {
    exit();
}
