<?php
/**
 * SafeHaven - Main Entry Point & Router
 * All requests go through this file.
 */

require_once __DIR__ . '/config/config.php';
require_once CONFIG_PATH . 'database.php';

$page = $_GET['page'] ?? $_GET['action'] ?? 'home';

$publicPages = ['home', 'login', 'register', 'do-login', 'do-register', 'contact-submit'];
$isPublicPage = in_array($page, $publicPages);

if (!$isPublicPage && !isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php?page=login');
    exit;
}

switch ($page) {

    // ── PUBLIC ──────────────────────────────────────────────────────────────
    case 'home':
        $pageTitle  = 'SafeHaven – Emergency Evacuation System';
        $activePage = 'home';
        $extraCss   = ['assets/css/landing_page.css'];
        $extraJs    = ['assets/js/Main.js'];
        require_once VIEW_PATH . 'shared/header.php';
        require_once VIEW_PATH . 'pages/home.php';
        require_once VIEW_PATH . 'shared/footer.php';
        break;

    case 'contact-submit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $d = [
                'name'    => trim($_POST['name']    ?? ''),
                'email'   => trim($_POST['email']   ?? ''),
                'subject' => trim($_POST['subject'] ?? ''),
                'message' => trim($_POST['message'] ?? ''),
            ];
            $errs = [];
            if (empty($d['name']))                                  $errs[] = 'Name is required.';
            if (empty($d['email']))                                 $errs[] = 'Email is required.';
            elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errs[] = 'Invalid email.';
            if (empty($d['subject']))                               $errs[] = 'Subject is required.';
            if (empty($d['message']))                               $errs[] = 'Message is required.';

            if ($errs) {
                $_SESSION['contact_errors'] = $errs;
                $_SESSION['contact_old']    = $d;
            } else {
                // Save to DB
                try {
                    require_once MODEL_PATH . 'MessageModel.php';
                    MessageModel::create($d);
                    $_SESSION['contact_success'] = true;
                } catch (Exception $e) {
                    // Fallback: save to JSON if DB fails
                    $file = MESSAGES_FILE;
                    $all  = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
                    $all[] = array_merge($d, ['id' => count($all) + 1, 'created' => date('Y-m-d H:i:s')]);
                    file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT));
                    $_SESSION['contact_success'] = true;
                }
            }
        }
        header('Location: ' . BASE_URL . 'index.php?page=home#contact');
        exit;

    case 'login':
        require_once CONTROLLER_PATH . 'AuthController.php';
        AuthController::showLoginForm();
        break;

    case 'register':
        require_once CONTROLLER_PATH . 'AuthController.php';
        AuthController::showRegisterForm();
        break;

    case 'do-login':
        require_once CONTROLLER_PATH . 'AuthController.php';
        AuthController::login();
        break;

    case 'do-register':
        require_once CONTROLLER_PATH . 'AuthController.php';
        AuthController::register();
        break;

    case 'logout':
        require_once CONTROLLER_PATH . 'AuthController.php';
        AuthController::logout();
        break;

    // ── DASHBOARD ───────────────────────────────────────────────────────────
    case 'dashboard':
        require_once CONTROLLER_PATH . 'DashboardController.php';
        DashboardController::index();
        break;

    // ── PROFILE ─────────────────────────────────────────────────────────────
    case 'profile':
        require_once CONTROLLER_PATH . 'ProfileController.php';
        ProfileController::show();
        break;

    case 'profile-update':
        require_once CONTROLLER_PATH . 'ProfileController.php';
        ProfileController::update();
        break;

    // ── ALERTS ──────────────────────────────────────────────────────────────
    case 'alerts':
        require_once CONTROLLER_PATH . 'AlertsController.php';
        AlertsController::handle();
        break;

    // ── EVACUATION CENTERS ──────────────────────────────────────────────────
    case 'evacuation-centers':
        require_once CONTROLLER_PATH . 'EvacuationController.php';
        EvacuationController::centers();
        break;

    // ── EVACUATION REQUEST ──────────────────────────────────────────────────
    case 'evacuation-request':
        require_once CONTROLLER_PATH . 'EvacuationController.php';
        EvacuationController::requestForm();
        break;

    case 'evacuation-request-submit':
        require_once CONTROLLER_PATH . 'EvacuationController.php';
        EvacuationController::submitRequest();
        break;

    // ── RESULT ──────────────────────────────────────────────────────────────
    case 'result':
        require_once CONTROLLER_PATH . 'ResultController.php';
        ResultController::show();
        break;

    // ── CAPACITY ────────────────────────────────────────────────────────────
    case 'capacity':
        require_once CONTROLLER_PATH . 'CapacityController.php';
        CapacityController::index();
        break;

    case 'capacity-update':
        require_once CONTROLLER_PATH . 'CapacityController.php';
        CapacityController::updateOccupancy();
        break;

    case 'capacity-data':
        require_once CONTROLLER_PATH . 'CapacityController.php';
        CapacityController::getData();
        break;

    case 'capacity-request-action':
        require_once CONTROLLER_PATH . 'CapacityController.php';
        CapacityController::updateRequestStatus();
        break;

    case 'capacity-request-family':
        require_once CONTROLLER_PATH . 'CapacityController.php';
        CapacityController::updateRequestFamily();
        break;

    case 'capacity-request-delete':
        require_once CONTROLLER_PATH . 'CapacityController.php';
        CapacityController::deleteRequest();
        break;


    case 'center-add':
        require_once CONTROLLER_PATH . 'CapacityController.php';
        CapacityController::addCenter();
        break;

    case 'center-edit':
        require_once CONTROLLER_PATH . 'CapacityController.php';
        CapacityController::editCenter();
        break;

    case 'center-delete':
        require_once CONTROLLER_PATH . 'CapacityController.php';
        CapacityController::deleteCenter();
        break;

    // ── EVACUATION REQUEST ADMIN ─────────────────────────────────────────────
    case 'admin-evacuation':
        require_once CONTROLLER_PATH . 'EvacuationController.php';
        EvacuationController::adminRequests();
        break;

    // ── USER MANAGEMENT ─────────────────────────────────────────────────────
    case 'user-management':
        require_once CONTROLLER_PATH . 'UserManagementController.php';
        UserManagementController::index();
        break;

    case 'user-add':
        require_once CONTROLLER_PATH . 'UserManagementController.php';
        UserManagementController::addUser();
        break;

    case 'user-edit':
        require_once CONTROLLER_PATH . 'UserManagementController.php';
        UserManagementController::editUser();
        break;

    case 'user-delete':
        require_once CONTROLLER_PATH . 'UserManagementController.php';
        UserManagementController::deleteUser();
        break;

    case 'user-get':
        require_once CONTROLLER_PATH . 'UserManagementController.php';
        UserManagementController::getUser();
        break;

    // ── SENSOR DATA (AJAX) ───────────────────────────────────────────────────
    case 'sensor-data':
        require_once CONTROLLER_PATH . 'SensorController.php';
        SensorController::getAll();
        break;

    case 'sensor-update':
        require_once CONTROLLER_PATH . 'SensorController.php';
        SensorController::update();
        break;

    case 'sensor-reset':
        require_once CONTROLLER_PATH . 'SensorController.php';
        SensorController::reset();
        break;

    // ── DRRM INCIDENT REPORT (admin only) ────────────────────────────────
    case 'drrm-report':
        require_once CONTROLLER_PATH . 'ReportController.php';
        // ReportController.php outputs PDF directly and calls exit
        break;

    // ── SMS TEST (admin only, debug) ─────────────────────────────────────
    case 'test-sms':
        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            echo 'Unauthorized'; exit;
        }
        require_once ROOT_PATH . 'services/PhilSmsService.php';
        require_once MODEL_PATH . 'UserModel.php';
        $testUser = UserModel::getById($_SESSION['user_id']);
        $testNumber = PhilSmsService::formatNumber($testUser['phone_number'] ?? '');
        $result = PhilSmsService::send($testNumber, '[SafeHaven] Test SMS - if you receive this, PhilSMS is working correctly!');
        header('Content-Type: application/json');
        echo json_encode([
            'raw_phone'       => $testUser['phone_number'] ?? 'NOT SET',
            'formatted_phone' => $testNumber,
            'result'          => $result,
        ], JSON_PRETTY_PRINT);
        exit;

    // ── 404 ─────────────────────────────────────────────────────────────────
    default:
        http_response_code(404);
        $pageTitle  = '404 – Page Not Found';
        $activePage = 'error';
        $extraCss   = [];
        $extraJs    = [];
        require_once VIEW_PATH . 'shared/header.php';
        echo '<div style="padding:100px;text-align:center;">';
        echo '<h1>404 – Page Not Found</h1>';
        echo '<p>The page you are looking for does not exist.</p>';
        echo '<a href="' . BASE_URL . 'index.php?page=dashboard">Go to Dashboard</a>';
        echo '</div>';
        require_once VIEW_PATH . 'shared/footer.php';
        break;
}
