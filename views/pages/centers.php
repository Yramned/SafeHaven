<?php
/**
 * SafeHaven – Evacuation Centers View (Unified Design)
 * Fixed search functionality
 */

if (!isset($evacuationCenters)) $evacuationCenters = [];
if (!isset($statistics))        $statistics = [];

function ctrBadgeClass($s) {
    return match($s) {
        'accepting' => 'ctr-badge-green',
        'limited'   => 'ctr-badge-yellow',
        'full'      => 'ctr-badge-red',
        default     => 'ctr-badge-gray'
    };
}
function ctrCardClass($s) {
    return match($s) {
        'accepting' => 'status-accepting',
        'limited'   => 'status-limited',
        'full'      => 'status-full',
        default     => 'status-unknown'
    };
}
function ctrBarColor($s) {
    return match($s) {
        'accepting' => 'var(--sh-green)',
        'limited'   => 'var(--sh-yellow)',
        'full'      => 'var(--sh-red)',
        default     => 'var(--sh-text-muted)'
    };
}

// Dot SVG helpers for badges
function ctrStatusDot($s) {
    $colors = ['accepting'=>'var(--sh-green)','limited'=>'var(--sh-yellow)','full'=>'var(--sh-red)'];
    $col = $colors[$s] ?? 'var(--sh-text-muted)';
    return '<svg viewBox="0 0 10 10" width="8" height="8"><circle cx="5" cy="5" r="4" fill="'.$col.'"/></svg>';
}
?>

<div class="sh-page">
<main class="sh-main">
<div class="sh-container">

  <!-- Page Header -->
  <div class="sh-page-head">
    <div class="sh-page-head-left">
      <div class="sh-page-head-icon sh-icon-teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div>
        <h2>Evacuation Centers</h2>
        <p>Real-time center availability and occupancy</p>
      </div>
    </div>
    <div class="sh-live-badge"><div class="sh-live-dot"></div>LIVE</div>
  </div>

  <!-- Stat Cards -->
  <div class="sh-stats">
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Total Evacuees</div>
        <div class="sh-stat-val"><?= number_format((int)($statistics['total_evacuees'] ?? 0)) ?></div>
        <div class="sh-stat-sub">Currently sheltered</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-orange">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Occupancy Rate</div>
        <div class="sh-stat-val"><?= $statistics['occupancy_rate'] ?? 0 ?>%</div>
        <div class="sh-stat-sub">Across all centers</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Available Beds</div>
        <div class="sh-stat-val"><?= number_format((int)($statistics['available_beds'] ?? 0)) ?></div>
        <div class="sh-stat-sub">Beds still open</div>
      </div>
    </div>
    <div class="sh-stat-card">
      <div class="sh-stat-icon sh-icon-purple">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
      </div>
      <div class="sh-stat-body">
        <div class="sh-stat-label">Total Centers</div>
        <div class="sh-stat-val"><?= $statistics['total_centers'] ?? 0 ?></div>
        <div class="sh-stat-sub">
          <span class="sh-dot sh-dot-green"></span><?= $statistics['accepting'] ?? 0 ?> accepting&nbsp;
          <span class="sh-dot sh-dot-yellow"></span><?= $statistics['limited'] ?? 0 ?> limited&nbsp;
          <span class="sh-dot sh-dot-red"></span><?= $statistics['full'] ?? 0 ?> full
        </div>
      </div>
    </div>
  </div>

  <!-- Centers Section -->
  <div class="sh-card">
    <div class="sh-card-head">
      <div class="sh-card-head-left">
        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          All Evacuation Centers
        </h3>
        <span class="sh-card-sub"><?= count($evacuationCenters) ?> centers registered</span>
      </div>
      <!-- Search — fixed: uses plain text input, JS filters on input event -->
      <div class="sh-search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="sh-search-input" id="centerSearchInput" placeholder="Search centers…" autocomplete="off">
      </div>
    </div>

    <?php if (empty($evacuationCenters)): ?>
      <div class="sh-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        <p>No evacuation centers are currently registered.</p>
      </div>
    <?php else: ?>
    <div style="padding:20px 24px;">
      <div class="centers-grid" id="centersGrid">
        <?php foreach ($evacuationCenters as $center):
          $cap      = max(1, (int)($center['capacity'] ?? 1));
          $cur      = (int)($center['current_occupancy'] ?? 0);
          $avail    = max(0, $cap - $cur);
          $pct      = round(($cur / $cap) * 100);
          $status   = $center['status'] ?? 'unknown';
          $badgeCls = ctrBadgeClass($status);
          $cardCls  = ctrCardClass($status);
          $barColor = ctrBarColor($status);
          $name     = $center['name']     ?? 'Unknown Center';
          $location = $center['barangay'] ?? $center['address'] ?? '';
          $contact  = $center['contact_number'] ?? '';
          $facilities = $center['facilities'] ?? '';
          $searchKey = strtolower($name . ' ' . $location);
        ?>
        <div class="center-card <?= $cardCls ?>" data-search="<?= htmlspecialchars($searchKey) ?>">

          <div class="center-card-top">
            <div>
              <div class="center-card-name"><?= htmlspecialchars($name) ?></div>
              <?php if ($location): ?>
              <div class="center-card-location">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= htmlspecialchars($location) ?>
              </div>
              <?php endif; ?>
            </div>
            <span class="ctr-badge <?= $badgeCls ?>">
              <?= ctrStatusDot($status) ?> <?= ucfirst($status) ?>
            </span>
          </div>

          <div class="center-card-numbers">
            <div class="cc-num-big"><?= $cur ?></div>
            <div class="cc-num-divider">/</div>
            <div class="cc-num-cap"><?= $cap ?></div>
          </div>

          <div class="sh-prog-track">
            <div class="sh-prog-fill" style="width:<?= min(100,$pct) ?>%;background:<?= $barColor ?>;"></div>
          </div>

          <div class="center-card-footer">
            <span class="cc-pct"><?= $pct ?>% full</span>
            <span class="cc-available">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/></svg>
              <?= $avail ?> beds free
            </span>
          </div>

          <?php if ($contact || $facilities): ?>
          <div class="center-card-details">
            <?php if ($contact): ?>
            <div class="cc-detail">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.36 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.11 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 21 16z"/></svg>
              <span><?= htmlspecialchars($contact) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($facilities): ?>
            <div class="cc-detail">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
              <span><?= htmlspecialchars($facilities) ?></span>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

        </div>
        <?php endforeach; ?>
      </div>

      <!-- No results message -->
      <div id="noSearchResults" style="display:none;" class="sh-empty" style="padding:40px 0;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <p>No centers match your search.</p>
      </div>
    </div>
    <?php endif; ?>

    <div class="ctr-footer">
      Last updated: <span id="ctrLastUpdate"></span>
    </div>
  </div>

</div></main></div>

<script>
// Last updated timestamp
document.getElementById('ctrLastUpdate').textContent = new Date().toLocaleTimeString('en-PH');

// Search filter — fixed: uses data-search attribute on each card
(function() {
    var input = document.getElementById('centerSearchInput');
    var grid  = document.getElementById('centersGrid');
    var noRes = document.getElementById('noSearchResults');
    if (!input || !grid) return;

    input.addEventListener('input', function() {
        var q = this.value.toLowerCase().trim();
        var cards = grid.querySelectorAll('.center-card');
        var visible = 0;
        cards.forEach(function(card) {
            var key = (card.dataset.search || '').toLowerCase();
            var show = !q || key.includes(q);
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noRes) noRes.style.display = (visible === 0 && q) ? 'flex' : 'none';
    });
})();
</script>
