<?php

require_once __DIR__ . '/administrator/elements_LQA/mod/ProductViewTrackerCls.php';

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

function getProductViewCount($idhanghoa) {
    try {
        $tracker = new ProductViewTracker();
        return $tracker->getViewCount($idhanghoa);
    } catch (Exception $e) {
        return 0;
    }
}
