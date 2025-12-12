<?php
class Database
{
  private static $instance = null;
  private $conn = null;

  private function __construct()
  {
    // Load .env file nếu chưa load
    $this->ensureEnvLoaded();

    // Kiểm tra file config.ini có tồn tại không
    $configFile = __DIR__ . '/config.ini';
    if (!file_exists($configFile)) {
      error_log("File config.ini không tồn tại tại: $configFile");
      // Tạo config mặc định
      $this->createDefaultConfig($configFile);
    }

    $config = parse_ini_file($configFile, true);
    if (!$config || !isset($config['section'])) {
      error_log("Không thể đọc file config.ini hoặc thiếu section");
      throw new Exception("Lỗi cấu hình database");
    }

    // Đọc thông tin từ .env nếu có, nếu không thì từ config.ini, rồi mới dùng mặc định
    $servername = $_ENV['DB_HOST'] ?? $config['section']['servername'] ?? 'mysql';
    $dbname = $_ENV['DB_DATABASE'] ?? $config['section']['dbname'] ?? 'sales_management';
    $username = $_ENV['DB_USERNAME'] ?? $config['section']['username'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? $config['section']['password'] ?? 'pw';
    $port = $_ENV['DB_PORT'] ?? $config['section']['port'] ?? 3306;

    // Thử kết nối với nhiều cấu hình khác nhau
    $connectionConfigs = [
      ['host' => $servername, 'port' => $port, 'user' => $username, 'pass' => $password, 'dbname' => $dbname],
      ['host' => 'mysql', 'port' => 3306, 'user' => 'root', 'pass' => 'root', 'dbname' => $dbname],
      ['host' => 'mysql', 'port' => 3306, 'user' => 'root', 'pass' => 'pw', 'dbname' => $dbname],
      ['host' => 'mysql', 'port' => 3306, 'user' => 'app_user', 'pass' => 'pw', 'dbname' => $dbname],
      ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => 'root', 'dbname' => $dbname],
      ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => 'pw', 'dbname' => $dbname],
      ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => '', 'dbname' => $dbname],
      ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => 'root', 'dbname' => $dbname],
      ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => 'pw', 'dbname' => $dbname],
      ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => '', 'dbname' => $dbname]
    ];

    $connected = false;
    $connectionErrors = [];
    foreach ($connectionConfigs as $connConfig) {
      try {
        $dsn = "mysql:host={$connConfig['host']};port={$connConfig['port']};dbname=$dbname;charset=utf8mb4";
        $this->conn = new PDO($dsn, $connConfig['user'], $connConfig['pass']);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Test connection
        $this->conn->query("SELECT 1");

        // error_log("Kết nối thành công đến MySQL: {$connConfig['host']}:{$connConfig['port']}");
        $connected = true;
        break;
      } catch (PDOException $e) {
        $lastError = $e->getMessage();
        // error_log("Không thể kết nối đến MySQL {$connConfig['host']}:{$connConfig['port']}: " . $lastError);
        $connectionErrors[] = "{$connConfig['host']}:{$connConfig['port']} -> $lastError";
        continue;
      }
    }

    // Nếu tất cả cấu hình đều thất bại
    if (!$connected || !$this->conn) {
      $debug_info = "\n\n=== DEBUG INFO ===\n";
      $debug_info .= "Env vars:\n";
      $debug_info .= "  DB_HOST: " . (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'NOT SET') . "\n";
      $debug_info .= "  DB_DATABASE: " . (isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : 'NOT SET') . "\n";
      $debug_info .= "  DB_USERNAME: " . (isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'NOT SET') . "\n";
      $debug_info .= "  DB_PORT: " . (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : 'NOT SET') . "\n";
      $debug_info .= "Config vars:\n";
      $debug_info .= "  servername: $servername\n";
      $debug_info .= "  dbname: $dbname\n";
      $debug_info .= "  username: $username\n";
      $debug_info .= "  port: $port\n";

      $error_msg = "Không thể kết nối đến cơ sở dữ liệu. Vui lòng kiểm tra:\n";
      $error_msg .= "1. MySQL server đã được khởi động chưa\n";
      $error_msg .= "2. Thông tin kết nối trong config.ini có đúng không\n";
      $error_msg .= "3. Docker containers đã chạy chưa\n";
      $error_msg .= "4. XAMPP/WAMP MySQL service đã khởi động chưa\n";
      $error_msg .= "Chi tiết lỗi:\n" . implode("\n", $connectionErrors);
      $error_msg .= $debug_info;

      throw new Exception($error_msg);
    }
  }

  /**
   * Tạo file config mặc định
   */
  private function createDefaultConfig($configFile)
  {
    $defaultConfig = "[section]\n";
    $defaultConfig .= "; Cấu hình kết nối database\n";
    $defaultConfig .= "servername = localhost\n";
    $defaultConfig .= "port = 3306\n";
    $defaultConfig .= "dbname = sales_management\n";
    $defaultConfig .= "username = root\n";
    $defaultConfig .= "password = pw\n\n";
    $defaultConfig .= "[local]\n";
    $defaultConfig .= "servername = localhost\n";
    $defaultConfig .= "port = 3306\n";
    $defaultConfig .= "dbname = sales_management\n";
    $defaultConfig .= "username = root\n";
    $defaultConfig .= "password = pw\n";

    try {
      file_put_contents($configFile, $defaultConfig);
      error_log("Đã tạo file config.ini mặc định");
    } catch (Exception $e) {
      error_log("Không thể tạo file config.ini: " . $e->getMessage());
    }
  }

  public static function getInstance()
  {
    if (self::$instance == null) {
      self::$instance = new Database();
    }
    return self::$instance;
  }

  public function getConnection()
  {
    return $this->conn;
  }

  public function deleteAndUpdateID($userIdToDelete)
  {
    try {
      // Bắt đầu một giao dịch
      $this->conn->beginTransaction();

      // Xóa người dùng
      $sql = "DELETE FROM users WHERE id = :idToDelete";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute(['idToDelete' => $userIdToDelete]);

      // Cập nhật lại ID
      $sql = "SET @count = 0";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute();

      $sql = "UPDATE users SET id = @count:= @count + 1";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute();

      $sql = "ALTER TABLE users AUTO_INCREMENT = 1";
      $stmt = $this->conn->prepare($sql);
      $stmt->execute();

      // Hoàn tất giao dịch
      $this->conn->commit();

      return true;
    } catch (PDOException $e) {
      // Hủy giao dịch nếu có lỗi
      $this->conn->rollBack();
      echo "Lỗi: " . $e->getMessage();
      return false;
    }
  }

  public function addProduct($tenHangHoa, $giaHangHoa, $moTa, $hinhAnh)
  {
    try {
      // Bắt đầu một giao dịch
      $this->conn->beginTransaction();

      // Chuẩn bị câu lệnh SQL để thêm hàng hóa
      $sql = "INSERT INTO hang_hoa (ten_hang_hoa, gia_tham_khao, mo_ta, hinh_anh)
              VALUES (:ten_hang_hoa, :gia_tham_khao, :mo_ta, :hinh_anh)";

      $stmt = $this->conn->prepare($sql);

      // Thực thi với dữ liệu hàng hóa
      $stmt->execute([
        'ten_hang_hoa' => $tenHangHoa,
        'gia_tham_khao' => $giaHangHoa,
        'mo_ta' => $moTa,
        'hinh_anh' => $hinhAnh
      ]);

      // Lấy ID của hàng hóa vừa thêm
      $hangHoaId = $this->conn->lastInsertId();

      // Hoàn tất giao dịch
      $this->conn->commit();

      return $hangHoaId;
    } catch (PDOException $e) {
      // Hủy giao dịch nếu có lỗi
      $this->conn->rollBack();
      return false;
    }
  }

  /**
   * Đảm bảo .env được load
   */
  private function ensureEnvLoaded()
  {
    // Nếu DB_HOST đã set thì .env đã được load
    if (isset($_ENV['DB_HOST'])) {
      // error_log("✓ DB_HOST đã được set trong \$_ENV: " . $_ENV['DB_HOST']);
      return;
    }

    error_log("✗ DB_HOST chưa được set, tìm .env file...");

    // Tìm .env file từ project root
    $envPath = dirname(dirname(dirname(dirname(__DIR__)))) . '/.env';

    error_log("Tìm .env tại: $envPath");

    if (!file_exists($envPath)) {
      error_log("✗ File .env không tồn tại tại: $envPath");
      // Thử các đường dẫn khác
      $altPaths = [
        '/var/www/html/.env',
        __DIR__ . '/../../../.env',
        $_SERVER['DOCUMENT_ROOT'] . '/.env'
      ];

      foreach ($altPaths as $alt) {
        if (file_exists($alt)) {
          error_log("✓ Tìm thấy .env tại: $alt");
          $envPath = $alt;
          break;
        }
      }

      if (!file_exists($envPath)) {
        error_log("✗ Không tìm thấy .env file ở bất kỳ đường dẫn nào");
        return;
      }
    }

    // Load .env file thủ công
    error_log("Đọc .env từ: $envPath");
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $loaded = 0;
    foreach ($lines as $line) {
      // Skip comments
      if (strpos(trim($line), '#') === 0) {
        continue;
      }

      // Parse key=value pairs
      if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove quotes if present
        if (preg_match('/^(["\'])(.+)\1$/', $value, $matches)) {
          $value = $matches[2];
        }

        $_ENV[$key] = $value;
        putenv("$key=$value");

        // Log database credentials
        if (strpos($key, 'DB_') === 0) {
          $displayValue = (strpos($key, 'PASSWORD') !== false) ? '***' : $value;
          error_log("  ✓ Loaded $key = $displayValue");
        }

        $loaded++;
      }
    }

    error_log("✓ Đã load $loaded environment variables từ .env");

    // Verify
    if (isset($_ENV['DB_HOST'])) {
      error_log("✓ Xác nhận: DB_HOST = " . $_ENV['DB_HOST']);
    }
  }
}
