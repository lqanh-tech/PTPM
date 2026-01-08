<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Ho_Chi_Minh');

$csvFile = __DIR__ . '/../../Danh-muc-Phuong-xa_moi.csv';
$logFile = __DIR__ . '/import.log';

$dbConfig = [
    'host' => 'mysql',
    'dbname' => 'sales_management',
    'username' => 'app_user',
    'password' => 'app_password',
    'charset' => 'utf8mb4'
];

function logMessage($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry;
}

try {
    logMessage("=== CSV Data Analysis Started ===");
    
    if (!file_exists($csvFile)) {
        throw new Exception("CSV file not found: $csvFile");
    }
    
    logMessage("CSV file found: $csvFile");
    logMessage("File size: " . number_format(filesize($csvFile)) . " bytes");
    
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    logMessage("Database connected successfully");
    
    $requiredTables = ['provinces', 'districts', 'wards'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            logMessage("WARNING: Table '$table' does not exist!", 'WARNING');
        } else {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            logMessage("Table '$table' exists with $count records");
        }
    }
    
    logMessage("\n=== Analyzing CSV Data ===");
    
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        throw new Exception("Cannot open CSV file");
    }
    
    stream_filter_append($handle, 'convert.iconv.UTF-8/UTF-8');
    
    $provinces = [];
    $districts = [];
    $wards = [];
    $lineNumber = 0;
    $dataStarted = false;
    $specialNotes = [];
    
    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $lineNumber++;
        
        if ($lineNumber <= 3 || empty($row[1])) {
            continue;
        }
        
        $dataStarted = true;
        
        $stt = trim($row[1] ?? '');
        $provinceCodeBNV = trim($row[2] ?? '');
        $provinceName = trim($row[3] ?? '');
        $provinceCodeTMS = trim($row[4] ?? '');
        $districtCodeTMS = trim($row[5] ?? '');
        $districtName = trim($row[6] ?? '');
        $autoIncrement = trim($row[7] ?? '');
        $wardCode = trim($row[8] ?? '');
        $wardName = trim($row[9] ?? '');
        $reviewStatus = trim($row[10] ?? '');
        
        if (empty($wardCode)) {
            continue;
        }
        
        if (!isset($provinces[$provinceCodeTMS])) {
            $provinces[$provinceCodeTMS] = [
                'code_tms' => $provinceCodeTMS,
                'code_bnv' => $provinceCodeBNV,
                'name' => $provinceName,
                'count' => 0
            ];
        }
        $provinces[$provinceCodeTMS]['count']++;
        
        if (!isset($districts[$districtCodeTMS])) {
            $districts[$districtCodeTMS] = [
                'code' => $districtCodeTMS,
                'name' => $districtName,
                'province_code_tms' => $provinceCodeTMS,
                'count' => 0
            ];
        }
        $districts[$districtCodeTMS]['count']++;
        
        $wards[$wardCode] = [
            'code' => $wardCode,
            'name' => $wardName,
            'district_code_tms' => $districtCodeTMS,
            'auto_increment' => $autoIncrement,
            'review_status' => $reviewStatus
        ];
        
        if (!empty($reviewStatus)) {
            $specialNotes[] = [
                'line' => $lineNumber,
                'ward_code' => $wardCode,
                'ward_name' => $wardName,
                'note' => $reviewStatus
            ];
        }
    }
    
    fclose($handle);
    
    logMessage("\n=== CSV Statistics ===");
    logMessage("Total lines processed: " . ($lineNumber - 3));
    logMessage("Unique provinces: " . count($provinces));
    logMessage("Unique districts: " . count($districts));
    logMessage("Total wards: " . count($wards));
    logMessage("Special notes found: " . count($specialNotes));
    
    logMessage("\n=== Top 10 Provinces by Ward Count ===");
    uasort($provinces, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    $topProvinces = array_slice($provinces, 0, 10, true);
    foreach ($topProvinces as $code => $info) {
        logMessage(sprintf("%-30s (TMS: %s): %d wards", $info['name'], $code, $info['count']));
    }
    
    if (!empty($specialNotes)) {
        logMessage("\n=== Special Notes/Warnings ===");
        foreach ($specialNotes as $note) {
            logMessage(sprintf("Line %d - %s (%s): %s", 
                $note['line'], 
                $note['ward_name'], 
                $note['ward_code'],
                $note['note']
            ), 'WARNING');
        }
    }
    
    logMessage("\n=== Database Mapping Analysis ===");
    
    $stmt = $pdo->query("SELECT id, code, name FROM provinces");
    $dbProvinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logMessage("Provinces in database: " . count($dbProvinces));
    
    $stmt = $pdo->query("SELECT id, code, name, province_id FROM districts");
    $dbDistricts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logMessage("Districts in database: " . count($dbDistricts));
    
    $analysisData = [
        'csv_provinces' => $provinces,
        'csv_districts' => $districts,
        'csv_wards' => $wards,
        'db_provinces' => $dbProvinces,
        'db_districts' => $dbDistricts,
        'special_notes' => $specialNotes,
        'stats' => [
            'total_provinces' => count($provinces),
            'total_districts' => count($districts),
            'total_wards' => count($wards),
            'db_provinces' => count($dbProvinces),
            'db_districts' => count($dbDistricts)
        ]
    ];
    
    $jsonFile = __DIR__ . '/csv_analysis.json';
    file_put_contents($jsonFile, json_encode($analysisData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    logMessage("Analysis data saved to: $jsonFile");
    
    logMessage("\n=== Analysis Completed Successfully ===");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}
