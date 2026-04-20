<?php
/**
 * SafeHaven - Register View
 * Variables: $errors, $oldData – set by AuthController::showRegisterForm()
 */
?>

<div class="auth-page">
    <div class="auth-container wide">
        <div class="auth-box">

            <div class="auth-header">
                <div class="auth-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                <h1>Create Account</h1>
                <p>Join SafeHaven Emergency Network</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>index.php?page=do-register" method="POST" novalidate>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        required
                        autocomplete="name"
                        placeholder="Enter your full name"
                        value="<?= htmlspecialchars($oldData['full_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        required
                        autocomplete="street-address"
                        placeholder="e.g. Brgy. Sta. Ana, Cebu City"
                        value="<?= htmlspecialchars($oldData['address'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autocomplete="email"
                        placeholder="you@email.com"
                        value="<?= htmlspecialchars($oldData['email'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="phone_number">
                        Phone Number
                        <span style="font-size:10px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;padding:1px 7px;border-radius:20px;margin-left:5px;font-weight:600;">📱 SMS</span>
                    </label>
                    <input
                        type="tel"
                        id="phone_number"
                        name="phone_number"
                        required
                        autocomplete="tel"
                        placeholder="e.g. 09171234567"
                        value="<?= htmlspecialchars($oldData['phone_number'] ?? '') ?>"
                    >
                    <small>You'll receive evacuation confirmations &amp; alerts via SMS.</small>
                </div>

                <div class="form-group">
                    <label>Register As</label>
                    <div class="role-selection">
                        <label class="role-option">
                            <input type="radio" name="role" value="evacuee"
                                <?= (!isset($oldData['role']) || $oldData['role'] === 'evacuee') ? 'checked' : '' ?> required>
                            <div class="role-card">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <span class="role-title">Evacuee</span>
                                <span class="role-desc">Request evacuation assistance</span>
                            </div>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="admin"
                                <?= (isset($oldData['role']) && $oldData['role'] === 'admin') ? 'checked' : '' ?>>
                            <div class="role-card">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                                <span class="role-title">Admin</span>
                                <span class="role-desc">Manage evacuation centers</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Create a password (min. 6 characters)"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                        autocomplete="new-password"
                        placeholder="Re-enter your password"
                    >
                </div>

                <button type="submit" class="btn-primary" id="registerBtn">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="<?= BASE_URL ?>index.php?page=login">Sign in here</a></p>
            </div>

        </div><!-- .auth-box -->
    </div><!-- .auth-container -->
</div><!-- .auth-page -->

<script>
/* Phone – strip non-numeric chars except +, -, (, ), space */
var phoneInput = document.getElementById('phone_number');
if (phoneInput) {
    phoneInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9+\-() ]/g, '');
    });
}
/* Disable submit on submit to prevent double-click */
document.querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('registerBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Creating account…'; }
});
</script>
