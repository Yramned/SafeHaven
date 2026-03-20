<?php
/**
 * SafeHaven - Capacity Model
 * All queries dynamically detect existing columns to support any DB version.
 */

require_once CONFIG_PATH . 'database.php';

class CapacityModel {
    private static function getDB() {
        return Database::getInstance()->getConnection();
    }

    /** Return column names that actually exist in a table */
    private static function getCols($table) {
        $db   = self::getDB();
        $cols = [];
        try {
            $res = $db->query("SHOW COLUMNS FROM `{$table}`");
            foreach ($res->fetchAll() as $row) { $cols[] = $row['Field']; }
        } catch (Exception $e) {}
        return $cols;
    }

    /**
     * Log a capacity change — only inserts columns that exist.
     */
    public static function logChange($centerId, $occupancy, $capacity, $changeType = 'manual-update', $changedBy = null, $notes = null) {
        $db   = self::getDB();
        $cols = self::getCols('capacity_logs');

        if (empty($cols)) { return false; } // table doesn't exist yet

        // Map every possible column name → value
        $wanted = [
            'center_id'   => $centerId,
            'occupancy'   => $occupancy,
            'capacity'    => $capacity,
            'change_type' => $changeType,
            'changed_by'  => $changedBy,
            'notes'       => $notes,
        ];

        $insertCols = [];
        $insertVals = [];
        foreach ($wanted as $col => $val) {
            if (in_array($col, $cols)) {
                $insertCols[] = $col;
                $insertVals[] = $val;
            }
        }

        // Add created_at only if the column exists and has no DEFAULT
        if (in_array('created_at', $cols)) {
            $insertCols[] = 'created_at';
            $insertVals[] = date('Y-m-d H:i:s');
        }

        if (empty($insertCols)) { return false; }

        $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
        $colList      = implode(', ', $insertCols);

        try {
            $stmt = $db->prepare("INSERT INTO capacity_logs ({$colList}) VALUES ({$placeholders})");
            return $stmt->execute($insertVals);
        } catch (Exception $e) {
            error_log("CapacityModel::logChange failed: " . $e->getMessage());
            return false; // Don't crash the whole request over a log failure
        }
    }

    /**
     * Get all capacity logs for a center.
     */
    public static function getLogsByCenter($centerId, $limit = 50) {
        $db      = self::getDB();
        $clCols  = self::getCols('capacity_logs');
        $uCols   = self::getCols('users');

        if (empty($clCols)) { return []; }

        $orderBy  = in_array('created_at', $clCols) ? 'cl.created_at DESC' : 'cl.id DESC';
        $nameCol  = in_array('full_name', $uCols) ? 'u.full_name as changed_by_name' : 'NULL as changed_by_name';
        $joinUser = in_array('changed_by', $clCols) ? "LEFT JOIN users u ON cl.changed_by = u.id" : '';

        $stmt = $db->prepare("
            SELECT cl.*, {$nameCol}
            FROM capacity_logs cl
            {$joinUser}
            WHERE cl.center_id = ?
            ORDER BY {$orderBy}
            LIMIT ?
        ");
        $stmt->execute([$centerId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get recent capacity logs across all centers.
     */
    public static function getRecentLogs($limit = 100) {
        $db     = self::getDB();
        $clCols = self::getCols('capacity_logs');
        $ecCols = self::getCols('evacuation_centers');
        $uCols  = self::getCols('users');

        if (empty($clCols)) { return []; }

        $orderBy     = in_array('created_at', $clCols) ? 'cl.created_at DESC' : 'cl.id DESC';
        $centerName  = in_array('name', $ecCols)      ? 'ec.name as center_name'       : 'NULL as center_name';
        $userName    = in_array('full_name', $uCols)   ? 'u.full_name as changed_by_name' : 'NULL as changed_by_name';
        $joinCenter  = "LEFT JOIN evacuation_centers ec ON cl.center_id = ec.id";
        $joinUser    = in_array('changed_by', $clCols) ? "LEFT JOIN users u ON cl.changed_by = u.id" : '';

        $stmt = $db->prepare("
            SELECT cl.*, {$centerName}, {$userName}
            FROM capacity_logs cl
            {$joinCenter}
            {$joinUser}
            ORDER BY {$orderBy}
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get capacity history for a center (for charts).
     */
    public static function getHistoryByCenter($centerId, $hours = 24) {
        $db   = self::getDB();
        $cols = self::getCols('capacity_logs');

        if (empty($cols)) { return []; }

        $occCol  = in_array('occupancy',  $cols) ? 'occupancy'  : 'NULL as occupancy';
        $capCol  = in_array('capacity',   $cols) ? 'capacity'   : 'NULL as capacity';
        $dateCol = in_array('created_at', $cols) ? 'created_at' : 'NULL as created_at';
        $where   = in_array('created_at', $cols)
            ? "AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)"
            : '';

        $params = in_array('created_at', $cols)
            ? [$centerId, $hours]
            : [$centerId];

        $orderBy = in_array('created_at', $cols) ? 'created_at ASC' : 'id ASC';

        $stmt = $db->prepare("
            SELECT {$occCol}, {$capCol}, {$dateCol}
            FROM capacity_logs
            WHERE center_id = ? {$where}
            ORDER BY {$orderBy}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}