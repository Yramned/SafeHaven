<?php
/**
 * SafeHaven - Sensor Controller
 *
 * Handles AJAX read / update of sensor_readings rows.
 * Routes (add to index.php):
 *   GET  ?page=sensor-data          → getAll()  (returns JSON)
 *   POST ?page=sensor-update        → update()  (admin only, returns JSON)
 *   POST ?page=sensor-reset         → reset()   (admin only, returns JSON)
 */

require_once MODEL_PATH . 'SensorDataModel.php';

class SensorController
{
    // ── GET: return all sensor readings as JSON ───────────────────────────────
    public static function getAll(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $sensors = SensorDataModel::getAll();
        echo json_encode(['success' => true, 'sensors' => array_values($sensors)]);
        exit;
    }

    // ── POST: update a single sensor (admin only) ─────────────────────────────
    public static function update(): void
    {
        header('Content-Type: application/json');

        if (!self::isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $key = trim($input['sensor_key'] ?? '');
        if (empty($key)) {
            echo json_encode(['success' => false, 'message' => 'sensor_key required']);
            exit;
        }

        $allowed_statuses = ['ok', 'warn', 'critical'];
        $data = [];
        if (isset($input['value']))  $data['value']  = trim($input['value']);
        if (isset($input['unit']))   $data['unit']   = trim($input['unit']);
        if (isset($input['trend']))  $data['trend']  = trim($input['trend']);
        if (isset($input['status']) && in_array($input['status'], $allowed_statuses)) {
            $data['status'] = $input['status'];
        }
        if (isset($input['icon']))  $data['icon']  = trim($input['icon']);
        if (isset($input['label'])) $data['label'] = trim($input['label']);

        if (empty($data)) {
            echo json_encode(['success' => false, 'message' => 'No valid fields supplied']);
            exit;
        }

        $ok      = SensorDataModel::upsert($key, $data, (int) $_SESSION['user_id']);
        $sensors = SensorDataModel::getAll();

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Sensor updated.' : 'Update failed.',
            'sensors' => array_values($sensors),
        ]);
        exit;
    }

    // ── POST: reset all sensors to defaults (admin only) ─────────────────────
    public static function reset(): void
    {
        header('Content-Type: application/json');

        if (!self::isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        SensorDataModel::resetDefaults((int) $_SESSION['user_id']);
        $sensors = SensorDataModel::getAll();

        echo json_encode([
            'success' => true,
            'message' => 'Sensors reset to defaults.',
            'sensors' => array_values($sensors),
        ]);
        exit;
    }

    // ── Helper ────────────────────────────────────────────────────────────────
    private static function isAdmin(): bool
    {
        return isset($_SESSION['user_id']) &&
               strtolower($_SESSION['user_role'] ?? '') === 'admin';
    }
}
