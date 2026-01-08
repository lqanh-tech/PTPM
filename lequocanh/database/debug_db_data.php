<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$configFile = __DIR__ . '/db_config.php';
if (!file_exists($configFile)) {
    die("Config file not found: $configFile\n");
}

$dbConfig = require $configFile;
$dsn = "mysql:host={$dbConfig['host']};dbname=sales_management;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected to database.\n";

    $stmt = $pdo->query("SELECT * FROM provinces ORDER BY id ASC LIMIT 1");
    $province = $stmt->fetch();
    
    if ($province) {
        echo "Found Province: " . json_encode($province, JSON_UNESCAPED_UNICODE) . "\n";
        
        $stmt = $pdo->prepare("SELECT * FROM districts WHERE province_id = ? LIMIT 5");
        $stmt->execute([$province['id']]);
        $districts = $stmt->fetchAll();
        
        echo "Found " . count($districts) . " districts for province ID {$province['id']}:\n";
        foreach ($districts as $d) {
            echo " - " . json_encode($d, JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        if (count($districts) == 0) {
            echo "WARNING: No districts found for province {$province['id']}!\n";

            $count = $pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn();
            echo "Total districts in table: $count\n";
        }
    } else {
        echo "ERROR: No provinces found!\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
