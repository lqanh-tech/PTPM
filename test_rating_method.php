<?php
require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

$hanghoa = new hanghoa();
$rating = $hanghoa->getAverageRating(143);

echo "=== TEST getAverageRating(143) ===\n";
echo "Average: {$rating['average']}\n";
echo "Count: {$rating['count']}\n";

if ($rating['count'] > 0) {
    echo "✅ SUCCESS: Tìm thấy {$rating['count']} đánh giá, trung bình {$rating['average']} sao\n";
} else {
    echo "❌ FAILED: Không tìm thấy đánh giá nào\n";
}