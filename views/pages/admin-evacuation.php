<script>window.SAFEHAVEN_BASE = "<?= BASE_URL ?>";</script>
<?php
/**
 * SafeHaven – Admin Evacuation Requests View (Unified Design)
 */

$pendingRequests = $pendingRequests ?? [];
$otherRequests   = $otherRequests   ?? [];
$statistics      = $statistics      ?? [];

function aeShPriCls($p) {
    return ['medical'=>'sh-badge-red','elderly'=>'sh-badge-yellow',
            'pregnant'=>'sh-badge-yellow','unaccompanied'=>'sh-badge-yellow',
            'disability'=>'sh-badge-blue'][$p] ?? 'sh-badge-blue';
}
function aeShStCls($s) {
    return ['approved'=>'sh-badge-green','rejected'=>'sh-badge-red',
            'completed'=>'sh-badge-blue','pending'=>'sh-badge-yellow'][$s] ?? 'sh-badge-blue';
}
?>

<div class="sh-page">
<main class="sh-main">
<div class="sh-container">

  <!-- Page Header -->
  <div class="sh-page-head">
    <div class="sh-page-head-left">
      <div class="sh-page-head-icon sh-icon-orange">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <div>
        <h2>Evacuation Requests</h2>
        <p>Review, approve or deny incoming evacuation requests</p>
      </div>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="sh-stats">
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-orange">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Pending Requests</div>
        <div class="sh-stat-val"><?= count($pendingRequests) ?></div>
        <div class="sh-stat-sub">Awaiting approval</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Total Requests</div>
        <div class="sh-stat-val"><?= count($pendingRequests) + count($otherRequests) ?></div>
        <div class="sh-stat-sub">All time</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Current Evacuees</div>
        <div class="sh-stat-val"><?= (int)($statistics['total_evacuees'] ?? 0) ?></div>
        <div class="sh-stat-sub">Across all centers</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-purple">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Capacity Available</div>
        <div class="sh-stat-val"><?= (int)($statistics['available_beds'] ?? 0) ?></div>
        <div class="sh-stat-sub">Beds remaining</div>
      </div>
    </div>
  </div>

  <!-- Pending Requests -->
  <div class="sh-card">
    <div class="sh-card-head">
      <div class="sh-card-head-left">
        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Pending Evacuation Requests
        </h3>
        <span class="sh-card-sub">Approve or deny each request below &nbsp;·&nbsp; <span style="color:#4ade80;font-size:11px;">📱 Approval SMS sent automatically</span></span>
      </div>
      <span class="sh-badge sh-badge-yellow"><?= count($pendingRequests) ?> pending</span>
    </div>

    <?php if (empty($pendingRequests)): ?>
      <div class="sh-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>
        <p>No pending requests — all caught up!</p>
      </div>
    <?php else: ?>
      <div class="sh-table-wrap sh-desktop-only">
        <table class="sh-table">
          <thead><tr>
            <th>Evacuee</th><th>Code</th><th>Center</th><th>People</th><th>Priority</th><th>Date</th><th>Actions</th>
          </tr></thead>
          <tbody>
            <?php foreach ($pendingRequests as $req): $rid=(int)$req['id']; ?>
            <tr id="preq-row-<?= $rid ?>">
              <td class="col-primary"><?= htmlspecialchars($req['user_name']??'Unknown') ?></td>
              <td style="font-family:monospace;color:var(--sh-purple);font-size:0.8rem;"><?= htmlspecialchars($req['confirmation_code']??'—') ?></td>
              <td style="color:var(--sh-text-sub);"><?= htmlspecialchars($req['center_name']??'—') ?></td>
              <td><span class="ae-people-cell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><?= (int)($req['family_members']??1) ?></span></td>
              <td><span class="sh-badge <?= aeShPriCls($req['priority']??'normal') ?>"><?= ucfirst($req['priority']??'normal') ?></span></td>
              <td style="font-size:0.8rem;color:var(--sh-text-muted);"><?= !empty($req['created_at'])?date('M d, Y',strtotime($req['created_at'])):'—' ?></td>
              <td>
                <div class="ae-action-group">
                  <button class="sh-btn sh-btn-success sh-btn-sm" onclick="handleRequest(<?= $rid ?>,'approved')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Approve
                  </button>
                  <button class="sh-btn sh-btn-danger sh-btn-sm" onclick="handleRequest(<?= $rid ?>,'rejected')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Deny
                  </button>
                  <button class="sh-btn sh-btn-ghost sh-btn-sm" onclick="confirmDelete(<?= $rid ?>)" title="Delete">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="sh-mobile-only" style="padding:14px;gap:10px;">
        <?php foreach ($pendingRequests as $req): $rid=(int)$req['id']; ?>
        <div class="ae-mobile-card" id="preq-card-<?= $rid ?>">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-weight:700;color:var(--sh-text);"><?= htmlspecialchars($req['user_name']??'Unknown') ?></span>
            <span class="sh-badge <?= aeShPriCls($req['priority']??'normal') ?>"><?= ucfirst($req['priority']??'normal') ?></span>
          </div>
          <div class="ae-mobile-meta">
            <span class="ae-mobile-meta-lbl">Code</span><span style="font-family:monospace;color:var(--sh-purple);font-size:0.8rem;"><?= htmlspecialchars($req['confirmation_code']??'—') ?></span>
            <span class="ae-mobile-meta-lbl">Center</span><span><?= htmlspecialchars($req['center_name']??'—') ?></span>
            <span class="ae-mobile-meta-lbl">People</span><span><?= (int)($req['family_members']??1) ?></span>
            <span class="ae-mobile-meta-lbl">Date</span><span><?= !empty($req['created_at'])?date('M d, Y',strtotime($req['created_at'])):'—' ?></span>
          </div>
          <div class="ae-mobile-btns">
            <button class="sh-btn sh-btn-success sh-btn-sm" onclick="handleRequest(<?= $rid ?>,'approved')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>Approve</button>
            <button class="sh-btn sh-btn-danger sh-btn-sm" onclick="handleRequest(<?= $rid ?>,'rejected')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Deny</button>
            <button class="sh-btn sh-btn-ghost sh-btn-sm" onclick="confirmDelete(<?= $rid ?>)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Delete</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- All Requests -->
  <div class="sh-card">
    <div class="sh-card-head">
      <div class="sh-card-head-left">
        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          All Evacuation Requests
        </h3>
        <span class="sh-card-sub">View and manage all processed requests</span>
      </div>
      <span class="sh-badge sh-badge-blue"><?= count($otherRequests) ?> records</span>
    </div>

    <?php if (empty($otherRequests)): ?>
      <div class="sh-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
        <p>No processed requests yet.</p>
      </div>
    <?php else: ?>
      <div class="sh-table-wrap sh-desktop-only">
        <table class="sh-table">
          <thead><tr>
            <th>Evacuee</th><th>Code</th><th>Center</th><th>People</th><th>Status</th><th>Date</th><th>Update</th><th>Actions</th>
          </tr></thead>
          <tbody>
            <?php foreach ($otherRequests as $req): $rid=(int)$req['id']; $fam=(int)($req['family_members']??1); $rSt=$req['status']??'pending'; ?>
            <tr id="hreq-row-<?= $rid ?>">
              <td class="col-primary"><?= htmlspecialchars($req['user_name']??'Unknown') ?></td>
              <td style="font-family:monospace;color:var(--sh-purple);font-size:0.8rem;"><?= htmlspecialchars($req['confirmation_code']??'—') ?></td>
              <td style="color:var(--sh-text-sub);"><?= htmlspecialchars($req['center_name']??'—') ?></td>
              <td id="fam-display-<?= $rid ?>"><span class="ae-people-cell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><?= $fam ?></span></td>
              <td><span class="sh-badge <?= aeShStCls($rSt) ?>" id="hreq-status-<?= $rid ?>"><?= ucfirst($rSt) ?></span></td>
              <td style="font-size:0.8rem;color:var(--sh-text-muted);"><?= !empty($req['created_at'])?date('M d, Y',strtotime($req['created_at'])):'—' ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:6px;">
                  <input type="number" class="sh-input" style="width:64px;padding:6px 8px;text-align:center;"
                         id="fam-input-<?= $rid ?>" value="<?= $fam ?>" min="1" max="50" data-orig="<?= $fam ?>">
                  <button class="sh-btn sh-btn-primary sh-btn-sm" onclick="saveFamilyCount(<?= $rid ?>)">Save</button>
                </div>
              </td>
              <td>
                <div class="ae-action-group">
                  <?php if ($rSt !== 'approved'): ?>
                  <button class="sh-btn sh-btn-success sh-btn-sm" onclick="handleRequest(<?= $rid ?>,'approved')" title="Approve"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg></button>
                  <?php endif; ?>
                  <?php if ($rSt !== 'rejected'): ?>
                  <button class="sh-btn sh-btn-danger sh-btn-sm" onclick="handleRequest(<?= $rid ?>,'rejected')" title="Deny"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                  <?php endif; ?>
                  <button class="sh-btn sh-btn-ghost sh-btn-sm" onclick="confirmDelete(<?= $rid ?>)" title="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="sh-mobile-only" style="padding:14px;gap:10px;">
        <?php foreach ($otherRequests as $req): $rid=(int)$req['id']; $fam=(int)($req['family_members']??1); $rSt=$req['status']??'pending'; ?>
        <div class="ae-mobile-card" id="hreq-card-<?= $rid ?>">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-weight:700;color:var(--sh-text);"><?= htmlspecialchars($req['user_name']??'Unknown') ?></span>
            <span class="sh-badge <?= aeShStCls($rSt) ?>"><?= ucfirst($rSt) ?></span>
          </div>
          <div class="ae-mobile-meta">
            <span class="ae-mobile-meta-lbl">Code</span><span style="font-family:monospace;color:var(--sh-purple);font-size:0.8rem;"><?= htmlspecialchars($req['confirmation_code']??'—') ?></span>
            <span class="ae-mobile-meta-lbl">Center</span><span><?= htmlspecialchars($req['center_name']??'—') ?></span>
            <span class="ae-mobile-meta-lbl">People</span>
            <div style="display:flex;gap:5px;align-items:center;">
              <input type="number" class="sh-input" style="width:56px;padding:5px 7px;" id="fam-input-<?= $rid ?>" value="<?= $fam ?>" min="1" max="50" data-orig="<?= $fam ?>">
              <button class="sh-btn sh-btn-primary sh-btn-sm" onclick="saveFamilyCount(<?= $rid ?>)">Save</button>
            </div>
          </div>
          <div class="ae-mobile-btns">
            <?php if ($rSt !== 'approved'): ?><button class="sh-btn sh-btn-success sh-btn-sm" onclick="handleRequest(<?= $rid ?>,'approved')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>Approve</button><?php endif; ?>
            <?php if ($rSt !== 'rejected'): ?><button class="sh-btn sh-btn-danger sh-btn-sm" onclick="handleRequest(<?= $rid ?>,'rejected')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Deny</button><?php endif; ?>
            <button class="sh-btn sh-btn-ghost sh-btn-sm" onclick="confirmDelete(<?= $rid ?>)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Delete</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Delete Modal -->
  <div class="sh-modal-overlay" id="deleteModal">
    <div class="sh-modal" style="max-width:380px;">
      <div class="sh-modal-body" style="align-items:center;text-align:center;padding:32px 28px 12px;">
        <div style="width:56px;height:56px;border-radius:50%;background:var(--sh-red-pale);border:1px solid rgba(231,76,60,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="var(--sh-red)" stroke-width="2" width="26" height="26"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
        </div>
        <h3 style="color:var(--sh-text);margin-bottom:6px;">Delete this request?</h3>
        <p style="color:var(--sh-text-sub);font-size:0.84rem;line-height:1.5;">This permanently removes the record and reverses any occupancy changes. Cannot be undone.</p>
      </div>
      <div class="sh-modal-foot" style="justify-content:center;">
        <button class="sh-btn sh-btn-ghost" onclick="closeDeleteModal()">Cancel</button>
        <button class="sh-btn sh-btn-danger" id="doDeleteBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Yes, Delete</button>
      </div>
    </div>
  </div>

  <div class="sh-toast" id="capToast">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="capToastMsg"></span>
  </div>

</div></main></div>
