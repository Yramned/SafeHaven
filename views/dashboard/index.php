<?php
/**
 * SafeHaven - Dashboard View
 * Pure view: $visibleModules, $userName provided by DashboardController.
 */
?>

<div class="dash-page">
<main class="dash-main">
<div class="dash-container">

<div class="dash-banner">
    <div>
        <h2>Welcome back, <span><?= htmlspecialchars($userName) ?></span></h2>
        <p>You are now connected to the SafeHaven Emergency Evacuation Network.</p>
    </div>
    <div class="dash-banner-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
    </div>
</div>

<div class="stat-row">
    <div class="stat-card top-green">
        <div class="stat-icon si-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div><div class="stat-label">Profile</div><div class="stat-value">Verified</div></div>
    </div>
    <div class="stat-card top-blue">
        <div class="stat-icon si-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <div><div class="stat-label">Alerts</div><div class="stat-value">Active</div></div>
    </div>
    <div class="stat-card top-orange">
        <div class="stat-icon si-orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div><div class="stat-label">Last Check</div><div class="stat-value">Just Now</div></div>
    </div>
</div>

<div class="modules-head">
    <h3>Modules</h3>
    <a href="#">View all &rarr;</a>
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
    <?php if (strtolower($_SESSION['user_role'] ?? '') === 'admin'): ?>
    <a href="index.php?page=drrm-report" class="btn-drrm-report" target="_blank">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align:middle;margin-right:5px">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
            <line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/>
        </svg>
        Download DRRM Incident Report (PDF)
    </a>
    <?php endif; ?>
    <a href="index.php?page=logout" class="btn-logout">Logout</a>
</div>

</div>
</main>
</div>
