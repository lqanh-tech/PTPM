<?php
require_once 'elements_LQA/security/securityMonitor.php';
require_once 'elements_LQA/security/intrusionDetector.php';
require_once 'elements_LQA/security/threatAnalyzer.php';

SecurityMonitor::logFailedLoginAttempt('attacker_user');
SecurityMonitor::logFailedLoginAttempt('attacker_user');
IntrusionDetector::recordFailedLogin('192.168.1.100');
IntrusionDetector::recordFailedLogin('192.168.1.100');
IntrusionDetector::recordFailedLogin('192.168.1.100');
IntrusionDetector::recordFailedLogin('192.168.1.100');
IntrusionDetector::recordFailedLogin('192.168.1.100');

SecurityMonitor::logCsrfAttempt('http://malicious.com', 'invalid_token', 'expected_token');
SecurityMonitor::logSuspiciousActivity('Unusual API calls', 'user123');

$allSecurityEvents = SecurityMonitor::getSecurityEvents();

$analyzedEvents = [];
foreach ($allSecurityEvents as $event) {
    $analyzedEvents[] = ThreatAnalyzer::analyzeEvent($event);
}

$correlatedThreats = ThreatAnalyzer::analyzeMultipleEvents($allSecurityEvents);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard</title>
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

    .event-card {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 10px;
        background-color: #f9f9f9;
    }

    .event-card h3 {
        margin-top: 0;
        color: #555;
    }

    .alert-critical {
        background-color: #ffcccc;
        border-left: 5px solid #cc0000;
        padding: 10px;
        margin-bottom: 10px;
    }

    .alert-high {
        background-color: #ffebcc;
        border-left: 5px solid #ff9900;
        padding: 10px;
        margin-bottom: 10px;
    }

    .alert-medium {
        background-color: #ffffcc;
        border-left: 5px solid #cccc00;
        padding: 10px;
        margin-bottom: 10px;
    }

    .alert-low {
        background-color: #e0ffe0;
        border-left: 5px solid #00cc00;
        padding: 10px;
        margin-bottom: 10px;
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>Security Dashboard</h1>

        <h2>Recent Security Events</h2>
        <?php if (!empty($allSecurityEvents)): ?>
        <?php foreach ($allSecurityEvents as $event): ?>
        <div class="event-card">
            <p><strong>Timestamp:</strong> <?php echo htmlspecialchars($event['timestamp']); ?></p>
            <p><strong>Event Type:</strong> <?php echo htmlspecialchars($event['event_type']); ?></p>
            <p><strong>IP Address:</strong> <?php echo htmlspecialchars($event['ip_address']); ?></p>
            <p><strong>Details:</strong> <?php echo htmlspecialchars(json_encode($event['details'])); ?></p>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p>No security events logged.</p>
        <?php endif; ?>

        <h2>Analyzed Events</h2>
        <?php if (!empty($analyzedEvents)): ?>
        <?php foreach ($analyzedEvents as $analysis): ?>
        <div class="event-card alert-<?php echo strtolower($analysis['severity']); ?>">
            <p><strong>Category:</strong> <?php echo htmlspecialchars($analysis['category']); ?></p>
            <p><strong>Severity:</strong> <?php echo htmlspecialchars($analysis['severity']); ?></p>
            <p><strong>Suggested Action:</strong> <?php echo htmlspecialchars($analysis['suggested_action']); ?></p>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p>No analyzed events.</p>
        <?php endif; ?>

        <h2>Correlated Threats</h2>
        <?php if (!empty($correlatedThreats)): ?>
        <?php foreach ($correlatedThreats as $threat): ?>
        <div class="event-card alert-<?php echo strtolower($threat['severity']); ?>">
            <p><strong>Type:</strong> <?php echo htmlspecialchars($threat['type']); ?></p>
            <p><strong>Severity:</strong> <?php echo htmlspecialchars($threat['severity']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($threat['description']); ?></p>
            <p><strong>Suggested Action:</strong> <?php echo htmlspecialchars($threat['suggested_action']); ?></p>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p>No correlated threats detected.</p>
        <?php endif; ?>

        <h2>Suspicious IP Addresses</h2>
        <pre><?php print_r(IntrusionDetector::getSuspiciousIps()); ?></pre>

    </div>
</body>

</html>