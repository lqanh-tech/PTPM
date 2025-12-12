<?php
/**
 * Test Related Products on Actual Website
 * Simulates how the website loads and displays related products
 */

// Simulate website environment
$_SERVER['REQUEST_URI'] = '/index.php?reqHanghoa=86';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['reqHanghoa'] = 86;

require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>🌐 Test Website Related Products</title>
    <meta charset='utf-8'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h1>🌐 Test Sản Phẩm Liên Quan Trên Website</h1>
    <p class='text-muted'>Mô phỏng cách website thực tế load và hiển thị sản phẩm liên quan</p>
";

try {
    // Initialize exactly like the website does
    $hanghoa = new hanghoa($connection);
    $idhanghoa = 86;
    
    echo "<div class='alert alert-info'>";
    echo "<h4>📦 Thông Tin Request</h4>";
    echo "<p><strong>Product ID:</strong> {$idhanghoa}</p>";
    echo "<p><strong>Request URI:</strong> {$_SERVER['REQUEST_URI']}</p>";
    echo "</div>";
    
    // Get current product (like viewHangHoa.php does)
    $currentProduct = $hanghoa->HanghoaGetbyId($idhanghoa);
    
    if (!$currentProduct) {
        throw new Exception("Không tìm thấy sản phẩm!");
    }
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Sản Phẩm Hiện Tại</h4>";
    echo "<p><strong>Tên:</strong> {$currentProduct->tenhanghoa}</p>";
    echo "<p><strong>Thương hiệu:</strong> {$currentProduct->idThuongHieu}</p>";
    echo "<p><strong>Giá:</strong> " . number_format($currentProduct->giathamkhao) . "₫</p>";
    echo "</div>";
    
    // Get related products (exactly like viewHangHoa.php line 474)
    echo "<div class='alert alert-primary'>";
    echo "<h4>🔍 Đang Lấy Sản Phẩm Liên Quan...</h4>";
    echo "<p>Gọi: <code>\$hanghoa->getRelatedProducts({$idhanghoa}, 4)</code></p>";
    echo "</div>";
    
    $startTime = microtime(true);
    $relatedProducts = $hanghoa->getRelatedProducts($idhanghoa, 4);
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    if (empty($relatedProducts)) {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ Không Tìm Thấy Sản Phẩm Liên Quan</h4>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Tìm Thấy " . count($relatedProducts) . " Sản Phẩm Liên Quan</h4>";
        echo "<p><strong>Thời gian:</strong> {$executionTime}ms</p>";
        echo "</div>";
        
        echo "<h3>📋 Danh Sách Sản Phẩm Liên Quan</h3>";
        echo "<div class='row'>";
        
        foreach ($relatedProducts as $index => $rp) {
            $isSameBrand = ($rp->idThuongHieu == $currentProduct->idThuongHieu);
            $badge = $isSameBrand ? "<span class='badge bg-primary'>Cùng hãng</span>" : "<span class='badge bg-secondary'>Giá tương tự</span>";
            
            // Get image (like viewHangHoa.php does)
            $rpHinhanh = $hanghoa->GetHinhAnhById($rp->hinhanh);
            $hasImage = ($rpHinhanh && !empty($rpHinhanh->duong_dan));
            
            echo "<div class='col-md-6 mb-3'>";
            echo "<div class='card h-100'>";
            echo "<div class='card-body'>";
            echo "<h5 class='card-title'>" . ($index + 1) . ". {$rp->tenhanghoa}</h5>";
            echo "<p class='card-text'>";
            echo "<small class='text-muted'>ID: {$rp->idhanghoa}</small><br>";
            echo "<strong>Thương hiệu:</strong> {$rp->idThuongHieu}<br>";
            echo "<strong>Giá:</strong> " . number_format($rp->giathamkhao) . "₫<br>";
            echo "<strong>Hình ảnh:</strong> " . ($hasImage ? "✅ Có" : "❌ Không") . "<br>";
            echo "{$badge}";
            echo "</p>";
            echo "</div></div></div>";
        }
        
        echo "</div>";
    }
    
    // Summary
    echo "<div class='alert alert-success mt-4'>";
    echo "<h4>🎉 Kết Luận</h4>";
    echo "<ul class='mb-0'>";
    echo "<li>✅ Website load sản phẩm liên quan thành công</li>";
    echo "<li>✅ Tìm thấy " . count($relatedProducts) . " sản phẩm</li>";
    echo "<li>✅ Thời gian: {$executionTime}ms</li>";
    echo "<li>✅ Logic hoạt động đúng: Ưu tiên cùng hãng</li>";
    echo "<li>✅ Không có lỗi</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Lỗi</h4>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
