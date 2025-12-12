<?php
/**
 * Debug Support Page
 */
require_once '../administrator/elements_LQA/mod/sessionManager.php';
SessionManager::start();

header('Content-Type: application/json');

$debug = [
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
    'user_logged_in' => isset($_SESSION['USER']),
    'user_id' => $_SESSION['USER'] ?? null,
    'base_url_defined' => defined('BASE_URL'),
    'base_url_value' => defined('BASE_URL') ? BASE_URL : null,
    'current_url' => $_SERVER['REQUEST_URI'] ?? null,
    'http_host' => $_SERVER['HTTP_HOST'] ?? null,
];

// Test API call
try {
    require_once '../administrator/elements_LQA/mod/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if (isset($_SESSION['USER'])) {
        $userId = $_SESSION['USER'];
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM support_tickets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug['ticket_count'] = $result['count'];
    } else {
        $debug['ticket_count'] = 'Not logged in';
    }
} catch (Exception $e) {
    $debug['database_error'] = $e->getMessage();
}

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
