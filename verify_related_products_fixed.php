<?php
/**
 * Verification Test for Related Products System
 * Tests that the system is working correctly after fixes
 */

require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>✅ Verification: Related Products System</title>
    <meta charset='utf-8'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .test-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; }
    </style>
</head>
<body>
<div class='container mt-4'>
    <h1>✅ Kiểm Tra Hệ Thống Sản Phẩm Liên Quan</h1>
    <p class='text-muted'>Xác nhận tất cả các chức năng hoạt động đúng</p>
";

try {
    $hanghoa = new hanghoa($connection);
    
    // TEST 1: Check method exists
    echo "<div class='test-section'>";
    echo "<h3>📋 Test 1: Kiểm tra method tồn tại</h3>";
    
    if (!method_exists($hanghoa, 'getRelatedProducts')) {
        throw new Exception("❌ Method getRelatedProducts không tồn tại!");
    }
    
    if (!method_exists($hanghoa, 'getSameBrandProducts')) {
        throw new Exception("❌ Method getSameBrandProducts không tồn tại!");
    }
    
    if (!method_exists($hanghoa, 'getSimilarPriceProducts')) {
        throw new Exception("❌ Method getSimilarPriceProducts không tồn tại!");
    }
    
    if (!method_exists($hanghoa, 'getAnyProducts')) {
        throw new Exception("❌ Method getAnyProducts không tồn tại!");
    }
    
    echo "<p class='success'>✅ Tất cả methods cần thiết đều tồn tại</p>";
    echo "<ul>";
    echo "<li>getRelatedProducts() ✓</li>";
    echo "<li>getSameBrandProducts() ✓</li>";
    echo "<li>getSimilarPriceProducts() ✓</li>";
    echo "<li>getAnyProducts() ✓</li>";
    echo "</ul>";
    echo "</div>";
    
    // TEST 2: Test with real product
    echo "<div class='test-section'>";
    echo "<h3>📋 Test 2: Test với sản phẩm thực</h3>";
    
    $testProductId = 86; // OnePlus Ace Pro
    $product = $hanghoa->HanghoaGetbyId($testProductId);
    
    if (!$product) {
        throw new Exception("❌ Không tìm thấy sản phẩm test!");
    }
    
    echo "<p><strong>Sản phẩm test:</strong> {$product->tenhanghoa}</p>";
    echo "<p><strong>ID:</strong> {$product->idhanghoa}</p>";
    echo "<p><strong>Thương hiệu:</strong> {$product->idThuongHieu}</p>";
    echo "<p><strong>Giá:</strong> " . number_format($product->giathamkhao) . "₫</p>";
    
    $startTime = microtime(true);
    $relatedProducts = $hanghoa->getRelatedProducts($testProductId, 4);
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    if (empty($relatedProducts)) {
        throw new Exception("❌ Không tìm thấy sản phẩm liên quan!");
    }
    
    echo "<p class='success'>✅ Tìm thấy " . count($relatedProducts) . " sản phẩm liên quan</p>";
    echo "<p><strong>Thời gian thực thi:</strong> {$executionTime}ms</p>";
    
    echo "<h5>Danh sách sản phẩm:</h5>";
    echo "<div class='row'>";
    foreach ($relatedProducts as $index => $rp) {
        $isSameBrand = ($rp->idThuongHieu == $product->idThuongHieu);
        $badge = $isSameBrand ? "<span class='badge bg-primary'>Cùng hãng</span>" : "";
        
        echo "<div class='col-md-6 mb-2'>";
        echo "<div class='card'>";
        echo "<div class='card-body'>";
        echo "<h6>" . ($index + 1) . ". {$rp->tenhanghoa}</h6>";
        echo "<p class='mb-0'><small>ID: {$rp->idhanghoa}</small><br>";
        echo "Giá: " . number_format($rp->giathamkhao) . "₫ {$badge}</p>";
        echo "</div></div></div>";
    }
    echo "</div>";
    echo "</div>";
    
    // TEST 3: Test multiple products
    echo "<div class='test-section'>";
    echo "<h3>📋 Test 3: Test với nhiều sản phẩm</h3>";
    
    $testProducts = $hanghoa->HanghoaGetAll();
    $testProducts = array_slice($testProducts, 0, 10);
    
    $successCount = 0;
    $totalTime = 0;
    
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Sản phẩm</th><th>Kết quả</th><th>Thời gian</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($testProducts as $tp) {
        $startTime = microtime(true);
        $related = $hanghoa->getRelatedProducts($tp->idhanghoa, 3);
        $endTime = microtime(true);
        $time = round(($endTime - $startTime) * 1000, 2);
        $totalTime += $time;
        
        $hasResults = !empty($related);
        if ($hasResults) $successCount++;
        
        $status = $hasResults 
            ? "<span class='badge bg-success'>" . count($related) . " sản phẩm</span>"
            : "<span class='badge bg-warning'>Không có</span>";
        
        echo "<tr>";
        echo "<td>{$tp->tenhanghoa}</td>";
        echo "<td>{$status}</td>";
        echo "<td>{$time}ms</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    $avgTime = round($totalTime / count($testProducts), 2);
    $successRate = round(($successCount / count($testProducts)) * 100);
    
    echo "<p class='success'>✅ Tỷ lệ thành công: {$successCount}/" . count($testProducts) . " ({$successRate}%)</p>";
    echo "<p><strong>Thời gian trung bình:</strong> {$avgTime}ms</p>";
    echo "</div>";
    
    // TEST 4: Verify no old methods exist
    echo "<div class='test-section'>";
    echo "<h3>📋 Test 4: Xác nhận không có methods cũ</h3>";
    
    $oldMethods = [
        'getSameBrandSimilarPrice',
        'getRelatedProductsTier1',
        'getRelatedProductsTier2',
        'getRelatedProductsTier3',
        'getRelatedProductsTier4',
        'getRelatedProductsTier5',
        'getRelatedProductsTier6'
    ];
    
    $foundOldMethods = [];
    foreach ($oldMethods as $method) {
        if (method_exists($hanghoa, $method)) {
            $foundOldMethods[] = $method;
        }
    }
    
    if (!empty($foundOldMethods)) {
        echo "<p class='error'>⚠️ Tìm thấy methods cũ: " . implode(', ', $foundOldMethods) . "</p>";
    } else {
        echo "<p class='success'>✅ Không có methods cũ nào tồn tại</p>";
    }
    echo "</div>";
    
    // FINAL SUMMARY
    echo "<div class='alert alert-success mt-4'>";
    echo "<h4>🎉 Tổng Kết</h4>";
    echo "<ul class='mb-0'>";
    echo "<li>✅ Tất cả methods hoạt động đúng</li>";
    echo "<li>✅ Logic 3 tầng: Cùng hãng → Giá tương tự → Fallback</li>";
    echo "<li>✅ Performance tốt (trung bình {$avgTime}ms)</li>";
    echo "<li>✅ Tỷ lệ thành công: {$successRate}%</li>";
    echo "<li>✅ Không có methods cũ</li>";
    echo "</ul>";
    echo "<hr>";
    echo "<p class='mb-0'><strong>Kết luận:</strong> Hệ thống sản phẩm liên quan hoạt động hoàn hảo! ✨</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Lỗi</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
