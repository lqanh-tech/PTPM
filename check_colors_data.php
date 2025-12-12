<?php
require_once './lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Kiểm tra dữ liệu màu sắc</h2>";

// Kiểm tra ID thuộc tính màu sắc
$stmt = $db->query("SELECT idThuocTinh, tenThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%'");
$attr = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>1. Thuộc tính màu sắc:</h3>";
echo "<pre>";
print_r($attr);
echo "</pre>";

if ($attr) {
    $colorAttrId = $attr['idThuocTinh'];
    
    // Kiểm tra màu sắc đã có
    echo "<h3>2. Màu sắc hiện có trong database:</h3>";
    $stmt = $db->prepare("SELECT tenThuocTinhHH, COUNT(*) as count FROM thuoctinhhh WHERE idThuocTinh = ? GROUP BY tenThuocTinhHH");
    $stmt->execute([$colorAttrId]);
    $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($colors) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Màu sắc</th><th>Số lượng sản phẩm</th></tr>";
        foreach ($colors as $color) {
            echo "<tr><td>{$color['tenThuocTinhHH']}</td><td>{$color['count']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>Chưa có màu sắc nào!</strong></p>";
        echo "<p>Bạn cần thêm màu sắc cho sản phẩm trước.</p>";
    }
    
    // Test API
    echo "<h3>3. Test API getAvailableColors:</h3>";
    echo "<a href='./lequocanh/administrator/elements_LQA/mod/getAvailableColors.php' target='_blank'>Click để xem JSON response</a>";
}
?>
