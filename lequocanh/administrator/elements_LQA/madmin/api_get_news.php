<?php

/**
 * API to get news data for editing
 * GET /administrator/elements_LQA/madmin/api_get_news.php?id=ID
 */

require_once __DIR__ . '/../mod/sessionManager.php';
SessionManager::start();

// Check access rights using PhanQuyen system
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
