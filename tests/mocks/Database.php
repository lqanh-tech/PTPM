<?php

/**
 * Mock Database class for testing - prevents actual DB connections
 */
class Database
{
    private static $instance = null;
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection()
    {
        static $mockPdo = null;
        if ($mockPdo === null) {
            $mockPdo = new PDO('sqlite::memory:');
        }
        return $mockPdo;
    }
}
