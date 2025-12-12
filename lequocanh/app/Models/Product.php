<?php

/**
 * Product Model
 * Replaces hanghoaCls with modern MVC approach
 */

require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel
{
    protected static $table = 'hanghoa';
    protected static $primaryKey = 'idhanghoa';
    protected static $timestamps = false; // Table doesn't have created_at/updated_at

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

    /**
     * Get all products with relationships
     */
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

    /**
     * Get products by category
     */
    public static function getByCategory($categoryId)
    {
        return static::where('idloaihang', $categoryId);
    }

    /**
     * Get products by brand
     */
    public static function getByBrand($brandId)
    {
        return static::where('idThuongHieu', $brandId);
    }

    /**
     * Search products
     */
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

    /**
     * Get featured products (with images)
     */
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

    /**
     * Get image URL
     */
    public function getImageUrl()
    {
        if (empty($this->hinhanh)) {
            return '/lequocanh/administrator/elements_LQA/img_LQA/no-image.png';
        }

        return "/lequocanh/administrator/elements_LQA/mhanghoa/displayImage.php?id=" . $this->hinhanh;
    }

    /**
     * Format price for display
     */
    public function getFormattedPrice()
    {
        return number_format($this->giathamkhao, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Check if product has image
     */
    public function hasImage()
    {
        return !empty($this->hinhanh) && $this->hinhanh != 0;
    }

    /**
     * Get category relationship
     */
    public function getCategory()
    {
        if (empty($this->idloaihang)) {
            return null;
        }

        // This would use Category model when implemented
        $sql = "SELECT * FROM loaihang WHERE idloaihang = ?";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->idloaihang]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Get brand relationship
     */
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

    /**
     * Get stock information
     */
    public function getStock()
    {
        $sql = "SELECT * FROM tonkho WHERE idhanghoa = ?";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->idhanghoa]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock()
    {
        $stock = $this->getStock();
        return $stock && $stock->soLuong > 0;
    }

    /**
     * Save product with inventory creation
     */
    public function save()
    {
        $result = parent::save();

        if ($result && !$this->exists) {
            // Create initial inventory record for new products
            $this->createInitialInventory();
        }

        return $result;
    }

    /**
     * Create initial inventory record
     */
    private function createInitialInventory()
    {
        $sql = "INSERT INTO tonkho (idhanghoa, soLuong, soLuongToiThieu, viTri) VALUES (?, 0, 0, NULL)";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->idhanghoa]);
    }

    /**
     * Delete product and related records
     */
    public function delete()
    {
        // Check for related data
        $relatedData = $this->checkRelatedData();

        if (!empty($relatedData)) {
            throw new Exception('Cannot delete product. Related data exists: ' . implode(', ', array_keys($relatedData)));
        }

        // Delete inventory record first
        $this->deleteInventory();

        // Delete the product
        return parent::delete();
    }

    /**
     * Check for related data that prevents deletion
     */
    private function checkRelatedData()
    {
        $related = [];
        $db = Database::getInstance()->getConnection();

        // Check orders
        $stmt = $db->prepare("SELECT COUNT(*) FROM chitietgiohang WHERE idhanghoa = ?");
        $stmt->execute([$this->idhanghoa]);
        if ($stmt->fetchColumn() > 0) {
            $related['orders'] = 'Product has order history';
        }

        // Check import records
        $stmt = $db->prepare("SELECT COUNT(*) FROM chitietphieunhap WHERE idhanghoa = ?");
        $stmt->execute([$this->idhanghoa]);
        if ($stmt->fetchColumn() > 0) {
            $related['imports'] = 'Product has import history';
        }

        return $related;
    }

    /**
     * Delete inventory record
     */
    private function deleteInventory()
    {
        $sql = "DELETE FROM tonkho WHERE idhanghoa = ?";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->idhanghoa]);
    }

    /**
     * Get validation rules
     */
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
