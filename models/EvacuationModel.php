<?php
/**
 * SafeHaven - Evacuation Request Model
 * Handles evacuation requests in the database
 */

require_once CONFIG_PATH . 'database.php';

class EvacuationModel {
    private static function getDB() {
        return Database::getInstance()->getConnection();
    }
    
    /**
     * Generate a unique confirmation code
     */
    private static function generateConfirmationCode() {
        $year = date('Y');
        $randomStr = strtoupper(substr(md5(uniqid(rand(), true)), 0, 7));
        return "EVAC-{$year}-{$randomStr}";
    }
    
    /**
     * Create a new evacuation request
     */
    public static function create($data) {
        $db = self::getDB();

        $specialNeeds = isset($data['special_needs']) && is_array($data['special_needs'])
            ? json_encode($data['special_needs'])
            : json_encode([]);

        // Detect actual columns AND their key names so we handle any schema version
        $cols     = [];   // column Field names
        $colKeys  = [];   // column Key values (PRI / UNI / MUL / '')
        try {
            $res = $db->query("SHOW COLUMNS FROM evacuation_requests");
            foreach ($res->fetchAll() as $row) {
                $cols[]              = $row['Field'];
                $colKeys[$row['Field']] = $row['Key'];
            }
        } catch (Exception $e) { return null; }

        // --- Resolve the confirmation-code column name ---
        // Some installs call it "confirmation_code", others "request_code"
        $codeCol = null;
        foreach (['confirmation_code', 'request_code', 'conf_code', 'code'] as $candidate) {
            if (in_array($candidate, $cols)) { $codeCol = $candidate; break; }
        }

        // Generate a guaranteed-unique code (retry up to 10x on collision)
        $confirmationCode = null;
        if ($codeCol) {
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $year      = date('Y');
                $randStr   = strtoupper(substr(md5(uniqid(rand(), true)), 0, 7));
                $candidate = "EVAC-{$year}-{$randStr}";

                // Check it doesn't already exist
                $chk = $db->prepare("SELECT id FROM evacuation_requests WHERE {$codeCol} = ? LIMIT 1");
                $chk->execute([$candidate]);
                if (!$chk->fetch()) {
                    $confirmationCode = $candidate;
                    break;
                }
            }
            if (!$confirmationCode) {
                // Absolute fallback — timestamp + random, virtually impossible to collide
                $confirmationCode = 'EVAC-' . date('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 4));
            }
        }

        // --- Build wanted column => value map ---
        $wanted = [
            'user_id'        => $data['user_id'],
            'center_id'      => $data['center_id']       ?? null,
            'location_street'    => $data['location_street']    ?? null,
            'location_barangay'  => $data['location_barangay']  ?? null,
            'location_city'      => $data['location_city']      ?? null,
            'location_latitude'  => $data['location_latitude']  ?? null,
            'location_longitude' => $data['location_longitude'] ?? null,
            'priority'       => $data['priority']         ?? null,
            'family_members' => $data['family_members']   ?? 1,
            'special_needs'  => $specialNeeds,
            'status'         => $data['status']           ?? 'approved',
            'notes'          => $data['notes']            ?? null,
        ];

        // Add the code under whatever column name the table actually uses
        if ($codeCol && $confirmationCode) {
            $wanted[$codeCol] = $confirmationCode;
        }

        // Only insert columns that actually exist
        $insertCols = [];
        $insertVals = [];
        foreach ($wanted as $col => $val) {
            if (in_array($col, $cols)) {
                $insertCols[] = $col;
                $insertVals[] = $val;
            }
        }

        if (empty($insertCols)) { return null; }

        $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
        $colList      = implode(', ', $insertCols);

        try {
            $stmt = $db->prepare("INSERT INTO evacuation_requests ({$colList}) VALUES ({$placeholders})");
            $stmt->execute($insertVals);
        } catch (Exception $e) {
            error_log("EvacuationModel::create INSERT failed: " . $e->getMessage());
            // If it still fails on unique constraint, surface a clear message
            if (strpos($e->getMessage(), '1062') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
                throw new Exception("Duplicate confirmation code — please try submitting again.");
            }
            throw $e;
        }

        $requestId = $db->lastInsertId();

        // Attach the generated code to a stub in case getById can't find it
        $result = self::getById($requestId);
        if (!empty($result['id']) && $codeCol && empty($result['confirmation_code'])) {
            $result['confirmation_code'] = $confirmationCode;
        }
        if (empty($result['confirmation_code']) && $confirmationCode) {
            $result['confirmation_code'] = $confirmationCode;
        }
        return $result;
    }
    
    /**
     * Get evacuation request by ID
     */
    public static function getById($id) {
        $db = self::getDB();

        // Detect columns in joined tables so we never reference missing ones
        $ecCols = [];
        $uCols  = [];
        try {
            $r = $db->query("SHOW COLUMNS FROM evacuation_centers");
            foreach ($r->fetchAll() as $row) { $ecCols[] = $row['Field']; }
            $r = $db->query("SHOW COLUMNS FROM users");
            foreach ($r->fetchAll() as $row) { $uCols[]  = $row['Field']; }
        } catch (Exception $e) {}

        // Build safe ec.* selects
        $ecMap = [
            'name'              => 'center_name',
            'address'           => 'center_address',
            'barangay'          => 'center_barangay',
            'latitude'          => 'center_latitude',
            'longitude'         => 'center_longitude',
            'contact_number'    => 'center_contact',
            'current_occupancy' => 'center_occupancy',
            'capacity'          => 'center_capacity',
        ];
        $ecSelects = [];
        foreach ($ecMap as $col => $alias) {
            $ecSelects[] = in_array($col, $ecCols) ? "ec.{$col} as {$alias}" : "NULL as {$alias}";
        }

        // Build safe u.* selects
        $uMap = [
            'full_name'    => 'user_name',
            'email'        => 'user_email',
            'phone_number' => 'user_phone',
        ];
        $uSelects = [];
        foreach ($uMap as $col => $alias) {
            $uSelects[] = in_array($col, $uCols) ? "u.{$col} as {$alias}" : "NULL as {$alias}";
        }

        $joinSelects = implode(", ", array_merge($ecSelects, $uSelects));

        // Code column alias for er table
        $erCols = [];
        try {
            $res = $db->query("SHOW COLUMNS FROM evacuation_requests");
            foreach ($res->fetchAll() as $row) { $erCols[] = $row['Field']; }
        } catch (Exception $e) {}

        $codeSelect = '';
        foreach (['confirmation_code', 'request_code', 'conf_code', 'code'] as $c) {
            if (in_array($c, $erCols)) {
                $codeSelect = ($c !== 'confirmation_code') ? ", er.{$c} as confirmation_code" : '';
                break;
            }
        }

        $stmt = $db->prepare("
            SELECT er.*{$codeSelect}, {$joinSelects}
            FROM evacuation_requests er
            LEFT JOIN evacuation_centers ec ON er.center_id = ec.id
            LEFT JOIN users u ON er.user_id = u.id
            WHERE er.id = ?
        ");
        $stmt->execute([$id]);
        $request = $stmt->fetch();

        if (!$request) {
            return ['id' => $id, 'confirmation_code' => null,
                    'family_members' => null, 'special_needs' => []];
        }

        if (!empty($request['special_needs'])) {
            $request['special_needs'] = json_decode($request['special_needs'], true) ?? [];
        } else {
            $request['special_needs'] = [];
        }

        return $request;
    }
    
    /**
     * Get evacuation request by confirmation code
     */
    public static function getByConfirmationCode($code) {
        $db = self::getDB();

        // Detect columns in joined tables so we never reference missing ones
        $ecCols = [];
        $uCols  = [];
        try {
            $r = $db->query("SHOW COLUMNS FROM evacuation_centers");
            foreach ($r->fetchAll() as $row) { $ecCols[] = $row['Field']; }
            $r = $db->query("SHOW COLUMNS FROM users");
            foreach ($r->fetchAll() as $row) { $uCols[]  = $row['Field']; }
        } catch (Exception $e) {}

        // Build safe ec.* selects
        $ecMap = [
            'name'              => 'center_name',
            'address'           => 'center_address',
            'barangay'          => 'center_barangay',
            'latitude'          => 'center_latitude',
            'longitude'         => 'center_longitude',
            'contact_number'    => 'center_contact',
            'current_occupancy' => 'center_occupancy',
            'capacity'          => 'center_capacity',
        ];
        $ecSelects = [];
        foreach ($ecMap as $col => $alias) {
            $ecSelects[] = in_array($col, $ecCols) ? "ec.{$col} as {$alias}" : "NULL as {$alias}";
        }

        // Build safe u.* selects
        $uMap = [
            'full_name'    => 'user_name',
            'email'        => 'user_email',
            'phone_number' => 'user_phone',
        ];
        $uSelects = [];
        foreach ($uMap as $col => $alias) {
            $uSelects[] = in_array($col, $uCols) ? "u.{$col} as {$alias}" : "NULL as {$alias}";
        }

        $joinSelects = implode(", ", array_merge($ecSelects, $uSelects));

        // Find which column holds the code
        $erCols = [];
        try {
            $res = $db->query("SHOW COLUMNS FROM evacuation_requests");
            foreach ($res->fetchAll() as $row) { $erCols[] = $row['Field']; }
        } catch (Exception $e) {}

        $codeCol = 'confirmation_code';
        foreach (['confirmation_code', 'request_code', 'conf_code', 'code'] as $c) {
            if (in_array($c, $erCols)) { $codeCol = $c; break; }
        }

        $stmt = $db->prepare("
            SELECT er.*, {$joinSelects}
            FROM evacuation_requests er
            LEFT JOIN evacuation_centers ec ON er.center_id = ec.id
            LEFT JOIN users u ON er.user_id = u.id
            WHERE er.{$codeCol} = ?
        ");
        $stmt->execute([$code]);
        $request = $stmt->fetch();

        if ($request && !empty($request['special_needs'])) {
            $request['special_needs'] = json_decode($request['special_needs'], true);
        }

        return $request;
    }
    
    /**
     * Get all evacuation requests for a user
     */
    public static function getByUserId($userId) {
        $db = self::getDB();

        // Detect columns in joined tables so we never reference missing ones
        $ecCols = [];
        $uCols  = [];
        try {
            $r = $db->query("SHOW COLUMNS FROM evacuation_centers");
            foreach ($r->fetchAll() as $row) { $ecCols[] = $row['Field']; }
            $r = $db->query("SHOW COLUMNS FROM users");
            foreach ($r->fetchAll() as $row) { $uCols[]  = $row['Field']; }
        } catch (Exception $e) {}

        // Build safe ec.* selects
        $ecMap = [
            'name'              => 'center_name',
            'address'           => 'center_address',
            'barangay'          => 'center_barangay',
            'latitude'          => 'center_latitude',
            'longitude'         => 'center_longitude',
            'contact_number'    => 'center_contact',
            'current_occupancy' => 'center_occupancy',
            'capacity'          => 'center_capacity',
        ];
        $ecSelects = [];
        foreach ($ecMap as $col => $alias) {
            $ecSelects[] = in_array($col, $ecCols) ? "ec.{$col} as {$alias}" : "NULL as {$alias}";
        }

        // Build safe u.* selects
        $uMap = [
            'full_name'    => 'user_name',
            'email'        => 'user_email',
            'phone_number' => 'user_phone',
        ];
        $uSelects = [];
        foreach ($uMap as $col => $alias) {
            $uSelects[] = in_array($col, $uCols) ? "u.{$col} as {$alias}" : "NULL as {$alias}";
        }

        $joinSelects = implode(", ", array_merge($ecSelects, $uSelects));

        $ecCols2 = $ecCols; // already populated above
        $nameCol     = in_array('name',     $ecCols2) ? 'ec.name as center_name'         : 'NULL as center_name';
        $barangayCol = in_array('barangay', $ecCols2) ? 'ec.barangay as center_barangay' : 'NULL as center_barangay';

        $stmt = $db->prepare("
            SELECT er.*, {$nameCol}, {$barangayCol}
            FROM evacuation_requests er
            LEFT JOIN evacuation_centers ec ON er.center_id = ec.id
            WHERE er.user_id = ?
            ORDER BY er.id DESC
        ");
        $stmt->execute([$userId]);
        $requests = $stmt->fetchAll();

        foreach ($requests as &$request) {
            if (!empty($request['special_needs'])) {
                $request['special_needs'] = json_decode($request['special_needs'], true);
            }
        }

        return $requests;
    }
    
    /**
     * Get all evacuation requests
     */
    public static function getAll($limit = null) {
        $db = self::getDB();

        // Detect every actual column in the table - works on any DB version
        $cols = [];
        try {
            $res = $db->query("SHOW COLUMNS FROM evacuation_requests");
            foreach ($res->fetchAll() as $row) {
                $cols[] = $row['Field'];
            }
        } catch (Exception $e) {
            return [];
        }

        // All possible columns - only select ones that exist
        // Note: confirmation_code handled separately below to support 'request_code' alias
        $allPossible = [
            'id', 'user_id', 'center_id', 'status', 'priority', 'notes',
            'family_members', 'special_needs',
            'location_street', 'location_barangay', 'location_city',
            'location_latitude', 'location_longitude',
            'created_at', 'updated_at'
        ];

        $selectParts = [];
        foreach ($allPossible as $c) {
            if (in_array($c, $cols)) {
                $selectParts[] = "er.{$c}";
            } else {
                $selectParts[] = "NULL as {$c}";
            }
        }

        // Handle confirmation_code under any column name the DB uses
        $codeAliasAdded = false;
        foreach (['confirmation_code', 'request_code', 'conf_code', 'code'] as $codeCandidate) {
            if (in_array($codeCandidate, $cols)) {
                $selectParts[] = ($codeCandidate === 'confirmation_code')
                    ? 'er.confirmation_code'
                    : "er.{$codeCandidate} as confirmation_code";
                $codeAliasAdded = true;
                break;
            }
        }
        if (!$codeAliasAdded) {
            $selectParts[] = "NULL as confirmation_code";
        }

        // Safe ec/u joins for getAll
        $ecCols2 = [];
        $uCols2  = [];
        try {
            $r2 = $db->query("SHOW COLUMNS FROM evacuation_centers");
            foreach ($r2->fetchAll() as $row) { $ecCols2[] = $row['Field']; }
            $r2 = $db->query("SHOW COLUMNS FROM users");
            foreach ($r2->fetchAll() as $row) { $uCols2[]  = $row['Field']; }
        } catch (Exception $e) {}

        $selectParts[] = in_array('name',         $ecCols2) ? 'ec.name as center_name'          : 'NULL as center_name';
        $selectParts[] = in_array('full_name',     $uCols2)  ? 'u.full_name as user_name'         : 'NULL as user_name';
        $selectParts[] = in_array('phone_number',  $uCols2)  ? 'u.phone_number as user_phone'     : 'NULL as user_phone';

        $selectSql = implode(", ", $selectParts);
        $orderBy   = in_array('created_at', $cols) ? 'er.created_at DESC' : 'er.id DESC';

        $sql = "SELECT {$selectSql}
                FROM evacuation_requests er
                LEFT JOIN evacuation_centers ec ON er.center_id = ec.id
                LEFT JOIN users u ON er.user_id = u.id
                ORDER BY {$orderBy}";

        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }

        $stmt     = $db->query($sql);
        $requests = $stmt->fetchAll();

        foreach ($requests as &$request) {
            if (!empty($request['special_needs'])) {
                $request['special_needs'] = json_decode($request['special_needs'], true);
            }
        }

        return $requests;
    }
    
    /**
     * Update evacuation request status
     */
    public static function updateStatus($id, $status) {
        $db = self::getDB();
        // updated_at may not exist on older installs
        $cols = [];
        try {
            $res = $db->query("SHOW COLUMNS FROM evacuation_requests");
            foreach ($res->fetchAll() as $row) { $cols[] = $row['Field']; }
        } catch (Exception $e) {}
        $updSql = in_array('updated_at', $cols)
            ? "UPDATE evacuation_requests SET status = ?, updated_at = NOW() WHERE id = ?"
            : "UPDATE evacuation_requests SET status = ? WHERE id = ?";
        $stmt = $db->prepare($updSql);
        return $stmt->execute([$status, $id]);
    }
    
    /**
     * Count evacuation requests by status
     */
    public static function countByStatus($status) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM evacuation_requests WHERE status = ?");
        $stmt->execute([$status]);
        $result = $stmt->fetch();
        return $result['total'];
    }
}