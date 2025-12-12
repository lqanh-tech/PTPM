<?php
/**
 * Script tự động thêm màu sắc cho tất cả sản phẩm
 * Mỗi sản phẩm sẽ được gán ngẫu nhiên 1-3 màu
 */

require_once './lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🎨 Thêm màu sắc cho tất cả sản phẩm</h1>";

// Lấy ID thuộc tính màu sắc
$stmt = $db->query("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' LIMIT 1");
$colorAttr = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$colorAttr) {
    echo "<p style='color: red;'>❌ Không tìm thấy thuộc tính màu sắc!</p>";
    exit;
}

$colorAttrId = $colorAttr['idThuocTinh'];
echo "<p>✅ ID thuộc tính màu sắc: <strong>$colorAttrId</strong></p>";

// Danh sách màu sắc
$colors = [
    'Đen',
    'Trắng',
    'Xanh dương',
    'Đỏ',
    'Vàng',
    'Tím',
    'Hồng',
    'Xám',
    'Bạc',
    'Cam',
    'Xanh lá',
    'Nâu'
];

echo "<p>📋 Danh sách màu: " . implode(', ', $colors) . "</p>";

// Lấy tất cả sản phẩm
$stmt = $db->query("SELECT idhanghoa, tenhanghoa FROM hanghoa ORDER BY idhanghoa");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Tổng số sản phẩm: " . count($products) . "</h2>";

// Xóa màu cũ (nếu có)
echo "<h3>1. Xóa màu sắc cũ...</h3>";
$deleteStmt = $db->prepare("DELETE FROM thuoctinhhh WHERE idThuocTinh = ?");
$deleteStmt->execute([$colorAttrId]);
echo "<p>✅ Đã xóa " . $deleteStmt->rowCount() . " records cũ</p>";

// Thêm màu mới
echo "<h3>2. Thêm màu sắc mới...</h3>";

$insertStmt = $db->prepare("
    INSERT INTO thuoctinhhh (idhanghoa, idThuocTinh, tenThuocTinhHH, ghiChu)
    VALUES (?, ?, ?, ?)
");

$totalAdded = 0;
$productColors = [];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #007bff; color: white;'>";
echo "<th>ID</th><th>Sản phẩm</th><th>Màu đã thêm</th><th>Số màu</th>";
echo "</tr>";

foreach ($products as $product) {
    $productId = $product['idhanghoa'];
    $productName = $product['tenhanghoa'];
    
    // Chọn ngẫu nhiên 1-3 màu cho mỗi sản phẩm
    $numColors = rand(1, 3);
    $selectedColors = array_rand(array_flip($colors), $numColors);
    
    // Nếu chỉ chọn 1 màu, array_rand trả về string, cần chuyển thành array
    if (!is_array($selectedColors)) {
        $selectedColors = [$selectedColors];
    }
    
    $addedColors = [];
    
    foreach ($selectedColors as $color) {
        try {
            $insertStmt->execute([
                $productId,
                $colorAttrId,
                $color,
                'Tự động thêm'
            ]);
            $addedColors[] = $color;
            $totalAdded++;
        } catch (Exception $e) {
            // Bỏ qua lỗi duplicate
        }
    }
    
    $productColors[$productId] = $addedColors;
    
    // Hiển thị
    echo "<tr>";
    echo "<td>$productId</td>";
    echo "<td>" . htmlspecialchars($productName) . "</td>";
    echo "<td>" . implode(', ', $addedColors) . "</td>";
    echo "<td style='text-align: center;'>" . count($addedColors) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>3. Tóm tắt</h3>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; border-left: 4px solid #28a745;'>";
echo "<p>✅ <strong>Hoàn thành!</strong></p>";
echo "<p>📊 Tổng số sản phẩm: <strong>" . count($products) . "</strong></p>";
echo "<p>🎨 Tổng số màu đã thêm: <strong>$totalAdded</strong></p>";
echo "<p>📈 Trung bình: <strong>" . round($totalAdded / count($products), 1) . "</strong> màu/sản phẩm</p>";
echo "</div>";

// Thống kê màu
echo "<h3>4. Thống kê theo màu</h3>";
$stmt = $db->prepare("
    SELECT tenThuocTinhHH as mau_sac, COUNT(*) as so_luong
    FROM thuoctinhhh
    WHERE idThuocTinh = ?
    GROUP BY tenThuocTinhHH
    ORDER BY so_luong DESC
");
$stmt->execute([$colorAttrId]);
$colorStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #007bff; color: white;'>";
echo "<th>Màu sắc</th><th>Số sản phẩm</th><th>%</th>";
echo "</tr>";

foreach ($colorStats as $stat) {
    $percentage = round(($stat['so_luong'] / count($products)) * 100, 1);
    echo "<tr>";
    echo "<td><strong>{$stat['mau_sac']}</strong></td>";
    echo "<td style='text-align: center;'>{$stat['so_luong']}</td>";
    echo "<td style='text-align: center;'>{$percentage}%</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>5. Kiểm tra bộ lọc</h3>";
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 5px; border-left: 4px solid #17a2b8;'>";
echo "<p>🔗 <a href='/lequocanh/' target='_blank' style='font-size: 18px; font-weight: bold;'>Mở trang sản phẩm để test bộ lọc màu</a></p>";
echo "<p>💡 Bây giờ bạn có thể chọn bất kỳ màu nào trong bộ lọc và sẽ thấy sản phẩm tương ứng!</p>";
echo "</div>";

echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
    h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
    h2, h3 { color: #007bff; }
    table { background: white; margin: 20px 0; }
    tr:nth-child(even) { background: #f8f9fa; }
    tr:hover { background: #e7f3ff; }
</style>";
?>
