<?php
require_once("../mod/database.php");
require_once("../mod/hanghoaCls.php");

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header("Cache-Control: public, max-age=86400");
header("Pragma: public");
header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

error_log("displayImage.php trong mhanghoa được gọi với ID: " . (isset($_GET['id']) ? $_GET['id'] : 'không có ID'));

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $imageId = (int)$_GET['id'];
    $hanghoa = new hanghoa();

    $image = $hanghoa->GetHinhAnhById($imageId);

    if ($image && !empty($image->duong_dan)) {

        $isDocker = (getenv('DOCKER_ENV') !== false) || file_exists('/.dockerenv');
        $imageRelativePath = $image->duong_dan;

        if ($isDocker) {
            $imagePath = '/var/www/html/' . $imageRelativePath;
        } else {

            $imagePath = 'D:/PHP_WS/lequocanh/' . $imageRelativePath;
        }

        error_log("Đường dẫn hình ảnh: " . $imagePath);

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

        if ($foundPath) {
            $imagePath = $foundPath;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $imagePath);
            finfo_close($finfo);

            header("Content-Type: $mime");
            header("Content-Length: " . filesize($imagePath));

            header("Cache-Control: public, max-age=86400");
            header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

            readfile($imagePath);
            exit;
        }
    }
}

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
        exit;
    }
}

header("Content-Type: image/png");
$width = 200;
$height = 200;
$image = imagecreatetruecolor($width, $height);

if (function_exists('imagecreatetruecolor')) {

    $bgColor = imagecolorallocate($image, 240, 240, 240);
    $textColor = imagecolorallocate($image, 100, 100, 100);

    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

    $borderColor = imagecolorallocate($image, 200, 200, 200);
    imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

    $text = "No Image";
    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;

    imagestring($image, $font, $x, $y, $text, $textColor);

    imagepng($image);
    imagedestroy($image);
} else {

    $base64Image = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==";
    echo base64_decode($base64Image);
}
