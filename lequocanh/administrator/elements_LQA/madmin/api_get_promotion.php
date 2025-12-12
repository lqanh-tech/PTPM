<?php

/**
 * API to get promotion data for editing
 * GET /administrator/elements_LQA/madmin/api_get_promotion.php?id=ID
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

require_once __DIR__ . '/../mod/PromotionManager.php';
$promotionManager = new PromotionManager();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid promotion ID']));
}

$promotion = $promotionManager->getPromotionById($id);
if (!$promotion) {
    http_response_code(404);
    die(json_encode(['error' => 'Promotion not found']));
}

header('Content-Type: application/json');
echo json_encode($promotion);
