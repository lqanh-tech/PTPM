<?php
/**
 * Static Asset Caching
 * Use this for serving CSS, JS, images with proper caching
 */

$file = $_GET['file'] ?? '';
$type = $_GET['type'] ?? 'css';

// Security: prevent directory traversal
$file = basename($file);

// Define allowed directories
$allowed_dirs = [
    'css' => __DIR__ . '/lequocanh/administrator/elements_LQA/css/',
    'js' => __DIR__ . '/lequocanh/administrator/elements_LQA/js/',
    'img' => __DIR__ . '/lequocanh/uploads/',
];

if (!isset($allowed_dirs[$type])) {
    http_response_code(404);
    exit;
}

$filepath = $allowed_dirs[$type] . $file;

if (!file_exists($filepath)) {
    http_response_code(404);
    exit;
}

// Set content type
$mime_types = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'img' => mime_content_type($filepath),
];

header('Content-Type: ' . $mime_types[$type]);

// Set aggressive caching (1 year)
$expires = 31536000; // 1 year in seconds
header('Cache-Control: public, max-age=' . $expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');

// ETag for validation
$etag = md5_file($filepath);
header('ETag: "' . $etag . '"');

// Check if client has cached version
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
    http_response_code(304);
    exit;
}

// Enable compression
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

// Output file
readfile($filepath);
