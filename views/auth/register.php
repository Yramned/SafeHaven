<?php
/**
 * SafeHaven - Register View
 * Pure view: $errors, $oldData provided by AuthController::showRegisterForm()
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
                <h1>Create Account</h1>
                <p>Join SafeHaven Emergency Network</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin:0; padding-left:20px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>index.php?page=do-register" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required
                           placeholder="Enter your full name"
                           value="<?= htmlspecialchars($oldData['full_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" required
                           placeholder="e.g. Brgy. Sta. Ana, Cebu"
                           value="<?= htmlspecialchars($oldData['address'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                           placeholder="you@email.com"
                           value="<?= htmlspecialchars($oldData['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" required
                           placeholder="Enter your phone number"
                           value="<?= htmlspecialchars($oldData['phone_number'] ?? '') ?>">
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
                    <input type="password" id="password" name="password" required
                           placeholder="Create a password (min. 6 characters)">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Re-enter your password">
                </div>

                <button type="submit" class="btn-primary">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="<?= BASE_URL ?>index.php?page=login">Login here</a></p>
            </div>
        </div>
    </div>
</div>
