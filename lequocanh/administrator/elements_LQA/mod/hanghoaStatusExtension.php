<?php

require_once __DIR__ . '/database.php';

trait HanghoaStatusTrait
{

    public function updateTrangThai($idhanghoa, $trangthai, $nguoi_thay_doi = null, $ly_do = '')
    {
        try {

            $validStatus = ['dang_ban', 'ngung_ban', 'het_hang'];
            if (!in_array($trangthai, $validStatus)) {
                throw new Exception("Trạng thái không hợp lệ: $trangthai");
            }
            
            $oldStatus = $this->db->query("SELECT trangthai FROM hanghoa WHERE idhanghoa = $idhanghoa")->fetchColumn();
            
            $sql = "UPDATE hanghoa SET trangthai = ? WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$trangthai, $idhanghoa]);
            
            if ($result && $oldStatus != $trangthai) {
                $this->logTrangThaiChange($idhanghoa, $oldStatus, $trangthai, $nguoi_thay_doi, $ly_do);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error updating product status: " . $e->getMessage());
            return false;
        }
    }
    
    private function logTrangThaiChange($idhanghoa, $trangthai_cu, $trangthai_moi, $nguoi_thay_doi, $ly_do)
    {
        try {
            $sql = "INSERT INTO hanghoa_trangthai_history 
                    (idhanghoa, trangthai_cu, trangthai_moi, ly_do, nguoi_thay_doi) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa, $trangthai_cu, $trangthai_moi, $ly_do, $nguoi_thay_doi]);
        } catch (Exception $e) {
            error_log("Error logging status change: " . $e->getMessage());
        }
    }
    
    public function autoUpdateHetHang($idhanghoa = null)
    {
        try {
            $updated = 0;
            
            $sql = "SELECT h.idhanghoa, h.trangthai, COALESCE(SUM(tk.soluongton), 0) as total_stock
                    FROM hanghoa h
                    LEFT JOIN tonkho tk ON h.idhanghoa = tk.idhanghoa
                    WHERE h.trangthai != 'ngung_ban'";
            
            if ($idhanghoa) {
                $sql .= " AND h.idhanghoa = ?";
            }
            
            $sql .= " GROUP BY h.idhanghoa, h.trangthai
                     HAVING total_stock = 0 AND h.trangthai != 'het_hang'
                     OR total_stock > 0 AND h.trangthai = 'het_hang'";
            
            $stmt = $this->db->prepare($sql);
            if ($idhanghoa) {
                $stmt->execute([$idhanghoa]);
            } else {
                $stmt->execute();
            }
            
            $products = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            foreach ($products as $product) {
                if ($product->total_stock == 0 && $product->trangthai != 'het_hang') {

                    $this->updateTrangThai($product->idhanghoa, 'het_hang', null, 'Tự động: Hết tồn kho');
                    $updated++;
                } elseif ($product->total_stock > 0 && $product->trangthai == 'het_hang') {

                    $this->updateTrangThai($product->idhanghoa, 'dang_ban', null, 'Tự động: Có hàng trở lại');
                    $updated++;
                }
            }
            
            return $updated;
        } catch (Exception $e) {
            error_log("Error auto-updating out of stock status: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTrangThaiHistory($idhanghoa, $limit = 10)
    {
        try {
            $sql = "SELECT h.*, nv.tenNV as ten_nhanvien
                    FROM hanghoa_trangthai_history h
                    LEFT JOIN nhanvien nv ON h.nguoi_thay_doi = nv.idNhanVien
                    WHERE h.idhanghoa = ?
                    ORDER BY h.ngay_thay_doi DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa, $limit]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            error_log("Error getting status history: " . $e->getMessage());
            return [];
        }
    }
    
    public function getStatusStatistics()
    {
        try {
            $sql = "SELECT 
                        trangthai,
                        COUNT(*) as so_luong,
                        CASE 
                            WHEN trangthai = 'dang_ban' THEN 'Đang bán'
                            WHEN trangthai = 'ngung_ban' THEN 'Ngừng bán'
                            WHEN trangthai = 'het_hang' THEN 'Hết hàng'
                        END as mo_ta
                    FROM hanghoa
                    GROUP BY trangthai";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting status statistics: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTonKho($idhanghoa)
    {
        try {
            $sql = "SELECT COALESCE(SUM(soluongton), 0) as total_stock
                    FROM tonkho
                    WHERE idhanghoa = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            
            return (int)$result->total_stock;
        } catch (Exception $e) {
            error_log("Error getting stock quantity: " . $e->getMessage());
            return 0;
        }
    }
}

class HanghoaFilterExtension
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function filterProducts($filters = [])
    {
        try {

            $sql = "SELECT DISTINCT h.*,
                    t.tenTH AS ten_thuonghieu,
                    l.tenloaihang AS ten_loaihang,
                    COALESCE(SUM(tk.soluongton), 0) as ton_kho,
                    COALESCE(AVG(pr.rating), 0) as avg_rating,
                    COUNT(DISTINCT pr.id) as review_count,
                    CASE 
                        WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' 
                        THEN 0 
                        ELSE 1 
                    END as image_priority
                    FROM hanghoa h
                    LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                    LEFT JOIN loaihang l ON h.idloaihang = l.idloaihang
                    LEFT JOIN tonkho tk ON h.idhanghoa = tk.idhanghoa
                    LEFT JOIN product_reviews pr ON h.idhanghoa = pr.ma_san_pham AND pr.is_approved = 1 AND (pr.status = 'visible' OR pr.status IS NULL)
                    LEFT JOIN thuoctinhhh tt ON h.idhanghoa = tt.idhanghoa
                    WHERE 1=1";
            
            $params = [];
            
            if (isset($filters['show_all_status']) && $filters['show_all_status'] === true) {

            } else {

                $sql .= " AND h.trangthai IN ('dang_ban', 'het_hang')";
            }
            
            if (isset($filters['trangthai']) && !empty($filters['trangthai'])) {
                $sql .= " AND h.trangthai = ?";
                $params[] = $filters['trangthai'];
            }
            
            if (isset($filters['min_price'])) {
                $sql .= " AND h.giathamkhao >= ?";
                $params[] = $filters['min_price'];
            }
            
            if (isset($filters['max_price'])) {
                $sql .= " AND h.giathamkhao <= ?";
                $params[] = $filters['max_price'];
            }
            
            if (isset($filters['category']) && $filters['category']) {
                $sql .= " AND h.idloaihang = ?";
                $params[] = $filters['category'];
            }
            
            if (isset($filters['colors']) && !empty($filters['colors'])) {
                $colorConditions = [];
                foreach ($filters['colors'] as $color) {
                    $colorConditions[] = "LOWER(tt.tenThuocTinhHH) LIKE ?";
                    $params[] = '%' . strtolower($color) . '%';
                }
                if (!empty($colorConditions)) {
                    $sql .= " AND (" . implode(" OR ", $colorConditions) . ")";
                }
            }
            
            if (isset($filters['sizes']) && !empty($filters['sizes'])) {
                $sizeConditions = [];
                foreach ($filters['sizes'] as $size) {
                    $sizeConditions[] = "LOWER(tt.tenThuocTinhHH) LIKE ?";
                    $params[] = '%' . strtolower($size) . '%';
                }
                if (!empty($sizeConditions)) {
                    $sql .= " AND (" . implode(" OR ", $sizeConditions) . ")";
                }
            }
            
            $sql .= " GROUP BY h.idhanghoa";
            
            if (isset($filters['min_rating']) && $filters['min_rating'] > 0) {
                $ratingThreshold = $filters['min_rating'] - 0.5;
                if ($ratingThreshold < 0.5) $ratingThreshold = 0.5;
                
                $sql .= " HAVING avg_rating >= ? AND review_count > 0";
                $params[] = $ratingThreshold;
            }
            
            $sql .= " ORDER BY image_priority ASC, h.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
            
        } catch (Exception $e) {
            error_log("Error filtering products: " . $e->getMessage());
            return [];
        }
    }
}
