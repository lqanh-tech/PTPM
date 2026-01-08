<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $result = [
        'success' => true,
        'debug' => []
    ];
    
    $stmt = $db->query("SHOW TABLES LIKE 'product_reviews'");
    $result['debug']['table_exists'] = $stmt->rowCount() > 0;
    
    if (!$result['debug']['table_exists']) {
        $result['debug']['error'] = 'Bảng product_reviews không tồn tại!';
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM product_reviews");
    $result['debug']['total_reviews'] = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM product_reviews WHERE is_approved = 1");
    $result['debug']['approved_reviews'] = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM product_reviews WHERE (status = 'visible' OR status IS NULL)");
    $result['debug']['visible_reviews'] = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM product_reviews WHERE is_approved = 1 AND (status = 'visible' OR status IS NULL)");
    $result['debug']['approved_and_visible'] = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $stmt = $db->query("
        SELECT rating, COUNT(*) as cnt 
        FROM product_reviews 
        WHERE is_approved = 1 AND (status = 'visible' OR status IS NULL)
        GROUP BY rating 
        ORDER BY rating DESC
    ");
    $result['debug']['rating_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("
        SELECT h.idhanghoa, h.tenhanghoa, 
               AVG(pr.rating) as avg_rating,
               COUNT(pr.id) as review_count
        FROM hanghoa h
        INNER JOIN product_reviews pr ON pr.ma_san_pham = h.idhanghoa
        WHERE pr.is_approved = 1 AND (pr.status = 'visible' OR pr.status IS NULL)
        GROUP BY h.idhanghoa
        ORDER BY avg_rating DESC
        LIMIT 10
    ");
    $result['debug']['products_with_reviews'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $minRating = isset($_GET['test_rating']) ? (int)$_GET['test_rating'] : 5;
    $threshold = $minRating - 0.5;
    
    $stmt = $db->prepare("
        SELECT h.idhanghoa, h.tenhanghoa,
            (SELECT COALESCE(AVG(pr.rating), 0) 
             FROM product_reviews pr 
             WHERE pr.ma_san_pham = h.idhanghoa 
             AND pr.is_approved = 1 
             AND (pr.status = 'visible' OR pr.status IS NULL)) as avg_rating,
            (SELECT COUNT(*) 
             FROM product_reviews pr 
             WHERE pr.ma_san_pham = h.idhanghoa 
             AND pr.is_approved = 1 
             AND (pr.status = 'visible' OR pr.status IS NULL)) as review_count
        FROM hanghoa h
        WHERE (SELECT COALESCE(AVG(pr.rating), 0) 
               FROM product_reviews pr 
               WHERE pr.ma_san_pham = h.idhanghoa 
               AND pr.is_approved = 1 
               AND (pr.status = 'visible' OR pr.status IS NULL)) >= ?
        AND (SELECT COUNT(*) 
             FROM product_reviews pr 
             WHERE pr.ma_san_pham = h.idhanghoa 
             AND pr.is_approved = 1 
             AND (pr.status = 'visible' OR pr.status IS NULL)) > 0
    ");
    $stmt->execute([$threshold]);
    $result['debug']['filter_test'] = [
        'min_rating' => $minRating,
        'threshold' => $threshold,
        'products_found' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
