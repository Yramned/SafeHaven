<?php
/**
 * SafeHaven - Admin Analytics Model
 * Aggregates real data from the database for the admin dashboard.
 */

require_once CONFIG_PATH . 'database.php';

class AdminAnalyticsModel {

    private static function getDB() {
        return Database::getInstance()->getConnection();
    }

    /** Overview stat cards */
    public static function getOverviewStats(): array {
        $db = self::getDB();

        // Users by role
        $stmt = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
        $roleCounts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $roleCounts[strtolower($row['role'])] = (int)$row['cnt'];
        }

        // Active (unread) alerts
        $activeAlerts = 0;
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM alerts WHERE is_read = 0");
            $activeAlerts = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}

        // Centers
        $totalCenters = 0;
        $availableCenters = 0;
        try {
            $stmt = $db->query("SELECT COUNT(*) as total, SUM(status IN ('accepting','limited')) as avail FROM evacuation_centers");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalCenters     = (int)($row['total'] ?? 0);
            $availableCenters = (int)($row['avail'] ?? 0);
        } catch (Exception $e) {}

        // Pending requests
        $pendingRequests = 0;
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM evacuation_requests WHERE status = 'pending'");
            $pendingRequests = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}

        // Total inside centers
        $evacueeCount = 0;
        try {
            $stmt = $db->query("SELECT COALESCE(SUM(current_occupancy),0) FROM evacuation_centers");
            $evacueeCount = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}

        return [
            'total_users'         => array_sum($roleCounts),
            'total_evacuees'      => $roleCounts['evacuee'] ?? 0,
            'total_admins'        => $roleCounts['admin']   ?? 0,
            'active_alerts'       => $activeAlerts,
            'total_centers'       => $totalCenters,
            'available_centers'   => $availableCenters,
            'pending_requests'    => $pendingRequests,
            'evacuees_in_centers' => $evacueeCount,
        ];
    }

    /** Monthly evacuation requests vs alerts — last 6 months */
    public static function getMonthlyActivity(): array {
        $db = self::getDB();

        $labels = [];
        $ym_keys = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts = strtotime("-$i months");
            $labels[]  = date('M', $ts);
            $ym_keys[] = date('Y-m', $ts);
        }

        $evac   = array_fill_keys($ym_keys, 0);
        $alerts = array_fill_keys($ym_keys, 0);

        try {
            $stmt = $db->query("
                SELECT DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as cnt
                FROM evacuation_requests
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY ym
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (isset($evac[$row['ym']])) $evac[$row['ym']] = (int)$row['cnt'];
            }
        } catch (Exception $e) {}

        try {
            $stmt = $db->query("
                SELECT DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as cnt
                FROM alerts
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY ym
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (isset($alerts[$row['ym']])) $alerts[$row['ym']] = (int)$row['cnt'];
            }
        } catch (Exception $e) {}

        return [
            'labels'      => $labels,
            'evacuations' => array_values($evac),
            'alerts'      => array_values($alerts),
        ];
    }

    /** User role distribution */
    public static function getUserRoleBreakdown(): array {
        $db = self::getDB();
        $data = ['evacuee' => 0, 'admin' => 0];
        $stmt = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data[strtolower($row['role'])] = (int)$row['cnt'];
        }
        return $data;
    }

    /** Alert severity breakdown */
    public static function getAlertSeverityBreakdown(): array {
        $db = self::getDB();
        $data = ['critical' => 0, 'evacuation' => 0, 'warning' => 0, 'info' => 0];
        try {
            $stmt = $db->query("SELECT severity, COUNT(*) as cnt FROM alerts GROUP BY severity");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = strtolower($row['severity']);
                if (isset($data[$key])) $data[$key] = (int)$row['cnt'];
            }
        } catch (Exception $e) {}
        return $data;
    }

    /** Evacuation request status breakdown — matches actual DB enum */
    public static function getRequestStatusBreakdown(): array {
        $db = self::getDB();
        // DB enum: pending, approved, rejected, completed, denied
        $data = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'completed' => 0, 'denied' => 0];
        try {
            $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM evacuation_requests GROUP BY status");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = strtolower($row['status']);
                if (array_key_exists($key, $data)) $data[$key] = (int)$row['cnt'];
            }
        } catch (Exception $e) {}
        return $data;
    }

    /** Request priority breakdown */
    public static function getRequestPriorityBreakdown(): array {
        $db = self::getDB();
        $data = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
        try {
            $stmt = $db->query("SELECT LOWER(priority) as priority, COUNT(*) as cnt FROM evacuation_requests WHERE priority IS NOT NULL GROUP BY priority");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = strtolower($row['priority']);
                if (array_key_exists($key, $data)) $data[$key] = (int)$row['cnt'];
            }
        } catch (Exception $e) {}
        return $data;
    }

    /** Center capacity table */
    public static function getCenterCapacityData(): array {
        $db = self::getDB();
        $rows = [];
        try {
            $stmt = $db->query("SELECT name, barangay, capacity, current_occupancy, status FROM evacuation_centers ORDER BY name ASC");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cap = max(1, (int)($row['capacity'] ?? 1));
                $occ = (int)($row['current_occupancy'] ?? 0);
                $row['pct'] = min(100, (int)round(($occ / $cap) * 100));
                $rows[] = $row;
            }
        } catch (Exception $e) {}
        return $rows;
    }

    /** Recent evacuation requests (last 10) */
    public static function getRecentRequests(): array {
        $db = self::getDB();
        $rows = [];
        try {
            $stmt = $db->query("
                SELECT
                    er.id,
                    er.status,
                    er.priority,
                    er.special_needs,
                    er.created_at,
                    er.location_barangay,
                    u.full_name AS user_name
                FROM evacuation_requests er
                LEFT JOIN users u ON er.user_id = u.id
                ORDER BY er.created_at DESC
                LIMIT 10
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (!empty($row['special_needs'])) {
                    $sn = json_decode($row['special_needs'], true);
                    $row['special_needs_display'] = is_array($sn)
                        ? implode(', ', array_filter($sn))
                        : $row['special_needs'];
                } else {
                    $row['special_needs_display'] = 'None';
                }
                $rows[] = $row;
            }
        } catch (Exception $e) {}
        return $rows;
    }
}
