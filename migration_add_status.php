<?php

/**
 * Add product status column to hanghoa table
 * Status values: 1 = Đang bán (Active), 2 = Ngừng bán (Discontinued), 3 = Hết hàng (Out of Stock)
 * Default: 1 (Đang bán)
 */

require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

try {
    echo "=== Adding status column to hanghoa table ===\n";

    // Check if column already exists
    $checkColumn = $db->query("SHOW COLUMNS FROM hanghoa LIKE 'trang_thai'");
    if ($checkColumn->rowCount() > 0) {
        echo "❌ Column 'trang_thai' already exists!\n";
        exit(1);
    }

    // Add the new column
    $sql = "ALTER TABLE hanghoa 
            ADD COLUMN trang_thai TINYINT(1) DEFAULT 1 COMMENT 'Product status: 1=Đang bán, 2=Ngừng bán, 3=Hết hàng' 
            AFTER noibat";

    $db->exec($sql);
    echo "✅ Column 'trang_thai' added successfully!\n";

    // Verify column was added
    $checkColumn = $db->query("SHOW COLUMNS FROM hanghoa LIKE 'trang_thai'");
    $colInfo = $checkColumn->fetch(PDO::FETCH_ASSOC);
    echo "Column info: " . print_r($colInfo, true) . "\n";

    // Show updated table structure
    echo "\n=== Updated hanghoa table structure ===\n";
    $columns = $db->query("SHOW COLUMNS FROM hanghoa");
    $result = $columns->fetchAll(PDO::FETCH_ASSOC);

    $statusCols = array_filter($result, function ($col) {
        return in_array($col['Field'], ['noibat', 'trang_thai', 'created_at']);
    });

    foreach ($statusCols as $col) {
        echo $col['Field'] . " | " . $col['Type'] . " | Default: " . $col['Default'] . "\n";
    }

    echo "\n✅ Migration completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
