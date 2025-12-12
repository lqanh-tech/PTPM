<?php
/**
 * API lấy thông tin Page/Blog
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../mod/PageManager.php';

$pageManager = new PageManager();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['error' => 'Missing ID']);
    exit;
}

$page = $pageManager->getPageById($id);

if ($page) {
    echo json_encode($page, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Page not found']);
}
