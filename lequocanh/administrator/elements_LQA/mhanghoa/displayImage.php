<?php
require_once("../mod/database.php");
require_once("../mod/hanghoaCls.php");

// Tắt báo lỗi để tránh output không mong muốn
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Thiết lập header cache để tránh tải lại liên tục
header("Cache-Control: public, max-age=86400"); // Cache 1 ngày
header("Pragma: public");
header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

// Ghi log để debug
error_log("displayImage.php trong mhanghoa được gọi với ID: " . (isset($_GET['id']) ? $_GET['id'] : 'không có ID'));

// Đảm bảo có ID ảnh
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $imageId = (int)$_GET['id'];
    $hanghoa = new hanghoa();

    // Lấy thông tin hình ảnh
    $image = $hanghoa->GetHinhAnhById($imageId);

    if ($image && !empty($image->duong_dan)) {
        // Xác định môi trường và cài đặt đường dẫn phù hợp
        $isDocker = (getenv('DOCKER_ENV') !== false) || file_exists('/.dockerenv');
        $imageRelativePath = $image->duong_dan;

        // Xây dựng đường dẫn tuyệt đối
        if ($isDocker) {
            $imagePath = '/var/www/html/' . $imageRelativePath;
        } else {
            // Trong trường hợp Windows, xây dựng đường dẫn thích hợp
            $imagePath = 'D:/PHP_WS/lequocanh/' . $imageRelativePath;
        }

        error_log("Đường dẫn hình ảnh: " . $imagePath);

        // Thử các đường dẫn khác nhau nếu đường dẫn chính không tồn tại
        $possiblePaths = [
            $imagePath,
            'D:/PHP_WS/lequocanh/' . $imageRelativePath,
            'D:/PHP_WS/' . $imageRelativePath,
            '../../../' . $imageRelativePath,
            '../../' . $imageRelativePath,
            '../' . $imageRelativePath,
            $imageRelativePath,
            '../../../uploads/' . basename($imageRelativePath),
            '../../uploads/' . basename($imageRelativePath),
            '../uploads/' . basename($imageRelativePath),
            './uploads/' . basename($imageRelativePath)
        ];

        $foundPath = null;
        foreach ($possiblePaths as $path) {
            error_log("Kiểm tra đường dẫn: " . $path);
            if (file_exists($path)) {
                $foundPath = $path;
                error_log("Tìm thấy hình ảnh tại: " . $path);
                break;
            }
        }

        // Kiểm tra xem file có tồn tại không
        if ($foundPath) {
            $imagePath = $foundPath;
            // Xác định loại MIME của file
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $imagePath);
            finfo_close($finfo);

            // Thiết lập header để tránh cache và định dạng đúng loại file
            header("Content-Type: $mime");
            header("Content-Length: " . filesize($imagePath));

            // Luôn sử dụng cache để tránh tải lại liên tục
            header("Cache-Control: public, max-age=86400"); // Cache 1 ngày
            header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

            // Đọc và xuất nội dung file
            readfile($imagePath);
            exit;
        }
    }
}

// Nếu không tìm thấy ảnh hoặc có lỗi, trả về hình ảnh mặc định
$defaultImagePaths = [
    "../../../elements_LQA/img_LQA/no-image.png",
    "../../elements_LQA/img_LQA/no-image.png",
    "../elements_LQA/img_LQA/no-image.png",
    "./elements_LQA/img_LQA/no-image.png",
    "../../../administrator/elements_LQA/img_LQA/no-image.png",
    "../../administrator/elements_LQA/img_LQA/no-image.png",
    "../administrator/elements_LQA/img_LQA/no-image.png",
    "./administrator/elements_LQA/img_LQA/no-image.png",
    "../../../img_LQA/no-image.png",
    "../../img_LQA/no-image.png",
    "../img_LQA/no-image.png",
    "./img_LQA/no-image.png"
];

$defaultImageFound = false;
foreach ($defaultImagePaths as $defaultImage) {
    if (file_exists($defaultImage)) {
        header("Content-Type: image/png");
        header("Content-Length: " . filesize($defaultImage));
        readfile($defaultImage);
        $defaultImageFound = true;
        exit; // Exit after sending the default image
    }
}

// Nếu không tìm thấy file no-image.png, tạo một hình ảnh đơn giản với text "No Image"
header("Content-Type: image/png");
$width = 200;
$height = 200;
$image = imagecreatetruecolor($width, $height);

// Kiểm tra nếu GD extension có sẵn
if (function_exists('imagecreatetruecolor')) {
    // Màu nền và màu chữ
    $bgColor = imagecolorallocate($image, 240, 240, 240);
    $textColor = imagecolorallocate($image, 100, 100, 100);

    // Vẽ nền
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

    // Vẽ viền
    $borderColor = imagecolorallocate($image, 200, 200, 200);
    imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

    // Thêm text
    $text = "No Image";
    $font = 5; // Font mặc định
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;

    imagestring($image, $font, $x, $y, $text, $textColor);

    // Output hình ảnh
    imagepng($image);
    imagedestroy($image);
} else {
    // Nếu GD không có sẵn, tạo một hình ảnh PNG đơn giản bằng dữ liệu base64
    // Đây là một hình ảnh PNG 1x1 pixel trong base64
    $base64Image = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==";
    echo base64_decode($base64Image);
}
