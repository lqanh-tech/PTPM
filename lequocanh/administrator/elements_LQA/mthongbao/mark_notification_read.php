<?php
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/CustomerNotificationManager.php';

SessionManager::start();

header('Content-Type: application/json');

if (!isset($_SESSION['USER'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'No notification ID provided']);
    exit;
}

$userId = $_SESSION['USER'];
$notificationId = intval($_POST['notification_id']);

$notificationManager = new CustomerNotificationManager();
$result = $notificationManager->markAsRead($notificationId, $userId);

echo json_encode(['success' => $result]);
?>
