<?php
require_once __DIR__ . '/lequocanh/app/autoload.php';
require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();
try {
    $db->exec("ALTER TABLE don_hang MODIFY COLUMN trang_thai ENUM('pending', 'approved', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending'");
    echo "OK: added 'delivered' status to don_hang.trang_thai\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
