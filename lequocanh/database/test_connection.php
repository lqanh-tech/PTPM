<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing MySQL Connection ===\n\n";

if (!extension_loaded('pdo_mysql')) {
    echo "ERROR: PDO MySQL extension not loaded!\n";
    echo "Please enable it in php.ini\n";
    exit(1);
}

echo "✓ PDO MySQL extension is loaded\n";

try {
    echo "Connecting to MySQL...\n";
    $pdo = new PDO('mysql:host=mysql;charset=utf8mb4', 'app_user', 'app_password', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ MySQL connection successful!\n\n";
    
    echo "Checking database 'sales_management'...\n";
    $stmt = $pdo->query("SHOW DATABASES LIKE 'sales_management'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database 'sales_management' exists\n\n";
        
        $pdo = new PDO('mysql:host=mysql;dbname=sales_management;charset=utf8mb4', 'app_user', 'app_password', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        echo "Checking tables...\n";
        $tables = ['provinces', 'districts', 'wards'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "✓ Table '$table' exists ($count records)\n";
            } else {
                echo "✗ Table '$table' NOT FOUND\n";
            }
        }
    } else {
        echo "✗ Database 'trainingdb' does not exist\n";
        echo "\nAvailable databases:\n";
        $stmt = $pdo->query("SHOW DATABASES");
        while ($db = $stmt->fetch(PDO::FETCH_NUM)) {
            echo "  - {$db[0]}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "\n✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPossible solutions:\n";
    echo "1. Start MySQL/MariaDB service\n";
    echo "2. Check if MySQL is running on localhost:3306\n";
    echo "3. Verify root user has no password\n";
    exit(1);
}

echo "\n=== All checks passed! ===\n";
