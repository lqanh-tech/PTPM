<?php
/**
 * District Model - Quản lý Quận/Huyện
 * MVC Pattern - Model Layer
 */

require_once __DIR__ . '/database.php';

class DistrictModel
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Lấy tất cả quận/huyện
     */
    public function getAll($activeOnly = true)
    {
        $sql = "SELECT d.*, p.name as province_name 
                FROM districts d 
                LEFT JOIN provinces p ON d.province_id = p.id";
        if ($activeOnly) {
            $sql .= " WHERE d.is_active = 1";
        }
        $sql .= " ORDER BY p.name, d.name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Lấy quận/huyện theo tỉnh
     */
    public function getByProvinceId($provinceId, $activeOnly = true)
    {
        $sql = "SELECT * FROM districts WHERE province_id = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$provinceId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Lấy quận/huyện theo ID
     */
    public function getById($id)
    {
        $sql = "SELECT d.*, p.name as province_name 
                FROM districts d 
                LEFT JOIN provinces p ON d.province_id = p.id 
                WHERE d.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Thêm quận/huyện mới
     */
    public function create($data)
    {
        $sql = "INSERT INTO districts (province_id, code, name, name_en, is_active) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['province_id'],
            $data['code'],
            $data['name'],
            $data['name_en'] ?? null,
            $data['is_active'] ?? 1
        ]);
    }

    /**
     * Cập nhật quận/huyện
     */
    public function update($id, $data)
    {
        $sql = "UPDATE districts 
                SET province_id = ?, code = ?, name = ?, name_en = ?, is_active = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['province_id'],
            $data['code'],
            $data['name'],
            $data['name_en'] ?? null,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    /**
     * Xóa quận/huyện (soft delete)
     */
    public function delete($id)
    {
        $sql = "UPDATE districts SET is_active = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Tìm kiếm quận/huyện
     */
    public function search($keyword, $provinceId = null)
    {
        $sql = "SELECT d.*, p.name as province_name 
                FROM districts d 
                LEFT JOIN provinces p ON d.province_id = p.id 
                WHERE (d.name LIKE ? OR d.code LIKE ?) 
                AND d.is_active = 1";
        
        $params = ["%$keyword%", "%$keyword%"];
        
        if ($provinceId) {
            $sql .= " AND d.province_id = ?";
            $params[] = $provinceId;
        }
        
        $sql .= " ORDER BY d.name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Đếm số lượng quận/huyện
     */
    public function count($provinceId = null, $activeOnly = true)
    {
        $sql = "SELECT COUNT(*) as total FROM districts WHERE 1=1";
        $params = [];
        
        if ($provinceId) {
            $sql .= " AND province_id = ?";
            $params[] = $provinceId;
        }
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
