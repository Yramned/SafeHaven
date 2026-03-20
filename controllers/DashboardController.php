<?php
/**
 * SafeHaven - Dashboard Controller
 */

class DashboardController {

    public static function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }

        $userRole = strtolower($_SESSION['user_role'] ?? 'evacuee');
        $userName = $_SESSION['user_name'] ?? 'User';

        $allModules = [
            [
                'name'        => 'Evacuation Request',
                'description' => 'Submit a GPS-tagged request with priority and special needs.',
                'url'         => 'index.php?page=evacuation-request',
                'icon_class'  => 'mi-red',
                'icon_svg'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                'roles'       => ['evacuee', 'admin'],
            ],
            [
                'name'        => 'Situational Alerts',
                'description' => 'Live disaster monitoring and sensor readings.',
                'url'         => 'index.php?page=alerts',
                'icon_class'  => 'mi-orange',
                'icon_svg'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
                'roles'       => ['evacuee', 'admin'],
            ],
            [
                'name'        => 'Evacuation Centers',
                'description' => 'Browse nearby centers with real-time capacity and directions.',
                'url'         => 'index.php?page=evacuation-centers',
                'icon_class'  => 'mi-blue',
                'icon_svg'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
                'roles'       => ['evacuee', 'admin'],
            ],
            [
                'name'        => 'Evacuation Requests',
                'description' => 'View, approve, or deny all evacuation requests.',
                'url'         => 'index.php?page=admin-evacuation',
                'icon_class'  => 'mi-orange',
                'icon_svg'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
                'roles'       => ['admin'],
            ],
            [
                'name'        => 'User Management',
                'description' => 'View, search, and manage registered evacuees and staff.',
                'url'         => 'index.php?page=user-management',
                'icon_class'  => 'mi-teal',
                'icon_svg'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                'roles'       => ['admin'],
            ],
            [
                'name'        => 'Capacity Management',
                'description' => 'Manage capacity in center evacuees in real time.',
                'url'         => 'index.php?page=capacity',
                'icon_class'  => 'mi-green',
                'icon_svg'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
                'roles'       => ['admin'],
            ],
            [
                'name'        => 'Profile',
                'description' => 'View and update your account information.',
                'url'         => 'index.php?page=profile',
                'icon_class'  => 'mi-purple',
                'icon_svg'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                'roles'       => ['evacuee', 'admin'],
            ],
        ];

        $visibleModules = array_values(array_filter($allModules, function ($module) use ($userRole) {
            return in_array($userRole, $module['roles']);
        }));

        $pageTitle  = 'Dashboard - SafeHaven';
        $activePage = 'dashboard';
        $extraCss   = ['assets/css/Dashboard.css'];
        $extraJs    = [];

        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'dashboard/index.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }
}
