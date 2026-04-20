<?php
/**
 * SafeHaven - Login View
 * Variables: $error, $success – set by AuthController::showLoginForm()
 */
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-box">

            <div class="auth-header">
                <div class="auth-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                <h1>SafeHaven</h1>
                <p>Emergency Evacuation System</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>index.php?page=do-login" method="POST" novalidate>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autocomplete="email"
                        placeholder="you@email.com"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" class="btn-primary" id="loginBtn">Sign In</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="<?= BASE_URL ?>index.php?page=register">Register here</a></p>
            </div>

        </div><!-- .auth-box -->
    </div><!-- .auth-container -->
</div><!-- .auth-page -->

<script>
document.querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('loginBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Signing in…'; }
});
</script>
