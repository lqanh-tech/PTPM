<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (file_exists(__DIR__ . '/db_config.php')) {
    $dbConfig = require __DIR__ . '/db_config.php';
    $dbConfig['username'] = $dbConfig['user'] ?? $dbConfig['username'];
    $dbConfig['password'] = $dbConfig['pass'] ?? $dbConfig['password'];
    $dbConfig['dbname'] = 'sales_management';
    $dbConfig['charset'] = 'utf8mb4';
} else {
    $dbConfig = [
        'host' => 'mysql',
        'dbname' => 'sales_management',
        'username' => 'app_user',
        'password' => 'app_password',
        'charset' => 'utf8mb4'
    ];
}

function logMessage($message) {
    echo $message . "\n";
}

try {
    logMessage("=== Ward Import Validation ===\n");
    
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $stats = [];
    $stats['provinces'] = $pdo->query("SELECT COUNT(*) FROM provinces")->fetchColumn();
    $stats['districts'] = $pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn();
    $stats['wards'] = $pdo->query("SELECT COUNT(*) FROM wards")->fetchColumn();
    
    logMessage("Total Counts:");
    logMessage("  Provinces: " . $stats['provinces']);
    logMessage("  Districts: " . $stats['districts']);
    logMessage("  Wards: " . $stats['wards']);
    
    $orphanedWards = $pdo->query("
        SELECT COUNT(*) 
        FROM wards w 
        LEFT JOIN districts d ON w.district_id = d.id 
        WHERE d.id IS NULL
    ")->fetchColumn();
    
    logMessage("\nData Integrity:");
    logMessage("  Orphaned wards (no district): " . $orphanedWards);
    
    $duplicateWards = $pdo->query("
        SELECT code, COUNT(*) as count 
        FROM wards 
        GROUP BY code 
        HAVING count > 1
    ")->fetchAll();
    
    logMessage("  Duplicate ward codes: " . count($duplicateWards));
    if (!empty($duplicateWards)) {
        foreach ($duplicateWards as $dup) {
            logMessage("    - Code {$dup['code']} appears {$dup['count']} times");
        }
    }
    
    logMessage("\nTop 10 Provinces by Ward Count:");
    $topProvinces = $pdo->query("
        SELECT p.name, COUNT(w.id) as ward_count
        FROM provinces p
        LEFT JOIN districts d ON d.province_id = p.id
        LEFT JOIN wards w ON w.district_id = d.id
        GROUP BY p.id, p.name
        ORDER BY ward_count DESC
        LIMIT 10
    ")->fetchAll();
    
    foreach ($topProvinces as $province) {
        logMessage(sprintf("  %-35s: %d wards", $province['name'], $province['ward_count']));
    }
    
    logMessage("\nSample Wards (First 5 from Hanoi):");
    $sampleWards = $pdo->query("
        SELECT w.code, w.name, d.name as district_name, p.name as province_name
        FROM wards w
        JOIN districts d ON w.district_id = d.id
        JOIN provinces p ON d.province_id = p.id
        WHERE p.code = '101' OR p.name LIKE '%Hà Nội%'
        LIMIT 5
    ")->fetchAll();
    
    foreach ($sampleWards as $ward) {
        logMessage(sprintf("  %s - %s (%s, %s)", 
            $ward['code'], 
            $ward['name'], 
            $ward['district_name'],
            $ward['province_name']
        ));
    }
    
    generateHTMLReport($pdo, $stats, $orphanedWards, $duplicateWards, $topProvinces, $sampleWards);
    
    logMessage("\n=== Validation Completed ===");
    logMessage("HTML report generated: ward_import_report.html");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}

function generateHTMLReport($pdo, $stats, $orphanedWards, $duplicateWards, $topProvinces, $sampleWards) {
    $reportFile = __DIR__ . '/ward_import_report.html';
    
    $allSamples = $pdo->query("
        SELECT w.code, w.name, d.name as district_name, p.name as province_name
        FROM wards w
        JOIN districts d ON w.district_id = d.id
        JOIN provinces p ON d.province_id = p.id
        ORDER BY p.code, d.code, w.code
        LIMIT 50
    ")->fetchAll();
    
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ward Import Report - <?= date('Y-m-d H:i:s') ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 { margin: 0; font-size: 14px; opacity: 0.9; }
        .stat-card .number { font-size: 36px; font-weight: bold; margin: 10px 0; }
        .success { color: #27ae60; }
        .warning { color: #f39c12; }
        .error { color: #e74c3c; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:hover { background-color: #f5f5f5; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge.success { background: #d4edda; color: #155724; }
        .badge.warning { background: #fff3cd; color: #856404; }
        .badge.error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Ward/Commune Import Report</h1>
        <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        
        <h2>Overview Statistics</h2>
        <div class="stats">
            <div class="stat-card">
                <h3>Provinces</h3>
                <div class="number"><?= $stats['provinces'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Districts</h3>
                <div class="number"><?= $stats['districts'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Wards/Communes</h3>
                <div class="number"><?= $stats['wards'] ?></div>
            </div>
        </div>
        
        <h2>Data Quality Checks</h2>
        <table>
            <tr>
                <th>Check</th>
                <th>Result</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Orphaned Wards (no district reference)</td>
                <td><?= $orphanedWards ?></td>
                <td><?= $orphanedWards == 0 ? '<span class="badge success">PASS</span>' : '<span class="badge error">FAIL</span>' ?></td>
            </tr>
            <tr>
                <td>Duplicate Ward Codes</td>
                <td><?= count($duplicateWards) ?></td>
                <td><?= count($duplicateWards) == 0 ? '<span class="badge success">PASS</span>' : '<span class="badge warning">WARNING</span>' ?></td>
            </tr>
            <tr>
                <td>Expected Ward Count (from CSV)</td>
                <td><?= $stats['wards'] ?> / ~3,325</td>
                <td><?= $stats['wards'] >= 3200 ? '<span class="badge success">PASS</span>' : '<span class="badge warning">CHECK</span>' ?></td>
            </tr>
        </table>
        
        <h2>Top 10 Provinces by Ward Count</h2>
        <table>
            <tr>
                <th>Province</th>
                <th>Ward Count</th>
            </tr>
            <?php foreach ($topProvinces as $province): ?>
            <tr>
                <td><?= htmlspecialchars($province['name']) ?></td>
                <td><?= $province['ward_count'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Sample Data (First 50 Wards)</h2>
        <table>
            <tr>
                <th>Code</th>
                <th>Ward Name</th>
                <th>District</th>
                <th>Province</th>
            </tr>
            <?php foreach ($allSamples as $ward): ?>
            <tr>
                <td><code><?= htmlspecialchars($ward['code']) ?></code></td>
                <td><?= htmlspecialchars($ward['name']) ?></td>
                <td><?= htmlspecialchars($ward['district_name']) ?></td>
                <td><?= htmlspecialchars($ward['province_name']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Test Queries</h2>
        <p>Use these SQL queries to verify the data:</p>
        <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;">
-- Count all wards
SELECT COUNT(*) as total_wards FROM wards;

-- Get wards for a specific province (e.g., Hanoi)
SELECT w.code, w.name, d.name as district, p.name as province
FROM wards w
JOIN districts d ON w.district_id = d.id
JOIN provinces p ON d.province_id = p.id
WHERE p.code = '101'
LIMIT 20;

-- Verify specific ward from CSV (Phường Hoàn Kiếm)
SELECT w.code, w.name, d.name as district, p.name as province
FROM wards w
JOIN districts d ON w.district_id = d.id
JOIN provinces p ON d.province_id = p.id
WHERE w.code = '10105001';
        </pre>
    </div>
</body>
</html>
    <?php
    $html = ob_get_clean();
    file_put_contents($reportFile, $html);
}
