<?php
/**
 * SafeHaven - Main Entry Point & Router
 * All requests go through this file
 */

// Load configuration first
require_once __DIR__ . '/config/config.php';

// Load database connection
require_once CONFIG_PATH . 'database.php';

// Get the requested page/action
$page = $_GET['page'] ?? $_GET['action'] ?? 'home';

// Define public pages (no authentication required)
$publicPages = ['home', 'login', 'register', 'do-login', 'do-register'];
$isPublicPage = in_array($page, $publicPages);

// Redirect to login if not authenticated and trying to access protected page
if (!$isPublicPage && !isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php?page=login');
    exit;
}

// Main Routing Logic
switch ($page) {
    // ===== PUBLIC PAGES =====
    case 'home':
        // Landing page with full visuals from SafeHaven(3)
        $pageTitle = 'SafeHaven – Emergency Evacuation System';
        $activePage = 'home';
        $extraCss = ['assets/css/landing_page.css'];
        $extraJs = ['assets/js/Main.js'];
        require_once VIEW_PATH . 'shared/header.php';
        require_once VIEW_PATH . 'pages/home.php';
        require_once VIEW_PATH . 'shared/footer.php';
        break;
        
    case 'contact-submit':
        // Handle contact form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $d = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'subject' => trim($_POST['subject'] ?? ''),
                'message' => trim($_POST['message'] ?? '')
            ];
            $errs = [];
            if (empty($d['name'])) $errs[] = 'Name is required.';
            if (empty($d['email'])) $errs[] = 'Email is required.';
            elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errs[] = 'Invalid email.';
            if (empty($d['subject'])) $errs[] = 'Subject is required.';
            if (empty($d['message'])) $errs[] = 'Message is required.';

            if ($errs) {
                $_SESSION['contact_errors'] = $errs;
                $_SESSION['contact_old'] = $d;
            } else {
                // Persist to JSON
                $file = MESSAGES_FILE;
                $all = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
                $all[] = array_merge($d, ['id' => count($all) + 1, 'created' => date('Y-m-d H:i:s')]);
                file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT));
                $_SESSION['contact_success'] = true;
            }
        }
        header('Location: ' . BASE_URL . 'index.php?page=home#contact');
        exit;
        break;
        
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
    
    // ===== DASHBOARD & PROTECTED PAGES =====
    case 'dashboard':
        $pageTitle = 'Dashboard - SafeHaven';
        $activePage = 'dashboard';
        $extraCss = ['assets/css/Dashboard.css'];
        $extraJs = [];
        require_once VIEW_PATH . 'shared/dashboard-header.php';
        
        // Dashboard content (role-based modules)
        $userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'evacuee';
        $allModules = [
            [
                'name' => 'Evacuation Request',
                'description' => 'Submit a GPS-tagged request with priority and special needs.',
                'url' => 'index.php?page=evacuation-request',
                'icon_class' => 'mi-red',
                'icon_svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                'roles' => ['evacuee', 'admin', 'Evacuee', 'Admin']
            ],
            [
                'name' => 'Situational Alerts',
                'description' => 'Live disaster monitoring and sensor readings.',
                'url' => 'index.php?page=alerts',
                'icon_class' => 'mi-orange',
                'icon_svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
                'roles' => ['evacuee', 'admin', 'Evacuee', 'Admin']
            ],
            [
                'name' => 'Evacuation Centers',
                'description' => 'Browse nearby centers with real-time capacity and directions.',
                'url' => 'index.php?page=evacuation-centers',
                'icon_class' => 'mi-blue',
                'icon_svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
                'roles' => ['evacuee', 'admin', 'Evacuee', 'Admin']
            ],
            [
                'name' => 'User Management',
                'description' => 'View, search, and manage registered evacuees and staff.',
                'url' => 'index.php?page=user-management',
                'icon_class' => 'mi-teal',
                'icon_svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                'roles' => ['admin', 'Admin']
            ],
            [
                'name' => 'Capacity Management',
                'description' => 'Manage Capacity In center evacuees in real time.',
                'url' => 'index.php?page=capacity',
                'icon_class' => 'mi-green',
                'icon_svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
                'roles' => ['admin', 'Admin']
            ],
            [
                'name' => 'Profile',
                'description' => 'Profile of users',
                'url' => 'index.php?page=profile',
                'icon_class' => 'mi-green',
                'icon_svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
                'roles' => ['admin', 'evacuee']
            ]
        ];
        
        $visibleModules = array_filter($allModules, function($module) use ($userRole) {
            return in_array(strtolower($userRole), array_map('strtolower', $module['roles']));
        });
        ?>
        
        <div class="dash-page">
        <main class="dash-main">
        <div class="dash-container">
        
        <div class="dash-banner">
            <div>
                <h2>Welcome back, <span><?= htmlspecialchars($_SESSION['user_name']) ?></span></h2>
                <p>You are now connected to the SafeHaven Emergency Evacuation Network.</p>
            </div>
            <div class="dash-banner-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
        </div>
        
        <div class="stat-row">
            <div class="stat-card top-green">
                <div class="stat-icon si-green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div><div class="stat-label">Profile</div><div class="stat-value">Verified</div></div>
            </div>
            <div class="stat-card top-blue">
                <div class="stat-icon si-blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <div><div class="stat-label">Alerts</div><div class="stat-value">Active</div></div>
            </div>
            <div class="stat-card top-orange">
                <div class="stat-icon si-orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div><div class="stat-label">Last Check</div><div class="stat-value">Just Now</div></div>
            </div>
        </div>
        
        <div class="modules-head">
            <h3>Modules</h3>
            <a href="#">View all →</a>
        </div>
        <div class="modules-grid">
            <?php foreach ($visibleModules as $module): ?>
            <a href="<?= htmlspecialchars($module['url']) ?>" class="module-card">
                <div class="mod-icon <?= htmlspecialchars($module['icon_class']) ?>">
                    <?= $module['icon_svg'] ?>
                </div>
                <div class="mod-info">
                    <h4><?= htmlspecialchars($module['name']) ?></h4>
                    <p><?= htmlspecialchars($module['description']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="logout-row">
            <a href="index.php?page=logout" class="btn-logout">Logout</a>
        </div>
        
        </div>
        </main>
        </div>
        
        <?php
        require_once VIEW_PATH . 'shared/footer.php';
        break;
        
    // ===== PAGE VIEWS =====
    case 'profile':
        $pageTitle = 'Profile - SafeHaven';
        $activePage = 'profile';
        $extraCss = ['assets/css/Profile.css'];
        $extraJs = [];
        require_once VIEW_PATH . 'pages/profile.php';
        break;
        
    case 'alerts':
        $pageTitle = 'Situational Alerts - SafeHaven';
        $activePage = 'alerts';
        $extraCss = ['assets/css/Heatmonitoring.css'];
        $extraJs = [];
        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/alerts.php';
        require_once VIEW_PATH . 'shared/footer.php';
        break;
        
    case 'evacuation-centers':
        $pageTitle = 'Evacuation Centers - SafeHaven';
        $activePage = 'evacuation-centers';
        $extraCss = ['assets/css/eva.css'];
        $extraJs = [];
        require_once VIEW_PATH . 'pages/centers.php';
        break;
        
    case 'evacuation-request':
        $pageTitle = 'SafeHaven – Evacuation Request';
        $activePage = 'evacuation-request';
        $extraCss = ['assets/css/evacuation-request.css'];
        $extraJs = ['assets/js/evacuation-request.js'];
        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/evacuation-request.php';
        require_once VIEW_PATH . 'shared/footer.php';
        break;
        
    case 'result':
        $pageTitle = 'Results - SafeHaven';
        $activePage = 'result';
        $extraCss = ['assets/css/EvacuationResult.css'];
        $extraJs = [];
        require_once VIEW_PATH . 'pages/result.php';
        break;
        
    case 'capacity':
        // Check if user is admin
        if (strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        }
        $pageTitle = 'Capacity Management - SafeHaven';
        $activePage = 'capacity';
        $extraCss = ['assets/css/Capacity.css'];
        $extraJs = [];
        require_once VIEW_PATH . 'pages/capacity.php';
        break;
        
    case 'user-management':
        // Check if user is admin
        if (strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        }
        $pageTitle = 'User Management - SafeHaven';
        $activePage = 'user-management';
        $extraCss = ['assets/css/UserManagement.css'];
        $extraJs = [];
        require_once VIEW_PATH . 'pages/user-management.php';
        break;
    
    // ===== 404 NOT FOUND =====
    default:
        http_response_code(404);
        $pageTitle = '404 - Page Not Found';
        $activePage = 'error';
        $extraCss = [];
        $extraJs = [];
        require_once VIEW_PATH . 'shared/header.php';
        echo '<div style="padding: 100px; text-align: center;">';
        echo '<h1>404 - Page Not Found</h1>';
        echo '<p>The page you are looking for does not exist.</p>';
        echo '<a href="index.php?page=dashboard">Go to Dashboard</a>';
        echo '</div>';
        require_once VIEW_PATH . 'shared/footer.php';
        break;
}
