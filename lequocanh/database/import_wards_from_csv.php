<?php
/**
 * Ward/Commune Import Script
 * Imports 3,325 wards from CSV into database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Ho_Chi_Minh');
set_time_limit(300); // 5 minutes

// Configuration
$csvFile = __DIR__ . '/../../Danh-muc-Phuong-xa_moi.csv';
$mappingFile = __DIR__ . '/mapping.json';
$logFile = __DIR__ . '/import.log';
$dryRun = in_array('--dry-run', $argv);
$batchSize = 100;

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
    logMessage("=== Ward/Commune Import Started ===");
    logMessage("Mode: " . ($dryRun ? 'DRY RUN' : 'EXECUTE'));
    logMessage("Batch size: $batchSize");
    
    // Check if CSV file exists
    if (!file_exists($csvFile)) {
        throw new Exception("CSV file not found: $csvFile");
    }
    
    // Load mapping data
    if (!file_exists($mappingFile)) {
        throw new Exception("Mapping file not found! Please run import_provinces_districts.php first.");
    }
    
    $mappingData = json_decode(file_get_contents($mappingFile), true);
    $districtMapping = $mappingData['district_mapping'] ?? [];
    
    if (empty($districtMapping)) {
        throw new Exception("District mapping is empty! Please run import_provinces_districts.php first.");
    }
    
    logMessage("Loaded mapping for " . count($districtMapping) . " districts");
    
    // Connect to database
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    logMessage("Database connected successfully");
    
    // Read CSV and prepare wards
    logMessage("\n=== Parsing CSV ===");
    $handle = fopen($csvFile, 'r');
    stream_filter_append($handle, 'convert.iconv.UTF-8/UTF-8');
    
    $wards = [];
    $lineNumber = 0;
    $skippedCount = 0;
    $specialNotesCount = 0;
    
    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $lineNumber++;
        
        // Skip header rows and empty rows
        if ($lineNumber <= 3 || empty($row[1])) {
            continue;
        }
        
        // Parse columns
        $districtCodeTMS = trim($row[5] ?? '');
        $wardCode = trim($row[8] ?? '');
        $wardName = trim($row[9] ?? '');
        $reviewStatus = trim($row[10] ?? '');
        
        // Skip if no ward code
        if (empty($wardCode) || empty($wardName)) {
            $skippedCount++;
            continue;
        }
        
        // Get district_id from mapping
        $districtId = $districtMapping[$districtCodeTMS] ?? null;
        
        if (!$districtId) {
            logMessage("WARNING: District mapping not found for TMS code: $districtCodeTMS (Ward: $wardName)", 'WARNING');
            $skippedCount++;
            continue;
        }
        
        // Track special notes
        if (!empty($reviewStatus)) {
            $specialNotesCount++;
            logMessage("Special note (line $lineNumber): $wardName - $reviewStatus", 'INFO');
        }
        
        $wards[] = [
            'code' => $wardCode,
            'name' => $wardName,
            'district_id' => $districtId,
            'line_number' => $lineNumber,
            'review_status' => $reviewStatus
        ];
    }
    
    fclose($handle);
    
    logMessage("Total wards parsed: " . count($wards));
    logMessage("Skipped rows: $skippedCount");
    logMessage("Special notes: $specialNotesCount");
    
    // Import wards in batches
    logMessage("\n=== Importing Wards ===");
    
    $totalWards = count($wards);
    $insertedCount = 0;
    $updatedCount = 0;
    $errorCount = 0;
    $batches = array_chunk($wards, $batchSize);
    $batchNumber = 0;
    
    foreach ($batches as $batch) {
        $batchNumber++;
        
        // if (!$dryRun) {
        //     $pdo->beginTransaction();
        // }
        
        try {
            foreach ($batch as $ward) {
                // Check if ward already exists
                $stmt = $pdo->prepare("SELECT id FROM wards WHERE code = ?");
                $stmt->execute([$ward['code']]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing ward
                    if ($dryRun) {
                        logMessage("[DRY RUN] Would update ward: {$ward['name']} (code: {$ward['code']})");
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE wards 
                            SET name = ?, district_id = ?, updated_at = NOW() 
                            WHERE code = ?
                        ");
                        $stmt->execute([$ward['name'], $ward['district_id'], $ward['code']]);
                        $updatedCount++;
                    }
                } else {
                    // Insert new ward
                    if ($dryRun) {
                        logMessage("[DRY RUN] Would insert ward: {$ward['name']} (code: {$ward['code']}, district_id: {$ward['district_id']})");
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO wards (district_id, code, name, is_active, created_at) 
                            VALUES (?, ?, ?, 1, NOW())
                        ");
                        $stmt->execute([$ward['district_id'], $ward['code'], $ward['name']]);
                        $insertedCount++;
                    }
                }
            }
            
            // if (!$dryRun) {
            //     $pdo->commit();
            // }
            
            $progress = round(($batchNumber / count($batches)) * 100);
            logMessage("Batch $batchNumber/" . count($batches) . " completed ($progress%) - Inserted: $insertedCount, Updated: $updatedCount");
            
        } catch (Exception $e) {
            // if (!$dryRun && $pdo->inTransaction()) {
            //     $pdo->rollBack();
            // }
            logMessage("ERROR in batch $batchNumber: " . $e->getMessage(), 'ERROR');
            $errorCount += count($batch);
            
            // Continue with next batch instead of failing completely
            continue;
        }
    }
    
    // Final summary
    logMessage("\n=== Import Summary ===");
    logMessage("Total wards in CSV: $totalWards");
    logMessage("Inserted: $insertedCount");
    logMessage("Updated: $updatedCount");
    logMessage("Errors: $errorCount");
    logMessage("Skipped: $skippedCount");
    
    if ($dryRun) {
        logMessage("\n=== DRY RUN completed (no changes made) ===");
    } else {
        logMessage("\n=== Import Completed Successfully ===");
    }
    
} catch (Exception $e) {
    // if (isset($pdo) && $pdo->inTransaction()) {
    //     $pdo->rollBack();
    // }
    logMessage("FATAL ERROR: " . $e->getMessage(), 'ERROR');
    // logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}
