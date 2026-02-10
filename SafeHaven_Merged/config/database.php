<?php
/**
 * SafeHaven - Database Configuration
 */

class Database {
    private static $instance = null;
    private $connection = null;
    
    private $host;
    private $dbname;
    private $username;
    private $password;
    
    private function __construct() {
        if (IS_LOCAL) {
            $this->host = 'localhost';
            $this->dbname = 'safehaven';
            $this->username = 'root';
            $this->password = '';
        } else {
            $this->host = 'tommy.heliohost.org';
            $this->dbname = 'safeheaven_safehaven';
            $this->username = 'safeheaven_admin';
            $this->password = 'YOUR_DATABASE_PASSWORD';
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
}

class JSONDatabase {
    
    public static function read($file) {
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }
    
    public static function write($file, $data) {
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public static function initialize($file, $defaultData = []) {
        if (!file_exists($file) || filesize($file) == 0) {
            self::write($file, $defaultData);
        }
    }
}