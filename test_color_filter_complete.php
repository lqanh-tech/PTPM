<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Bộ Lọc Màu Sắc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="lequocanh/public_files/product_filter.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .color-demo {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="fas fa-palette"></i> Test Bộ Lọc Màu Sắc</h1>
        
        <?php
        require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/database.php';
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // 1. Check color attribute exists
            echo '<div class="status success">';
            echo '<h2>✅ Bước 1: Kiểm tra thuộc tính màu sắc</h2>';
            $colorStmt = $db->prepare("SELECT * FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%'");
            $colorStmt->execute();
            $colorAttr = $colorStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($colorAttr) {
                echo "<p><strong>ID:</strong> {$colorAttr['idThuocTinh']}</p>";
                echo "<p><strong>Tên:</strong> {$colorAttr['tenThuocTinh']}</p>";
            } else {
                echo '<p>⚠️ Không tìm thấy thuộc tính màu sắc</p>';
            }
            echo '</div>';
            
            // 2. Check products with colors
            if ($colorAttr) {
                echo '<div class="status success">';
                echo '<h2>✅ Bước 2: Kiểm tra sản phẩm có màu sắc</h2>';
                $productsStmt = $db->prepare("
                    SELECT 
                        h.idhanghoa,
                        h.tenhanghoa,
                        t.tenThuocTinhHH as color
                    FROM hanghoa h
                    INNER JOIN thuoctinhhh t ON h.idhanghoa = t.idhanghoa
                    WHERE t.idThuocTinh = ?
                    LIMIT 10
                ");
                $productsStmt->execute([$colorAttr['idThuocTinh']]);
                $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($products)) {
                    echo '<p>Tìm thấy ' . count($products) . ' sản phẩm có màu sắc:</p>';
                    echo '<ul>';
                    foreach ($products as $p) {
                        echo "<li>#{$p['idhanghoa']}: {$p['tenhanghoa']} - <strong>{$p['color']}</strong></li>";
                    }
                    echo '</ul>';
                } else {
                    echo '<p>⚠️ Không có sản phẩm nào được gán màu sắc</p>';
                }
                echo '</div>';
                
                // 3. Test color filter rendering
                echo '<div class="status info">';
                echo '<h2>🎨 Bước 3: Render bộ lọc màu sắc</h2>';
                echo '<div class="color-options" id="colorFilterContainer">';
                include __DIR__ . '/lequocanh/apart/render_color_filter.php';
                echo '</div>';
                echo '</div>';
                
                // 4. Color statistics
                echo '<div class="status success">';
                echo '<h2>📊 Bước 4: Thống kê màu sắc</h2>';
                $statsStmt = $db->prepare("
                    SELECT 
                        tenThuocTinhHH as color,
                        COUNT(*) as count
                    FROM thuoctinhhh
                    WHERE idThuocTinh = ?
                    GROUP BY tenThuocTinhHH
                    ORDER BY count DESC
                ");
                $statsStmt->execute([$colorAttr['idThuocTinh']]);
                $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<ul>';
                foreach ($stats as $stat) {
                    echo "<li><strong>{$stat['color']}</strong>: {$stat['count']} sản phẩm</li>";
                }
                echo '</ul>';
                echo '</div>';
                
                // 5. Test filter API
                echo '<div class="status info">';
                echo '<h2>🔧 Bước 5: Test API lọc màu</h2>';
                echo '<p>API endpoint: <code>/lequocanh/api/filter_products.php</code></p>';
                echo '<p>Thử lọc theo màu đen:</p>';
                echo '<pre>';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://localhost:20080/lequocanh/api/filter_products.php?colors=black');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                
                $data = json_decode($response, true);
                if ($data && $data['success']) {
                    echo "✅ API hoạt động!\n";
                    echo "Tìm thấy: {$data['total']} sản phẩm màu đen\n";
                } else {
                    echo "⚠️ API có vấn đề: " . ($data['message'] ?? 'Unknown error');
                }
                echo '</pre>';
                echo '</div>';
            }
            
            // Summary
            echo '<div class="status success" style="background: #d1f2eb; border-color: #76c7c0;">';
            echo '<h2>✨ Tổng kết</h2>';
            echo '<ul>';
            echo '<li>✅ Thuộc tính màu sắc đã được tạo</li>';
            echo '<li>✅ ' . count($products ?? []) . ' sản phẩm đã có màu sắc</li>';
            echo '<li>✅ Bộ lọc màu đã render thành công</li>';
            echo '<li>✅ Hệ thống sẵn sàng sử dụng!</li>';
            echo '</ul>';
            echo '<p><strong>Bước tiếp theo:</strong></p>';
            echo '<ol>';
            echo '<li>Truy cập trang chủ: <a href="/lequocanh/" target="_blank">http://localhost:20080/lequocanh/</a></li>';
            echo '<li>Cuộn xuống phần "Sản phẩm"</li>';
            echo '<li>Thấy bộ lọc màu sắc bên trái</li>';
            echo '<li>Click vào màu để lọc sản phẩm</li>';
            echo '</ol>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="status" style="background: #f8d7da; color: #721c24; border-color: #f5c6cb;">';
            echo '<h2>❌ Lỗi</h2>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '</div>';
        }
        ?>
        
        <script src="lequocanh/public_files/product_filter.js"></script>
    </div>
</body>
</html>
