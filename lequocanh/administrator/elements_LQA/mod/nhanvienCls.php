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

class NhanVien
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function nhanvienGetAll()
    {
        $sql = 'SELECT nv.*, u.hoten as ten_user, u.dienthoai as sdt_user, u.username as username_user
                FROM nhanvien nv
                LEFT JOIN user u ON nv.iduser = u.iduser';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);

        if (!$getAll->execute()) {
            error_log(print_r($getAll->errorInfo(), true));
            return false;
        }

        return $getAll->fetchAll();
    }

    public function nhanvienAdd($tenNV, $SDT, $email, $luongCB, $phuCap, $chucVu, $iduser = null)
    {
        $sql = "INSERT INTO nhanvien (tenNV, SDT, email, luongCB, phuCap, chucVu, iduser) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $data = array($tenNV, $SDT, $email, $luongCB, $phuCap, $chucVu, $iduser);

        $add = $this->db->prepare($sql);

        if (!$add->execute($data)) {
            error_log(print_r($add->errorInfo(), true));
            return false;
        }

        return $add->rowCount();
    }

    public function getLastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function nhanvienDelete($idNhanVien)
    {
        $sql = "DELETE FROM nhanvien WHERE idNhanVien = ?";
        $data = array($idNhanVien);

        $del = $this->db->prepare($sql);

        if (!$del->execute($data)) {
            error_log(print_r($del->errorInfo(), true));
            return false;
        }

        return $del->rowCount();
    }

    public function nhanvienUpdate($tenNV, $SDT, $email, $luongCB, $phuCap, $chucVu, $idNhanVien, $iduser)
    {
        $db = Database::getInstance()->getConnection();

        try {

            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/administrator/debug_log.txt', "idNhanVien: $idNhanVien\niduser: $iduser\ntenNV: $tenNV\nSDT: $SDT\nemail: $email\nluongCB: $luongCB\nphuCap: $phuCap\nchucVu: $chucVu\n", FILE_APPEND);

            if ($iduser === '') {
                $iduser = null;
            }

            if ($iduser !== null) {
                $query = "UPDATE nhanvien
                        SET tennv = :tenNV,
                            sdt = :SDT,
                            email = :email,
                            luongcb = :luongCB,
                            phucap = :phuCap,
                            chucvu = :chucVu,
                            iduser = :iduser
                        WHERE idnhanvien = :idNhanVien";

                $statement = $db->prepare($query);
                $statement->bindParam(':tenNV', $tenNV);
                $statement->bindParam(':SDT', $SDT);
                $statement->bindParam(':email', $email);
                $statement->bindParam(':luongCB', $luongCB, PDO::PARAM_STR);
                $statement->bindParam(':phuCap', $phuCap, PDO::PARAM_STR);
                $statement->bindParam(':chucVu', $chucVu);
                $statement->bindParam(':idNhanVien', $idNhanVien, PDO::PARAM_INT);
                $statement->bindParam(':iduser', $iduser, PDO::PARAM_INT);
            } else {
                $query = "UPDATE nhanvien
                        SET tennv = :tenNV,
                            sdt = :SDT,
                            email = :email,
                            luongcb = :luongCB,
                            phucap = :phuCap,
                            chucvu = :chucVu,
                            iduser = NULL
                        WHERE idnhanvien = :idNhanVien";

                $statement = $db->prepare($query);
                $statement->bindParam(':tenNV', $tenNV);
                $statement->bindParam(':SDT', $SDT);
                $statement->bindParam(':email', $email);
                $statement->bindParam(':luongCB', $luongCB, PDO::PARAM_STR);
                $statement->bindParam(':phuCap', $phuCap, PDO::PARAM_STR);
                $statement->bindParam(':chucVu', $chucVu);
                $statement->bindParam(':idNhanVien', $idNhanVien, PDO::PARAM_INT);
            }

            return $statement->execute();
        } catch (PDOException $e) {
            file_put_contents(
                '/var/www/html/debug_log.txt',
                "nhanvienUpdate ERROR: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            return false;
        }
    }

    public function nhanvienGetById($idNhanVien)
    {
        $sql = 'SELECT nv.*, u.hoten as ten_user, u.dienthoai as sdt_user, u.username as username_user
                FROM nhanvien nv
                LEFT JOIN user u ON nv.iduser = u.iduser
                WHERE nv.idNhanVien = ?';
        $data = array($idNhanVien);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);

        if (!$getOne->execute($data)) {
            error_log(print_r($getOne->errorInfo(), true));
            return false;
        }

        return $getOne->fetch();
    }
}
