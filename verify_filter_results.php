<?php
// Verify the filtered results are correct

require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "=== Verify Color Filter (Đen) ===\n";
$sql = "SELECT h.tenhanghoa, GROUP_CONCAT(DISTINCT tt.tenThuocTinhHH SEPARATOR ' | ') as attributes
        FROM hanghoa h
        INNER JOIN thuoctinhhh tt ON h.idhanghoa = tt.idhanghoa
        WHERE tt.idThuocTinh = 7 AND CONCAT(',', tt.tenThuocTinhHH, ',') LIKE '%,Đen,%'
        GROUP BY h.idhanghoa
        LIMIT 5";
$stmt = $db->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($results) . " products with color 'Đen':\n";
foreach ($results as $row) {
    echo "  - {$row['tenhanghoa']}: {$row['attributes']}\n";
}

echo "\n=== Verify Size Filter (8GB RAM) ===\n";
$sql = "SELECT h.tenhanghoa, GROUP_CONCAT(DISTINCT tt.tenThuocTinhHH SEPARATOR ' | ') as attributes
        FROM hanghoa h
        INNER JOIN thuoctinhhh tt ON h.idhanghoa = tt.idhanghoa
        WHERE tt.idThuocTinh IN (8, 9, 10) AND CONCAT(',', tt.tenThuocTinhHH, ',') LIKE '%,8GB,%'
        GROUP BY h.idhanghoa
        LIMIT 5";
$stmt = $db->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($results) . " products with size '8GB':\n";
foreach ($results as $row) {
    echo "  - {$row['tenhanghoa']}: {$row['attributes']}\n";
}
