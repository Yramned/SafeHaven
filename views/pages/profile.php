<?php
/**
 * SafeHaven - Profile View
 * Pure view: all variables ($user, $successMessage, $errorMessages) provided by ProfileController.
 */
?>

<div class="profile-page">
<main class="profile-main">
<div class="profile-container">

    <!-- Page Header -->
    <div class="profile-page-head">
        <div class="profile-page-head-left">
            <div class="profile-page-head-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <h2>My Profile</h2>
                <p>Manage your account information</p>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($successMessage): ?>
        <div class="alert alert-success">&#10003; <?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessages)): ?>
        <div class="alert alert-error">
            <?php foreach ($errorMessages as $err): ?>
                <p>&#10005; <?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Grid -->
    <div class="profile-grid">

        <!-- Left: Profile Card -->
        <div class="profile-card">
            <div class="profile-avatar-section">
                <div class="profile-avatar">
                    <div class="avatar-initials">
                        <?php
                            $parts = explode(' ', trim($user['name']));
                            echo strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                        ?>
                    </div>
                </div>
            </div>

            <div class="profile-info">
                <h3><?= htmlspecialchars($user['name']) ?></h3>
                <span class="profile-role-badge"><?= htmlspecialchars($user['role']) ?></span>
                <p class="profile-email">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <?= htmlspecialchars($user['email']) ?>
                </p>
                <?php if ($user['phone']): ?>
                <p class="profile-phone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.73A16 16 0 0 0 15 15.73l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <?= htmlspecialchars($user['phone']) ?>
                </p>
                <?php endif; ?>
            </div>

            <div class="profile-meta">
                <div class="meta-item">
                    <span class="meta-label">Member Since</span>
                    <span class="meta-value"><?= date('M d, Y', strtotime($user['joined_date'])) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Account Status</span>
                    <span class="status-badge-active">&#9679; Active</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">User ID</span>
                    <span class="meta-value">#<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?></span>
                </div>
            </div>
        </div>

        <!-- Right: Edit Form -->
        <div class="profile-forms">
            <div class="form-section">
                <div class="form-section-header">
                    <h3>Personal Information</h3>
                    <button type="button" class="btn-edit" id="editBtn" onclick="toggleEdit()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit Profile
                    </button>
                </div>

                <form id="profileForm" method="POST" action="<?= BASE_URL ?>index.php?page=profile-update">

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name"
                               value="<?= htmlspecialchars($user['name']) ?>"
                               readonly required autocomplete="name">
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email"
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   readonly required autocomplete="email">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($user['phone']) ?>"
                                   readonly required autocomplete="tel">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address / Barangay</label>
                        <input type="text" id="address" name="address"
                               value="<?= htmlspecialchars($user['address']) ?>"
                               readonly required autocomplete="street-address">
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password <span class="optional">(optional)</span></label>
                        <input type="password" id="new_password" name="new_password"
                               placeholder="Leave blank to keep current password"
                               readonly autocomplete="new-password">
                        <span class="field-hint">Minimum 6 characters</span>
                    </div>

                    <div class="form-actions" id="formActions" style="display:none;">
                        <button type="button" class="btn-cancel" id="cancelBtn" onclick="cancelEdit()">&#10005; Cancel</button>
                        <button type="submit" class="btn-save">&#10003; Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div><!-- /.profile-grid -->

</div><!-- /.profile-container -->
</main>
</div>
