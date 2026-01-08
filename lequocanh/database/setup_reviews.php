<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Setting up product reviews tables...\n\n";
    
    $sql = file_get_contents(__DIR__ . '/create_product_reviews.sql');
    
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        try {
            $db->exec($statement);
            $success_count++;
            echo "✓ Executed successfully\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠ Already exists (skipped)\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $error_count++;
            }
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Setup Complete!\n";
    echo "Successful: $success_count\n";
    echo "Errors: $error_count\n";
    echo str_repeat("=", 50) . "\n\n";
    
    echo "Verifying tables...\n";
    
    $tables = ['product_reviews', 'review_helpful'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' NOT found\n";
        }
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM hoadon LIKE 'trangthai'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'trangthai' exists in hoadon table\n";
    } else {
        echo "⚠ Column 'trangthai' NOT found - will try to add\n";
        try {
            $db->exec("ALTER TABLE hoadon ADD COLUMN trangthai ENUM('Đang xử lý','Đã xác nhận','Đang giao','Đã giao','Hoàn thành','Hủy') DEFAULT 'Đang xử lý'");
            echo "✓ Added 'trangthai' column successfully\n";
        } catch (PDOException $e) {
            echo "✗ Could not add column: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Database setup complete! You can now use the review system.\n";
    
} catch (Exception $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
