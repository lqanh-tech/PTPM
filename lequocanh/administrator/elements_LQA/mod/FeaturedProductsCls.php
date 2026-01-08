<?php

require_once __DIR__ . '/database.php';

class FeaturedProducts
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getFeaturedProducts($limit = 8)
    {
        $sql = "SELECT h.*, 
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                CASE 
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL 
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN h.sale_price
                    ELSE h.giathamkhao
                END as gia_hien_tai,
                CASE 
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL 
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN ROUND(((h.giathamkhao - h.sale_price) / h.giathamkhao) * 100)
                    ELSE 0
                END as discount_percent
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                WHERE h.is_featured = 1
                ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END) ASC,
                         h.view_count DESC, h.created_at DESC
                LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getNewProducts($limit = 8)
    {
        $sql = "SELECT h.*, 
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                CASE 
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL 
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN h.sale_price
                    ELSE h.giathamkhao
                END as gia_hien_tai,
                CASE 
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL 
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN ROUND(((h.giathamkhao - h.sale_price) / h.giathamkhao) * 100)
                    ELSE 0
                END as discount_percent
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                WHERE h.is_new = 1
                ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END) ASC,
                         h.created_at DESC
                LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getSaleProducts($limit = 8)
    {
        $sql = "SELECT h.*, 
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                h.sale_price as gia_hien_tai,
                ROUND(((h.giathamkhao - h.sale_price) / h.giathamkhao) * 100) as discount_percent,
                h.sale_end_date
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                WHERE h.is_sale = 1 
                AND h.sale_price IS NOT NULL
                AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END) ASC,
                         discount_percent DESC, h.created_at DESC
                LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function setFeatured($idhanghoa, $is_featured = 1)
    {
        $sql = "UPDATE hanghoa SET is_featured = ? WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$is_featured, $idhanghoa]);
    }

    public function setNew($idhanghoa, $is_new = 1)
    {
        $sql = "UPDATE hanghoa SET is_new = ? WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$is_new, $idhanghoa]);
    }

    public function setSale($idhanghoa, $sale_price, $sale_end_date = null)
    {
        $sql = "UPDATE hanghoa 
                SET is_sale = 1, 
                    sale_price = ?,
                    sale_start_date = NOW(),
                    sale_end_date = ?
                WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sale_price, $sale_end_date, $idhanghoa]);
    }

    public function removeSale($idhanghoa)
    {
        $sql = "UPDATE hanghoa 
                SET is_sale = 0, 
                    sale_price = NULL,
                    sale_start_date = NULL,
                    sale_end_date = NULL
                WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idhanghoa]);
    }

    public function incrementViewCount($idhanghoa)
    {
        $sql = "UPDATE hanghoa SET view_count = view_count + 1 WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idhanghoa]);
    }

    public function getMostViewedProducts($limit = 8)
    {
        $sql = "SELECT h.*, 
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                CASE 
                    WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL 
                         AND (h.sale_end_date IS NULL OR h.sale_end_date > NOW())
                    THEN h.sale_price
                    ELSE h.giathamkhao
                END as gia_hien_tai
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                WHERE h.view_count > 0
                ORDER BY h.view_count DESC
                LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
