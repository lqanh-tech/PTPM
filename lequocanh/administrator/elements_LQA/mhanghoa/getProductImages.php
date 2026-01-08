<?php
require_once("../mod/database.php");
require_once("../mod/hanghoaCls.php");

if (!isset($_GET['idhanghoa']) || empty($_GET['idhanghoa'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu ID hàng hóa'
    ]);
    exit;
}

$idhanghoa = intval($_GET['idhanghoa']);
$hanghoaObj = new hanghoa();

$images = $hanghoaObj->GetAllImagesForProduct($idhanghoa);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'images' => $images
]);
