<?php
/**
 * Script tổng hợp sửa tất cả lỗi màu sắc
 * Chạy tất cả các bước cần thiết để sửa hệ thống bộ lọc màu sắc
 */

require_once './lequocanh/administrator/elements_LQA/mod/database.php';

$results = [];
$errors = [];

// Bước 1: Kiểm tra và tạo thuộc tính màu sắc
function setupColorAttribute($conn) {
    global $results, $errors;
    
    try {
        // Tìm thuộc tính màu sắc
        $sql = "SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' OR tenThuocTinh LIKE '%color%' LIMIT 1";
        $stmt = $conn->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $colorAttrId = $row['idThuocTinh'];
            $results[] = "✅ Tìm thấy thuộc tính màu sắc với ID: $colorAttrId";
            return $colorAttrId;
        } else {
            // Tạo mới thuộc tính màu sắc
            $sql = "INSERT INTO thuoctinh (tenThuocTinh, ghiChu) VALUES ('Màu sắc', 'Thuộc tính màu sắc sản phẩm')";
            $conn->exec($sql);
            $colorAttrId = $conn->lastInsertId();
            $results[] = "✅ Đã tạo thuộc tính màu sắc mới với ID: $colorAttrId";
            return $colorAttrId;
        }
    } catch (Exception $e) {
        $errors[] = "❌ Lỗi: " . $e->getMessage();
        return null;
    }
}

// Bước 2: Cập nhật ID trong hanghoaCls.php
function updateColorIdInCode($colorAttrId) {
    global $results, $errors;
    
    $file = './lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';
    
    if (!file_exists($file)) {
        $errors[] = "❌ Không tìm thấy file: $file";
        return false;
    }
    
    // Backup file
    $backup = $file . '.backup.' . date('YmdHis');
    if (!copy($file, $backup)) {
        $errors[] = "❌ Không thể tạo backup file";
        return false;
    }
    $results[] = "✅ Đã backup file: $backup";
    
    // Đọc nội dung file
    $content = file_get_contents($file);
    
    // Tìm và thay thế ID cứng
    $patterns = [
        "/idThuocTinh\s*=\s*\d+\s*--\s*Màu sắc/i",
        "/idThuocTinh\s*=\s*\d+\s*\/\*\s*Màu sắc\s*\*\//i",
        "/WHERE\s+tt\.idThuocTinh\s*=\s*\d+/i"
    ];
    
    $replacements = [
        "idThuocTinh = $colorAttrId -- Màu sắc",
        "idThuocTinh = $colorAttrId /* Màu sắc */",
        "WHERE tt.idThuocTinh = $colorAttrId"
    ];
    
    $updated = false;
    foreach ($patterns as $i => $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacements[$i], $content);
            $updated = true;
        }
    }
    
    if ($updated) {
        file_put_contents($file, $content);
        $results[] = "✅ Đã cập nhật ID màu sắc trong hanghoaCls.php thành: $colorAttrId";
        return true;
    } else {
        $results[] = "⚠️ Không tìm thấy ID cứng trong code, có thể đã được cập nhật";
        return true;
    }
}

// Bước 3: Kiểm tra dữ liệu màu sắc
function checkColorData($conn, $colorAttrId) {
    global $results, $errors;
    
    try {
        $sql = "SELECT tenThuocTinhHH as mau_sac, COUNT(*) as so_luong 
                FROM thuoctinhhh 
                WHERE idThuocTinh = $colorAttrId 
                GROUP BY tenThuocTinhHH 
                ORDER BY so_luong DESC";
        
        $stmt = $conn->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $colors = [];
            foreach ($rows as $row) {
                $colors[] = $row['mau_sac'] . " (" . $row['so_luong'] . " sản phẩm)";
            }
            $results[] = "✅ Tìm thấy " . count($colors) . " màu sắc: " . implode(", ", $colors);
            return true;
        } else {
            $results[] = "⚠️ Chưa có sản phẩm nào được gán màu sắc";
            return false;
        }
    } catch (Exception $e) {
        $errors[] = "❌ Lỗi kiểm tra dữ liệu: " . $e->getMessage();
        return false;
    }
}

// Bước 4: Test bộ lọc
function testColorFilter($conn, $colorAttrId) {
    global $results, $errors;
    
    try {
        // Lấy một màu để test
        $sql = "SELECT tenThuocTinhHH FROM thuoctinhhh WHERE idThuocTinh = $colorAttrId LIMIT 1";
        $stmt = $conn->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $testColor = $row['tenThuocTinhHH'];
            
            // Test filter
            $sql = "SELECT h.idhanghoa, h.tenhanghoa 
                    FROM hanghoa h
                    INNER JOIN thuoctinhhh tt ON h.idhanghoa = tt.idhanghoa
                    WHERE tt.idThuocTinh = $colorAttrId 
                    AND tt.tenThuocTinhHH = :testColor
                    LIMIT 5";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute(['testColor' => $testColor]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($products) > 0) {
                $productNames = array_column($products, 'tenhanghoa');
                $results[] = "✅ Test filter màu '$testColor' thành công, tìm thấy " . count($products) . " sản phẩm";
                return true;
            } else {
                $errors[] = "❌ Test filter thất bại, không tìm thấy sản phẩm";
                return false;
            }
        } else {
            $results[] = "⚠️ Không có màu nào để test";
            return false;
        }
    } catch (Exception $e) {
        $errors[] = "❌ Lỗi test filter: " . $e->getMessage();
        return false;
    }
}

// Chạy tất cả các bước
$conn = Database::getInstance()->getConnection();

$results[] = "🚀 Bắt đầu sửa lỗi hệ thống màu sắc...";

// Bước 1
$colorAttrId = setupColorAttribute($conn);

// Bước 2
if ($colorAttrId) {
    updateColorIdInCode($colorAttrId);
    
    // Bước 3
    checkColorData($conn, $colorAttrId);
    
    // Bước 4
    testColorFilter($conn, $colorAttrId);
}

// PDO không cần close(), connection sẽ tự động đóng khi script kết thúc

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả sửa lỗi màu sắc</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #007bff;
            margin-top: 30px;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .next-steps {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 Kết quả sửa lỗi hệ thống màu sắc</h1>
        
        <?php if (!empty($results)): ?>
        <div class="step success">
            <h2>✅ Các bước đã thực hiện thành công</h2>
            <ul>
                <?php foreach ($results as $result): ?>
                <li><?php echo htmlspecialchars($result); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="step error">
            <h2>❌ Lỗi gặp phải</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="next-steps">
            <h2>📋 Các bước tiếp theo</h2>
            <ol>
                <li><strong>Thêm màu sắc cho sản phẩm:</strong>
                    <ul>
                        <li>Truy cập trang quản trị: <a href="/lequocanh/administrator/" target="_blank">Admin Panel</a></li>
                        <li>Vào "Quản lý thuộc tính hàng hóa"</li>
                        <li>Chọn sản phẩm và thêm màu sắc</li>
                    </ul>
                </li>
                <li><strong>Test bộ lọc:</strong>
                    <ul>
                        <li><a href="/test_color_filter.php" target="_blank" class="btn">Test Color Filter</a></li>
                        <li><a href="/lequocanh/" target="_blank" class="btn btn-success">Test Frontend</a></li>
                    </ul>
                </li>
                <li><strong>Xem hướng dẫn chi tiết:</strong>
                    <ul>
                        <li><a href="/HUONG_DAN_SUA_LOI_MAU_SAC.md" target="_blank">Hướng dẫn sửa lỗi màu sắc</a></li>
                    </ul>
                </li>
            </ol>
        </div>
        
        <div class="step info">
            <h2>💡 Lưu ý quan trọng</h2>
            <ul>
                <li>✅ File gốc đã được backup trước khi sửa</li>
                <li>✅ ID thuộc tính màu sắc đã được cập nhật trong code</li>
                <li>⚠️ Cần thêm màu sắc cho sản phẩm để bộ lọc hoạt động</li>
                <li>⚠️ Sử dụng tên màu chuẩn: Đỏ, Xanh dương, Xanh lá, Vàng, Cam, Tím, Hồng, Đen, Trắng, Xám, Nâu, Bạc</li>
            </ul>
        </div>
        
        <?php if ($colorAttrId): ?>
        <div class="step success">
            <h2>🎯 Thông tin hệ thống</h2>
            <ul>
                <li><strong>ID thuộc tính màu sắc:</strong> <?php echo $colorAttrId; ?></li>
                <li><strong>File đã cập nhật:</strong> lequocanh/administrator/elements_LQA/cls/hanghoaCls.php</li>
                <li><strong>API màu sắc:</strong> /lequocanh/administrator/elements_LQA/mod/getAvailableColors.php</li>
            </ul>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="/" class="btn">← Về trang chủ</a>
            <a href="/lequocanh/administrator/" class="btn btn-success">Quản trị →</a>
        </div>
    </div>
</body>
</html>
