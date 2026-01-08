<?php

require_once './elements_LQA/mod/sessionManager.php';
require_once './elements_LQA/mod/databaseOptimizer.php';

SessionManager::start();

if (!isset($_SESSION['ADMIN'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$optimizer = DatabaseOptimizer::getInstance();
$response = ['success' => false, 'message' => 'Unknown action'];

switch ($action) {
    case 'clear_cache':
        try {
            $result = $optimizer->clearCache();
            if ($result) {
                $response = ['success' => true, 'message' => 'Cache cleared successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to clear cache'];
            }
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
        break;
        
    case 'optimize_tables':
        try {
            $results = $optimizer->optimizeTables();
            $successCount = count(array_filter($results, function($r) { return $r === 'success'; }));
            $totalCount = count($results);
            
            $response = [
                'success' => true, 
                'message' => "Optimized $successCount/$totalCount tables successfully",
                'details' => $results
            ];
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
        break;
        
    case 'get_stats':
        try {
            $stats = $optimizer->getPerformanceStats();
            $response = ['success' => true, 'data' => $stats];
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
        break;
        
    default:
        $response = ['success' => false, 'message' => 'Invalid action'];
}

header('Content-Type: application/json');
echo json_encode($response);