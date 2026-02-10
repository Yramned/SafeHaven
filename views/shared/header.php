<?php
/**
 * SafeHaven – Public Header Template
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle ?? 'SafeHaven') ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" />

    <!-- Base CSS -->
    <link rel="stylesheet" href="<?= CSS_PATH ?>HeaderFooter.css" />

    <!-- Page-specific CSS -->
    <?php foreach (($extraCss ?? []) as $sheet): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?><?= htmlspecialchars($sheet) ?>" />
    <?php endforeach; ?>

</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="nav-inner">

        <!-- Logo -->
        <a href="<?= BASE_URL ?>index.php" class="nav-logo">
            <div class="nav-logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#5dade2" stroke-width="2" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <span class="nav-logo-text">SafeHaven</span>
        </a>

        <!-- Desktop links -->
        <ul class="nav-links" id="navLinks">
            <?php
            $navPages = [
                ['key'=>'home',        'label'=>'Home',        'href'=>'index.php'],
                ['key'=>'features',    'label'=>'Features',    'href'=>'index.php#features'],
                ['key'=>'how-it-works','label'=>'How It Works','href'=>'index.php#how-it-works'],
                ['key'=>'contact',     'label'=>'Contact',     'href'=>'index.php#contact'],
            ];

            foreach ($navPages as $nav):
                $cls = (($activePage ?? '') === $nav['key']) ? ' class="active"' : '';
            ?>
                <li><a href="<?= BASE_URL . $nav['href'] ?>"<?= $cls ?>><?= $nav['label'] ?></a></li>
            <?php endforeach; ?>
        </ul>

        <!-- Desktop actions -->
        <div class="nav-actions" id="navActions">
            <?php if (isset($_SESSION['user_name'])): ?>
                <span class="nav-user">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="<?= BASE_URL ?>index.php?page=logout" class="btn-nav btn-nav-ghost">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>index.php?page=login" class="btn-nav btn-nav-ghost">Login</a>
                <a href="<?= BASE_URL ?>index.php?page=register" class="btn-nav btn-nav-primary">Register</a>
            <?php endif; ?>
        </div>

        <!-- Hamburger (mobile) -->
        <button class="nav-hamburger" id="navHamburger" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobile panel -->
    <div class="nav-mobile-panel" id="navMobile">
        <ul class="nav-links">
            <?php foreach ($navPages as $nav):
                $cls = (($activePage ?? '') === $nav['key']) ? ' class="active"' : '';
            ?>
                <li><a href="<?= BASE_URL . $nav['href'] ?>"<?= $cls ?>><?= $nav['label'] ?></a></li>
            <?php endforeach; ?>
        </ul>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_name'])): ?>
                <a href="<?= BASE_URL ?>index.php?page=logout" class="btn-nav btn-nav-ghost">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>index.php?page=login" class="btn-nav btn-nav-ghost">Login</a>
                <a href="<?= BASE_URL ?>index.php?page=register" class="btn-nav btn-nav-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
