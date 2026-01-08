<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();
require_once '../mod/database.php';

if (!isset($_SESSION['ADMIN'])) {
    header('Location: ../../userLogin.php');
    exit();
}

echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật cấu trúc bảng orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .alert {
            margin-bottom: 10px;
        }
        .btn-back {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cập nhật cấu trúc bảng orders</h2>';

$db = Database::getInstance();
$conn = $db->getConnection();

try {

    $checkTableSql = "SHOW TABLES LIKE 'orders'";
    $checkTableStmt = $conn->prepare($checkTableSql);
    $checkTableStmt->execute();

    if ($checkTableStmt->rowCount() == 0) {
        echo "<p>Bảng orders không tồn tại. Vui lòng tạo bảng orders trước.</p>";
        exit();
    }

    $columns = [
        'pending_read' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'approved_read' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'cancelled_read' => 'TINYINT(1) NOT NULL DEFAULT 0'
    ];

    $columnsAdded = 0;

    foreach ($columns as $column => $definition) {
        $checkColumnSql = "SHOW COLUMNS FROM orders LIKE '$column'";
        $checkColumnStmt = $conn->prepare($checkColumnSql);
        $checkColumnStmt->execute();

        if ($checkColumnStmt->rowCount() == 0) {

            $addColumnSql = "ALTER TABLE orders ADD COLUMN $column $definition";
            $conn->exec($addColumnSql);
            $columnsAdded++;

            echo "<p>Đã thêm cột $column vào bảng orders.</p>";
        } else {
            echo "<p>Cột $column đã tồn tại trong bảng orders.</p>";
        }
    }

    if ($columnsAdded > 0) {
        echo "<p>Đã cập nhật cấu trúc bảng orders thành công!</p>";
    } else {
        echo "<p>Không cần cập nhật cấu trúc bảng orders.</p>";
    }

    $updateSql = "UPDATE orders SET pending_read = 1, approved_read = 1, cancelled_read = 1";
    $conn->exec($updateSql);

    echo "<p>Đã đánh dấu tất cả thông báo đơn hàng hiện tại là đã đọc.</p>";

    echo "<div class='btn-back'>
        <a href='../../index.php?req=orders' class='btn btn-primary'>Quay lại trang quản lý đơn hàng</a>
        <a href='../../index.php' class='btn btn-secondary'>Quay lại trang chủ</a>
    </div>";

    echo "</div></body></html>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Lỗi: " . $e->getMessage() . "</div>";
    echo "<div class='btn-back'>
        <a href='../../index.php' class='btn btn-secondary'>Quay lại trang chủ</a>
    </div>";
    echo "</div></body></html>";
}
?>
