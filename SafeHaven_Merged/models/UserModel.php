<?php
/**
 * SafeHaven - User Model
 */

require_once CONFIG_PATH . 'database.php';

class UserModel {
    
    public static function getAll() {
        return JSONDatabase::read(USERS_FILE);
    }
    
    public static function getById($id) {
        $users = self::getAll();
        foreach ($users as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }
    
    public static function getByEmail($email) {
        $users = self::getAll();
        foreach ($users as $user) {
            if (strtolower($user['email']) === strtolower($email)) {
                return $user;
            }
        }
        return null;
    }
    
    public static function create($data) {
        $users = self::getAll();
        
        // Generate new ID
        $maxId = 0;
        foreach ($users as $user) {
            if ($user['id'] > $maxId) {
                $maxId = $user['id'];
            }
        }
        
        $newUser = [
            'id' => $maxId + 1,
            'full_name' => $data['full_name'],
            'phone_number' => $data['phone_number'],
            'address' => $data['address'],
            'email' => strtolower($data['email']),
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role' => $data['role'] ?? 'evacuee',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $users[] = $newUser;
        JSONDatabase::write(USERS_FILE, $users);
        
        return $newUser;
    }
    
    public static function update($id, $data) {
        $users = self::getAll();
        $updated = false;
        
        foreach ($users as $key => $user) {
            if ($user['id'] == $id) {
                if (isset($data['full_name'])) {
                    $users[$key]['full_name'] = $data['full_name'];
                }
                if (isset($data['email'])) {
                    $users[$key]['email'] = strtolower($data['email']);
                }
                if (isset($data['phone_number'])) {
                    $users[$key]['phone_number'] = $data['phone_number'];
                }
                if (isset($data['address'])) {
                    $users[$key]['address'] = $data['address'];
                }
                if (isset($data['password']) && !empty($data['password'])) {
                    $users[$key]['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
                }
                if (isset($data['role'])) {
                    $users[$key]['role'] = $data['role'];
                }
                
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            JSONDatabase::write(USERS_FILE, $users);
            return true;
        }
        
        return false;
    }
    
    public static function delete($id) {
        $users = self::getAll();
        $filtered = array_filter($users, function($user) use ($id) {
            return $user['id'] != $id;
        });
        
        JSONDatabase::write(USERS_FILE, array_values($filtered));
        return count($filtered) < count($users);
    }
    
    public static function authenticate($email, $password) {
        $user = self::getByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return null;
    }
    
    public static function getByRole($role) {
        $users = self::getAll();
        return array_filter($users, function($user) use ($role) {
            return ($user['role'] ?? 'evacuee') === $role;
        });
    }
    
    public static function initialize() {
        $defaultUsers = [
            [
                'id' => 1,
                'full_name' => 'evacuee',
                'phone_number' => '123456789',
                'address' => 'bcd',
                'email' => 'evacuee@gmail.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'role' => 'evacuee',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'full_name' => 'admin',
                'phone_number' => '987654321',
                'address' => 'bcd',
                'email' => 'admin@gmail.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        JSONDatabase::initialize(USERS_FILE, $defaultUsers);
    }
}