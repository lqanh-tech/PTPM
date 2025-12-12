<?php
require_once './lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "=== KIỂM TRA DỮ LIỆU MÀU SẮC ===\n\n";

// Lấy danh sách màu sắc
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

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Tổng số: " . count($products) . " sản phẩm có màu sắc\n\n";

foreach ($products as $p) {
    echo "ID: {$p['idhanghoa']}\n";
    echo "Sản phẩm: {$p['tenhanghoa']}\n";
    echo "Màu: {$p['mau_sac']}\n";
    
    // Kiểm tra xem có nhiều màu trong 1 string không
    if (strpos($p['mau_sac'], ',') !== false) {
        echo "⚠️ CẢNH BÁO: Có nhiều màu trong 1 string!\n";
        echo "   Nên tách thành: " . str_replace(',', ' | ', $p['mau_sac']) . "\n";
    }
    
    echo "---\n";
}

// Thống kê màu
echo "\n=== THỐNG KÊ MÀU SẮC ===\n\n";
$stmt = $db->query("
    SELECT 
        tenThuocTinhHH as mau_sac,
        COUNT(*) as so_luong
    FROM thuoctinhhh
    WHERE idThuocTinh = 26
    GROUP BY tenThuocTinhHH
    ORDER BY so_luong DESC
");

$colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($colors as $c) {
    echo "{$c['mau_sac']}: {$c['so_luong']} sản phẩm\n";
}
