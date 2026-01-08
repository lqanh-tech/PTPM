<?php
require_once __DIR__ . '/database.php';

class MTonKho
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getTonKhoByIdHangHoa($idhanghoa)
    {
        $sql = "SELECT * FROM tonkho WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute([$idhanghoa]);
        return $stmt->fetch();
    }

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

    public function updateSoLuong($idhanghoa, $soLuongThayDoi, $isIncrement = true, $useExternalTransaction = false)
    {
        try {

            try {
                $this->db->exec("CREATE TABLE IF NOT EXISTS system_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    message TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            } catch (PDOException $e) {
                error_log("Error creating system_logs table: " . $e->getMessage());
            }

            $logMessage = "Updating tonkho for idhanghoa: " . $idhanghoa . ", soLuongThayDoi: " . $soLuongThayDoi . ", isIncrement: " . ($isIncrement ? "true" : "false");
            $this->logToDatabase($logMessage);

            error_log($logMessage);

            $tonkho = $this->getTonKhoByIdHangHoa($idhanghoa);

            if ($tonkho) {

                $oldSoLuong = $tonkho->soLuong;
                $newSoLuong = $isIncrement
                    ? $oldSoLuong + $soLuongThayDoi
                    : $oldSoLuong - $soLuongThayDoi;

                $newSoLuong = max(0, $newSoLuong);

                $logMessage = "Updating existing tonkho: old soLuong = " . $oldSoLuong . ", new soLuong = " . $newSoLuong;
                $this->logToDatabase($logMessage);
                error_log($logMessage);

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

                $logMessage = "Creating new tonkho entry for idhanghoa: " . $idhanghoa . " with soLuong: " . ($isIncrement ? $soLuongThayDoi : 0);
                $this->logToDatabase($logMessage);
                error_log($logMessage);

                try {
                    $checkTable = $this->db->query("SHOW TABLES LIKE 'tonkho'");
                    if ($checkTable->rowCount() == 0) {

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

                $needInternalTransaction = !$useExternalTransaction;
                if ($needInternalTransaction) {
                    $this->db->beginTransaction();
                }

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

    public function checkHangHoaExists($idhanghoa)
    {
        $sql = "SELECT COUNT(*) FROM tonkho WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idhanghoa]);
        return $stmt->fetchColumn() > 0;
    }

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
