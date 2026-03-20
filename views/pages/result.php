<?php
/**
 * SafeHaven - Result View
 * Variables provided by ResultController:
 *   $isApproved  – bool
 *   $approvalData – array with center info, confirmation code, etc.
 *   $declineData  – array with alternative centers
 */

$needs = $approvalData['special_needs'] ?? [];
if (!is_array($needs)) {
    $needs = json_decode($needs, true) ?? [];
}
$famCount = (int)($approvalData['family_members'] ?? 1);
$priority  = ucfirst($approvalData['priority']    ?? 'N/A');
?>

<div class="result-page">
<main class="result-main">
<div class="result-container">

    <?php if ($isApproved): ?>
    <!-- ════ APPROVED ════════════════════════════════════════════════════════ -->

    <div class="result-hero approved">
        <div class="result-icon icon-approved">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <h1>Request Submitted!</h1>
        <p class="result-subtitle">Your evacuation request is now <strong>pending admin approval</strong></p>
        <p class="result-note">You will be notified once approved. Present the confirmation code upon arrival.</p>
    </div>

    <!-- Assigned Center -->
    <div class="center-card">
        <div class="center-card-header">
            <h2>Assigned Evacuation Center</h2>
            <span class="badge badge-success">Pending Approval</span>
        </div>
        <div class="center-info">
            <div class="center-icon-wrapper">
                <div class="center-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
            </div>
            <div class="center-details">
                <h3><?= htmlspecialchars($approvalData['center_name'] ?? 'N/A') ?></h3>
                <p><?= htmlspecialchars($approvalData['center_address'] ?? '') ?></p>
            </div>
        </div>
        <?php if (!empty($approvalData['contact_number'])): ?>
        <p style="margin-top:8px;color:#666;font-size:13px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.36 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.11 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16z"/></svg> <?= htmlspecialchars($approvalData['contact_number']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Confirmation Code -->
    <div class="confirmation-card">
        <div class="conf-label">Confirmation Code</div>
        <div class="conf-code"><?= htmlspecialchars($approvalData['confirmation_code'] ?? '—') ?></div>
        <div class="conf-note">Present this code upon arrival</div>
    </div>

    <!-- Summary -->
    <div class="summary-card">
        <div class="summary-row">
            <span class="summary-label">Priority Classification</span>
            <span class="summary-value"><?= htmlspecialchars($priority) ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Family Members</span>
            <span class="summary-value"><?= $famCount ?> <?= $famCount === 1 ? 'person' : 'people' ?></span>
        </div>
        <?php if (!empty($needs)): ?>
        <div class="summary-row">
            <span class="summary-label">Special Needs</span>
            <span class="summary-value">
                <?php foreach ($needs as $n): ?>
                <span class="badge badge-special"><?= htmlspecialchars(ucfirst($n)) ?></span>
                <?php endforeach; ?>
            </span>
        </div>
        <?php endif; ?>
        <div class="summary-row">
            <span class="summary-label">Center Capacity</span>
            <span class="summary-value capacity-value"><?= (int)($approvalData['capacity_percent'] ?? 0) ?>%</span>
        </div>
        <div class="capacity-bar-wrapper">
            <div class="capacity-bar-bg">
                <div class="capacity-bar-fill" style="width:<?= min(100,(int)($approvalData['capacity_percent']??0)) ?>%"></div>
            </div>
        </div>
        <div class="summary-row">
            <span class="summary-label">Status</span>
            <span class="summary-value" style="font-weight:700;color:#f39c12;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Pending Admin Approval
            </span>
        </div>
    </div>

    <!-- Actions -->
    <div class="action-buttons">
        <?php if (!empty($approvalData['contact_number'])): ?>
        <a class="btn-success" href="tel:<?= htmlspecialchars($approvalData['contact_number']) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.33A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.73A16 16 0 0 0 15 15.73l.91-.91a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            Call Center
        </a>
        <?php endif; ?>
        <a class="btn-primary" href="<?= BASE_URL ?>index.php?page=evacuation-request">
            Submit Another Request
        </a>
    </div>

    <?php else: ?>
    <!-- ════ DECLINED ════════════════════════════════════════════════════════ -->

    <div class="result-hero declined">
        <div class="result-icon icon-declined">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </div>
        <h1>Request Declined</h1>
        <p class="result-subtitle">Don't worry, alternative centers are available</p>
    </div>

    <div class="original-request-card">
        <div class="original-header"><h2>Your Request Details</h2></div>
        <div class="summary-row"><span class="summary-label">Priority</span><span class="summary-value"><?= htmlspecialchars($priority) ?></span></div>
        <div class="summary-row"><span class="summary-label">Family Members</span><span class="summary-value"><?= $famCount ?> <?= $famCount===1?'person':'people' ?></span></div>
        <?php if (!empty($needs)): ?>
        <div class="summary-row">
            <span class="summary-label">Special Needs</span>
            <span class="summary-value"><?php foreach($needs as $n) echo '<span class="badge badge-special">'.htmlspecialchars(ucfirst($n)).'</span> '; ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="original-request-card">
        <div class="original-header"><h2>Original Center</h2></div>
        <p class="original-center-name"><?= htmlspecialchars($declineData['original_center'] ?? 'N/A') ?></p>
        <p class="original-reason">Your request has been declined. Please choose from the available alternatives below or submit a new request.</p>
    </div>

    <?php if (!empty($declineData['alternative_centers'])): ?>
    <div class="alternatives-section">
        <h2 class="alternatives-heading">Alternative Centers Available</h2>
        <div class="alternatives-grid">
            <?php foreach ($declineData['alternative_centers'] as $c): ?>
            <?php
                $capPct = (int)($c['capacity'] ?? 0);
                $cStatus= $c['status'] ?? 'accepting';
                $cColor = ['accepting'=>'#10b981','limited'=>'#f59e0b','full'=>'#ef4444'][$cStatus] ?? '#6b7280';
            ?>
            <div class="alternative-card">
                <div class="alt-card-header">
                    <div class="alt-card-title">
                        <div class="alt-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        </div>
                        <div>
                            <h3><?= htmlspecialchars($c['name']) ?></h3>
                            <p class="alt-address"><?= htmlspecialchars($c['address']) ?></p>
                        </div>
                    </div>
                    <span class="badge" style="background:<?= $cColor ?>;color:#fff;"><?= ucfirst($cStatus) ?></span>
                </div>
                <div class="alt-capacity">
                    <div class="alt-capacity-label">
                        <span>Capacity Used</span>
                        <span class="alt-capacity-percent"><?= $capPct ?>%</span>
                    </div>
                    <div class="capacity-bar-bg">
                        <div class="capacity-bar-fill" style="width:<?= $capPct ?>%;background:<?= $cColor ?>"></div>
                    </div>
                </div>
                <?php if (!empty($c['contact'])): ?>
                <p style="font-size:12px;color:#666;margin-top:8px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.36 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.11 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16z"/></svg> <?= htmlspecialchars($c['contact']) ?></p>
                <?php endif; ?>
                <a class="btn-select-center" href="<?= BASE_URL ?>index.php?page=evacuation-request">
                    Select This Center
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:30px;">
        <p>No alternative centers are currently available. Please contact emergency services.</p>
    </div>
    <?php endif; ?>

    <div class="back-action">
        <a href="<?= BASE_URL ?>index.php?page=evacuation-request" class="btn-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>
            Submit New Request
        </a>
    </div>

    <?php endif; ?>

    <div class="another-request">
        <a href="<?= BASE_URL ?>index.php?page=dashboard">← Back to Dashboard</a>
    </div>

</div>
</main>
</div>
