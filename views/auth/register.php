<?php
/**
 * SafeHaven - Register Page
 */

$pageTitle = 'Register - SafeHaven';
$activePage = 'register';
$extraCss = ['assets/css/Auth.css'];
$extraJs = ['assets/js/Auth.js'];

require_once VIEW_PATH . 'shared/header.php';

$oldData = $_SESSION['register_old'] ?? [];
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

            <?php if (isset($_SESSION['register_errors'])): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($_SESSION['register_errors'] as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['register_errors']); ?>
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

<style>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px;
}

.auth-container {
    width: 100%;
    max-width: 500px;
}

.auth-box {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-logo {
    width: 60px;
    height: 60px;
    margin: 0 auto 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.auth-logo svg {
    width: 32px;
    height: 32px;
    stroke: white;
}

.auth-header h1 {
    font-size: 28px;
    color: #333;
    margin-bottom: 5px;
}

.auth-header p {
    color: #666;
    font-size: 14px;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-error {
    background-color: #fee;
    color: #c00;
    border: 1px solid #fcc;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
}

/* Role Selection Styles */
.role-selection {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.role-option {
    cursor: pointer;
}

.role-option input[type="radio"] {
    display: none;
}

.role-card {
    background: rgba(102, 126, 234, 0.05);
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 20px 12px;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-height: 140px;
    justify-content: center;
}

.role-card svg {
    width: 32px;
    height: 32px;
    color: #667eea;
    transition: all 0.3s ease;
}

.role-title {
    font-weight: 600;
    font-size: 16px;
    color: #333;
    display: block;
}

.role-desc {
    font-size: 12px;
    color: #666;
    display: block;
}

.role-option input[type="radio"]:checked + .role-card {
    background: rgba(102, 126, 234, 0.15);
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.role-option input[type="radio"]:checked + .role-card svg {
    color: #667eea;
    transform: scale(1.1);
}

.role-option:hover .role-card {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.btn-primary {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    margin-top: 10px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.auth-footer {
    text-align: center;
    margin-top: 25px;
    padding-top: 25px;
    border-top: 1px solid #eee;
    color: #666;
    font-size: 14px;
}

.auth-footer a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.auth-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 460px) {
    .role-selection {
        grid-template-columns: 1fr;
    }
    
    .auth-box {
        padding: 30px 20px;
    }
}
</style>

<?php 
unset($_SESSION['register_old']);
require_once VIEW_PATH . 'shared/footer.php'; 
?>