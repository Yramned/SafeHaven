<?php
/**
 * SafeHaven - Dashboard View
 * Modules always on top, analytics below for admin.
 */
$isAdmin = strtolower($_SESSION['user_role'] ?? '') === 'admin';
?>

<div class="dash-page">
<main class="dash-main">
<div class="dash-container">

<!-- ── Welcome Banner ─────────────────────────────────── -->
<div class="dash-banner">
    <div>
        <h2>Welcome back, <span><?= htmlspecialchars($userName) ?></span></h2>
        <p>You are now connected to the SafeHaven Emergency Evacuation Network.</p>
    </div>
    <div class="dash-banner-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
    </div>
</div>

<?php if (!$isAdmin): ?>
<!-- ── Evacuee stat cards ──────────────────────────────── -->
<div class="stat-row">
    <div class="stat-card top-green">
        <div class="stat-icon si-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div><div class="stat-label">Profile</div><div class="stat-value">Verified</div></div>
    </div>
    <div class="stat-card top-blue">
        <div class="stat-icon si-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </div>
        <div><div class="stat-label">Alerts</div><div class="stat-value">Active</div></div>
    </div>
    <div class="stat-card top-orange">
        <div class="stat-icon si-orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div><div class="stat-label">Last Check</div><div class="stat-value">Just Now</div></div>
    </div>
</div>
<?php endif; ?>

<!-- ── Modules (always on top for everyone) ───────────── -->
<div class="modules-head">
    <h3>Modules</h3>
</div>
<div class="modules-grid">
    <?php foreach ($visibleModules as $module): ?>
    <a href="<?= htmlspecialchars($module['url']) ?>" class="module-card">
        <div class="mod-icon <?= htmlspecialchars($module['icon_class']) ?>"><?= $module['icon_svg'] ?></div>
        <div class="mod-info">
            <h4><?= htmlspecialchars($module['name']) ?></h4>
            <p><?= htmlspecialchars($module['description']) ?></p>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($isAdmin && !empty($analyticsData)): ?>
<?php
    $ov = $analyticsData['overview'];
    $mo = $analyticsData['monthly'];
    $ur = $analyticsData['user_roles'];
    $as = $analyticsData['alert_severity'];
    $rs = $analyticsData['request_status'];
    $rp = $analyticsData['request_priority'];
    $centers    = $analyticsData['centers'];
    $recentReqs = $analyticsData['recent_requests'];
?>

<div class="an-divider"></div>

<!-- ── Overview Cards ─────────────────────────────────── -->
<div class="an-section-label">System Overview</div>
<div class="an-stat-row">
    <div class="an-stat-card">
        <div class="an-stat-icon an-icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div class="an-stat-value"><?= number_format((int)($ov['total_users'] ?? 0)) ?></div>
            <div class="an-stat-label">Total Users</div>
            <div class="an-stat-sub"><?= (int)($ov['total_evacuees'] ?? 0) ?> evacuees &middot; <?= (int)($ov['total_admins'] ?? 0) ?> admins</div>
        </div>
    </div>
    <div class="an-stat-card">
        <div class="an-stat-icon an-icon-red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </div>
        <div>
            <div class="an-stat-value"><?= (int)($ov['active_alerts'] ?? 0) ?></div>
            <div class="an-stat-label">Active Alerts</div>
            <div class="an-stat-sub"><?= (int)(($as['critical'] ?? 0) + ($as['evacuation'] ?? 0)) ?> critical / evacuation</div>
        </div>
    </div>
    <div class="an-stat-card">
        <div class="an-stat-icon an-icon-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div>
            <div class="an-stat-value"><?= (int)($ov['total_centers'] ?? 0) ?></div>
            <div class="an-stat-label">Evac Centers</div>
            <div class="an-stat-sub"><?= (int)($ov['available_centers'] ?? 0) ?> available now</div>
        </div>
    </div>
    <div class="an-stat-card">
        <div class="an-stat-icon an-icon-orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
            <div class="an-stat-value"><?= (int)($ov['pending_requests'] ?? 0) ?></div>
            <div class="an-stat-label">Pending Requests</div>
            <div class="an-stat-sub"><?= (int)($ov['evacuees_in_centers'] ?? 0) ?> currently in centers</div>
        </div>
    </div>
</div>

<!-- ── Activity + Request Status ──────────────────────── -->
<div class="an-section-label">Activity &amp; Trends</div>
<div class="an-charts-row">
    <div class="an-chart-card an-chart-wide">
        <div class="an-chart-head">
            <span class="an-chart-title">Activity Over Time</span>
            <div class="an-period-filters">
                <button class="an-period-btn an-period-active" data-period="daily">Daily</button>
                <button class="an-period-btn" data-period="weekly">Weekly</button>
                <button class="an-period-btn" data-period="monthly">Monthly</button>
            </div>
        </div>
        <div class="an-legend">
            <span><span class="an-leg" style="background:#3498db"></span>Evacuation Requests</span>
            <span><span class="an-leg" style="background:#e74c3c"></span>Alerts Issued</span>
        </div>
        <div id="chartMonthlyWrap" style="position:relative;height:220px">
            <canvas id="chartMonthly" role="img" aria-label="Activity bar chart">Activity data.</canvas>
        </div>
    </div>
    <div class="an-chart-card">
        <div class="an-chart-head">
            <span class="an-chart-title">Request Status</span>
            <span class="an-badge an-badge-orange">BREAKDOWN</span>
        </div>
        <div class="an-legend">
            <span><span class="an-leg" style="background:#f39c12"></span>Pending</span>
            <span><span class="an-leg" style="background:#27ae60"></span>Approved</span>
            <span><span class="an-leg" style="background:#1abc9c"></span>Completed</span>
            <span><span class="an-leg" style="background:#e74c3c"></span>Rejected/Denied</span>
        </div>
        <div style="position:relative;height:200px">
            <canvas id="chartReqStatus" role="img" aria-label="Request status donut chart">Status breakdown.</canvas>
        </div>
    </div>
</div>

<!-- ── User Roles + Alert Severity + Priority ─────────── -->
<div class="an-section-label">Users &amp; Alerts</div>
<div class="an-charts-row an-charts-three">
    <div class="an-chart-card">
        <div class="an-chart-head">
            <span class="an-chart-title">User Role Breakdown</span>
            <span class="an-badge an-badge-blue">BY ROLE</span>
        </div>
        <div class="an-legend">
            <span><span class="an-leg" style="background:#3498db"></span>Evacuees</span>
            <span><span class="an-leg" style="background:#6366f1"></span>Admins</span>
        </div>
        <div style="position:relative;height:190px">
            <canvas id="chartUserRoles" role="img" aria-label="User roles donut">Roles.</canvas>
        </div>
    </div>
    <div class="an-chart-card">
        <div class="an-chart-head">
            <span class="an-chart-title">Alert Severity</span>
            <span class="an-badge an-badge-red">BREAKDOWN</span>
        </div>
        <div class="an-legend">
            <span><span class="an-leg" style="background:#e74c3c"></span>Critical</span>
            <span><span class="an-leg" style="background:#e67e22"></span>Evacuation</span>
            <span><span class="an-leg" style="background:#f39c12"></span>Warning</span>
            <span><span class="an-leg" style="background:#3498db"></span>Info</span>
        </div>
        <div style="position:relative;height:190px">
            <canvas id="chartAlertSev" role="img" aria-label="Alert severity donut">Severity.</canvas>
        </div>
    </div>
    <div class="an-chart-card">
        <div class="an-chart-head">
            <span class="an-chart-title">Request Priority</span>
            <span class="an-badge an-badge-orange">BY LEVEL</span>
        </div>
        <div class="an-legend">
            <span><span class="an-leg" style="background:#3498db"></span>Low</span>
            <span><span class="an-leg" style="background:#f39c12"></span>Medium</span>
            <span><span class="an-leg" style="background:#e67e22"></span>High</span>
            <span><span class="an-leg" style="background:#e74c3c"></span>Critical</span>
        </div>
        <div style="position:relative;height:190px">
            <canvas id="chartPriority" role="img" aria-label="Priority bar chart">Priority.</canvas>
        </div>
    </div>
</div>

<!-- ── Center Capacity Table ──────────────────────────── -->
<div class="an-section-label">Evacuation Center Capacity</div>
<div class="an-table-card">
    <div class="an-chart-head">
        <span class="an-chart-title">Live Center Status</span>
        <a href="<?= BASE_URL ?>index.php?page=capacity" class="an-badge an-badge-green" style="text-decoration:none">MANAGE &rarr;</a>
    </div>
    <?php if (!empty($centers)): ?>
    <div class="an-table-wrap">
    <table class="an-table">
        <thead><tr><th>Center Name</th><th>Barangay</th><th>Occupancy</th><th>Capacity Used</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($centers as $c):
            $pct    = (int)($c['pct'] ?? 0);
            $cap    = (int)($c['capacity'] ?? 0);
            $occ    = (int)($c['current_occupancy'] ?? 0);
            $status = strtolower($c['status'] ?? 'accepting');
            if ($status === 'full' || $pct >= 90)            { $bar = '#e74c3c'; $pc = 'an-pill-red';    $pt = 'Full'; }
            elseif ($status === 'limited' || $pct >= 60)     { $bar = '#e67e22'; $pc = 'an-pill-orange'; $pt = 'Limited'; }
            else                                              { $bar = '#27ae60'; $pc = 'an-pill-green';  $pt = 'Accepting'; }
        ?>
        <tr>
            <td class="an-td-bold"><?= htmlspecialchars($c['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['barangay'] ?? '—') ?></td>
            <td><?= $occ ?> / <?= $cap ?></td>
            <td>
                <div class="an-bar-wrap">
                    <div class="an-bar-track"><div class="an-bar-fill" style="width:<?= $pct ?>%;background:<?= $bar ?>"></div></div>
                    <span class="an-bar-pct"><?= $pct ?>%</span>
                </div>
            </td>
            <td><span class="an-pill <?= $pc ?>"><?= $pt ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <p class="an-empty">No evacuation centers found. <a href="<?= BASE_URL ?>index.php?page=capacity">Add centers &rarr;</a></p>
    <?php endif; ?>
</div>

<!-- ── Recent Requests Table ──────────────────────────── -->
<div class="an-section-label">Recent Evacuation Requests</div>
<div class="an-table-card">
    <div class="an-chart-head">
        <span class="an-chart-title">Latest Requests</span>
        <a href="<?= BASE_URL ?>index.php?page=admin-evacuation" class="an-badge an-badge-orange" style="text-decoration:none">VIEW ALL &rarr;</a>
    </div>
    <?php if (!empty($recentReqs)): ?>
    <div class="an-table-wrap">
    <table class="an-table">
        <thead><tr><th>#</th><th>Requestor</th><th>Barangay</th><th>Priority</th><th>Special Needs</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recentReqs as $req):
            $status   = strtolower($req['status']   ?? 'pending');
            $priority = strtolower($req['priority'] ?? 'medium');
            if ($status === 'approved')                                  { $sc = 'an-pill-green';  $st = 'Approved'; }
            elseif ($status === 'completed')                             { $sc = 'an-pill-teal';   $st = 'Completed'; }
            elseif ($status === 'rejected' || $status === 'denied')     { $sc = 'an-pill-red';    $st = ucfirst($status); }
            else                                                         { $sc = 'an-pill-orange'; $st = 'Pending'; }
            if ($priority === 'critical')      { $pc = 'an-pill-red'; }
            elseif ($priority === 'high')      { $pc = 'an-pill-orange'; }
            elseif ($priority === 'medium')    { $pc = 'an-pill-yellow'; }
            else                               { $pc = 'an-pill-blue'; }
        ?>
        <tr>
            <td class="an-td-muted"><?= (int)$req['id'] ?></td>
            <td class="an-td-bold"><?= htmlspecialchars($req['user_name'] ?? 'Unknown') ?></td>
            <td><?= htmlspecialchars($req['location_barangay'] ?? '—') ?></td>
            <td><span class="an-pill <?= $pc ?>"><?= ucfirst($priority) ?></span></td>
            <td class="an-td-muted an-td-truncate"><?= htmlspecialchars($req['special_needs_display'] ?? 'None') ?></td>
            <td><span class="an-pill <?= $sc ?>"><?= $st ?></span></td>
            <td class="an-td-muted"><?= !empty($req['created_at']) ? date('M d, Y', strtotime($req['created_at'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <p class="an-empty">No evacuation requests yet.</p>
    <?php endif; ?>
</div>

<!-- ── Chart.js ───────────────────────────────────────── -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function(){
    var BASE  = '<?= BASE_URL ?>';
    var grid  = 'rgba(52,152,219,0.1)';
    var ticks = '#5a7a95';
    var ur = <?= json_encode($ur) ?>;
    var as = <?= json_encode($as) ?>;
    var rs = <?= json_encode($rs) ?>;
    var rp = <?= json_encode($rp) ?>;

    // ── Activity chart with Daily / Weekly / Monthly filter ──────────────
    var activityChart = null;

    function buildActivityChart(data) {
        var ctx = document.getElementById('chartMonthly');
        if (!ctx) return;
        if (activityChart) { activityChart.destroy(); }
        activityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {label:'Evacuation Requests', data:data.evacuations, backgroundColor:'#3498db', borderRadius:4},
                    {label:'Alerts Issued',        data:data.alerts,     backgroundColor:'#e74c3c', borderRadius:4}
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid:{color:grid}, ticks:{color:ticks, font:{size:11}} },
                    y: { grid:{color:grid}, ticks:{color:ticks, font:{size:11}}, beginAtZero:true }
                }
            }
        });
    }

    function loadActivityData(period) {
        var wrap = document.getElementById('chartMonthlyWrap');
        if (wrap) wrap.style.opacity = '0.5';
        fetch(BASE + 'index.php?page=chart-data&period=' + encodeURIComponent(period))
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function(data) {
                if (wrap) wrap.style.opacity = '1';
                buildActivityChart(data);
            })
            .catch(function(err) {
                if (wrap) wrap.style.opacity = '1';
                console.error('[SafeHaven] Chart data error:', err);
            });
    }

    // Initial load (default: daily)
    loadActivityData('daily');

    // Filter button click handler
    document.querySelectorAll('.an-period-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.an-period-btn').forEach(function(b){ b.classList.remove('an-period-active'); });
            this.classList.add('an-period-active');
            loadActivityData(this.getAttribute('data-period'));
        });
    });

    // ── Other charts (static, no filter needed) ──────────────────────────
    new Chart(document.getElementById('chartReqStatus'),{
        type:'doughnut',
        data:{
            labels:['Pending','Approved','Completed','Rejected','Denied'],
            datasets:[{data:[rs.pending,rs.approved,rs.completed,rs.rejected,rs.denied],
                backgroundColor:['#f39c12','#27ae60','#1abc9c','#e74c3c','#c0392b'],borderWidth:0,hoverOffset:4}]
        },
        options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{display:false}}}
    });

    new Chart(document.getElementById('chartUserRoles'),{
        type:'doughnut',
        data:{
            labels:['Evacuees','Admins'],
            datasets:[{data:[ur.evacuee||0,ur.admin||0],
                backgroundColor:['#3498db','#6366f1'],borderWidth:0,hoverOffset:4}]
        },
        options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{display:false}}}
    });

    new Chart(document.getElementById('chartAlertSev'),{
        type:'doughnut',
        data:{
            labels:['Critical','Evacuation','Warning','Info'],
            datasets:[{data:[as.critical,as.evacuation,as.warning,as.info],
                backgroundColor:['#e74c3c','#e67e22','#f39c12','#3498db'],borderWidth:0,hoverOffset:4}]
        },
        options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{display:false}}}
    });

    new Chart(document.getElementById('chartPriority'),{
        type:'bar',
        data:{
            labels:['Low','Medium','High','Critical'],
            datasets:[{label:'Requests',data:[rp.low||0,rp.medium||0,rp.high||0,rp.critical||0],
                backgroundColor:['#3498db','#f39c12','#e67e22','#e74c3c'],borderRadius:4}]
        },
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
            scales:{x:{grid:{display:false},ticks:{color:ticks,font:{size:11}}},y:{grid:{color:grid},ticks:{color:ticks,font:{size:11}},beginAtZero:true}}}
    });
})();
</script>

<?php endif; ?>

<div class="logout-row">
    <a href="index.php?page=logout" class="btn-logout">Logout</a>
</div>

</div>
</main>
</div>
