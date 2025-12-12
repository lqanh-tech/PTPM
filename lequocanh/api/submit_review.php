<?php
/**
 * Submit Product Review API
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../administrator/elements_LQA/mod/ProductReviewCls.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['USER'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn cần đăng nhập để đánh giá'
        ]);
        exit;
    }

    // Get user ID
    require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT iduser FROM user WHERE username = ?");
    $stmt->execute([$_SESSION['USER']]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy thông tin người dùng'
        ]);
        exit;
    }

    $iduser = $user->iduser;

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $idhanghoa = isset($input['idhanghoa']) ? (int)$input['idhanghoa'] : 0;
    $rating = isset($input['rating']) ? (int)$input['rating'] : 0;
    $title = isset($input['title']) ? trim($input['title']) : '';
    $text = isset($input['text']) ? trim($input['text']) : '';

    // Validate input
    if (!$idhanghoa || !$rating || !$title || !$text) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng điền đầy đủ thông tin'
        ]);
        exit;
    }

    // Initialize review class
    $reviewCls = new ProductReview();

    // Check if user can review
    $canReview = $reviewCls->canUserReview($iduser, $idhanghoa);
    
    if (!$canReview['can_review']) {
        echo json_encode([
            'success' => false,
            'message' => $canReview['reason']
        ]);
        exit;
    }

    //Sanitize inputs
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Add review
    $idhoadon = isset($canReview['idhoadon']) ? $canReview['idhoadon'] : null;
    $result = $reviewCls->addReview($idhanghoa, $iduser, $idhoadon, $rating, $title, $text);

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Submit review error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
    ]);
}
