<?php
/**
 * SafeHaven - Profile View
 * Variables: $user, $successMessage, $errorMessages — from ProfileController
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
                <p>Manage your account information &amp; SMS notifications</p>
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
                <div class="meta-item">
                    <span class="meta-label">SMS Notifications</span>
                    <span class="status-badge-active">&#9679; Enabled</span>
                </div>
            </div>

            <!-- SMS Info Box -->
            <div class="sms-info-box">
                <div class="sms-info-icon">📱</div>
                <div class="sms-info-text">
                    <strong>SMS Alerts Active</strong>
                    <p>You will receive evacuation confirmations and situational alerts via SMS to your registered number<?= !empty($user['family_numbers']) ? ' and ' . count($user['family_numbers']) . ' family number(s)' : '' ?>.</p>
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
                    <!-- Hidden field to carry family numbers as JSON -->
                    <input type="hidden" id="family_numbers_input" name="family_numbers" value="<?= htmlspecialchars(json_encode($user['family_numbers'])) ?>">

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
                            <label for="phone">
                                Phone Number
                                <span class="sms-badge">📱 SMS</span>
                            </label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($user['phone']) ?>"
                                   readonly required autocomplete="tel"
                                   placeholder="e.g. 09171234567">
                            <span class="field-hint">Used for evacuation & alert SMS notifications</span>
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

                    <!-- ── Family SMS Numbers ─────────────────────────────── -->
                    <div class="family-numbers-section" id="familyNumbersSection">
                        <div class="family-section-header">
                            <div class="family-header-left">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                                <div>
                                    <h4>Family SMS Notifications</h4>
                                    <p>Add family members' numbers to notify them of evacuation alerts</p>
                                </div>
                            </div>
                        </div>

                        <div id="familyNumbersList">
                            <?php foreach ($user['family_numbers'] as $i => $fn): ?>
                            <div class="family-number-row" data-index="<?= $i ?>">
                                <div class="family-number-icon">👤</div>
                                <input type="tel" class="family-number-input"
                                       value="<?= htmlspecialchars($fn) ?>"
                                       placeholder="e.g. 09171234567"
                                       readonly
                                       data-index="<?= $i ?>">
                                <button type="button" class="btn-remove-family" onclick="removeFamilyNumber(<?= $i ?>)" style="display:none;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn-add-family" id="addFamilyBtn" onclick="addFamilyNumber()" style="display:none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Add Family Member Number
                        </button>

                        <?php if (empty($user['family_numbers'])): ?>
                        <div class="family-empty-state" id="familyEmptyState">
                            <span>📵</span>
                            <p>No family numbers added yet. Click <strong>Edit Profile</strong> to add them.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- ─────────────────────────────────────────────────────── -->

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

<style>
/* ── SMS badge ── */
.sms-badge {
    display: inline-block;
    font-size: 10px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    padding: 1px 6px;
    border-radius: 20px;
    margin-left: 6px;
    vertical-align: middle;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* ── SMS info box in profile card ── */
.sms-info-box {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: rgba(34,197,94,0.08);
    border: 1px solid rgba(34,197,94,0.25);
    border-radius: 10px;
    padding: 12px 14px;
    margin-top: 16px;
}
.sms-info-icon { font-size: 22px; flex-shrink: 0; }
.sms-info-text { font-size: 12.5px; line-height: 1.5; color: var(--text-secondary, #888); }
.sms-info-text strong { color: var(--text-primary, #fff); display: block; margin-bottom: 3px; }

/* ── Family numbers section ── */
.family-numbers-section {
    margin-top: 22px;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 18px;
    background: rgba(255,255,255,0.02);
}
.family-section-header { margin-bottom: 14px; }
.family-header-left {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.family-header-left svg { margin-top: 2px; flex-shrink: 0; stroke: #60a5fa; }
.family-header-left h4 { margin: 0 0 2px; font-size: 14px; font-weight: 600; color: var(--text-primary, #fff); }
.family-header-left p { margin: 0; font-size: 12px; color: var(--text-secondary, #999); }

.family-number-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.family-number-icon { font-size: 16px; flex-shrink: 0; }
.family-number-input {
    flex: 1;
    background: var(--input-bg, rgba(255,255,255,0.05));
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 8px 12px;
    color: var(--text-primary, #fff);
    font-size: 13.5px;
    outline: none;
    transition: border-color .2s;
}
.family-number-input:not([readonly]):focus { border-color: #60a5fa; }
.family-number-input[readonly] { opacity: 0.75; cursor: default; }

.btn-remove-family {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.3);
    color: #f87171;
    border-radius: 6px;
    padding: 6px 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: background .15s;
}
.btn-remove-family:hover { background: rgba(239,68,68,0.25); }

.btn-add-family {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(96,165,250,0.1);
    border: 1px dashed rgba(96,165,250,0.4);
    color: #60a5fa;
    border-radius: 8px;
    padding: 9px 16px;
    font-size: 13px;
    cursor: pointer;
    width: 100%;
    justify-content: center;
    margin-top: 4px;
    transition: background .15s;
}
.btn-add-family:hover { background: rgba(96,165,250,0.18); }

.family-empty-state {
    text-align: center;
    padding: 18px;
    color: var(--text-secondary, #888);
    font-size: 13px;
}
.family-empty-state span { font-size: 28px; display: block; margin-bottom: 6px; }
</style>
