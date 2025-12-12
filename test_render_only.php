<?php
session_start();
$_SESSION['cart_weight'] = 2.5;
$_SESSION['cart_total'] = 500000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();

$cartWeight = $_SESSION['cart_weight'] ?? 1.0;
$cartValue = $_SESSION['cart_total'] ?? 0;
$provinceId = $_SESSION['province_id'] ?? 1;
$districtId = $_SESSION['district_id'] ?? 1;

$stmt = $db->query("
    SELECT 
        sm.*,
        COUNT(DISTINCT sf.id) as fee_config_count,
        MIN(sf.base_fee) as min_base_fee,
        MIN(sf.min_order_free_ship) as min_free_ship_threshold
    FROM shipping_methods sm
    LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
    WHERE sm.is_active = 1
    GROUP BY sm.id, sm.code, sm.name, sm.description, sm.delivery_time, sm.price_multiplier, sm.is_active, sm.sort_order, sm.created_at, sm.updated_at
    ORDER BY sm.sort_order DESC
");
$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Loại bỏ duplicate
$uniqueMethods = [];
$seenCodes = [];
foreach ($shippingMethods as $method) {
    if (!in_array($method['code'], $seenCodes)) {
        $uniqueMethods[] = $method;
        $seenCodes[] = $method['code'];
    }
}
$shippingMethods = $uniqueMethods;

// Tính phí
foreach ($shippingMethods as &$method) {
    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
    $stmt->execute([$method['id'], $provinceId, $districtId, $cartWeight, $cartValue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $method['calculated_fee'] = $result['fee'] ?? 0;
    
    $stmt = $db->prepare("
        SELECT base_fee, fee_per_kg, min_order_free_ship
        FROM shipping_fees
        WHERE shipping_method_id = ? AND is_active = 1
        ORDER BY priority DESC
        LIMIT 1
    ");
    $stmt->execute([$method['id']]);
    $feeDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $method['base_fee'] = $feeDetail['base_fee'] ?? 0;
    $method['fee_per_kg'] = $feeDetail['fee_per_kg'] ?? 0;
    $method['min_free_ship'] = $feeDetail['min_order_free_ship'] ?? 0;
    $method['is_free'] = ($method['calculated_fee'] == 0);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Render - NO JAVASCRIPT</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #667eea; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Render - Không có JavaScript</h1>
        
        <div class="info">
            <strong>DEBUG INFO:</strong><br>
            - Số bản ghi từ query: <?php echo count($stmt->fetchAll()); ?><br>
            - Số bản ghi sau loại duplicate: <?php echo count($shippingMethods); ?><br>
            - Số lần foreach sẽ chạy: 1 lần duy nhất
        </div>

        <h2>📋 Phương thức vận chuyển</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Radio</th>
                    <th>Tên</th>
                    <th>Mô tả</th>
                    <th>Thời gian giao</th>
                    <th>Phí hiện tại</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $renderCount = 0;
                foreach ($shippingMethods as $index => $method): 
                    $renderCount++;
                    $isSelected = $index === 0;
                ?>
                <tr>
                    <td><?php echo $renderCount; ?></td>
                    <td>
                        <input type="radio" name="shipping_method" value="<?php echo $method['code']; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                    </td>
                    <td><strong><?php echo htmlspecialchars($method['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($method['description'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($method['delivery_time'] ?? ''); ?></td>
                    <td>
                        <?php if ($method['is_free']): ?>
                            <strong style="color: green;">Miễn phí</strong>
                        <?php else: ?>
                            <strong><?php echo number_format($method['calculated_fee'], 0, ',', '.'); ?>₫</strong>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="info">
            <strong>KẾT QUẢ:</strong><br>
            - Tổng số dòng đã render: <strong><?php echo $renderCount; ?></strong><br>
            - Nếu bạn thấy số này là 4 và không có dòng trùng, nghĩa là PHP code OK<br>
            - Nếu vẫn thấy trùng trên trang thật, vấn đề là do JavaScript hoặc cache
        </div>
    </div>
</body>
</html>
