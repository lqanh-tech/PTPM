<?php

header('Content-Type: application/json');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/ProductReviewCls.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

$security = ApiSecurityMiddleware::getInstance();
$security->handle();

session_start();

try {

    if (!isset($_SESSION['USER'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn cần đăng nhập'
        ]);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT iduser FROM user WHERE username = ?");
    $stmt->execute([$_SESSION['USER']]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $review_id = isset($input['review_id']) ? (int)$input['review_id'] : 0;

    if (!$review_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid review ID'
        ]);
        exit;
    }

    $reviewCls = new ProductReview();
    $result = $reviewCls->markHelpful($review_id, $user->iduser);

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Mark helpful error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error occurred'
    ]);
}
