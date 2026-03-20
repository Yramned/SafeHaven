<?php
/**
 * SafeHaven - Evacuation Center Model
 * All queries dynamically detect existing columns to support any DB version.
 */

require_once CONFIG_PATH . 'database.php';

class EvacuationCenterModel {
    private static function getDB() {
        return Database::getInstance()->getConnection();
    }

    /** Return column names that actually exist in evacuation_centers */
    private static function getCols() {
        $db   = self::getDB();
        $cols = [];
        try {
            $res = $db->query("SHOW COLUMNS FROM evacuation_centers");
            foreach ($res->fetchAll() as $row) { $cols[] = $row['Field']; }
        } catch (Exception $e) {}
        return $cols;
    }

    /** Get all evacuation centers */
    public static function getAll() {
        $db   = self::getDB();
        $cols = self::getCols();
        $order = in_array('name', $cols) ? 'ORDER BY name ASC' : 'ORDER BY id ASC';
        $stmt = $db->query("SELECT * FROM evacuation_centers {$order}");
        return $stmt->fetchAll();
    }

    /** Get evacuation center by ID */
    public static function getById($id) {
        $db   = self::getDB();
        $stmt = $db->prepare("SELECT * FROM evacuation_centers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Get centers by status */
    public static function getByStatus($status) {
        $db   = self::getDB();
        $cols = self::getCols();
        $order = in_array('name', $cols) ? 'ORDER BY name ASC' : 'ORDER BY id ASC';
        $where = in_array('status', $cols) ? 'WHERE status = ?' : 'WHERE 1=1';
        $stmt = $db->prepare("SELECT * FROM evacuation_centers {$where} {$order}");
        $stmt->execute(in_array('status', $cols) ? [$status] : []);
        return $stmt->fetchAll();
    }

    /** Get available centers (accepting or limited) */
    public static function getAvailable() {
        $db   = self::getDB();
        $cols = self::getCols();

        if (in_array('status', $cols) && in_array('current_occupancy', $cols)) {
            $stmt = $db->query("
                SELECT * FROM evacuation_centers
                WHERE status IN ('accepting', 'limited')
                ORDER BY status ASC, current_occupancy ASC
            ");
        } elseif (in_array('status', $cols)) {
            $stmt = $db->query("
                SELECT * FROM evacuation_centers
                WHERE status IN ('accepting', 'limited')
                ORDER BY id ASC
            ");
        } else {
            $stmt = $db->query("SELECT * FROM evacuation_centers ORDER BY id ASC");
        }
        return $stmt->fetchAll();
    }

    /** Find the best available center (most free space) */
    public static function findBestAvailable() {
        $db   = self::getDB();
        $cols = self::getCols();

        if (in_array('status', $cols) && in_array('capacity', $cols) && in_array('current_occupancy', $cols)) {
            $stmt = $db->query("
                SELECT * FROM evacuation_centers
                WHERE status = 'accepting'
                ORDER BY (capacity - current_occupancy) DESC
                LIMIT 1
            ");
        } elseif (in_array('status', $cols)) {
            $stmt = $db->query("
                SELECT * FROM evacuation_centers
                WHERE status = 'accepting'
                ORDER BY id ASC LIMIT 1
            ");
        } else {
            $stmt = $db->query("SELECT * FROM evacuation_centers ORDER BY id ASC LIMIT 1");
        }
        return $stmt->fetch();
    }

    /** Update center occupancy */
    public static function updateOccupancy($centerId, $newOccupancy) {
        $db   = self::getDB();
        $cols = self::getCols();

        $center = self::getById($centerId);
        if (!$center) { return false; }

        $setParts = [];
        $params   = [];

        if (in_array('current_occupancy', $cols)) {
            $setParts[] = 'current_occupancy = ?';
            $params[]   = $newOccupancy;
        }

        // Recalculate status if we have the needed columns
        if (in_array('status', $cols) && in_array('capacity', $cols)) {
            $capacity   = (int)($center['capacity'] ?? 0);
            $status     = 'accepting';
            if ($capacity > 0) {
                if ($newOccupancy >= $capacity)        { $status = 'full'; }
                elseif ($newOccupancy >= $capacity * 0.8) { $status = 'limited'; }
            }
            $setParts[] = 'status = ?';
            $params[]   = $status;
        }

        if (in_array('updated_at', $cols)) {
            $setParts[] = 'updated_at = NOW()';
        }

        if (empty($setParts)) { return false; }

        $params[] = $centerId;
        $sql      = "UPDATE evacuation_centers SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt     = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Increment occupancy */
    public static function incrementOccupancy($centerId, $count = 1) {
        $center = self::getById($centerId);
        if (!$center) { return false; }
        $newOccupancy = (int)($center['current_occupancy'] ?? 0) + (int)$count;
        return self::updateOccupancy($centerId, $newOccupancy);
    }

    /** Decrement occupancy */
    public static function decrementOccupancy($centerId, $count = 1) {
        $center = self::getById($centerId);
        if (!$center) { return false; }
        $newOccupancy = max(0, (int)($center['current_occupancy'] ?? 0) - (int)$count);
        return self::updateOccupancy($centerId, $newOccupancy);
    }

    /** Get aggregate statistics */
    public static function getStatistics() {
        $db   = self::getDB();
        $cols = self::getCols();

        $capCol  = in_array('capacity',          $cols) ? 'SUM(capacity)'          : '0';
        $occCol  = in_array('current_occupancy', $cols) ? 'SUM(current_occupancy)' : '0';
        $stAcc   = in_array('status', $cols) ? "SUM(CASE WHEN status = 'accepting' THEN 1 ELSE 0 END)" : '0';
        $stLim   = in_array('status', $cols) ? "SUM(CASE WHEN status = 'limited'   THEN 1 ELSE 0 END)" : '0';
        $stFull  = in_array('status', $cols) ? "SUM(CASE WHEN status = 'full'      THEN 1 ELSE 0 END)" : '0';

        $stmt = $db->query("
            SELECT
                COUNT(*)      as total_centers,
                {$capCol}     as total_capacity,
                {$occCol}     as total_evacuees,
                {$stAcc}      as accepting,
                {$stLim}      as limited,
                {$stFull}     as full
            FROM evacuation_centers
        ");
        $stats = $stmt->fetch();

        $cap = (int)($stats['total_capacity'] ?? 0);
        $occ = (int)($stats['total_evacuees'] ?? 0);

        $stats['occupancy_rate'] = $cap > 0 ? round(($occ / $cap) * 100) : 0;
        $stats['available_beds'] = max(0, $cap - $occ);

        return $stats;
    }

    /** Create a new evacuation center */
    public static function create($data) {
        $db   = self::getDB();
        $cols = self::getCols();

        $wanted = [
            'name'              => $data['name']              ?? null,
            'barangay'          => $data['barangay']          ?? null,
            'address'           => $data['address']           ?? null,
            'latitude'          => $data['latitude']          ?? null,
            'longitude'         => $data['longitude']         ?? null,
            'capacity'          => $data['capacity']          ?? 0,
            'current_occupancy' => $data['current_occupancy'] ?? 0,
            'status'            => $data['status']            ?? 'accepting',
            'contact_number'    => $data['contact_number']    ?? null,
            'facilities'        => $data['facilities']        ?? null,
        ];

        $insertCols = [];
        $insertVals = [];
        foreach ($wanted as $col => $val) {
            if (in_array($col, $cols)) {
                $insertCols[] = $col;
                $insertVals[] = $val;
            }
        }

        if (empty($insertCols)) { return false; }

        $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
        $colList      = implode(', ', $insertCols);
        $stmt         = $db->prepare("INSERT INTO evacuation_centers ({$colList}) VALUES ({$placeholders})");
        return $stmt->execute($insertVals);
    }

    /** Update an evacuation center */
    public static function update($id, $data) {
        $db   = self::getDB();
        $cols = self::getCols();

        $possible = ['name', 'barangay', 'address', 'latitude', 'longitude',
                     'capacity', 'contact_number', 'facilities'];

        $setParts = [];
        $params   = [];
        foreach ($possible as $field) {
            if (isset($data[$field]) && in_array($field, $cols)) {
                $setParts[] = "{$field} = ?";
                $params[]   = $data[$field];
            }
        }

        if (empty($setParts)) { return false; }

        if (in_array('updated_at', $cols)) {
            $setParts[] = 'updated_at = NOW()';
        }

        $params[] = $id;
        $sql      = "UPDATE evacuation_centers SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt     = $db->prepare($sql);
        return $stmt->execute($params);
    }
}