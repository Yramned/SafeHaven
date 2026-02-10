<?php
/**
 * SafeHaven - Message Model
 */

require_once CONFIG_PATH . 'database.php';

class MessageModel {
    
    public static function getAll() {
        return JSONDatabase::read(MESSAGES_FILE);
    }
    
    public static function create($data) {
        $messages = self::getAll();
        
        $newMessage = [
            'id' => count($messages) + 1,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'created_at' => date('Y-m-d H:i:s'),
            'read' => false
        ];
        
        $messages[] = $newMessage;
        JSONDatabase::write(MESSAGES_FILE, $messages);
        
        return $newMessage;
    }
    
    public static function markAsRead($id) {
        $messages = self::getAll();
        
        foreach ($messages as $key => $message) {
            if ($message['id'] == $id) {
                $messages[$key]['read'] = true;
                JSONDatabase::write(MESSAGES_FILE, $messages);
                return true;
            }
        }
        
        return false;
    }
    
    public static function getUnreadCount() {
        $messages = self::getAll();
        return count(array_filter($messages, fn($m) => !($m['read'] ?? false)));
    }
    
    public static function initialize() {
        JSONDatabase::initialize(MESSAGES_FILE, []);
    }
}