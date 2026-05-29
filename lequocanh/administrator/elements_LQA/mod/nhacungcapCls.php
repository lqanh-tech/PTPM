<?php
require_once 'database.php';

class nhacungcap
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getInstance()->getConnection();
    }

    public function NhacungcapGetAll()
    {
        $sql = 'SELECT idNhaCungCap, tenNhaCungCap, diachi, sodienthoai, email FROM nhacungcap ORDER BY idNCC DESC';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    public function NhacungcapAdd($tenNCC, $nguoiLienHe, $soDienThoai, $email, $diaChi, $maSoThue, $ghiChu)
    {
        $sql = "INSERT INTO nhacungcap (tenNCC, nguoiLienHe, soDienThoai, email, diaChi, maSoThue, ghiChu, trangThai) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $data = array($tenNCC, $nguoiLienHe, $soDienThoai, $email, $diaChi, $maSoThue, $ghiChu);
        $add = $this->db->prepare($sql);
        $add->execute($data);
        return $add->rowCount();
    }

    public function NhacungcapDelete($idNCC)
    {
        $sql = "DELETE FROM nhacungcap WHERE idNCC = ?";
        $data = array($idNCC);
        $del = $this->db->prepare($sql);
        $del->execute($data);
        return $del->rowCount();
    }

    public function NhacungcapUpdate($tenNCC, $nguoiLienHe, $soDienThoai, $email, $diaChi, $maSoThue, $ghiChu, $trangThai, $idNCC)
    {
        $sql = "UPDATE nhacungcap SET 
                tenNCC = ?, 
                nguoiLienHe = ?, 
                soDienThoai = ?, 
                email = ?, 
                diaChi = ?, 
                maSoThue = ?, 
                ghiChu = ?, 
                trangThai = ? 
                WHERE idNCC = ?";
        $data = array($tenNCC, $nguoiLienHe, $soDienThoai, $email, $diaChi, $maSoThue, $ghiChu, $trangThai, $idNCC);
        $update = $this->db->prepare($sql);
        $update->execute($data);
        return $update->rowCount();
    }

    public function NhacungcapGetbyId($idNCC)
    {
        $sql = "SELECT idNhaCungCap, tenNhaCungCap, diachi, sodienthoai, email FROM nhacungcap WHERE idNCC = ?";
        $data = array($idNCC);
        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);
        $getOne->execute($data);
        return $getOne->fetch();
    }

    public function NhacungcapSearch($keyword)
    {
        $sql = "SELECT idNhaCungCap, tenNhaCungCap, diachi, sodienthoai, email FROM nhacungcap 
                WHERE tenNCC LIKE ? OR nguoiLienHe LIKE ? OR soDienThoai LIKE ? OR email LIKE ? 
                ORDER BY idNCC DESC";
        $data = array("%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%");
        $search = $this->db->prepare($sql);
        $search->setFetchMode(PDO::FETCH_OBJ);
        $search->execute($data);
        return $search->fetchAll();
    }

    public function UpdateStatus($idNCC, $trangThai)
    {
        $sql = "UPDATE nhacungcap SET trangThai = ? WHERE idNCC = ?";
        $data = array($trangThai, $idNCC);
        $update = $this->db->prepare($sql);
        $update->execute($data);
        return $update->rowCount();
    }
}
