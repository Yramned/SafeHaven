<?php /* Inject BASE_URL for JS */ ?>
<script>window.SAFEHAVEN_BASE = "<?= BASE_URL ?>";</script>
<?php
/**
 * SafeHaven – Capacity Management (Unified Design)
 */

$statistics = $statistics ?? [
    'total_centers'=>0,'total_capacity'=>0,'total_evacuees'=>0,
    'available_beds'=>0,'occupancy_rate'=>0,'accepting'=>0,'limited'=>0,'full'=>0,
];
$centers = $centers ?? [];
$occ     = min(100, max(0, (int)($statistics['occupancy_rate'] ?? 0)));
$tc      = (int)($statistics['total_capacity']  ?? 0);
$ab      = (int)($statistics['available_beds']  ?? 0);
?>

<div class="sh-page">
<main class="sh-main">
<div class="sh-container">

  <!-- ── Page Header -->
  <div class="sh-page-head">
    <div class="sh-page-head-left">
      <div class="sh-page-head-icon sh-icon-blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div>
        <h2>Capacity Management</h2>
        <p>Monitor and manage evacuation center occupancy</p>
      </div>
    </div>
    <div class="sh-live-badge"><div class="sh-live-dot"></div>LIVE</div>
  </div>

  <!-- ── Stat Cards -->
  <div class="sh-stats">
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Current Occupancy</div>
        <div class="sh-stat-val"><?= $occ ?>%</div>
        <div class="sh-prog-track"><div class="sh-prog-fill" style="width:<?= $occ ?>%"></div></div>
        <div class="sh-stat-sub"><?= (int)($statistics['total_evacuees']??0) ?> / <?= $tc ?> beds</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Available Beds</div>
        <div class="sh-stat-val"><?= $ab ?></div>
        <div class="sh-stat-sub"><?= $tc > 0 ? round(($ab/$tc)*100) : 0 ?>% of capacity free</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-purple">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Total Capacity</div>
        <div class="sh-stat-val"><?= $tc ?></div>
        <div class="sh-stat-sub">
          <span class="sh-dot sh-dot-green"></span><?= (int)($statistics['accepting']??0) ?> accepting&nbsp;
          <span class="sh-dot sh-dot-yellow"></span><?= (int)($statistics['limited']??0) ?> limited&nbsp;
          <span class="sh-dot sh-dot-red"></span><?= (int)($statistics['full']??0) ?> full
        </div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-orange">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Total Centers</div>
        <div class="sh-stat-val"><?= (int)($statistics['total_centers']??0) ?></div>
        <div class="sh-stat-sub">Registered shelter locations</div>
      </div>
    </div>
  </div>

  <!-- ── Evacuation Centers Card -->
  <div class="sh-card">
    <div class="sh-card-head">
      <div class="sh-card-head-left">
        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          Evacuation Centers
        </h3>
        <span class="sh-card-sub">Manage centers · update occupancy · add or edit</span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <button class="sh-btn sh-btn-ghost" onclick="location.reload()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
          Refresh
        </button>
        <button class="sh-btn sh-btn-teal" onclick="openAddCenter()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Center
        </button>
      </div>
    </div>

    <?php if (empty($centers)): ?>
      <div class="sh-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        <p>No evacuation centers found.</p>
      </div>
    <?php else: ?>
    <div class="cap-ec-grid">
      <?php foreach ($centers as $c):
        $cap   = max(1,(int)($c['capacity']??1));
        $cur   = (int)($c['current_occupancy']??0);
        $avail = max(0,$cap-$cur);
        $pct   = round(($cur/$cap)*100);
        $st    = $c['status']??'unknown';
        $scBadge  = ['accepting'=>'sh-badge-green','limited'=>'sh-badge-yellow','full'=>'sh-badge-red'][$st]??'sh-badge-gray';
        $barColor = ['accepting'=>'var(--sh-green)','limited'=>'var(--sh-yellow)','full'=>'var(--sh-red)'][$st]??'var(--sh-text-muted)';
        $leftCol  = ['accepting'=>'var(--sh-green)','limited'=>'var(--sh-yellow)','full'=>'var(--sh-red)'][$st]??'rgba(255,255,255,.08)';
      ?>
      <div class="cap-ec-card" id="center-row-<?= (int)$c['id'] ?>" style="border-left:3px solid <?= $leftCol ?>;">
        <div class="cap-ec-top">
          <div class="cap-ec-name"><?= htmlspecialchars($c['name']??'') ?></div>
          <span class="sh-badge <?= $scBadge ?>" id="center-status-<?= (int)$c['id'] ?>"><?= ucfirst($st) ?></span>
        </div>
        <?php if (!empty($c['barangay']) || !empty($c['address'])): ?>
        <div class="cap-ec-location">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <?= htmlspecialchars($c['barangay'] ?? $c['address'] ?? '') ?>
        </div>
        <?php endif; ?>

        <div class="cap-ec-numbers">
          <div class="cap-ec-num-block">
            <div class="cap-ec-big" id="occ-cur-<?= (int)$c['id'] ?>"><?= $cur ?></div>
            <div class="cap-ec-lbl">inside</div>
          </div>
          <div class="cap-ec-divider">/</div>
          <div class="cap-ec-num-block">
            <div class="cap-ec-big cap-ec-big-cap"><?= $cap ?></div>
            <div class="cap-ec-lbl">capacity</div>
          </div>
          <div class="cap-ec-avail-pill">
            <div class="cap-ec-big cap-ec-big-avail"><?= $avail ?></div>
            <div class="cap-ec-lbl">free</div>
          </div>
        </div>

        <div class="sh-prog-track" style="margin-bottom:4px;">
          <div class="sh-prog-fill" style="background:<?= $barColor ?>;width:<?= min(100,$pct) ?>%;"></div>
        </div>
        <div class="cap-ec-pct-row">
          <span><?= $pct ?>% occupied</span>
          <span style="color:var(--sh-teal);"><?= $avail ?> beds free</span>
        </div>

        <hr class="sh-divider" style="margin:12px 0;">

        <div class="cap-ec-update-row">
          <label class="cap-ec-update-lbl">Update occupancy:</label>
          <input type="number" class="sh-input cap-ec-input"
                 id="occ-input-<?= (int)$c['id'] ?>"
                 value="<?= $cur ?>" min="0" max="<?= $cap ?>">
          <button class="sh-btn sh-btn-teal sh-btn-sm"
                  onclick="saveOccupancy(<?= (int)$c['id'] ?>, <?= $cap ?>)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
            Save
          </button>
        </div>

        <div class="cap-ec-actions">
          <button class="sh-btn sh-btn-ghost sh-btn-sm"
                  onclick="openBedsModal(<?= (int)$c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name']??'')) ?>', <?= $cur ?>, <?= $cap ?>)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9V4a1 1 0 0 1 1-1h18a1 1 0 0 1 1 1v5"/><path d="M2 20v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5"/><path d="M2 15h20"/></svg>
            Beds
          </button>
          <button class="sh-btn sh-btn-ghost sh-btn-sm"
                  onclick="openEditCenter(<?= (int)$c['id'] ?>, <?= htmlspecialchars(json_encode([
                    'name'           => $c['name']           ?? '',
                    'barangay'       => $c['barangay']        ?? '',
                    'address'        => $c['address']         ?? '',
                    'capacity'       => $cap,
                    'contact_number' => $c['contact_number']  ?? '',
                    'facilities'     => $c['facilities']      ?? '',
                  ]), ENT_QUOTES) ?>)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
          <button class="sh-btn sh-btn-danger sh-btn-sm"
                  onclick="confirmDeleteCenter(<?= (int)$c['id'] ?>, '<?= htmlspecialchars($c['name']??'') ?>')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
            Delete
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Modals -->
  <div class="sh-modal-overlay" id="addCenterModal">
    <div class="sh-modal sh-modal-wide">
      <div class="sh-modal-head">
        <div class="sh-modal-head-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="var(--sh-teal)" stroke-width="2" width="18" height="18"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          <h3>Add Evacuation Center</h3>
        </div>
        <button class="sh-modal-close" onclick="closeAddCenter()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      </div>
      <div class="sh-modal-body">
        <div id="addCenterError" class="sh-flash sh-flash-error" style="display:none;"></div>
        <div class="sh-form-grid">
          <div class="sh-form-group sh-span-2"><label class="sh-label">Center Name *</label><input type="text" class="sh-input" id="ac_name" placeholder="e.g. Barangay Hall Shelter"></div>
          <div class="sh-form-group"><label class="sh-label">Barangay</label><input type="text" class="sh-input" id="ac_barangay" placeholder="e.g. Barangay 5"></div>
          <div class="sh-form-group"><label class="sh-label">Address</label><input type="text" class="sh-input" id="ac_address" placeholder="Full address"></div>
          <div class="sh-form-group"><label class="sh-label">Max Capacity *</label><input type="number" class="sh-input" id="ac_capacity" placeholder="e.g. 500" min="1"></div>
          <div class="sh-form-group"><label class="sh-label">Contact Number</label><input type="text" class="sh-input" id="ac_contact" placeholder="e.g. 09XXXXXXXXX"></div>
          <div class="sh-form-group sh-span-2"><label class="sh-label">Facilities</label><input type="text" class="sh-input" id="ac_facilities" placeholder="e.g. Medical, Food, Water, Restrooms"></div>
        </div>
      </div>
      <div class="sh-modal-foot">
        <button class="sh-btn sh-btn-ghost" onclick="closeAddCenter()">Cancel</button>
        <button class="sh-btn sh-btn-teal" onclick="submitAddCenter()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add Center</button>
      </div>
    </div>
  </div>

  <div class="sh-modal-overlay" id="editCenterModal">
    <div class="sh-modal sh-modal-wide">
      <div class="sh-modal-head">
        <div class="sh-modal-head-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="var(--sh-blue)" stroke-width="2" width="18" height="18"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          <h3>Edit Evacuation Center</h3>
        </div>
        <button class="sh-modal-close" onclick="closeEditCenter()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      </div>
      <div class="sh-modal-body">
        <input type="hidden" id="ec_center_id">
        <div id="editCenterError" class="sh-flash sh-flash-error" style="display:none;"></div>
        <div class="sh-form-grid">
          <div class="sh-form-group sh-span-2"><label class="sh-label">Center Name *</label><input type="text" class="sh-input" id="ec_name"></div>
          <div class="sh-form-group"><label class="sh-label">Barangay</label><input type="text" class="sh-input" id="ec_barangay"></div>
          <div class="sh-form-group"><label class="sh-label">Address</label><input type="text" class="sh-input" id="ec_address"></div>
          <div class="sh-form-group"><label class="sh-label">Max Capacity *</label><input type="number" class="sh-input" id="ec_capacity" min="1"></div>
          <div class="sh-form-group"><label class="sh-label">Contact Number</label><input type="text" class="sh-input" id="ec_contact"></div>
          <div class="sh-form-group sh-span-2"><label class="sh-label">Facilities</label><input type="text" class="sh-input" id="ec_facilities"></div>
        </div>
      </div>
      <div class="sh-modal-foot">
        <button class="sh-btn sh-btn-ghost" onclick="closeEditCenter()">Cancel</button>
        <button class="sh-btn sh-btn-primary" onclick="submitEditCenter()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>Save Changes</button>
      </div>
    </div>
  </div>

  <div class="sh-modal-overlay" id="deleteCenterModal">
    <div class="sh-modal" style="max-width:380px;">
      <div class="sh-modal-body" style="align-items:center;text-align:center;padding:32px 28px 12px;">
        <div style="width:56px;height:56px;border-radius:50%;background:var(--sh-red-pale);border:1px solid rgba(231,76,60,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="var(--sh-red)" stroke-width="2" width="26" height="26"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
        </div>
        <h3 style="color:var(--sh-text);margin-bottom:6px;">Delete this center?</h3>
        <p id="deleteCenterMsg" style="color:var(--sh-text-sub);font-size:0.84rem;line-height:1.5;"></p>
      </div>
      <div class="sh-modal-foot" style="justify-content:center;">
        <button class="sh-btn sh-btn-ghost" onclick="closeDeleteCenterModal()">Cancel</button>
        <button class="sh-btn sh-btn-danger" id="doDeleteCenterBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Yes, Delete</button>
      </div>
    </div>
  </div>

  <div class="sh-modal-overlay" id="deleteModal">
    <div class="sh-modal" style="max-width:380px;">
      <div class="sh-modal-body" style="align-items:center;text-align:center;padding:32px 28px 12px;">
        <div style="width:56px;height:56px;border-radius:50%;background:var(--sh-red-pale);border:1px solid rgba(231,76,60,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="var(--sh-red)" stroke-width="2" width="26" height="26"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <h3 style="color:var(--sh-text);margin-bottom:6px;">Delete this request?</h3>
        <p style="color:var(--sh-text-sub);font-size:0.84rem;line-height:1.5;">This permanently removes the record. Cannot be undone.</p>
      </div>
      <div class="sh-modal-foot" style="justify-content:center;">
        <button class="sh-btn sh-btn-ghost" onclick="closeDeleteModal()">Cancel</button>
        <button class="sh-btn sh-btn-danger" id="doDeleteBtn">Yes, Delete</button>
      </div>
    </div>
  </div>

  <div class="sh-modal-overlay" id="bedsModal">
    <div class="sh-modal" style="max-width:440px;">
      <div class="sh-modal-head">
        <div class="sh-modal-head-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="var(--sh-purple)" stroke-width="2" width="18" height="18"><path d="M2 9V4a1 1 0 0 1 1-1h18a1 1 0 0 1 1 1v5"/><path d="M2 20v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5"/><path d="M2 15h20"/></svg>
          <h3>Edit Beds – <span id="bedsModalName"></span></h3>
        </div>
        <button class="sh-modal-close" onclick="closeBedsModal()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
      </div>
      <div class="sh-modal-body">
        <div id="bedsModalError" class="sh-flash sh-flash-error" style="display:none;"></div>
        <input type="hidden" id="bm_center_id">
        <div class="sh-form-grid">
          <div class="sh-form-group"><label class="sh-label">Current Occupancy</label><input type="number" class="sh-input" id="bm_occupancy" min="0" placeholder="0"></div>
          <div class="sh-form-group"><label class="sh-label">Total Bed Capacity</label><input type="number" class="sh-input" id="bm_capacity" min="1" placeholder="e.g. 500"></div>
        </div>
        <div class="cap-beds-preview" id="bedsPreview">
          Beds free: <strong id="bmPreviewFree">—</strong> of <strong id="bmPreviewTotal">—</strong>
        </div>
      </div>
      <div class="sh-modal-foot">
        <button class="sh-btn sh-btn-ghost" onclick="closeBedsModal()">Cancel</button>
        <button class="sh-btn sh-btn-purple" onclick="submitBeds()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>Save Beds</button>
      </div>
    </div>
  </div>

  <div class="sh-toast" id="capToast">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="capToastMsg"></span>
  </div>

</div></main></div>
