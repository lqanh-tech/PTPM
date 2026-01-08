<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../elements_LQA/mod/hanghoaCls.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

error_log("displayImage.php được gọi với ID: " . (isset($_GET['id']) ? $_GET['id'] : 'không có ID'));

$imageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

header('Cache-Control: max-age=86400, public');
header('Pragma: public');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

$etag = md5(isset($_GET['id']) ? $_GET['id'] : '0');
header('ETag: "' . $etag . '"');

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
    header('HTTP/1.1 304 Not Modified');
    exit;
}

if ($imageId <= 0) {

    $defaultImagePaths = [
        "../../elements_LQA/img_LQA/no-image.png",
        "../../../elements_LQA/img_LQA/no-image.png",
        "../elements_LQA/img_LQA/no-image.png",
        "./elements_LQA/img_LQA/no-image.png",
        "../../../administrator/elements_LQA/img_LQA/no-image.png",
        "../../administrator/elements_LQA/img_LQA/no-image.png",
        "../administrator/elements_LQA/img_LQA/no-image.png",
        "./administrator/elements_LQA/img_LQA/no-image.png"
    ];

    $defaultImageFound = false;
    foreach ($defaultImagePaths as $defaultImage) {
        if (file_exists($defaultImage)) {
            header("Content-Type: image/png");
            header("Content-Length: " . filesize($defaultImage));
            readfile($defaultImage);
            $defaultImageFound = true;
            break;
        }
    }

    if (!$defaultImageFound) {

        header("Content-Type: image/png");
        $width = 200;
        $height = 200;
        $image = imagecreatetruecolor($width, $height);

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
    }
    exit;
}

$hanghoa = new hanghoa();
$hinhanh = $hanghoa->GetHinhAnhById($imageId);

error_log("Displaying image ID: " . $imageId);
if ($hinhanh) {
    error_log("Image path: " . $hinhanh->duong_dan);
}

$isDocker = (getenv('DOCKER_ENV') !== false) || file_exists('/.dockerenv');

if ($hinhanh && !empty($hinhanh->duong_dan)) {
    $imagePath = $hinhanh->duong_dan;

    if (strpos($imagePath, 'administrator/') === 0) {

        $imagePath = substr($imagePath, strlen('administrator/'));
    }

    if ($isDocker) {
        $absolutePath = '/var/www/html/' . $imagePath;
        error_log("Docker absolute path: " . $absolutePath);

        if (file_exists($absolutePath)) {
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            $contentType = 'image/jpeg';

            if ($extension === 'png') {
                $contentType = 'image/png';
            } elseif ($extension === 'gif') {
                $contentType = 'image/gif';
            } elseif ($extension === 'webp') {
                $contentType = 'image/webp';
            }

            header('Content-Type: ' . $contentType);
            readfile($absolutePath);
            exit;
        }
    }

    $possiblePaths = [
        '../../../' . $imagePath,
        '../../' . $imagePath,
        '../' . $imagePath,
        '../../../uploads/' . basename($imagePath),
        '../../uploads/' . basename($imagePath),
        '../uploads/' . basename($imagePath),
        './uploads/' . basename($imagePath),
        $imagePath,

        '../../../administrator/' . $imagePath,
        '../../administrator/' . $imagePath,
        '../administrator/' . $imagePath,
        './administrator/' . $imagePath,
        '../../../administrator/uploads/' . basename($imagePath),
        '../../administrator/uploads/' . basename($imagePath),
        '../administrator/uploads/' . basename($imagePath),
        './administrator/uploads/' . basename($imagePath)
    ];

    foreach ($possiblePaths as $path) {
        error_log("Checking path: " . $path);
        if (file_exists($path)) {
            error_log("Found image at: " . $path);

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $contentType = 'image/jpeg';

            if ($extension === 'png') {
                $contentType = 'image/png';
            } elseif ($extension === 'gif') {
                $contentType = 'image/gif';
            } elseif ($extension === 'webp') {
                $contentType = 'image/webp';
            }

            header('Content-Type: ' . $contentType);
            readfile($path);
            exit;
        }
    }
}

$defaultImagePaths = [
    "../../elements_LQA/img_LQA/no-image.png",
    "../../../elements_LQA/img_LQA/no-image.png",
    "../elements_LQA/img_LQA/no-image.png",
    "./elements_LQA/img_LQA/no-image.png",
    "../../../administrator/elements_LQA/img_LQA/no-image.png",
    "../../administrator/elements_LQA/img_LQA/no-image.png",
    "../administrator/elements_LQA/img_LQA/no-image.png",
    "./administrator/elements_LQA/img_LQA/no-image.png"
];

$defaultImageFound = false;
foreach ($defaultImagePaths as $defaultImage) {
    if (file_exists($defaultImage)) {
        header("Content-Type: image/png");
        header("Content-Length: " . filesize($defaultImage));
        readfile($defaultImage);
        $defaultImageFound = true;
        break;
    }
}

if (!$defaultImageFound) {

    header("Content-Type: image/png");
    $width = 200;
    $height = 200;
    $image = imagecreatetruecolor($width, $height);

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
}
