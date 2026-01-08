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

class ThuongHieu
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function thuonghieuGetAll()
    {
        $sql = 'SELECT * FROM thuonghieu';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);

        if (!$getAll->execute()) {
            error_log(print_r($getAll->errorInfo(), true));
            return false;
        }

        return $getAll->fetchAll();
    }

    public function thuonghieuAdd($tenTH, $SDT, $email, $diaChi, $hinhanh)
    {
        $sql = "INSERT INTO thuonghieu (tenTH, SDT, email, diaChi, hinhanh) VALUES (?, ?, ?, ?, ?)";
        $data = array($tenTH, $SDT, $email, $diaChi, $hinhanh);

        $add = $this->db->prepare($sql);

        if (!$add->execute($data)) {
            error_log(print_r($add->errorInfo(), true));
            return false;
        }

        return $add->rowCount();
    }

    public function thuonghieuDelete($idThuongHieu)
    {
        $sql = "DELETE FROM thuonghieu WHERE idThuongHieu = ?";
        $data = array($idThuongHieu);

        $del = $this->db->prepare($sql);

        if (!$del->execute($data)) {
            error_log(print_r($del->errorInfo(), true));
            return false;
        }

        return $del->rowCount();
    }

    public function thuonghieuUpdate($tenTH, $SDT, $email, $diaChi, $hinhanh, $idThuongHieu)
    {
        $sql = "UPDATE thuonghieu 
                SET tenTH = ?, SDT = ?, email = ?, diaChi = ?, hinhanh = ? 
                WHERE idThuongHieu = ?";
        $data = array($tenTH, $SDT, $email, $diaChi, $hinhanh, $idThuongHieu);

        $update = $this->db->prepare($sql);

        if (!$update->execute($data)) {
            error_log(print_r($update->errorInfo(), true));
            return false;
        }

        return $update->rowCount();
    }

    public function thuonghieuGetbyId($idThuongHieu)
    {
        $sql = 'SELECT * FROM thuonghieu WHERE idThuongHieu = ?';
        $data = array($idThuongHieu);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);

        if (!$getOne->execute($data)) {
            error_log(print_r($getOne->errorInfo(), true));
            return false;
        }

        return $getOne->fetch();
    }
}
