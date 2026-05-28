<?php

declare(strict_types=1);

namespace App\Services;

use Database;
use PDO;

class UserService
{
    private static ?self $instance = null;
    private PDO $db;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of singleton.
     */
    private function __clone() {}

    /**
     * Get user by username.
     */
    public function getUserByUsername(string $username): ?object
    {
        $sql = "SELECT iduser, username, hoten, email, dienthoai, diachi
                FROM user 
                WHERE username = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Get user by ID.
     */
    public function getUserById(int $userId): ?object
    {
        $sql = "SELECT iduser, username, hoten, email, dienthoai, diachi
                FROM user 
                WHERE iduser = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Get user by email.
     */
    public function getUserByEmail(string $email): ?object
    {
        $sql = "SELECT iduser, username, hoten, email, dienthoai, diachi
                FROM user 
                WHERE email = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Check if user is an employee.
     */
    public function isEmployee(int $userId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM nhanvien WHERE iduser = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return ($result->count ?? 0) > 0;
    }

    /**
     * Get user with full info including employee status.
     */
    public function getUserFullInfo(string $username): ?object
    {
        $user = $this->getUserByUsername($username);
        if (!$user) {
            return null;
        }

        $user->isEmployee = $this->isEmployee((int) $user->iduser);
        return $user;
    }

    /**
     * Update user profile.
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $allowedFields = ['hoten', 'email', 'dienthoai', 'diachi', 'avatar'];
        $updateFields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateFields[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $params[] = $userId;
        $sql = "UPDATE user SET " . implode(', ', $updateFields) . " WHERE iduser = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Change user password.
     */
    public function changePassword(int $userId, string $newPassword): bool
    {
        $sql = "UPDATE user SET password = ? WHERE iduser = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $userId]);
    }

    /**
     * Verify user password.
     */
    public function verifyPassword(int $userId, string $password): bool
    {
        $sql = "SELECT password FROM user WHERE iduser = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$result) {
            return false;
        }

        return password_verify($password, $result->password);
    }

    /**
     * Get all users (admin).
     */
    public function getAllUsers(): array
    {
        $sql = "SELECT iduser, username, hoten, email, dienthoai, diachi, ngay_tao, trang_thai
                FROM user 
                ORDER BY ngay_tao DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Search users by keyword.
     */
    public function searchUsers(string $keyword): array
    {
        $sql = "SELECT iduser, username, hoten, email, dienthoai
                FROM user 
                WHERE username LIKE ? OR hoten LIKE ? OR email LIKE ?
                ORDER BY hoten ASC
                LIMIT 50";

        $searchTerm = "%{$keyword}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get user count.
     */
    public function getUserCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return (int) ($result->count ?? 0);
    }

    /**
     * Update user status (active/inactive).
     */
    public function updateStatus(int $userId, int $status): bool
    {
        $sql = "UPDATE user SET trang_thai = ? WHERE iduser = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $userId]);
    }
}

if (!function_exists('getUserService')) {
    function getUserService(): UserService
    {
        return UserService::getInstance();
    }
}
