<?php
/**
 * SafeHaven - Sensor Data Model
 *
 * Stores admin-editable situational sensor readings in the database.
 * These values are shown on the Alerts page sensor cards and reflect
 * across the entire site for all users.
 *
 * Table: sensor_readings
 *   id, sensor_key, label, value, unit, trend, status, icon,
 *   updated_by, updated_at
 *
 * Status values: ok | warn | critical
 */

require_once CONFIG_PATH . 'database.php';

class SensorDataModel
{
    // Default sensor definitions (used when the table is empty)
    private static array $defaults = [
        'temperature' => [
            'label'  => 'Temperature',
            'value'  => '38.4',
            'unit'   => '°C',
            'trend'  => '↑ 2.1° from last hour',
            'status' => 'critical',
        ],
        'humidity' => [
            'label'  => 'Humidity',
            'value'  => '82',
            'unit'   => '%',
            'trend'  => '↑ 5% from last hour',
            'status' => 'warn',
        ],
        'wind_speed' => [
            'label'  => 'Wind Speed',
            'value'  => '14',
            'unit'   => 'km/h',
            'trend'  => '↓ 3 km/h from last hour',
            'status' => 'ok',
        ],
        'flood_level' => [
            'label'  => 'Flood Level',
            'value'  => '2.8',
            'unit'   => 'm',
            'trend'  => '↑ 0.4 m – rising',
            'status' => 'warn',
        ],
    ];

    private static function getDB(): \PDO
    {
        return Database::getInstance()->getConnection();
    }

    /** Create table + seed defaults if it doesn't exist yet */
    private static function ensureTable(): void
    {
        $db = self::getDB();
        $db->exec("
            CREATE TABLE IF NOT EXISTS `sensor_readings` (
              `id`          int(11)      NOT NULL AUTO_INCREMENT,
              `sensor_key`  varchar(64)  NOT NULL,
              `label`       varchar(128) NOT NULL,
              `value`       varchar(64)  NOT NULL DEFAULT '0',
              `unit`        varchar(32)  NOT NULL DEFAULT '',
              `trend`       varchar(255) NOT NULL DEFAULT '',
              `status`      enum('ok','warn','critical') NOT NULL DEFAULT 'ok',
              `icon`        varchar(16)  NOT NULL DEFAULT '',
              `updated_by`  int(11)      DEFAULT NULL,
              `updated_at`  timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_sensor_key` (`sensor_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed defaults if table is empty
        $count = (int) $db->query("SELECT COUNT(*) FROM sensor_readings")->fetchColumn();
        if ($count === 0) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO sensor_readings
                    (sensor_key, label, value, unit, trend, status, icon)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach (self::$defaults as $key => $d) {
                $stmt->execute([$key, $d['label'], $d['value'], $d['unit'], $d['trend'], $d['status'], $d['icon']]);
            }
        }
    }

    /** Return all sensor rows as assoc array keyed by sensor_key */
    public static function getAll(): array
    {
        self::ensureTable();
        $db   = self::getDB();
        $rows = $db->query("SELECT * FROM sensor_readings ORDER BY id ASC")->fetchAll();
        $out  = [];
        foreach ($rows as $row) {
            $out[$row['sensor_key']] = $row;
        }
        return $out;
    }

    /** Return a single sensor row */
    public static function getByKey(string $key): ?array
    {
        self::ensureTable();
        $db   = self::getDB();
        $stmt = $db->prepare("SELECT * FROM sensor_readings WHERE sensor_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Update a sensor reading (admin only) */
    public static function update(string $key, array $data, int $updatedBy): bool
    {
        self::ensureTable();
        $db = self::getDB();

        $allowed = ['value', 'unit', 'trend', 'status', 'icon', 'label'];
        $sets    = [];
        $params  = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]   = "`{$col}` = ?";
                $params[] = $data[$col];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sets[]   = '`updated_by` = ?';
        $params[] = $updatedBy;
        $params[] = $key;

        $sql  = 'UPDATE sensor_readings SET ' . implode(', ', $sets) . ' WHERE sensor_key = ?';
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Upsert: insert or update a sensor row */
    public static function upsert(string $key, array $data, int $updatedBy): bool
    {
        self::ensureTable();
        $existing = self::getByKey($key);
        if ($existing) {
            return self::update($key, $data, $updatedBy);
        }

        $db   = self::getDB();
        $stmt = $db->prepare("
            INSERT INTO sensor_readings (sensor_key, label, value, unit, trend, status, icon, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $key,
            $data['label']  ?? $key,
            $data['value']  ?? '0',
            $data['unit']   ?? '',
            $data['trend']  ?? '',
            $data['status'] ?? 'ok',
            $data['icon']   ?? '',
            $updatedBy,
        ]);
    }

    /** Reset all sensors to factory defaults */
    public static function resetDefaults(int $updatedBy): void
    {
        self::ensureTable();
        foreach (self::$defaults as $key => $d) {
            self::upsert($key, $d, $updatedBy);
        }
    }
}
