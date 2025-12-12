<?php
/**
 * Đồng bộ dữ liệu phương thức vận chuyển giữa Admin và Frontend
 * Kiểm tra và sửa lỗi không khớp
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Đồng bộ dữ liệu Admin - Frontend</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; background: #ecf0f1; padding: 10px; border-radius: 5px; }
        .section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .success { color: #27ae60; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        .badge { padding: 5px 10px; border-radius: 3px; color: white; font-size: 12px; }
        .badge-success { background: #27ae60; }
        .badge-warning { background: #f39c12; }
        .badge-danger { background: #e74c3c; }
        .badge-info { background: #3498db; }
        .diff { background: #fff3cd; padding: 10px; border-left: 4px solid #f39c12; margin: 10px 0; }
        .fix { background: #d4edda; padding: 10px; border-left: 4px solid #28a745; margin: 10px 0; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔄 ĐỒNG BỘ DỮ LIỆU ADMIN - FRONTEND</h1>
<p><strong>Ngày thực hiện:</strong> " . date('d/m/Y H:i:s') . "</p>
";

// BƯỚC 1: Kiểm tra dữ liệu hiện tại
echo "<h2>📊 BƯỚC 1: Kiểm tra dữ liệu hiện tại</h2>";
echo "<div class='section'>";

// Lấy dữ liệu từ shipping_methods
$stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order DESC");
$adminMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Phương thức vận chuyển trong Admin:</h3>";
echo "<table>";
echo "<tr><th>ID</th><th>Mã</th><th>Tên</th><th>Mô tả</th><th>Thời gian giao</th><th>Hệ số giá</th><th>Trạng thái</th></tr>";
foreach ($adminMethods as $method) {
    $statusBadge = $method['is_active'] ? 'badge-success' : 'badge-danger';
    $statusText = $method['is_active'] ? 'Hoạt động' : 'Tắt';
    echo "<tr>";
    echo "<td>{$method['id']}</td>";
    echo "<td><strong>{$method['code']}</strong></td>";
    echo "<td>{$method['name']}</td>";
    echo "<td>{$method['description']}</td>";
    echo "<td>{$method['delivery_time']}</td>";
    echo "<td>{$method['price_multiplier']}x</td>";
    echo "<td><span class='badge {$statusBadge}'>{$statusText}</span></td>";
    echo "</tr>";
}
echo "</table>";

// Kiểm tra view v_shipping_methods_with_fees
echo "<h3>Kiểm tra View v_shipping_methods_with_fees:</h3>";
try {
    $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
    $viewMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><span class='success'>✅ View tồn tại và hoạt động</span></p>";
    echo "<p><strong>Số phương thức trong view:</strong> " . count($viewMethods) . "</p>";
    
    if (count($viewMethods) != count(array_filter($adminMethods, fn($m) => $m['is_active']))) {
        echo "<div class='diff'>";
        echo "<strong>⚠️ CẢNH BÁO:</strong> Số lượng phương thức trong view (" . count($viewMethods) . ") ";
        echo "khác với số phương thức active trong bảng (" . count(array_filter($adminMethods, fn($m) => $m['is_active'])) . ")";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p><span class='error'>❌ View không tồn tại hoặc có lỗi: " . $e->getMessage() . "</span></p>";
}

echo "</div>";

// BƯỚC 2: Kiểm tra cấu hình phí
echo "<h2>💰 BƯỚC 2: Kiểm tra cấu hình phí</h2>";
echo "<div class='section'>";

foreach ($adminMethods as $method) {
    if (!$method['is_active']) continue;
    
    echo "<h3>Phương thức: {$method['name']} ({$method['code']})</h3>";
    
    $stmt = $db->prepare("
        SELECT sf.*, p.name as province_name, d.name as district_name
        FROM shipping_fees sf
        LEFT JOIN provinces p ON sf.province_id = p.id
        LEFT JOIN districts d ON sf.district_id = d.id
        WHERE sf.shipping_method_id = ? AND sf.is_active = 1
        ORDER BY sf.priority DESC
    ");
    $stmt->execute([$method['id']]);
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fees) == 0) {
        echo "<div class='diff'>";
        echo "<strong>⚠️ CẢNH BÁO:</strong> Phương thức này không có cấu hình phí nào!";
        echo "</div>";
        continue;
    }
    
    echo "<table>";
    echo "<tr><th>Tên</th><th>Khu vực</th><th>Phí cơ bản</th><th>Phí/kg</th><th>Miễn phí từ</th><th>Ưu tiên</th></tr>";
    foreach ($fees as $fee) {
        $area = $fee['province_name'] ?? 'Toàn quốc';
        if ($fee['district_name']) $area .= " - " . $fee['district_name'];
        
        echo "<tr>";
        echo "<td>{$fee['name']}</td>";
        echo "<td>{$area}</td>";
        echo "<td>" . number_format($fee['base_fee'], 0, ',', '.') . " đ</td>";
        echo "<td>" . number_format($fee['fee_per_kg'], 0, ',', '.') . " đ</td>";
        echo "<td>" . ($fee['min_order_free_ship'] ? number_format($fee['min_order_free_ship'], 0, ',', '.') . " đ" : "Không") . "</td>";
        echo "<td>{$fee['priority']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "</div>";

// BƯỚC 3: So sánh dữ liệu Admin vs Frontend
echo "<h2>🔍 BƯỚC 3: So sánh dữ liệu Admin vs Frontend</h2>";
echo "<div class='section'>";

echo "<h3>Dữ liệu hiển thị cho người dùng (Frontend):</h3>";

// Giả lập dữ liệu frontend
$cartWeight = 2.5;
$cartValue = 500000;
$provinceId = 1;
$districtId = 1;

echo "<p><strong>Giỏ hàng test:</strong> Trọng lượng {$cartWeight}kg, Giá trị " . number_format($cartValue, 0, ',', '.') . " đ</p>";

$stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
$frontendMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>Mã</th><th>Tên (Frontend)</th><th>Tên (Admin)</th><th>Phí tính toán</th><th>Khớp?</th></tr>";

$mismatches = [];

foreach ($frontendMethods as $fMethod) {
    // Tìm method tương ứng trong admin
    $aMethod = array_filter($adminMethods, fn($m) => $m['code'] === $fMethod['code']);
    $aMethod = reset($aMethod);
    
    // Tính phí
    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
    $stmt->execute([$fMethod['id'], $provinceId, $districtId, $cartWeight, $cartValue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $calculatedFee = $result['fee'] ?? 0;
    
    $nameMatch = ($fMethod['name'] === $aMethod['name']);
    $matchBadge = $nameMatch ? 'badge-success' : 'badge-warning';
    $matchText = $nameMatch ? '✅ Khớp' : '⚠️ Khác';
    
    if (!$nameMatch) {
        $mismatches[] = [
            'code' => $fMethod['code'],
            'frontend_name' => $fMethod['name'],
            'admin_name' => $aMethod['name']
        ];
    }
    
    echo "<tr>";
    echo "<td><strong>{$fMethod['code']}</strong></td>";
    echo "<td>{$fMethod['name']}</td>";
    echo "<td>{$aMethod['name']}</td>";
    echo "<td>" . number_format($calculatedFee, 0, ',', '.') . " đ</td>";
    echo "<td><span class='badge {$matchBadge}'>{$matchText}</span></td>";
    echo "</tr>";
}
echo "</table>";

if (count($mismatches) > 0) {
    echo "<div class='diff'>";
    echo "<h4>⚠️ Phát hiện " . count($mismatches) . " sự khác biệt:</h4>";
    echo "<ul>";
    foreach ($mismatches as $mm) {
        echo "<li><strong>{$mm['code']}:</strong> Frontend hiển thị '{$mm['frontend_name']}' nhưng Admin là '{$mm['admin_name']}'</li>";
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='fix'>";
    echo "<p><strong>✅ Tất cả dữ liệu đã khớp giữa Admin và Frontend!</strong></p>";
    echo "</div>";
}

echo "</div>";

// BƯỚC 4: Kiểm tra các vấn đề phổ biến
echo "<h2>🔧 BƯỚC 4: Kiểm tra các vấn đề phổ biến</h2>";
echo "<div class='section'>";

$issues = [];

// Issue 1: Phương thức không có cấu hình phí
foreach ($adminMethods as $method) {
    if (!$method['is_active']) continue;
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM shipping_fees WHERE shipping_method_id = ? AND is_active = 1");
    $stmt->execute([$method['id']]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $issues[] = [
            'type' => 'no_fee_config',
            'severity' => 'high',
            'message' => "Phương thức '{$method['name']}' ({$method['code']}) không có cấu hình phí",
            'fix' => "Thêm cấu hình phí trong bảng shipping_fees"
        ];
    }
}

// Issue 2: Phương thức có nhiều cấu hình phí cùng priority
foreach ($adminMethods as $method) {
    if (!$method['is_active']) continue;
    
    $stmt = $db->prepare("
        SELECT priority, COUNT(*) as count 
        FROM shipping_fees 
        WHERE shipping_method_id = ? AND is_active = 1 
        GROUP BY priority 
        HAVING count > 1
    ");
    $stmt->execute([$method['id']]);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($duplicates as $dup) {
        $issues[] = [
            'type' => 'duplicate_priority',
            'severity' => 'medium',
            'message' => "Phương thức '{$method['name']}' có {$dup['count']} cấu hình phí cùng priority {$dup['priority']}",
            'fix' => "Điều chỉnh priority để đảm bảo thứ tự ưu tiên rõ ràng"
        ];
    }
}

// Issue 3: Kiểm tra function calculate_shipping_fee
try {
    $stmt = $db->query("SHOW FUNCTION STATUS WHERE Name = 'calculate_shipping_fee'");
    $functionExists = $stmt->rowCount() > 0;
    
    if (!$functionExists) {
        $issues[] = [
            'type' => 'missing_function',
            'severity' => 'critical',
            'message' => "Function calculate_shipping_fee không tồn tại",
            'fix' => "Chạy script tạo function từ DB/shipping_system_schema.sql"
        ];
    }
} catch (Exception $e) {
    $issues[] = [
        'type' => 'function_error',
        'severity' => 'critical',
        'message' => "Lỗi khi kiểm tra function: " . $e->getMessage(),
        'fix' => "Kiểm tra quyền truy cập database"
    ];
}

// Issue 4: Kiểm tra view v_shipping_methods_with_fees
try {
    $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $viewExists = in_array('v_shipping_methods_with_fees', $views);
    
    if (!$viewExists) {
        $issues[] = [
            'type' => 'missing_view',
            'severity' => 'high',
            'message' => "View v_shipping_methods_with_fees không tồn tại",
            'fix' => "Chạy script create_shipping_view.php"
        ];
    }
} catch (Exception $e) {
    // Ignore error
}

// Hiển thị issues
if (count($issues) > 0) {
    echo "<h3>⚠️ Phát hiện " . count($issues) . " vấn đề:</h3>";
    echo "<table>";
    echo "<tr><th>Mức độ</th><th>Loại</th><th>Vấn đề</th><th>Cách khắc phục</th></tr>";
    
    foreach ($issues as $issue) {
        $severityBadge = match($issue['severity']) {
            'critical' => 'badge-danger',
            'high' => 'badge-warning',
            'medium' => 'badge-info',
            default => 'badge-success'
        };
        
        $severityText = match($issue['severity']) {
            'critical' => 'NGHIÊM TRỌNG',
            'high' => 'CAO',
            'medium' => 'TRUNG BÌNH',
            default => 'THẤP'
        };
        
        echo "<tr>";
        echo "<td><span class='badge {$severityBadge}'>{$severityText}</span></td>";
        echo "<td>{$issue['type']}</td>";
        echo "<td>{$issue['message']}</td>";
        echo "<td>{$issue['fix']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='fix'>";
    echo "<p><strong>✅ Không phát hiện vấn đề nào!</strong></p>";
    echo "</div>";
}

echo "</div>";

// BƯỚC 5: Đề xuất sửa lỗi
echo "<h2>🛠️ BƯỚC 5: Đề xuất sửa lỗi</h2>";
echo "<div class='section'>";

if (count($issues) > 0 || count($mismatches) > 0) {
    echo "<h3>Các bước khắc phục:</h3>";
    echo "<ol>";
    
    if (count($mismatches) > 0) {
        echo "<li><strong>Đồng bộ tên phương thức:</strong>";
        echo "<ul>";
        foreach ($mismatches as $mm) {
            echo "<li>Cập nhật tên '{$mm['frontend_name']}' thành '{$mm['admin_name']}' hoặc ngược lại</li>";
        }
        echo "</ul>";
        echo "</li>";
    }
    
    foreach ($issues as $issue) {
        echo "<li><strong>{$issue['message']}:</strong> {$issue['fix']}</li>";
    }
    
    echo "</ol>";
    
    echo "<h3>Script tự động sửa lỗi:</h3>";
    echo "<p>Chạy các lệnh sau để tự động sửa một số lỗi:</p>";
    echo "<pre style='background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px;'>";
    echo "# Tạo lại view\n";
    echo "docker exec php_ws-web-1 php /var/www/html/create_shipping_view.php\n\n";
    echo "# Kiểm tra lại\n";
    echo "docker exec php_ws-web-1 php /var/www/html/sync_shipping_data_admin_frontend.php\n";
    echo "</pre>";
} else {
    echo "<div class='fix'>";
    echo "<p><strong>✅ Hệ thống đã đồng bộ hoàn hảo! Không cần sửa lỗi.</strong></p>";
    echo "</div>";
}

echo "</div>";

// TỔNG KẾT
echo "<h2>📋 TỔNG KẾT</h2>";
echo "<div class='section'>";

$totalChecks = 5;
$passedChecks = 0;

if (count($adminMethods) > 0) $passedChecks++;
if (count($viewMethods) > 0) $passedChecks++;
if (count($mismatches) == 0) $passedChecks++;
if (count($issues) == 0) $passedChecks++;
if (count($frontendMethods) == count(array_filter($adminMethods, fn($m) => $m['is_active']))) $passedChecks++;

$percentage = round(($passedChecks / $totalChecks) * 100, 1);
$statusClass = $percentage == 100 ? 'success' : ($percentage >= 80 ? 'warning' : 'error');

echo "<div style='padding: 20px; background: #ecf0f1; border-radius: 5px; text-align: center;'>";
echo "<p style='font-size: 24px; margin: 10px 0;'><strong>Tổng số kiểm tra:</strong> {$totalChecks}</p>";
echo "<p style='font-size: 24px; margin: 10px 0;'><strong>Passed:</strong> <span class='success'>{$passedChecks}</span></p>";
echo "<p style='font-size: 24px; margin: 10px 0;'><strong>Issues:</strong> <span class='error'>" . count($issues) . "</span></p>";
echo "<p style='font-size: 24px; margin: 10px 0;'><strong>Mismatches:</strong> <span class='warning'>" . count($mismatches) . "</span></p>";
echo "<p style='font-size: 36px; margin: 20px 0;'><strong class='{$statusClass}'>{$percentage}%</strong></p>";

if ($percentage == 100) {
    echo "<p style='font-size: 20px; color: #27ae60;'><strong>🎉 HỆ THỐNG HOÀN TOÀN ĐỒNG BỘ!</strong></p>";
} elseif ($percentage >= 80) {
    echo "<p style='font-size: 20px; color: #f39c12;'><strong>⚠️ CẦN KIỂM TRA MỘT SỐ ĐIỂM</strong></p>";
} else {
    echo "<p style='font-size: 20px; color: #e74c3c;'><strong>❌ CẦN KHẮC PHỤC CÁC VẤN ĐỀ</strong></p>";
}

echo "</div>";
echo "</div>";

echo "</div>
</body>
</html>";
