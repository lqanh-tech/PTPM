<?php
/**
 * Ward Model - Quản lý Phường/Xã
 * MVC Pattern - Model Layer
 */

require_once __DIR__ . '/database.php';

class WardModel
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Lấy phường/xã theo quận/huyện
     */
    public function getByDistrictId($districtId, $activeOnly = true)
    {
        $sql = "SELECT * FROM wards WHERE district_id = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$districtId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Lấy phường/xã theo ID
     */
    public function getById($id)
    {
        $sql = "SELECT w.*, d.name as district_name, p.name as province_name 
                FROM wards w 
                LEFT JOIN districts d ON w.district_id = d.id 
                LEFT JOIN provinces p ON d.province_id = p.id 
                WHERE w.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Thêm phường/xã mới
     */
    public function create($data)
    {
        $sql = "INSERT INTO wards (district_id, code, name, name_en, is_active) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['district_id'],
            $data['code'],
            $data['name'],
            $data['name_en'] ?? null,
            $data['is_active'] ?? 1
        ]);
    }

    /**
     * Cập nhật phường/xã
     */
    public function update($id, $data)
    {
        $sql = "UPDATE wards 
                SET district_id = ?, code = ?, name = ?, name_en = ?, is_active = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['district_id'],
            $data['code'],
            $data['name'],
            $data['name_en'] ?? null,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    /**
     * Xóa phường/xã (soft delete)
     */
    public function delete($id)
    {
        $sql = "UPDATE wards SET is_active = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Tìm kiếm phường/xã
     */
    public function search($keyword, $districtId = null)
    {
        $sql = "SELECT w.*, d.name as district_name 
                FROM wards w 
                LEFT JOIN districts d ON w.district_id = d.id 
                WHERE (w.name LIKE ? OR w.code LIKE ?) 
                AND w.is_active = 1";
        
        $params = ["%$keyword%", "%$keyword%"];
        
        if ($districtId) {
            $sql .= " AND w.district_id = ?";
            $params[] = $districtId;
        }
        
        $sql .= " ORDER BY w.name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
