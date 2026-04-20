<?php
/**
 * SafeHaven - Alerts Controllers
 * Handles GET (page render) and POST (create, delete, mark-read) for situational alerts.
 */

require_once MODEL_PATH . 'AlertModel.php';
require_once MODEL_PATH . 'SensorDataModel.php';
require_once MODEL_PATH . 'UserModel.php';

class AlertsController {

    public static function handle() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }

        $userRole = strtolower($_SESSION['user_role'] ?? 'evacuee');
        $isAdmin  = $userRole === 'admin';

        // ── CSRF token ────────────────────────────────────────────────────────
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // ── POST: handle actions ──────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action   = $_POST['action']   ?? '';
            $token    = $_POST['csrf_token'] ?? '';

            // CSRF guard
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                $_SESSION['alert_flash'] = ['type'=>'error','msg'=>'Security token invalid. Please refresh.'];
                header('Location: ' . BASE_URL . 'index.php?page=alerts');
                exit;
            }

            // ── JSON responses for fetch() calls ─────────────────────────────
            if ($action === 'mark_read') {
                header('Content-Type: application/json');
                $alertId = (int)($_POST['alert_id'] ?? 0);
                if ($alertId) {
                    AlertModel::markRead($alertId);
                    echo json_encode(['ok' => true]);
                } else {
                    echo json_encode(['ok' => false]);
                }
                exit;
            }

            // ── Admin-only form actions ───────────────────────────────────────
            if ($action === 'create_alert') {
                if (!$isAdmin) {
                    $_SESSION['alert_flash'] = ['type'=>'error','msg'=>'Permission denied.'];
                    header('Location: ' . BASE_URL . 'index.php?page=alerts');
                    exit;
                }

                $title    = trim($_POST['title']    ?? '');
                $message  = trim($_POST['message']  ?? '');
                $severity = trim($_POST['severity'] ?? 'info');
                $location = trim($_POST['location'] ?? '');

                $allowed = ['critical','evacuation','warning','info'];
                if (empty($title) || empty($message) || !in_array($severity, $allowed)) {
                    $_SESSION['alert_flash'] = ['type'=>'error','msg'=>'All required fields must be filled.'];
                    header('Location: ' . BASE_URL . 'index.php?page=alerts');
                    exit;
                }

                $id = AlertModel::create([
                    'title'      => $title,
                    'message'    => $message,
                    'severity'   => $severity,
                    'location'   => $location ?: null,
                    'created_by' => $_SESSION['user_id'],
                ]);

                // ── Send SMS to all evacuees + their family numbers ──────────
                if ($id) {
                    try {
                        require_once ROOT_PATH . 'services/PhilSmsService.php';
                        $alertRow  = AlertModel::getById($id);
                        $allUsers  = UserModel::getAllEvacuees();
                        $numbers   = PhilSmsService::collectAlertNumbers($allUsers);
                        if (!empty($numbers)) {
                            $smsText = PhilSmsService::buildAlertMessage($alertRow);
                            PhilSmsService::send($numbers, $smsText);
                        }
                    } catch (Exception $smsEx) {
                        error_log('[PhilSMS] Alert broadcast error: ' . $smsEx->getMessage());
                    }
                }
                // ─────────────────────────────────────────────────────────────

                $_SESSION['alert_flash'] = $id
                    ? ['type'=>'success','msg'=>'Alert broadcast successfully.']
                    : ['type'=>'error',  'msg'=>'Failed to create alert.'];

                header('Location: ' . BASE_URL . 'index.php?page=alerts');
                exit;
            }

            if ($action === 'delete_alert') {
                if (!$isAdmin) {
                    $_SESSION['alert_flash'] = ['type'=>'error','msg'=>'Permission denied.'];
                    header('Location: ' . BASE_URL . 'index.php?page=alerts');
                    exit;
                }

                $alertId = (int)($_POST['alert_id'] ?? 0);
                if ($alertId && AlertModel::delete($alertId)) {
                    $_SESSION['alert_flash'] = ['type'=>'success','msg'=>'Alert deleted.'];
                } else {
                    $_SESSION['alert_flash'] = ['type'=>'error','msg'=>'Could not delete alert.'];
                }
                header('Location: ' . BASE_URL . 'index.php?page=alerts');
                exit;
            }

            // Unknown POST
            header('Location: ' . BASE_URL . 'index.php?page=alerts');
            exit;
        }

        // ── GET: render page ─────────────────────────────────────────────────
        $filterSeverity = $_GET['severity'] ?? 'all';
        $alerts         = AlertModel::getAll($filterSeverity);
        $counts         = AlertModel::getCounts();
        $sensorData     = SensorDataModel::getAll();

        $userId    = (int)$_SESSION['user_id'];
        $userName  = $_SESSION['user_name'] ?? '';
        $alertFlash = $_SESSION['alert_flash'] ?? null;
        unset($_SESSION['alert_flash']);

        $pageTitle  = 'Situational Alerts - SafeHaven';
        $activePage = 'alerts';
        $extraCss   = ['assets/css/safehaven-system.css','assets/css/Heatmonitoring.css','assets/css/alerts.css'];
        $extraJs    = ['assets/js/alerts.js'];
        $csrfToken  = $_SESSION['csrf_token'];

        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/alerts.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }
}
