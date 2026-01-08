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

class MChiTietPhieuNhap
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
            $sql = "CREATE TABLE IF NOT EXISTS mchitietphieunhap (
                idCTPN INT AUTO_INCREMENT PRIMARY KEY,
                idPhieuNhap INT NOT NULL,
                idhanghoa INT NOT NULL,
                soLuong INT NOT NULL DEFAULT 0,
                donGia DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                giaNhap DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                thanhTien DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                FOREIGN KEY (idPhieuNhap) REFERENCES mphieunhap(idPhieuNhap) ON DELETE CASCADE,
                FOREIGN KEY (idhanghoa) REFERENCES hanghoa(idhanghoa)
            )";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating mchitietphieunhap table: " . $e->getMessage());
        }
    }

    public function getChiTietByPhieuNhapId($idPhieuNhap)
    {
        $sql = "SELECT ct.*, h.tenhanghoa, h.mota, dvt.tenDonViTinh
                FROM mchitietphieunhap ct
                LEFT JOIN hanghoa h ON ct.idhanghoa = h.idhanghoa
                LEFT JOIN donvitinh dvt ON h.idDonViTinh = dvt.idDonViTinh
                WHERE ct.idPhieuNhap = ?
                ORDER BY ct.idCTPN";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute([$idPhieuNhap]);
        return $stmt->fetchAll();
    }

    public function getChiTietById($idCTPN)
    {
        $sql = "SELECT ct.*, h.tenhanghoa, h.mota, dvt.tenDonViTinh
                FROM mchitietphieunhap ct
                LEFT JOIN hanghoa h ON ct.idhanghoa = h.idhanghoa
                LEFT JOIN donvitinh dvt ON h.idDonViTinh = dvt.idDonViTinh
                WHERE ct.idCTPN = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute([$idCTPN]);
        return $stmt->fetch();
    }

    public function addChiTietPhieuNhap($idPhieuNhap, $idhanghoa, $soLuong, $donGia, $giaNhap)
    {
        try {

            $checkSql = "SELECT trangThai FROM mphieunhap WHERE idPhieuNhap = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$idPhieuNhap]);
            $trangThai = $checkStmt->fetchColumn();
            
            if ($trangThai != 0) {

                return false;
            }
            
            $thanhTien = $soLuong * $giaNhap;
            
            $checkExistSql = "SELECT idCTPN, soLuong FROM mchitietphieunhap 
                             WHERE idPhieuNhap = ? AND idhanghoa = ?";
            $checkExistStmt = $this->db->prepare($checkExistSql);
            $checkExistStmt->execute([$idPhieuNhap, $idhanghoa]);
            $existingItem = $checkExistStmt->fetch(PDO::FETCH_OBJ);
            
            if ($existingItem) {

                $newSoLuong = $existingItem->soLuong + $soLuong;
                $newThanhTien = $newSoLuong * $giaNhap;
                
                $updateSql = "UPDATE mchitietphieunhap 
                             SET soLuong = ?, donGia = ?, giaNhap = ?, thanhTien = ? 
                             WHERE idCTPN = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$newSoLuong, $donGia, $giaNhap, $newThanhTien, $existingItem->idCTPN]);
                
                $this->updateTongTien($idPhieuNhap);
                
                return $existingItem->idCTPN;
            } else {

                $sql = "INSERT INTO mchitietphieunhap (idPhieuNhap, idhanghoa, soLuong, donGia, giaNhap, thanhTien) 
                       VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$idPhieuNhap, $idhanghoa, $soLuong, $donGia, $giaNhap, $thanhTien]);
                
                $this->updateTongTien($idPhieuNhap);
                
                return $this->db->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Error adding chi tiet phieu nhap: " . $e->getMessage());
            return false;
        }
    }

    public function updateChiTietPhieuNhap($idCTPN, $soLuong, $donGia, $giaNhap)
    {
        try {

            $getInfoSql = "SELECT idPhieuNhap FROM mchitietphieunhap WHERE idCTPN = ?";
            $getInfoStmt = $this->db->prepare($getInfoSql);
            $getInfoStmt->execute([$idCTPN]);
            $idPhieuNhap = $getInfoStmt->fetchColumn();
            
            $checkSql = "SELECT trangThai FROM mphieunhap WHERE idPhieuNhap = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$idPhieuNhap]);
            $trangThai = $checkStmt->fetchColumn();
            
            if ($trangThai != 0) {

                return false;
            }
            
            $thanhTien = $soLuong * $giaNhap;
            
            $sql = "UPDATE mchitietphieunhap 
                   SET soLuong = ?, donGia = ?, giaNhap = ?, thanhTien = ? 
                   WHERE idCTPN = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$soLuong, $donGia, $giaNhap, $thanhTien, $idCTPN]);
            
            $this->updateTongTien($idPhieuNhap);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error updating chi tiet phieu nhap: " . $e->getMessage());
            return false;
        }
    }

    public function deleteChiTietPhieuNhap($idCTPN)
    {
        try {

            $getInfoSql = "SELECT idPhieuNhap FROM mchitietphieunhap WHERE idCTPN = ?";
            $getInfoStmt = $this->db->prepare($getInfoSql);
            $getInfoStmt->execute([$idCTPN]);
            $idPhieuNhap = $getInfoStmt->fetchColumn();
            
            $checkSql = "SELECT trangThai FROM mphieunhap WHERE idPhieuNhap = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$idPhieuNhap]);
            $trangThai = $checkStmt->fetchColumn();
            
            if ($trangThai != 0) {

                return false;
            }
            
            $sql = "DELETE FROM mchitietphieunhap WHERE idCTPN = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idCTPN]);
            
            $this->updateTongTien($idPhieuNhap);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error deleting chi tiet phieu nhap: " . $e->getMessage());
            return false;
        }
    }

    private function updateTongTien($idPhieuNhap)
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
}
