<?php
require_once 'database.php';

class loaihang
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
            if (!$this->db) {
                throw new Exception("Không thể lấy kết nối cơ sở dữ liệu");
            }
        } catch (Exception $e) {
            error_log("Lỗi trong loaihangCls constructor: " . $e->getMessage());
            throw new Exception("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
        }
    }

    public function LoaihangGetAll()
    {
        $sql = 'SELECT * FROM loaihang';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    public function LoaihangAdd($tenloaihang, $hinhanh, $mota)
    {
        $sql = "INSERT INTO loaihang (tenloaihang, hinhanh, mota) VALUES (?,?,?)";
        $data = array($tenloaihang, $hinhanh, $mota);
        $add = $this->db->prepare($sql);
        $add->execute($data);
        return $add->rowCount();
    }

    public function LoaihangDelete($idloaihang)
    {
        try {

            $checkSql = "SELECT COUNT(*) as count FROM hanghoa WHERE idloaihang = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute(array($idloaihang));
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {

                throw new Exception("Không thể xóa loại hàng này vì vẫn có {$result['count']} sản phẩm đang sử dụng. Vui lòng xóa các sản phẩm thuộc loại này trước hoặc chuyển chúng sang loại khác.");
            }
            
            $sql = "DELETE FROM loaihang WHERE idloaihang = ?";
            $data = array($idloaihang);
            $del = $this->db->prepare($sql);
            $del->execute($data);
            return $del->rowCount();
            
        } catch (PDOException $e) {

            if ($e->getCode() == '23000') {
                throw new Exception("Không thể xóa loại hàng này vì vẫn có sản phẩm đang sử dụng. Vui lòng xóa các sản phẩm thuộc loại này trước.");
            }
            throw new Exception("Lỗi cơ sở dữ liệu: " . $e->getMessage());
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function LoaihangUpdate($tenloaihang, $hinhanh, $mota, $idloaihang)
    {
        $sql = "UPDATE loaihang SET tenloaihang=?, hinhanh=?, mota=? WHERE idloaihang=?";
        $data = array($tenloaihang, $hinhanh, $mota, $idloaihang);
        $update = $this->db->prepare($sql);
        $update->execute($data);
        return $update->rowCount();
    }

    public function LoaihangGetbyId($idloaihang)
    {
        $sql = "SELECT * FROM loaihang WHERE idloaihang=?";
        $data = array($idloaihang);
        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);
        $getOne->execute($data);
        return $getOne->fetch();
    }
    
    public function getHanghoaByLoaihang($idloaihang)
    {
        $sql = "SELECT idhanghoa, tenhanghoa FROM hanghoa WHERE idloaihang = ? LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute(array($idloaihang));
        return $stmt->fetchAll();
    }
    
    public function countHanghoaByLoaihang($idloaihang)
    {
        $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idloaihang = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($idloaihang));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
