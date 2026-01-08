<?php
require_once 'performanceMonitor.php';
require_once 'errorTracker.php';
require_once 'userActivityTracker.php';
require_once 'alertSystem.php';

UserActivityTracker::init();
ErrorTracker::registerErrorHandler();

RealtimePerformanceMonitor::startOperation('Page Load');

usleep(50000);
RealtimePerformanceMonitor::endOperation('Page Load');

RealtimePerformanceMonitor::startOperation('Database Query 1');
usleep(120000);
RealtimePerformanceMonitor::endOperation('Database Query 1');

trigger_error("This is a test warning!", E_USER_WARNING);
trigger_error("This is a test error!", E_USER_ERROR);

UserActivityTracker::logActivity('Page View', ['page_name' => 'Dashboard']);
UserActivityTracker::logActivity('Button Click', ['button_id' => 'refresh_data']);

AlertSystem::defineRule('High Memory Usage', [
    'type' => 'metric_threshold',
    'metric' => 'peak_memory_usage_mb',
    'threshold' => 64,
    'operator' => '>',
    'description' => 'Alert when peak memory usage exceeds 64MB.'
]);

AlertSystem::defineRule('High Error Rate', [
    'type' => 'error_rate',
    'threshold' => 1,
    'description' => 'Alert when more than 1 error occurs.'
]);

$currentMetrics = [
    'page_load_duration' => RealtimePerformanceMonitor::getOperationDuration('Page Load'),
    'db_query_duration' => RealtimePerformanceMonitor::getOperationDuration('Database Query 1'),
    'memory_usage_mb' => RealtimePerformanceMonitor::getMemoryUsage() / (1024 * 1024),
    'peak_memory_usage_mb' => RealtimePerformanceMonitor::getPeakMemoryUsage() / (1024 * 1024),
    'cpu_usage_mock' => RealtimePerformanceMonitor::getCpuUsage()
];
AlertSystem::evaluateRules($currentMetrics, ErrorTracker::getErrors());

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitoring Dashboard</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background-color: #f4f4f4;
    }

    .container {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    h1,
    h2 {
        color: #333;
    }

    pre {
        background-color: #eee;
        padding: 10px;
        border-radius: 4px;
        overflow-x: auto;
    }

    .metric-card {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 10px;
        background-color: #f9f9f9;
    }

    .metric-card h3 {
        margin-top: 0;
        color: #555;
    }

    .alert {
        background-color: #ffe0e0;
        border-left: 5px solid #ff0000;
        padding: 10px;
        margin-bottom: 10px;
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>System Monitoring Dashboard</h1>

        <h2>Performance Metrics</h2>
        <div class="metric-card">
            <h3>Page Load Duration</h3>
            <p>Execution Time:
                <?php echo sprintf('%.4f', RealtimePerformanceMonitor::getOperationDuration('Page Load')); ?> seconds
            </p>
        </div>
        <div class="metric-card">
            <h3>Database Query Duration</h3>
            <p>Execution Time:
                <?php echo sprintf('%.4f', RealtimePerformanceMonitor::getOperationDuration('Database Query 1')); ?>
                seconds</p>
        </div>
        <div class="metric-card">
            <h3>Memory Usage</h3>
            <p>Current:
                <?php echo RealtimePerformanceMonitor::formatBytes(RealtimePerformanceMonitor::getMemoryUsage()); ?></p>
            <p>Peak:
                <?php echo RealtimePerformanceMonitor::formatBytes(RealtimePerformanceMonitor::getPeakMemoryUsage()); ?>
            </p>
        </div>
        <div class="metric-card">
            <h3>CPU Usage (Mock)</h3>
            <p>Current: <?php echo RealtimePerformanceMonitor::getCpuUsage(); ?>%</p>
        </div>

        <h2>Error Tracking</h2>
        <?php if (!empty(ErrorTracker::getErrors())): ?>
        <?php foreach (ErrorTracker::getErrors() as $error): ?>
        <div class="alert">
            <p><strong>Type:</strong> <?php echo htmlspecialchars($error['type']); ?></p>
            <p><strong>Message:</strong> <?php echo htmlspecialchars($error['message']); ?></p>
            <p><strong>Timestamp:</strong> <?php echo htmlspecialchars($error['timestamp']); ?></p>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p>No errors logged.</p>
        <?php endif; ?>

        <h3>Error Counts by Type</h3>
        <pre><?php print_r(ErrorTracker::getErrorCounts()); ?></pre>

        <h2>User Activity</h2>
        <div class="metric-card">
            <h3>Session Duration</h3>
            <p><?php echo UserActivityTracker::getSessionDuration(); ?> seconds</p>
        </div>
        <h3>Recent Activities</h3>
        <pre><?php print_r(UserActivityTracker::getSessionActivities()); ?></pre>

        <h2>Alerts</h2>
        <?php if (!empty(AlertSystem::getTriggeredAlerts())): ?>
        <?php foreach (AlertSystem::getTriggeredAlerts() as $alert): ?>
        <div class="alert">
            <p><strong>Rule:</strong> <?php echo htmlspecialchars($alert['rule_name']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($alert['config']['description']); ?></p>
            <p><strong>Timestamp:</strong> <?php echo htmlspecialchars($alert['timestamp']); ?></p>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p>No active alerts.</p>
        <?php endif; ?>

    </div>
</body>

</html>