<?php

$possible_paths = array(
    dirname(__FILE__) . '/database.php',
    dirname(dirname(dirname(__FILE__))) . '/elements_LQA/mod/database.php',
    dirname(dirname(dirname(dirname(__FILE__)))) . '/administrator/elements_LQA/mod/database.php'
);

$database_file = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $database_file = $path;
        break;
    }
}

if ($database_file === null) {
    die("Không thể tìm thấy file database.php");
}

require_once $database_file;

class NhatKyHoatDong
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS nhat_ky_hoat_dong (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                hanh_dong VARCHAR(100) NOT NULL,
                doi_tuong VARCHAR(50) NOT NULL,
                doi_tuong_id INT,
                chi_tiet TEXT,
                ip_address VARCHAR(50),
                thoi_gian TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_hanh_dong (hanh_dong),
                INDEX idx_doi_tuong (doi_tuong, doi_tuong_id),
                INDEX idx_thoi_gian (thoi_gian)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $this->db->exec($sql);
            return true;
        } catch (PDOException $e) {
            error_log("Lỗi khi tạo bảng nhat_ky_hoat_dong: " . $e->getMessage());
            return false;
        }
    }

    public function ghiNhatKy($username, $hanhDong, $doiTuong, $doiTuongId = null, $chiTiet = '')
    {
        try {
            $ipAddress = $this->getClientIP();

            $moDun = 'Hệ thống';
            if (strpos($username, 'manager') !== false) {
                $moDun = 'Quản lý';
            } elseif (strpos($username, 'staff') !== false) {
                $moDun = 'Nhân viên';
            } elseif ($username === 'admin') {
                $moDun = 'Quản trị';
            }

            $sql = "INSERT INTO nhat_ky_hoat_dong (username, hanh_dong, doi_tuong, doi_tuong_id, chi_tiet, mo_dun, ip_address)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $hanhDong, $doiTuong, $doiTuongId, $chiTiet, $moDun, $ipAddress]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Lỗi khi ghi nhật ký hoạt động: " . $e->getMessage());
            return false;
        }
    }

    public function layDanhSachNhatKy($filters = [], $limit = 100, $offset = 0)
    {
        try {

            $checkTableSql = "SHOW TABLES LIKE 'nhat_ky_hoat_dong'";
            $checkTableStmt = $this->db->prepare($checkTableSql);
            $checkTableStmt->execute();

            if ($checkTableStmt->rowCount() == 0) {

                $this->createTableIfNotExists();
                return [];
            }

            $whereClause = [];
            $params = [];

            if (isset($filters['username']) && !empty($filters['username'])) {
                $whereClause[] = "nk.username = ?";
                $params[] = $filters['username'];
            } elseif (isset($filters['username_in']) && is_array($filters['username_in']) && !empty($filters['username_in'])) {

                $placeholders = implode(',', array_fill(0, count($filters['username_in']), '?'));
                $whereClause[] = "nk.username IN ($placeholders)";
                $params = array_merge($params, $filters['username_in']);
            }

            if (isset($filters['hanh_dong']) && !empty($filters['hanh_dong'])) {
                $whereClause[] = "nk.hanh_dong = ?";
                $params[] = $filters['hanh_dong'];
            }

            if (isset($filters['doi_tuong']) && !empty($filters['doi_tuong'])) {
                $whereClause[] = "nk.doi_tuong = ?";
                $params[] = $filters['doi_tuong'];
            }

            if (isset($filters['doi_tuong_id']) && !empty($filters['doi_tuong_id'])) {
                $whereClause[] = "nk.doi_tuong_id = ?";
                $params[] = $filters['doi_tuong_id'];
            }

            if (isset($filters['tu_ngay']) && !empty($filters['tu_ngay'])) {
                $whereClause[] = "nk.thoi_gian >= ?";
                $params[] = $filters['tu_ngay'] . ' 00:00:00';
            }

            if (isset($filters['den_ngay']) && !empty($filters['den_ngay'])) {
                $whereClause[] = "nk.thoi_gian <= ?";
                $params[] = $filters['den_ngay'] . ' 23:59:59';
            }

            $where = count($whereClause) > 0 ? "WHERE " . implode(" AND ", $whereClause) : "";

            $checkUserTableSql = "SHOW TABLES LIKE 'user'";
            $checkUserTableStmt = $this->db->prepare($checkUserTableSql);
            $checkUserTableStmt->execute();

            $checkNhanVienTableSql = "SHOW TABLES LIKE 'nhanvien'";
            $checkNhanVienTableStmt = $this->db->prepare($checkNhanVienTableSql);
            $checkNhanVienTableStmt->execute();

            $sql = "SELECT nk.*
                    FROM nhat_ky_hoat_dong nk
                    $where
                    ORDER BY nk.thoi_gian DESC
                    LIMIT $limit OFFSET $offset";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy danh sách nhật ký hoạt động: " . $e->getMessage());
            return [];
        }
    }

    public function demTongSoNhatKy($filters = [])
    {
        try {

            $checkTableSql = "SHOW TABLES LIKE 'nhat_ky_hoat_dong'";
            $checkTableStmt = $this->db->prepare($checkTableSql);
            $checkTableStmt->execute();

            if ($checkTableStmt->rowCount() == 0) {

                $this->createTableIfNotExists();
                return 0;
            }

            $whereClause = [];
            $params = [];

            if (isset($filters['username']) && !empty($filters['username'])) {
                $whereClause[] = "username = ?";
                $params[] = $filters['username'];
            } elseif (isset($filters['username_in']) && is_array($filters['username_in']) && !empty($filters['username_in'])) {

                $placeholders = implode(',', array_fill(0, count($filters['username_in']), '?'));
                $whereClause[] = "username IN ($placeholders)";
                $params = array_merge($params, $filters['username_in']);
            }

            if (isset($filters['hanh_dong']) && !empty($filters['hanh_dong'])) {
                $whereClause[] = "hanh_dong = ?";
                $params[] = $filters['hanh_dong'];
            }

            if (isset($filters['doi_tuong']) && !empty($filters['doi_tuong'])) {
                $whereClause[] = "doi_tuong = ?";
                $params[] = $filters['doi_tuong'];
            }

            if (isset($filters['doi_tuong_id']) && !empty($filters['doi_tuong_id'])) {
                $whereClause[] = "doi_tuong_id = ?";
                $params[] = $filters['doi_tuong_id'];
            }

            if (isset($filters['tu_ngay']) && !empty($filters['tu_ngay'])) {
                $whereClause[] = "thoi_gian >= ?";
                $params[] = $filters['tu_ngay'] . ' 00:00:00';
            }

            if (isset($filters['den_ngay']) && !empty($filters['den_ngay'])) {
                $whereClause[] = "thoi_gian <= ?";
                $params[] = $filters['den_ngay'] . ' 23:59:59';
            }

            $where = count($whereClause) > 0 ? "WHERE " . implode(" AND ", $whereClause) : "";

            $sql = "SELECT COUNT(*) as total FROM nhat_ky_hoat_dong $where";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Lỗi khi đếm tổng số nhật ký hoạt động: " . $e->getMessage());
            return 0;
        }
    }

    public function getActivityById($id)
    {
        try {

            $checkTableSql = "SHOW TABLES LIKE 'nhat_ky_hoat_dong'";
            $checkTableStmt = $this->db->prepare($checkTableSql);
            $checkTableStmt->execute();

            if ($checkTableStmt->rowCount() == 0) {

                $this->createTableIfNotExists();
                return false;
            }

            $sql = "SELECT * FROM nhat_ky_hoat_dong WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy chi tiết nhật ký hoạt động: " . $e->getMessage());
            return false;
        }
    }

    private function getClientIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        return $ip;
    }
}
