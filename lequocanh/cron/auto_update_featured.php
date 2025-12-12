<?php
/**
 * Cron Job - Tự động cập nhật sản phẩm nổi bật hàng ngày
 * 
 * Cách chạy:
 * 1. Manual: php auto_update_featured.php
 * 2. Cron (Linux): 0 2 * * * php /path/to/auto_update_featured.php
 * 3. Task Scheduler (Windows): Chạy lúc 2h sáng mỗi ngày
 */

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/AutoFeaturedCls.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/FeaturedProductsCls.php';

$autoFeatured = new AutoFeatured();
$featuredMgr = new FeaturedProducts();

echo "[" . date('Y-m-d H:i:s') . "] Bắt đầu cập nhật sản phẩm nổi bật...\n";

try {
    // 1. Cập nhật sản phẩm nổi bật theo điểm tổng hợp (khuyến nghị)
    echo "- Đánh dấu sản phẩm theo điểm tổng hợp...\n";
    $autoFeatured->autoMarkByScore(20);
    
    // 2. Tự động đánh dấu sản phẩm mới (trong 30 ngày)
    echo "- Đánh dấu sản phẩm mới...\n";
    $db = Database::getInstance()->getConnection();
    $sql = "UPDATE hanghoa 
            SET is_new = 1 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $db->exec($sql);
    
    // Bỏ đánh dấu sản phẩm cũ hơn 30 ngày
    $sql = "UPDATE hanghoa 
            SET is_new = 0 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $db->exec($sql);
    
    // 3. Tự động hết hạn khuyến mãi
    echo "- Kiểm tra khuyến mãi hết hạn...\n";
    $sql = "UPDATE hanghoa 
            SET is_sale = 0, sale_price = NULL 
            WHERE is_sale = 1 
            AND sale_end_date IS NOT NULL 
            AND sale_end_date < NOW()";
    $result = $db->exec($sql);
    echo "  + Đã hủy $result khuyến mãi hết hạn\n";
    
    // 4. Thống kê
    $stats = $db->query("SELECT 
        SUM(is_featured) as featured_count,
        SUM(is_new) as new_count,
        SUM(is_sale) as sale_count
        FROM hanghoa")->fetch(PDO::FETCH_OBJ);
    
    echo "\n✅ Hoàn thành!\n";
    echo "Thống kê:\n";
    echo "- Sản phẩm nổi bật: " . $stats->featured_count . "\n";
    echo "- Sản phẩm mới: " . $stats->new_count . "\n";
    echo "- Sản phẩm khuyến mãi: " . $stats->sale_count . "\n";
    
    // Log vào file
    $log_file = __DIR__ . '/../logs/auto_featured.log';
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_entry = sprintf(
        "[%s] Featured: %d, New: %d, Sale: %d\n",
        date('Y-m-d H:i:s'),
        $stats->featured_count,
        $stats->new_count,
        $stats->sale_count
    );
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    
    // Log error
    $error_log = __DIR__ . '/../logs/auto_featured_error.log';
    file_put_contents($error_log, 
        "[" . date('Y-m-d H:i:s') . "] " . $e->getMessage() . "\n", 
        FILE_APPEND
    );
}

echo "\n";
