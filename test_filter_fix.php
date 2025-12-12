<?php
require_once './lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

$hanghoa = new hanghoa();

echo "<h2>Test Filter Màu Sắc</h2>";

// Test 1: Filter màu Trắng
echo "<h3>Test 1: Filter màu Trắng (white)</h3>";
$filters = [
    'min_price' => 0,
    'max_price' => 100000000,
    'colors' => ['white']
];

$products = $hanghoa->filterProducts($filters);
echo "<p>Số sản phẩm tìm thấy: <strong>" . count($products) . "</strong></p>";

if (count($products) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Tên sản phẩm</th><th>Giá</th></tr>";
    foreach ($products as $p) {
        echo "<tr>";
        echo "<td>{$p->idhanghoa}</td>";
        echo "<td>{$p->tenhanghoa}</td>";
        echo "<td>" . number_format($p->giathamkhao) . " VNĐ</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Không tìm thấy sản phẩm nào!</p>";
}

// Test 2: Filter màu Đen
echo "<h3>Test 2: Filter màu Đen (black)</h3>";
$filters = [
    'min_price' => 0,
    'max_price' => 100000000,
    'colors' => ['black']
];

$products = $hanghoa->filterProducts($filters);
echo "<p>Số sản phẩm tìm thấy: <strong>" . count($products) . "</strong></p>";

if (count($products) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Tên sản phẩm</th><th>Giá</th></tr>";
    foreach ($products as $p) {
        echo "<tr>";
        echo "<td>{$p->idhanghoa}</td>";
        echo "<td>{$p->tenhanghoa}</td>";
        echo "<td>" . number_format($p->giathamkhao) . " VNĐ</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 3: Filter nhiều màu
echo "<h3>Test 3: Filter nhiều màu (white, black, purple, yellow)</h3>";
$filters = [
    'min_price' => 0,
    'max_price' => 100000000,
    'colors' => ['white', 'black', 'purple', 'yellow']
];

$products = $hanghoa->filterProducts($filters);
echo "<p>Số sản phẩm tìm thấy: <strong>" . count($products) . "</strong></p>";

if (count($products) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Tên sản phẩm</th><th>Giá</th></tr>";
    foreach ($products as $p) {
        echo "<tr>";
        echo "<td>{$p->idhanghoa}</td>";
        echo "<td>{$p->tenhanghoa}</td>";
        echo "<td>" . number_format($p->giathamkhao) . " VNĐ</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Kiểm tra dữ liệu màu trong database
echo "<h3>Dữ liệu màu sắc trong database:</h3>";
require_once './lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();

$stmt = $db->query("
    SELECT 
        h.idhanghoa,
        h.tenhanghoa,
        tt.tenThuocTinhHH as mau_sac
    FROM thuoctinhhh tt
    JOIN hanghoa h ON tt.idhanghoa = h.idhanghoa
    WHERE tt.idThuocTinh = 26
    ORDER BY h.tenhanghoa
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($data) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID Sản phẩm</th><th>Tên sản phẩm</th><th>Màu sắc</th></tr>";
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>{$row['idhanghoa']}</td>";
        echo "<td>{$row['tenhanghoa']}</td>";
        echo "<td>{$row['mau_sac']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
