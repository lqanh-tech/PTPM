<?php
require_once __DIR__ . '/database.php';

class MTonKho
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Lấy thông tin tồn kho theo ID hàng hóa
    public function getTonKhoByIdHangHoa($idhanghoa)
    {
        $sql = "SELECT * FROM tonkho WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute([$idhanghoa]);
        return $stmt->fetch();
    }

    // Lấy tất cả thông tin tồn kho
    public function getAllTonKho()
    {
        $sql = "SELECT t.*, h.tenhanghoa, h.mota, dvt.tenDonViTinh
                FROM tonkho t
                LEFT JOIN hanghoa h ON t.idhanghoa = h.idhanghoa
                LEFT JOIN donvitinh dvt ON h.idDonViTinh = dvt.idDonViTinh
                ORDER BY t.idTonKho";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Cập nhật số lượng tồn kho
    public function updateSoLuong($idhanghoa, $soLuongThayDoi, $isIncrement = true, $useExternalTransaction = false)
    {
        try {
            // Tạo bảng system_logs nếu chưa tồn tại
            try {
                $this->db->exec("CREATE TABLE IF NOT EXISTS system_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    message TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            } catch (PDOException $e) {
                error_log("Error creating system_logs table: " . $e->getMessage());
            }

            // Ghi log vào bảng system_logs
            $logMessage = "Updating tonkho for idhanghoa: " . $idhanghoa . ", soLuongThayDoi: " . $soLuongThayDoi . ", isIncrement: " . ($isIncrement ? "true" : "false");
            $this->logToDatabase($logMessage);

            // Ghi log để debug
            error_log($logMessage);

            // Kiểm tra xem hàng hóa đã có trong bảng tồn kho chưa
            $tonkho = $this->getTonKhoByIdHangHoa($idhanghoa);

            if ($tonkho) {
                // Nếu đã có, cập nhật số lượng
                $oldSoLuong = $tonkho->soLuong;
                $newSoLuong = $isIncrement
                    ? $oldSoLuong + $soLuongThayDoi
                    : $oldSoLuong - $soLuongThayDoi;

                // Đảm bảo số lượng không âm
                $newSoLuong = max(0, $newSoLuong);

                $logMessage = "Updating existing tonkho: old soLuong = " . $oldSoLuong . ", new soLuong = " . $newSoLuong;
                $this->logToDatabase($logMessage);
                error_log($logMessage);

                // Chỉ sử dụng transaction nội bộ nếu không có transaction bên ngoài
                $needInternalTransaction = !$useExternalTransaction;
                if ($needInternalTransaction) {
                    $this->db->beginTransaction();
                }

                $sql = "UPDATE tonkho SET soLuong = ?, ngayCapNhat = CURRENT_TIMESTAMP WHERE idhanghoa = ?";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$newSoLuong, $idhanghoa]);

                if ($result) {
                    if ($needInternalTransaction && $this->db->inTransaction()) {
                        $this->db->commit();
                    }
                    $logMessage = "Update result: success, rows affected: " . $stmt->rowCount() . ", idhanghoa: " . $idhanghoa . ", new soLuong: " . $newSoLuong;
                } else {
                    if ($needInternalTransaction && $this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    $logMessage = "Update result: failed, idhanghoa: " . $idhanghoa;
                }

                $this->logToDatabase($logMessage);
                error_log($logMessage);

                return $result;
            } else {
                // Nếu chưa có, thêm mới vào bảng tồn kho
                $logMessage = "Creating new tonkho entry for idhanghoa: " . $idhanghoa . " with soLuong: " . ($isIncrement ? $soLuongThayDoi : 0);
                $this->logToDatabase($logMessage);
                error_log($logMessage);

                // Kiểm tra xem bảng tonkho có tồn tại không
                try {
                    $checkTable = $this->db->query("SHOW TABLES LIKE 'tonkho'");
                    if ($checkTable->rowCount() == 0) {
                        // Tạo bảng tonkho nếu chưa tồn tại
                        $logMessage = "Table tonkho does not exist, creating it";
                        $this->logToDatabase($logMessage);
                        error_log($logMessage);

                        $createTable = "CREATE TABLE IF NOT EXISTS tonkho (
                            idTonKho INT AUTO_INCREMENT PRIMARY KEY,
                            idhanghoa INT NOT NULL,
                            soLuong INT NOT NULL DEFAULT 0,
                            soLuongToiThieu INT NOT NULL DEFAULT 0,
                            viTri VARCHAR(255),
                            ngayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (idhanghoa) REFERENCES hanghoa(idhanghoa)
                        )";
                        $this->db->exec($createTable);
                    }
                } catch (PDOException $e) {
                    $logMessage = "Error checking/creating tonkho table: " . $e->getMessage();
                    $this->logToDatabase($logMessage);
                    error_log($logMessage);
                }

                // Chỉ sử dụng transaction nội bộ nếu không có transaction bên ngoài
                $needInternalTransaction = !$useExternalTransaction;
                if ($needInternalTransaction) {
                    $this->db->beginTransaction();
                }

                // Nếu là tăng số lượng, thêm mới với số lượng đã cho
                // Nếu là giảm số lượng, thêm mới với số lượng 0 (vì không thể giảm từ không có gì)
                $initialSoLuong = $isIncrement ? $soLuongThayDoi : 0;

                $sql = "INSERT INTO tonkho (idhanghoa, soLuong, soLuongToiThieu, viTri) VALUES (?, ?, 0, '')";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$idhanghoa, $initialSoLuong]);

                if ($result) {
                    if ($needInternalTransaction && $this->db->inTransaction()) {
                        $this->db->commit();
                    }
                    $logMessage = "Insert result: success, last insert ID: " . $this->db->lastInsertId() . ", idhanghoa: " . $idhanghoa . ", soLuong: " . $initialSoLuong;
                } else {
                    if ($needInternalTransaction && $this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    $logMessage = "Insert result: failed, idhanghoa: " . $idhanghoa;
                }

                $this->logToDatabase($logMessage);
                error_log($logMessage);

                return $result;
            }
        } catch (PDOException $e) {
            $logMessage = "Error updating tonkho: " . $e->getMessage();
            $this->logToDatabase($logMessage);
            error_log($logMessage);
            return false;
        }
    }

    // Ghi log vào bảng system_logs
    private function logToDatabase($message)
    {
        try {
            $sql = "INSERT INTO system_logs (message) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$message]);
        } catch (PDOException $e) {
            error_log("Error logging to database: " . $e->getMessage());
        }
    }

    // Cập nhật thông tin tồn kho
    public function updateTonKho($idTonKho, $soLuong, $soLuongToiThieu, $viTri)
    {
        try {
            $sql = "UPDATE tonkho
                    SET soLuong = ?, soLuongToiThieu = ?, viTri = ?, ngayCapNhat = CURRENT_TIMESTAMP
                    WHERE idTonKho = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$soLuong, $soLuongToiThieu, $viTri, $idTonKho]);
        } catch (PDOException $e) {
            error_log("Error updating tonkho: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra hàng hóa có tồn tại trong bảng tồn kho không
    public function checkHangHoaExists($idhanghoa)
    {
        $sql = "SELECT COUNT(*) FROM tonkho WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idhanghoa]);
        return $stmt->fetchColumn() > 0;
    }

    // Lấy danh sách hàng hóa sắp hết (số lượng dưới mức tối thiểu nhưng chưa hết hàng)
    public function getHangHoaSapHet()
    {
        $sql = "SELECT t.*, h.tenhanghoa, h.mota, dvt.tenDonViTinh
                FROM tonkho t
                LEFT JOIN hanghoa h ON t.idhanghoa = h.idhanghoa
                LEFT JOIN donvitinh dvt ON h.idDonViTinh = dvt.idDonViTinh
                WHERE t.soLuong > 0 AND t.soLuong <= t.soLuongToiThieu AND t.soLuongToiThieu > 0
                ORDER BY t.soLuong ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Lấy danh sách hàng hóa hết hàng (số lượng = 0)
    public function getHangHoaHetHang()
    {
        $sql = "SELECT t.*, h.tenhanghoa, h.mota, dvt.tenDonViTinh
                FROM tonkho t
                LEFT JOIN hanghoa h ON t.idhanghoa = h.idhanghoa
                LEFT JOIN donvitinh dvt ON h.idDonViTinh = dvt.idDonViTinh
                WHERE t.soLuong = 0
                ORDER BY t.idTonKho";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Lấy thông tin tồn kho theo ID tồn kho
    public function getTonKhoById($idTonKho)
    {
        try {
            $sql = "SELECT * FROM tonkho WHERE idTonKho = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute([$idTonKho]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting tonkho by ID: " . $e->getMessage());
            return null;
        }
    }
}
