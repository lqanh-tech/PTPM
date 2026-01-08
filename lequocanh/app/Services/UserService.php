<?php

require_once __DIR__ . '/../../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../../cache/QueryCache.php';

class UserService
{
    private static $instance = null;
    private $db;
    private $cache;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->cache = QueryCache::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getUserByUsername($username)
    {
        $sql = "SELECT iduser, username, hoten, email, sodienthoai, diachi 
                FROM user 
                WHERE username = ?";
        
        return $this->cache->queryOne($this->db, $sql, [$username], 300);
    }

    public function getUserById($userId)
    {
        $sql = "SELECT iduser, username, hoten, email, sodienthoai, diachi 
                FROM user 
                WHERE iduser = ?";
        
        return $this->cache->queryOne($this->db, $sql, [$userId], 300);
    }

    public function getUserByEmail($email)
    {
        $sql = "SELECT iduser, username, hoten, email, sodienthoai, diachi 
                FROM user 
                WHERE email = ?";
        
        return $this->cache->queryOne($this->db, $sql, [$email], 300);
    }

    public function isEmployee($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM nhanvien WHERE iduser = ?";
        $result = $this->cache->queryOne($this->db, $sql, [$userId], 300);
        return ($result->count ?? 0) > 0;
    }

    public function getUserFullInfo($username)
    {
        $user = $this->getUserByUsername($username);
        if (!$user) {
            return null;
        }

        $user->isEmployee = $this->isEmployee($user->iduser);
        return $user;
    }

    public function invalidateUserCache($userId = null)
    {
        $this->cache->invalidateProducts();
    }
}

if (!function_exists('getUserService')) {
    function getUserService()
    {
        return UserService::getInstance();
    }
}
