<?php
declare(strict_types=1);

/**
 * Advanced Search API - Tìm kiếm nâng cao
 * 
 * GET /api/search.php?q=keyword&sort=price_asc&brand=1&min_price=100000&max_price=5000000
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$conn = $db->getConnection();

$query = $_GET['q'] ?? '';
$sort = $_GET['sort'] ?? 'relevance';
$brandId = intval($_GET['brand'] ?? 0);
$categoryId = intval($_GET['category'] ?? 0);
$minPrice = intval($_GET['min_price'] ?? 0);
$maxPrice = intval($_GET['max_price'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, max(1, intval($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;

try {
    $where = ["h.trang_thai = 1"];
    $params = [];
    
    // Search by keyword
    if (!empty($query)) {
        $where[] = "(h.tenhanghoa LIKE ? OR h.mota LIKE ?)";
        $params[] = "%{$query}%";
        $params[] = "%{$query}%";
    }
    
    // Filter by brand
    if ($brandId > 0) {
        $where[] = "h.idThuongHieu = ?";
        $params[] = $brandId;
    }
    
    // Filter by category
    if ($categoryId > 0) {
        $where[] = "h.idloaihang = ?";
        $params[] = $categoryId;
    }
    
    // Filter by price
    if ($minPrice > 0) {
        $where[] = "h.giathamkhao >= ?";
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $where[] = "h.giathamkhao <= ?";
        $params[] = $maxPrice;
    }
    
    $whereSQL = implode(' AND ', $where);
    
    // Sort
    $orderBy = match($sort) {
        'price_asc' => 'h.giathamkhao ASC',
        'price_desc' => 'h.giathamkhao DESC',
        'newest' => 'h.created_at DESC',
        'popular' => 'h.view_count DESC',
        'name_asc' => 'h.tenhanghoa ASC',
        'name_desc' => 'h.tenhanghoa DESC',
        default => 'h.is_featured DESC, h.view_count DESC'
    };
    
    // Count total
    $countSQL = "SELECT COUNT(*) as total FROM hanghoa h WHERE {$whereSQL}";
    $countStmt = $conn->prepare($countSQL);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get products
    $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.giakhuyenmai, h.hinhanh, h.mota,
                   h.is_featured, h.is_new, h.is_sale, h.view_count,
                   th.tenTH as brand_name, lh.tenloaihang as category_name,
                   t.soLuong as stock_quantity
            FROM hanghoa h
            LEFT JOIN thuonghieu th ON h.idThuongHieu = th.idThuongHieu
            LEFT JOIN loaihang lh ON h.idloaihang = lh.idloaihang
            LEFT JOIN tonkho t ON h.idhanghoa = t.idhanghoa
            WHERE {$whereSQL}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format products
    $formattedProducts = array_map(function($p) {
        $hasDiscount = !empty($p['giakhuyenmai']) && $p['giakhuyenmai'] > 0 && $p['giakhuyenmai'] < $p['giathamkhao'];
        $currentPrice = $hasDiscount ? $p['giakhuyenmai'] : $p['giathamkhao'];
        $discountPercent = $hasDiscount ? round((($p['giathamkhao'] - $p['giakhuyenmai']) / $p['giathamkhao']) * 100) : 0;
        
        $imageSrc = (!empty($p['hinhanh']) && $p['hinhanh'] > 0)
            ? "/lequocanh/administrator/elements_LQA/mhanghoa/displayImage.php?id=" . $p['hinhanh']
            : "/lequocanh/administrator/elements_LQA/img_LQA/no-image.png";
        
        return [
            'id' => $p['idhanghoa'],
            'name' => $p['tenhanghoa'],
            'price' => $currentPrice,
            'original_price' => $p['giathamkhao'],
            'has_discount' => $hasDiscount,
            'discount_percent' => $discountPercent,
            'image' => $imageSrc,
            'description' => mb_substr(strip_tags($p['mota'] ?? ''), 0, 100) . '...',
            'brand' => $p['brand_name'],
            'category' => $p['category_name'],
            'stock' => $p['stock_quantity'] ?? 0,
            'is_featured' => $p['is_featured'] == 1,
            'is_new' => $p['is_new'] == 1,
            'is_sale' => $p['is_sale'] == 1,
            'url' => "/lequocanh/index.php?reqHanghoa=" . $p['idhanghoa']
        ];
    }, $products);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit),
        'products' => $formattedProducts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi tìm kiếm: ' . $e->getMessage()
    ]);
}