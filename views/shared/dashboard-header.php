<?php
/**
 * SafeHaven – Dashboard Header Template
 */

$userName = $_SESSION['user_name'] ?? 'User';
$userRole = strtolower($_SESSION['user_role'] ?? 'evacuee');

// Define all navigation links with their access permissions
$allNavLinks = [
    [
        'name' => 'Evacuation Request',
        'url' => 'index.php?page=evacuation-request',
        'page_id' => 'evacuation-request',
        'roles' => ['evacuee', 'admin']
    ],
    [
        'name' => 'Situational Alerts',
        'url' => 'index.php?page=alerts',
        'page_id' => 'alerts',
        'roles' => ['evacuee', 'admin']
    ],
    [
        'name' => 'Evacuation Centers',
        'url' => 'index.php?page=evacuation-centers',
        'page_id' => 'evacuation-centers',
        'roles' => ['evacuee', 'admin']
    ],
    [
        'name' => 'Capacity Management',
        'url' => 'index.php?page=capacity',
        'page_id' => 'capacity',
        'roles' => ['admin']
    ],
    [
        'name' => 'User Management',
        'url' => 'index.php?page=user-management',
        'page_id' => 'user-management',
        'roles' => ['admin']
    ],
    [
        'name' => 'Profile',
        'url' => 'index.php?page=profile',
        'page_id' => 'profile',
        'roles' => ['evacuee', 'admin']
    ]
];

// Filter navigation links based on user role
$visibleNavLinks = array_filter($allNavLinks, function($link) use ($userRole) {
    return in_array($userRole, $link['roles']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SafeHaven Dashboard') ?></title>

    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= CSS_PATH ?>HeaderFooter.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>dashboard-header.css">

    <!-- Page-specific CSS -->
    <?php foreach (($extraCss ?? []) as $css): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?><?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>

</head>

<body>

<header class="dashboard-header">
    <div class="header-container">

        <!-- Logo -->
        <a href="<?= BASE_URL ?>index.php?page=dashboard" class="header-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
            </div>
            <span class="logo-text">SafeHaven</span>
        </a>

        <!-- Navigation (Role-based) -->
        <nav class="header-nav" id="headerNav">
            <?php foreach ($visibleNavLinks as $link): ?>
                <a href="<?= BASE_URL . htmlspecialchars($link['url']) ?>" 
                   class="nav-link <?= ($activePage ?? '') === $link['page_id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($link['name']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- User -->
        <div class="header-user">
            <span class="user-greeting">Hi, <span><?= htmlspecialchars($userName) ?></span></span>
            <a href="<?= BASE_URL ?>index.php?page=logout" class="header-logout">Logout</a>
            <button class="mobile-toggle" onclick="toggleHeaderMenu()" aria-label="Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>

    </div>
</header>

<script>
function toggleHeaderMenu() {
    document.getElementById('headerNav').classList.toggle('open');
}

document.addEventListener('click', function (e) {
    const nav = document.getElementById('headerNav');
    const toggle = document.querySelector('.mobile-toggle');
    if (!nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.classList.remove('open');
    }
});
</script>
