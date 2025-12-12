<?php
require_once __DIR__ . '/database.php';

class ThuocTinhHH
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Lấy tất cả các thuộc tính
    public function thuoctinhhhGetAll()
    {
        $sql = 'SELECT * FROM thuoctinhhh';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);

        if (!$getAll->execute()) {
            error_log(print_r($getAll->errorInfo(), true));
            return false;
        }

        return $getAll->fetchAll();
    }

    // Thêm thuộc tính mới
    public function thuoctinhhhAdd($idhanghoa, $idThuocTinh, $tenThuocTinhHH,  $ghiChu)
    {
        $sql = "INSERT INTO thuoctinhhh (idhanghoa, idThuocTinh, tenThuocTinhHH,  ghiChu) VALUES (?, ?, ?, ?)";
        $data = array($idhanghoa, $idThuocTinh, $tenThuocTinhHH,  $ghiChu);

        $add = $this->db->prepare($sql);

        if (!$add->execute($data)) {
            error_log(print_r($add->errorInfo(), true));
            return false;
        }

        return $add->rowCount();
    }

    // Xóa thuộc tính theo ID
    public function thuoctinhhhDelete($idThuocTinhHH)
    {
        $sql = "DELETE FROM thuoctinhhh WHERE idThuocTinhHH = ?";
        $data = array($idThuocTinhHH);

        $del = $this->db->prepare($sql);

        if (!$del->execute($data)) {
            error_log(print_r($del->errorInfo(), true));
            return false;
        }

        return $del->rowCount();
    }

    // Cập nhật thông tin thuộc tính
    public function thuoctinhhhUpdate($idhanghoa, $idThuocTinh, $tenThuocTinhHH, $idThuocTinhHH)
    {
        // Ghi log dữ liệu đầu vào
        $log_file = __DIR__ . '/../mthuoctinhhh/update_log.txt';
        $log_data = date('Y-m-d H:i:s') . " - Update parameters:\n";
        $log_data .= "idhanghoa: $idhanghoa (type: " . gettype($idhanghoa) . ")\n";
        $log_data .= "idThuocTinh: $idThuocTinh (type: " . gettype($idThuocTinh) . ")\n";
        $log_data .= "tenThuocTinhHH: $tenThuocTinhHH (type: " . gettype($tenThuocTinhHH) . ")\n";
        $log_data .= "idThuocTinhHH: $idThuocTinhHH (type: " . gettype($idThuocTinhHH) . ")\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);

        $sql = "UPDATE thuoctinhhh 
                SET idhanghoa = ?, idThuocTinh = ?, tenThuocTinhHH = ?
                WHERE idThuocTinhHH = ?";
        $data = array($idhanghoa, $idThuocTinh, $tenThuocTinhHH, $idThuocTinhHH);

        // Ghi log SQL trước khi thực thi
        $log_data = date('Y-m-d H:i:s') . " - SQL Query: $sql\n";
        $log_data .= "Data: " . print_r($data, true) . "\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);

        $update = $this->db->prepare($sql);

        $success = $update->execute($data);

        // Ghi log kết quả
        if (!$success) {
            $error_info = $update->errorInfo();
            $log_data = date('Y-m-d H:i:s') . " - SQL Error:\n";
            $log_data .= "Code: " . $error_info[0] . "\n";
            $log_data .= "SQL State: " . $error_info[1] . "\n";
            $log_data .= "Message: " . $error_info[2] . "\n";
            file_put_contents($log_file, $log_data, FILE_APPEND);
            error_log(print_r($error_info, true));
            return false;
        }

        $log_data = date('Y-m-d H:i:s') . " - Success, rows affected: " . $update->rowCount() . "\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);

        return $update->rowCount();
    }

    // Lấy thông tin thuộc tính theo ID
    public function thuoctinhhhGetbyId($idThuocTinhHH)
    {
        $sql = 'SELECT * FROM thuoctinhhh WHERE idThuocTinhHH = ?';
        $data = array($idThuocTinhHH);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);

        if (!$getOne->execute($data)) {
            error_log(print_r($getOne->errorInfo(), true));
            return false;
        }

        return $getOne->fetch();
    }

    public function thuoctinhhhGetbyIdloaihang($idloaihang)
    {
        $sql = 'SELECT * FROM thuoctinhhh WHERE idloaihang = ?';
        $data = array($idloaihang);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);

        if (!$getOne->execute($data)) {
            error_log(print_r($getOne->errorInfo(), true));
            return false;
        }

        return $getOne->fetchAll();
    }

    // Thêm phương thức này vào lớp ThuocTinhHH
    public function thuoctinhhhGetbyIdHanghoa($idhanghoa)
    {
        $sql = 'SELECT * FROM thuoctinhhh WHERE idhanghoa = ?';
        $data = array($idhanghoa);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);

        if (!$getOne->execute($data)) {
            error_log(print_r($getOne->errorInfo(), true));
            return false;
        }

        return $getOne->fetchAll();
    }
}
