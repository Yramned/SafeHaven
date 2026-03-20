<?php
/**
 * SafeHaven - User Model
 * Uses PDO for database operations
 */

require_once CONFIG_PATH . 'database.php';

class UserModel {
    private static function getDB() {
        return Database::getInstance()->getConnection();
    }
    
    public static function getAll() {
        $db = self::getDB();
        $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    public static function getById($id) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public static function getByEmail($email) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public static function create($data) {
        $db = self::getDB();
        
        $stmt = $db->prepare("
            INSERT INTO users (full_name, phone_number, address, email, password, role, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $role = $data['role'] ?? 'evacuee';
        
        $stmt->execute([
            $data['full_name'],
            $data['phone_number'],
            $data['address'],
            strtolower($data['email']),
            $hashedPassword,
            $role
        ]);
        
        $newUserId = $db->lastInsertId();
        return self::getById($newUserId);
    }
    
    public static function update($id, $data) {
        $db = self::getDB();
        
        $updates = [];
        $params = [];
        
        if (isset($data['full_name'])) {
            $updates[] = "full_name = ?";
            $params[] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $updates[] = "email = ?";
            $params[] = strtolower($data['email']);
        }
        if (isset($data['phone_number'])) {
            $updates[] = "phone_number = ?";
            $params[] = $data['phone_number'];
        }
        if (isset($data['address'])) {
            $updates[] = "address = ?";
            $params[] = $data['address'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['role'])) {
            $updates[] = "role = ?";
            $params[] = $data['role'];
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public static function delete($id) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public static function authenticate($email, $password) {
        $user = self::getByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return null;
    }
    
    public static function getByRole($role) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
    
    public static function count() {
        $db = self::getDB();
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public static function countByRole($role) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = ?");
        $stmt->execute([$role]);
        $result = $stmt->fetch();
        return $result['total'];
    }
}
