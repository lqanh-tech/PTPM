<?php
/**
 * API to get address data (districts, wards) for cascade dropdown
 */

// Set charset BEFORE any output
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->exec("SET NAMES utf8mb4");
    $db->exec("SET CHARACTER SET utf8mb4");
    $db->exec("SET character_set_results = utf8mb4");
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_districts':
            $provinceId = intval($_GET['province_id'] ?? 0);
            
            if ($provinceId <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid province_id'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT id, code, name
                FROM districts
                WHERE province_id = ? AND is_active = 1
                ORDER BY name ASC
            ");
            $stmt->execute([$provinceId]);
            $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'districts' => $districts,
                'total' => count($districts)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get_wards':
            $districtId = intval($_GET['district_id'] ?? 0);
            
            if ($districtId <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid district_id'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT id, code, name
                FROM wards
                WHERE district_id = ? AND is_active = 1
                ORDER BY name ASC
            ");
            $stmt->execute([$districtId]);
            $wards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'wards' => $wards,
                'total' => count($wards)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get_all_provinces':
            $stmt = $db->query("
                SELECT id, code, name, region
                FROM provinces
                WHERE is_active = 1
                ORDER BY name ASC
            ");
            $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'provinces' => $provinces,
                'total' => count($provinces)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
