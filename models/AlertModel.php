<?php
/**
 * SafeHaven - Alert Model
 * Handles situational alerts stored in DB.
 * Gracefully creates the table if it doesn't exist yet.
 */

require_once CONFIG_PATH . 'database.php';

class AlertModel {
    private static function getDB() {
        return Database::getInstance()->getConnection();
    }

    /** Ensure the alerts table exists (auto-migration) */
    private static function ensureTable() {
        $db = self::getDB();
        $db->exec("
            CREATE TABLE IF NOT EXISTS `alerts` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `message` text NOT NULL,
              `severity` enum('critical','evacuation','warning','info') NOT NULL DEFAULT 'info',
              `location` varchar(255) DEFAULT NULL,
              `created_by` int(11) DEFAULT NULL,
              `is_read` tinyint(1) DEFAULT 0,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_severity` (`severity`),
              KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /** Get all alerts, optionally filtered by severity */
    public static function getAll(?string $severity = null): array {
        self::ensureTable();
        $db = self::getDB();
        if ($severity && $severity !== 'all') {
            $stmt = $db->prepare("
                SELECT a.*, u.full_name AS created_by_name
                FROM alerts a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.severity = ?
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$severity]);
        } else {
            $stmt = $db->query("
                SELECT a.*, u.full_name AS created_by_name
                FROM alerts a
                LEFT JOIN users u ON a.created_by = u.id
                ORDER BY a.created_at DESC
            ");
        }
        return $stmt->fetchAll();
    }

    /** Get counts: critical, warning, unread */
    public static function getCounts(): array {
        self::ensureTable();
        $db = self::getDB();
        $stmt = $db->query("
            SELECT
                SUM(severity IN ('critical','evacuation'))       AS critical_count,
                SUM(severity = 'warning')                         AS warning_count,
                SUM(is_read = 0)                                  AS unread_count
            FROM alerts
        ");
        $row = $stmt->fetch();
        return [
            'critical_count' => (int)($row['critical_count'] ?? 0),
            'warning_count'  => (int)($row['warning_count']  ?? 0),
            'unread_count'   => (int)($row['unread_count']   ?? 0),
        ];
    }

    /** Create a new alert */
    public static function create(array $data): int|false {
        self::ensureTable();
        $db = self::getDB();
        $stmt = $db->prepare("
            INSERT INTO alerts (title, message, severity, location, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ok = $stmt->execute([
            $data['title'],
            $data['message'],
            $data['severity'],
            $data['location'] ?? null,
            $data['created_by'] ?? null,
        ]);
        return $ok ? (int)$db->lastInsertId() : false;
    }

    /** Mark a single alert as read */
    public static function markRead(int $id): bool {
        self::ensureTable();
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE alerts SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Delete an alert */
    public static function delete(int $id): bool {
        self::ensureTable();
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM alerts WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Get by ID */
    public static function getById(int $id): ?array {
        self::ensureTable();
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM alerts WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
