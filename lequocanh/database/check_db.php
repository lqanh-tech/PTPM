<?php
// Quick database check script
$pdo = new PDO('mysql:host=localhost;dbname=trainingdb;charset=utf8mb4', 'root', '');
echo "Database connection: OK\n\n";

$tables = ['provinces', 'districts', 'wards'];
foreach($tables as $t) {
    $r = $pdo->query("SHOW TABLES LIKE '$t'");
    $exists = $r->rowCount() > 0;
    echo "Table $t: " . ($exists ? 'EXISTS' : 'NOT FOUND');
    if ($exists) {
        $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo " ($count records)";
    }
    echo "\n";
}
