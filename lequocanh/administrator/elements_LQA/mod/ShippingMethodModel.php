<?php

require_once __DIR__ . '/database.php';

class ShippingMethodModel
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
        $sql = "SELECT * FROM shipping_methods";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY sort_order, name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM shipping_methods WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getByCode($code)
    {
        $sql = "SELECT * FROM shipping_methods WHERE code = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function create($data)
    {
        $sql = "INSERT INTO shipping_methods (code, name, description, delivery_time, price_multiplier, icon, is_active, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['description'] ?? null,
            $data['delivery_time'] ?? null,
            $data['price_multiplier'] ?? 1.0,
            $data['icon'] ?? null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE shipping_methods 
                SET code = ?, name = ?, description = ?, delivery_time = ?, 
                    price_multiplier = ?, icon = ?, is_active = ?, sort_order = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['description'] ?? null,
            $data['delivery_time'] ?? null,
            $data['price_multiplier'] ?? 1.0,
            $data['icon'] ?? null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0,
            $id
        ]);
    }

    public function delete($id)
    {
        $sql = "UPDATE shipping_methods SET is_active = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}
