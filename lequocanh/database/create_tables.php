<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbname = 'sales_management';

$configs = [
    ['host' => 'mysql', 'user' => 'app_user', 'pass' => 'app_password'],
    ['host' => 'mysql', 'user' => 'root', 'pass' => 'root'],
    ['host' => 'mysql', 'user' => 'root', 'pass' => 'pw'],
    ['host' => 'mysql', 'user' => 'app_user', 'pass' => 'pw'],
    ['host' => 'mysql', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
];

$pdo = null;
$connectedConfig = null;

echo "Attempting to connect to database '$dbname'...\n";

foreach ($configs as $config) {
    try {
        echo "Trying {$config['user']}@{$config['host']} with password '" . ($config['pass'] ? '***' : '(empty)') . "'... ";
        $dsn = "mysql:host={$config['host']};dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        echo "SUCCESS!\n";
        $connectedConfig = $config;
        break;
    } catch (PDOException $e) {
        echo "Failed: " . $e->getMessage() . "\n";
    }
}

if (!$pdo) {
    echo "\nFATAL ERROR: Could not connect with any credentials.\n";
    exit(1);
}

echo "\nUsing credentials: {$connectedConfig['user']}@{$connectedConfig['host']}\n";

try {

    $sql = "
    CREATE TABLE IF NOT EXISTS provinces (
        id INT PRIMARY KEY AUTO_INCREMENT,
        code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Mã tỉnh/thành',
        name VARCHAR(100) NOT NULL COMMENT 'Tên tiếng Việt',
        name_en VARCHAR(100) COMMENT 'Tên tiếng Anh',
        region VARCHAR(50) COMMENT 'Miền: Bắc/Trung/Nam',
        is_active TINYINT(1) DEFAULT 1 COMMENT '1: Hoạt động, 0: Không hoạt động',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_code (code),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách tỉnh/thành phố Việt Nam';

    CREATE TABLE IF NOT EXISTS districts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        province_id INT NOT NULL,
        code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Mã quận/huyện',
        name VARCHAR(100) NOT NULL COMMENT 'Tên tiếng Việt',
        name_en VARCHAR(100) COMMENT 'Tên tiếng Anh',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
        INDEX idx_province (province_id),
        INDEX idx_code (code),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách quận/huyện';

    CREATE TABLE IF NOT EXISTS wards (
        id INT PRIMARY KEY AUTO_INCREMENT,
        district_id INT NOT NULL,
        code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Mã phường/xã',
        name VARCHAR(100) NOT NULL COMMENT 'Tên tiếng Việt',
        name_en VARCHAR(100) COMMENT 'Tên tiếng Anh',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
        INDEX idx_district (district_id),
        INDEX idx_code (code),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách phường/xã';
    ";
    
    echo "Creating tables...\n";
    $pdo->exec($sql);
    echo "Tables created successfully.\n";
    
    $tables = ['provinces', 'districts', 'wards'];
    foreach ($tables as $t) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$t'");
        echo "Table '$t': " . ($stmt->rowCount() > 0 ? 'EXISTS' : 'NOT FOUND') . "\n";
    }
    
    $configContent = "<?php\nreturn " . var_export($connectedConfig, true) . ";\n";
    file_put_contents(__DIR__ . '/db_config.php', $configContent);
    echo "Saved working configuration to db_config.php\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
