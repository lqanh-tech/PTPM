<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/database.php';

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use GET.'
    ]);
    exit;
}

try {
    $type = $_GET['type'] ?? '';
    $provinceId = isset($_GET['province_id']) ? intval($_GET['province_id']) : null;
    $districtId = isset($_GET['district_id']) ? intval($_GET['district_id']) : null;

    $db = Database::getInstance()->getConnection();
    $data = [];

    switch ($type) {
        case 'provinces':
            $stmt = $db->prepare("SELECT id as ProvinceID, name as ProvinceName, code as Code FROM provinces WHERE is_active = 1 ORDER BY name ASC");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'districts':
            if (!$provinceId) {
                throw new Exception('province_id is required for getting districts');
            }
            $stmt = $db->prepare("SELECT id as DistrictID, name as DistrictName, code as Code FROM districts WHERE province_id = ? AND is_active = 1 ORDER BY name ASC");
            $stmt->execute([$provinceId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'wards':
            if (!$districtId) {
                throw new Exception('district_id is required for getting wards');
            }

            $stmt = $db->prepare("SELECT code as WardCode, name as WardName FROM wards WHERE district_id = ? AND is_active = 1 ORDER BY name ASC");
            $stmt->execute([$districtId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        default:
            throw new Exception('Invalid type. Use: provinces, districts, or wards');
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data),
        'type' => $type
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Get Address Data API Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
