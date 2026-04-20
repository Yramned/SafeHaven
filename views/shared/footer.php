<?php
/**
 * SafeHaven – Footer Template
 * Used by both public (header.php) and dashboard (dashboard-header.php) pages.
 * $isDashboard is set to true by dashboard-header.php to skip the public footer.
 */
?>

<?php if (empty($isDashboard)): ?>
<!-- PUBLIC FOOTER ─────────────────────────────────────────── -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Brand column -->
            <div class="footer-col">
                <div class="footer-brand-logo">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7eb8da" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>SafeHaven</span>
                </div>
                <p>Empowering emergency evacuation through accessible communication and coordination across communities.</p>
            </div>

            <!-- Quick links -->
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>index.php#features">Features</a></li>
                    <li><a href="<?= BASE_URL ?>index.php#how-it-works">How It Works</a></li>
                    <li><a href="<?= BASE_URL ?>index.php#contact">Contact</a></li>
                </ul>
            </div>

            <!-- Contact info -->
            <div class="footer-col">
                <h4>Information</h4>
                <ul>
                    <li><a href="tel:<?= CONTACT_PHONE ?>"><?= CONTACT_PHONE ?></a></li>
                    <li><a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a></li>
                    <li><a href="#"><?= CONTACT_WEBSITE ?></a></li>
                    <li><?= CONTACT_ADDRESS ?></li>
                </ul>
            </div>
        </div>

        <hr class="footer-divider" />
        <p class="footer-bottom">&copy; <?= date('Y') ?> SafeHaven. All Rights Reserved.</p>
    </div>
</footer>
<?php endif; ?>

<!-- GLOBAL JS (public pages only – dashboard pages load their own scripts) -->
<?php if (empty($isDashboard)): ?>
    <script src="<?= JS_PATH ?>Main.js"></script>
<?php endif; ?>

<!-- Page-specific scripts -->
<?php foreach (($extraJs ?? []) as $script): ?>
    <script src="<?= BASE_URL ?><?= htmlspecialchars($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>
