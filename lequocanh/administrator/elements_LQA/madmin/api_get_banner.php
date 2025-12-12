<?php

/**
 * API to get banner data for editing
 * GET /administrator/elements_LQA/madmin/api_get_banner.php?id=ID
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

require_once __DIR__ . '/../mod/BannerManager.php';
$bannerManager = new BannerManager();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid banner ID']));
}

$banner = $bannerManager->getBannerById($id);
if (!$banner) {
    http_response_code(404);
    die(json_encode(['error' => 'Banner not found']));
}

header('Content-Type: application/json');
echo json_encode($banner);
