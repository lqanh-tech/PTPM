<?php
/**
 * Province and District Import Script
 * Imports unique provinces and districts from CSV into database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Configuration
$csvFile = __DIR__ . '/../../Danh-muc-Phuong-xa_moi.csv';
$logFile = __DIR__ . '/import.log';
$dryRun = in_array('--dry-run', $argv);

// Database configuration
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

// Logger function
function logMessage($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry;
}

try {
    logMessage("=== Province/District Import Started ===");
    logMessage("Mode: " . ($dryRun ? 'DRY RUN' : 'EXECUTE'));
    
    // Check if CSV file exists
    if (!file_exists($csvFile)) {
        throw new Exception("CSV file not found: $csvFile");
    }
    
    // Connect to database
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    logMessage("Database connected successfully");
    
    // Begin transaction
    // if (!$dryRun) {
    //     $pdo->beginTransaction();
    // }
    
    // Read CSV and collect unique provinces and districts
    logMessage("\n=== Parsing CSV ===");
    $handle = fopen($csvFile, 'r');
    stream_filter_append($handle, 'convert.iconv.UTF-8/UTF-8');
    
    $provinces = [];
    $districts = [];
    $lineNumber = 0;
    
    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $lineNumber++;
        
        // Skip header rows
        if ($lineNumber <= 3 || empty($row[1])) {
            continue;
        }
        
        // Parse columns
        $provinceCodeBNV = trim($row[2] ?? '');
        $provinceName = trim($row[3] ?? '');
        $provinceCodeTMS = trim($row[4] ?? '');
        $districtCodeTMS = trim($row[5] ?? '');
        $districtName = trim($row[6] ?? '');
        $wardCode = trim($row[8] ?? '');
        
        // Skip if no ward code
        if (empty($wardCode)) {
            continue;
        }
        
        // Collect provinces (using TMS code as key)
        if (!empty($provinceCodeTMS) && !isset($provinces[$provinceCodeTMS])) {
            $provinces[$provinceCodeTMS] = [
                'code' => $provinceCodeTMS,
                'name' => $provinceName,
                'code_bnv' => $provinceCodeBNV
            ];
        }
        
        // Collect districts
        if (!empty($districtCodeTMS) && !isset($districts[$districtCodeTMS])) {
            $districts[$districtCodeTMS] = [
                'code' => $districtCodeTMS,
                'name' => $districtName,
                'province_code_tms' => $provinceCodeTMS
            ];
        }
    }
    
    fclose($handle);
    
    logMessage("Found " . count($provinces) . " unique provinces");
    logMessage("Found " . count($districts) . " unique districts");
    
    // Import provinces
    logMessage("\n=== Importing Provinces ===");
    $provinceMapping = []; // TMS code => DB ID
    $newProvinces = 0;
    $existingProvinces = 0;
    
    foreach ($provinces as $tmsCode => $province) {
        // Check if province already exists by code
        $stmt = $pdo->prepare("SELECT id FROM provinces WHERE code = ?");
        $stmt->execute([$tmsCode]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $provinceMapping[$tmsCode] = $existing['id'];
            $existingProvinces++;
            logMessage("Province exists: {$province['name']} (ID: {$existing['id']})");
        } else {
            if ($dryRun) {
                logMessage("[DRY RUN] Would insert province: {$province['name']} (code: $tmsCode)");
                $provinceMapping[$tmsCode] = 9999; // Dummy ID for dry run
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO provinces (code, name, is_active, created_at) 
                    VALUES (?, ?, 1, NOW())
                ");
                $stmt->execute([$tmsCode, $province['name']]);
                $newId = $pdo->lastInsertId();
                $provinceMapping[$tmsCode] = $newId;
                logMessage("Inserted province: {$province['name']} (ID: $newId)");
                $newProvinces++;
            }
        }
    }
    
    logMessage("Provinces - New: $newProvinces, Existing: $existingProvinces");
    
    // Import districts
    logMessage("\n=== Importing Districts ===");
    $districtMapping = []; // TMS code => DB ID
    $newDistricts = 0;
    $existingDistricts = 0;
    
    foreach ($districts as $tmsCode => $district) {
        // Get province_id from mapping
        $provinceId = $provinceMapping[$district['province_code_tms']] ?? null;
        
        if (!$provinceId) {
            logMessage("WARNING: Province not found for district {$district['name']}", 'WARNING');
            continue;
        }
        
        // Check if district already exists
        $stmt = $pdo->prepare("SELECT id FROM districts WHERE code = ?");
        $stmt->execute([$tmsCode]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $districtMapping[$tmsCode] = $existing['id'];
            $existingDistricts++;
            logMessage("District exists: {$district['name']} (ID: {$existing['id']})");
        } else {
            if ($dryRun) {
                logMessage("[DRY RUN] Would insert district: {$district['name']} (code: $tmsCode, province_id: $provinceId)");
                $districtMapping[$tmsCode] = 9999; // Dummy ID for dry run
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO districts (province_id, code, name, is_active, created_at) 
                    VALUES (?, ?, ?, 1, NOW())
");
                $stmt->execute([$provinceId, $tmsCode, $district['name']]);
                $newId = $pdo->lastInsertId();
                $districtMapping[$tmsCode] = $newId;
                logMessage("Inserted district: {$district['name']} (ID: $newId)");
                $newDistricts++;
            }
        }
    }
    
    logMessage("Districts - New: $newDistricts, Existing: $existingDistricts");
    
    // Save mapping for next step
    $mappingData = [
        'province_mapping' => $provinceMapping,
        'district_mapping' => $districtMapping,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    file_put_contents(__DIR__ . '/mapping.json', json_encode($mappingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    logMessage("Mapping saved to mapping.json");
    
    // Commit transaction
    // if (!$dryRun) {
    //     $pdo->commit();
    //     logMessage("\n=== Transaction committed successfully ===");
    // } else {
    //     logMessage("\n=== DRY RUN completed (no changes made) ===");
    // }
    
    logMessage("\n=== Import Completed Successfully ===");
    
} catch (Exception $e) {
    // if (isset($pdo) && $pdo->inTransaction()) {
    //     $pdo->rollBack();
    //     logMessage("Transaction rolled back", 'ERROR');
    // }
    logMessage("ERROR: " . $e->getMessage(), 'ERROR');
    // logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}
