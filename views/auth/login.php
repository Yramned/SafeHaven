<?php
/**
 * SafeHaven - Login View
 * Pure view: $error, $success provided by AuthController::showLoginForm()
 */
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="auth-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </svg>
                </div>
                <h1>SafeHaven</h1>
                <p>Emergency Evacuation System</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>index.php?page=do-login" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

                <button type="submit" class="btn-primary">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="<?= BASE_URL ?>index.php?page=register">Register here</a></p>
            </div>
        </div>
    </div>
</div>
