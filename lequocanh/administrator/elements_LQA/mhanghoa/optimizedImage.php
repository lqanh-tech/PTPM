<?php

$cacheTime = 31536000;
header('Cache-Control: public, max-age=' . $cacheTime . ', immutable');
header('CDN-Cache-Control: max-age=' . $cacheTime);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$width = isset($_GET['w']) ? (int)$_GET['w'] : 0;
$quality = isset($_GET['q']) ? (int)$_GET['q'] : 85;

if ($id <= 0) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT duong_dan, loai_file FROM hinhanh WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$image || empty($image->duong_dan)) {

        $noImage = __DIR__ . '/../img_LQA/no-image.png';
        if (file_exists($noImage)) {
            header('Content-Type: image/png');
            readfile($noImage);
        } else {
            http_response_code(404);
        }
        exit;
    }
    
    $etag = md5($id . $image->duong_dan . $width . $quality);
    header('ETag: "' . $etag . '"');
    
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
        trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
        http_response_code(304);
        exit;
    }
    
    $contentType = $image->loai_file ?: 'image/jpeg';
    header('Content-Type: ' . $contentType);
    
    if (strpos($image->duong_dan, 'data:image') === 0) {

        $data = explode(',', $image->duong_dan);
        echo base64_decode($data[1] ?? '');
    } elseif (file_exists($image->duong_dan)) {

        if ($width > 0 && extension_loaded('gd')) {

            outputResizedImage($image->duong_dan, $width, $quality, $contentType);
        } else {
            readfile($image->duong_dan);
        }
    } else {

        $basePath = __DIR__ . '/../../../../';
        $fullPath = $basePath . $image->duong_dan;
        
        if (file_exists($fullPath)) {
            if ($width > 0 && extension_loaded('gd')) {
                outputResizedImage($fullPath, $width, $quality, $contentType);
            } else {
                readfile($fullPath);
            }
        } else {
            http_response_code(404);
        }
    }
    
} catch (Exception $e) {
    error_log('Image error: ' . $e->getMessage());
    http_response_code(500);
}

function outputResizedImage($path, $maxWidth, $quality, $contentType) {
    $info = getimagesize($path);
    if (!$info) {
        readfile($path);
        return;
    }
    
    $origWidth = $info[0];
    $origHeight = $info[1];
    
    if ($origWidth <= $maxWidth) {
        readfile($path);
        return;
    }
    
    $ratio = $maxWidth / $origWidth;
    $newWidth = $maxWidth;
    $newHeight = (int)($origHeight * $ratio);
    
    switch ($info['mime']) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($path);
            break;
        case 'image/png':
            $source = imagecreatefrompng($path);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($path);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($path);
            break;
        default:
            readfile($path);
            return;
    }
    
    if (!$source) {
        readfile($path);
        return;
    }
    
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    
    if ($info['mime'] === 'image/png' || $info['mime'] === 'image/gif') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    
    switch ($info['mime']) {
        case 'image/jpeg':
            imagejpeg($resized, null, $quality);
            break;
        case 'image/png':
            imagepng($resized, null, 9 - round($quality / 10));
            break;
        case 'image/gif':
            imagegif($resized);
            break;
        case 'image/webp':
            imagewebp($resized, null, $quality);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($resized);
}
