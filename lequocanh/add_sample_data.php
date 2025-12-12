<?php
/**
 * Thêm dữ liệu mẫu cho News và Promotions
 */

require_once 'administrator/elements_LQA/mod/database.php';
require_once 'administrator/elements_LQA/mod/NewsManager.php';
require_once 'administrator/elements_LQA/mod/PromotionManager.php';

$newsManager = new NewsManager();
$promotionManager = new PromotionManager();

echo "========================================\n";
echo "THÊM DỮ LIỆU MẪU\n";
echo "========================================\n\n";

// Thêm tin tức mẫu
echo "1. Thêm tin tức mẫu...\n";
$newsData = [
    [
        'title' => 'iPhone 15 Pro Max chính thức ra mắt tại Việt Nam',
        'content' => 'Apple vừa chính thức giới thiệu iPhone 15 Pro Max với nhiều cải tiến đáng chú ý. Sản phẩm được trang bị chip A17 Pro mạnh mẽ, camera 48MP với zoom quang học 5x, và khung viền titan cao cấp. Giá bán dự kiến từ 29.990.000 VNĐ.',
        'author' => 'Admin'
    ],
    [
        'title' => 'Samsung Galaxy S24 Ultra - Siêu phẩm flagship 2024',
        'content' => 'Samsung Galaxy S24 Ultra đã chính thức được công bố với màn hình Dynamic AMOLED 2X 6.8 inch, chip Snapdragon 8 Gen 3, và hệ thống camera AI tiên tiến. Đặc biệt, sản phẩm hỗ trợ S Pen với độ trễ cực thấp.',
        'author' => 'Admin'
    ],
    [
        'title' => 'Top 5 smartphone gaming tốt nhất năm 2024',
        'content' => 'Bài viết tổng hợp 5 chiếc smartphone gaming hàng đầu hiện nay bao gồm: ASUS ROG Phone 8, RedMagic 9 Pro, Black Shark 6 Pro, Lenovo Legion Y90, và OnePlus 12. Tất cả đều được trang bị chip mạnh mẽ và hệ thống tản nhiệt hiệu quả.',
        'author' => 'Admin'
    ]
];

foreach ($newsData as $news) {
    $result = $newsManager->addNews(
        $news['title'],
        $news['content'],
        '', // Không có ảnh
        $news['author'],
        1, // Đã xuất bản
        date('Y-m-d H:i:s')
    );
    
    if ($result) {
        echo "  ✓ Đã thêm: {$news['title']}\n";
    } else {
        echo "  ✗ Lỗi thêm: {$news['title']}\n";
    }
}

echo "\n2. Thêm chương trình ưu đãi mẫu...\n";
$promoData = [
    [
        'title' => 'Giảm 20% tất cả iPhone',
        'description' => 'Áp dụng cho tất cả dòng iPhone từ iPhone 12 trở lên',
        'discount' => 20.00,
        'days' => 30
    ],
    [
        'title' => 'Mua 1 tặng 1 phụ kiện',
        'description' => 'Mua điện thoại Samsung tặng ngay ốp lưng + dán màn hình',
        'discount' => 15.00,
        'days' => 15
    ],
    [
        'title' => 'Flash Sale cuối tuần',
        'description' => 'Giảm sốc đến 30% cho các sản phẩm gaming phone',
        'discount' => 30.00,
        'days' => 7
    ]
];

foreach ($promoData as $promo) {
    $result = $promotionManager->addPromotion(
        $promo['title'],
        $promo['description'],
        $promo['discount'],
        date('Y-m-d'),
        date('Y-m-d', strtotime("+{$promo['days']} days")),
        1 // Kích hoạt
    );
    
    if ($result) {
        echo "  ✓ Đã thêm: {$promo['title']}\n";
    } else {
        echo "  ✗ Lỗi thêm: {$promo['title']}\n";
    }
}

echo "\n========================================\n";
echo "✅ HOÀN THÀNH!\n";
echo "========================================\n";
echo "\nTruy cập trang chủ để xem kết quả:\n";
echo "http://localhost:20080/lequocanh/\n";
