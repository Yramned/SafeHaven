<script>window.SAFEHAVEN_BASE = "<?= BASE_URL ?>";</script>
<?php
/**
 * SafeHaven – User Management View (Unified Design)
 */

if (!function_exists('initials')) {
    function initials($name) {
        $words = explode(' ', trim($name));
        return strtoupper(count($words) >= 2
            ? substr($words[0], 0, 1) . substr($words[1], 0, 1)
            : substr($name, 0, 2));
    }
}
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
?>

<div class="sh-page">
<main class="sh-main">
<div class="sh-container">

  <!-- Page Header -->
  <div class="sh-page-head">
    <div class="sh-page-head-left">
      <div class="sh-page-head-icon sh-icon-purple">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div>
        <h2>User Management</h2>
        <p>Manage registered users and their roles</p>
      </div>
    </div>
    <button class="sh-btn sh-btn-primary" id="openModalBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add New User
    </button>
  </div>

  <!-- Stats -->
  <div class="sh-stats">
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Total Users</div>
        <div class="sh-stat-val" id="totalCount"><?= $totalUsers ?></div>
        <div class="sh-stat-sub">Registered accounts</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Evacuees</div>
        <div class="sh-stat-val" id="evacueeStat"><?= $evacuees ?></div>
        <div class="sh-stat-sub">Standard users</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-red">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Admins</div>
        <div class="sh-stat-val" id="adminStat"><?= $admins ?? ($totalUsers - $evacuees) ?></div>
        <div class="sh-stat-sub">Admin accounts</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-orange">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Active Sessions</div>
        <div class="sh-stat-val">—</div>
        <div class="sh-stat-sub">Real-time tracking</div>
      </div>
    </div>
  </div>

  <!-- Flash message -->
  <div id="umFlash" class="sh-flash" style="display:none;"></div>

  <!-- Users Table Card -->
  <div class="sh-card">
    <div class="sh-card-head">
      <div class="sh-card-head-left">
        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          All Users
        </h3>
        <span class="sh-card-sub">Click edit or delete to manage a user</span>
      </div>
    </div>
    <div class="sh-table-wrap">
      <table class="sh-table" id="usersTable">
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Phone</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="usersTableBody">
          <?php foreach ($users as $u): ?>
          <tr id="user-row-<?= (int)$u['id'] ?>">
            <td>
              <div class="um-avatar-cell">
                <div class="sh-avatar"><?= initials($u['full_name']) ?></div>
                <span class="col-primary"><?= htmlspecialchars($u['full_name']) ?></span>
              </div>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <span class="sh-badge <?= $u['role']==='admin' ? 'sh-badge-red' : 'sh-badge-blue' ?>">
                <?php if ($u['role']==='admin'): ?>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <?php else: ?>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?php endif; ?>
                <?= ucfirst($u['role']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($u['phone_number'] ?? 'N/A') ?></td>
            <td style="font-size:0.8rem;"><?= !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : 'N/A' ?></td>
            <td>
              <div style="display:flex;gap:5px;">
                <button class="sh-btn sh-btn-ghost sh-btn-sm btn-row-edit" data-id="<?= (int)$u['id'] ?>" title="Edit">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </button>
                <?php if ((int)$u['id'] !== $currentUserId): ?>
                <button class="sh-btn sh-btn-danger sh-btn-sm btn-row-delete" data-id="<?= (int)$u['id'] ?>" title="Delete">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div></main></div>

<!-- Add / Edit Modal -->
<div class="sh-modal-overlay" id="modalOverlay">
  <div class="sh-modal sh-modal-wide">
    <div class="sh-modal-head">
      <div class="sh-modal-head-left">
        <svg viewBox="0 0 24 24" fill="none" stroke="var(--sh-purple)" stroke-width="2" width="18" height="18"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        <h3 id="modalTitle">Add New User</h3>
      </div>
      <button class="sh-modal-close" id="closeModalBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="sh-modal-body">
      <div id="modalError" class="sh-flash sh-flash-error" style="display:none;"></div>
      <input type="hidden" id="editUserId" value="">
      <div class="sh-form-grid">
        <div class="sh-form-group sh-span-2">
          <label class="sh-label">Full Name *</label>
          <input type="text" id="fieldName" class="sh-input" placeholder="e.g. Juan Dela Cruz">
        </div>
        <div class="sh-form-group">
          <label class="sh-label">Email *</label>
          <input type="email" id="fieldEmail" class="sh-input" placeholder="email@example.com">
        </div>
        <div class="sh-form-group">
          <label class="sh-label">Phone</label>
          <input type="tel" id="fieldPhone" class="sh-input" placeholder="+63 9XX XXX XXXX">
        </div>
        <div class="sh-form-group">
          <label class="sh-label">Role *</label>
          <select id="fieldRole" class="sh-input">
            <option value="evacuee">Evacuee</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="sh-form-group">
          <label class="sh-label">Address</label>
          <input type="text" id="fieldAddress" class="sh-input" placeholder="Barangay / City">
        </div>
        <div class="sh-form-group sh-span-2" id="passwordRow">
          <label class="sh-label">Password <span id="pwdNote" style="color:var(--sh-text-muted);text-transform:none;font-weight:400;">(required for new users)</span></label>
          <input type="password" id="fieldPassword" class="sh-input" placeholder="Min. 6 characters">
        </div>
      </div>
    </div>
    <div class="sh-modal-foot">
      <button class="sh-btn sh-btn-ghost" id="cancelModalBtn">Cancel</button>
      <button class="sh-btn sh-btn-primary" id="saveUserBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>
        Save User
      </button>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="sh-modal-overlay" id="deleteModal">
  <div class="sh-modal" style="max-width:380px;">
    <div class="sh-modal-body" style="align-items:center;text-align:center;padding:32px 28px 12px;">
      <div style="width:56px;height:56px;border-radius:50%;background:var(--sh-red-pale);border:1px solid rgba(231,76,60,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="var(--sh-red)" stroke-width="2" width="26" height="26"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>
      </div>
      <h3 style="color:var(--sh-text);margin-bottom:6px;">Delete User?</h3>
      <p style="color:var(--sh-text-sub);font-size:0.84rem;line-height:1.5;">This permanently removes the user account and all their evacuation requests. This cannot be undone.</p>
    </div>
    <input type="hidden" id="deleteUserId">
    <div class="sh-modal-foot" style="justify-content:center;">
      <button class="sh-btn sh-btn-ghost" id="cancelDeleteBtn">Cancel</button>
      <button class="sh-btn sh-btn-danger" id="confirmDeleteBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
        Yes, Delete
      </button>
    </div>
  </div>
</div>
