<?php
/**
 * SafeHaven - Message Model
 * Handles contact form messages
 */

require_once CONFIG_PATH . 'database.php';

class MessageModel {
    private static function getDB() {
        return Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new message
     */
    public static function create($data) {
        $db = self::getDB();
        
        $stmt = $db->prepare("
            INSERT INTO messages (name, email, subject, message, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['subject'],
            $data['message']
        ]);
    }
    
    /**
     * Get all messages
     */
    public static function getAll() {
        $db = self::getDB();
        $stmt = $db->query("SELECT * FROM messages ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    /**
     * Get message by ID
     */
    public static function getById($id) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Mark message as read
     */
    public static function markAsRead($id) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE messages SET is_read = TRUE WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get unread message count
     */
    public static function getUnreadCount() {
        $db = self::getDB();
        $stmt = $db->query("SELECT COUNT(*) as total FROM messages WHERE is_read = FALSE");
        $result = $stmt->fetch();
        return $result['total'];
    }
}
