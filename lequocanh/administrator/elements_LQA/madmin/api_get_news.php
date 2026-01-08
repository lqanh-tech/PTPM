<?php

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header_remove('X-Powered-By');

require_once __DIR__ . '/../mod/sessionManager.php';
SessionManager::start();

require_once __DIR__ . '/../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!$phanQuyen->checkAccess('marketing_content', $username)) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once __DIR__ . '/../mod/NewsManager.php';
$newsManager = new NewsManager();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid news ID']));
}

$news = $newsManager->getNewsById($id);
if (!$news) {
    http_response_code(404);
    die(json_encode(['error' => 'News not found']));
}

header('Content-Type: application/json');
echo json_encode($news);
