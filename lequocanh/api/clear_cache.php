<?php

header('Content-Type: application/json');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';

$security = ApiSecurityMiddleware::getInstance();
$security->handle();

session_start();
if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../cache/CacheManager.php';

$action = $_GET['action'] ?? 'all';
$cache = CacheManager::getInstance();

try {
    switch ($action) {
        case 'all':
            $cache->clear();
            $message = 'Đã xóa tất cả cache';
            break;
            
        case 'products':

            $cacheDir = __DIR__ . '/../cache';
            $files = glob($cacheDir . '/products_*.cache');
            $files = array_merge($files, glob($cacheDir . '/rating_*.cache'));
            foreach ($files as $file) {
                unlink($file);
            }
            $message = 'Đã xóa cache sản phẩm';
            break;
            
        case 'pages':

            $cacheDir = __DIR__ . '/../cache';
            $files = glob($cacheDir . '/page_*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
            $message = 'Đã xóa cache trang';
            break;
            
        default:

            $cache->delete($action);
            $message = "Đã xóa cache: {$action}";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
