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

class HinhAnh
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function ThemHinhAnh($ten_file, $duong_dan, $loai_file, $kich_thuoc, $id_tham_chieu, $loai_tham_chieu, $thu_tu)
    {
        $sql = "INSERT INTO hinhanh (ten_file, duong_dan, loai_file, kich_thuoc, id_tham_chieu, loai_tham_chieu, thu_tu, trang_thai) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$ten_file, $duong_dan, $loai_file, $kich_thuoc, $id_tham_chieu, $loai_tham_chieu, $thu_tu]);
    }

    public function LayTatCaHinhAnh()
    {
        $sql = "SELECT * FROM hinhanh WHERE trang_thai = 1 ORDER BY ngay_tao DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function XoaHinhAnh($id)
    {
        $sql = "SELECT duong_dan FROM hinhanh WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $file = $stmt->fetch(PDO::FETCH_OBJ);

        if ($file instanceof stdClass) {
            if (file_exists($file->duong_dan)) {
                unlink($file->duong_dan);
            }

            $sql = "DELETE FROM hinhanh WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        }
        return false;
    }

    public function XoaNhieuHinhAnh($ids)
    {
        if (empty($ids)) return false;

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT duong_dan FROM hinhanh WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);

        while (($file = $stmt->fetch(PDO::FETCH_OBJ)) instanceof stdClass) {
            if ($file instanceof stdClass && file_exists($file->duong_dan)) {
                unlink($file->duong_dan);
            }
        }

        $sql = "DELETE FROM hinhanh WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($ids);
    }
}