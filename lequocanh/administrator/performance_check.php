<?php
/**
 * Performance Check Dashboard
 * Kiểm tra và báo cáo hiệu suất website
 */

require_once __DIR__ . '/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/elements_LQA/mod/database.php';

SessionManager::start();

// Kiểm tra quyền admin
if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    header('Location: userLogin.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Lấy thông tin cache
$cacheDir = __DIR__ . '/../cache';
$cacheFiles = glob($cacheDir . '/*.cache');
$cacheSize = 0;
$cacheCount = count($cacheFiles);
foreach ($cacheFiles as $file) {
    $cacheSize += filesize($file);
}

// Kiểm tra database
$dbStats = [];
try {
    // Số lượng sản phẩm
    $stmt = $db->query("SELECT COUNT(*) as count FROM hanghoa");
    $dbStats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Số lượng đơn hàng
    $stmt = $db->query("SELECT COUNT(*) as count FROM donhang");
    $dbStats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Số lượng khách hàng
    $stmt = $db->query("SELECT COUNT(*) as count FROM khachhang");
    $dbStats['customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Database size
    $stmt = $db->query("SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()");
    $dbStats['size'] = $stmt->fetch(PDO::FETCH_ASSOC)['size_mb'];
} catch (Exception $e) {
    $dbStats['error'] = $e->getMessage();
}

// PHP Info
$phpInfo = [
    'version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'opcache' => function_exists('opcache_get_status') ? 'Enabled' : 'Disabled',
    'zlib' => extension_loaded('zlib') ? 'Enabled' : 'Disabled',
];

// Server Info
$serverInfo = [
    'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'php_sapi' => php_sapi_name(),
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stat-card { border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .stat-card.green { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .stat-card.blue { background: linear-gradient(135deg, #007bff, #6610f2); color: white; }
        .stat-card.orange { background: linear-gradient(135deg, #fd7e14, #ffc107); color: white; }
        .stat-card.red { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }
        .stat-value { font-size: 2rem; font-weight: bold; }
        .stat-label { opacity: 0.9; }
        .check-item { padding: 10px; border-bottom: 1px solid #eee; }
        .check-item:last-child { border-bottom: none; }
        .check-pass { color: #28a745; }
        .check-fail { color: #dc3545; }
        .check-warn { color: #ffc107; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <h1 class="mb-4"><i class="fas fa-tachometer-alt"></i> Performance Dashboard</h1>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="stat-value"><?php echo $cacheCount; ?></div>
                    <div class="stat-label"><i class="fas fa-database"></i> Cache Files</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="stat-value"><?php echo round($cacheSize / 1024, 2); ?> KB</div>
                    <div class="stat-label"><i class="fas fa-hdd"></i> Cache Size</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="stat-value"><?php echo $dbStats['products'] ?? 0; ?></div>
                    <div class="stat-label"><i class="fas fa-box"></i> Sản phẩm</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card red">
                    <div class="stat-value"><?php echo $dbStats['size'] ?? 0; ?> MB</div>
                    <div class="stat-label"><i class="fas fa-database"></i> Database Size</div>
                </div>
            </div>
        </div>
        
        <!-- Performance Checks -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><i class="fas fa-check-circle"></i> Performance Checks</div>
                    <div class="card-body">
                        <div class="check-item">
                            <i class="fas fa-<?php echo extension_loaded('zlib') ? 'check check-pass' : 'times check-fail'; ?>"></i>
                            GZIP Compression
                        </div>
                        <div class="check-item">
                            <i class="fas fa-<?php echo function_exists('opcache_get_status') ? 'check check-pass' : 'times check-fail'; ?>"></i>
                            OPcache
                        </div>
                        <div class="check-item">
                            <i class="fas fa-<?php echo file_exists($cacheDir . '/CacheManager.php') ? 'check check-pass' : 'times check-fail'; ?>"></i>
                            Cache System
                        </div>
                        <div class="check-item">
                            <i class="fas fa-<?php echo file_exists(__DIR__ . '/../sw.js') ? 'check check-pass' : 'times check-fail'; ?>"></i>
                            Service Worker
                        </div>
                        <div class="check-item">
                            <i class="fas fa-<?php echo (int)ini_get('memory_limit') >= 128 ? 'check check-pass' : 'exclamation-triangle check-warn'; ?>"></i>
                            Memory Limit (<?php echo ini_get('memory_limit'); ?>)
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><i class="fas fa-server"></i> Server Info</div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><td>PHP Version</td><td><?php echo $phpInfo['version']; ?></td></tr>
                            <tr><td>Memory Limit</td><td><?php echo $phpInfo['memory_limit']; ?></td></tr>
                            <tr><td>Max Execution Time</td><td><?php echo $phpInfo['max_execution_time']; ?>s</td></tr>
                            <tr><td>OPcache</td><td><?php echo $phpInfo['opcache']; ?></td></tr>
                            <tr><td>GZIP</td><td><?php echo $phpInfo['zlib']; ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cache Management -->
        <div class="card mt-4">
            <div class="card-header"><i class="fas fa-broom"></i> Cache Management</div>
            <div class="card-body">
                <p>Tổng số file cache: <strong><?php echo $cacheCount; ?></strong> | Kích thước: <strong><?php echo round($cacheSize / 1024, 2); ?> KB</strong></p>
                <button class="btn btn-danger" onclick="clearCache('all')">
                    <i class="fas fa-trash"></i> Xóa tất cả cache
                </button>
                <button class="btn btn-warning" onclick="clearCache('products')">
                    <i class="fas fa-box"></i> Xóa cache sản phẩm
                </button>
                <button class="btn btn-info" onclick="clearCache('pages')">
                    <i class="fas fa-file"></i> Xóa cache trang
                </button>
                <div id="cacheResult" class="mt-3"></div>
            </div>
        </div>
        
        <!-- Recommendations -->
        <div class="card mt-4">
            <div class="card-header"><i class="fas fa-lightbulb"></i> Gợi ý tối ưu</div>
            <div class="card-body">
                <ul>
                    <li>Bật <strong>OPcache</strong> trong php.ini để tăng tốc PHP</li>
                    <li>Cấu hình <strong>Cloudflare Page Rules</strong> để cache static assets</li>
                    <li>Sử dụng <strong>CDN</strong> cho hình ảnh sản phẩm</li>
                    <li>Thêm <strong>indexes</strong> cho các cột thường query trong database</li>
                    <li>Bật <strong>Browser Caching</strong> với thời gian dài cho static files</li>
                </ul>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>
    </div>
    
    <script>
    function clearCache(type) {
        fetch('../api/clear_cache.php?action=' + type)
            .then(response => response.json())
            .then(data => {
                document.getElementById('cacheResult').innerHTML = 
                    '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + 
                    data.message + '</div>';
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(error => {
                document.getElementById('cacheResult').innerHTML = 
                    '<div class="alert alert-danger">Lỗi: ' + error.message + '</div>';
            });
    }
    </script>
</body>
</html>
