<?php

require_once './elements_LQA/mod/sessionManager.php';
require_once './elements_LQA/mod/databaseOptimizer.php';
require_once './elements_LQA/monitoring/ModernMonitoringSystem.php';

SessionManager::start();

if (!isset($_SESSION['ADMIN'])) {
    header('Location: userLogin.php');
    exit();
}

$optimizer = DatabaseOptimizer::getInstance();
$monitor = ModernMonitoringSystem::getInstance();

$performanceStats = $optimizer->getPerformanceStats();
$systemHealth = $monitor->getSystemHealth();
$slowQueries = $optimizer->analyzeSlowQueries(10);
$indexSuggestions = $optimizer->suggestIndexes();
$databaseSize = $optimizer->getDatabaseSize();

$currentTime = date('Y-m-d H:i:s');
$uptime = $systemHealth['uptime'];
$memoryUsage = $systemHealth['memory'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Dashboard - Hệ Thống Quản Lý</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .status-healthy { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-danger { color: #dc3545; }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-0">
                    <i class="fas fa-tachometer-alt text-primary"></i>
                    Performance Dashboard
                </h1>
                <p class="text-muted">Real-time system performance monitoring</p>
            </div>
        </div>

        <!-- System Health Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-heartbeat fa-2x mb-2 <?php echo $systemHealth['status'] === 'healthy' ? 'status-healthy' : 'status-danger'; ?>"></i>
                        <h5>System Status</h5>
                        <span class="metric-value <?php echo $systemHealth['status'] === 'healthy' ? 'status-healthy' : 'status-danger'; ?>">
                            <?php echo ucfirst($systemHealth['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2 text-info"></i>
                        <h5>Uptime</h5>
                        <span class="metric-value text-info">
                            <?php echo gmdate("H:i:s", $uptime); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-memory fa-2x mb-2 text-warning"></i>
                        <h5>Memory Usage</h5>
                        <span class="metric-value text-warning">
                            <?php echo round($memoryUsage['usage'] / 1024 / 1024, 1); ?>MB
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-database fa-2x mb-2 text-success"></i>
                        <h5>DB Status</h5>
                        <span class="metric-value <?php echo $systemHealth['database']['healthy'] ? 'status-healthy' : 'status-danger'; ?>">
                            <?php echo $systemHealth['database']['healthy'] ? 'Healthy' : 'Error'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line"></i>
                            Query Performance (24h)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($performanceStats['queries'])): ?>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Total Queries</small>
                                <div class="h4"><?php echo number_format($performanceStats['queries']['total_queries']); ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Avg Response Time</small>
                                <div class="h4"><?php echo round($performanceStats['queries']['avg_execution_time'] * 1000, 2); ?>ms</div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-6">
                                <small class="text-muted">Slow Queries</small>
                                <div class="h4 text-warning"><?php echo $performanceStats['queries']['slow_queries']; ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Max Response Time</small>
                                <div class="h4 text-danger"><?php echo round($performanceStats['queries']['max_execution_time'] * 1000, 2); ?>ms</div>
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No performance data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group"></i>
                            Cache Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($performanceStats['cache'])): ?>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Cached Queries</small>
                                <div class="h4"><?php echo number_format($performanceStats['cache']['total_cached']); ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Cache Hits</small>
                                <div class="h4"><?php echo number_format($performanceStats['cache']['total_hits']); ?></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">Hit Ratio</small>
                            <div class="progress mt-1">
                                <div class="progress-bar bg-success" style="width: <?php echo $performanceStats['cache']['hit_ratio']; ?>%">
                                    <?php echo $performanceStats['cache']['hit_ratio']; ?>%
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No cache data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slow Queries -->
        <?php if (!empty($slowQueries)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            Slow Queries Analysis
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Query</th>
                                        <th>Avg Time</th>
                                        <th>Max Time</th>
                                        <th>Frequency</th>
                                        <th>Recommendations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($slowQueries, 0, 5) as $query): ?>
                                    <tr>
                                        <td>
                                            <code class="small">
                                                <?php echo htmlspecialchars(substr($query['query'], 0, 80)) . '...'; ?>
                                            </code>
                                        </td>
                                        <td><span class="badge bg-warning"><?php echo $query['avg_time']; ?>s</span></td>
                                        <td><span class="badge bg-danger"><?php echo $query['max_time']; ?>s</span></td>
                                        <td><?php echo $query['frequency']; ?></td>
                                        <td>
                                            <?php if (!empty($query['recommendations'])): ?>
                                                <ul class="small mb-0">
                                                    <?php foreach (array_slice($query['recommendations'], 0, 2) as $rec): ?>
                                                    <li><?php echo htmlspecialchars($rec); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Database Size -->
        <?php if (!empty($databaseSize)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-hdd"></i>
                            Database Size Analysis
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Table</th>
                                        <th>Size (MB)</th>
                                        <th>Rows</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($databaseSize, 0, 10) as $table): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($table['table_name']); ?></td>
                                        <td><?php echo $table['size_mb']; ?> MB</td>
                                        <td><?php echo number_format($table['table_rows']); ?></td>
                                        <td>
                                            <?php if ($table['size_mb'] > 100): ?>
                                                <span class="badge bg-warning">Large</span>
                                            <?php elseif ($table['size_mb'] > 10): ?>
                                                <span class="badge bg-info">Medium</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Small</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tools"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="clearCache()">
                                <i class="fas fa-broom"></i> Clear Cache
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="optimizeTables()">
                                <i class="fas fa-wrench"></i> Optimize Tables
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="refreshData()">
                                <i class="fas fa-sync"></i> Refresh Data
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Admin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearCache() {
            if (confirm('Are you sure you want to clear the cache?')) {
                fetch('performance_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'clear_cache'})
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
            }
        }

        function optimizeTables() {
            if (confirm('This may take a few minutes. Continue?')) {
                fetch('performance_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'optimize_tables'})
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
            }
        }

        function refreshData() {
            location.reload();
        }

        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>