<?php
$s = '../../elements_LQA/mod/database.php';
if (file_exists($s)) {
    $f = $s;
} else {
    $f = './elements_LQA/mod/database.php';
    if (!file_exists($f)) {
        $f = './administrator/elements_LQA/mod/database.php';
    }
}
require_once $f;

class MPhieuNhap
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
            $sql = "CREATE TABLE IF NOT EXISTS mphieunhap (
                idPhieuNhap INT AUTO_INCREMENT PRIMARY KEY,
                maPhieuNhap VARCHAR(50) NOT NULL,
                ngayNhap DATETIME DEFAULT CURRENT_TIMESTAMP,
                idNhanVien INT,
                idNCC INT,
                tongTien DECIMAL(15,2) DEFAULT 0.00,
                ghiChu TEXT,
                trangThai TINYINT(1) DEFAULT 0 COMMENT '0: Chờ duyệt, 1: Đã duyệt, 2: Đã hủy',
                FOREIGN KEY (idNhanVien) REFERENCES nhanvien(idNhanVien),
                FOREIGN KEY (idNCC) REFERENCES nhacungcap(idNCC)
            )";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating mphieunhap table: " . $e->getMessage());
        }
    }

    public function getAllPhieuNhap()
    {
        $sql = "SELECT pn.*, nv.tenNV, ncc.tenNCC
                FROM mphieunhap pn
                LEFT JOIN nhanvien nv ON pn.idNhanVien = nv.idNhanVien
                LEFT JOIN nhacungcap ncc ON pn.idNCC = ncc.idNCC
                ORDER BY pn.idPhieuNhap DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPhieuNhapById($idPhieuNhap)
    {
        $sql = "SELECT pn.*, nv.tenNV, ncc.tenNCC
                FROM mphieunhap pn
                LEFT JOIN nhanvien nv ON pn.idNhanVien = nv.idNhanVien
                LEFT JOIN nhacungcap ncc ON pn.idNCC = ncc.idNCC
                WHERE pn.idPhieuNhap = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute([$idPhieuNhap]);
        return $stmt->fetch();
    }

    public function addPhieuNhap($maPhieuNhap, $idNhanVien, $idNCC, $ghiChu)
    {
        try {
            $sql = "INSERT INTO mphieunhap (maPhieuNhap, idNhanVien, idNCC, ghiChu, trangThai)
                    VALUES (?, ?, ?, ?, 0)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$maPhieuNhap, $idNhanVien, $idNCC, $ghiChu]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error adding phieu nhap: " . $e->getMessage());
            return false;
        }
    }

    public function updatePhieuNhap($idPhieuNhap, $maPhieuNhap, $idNhanVien, $idNCC, $ghiChu)
    {
        try {
            $sql = "UPDATE mphieunhap
                    SET maPhieuNhap = ?, idNhanVien = ?, idNCC = ?, ghiChu = ?
                    WHERE idPhieuNhap = ? AND trangThai = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$maPhieuNhap, $idNhanVien, $idNCC, $ghiChu, $idPhieuNhap]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error updating phieu nhap: " . $e->getMessage());
            return false;
        }
    }

    public function deletePhieuNhap($idPhieuNhap)
    {
        try {

            $sql = "DELETE FROM mphieunhap WHERE idPhieuNhap = ? AND trangThai = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPhieuNhap]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error deleting phieu nhap: " . $e->getMessage());
            return false;
        }
    }

    public function approvePhieuNhap($idPhieuNhap)
    {
        try {

            error_log("Starting approval process for phieu nhap ID: " . $idPhieuNhap);

            if (!class_exists('MTonKho')) {
                require_once 'mtonkhoCls.php';
                error_log("MTonKho class loaded");
            }

            $this->db->beginTransaction();
            error_log("Transaction started");

            $sql = "UPDATE mphieunhap SET trangThai = 1 WHERE idPhieuNhap = ? AND trangThai = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPhieuNhap]);

            $rowsAffected = $stmt->rowCount();
            error_log("Update phieu nhap status: rows affected = " . $rowsAffected);

            if ($rowsAffected > 0) {

                $sql = "SELECT * FROM mchitietphieunhap WHERE idPhieuNhap = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$idPhieuNhap]);
                $chiTietList = $stmt->fetchAll(PDO::FETCH_OBJ);

                error_log("Found " . count($chiTietList) . " detail items for phieu nhap ID: " . $idPhieuNhap);

                $tonkhoObj = new MTonKho();

                if (!class_exists('hanghoa')) {
                    require_once 'hanghoaCls.php';
                    error_log("hanghoa class loaded");
                }
                $hanghoaObj = new hanghoa();

                foreach ($chiTietList as $chiTiet) {

                    error_log("Processing detail item: idCTPN = " . $chiTiet->idCTPN .
                        ", idhanghoa = " . $chiTiet->idhanghoa .
                        ", soLuong = " . $chiTiet->soLuong .
                        ", giaNhap = " . $chiTiet->giaNhap);

                    $updateResult = $tonkhoObj->updateSoLuong($chiTiet->idhanghoa, $chiTiet->soLuong, true, true);
                    error_log("Update tonkho result: " . ($updateResult ? "success" : "failed"));

                    if (!class_exists('PriceLogicConfig')) {
                        require_once dirname(__FILE__) . '/../config/price_logic_config.php';
                    }
                    if (!class_exists('Dongia')) {
                        require_once 'dongiaCls.php';
                    }

                    $dongiaObj = new Dongia();
                    $currentActivePrice = $dongiaObj->DongiaGetActiveByProduct($chiTiet->idhanghoa);
                    $hasActivePrice = ($currentActivePrice !== false);

                    if (PriceLogicConfig::shouldUpdateReferencePrice($hasActivePrice)) {
                        $priceToUpdate = $chiTiet->giaNhap;

                        if (PriceLogicConfig::AUTO_APPLY_PROFIT_MARGIN) {
                            $priceToUpdate = PriceLogicConfig::calculateSellingPrice($chiTiet->giaNhap);
                        }

                        $updatePriceResult = $hanghoaObj->HanghoaUpdatePrice($chiTiet->idhanghoa, $priceToUpdate);
                        error_log("Update hanghoa price result: " . ($updatePriceResult ? "success" : "failed") .
                            ", idhanghoa = " . $chiTiet->idhanghoa .
                            ", import price = " . $chiTiet->giaNhap .
                            ", selling price = " . $priceToUpdate);
                    }

                    if (PriceLogicConfig::shouldCreatePriceFromImport() && !$hasActivePrice) {
                        $sellingPrice = PriceLogicConfig::calculateSellingPrice($chiTiet->giaNhap);
                        $ngayApDung = date('Y-m-d');
                        $ngayKetThuc = date('Y-m-d', strtotime('+1 year'));
                        $ghiChu = "Tự động tạo từ phiếu nhập - Giá nhập: " . number_format($chiTiet->giaNhap) . " VNĐ";

                        $addPriceResult = $dongiaObj->DongiaAdd(
                            $chiTiet->idhanghoa,
                            $sellingPrice,
                            $ngayApDung,
                            $ngayKetThuc,
                            '',
                            $ghiChu
                        );

                        error_log("Auto create price result: " . ($addPriceResult ? "success" : "failed") .
                            ", idhanghoa = " . $chiTiet->idhanghoa .
                            ", import price = " . $chiTiet->giaNhap .
                            ", auto selling price = " . $sellingPrice);
                    } else if ($hasActivePrice) {
                        error_log("Skipped price creation for product " . $chiTiet->idhanghoa .
                            " because it has active price: " . $currentActivePrice->giaBan .
                            " (import price was: " . $chiTiet->giaNhap . ")");
                    }
                }

                $this->db->commit();
                error_log("Transaction committed successfully");
                return true;
            } else {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                error_log("No rows affected when updating phieu nhap status, transaction rolled back");
                return false;
            }
        } catch (PDOException $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
                error_log("Transaction rolled back due to PDO error");
            }
            error_log("Error approving phieu nhap: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
                error_log("Transaction rolled back due to general error");
            }
            error_log("General error in approvePhieuNhap: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function cancelPhieuNhap($idPhieuNhap)
    {
        try {

            $sql = "UPDATE mphieunhap SET trangThai = 2 WHERE idPhieuNhap = ? AND trangThai = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPhieuNhap]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error canceling phieu nhap: " . $e->getMessage());
            return false;
        }
    }

    public function updateTongTien($idPhieuNhap)
    {
        try {
            $sql = "UPDATE mphieunhap pn
                    SET tongTien = (
                        SELECT COALESCE(SUM(thanhTien), 0)
                        FROM mchitietphieunhap
                        WHERE idPhieuNhap = ?
                    )
                    WHERE pn.idPhieuNhap = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPhieuNhap, $idPhieuNhap]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error updating tong tien: " . $e->getMessage());
            return false;
        }
    }

    public function forceUpdateTonKho($idPhieuNhap)
    {
        try {
            error_log("Force updating tonkho for approved phieu nhap ID: " . $idPhieuNhap);

            if (!class_exists('MTonKho')) {
                require_once 'mtonkhoCls.php';
                error_log("MTonKho class loaded");
            }

            $sql = "SELECT trangThai FROM mphieunhap WHERE idPhieuNhap = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPhieuNhap]);
            $trangThai = $stmt->fetchColumn();

            error_log("Phieu nhap status: " . $trangThai);

            if ($trangThai == 1) {
                $this->db->beginTransaction();

                $sql = "SELECT * FROM mchitietphieunhap WHERE idPhieuNhap = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$idPhieuNhap]);
                $chiTietList = $stmt->fetchAll(PDO::FETCH_OBJ);

                error_log("Found " . count($chiTietList) . " detail items for phieu nhap ID: " . $idPhieuNhap);

                $tonkhoObj = new MTonKho();

                if (!class_exists('hanghoa')) {
                    require_once 'hanghoaCls.php';
                    error_log("hanghoa class loaded");
                }
                $hanghoaObj = new hanghoa();

                foreach ($chiTietList as $chiTiet) {

                    error_log("Processing detail item: idCTPN = " . $chiTiet->idCTPN .
                        ", idhanghoa = " . $chiTiet->idhanghoa .
                        ", soLuong = " . $chiTiet->soLuong .
                        ", giaNhap = " . $chiTiet->giaNhap);

                    $updateResult = $tonkhoObj->updateSoLuong($chiTiet->idhanghoa, $chiTiet->soLuong, true, true);
                    error_log("Update tonkho result: " . ($updateResult ? "success" : "failed"));

                    if (!class_exists('PriceLogicConfig')) {
                        require_once dirname(__FILE__) . '/../config/price_logic_config.php';
                    }
                    if (!class_exists('Dongia')) {
                        require_once 'dongiaCls.php';
                    }

                    $dongiaObj = new Dongia();
                    $currentActivePrice = $dongiaObj->DongiaGetActiveByProduct($chiTiet->idhanghoa);
                    $hasActivePrice = ($currentActivePrice !== false);

                    if (PriceLogicConfig::shouldUpdateReferencePrice($hasActivePrice)) {
                        $priceToUpdate = $chiTiet->giaNhap;

                        if (PriceLogicConfig::AUTO_APPLY_PROFIT_MARGIN) {
                            $priceToUpdate = PriceLogicConfig::calculateSellingPrice($chiTiet->giaNhap);
                        }

                        $updatePriceResult = $hanghoaObj->HanghoaUpdatePrice($chiTiet->idhanghoa, $priceToUpdate);
                        error_log("Force update hanghoa price result: " . ($updatePriceResult ? "success" : "failed") .
                            ", idhanghoa = " . $chiTiet->idhanghoa .
                            ", import price = " . $chiTiet->giaNhap .
                            ", selling price = " . $priceToUpdate);
                    }

                    if (PriceLogicConfig::shouldCreatePriceFromImport() && !$hasActivePrice) {
                        $sellingPrice = PriceLogicConfig::calculateSellingPrice($chiTiet->giaNhap);
                        $ngayApDung = date('Y-m-d');
                        $ngayKetThuc = date('Y-m-d', strtotime('+1 year'));
                        $ghiChu = "Tự động tạo từ phiếu nhập (force update) - Giá nhập: " . number_format($chiTiet->giaNhap) . " VNĐ";

                        $addPriceResult = $dongiaObj->DongiaAdd(
                            $chiTiet->idhanghoa,
                            $sellingPrice,
                            $ngayApDung,
                            $ngayKetThuc,
                            '',
                            $ghiChu
                        );

                        error_log("Force auto create price result: " . ($addPriceResult ? "success" : "failed") .
                            ", idhanghoa = " . $chiTiet->idhanghoa .
                            ", import price = " . $chiTiet->giaNhap .
                            ", auto selling price = " . $sellingPrice);
                    } else if ($hasActivePrice) {
                        error_log("Force update: Skipped price creation for product " . $chiTiet->idhanghoa .
                            " because it has active price: " . $currentActivePrice->giaBan .
                            " (import price was: " . $chiTiet->giaNhap . ")");
                    }
                }

                $this->db->commit();
                error_log("Transaction committed successfully");
                return true;
            } else {
                error_log("Phieu nhap is not approved, cannot update tonkho");
                return false;
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error force updating tonkho: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("General error in forceUpdateTonKho: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
}
