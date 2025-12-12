<?php
require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$connection = $db->getConnection();

echo "Running SQL setup...\n\n";

$sql = file_get_contents('setup_review_tables_simple.sql');

// Split by semicolon but keep CREATE VIEW statements together
$statements = [];
$current = '';
$inView = false;

foreach (explode("\n", $sql) as $line) {
    $line = trim($line);
    
    // Skip comments and empty lines
    if (empty($line) || strpos($line, '--') === 0) continue;
    
    if (stripos($line, 'CREATE') !== false && stripos($line, 'VIEW') !== false) {
        $inView = true;
    }
    
    $current .= $line . "\n";
    
    if (strpos($line, ';') !== false) {
        if (!$inView || stripos($line, 'ORDER BY') !== false) {
            $statements[] = trim($current);
            $current = '';
            $inView = false;
        }
    }
}

$success = 0;
$errors = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    try {
        $connection->exec($statement);
        $success++;
        echo "✅ Success\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false ||
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "ℹ️  Already exists (skipped)\n";
        } else {
            echo "❌ Error: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

echo "\n=== Summary ===\n";
echo "Success: $success\n";
echo "Errors: $errors\n";
echo "\nSetup completed!\n";
