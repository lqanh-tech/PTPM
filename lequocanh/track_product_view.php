<?php
/**
 * Track Product View
 * Include file này vào trang chi tiết sản phẩm để track lượt xem
 * 
 * Cách dùng:
 * require_once __DIR__ . '/track_product_view.php';
 * trackProductView($idhanghoa);
 */

require_once __DIR__ . '/administrator/elements_LQA/mod/ProductViewTrackerCls.php';

/**
 * Track lượt xem sản phẩm
 */
function trackProductView($idhanghoa) {
    if (empty($idhanghoa) || !is_numeric($idhanghoa)) {
        return false;
    }
    
    try {
        $tracker = new ProductViewTracker();
        return $tracker->trackView($idhanghoa);
    } catch (Exception $e) {
        error_log("Track view error: " . $e->getMessage());
        return false;
    }
}

/**
 * Lấy lượt xem sản phẩm
 */
function getProductViewCount($idhanghoa) {
    try {
        $tracker = new ProductViewTracker();
        return $tracker->getViewCount($idhanghoa);
    } catch (Exception $e) {
        return 0;
    }
}
