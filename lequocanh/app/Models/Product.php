<?php

require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel
{
    protected static $table = 'hanghoa';
    protected static $primaryKey = 'idhanghoa';
    protected static $timestamps = false;

    protected static $fillable = [
        'tenhanghoa',
        'mota',
        'giathamkhao',
        'hinhanh',
        'idloaihang',
        'idThuongHieu',
        'idDonViTinh',
        'idNhanVien',
        'ghichu'
    ];

    protected static $hidden = [];

    public static function getAllWithRelations()
    {
        $sql = 'SELECT h.*,
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                n.tenNV AS ten_nhanvien,
                lh.tenloaihang AS ten_loaihang,
                CASE 
                    WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != "" 
                    THEN 0 
                    ELSE 1 
                END as image_priority
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                LEFT JOIN nhanvien n ON h.idNhanVien = n.idNhanVien
                LEFT JOIN loaihang lh ON h.idloaihang = lh.idloaihang
                ORDER BY image_priority ASC, h.tenhanghoa ASC';

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = new static($row);
            $product->exists = true;
            $results[] = $product;
        }

        return $results;
    }

    public static function getByCategory($categoryId)
    {
        return static::where('idloaihang', $categoryId);
    }

    public static function getByBrand($brandId)
    {
        return static::where('idThuongHieu', $brandId);
    }

    public static function search($keyword)
    {
        $sql = "SELECT * FROM " . static::getTable() . " WHERE tenhanghoa LIKE ? OR mota LIKE ?";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $searchTerm = "%$keyword%";
        $stmt->execute([$searchTerm, $searchTerm]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = new static($row);
            $product->exists = true;
            $results[] = $product;
        }

        return $results;
    }

    public static function getFeatured($limit = 10)
    {
        $sql = "SELECT * FROM " . static::getTable() . " 
                WHERE hinhanh IS NOT NULL AND hinhanh != 0 AND hinhanh != ''
                ORDER BY idhanghoa DESC LIMIT ?";

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = new static($row);
            $product->exists = true;
            $results[] = $product;
        }

        return $results;
    }

    public function getImageUrl()
    {
        if (empty($this->hinhanh)) {
            return '/lequocanh/administrator/elements_LQA/img_LQA/no-image.png';
        }

        return "/lequocanh/administrator/elements_LQA/mhanghoa/displayImage.php?id=" . $this->hinhanh;
    }

    public function getFormattedPrice()
    {
        return number_format($this->giathamkhao, 0, ',', '.') . ' VNĐ';
    }

    public function hasImage()
    {
        return !empty($this->hinhanh) && $this->hinhanh != 0;
    }

    public function getCategory()
    {
        if (empty($this->idloaihang)) {
            return null;
        }

        $sql = "SELECT * FROM loaihang WHERE idloaihang = ?";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->idloaihang]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getBrand()
    {
        if (empty($this->idThuongHieu)) {
            return null;
        }

        $sql = "SELECT * FROM thuonghieu WHERE idThuongHieu = ?";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->idThuongHieu]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getStock()
    {
        $sql = "SELECT * FROM tonkho WHERE idhanghoa = ?";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->idhanghoa]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function isInStock()
    {
        $stock = $this->getStock();
        return $stock && $stock->soLuong > 0;
    }

    public function save()
    {
        $result = parent::save();

        if ($result && !$this->exists) {

            $this->createInitialInventory();
        }

        return $result;
    }

    private function createInitialInventory()
    {
        $sql = "INSERT INTO tonkho (idhanghoa, soLuong, soLuongToiThieu, viTri) VALUES (?, 0, 0, NULL)";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->idhanghoa]);
    }

    public function delete()
    {

        $relatedData = $this->checkRelatedData();

        if (!empty($relatedData)) {
            throw new Exception('Cannot delete product. Related data exists: ' . implode(', ', array_keys($relatedData)));
        }

        $this->deleteInventory();

        return parent::delete();
    }

    private function checkRelatedData()
    {
        $related = [];
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM chitietgiohang WHERE idhanghoa = ?");
        $stmt->execute([$this->idhanghoa]);
        if ($stmt->fetchColumn() > 0) {
            $related['orders'] = 'Product has order history';
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM chitietphieunhap WHERE idhanghoa = ?");
        $stmt->execute([$this->idhanghoa]);
        if ($stmt->fetchColumn() > 0) {
            $related['imports'] = 'Product has import history';
        }

        return $related;
    }

    private function deleteInventory()
    {
        $sql = "DELETE FROM tonkho WHERE idhanghoa = ?";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->idhanghoa]);
    }

    public static function getValidationRules()
    {
        return [
            'tenhanghoa' => 'required|min:3|max:255',
            'giathamkhao' => 'required|numeric|min:0',
            'idloaihang' => 'required|numeric',
            'mota' => 'max:1000'
        ];
    }
}
