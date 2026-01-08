<?php

require_once __DIR__ . '/database.php';

class ProvinceModel
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function getAll($activeOnly = true)
    {
        $sql = "SELECT * FROM provinces";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY region, name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM provinces WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getByCode($code)
    {
        $sql = "SELECT * FROM provinces WHERE code = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getByRegion($region)
    {
        $sql = "SELECT * FROM provinces WHERE region = ? AND is_active = 1 ORDER BY name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$region]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create($data)
    {
        $sql = "INSERT INTO provinces (code, name, name_en, region, is_active) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['name_en'] ?? null,
            $data['region'] ?? null,
            $data['is_active'] ?? 1
        ]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE provinces 
                SET code = ?, name = ?, name_en = ?, region = ?, is_active = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['name_en'] ?? null,
            $data['region'] ?? null,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    public function delete($id)
    {
        $sql = "UPDATE provinces SET is_active = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function forceDelete($id)
    {
        $sql = "DELETE FROM provinces WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function search($keyword)
    {
        $sql = "SELECT * FROM provinces 
                WHERE (name LIKE ? OR name_en LIKE ? OR code LIKE ?) 
                AND is_active = 1 
                ORDER BY name";
        $searchTerm = "%$keyword%";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function count($activeOnly = true)
    {
        $sql = "SELECT COUNT(*) as total FROM provinces";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
