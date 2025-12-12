<?php
/**
 * Optimized Image Display
 * Serve images với caching headers và lazy loading support
 */

// Set caching headers
$cacheTime = 31536000; // 1 year
header('Cache-Control: public, max-age=' . $cacheTime . ', immutable');
header('CDN-Cache-Control: max-age=' . $cacheTime);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');

// Get image ID
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
        // Return no-image placeholder
        $noImage = __DIR__ . '/../img_LQA/no-image.png';
        if (file_exists($noImage)) {
            header('Content-Type: image/png');
            readfile($noImage);
        } else {
            http_response_code(404);
        }
        exit;
    }
    
    // Check ETag
    $etag = md5($id . $image->duong_dan . $width . $quality);
    header('ETag: "' . $etag . '"');
    
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
        trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
        http_response_code(304);
        exit;
    }
    
    // Determine content type
    $contentType = $image->loai_file ?: 'image/jpeg';
    header('Content-Type: ' . $contentType);
    
    // Check if image is base64 or file path
    if (strpos($image->duong_dan, 'data:image') === 0) {
        // Base64 image
        $data = explode(',', $image->duong_dan);
        echo base64_decode($data[1] ?? '');
    } elseif (file_exists($image->duong_dan)) {
        // File path
        if ($width > 0 && extension_loaded('gd')) {
            // Resize image
            outputResizedImage($image->duong_dan, $width, $quality, $contentType);
        } else {
            readfile($image->duong_dan);
        }
    } else {
        // Try relative path
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

/**
 * Output resized image
 */
function outputResizedImage($path, $maxWidth, $quality, $contentType) {
    $info = getimagesize($path);
    if (!$info) {
        readfile($path);
        return;
    }
    
    $origWidth = $info[0];
    $origHeight = $info[1];
    
    // Don't upscale
    if ($origWidth <= $maxWidth) {
        readfile($path);
        return;
    }
    
    // Calculate new dimensions
    $ratio = $maxWidth / $origWidth;
    $newWidth = $maxWidth;
    $newHeight = (int)($origHeight * $ratio);
    
    // Create image resource
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
    
    // Create resized image
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG/GIF
    if ($info['mime'] === 'image/png' || $info['mime'] === 'image/gif') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    
    // Output
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
