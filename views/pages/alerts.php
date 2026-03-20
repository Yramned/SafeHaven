<script>window.SAFEHAVEN_BASE = "<?= BASE_URL ?>";</script>
<?php
/**
 * SafeHaven – Situational Alerts View (Improved Design)
 */
if (!isset($userRole))       $userRole       = $_SESSION['user_role'] ?? 'evacuee';
if (!isset($userId))         $userId         = (int)($_SESSION['user_id'] ?? 0);
if (!isset($userName))       $userName       = $_SESSION['user_name']  ?? '';
if (!isset($isAdmin))        $isAdmin        = in_array($userRole, ['admin','center_manager']);
if (!isset($alerts))         $alerts         = [];
if (!isset($counts))         $counts         = ['critical_count'=>0,'warning_count'=>0,'unread_count'=>0];
if (!isset($sensorData))     $sensorData     = [];
if (!isset($filterSeverity)) $filterSeverity = $_GET['severity'] ?? 'all';
if (!isset($alertFlash))     $alertFlash     = $_SESSION['alert_flash'] ?? null;
unset($_SESSION['alert_flash']);

$severityConfig = [
    'critical'   => ['label'=>'Critical',   'color'=>'#ff4d4d','bg'=>'rgba(255,77,77,0.14)',  'border'=>'rgba(255,77,77,0.4)',  'iconType'=>'critical',   'badge'=>'badge-red'],
    'evacuation' => ['label'=>'Evacuation', 'color'=>'#ff7070','bg'=>'rgba(139,0,0,0.14)',    'border'=>'rgba(139,0,0,0.4)',    'iconType'=>'evacuation', 'badge'=>'badge-dark-red'],
    'warning'    => ['label'=>'Warning',    'color'=>'#ffcc44','bg'=>'rgba(255,204,68,0.14)', 'border'=>'rgba(255,204,68,0.4)', 'iconType'=>'warning',    'badge'=>'badge-orange'],
    'info'       => ['label'=>'Info',       'color'=>'#5eb0ff','bg'=>'rgba(94,176,255,0.14)', 'border'=>'rgba(94,176,255,0.4)', 'iconType'=>'info',       'badge'=>'badge-blue'],
];

function alertSvgIcon(string $type, string $color='currentColor', int $size=20): string {
    $svgs = [
        'critical'   => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'evacuation' => '<path d="M13 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/><path d="M10 9l-2 5h8l-2-5"/><path d="M9 19v-5"/><path d="M15 19v-5"/>',
        'warning'    => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'info'       => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
    ];
    $d = $svgs[$type] ?? $svgs['info'];
    return "<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"{$color}\" stroke-width=\"2\" width=\"{$size}\" height=\"{$size}\">{$d}</svg>";
}

function sensorSvgIcon(string $key): string {
    $icons = [
        'temperature' => '<path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/>',
        'humidity'    => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',
        'wind_speed'  => '<path d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"/>',
        'flood_level' => '<path d="M2 12h20"/><path d="M2 8c1.5 2 3 2 4.5 0S9.5 6 11 8s3 2 4.5 0 3-2 4.5 0"/><path d="M2 16c1.5 2 3 2 4.5 0S9.5 14 11 16s3 2 4.5 0 3-2 4.5 0"/>',
    ];
    $d = $icons[$key] ?? '<circle cx="12" cy="12" r="3"/><path d="M12 1v4m0 14v4M4.22 4.22l2.83 2.83m9.9 9.9 2.83 2.83M1 12h4m14 0h4M4.22 19.78l2.83-2.83m9.9-9.9 2.83-2.83"/>';
    return "<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" width=\"20\" height=\"20\">{$d}</svg>";
}

if (!function_exists('alertTimeAgo')) {
    function alertTimeAgo(string $dt): string {
        $d = time() - strtotime($dt);
        if ($d < 60)    return $d.'s ago';
        if ($d < 3600)  return floor($d/60).'m ago';
        if ($d < 86400) return floor($d/3600).'h ago';
        return floor($d/86400).'d ago';
    }
}
function sBadgeCls(string $s): string { return ['critical'=>'badge-red','warn'=>'badge-orange','ok'=>'badge-green'][$s] ?? 'badge-green'; }
function sBadgeLbl(string $s): string { return ['critical'=>'Critical','warn'=>'Warning','ok'=>'Normal'][$s] ?? 'Normal'; }
function sCardCls (string $s): string { return ['critical'=>'status-critical','warn'=>'status-warn','ok'=>'status-ok'][$s] ?? 'status-ok'; }

$criticalCount = (int)($counts['critical_count'] ?? 0);
$warningCount  = (int)($counts['warning_count']  ?? 0);
$unreadCount   = (int)($counts['unread_count']   ?? 0);
$csrfToken     = $_SESSION['csrf_token'] ?? '';

$defaultSensors = [
    'temperature' => ['label'=>'Temperature','value'=>'—','unit'=>'°C',  'trend'=>'','status'=>'ok','sensor_key'=>'temperature'],
    'humidity'    => ['label'=>'Humidity',   'value'=>'—','unit'=>'%',   'trend'=>'','status'=>'ok','sensor_key'=>'humidity'],
    'wind_speed'  => ['label'=>'Wind Speed', 'value'=>'—','unit'=>'km/h','trend'=>'','status'=>'ok','sensor_key'=>'wind_speed'],
    'flood_level' => ['label'=>'Flood Level','value'=>'—','unit'=>'m',   'trend'=>'','status'=>'ok','sensor_key'=>'flood_level'],
];
$sensors = [];
foreach ($defaultSensors as $k => $def) {
    $sensors[$k] = !empty($sensorData[$k]) ? $sensorData[$k] : $def;
    $sensors[$k]['sensor_key'] = $k;
}
?>

<div class="sh-page">
<main class="sh-main">
<div class="sh-container">

<?php if (!empty($alertFlash)): ?>
<div class="sh-flash sh-flash-<?= htmlspecialchars($alertFlash['type']) ?>">
    <?php if ($alertFlash['type']==='success'): ?>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
    <?php else: ?>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    <?php endif; ?>
    <?= htmlspecialchars($alertFlash['msg']) ?>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="sh-page-head">
    <div class="sh-page-head-left">
        <div class="sh-page-head-icon sh-icon-red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <div>
            <h2>Situational Alerts</h2>
            <p>Real-time disaster monitoring &amp; sensor data</p>
        </div>
    </div>
</div>

<!-- Priority Statistics with CREATE ALERT at top for Admin -->
<div class="sh-stats sh-stats-3col">
    <div class="sh-stat-card">
        <div class="sh-stat-icon sh-icon-red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="sh-stat-body">
            <div class="sh-stat-label">Critical Alerts</div>
            <div class="sh-stat-val" id="countCritical"><?= $criticalCount ?></div>
            <div class="sh-stat-sub">Require immediate action</div>
        </div>
    </div>
    <div class="sh-stat-card">
        <div class="sh-stat-icon sh-icon-orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
        </div>
        <div class="sh-stat-body">
            <div class="sh-stat-label">Warning Alerts</div>
            <div class="sh-stat-val"><?= $warningCount ?></div>
            <div class="sh-stat-sub">High-priority notifications</div>
        </div>
    </div>
    <div class="sh-stat-card">
        <div class="sh-stat-icon sh-icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </div>
        <div class="sh-stat-body">
            <div class="sh-stat-label">Unread Alerts</div>
            <div class="sh-stat-val" id="countUnread"><?= $unreadCount ?></div>
            <div class="sh-stat-sub">New notifications</div>
        </div>
    </div>
</div>



<!-- Sensor Readings Section -->
<div class="sh-card">
    <div class="sh-card-head">
        <div class="sh-card-head-left">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                Environmental Sensors
            </h3>
            <span class="sh-card-sub"><?= $isAdmin ? 'Real-time environmental monitoring' : 'Latest sensor readings' ?></span>
        </div>
        <?php if ($isAdmin): ?>
        <button class="sh-btn sh-btn-ghost sh-btn-sm" id="openSensorModal" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Edit Sensor Data
        </button>
        <?php endif; ?>
    </div>
    <div class="sensor-grid" id="sensorGrid">
        <?php foreach ($sensors as $key => $s): ?>
        <div class="sensor-card <?= sCardCls($s['status'] ?? 'ok') ?>" data-key="<?= htmlspecialchars($key) ?>">
            <div class="sc-head">
                <span class="sc-title sc-title-icon">
                    <?= sensorSvgIcon($key) ?>
                    <?= htmlspecialchars($s['label'] ?? $key) ?>
                </span>
                <span class="badge <?= sBadgeCls($s['status'] ?? 'ok') ?>"><?= sBadgeLbl($s['status'] ?? 'ok') ?></span>
            </div>
            <div class="sc-val"><?= htmlspecialchars($s['value'] ?? '—') ?><span class="sc-unit"><?= htmlspecialchars($s['unit'] ?? '') ?></span></div>
            <div class="sc-trend"><?= htmlspecialchars($s['trend'] ?? '') ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Alerts List Section -->
<div class="sh-card">
    <div class="sh-card-head">
        <div class="sh-card-head-left">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.36 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.11 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16z"/></svg>
                Recent Alerts
            </h3>
            <span class="sh-card-sub">Latest emergency notifications</span>
        </div>
        <div class="a-filter-tabs">
            <?php
            $filterLabels = [
                'all'        => ['label'=>'All',       'svg'=>''],
                'critical'   => ['label'=>'Critical',   'svg'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'],
                'evacuation' => ['label'=>'Evacuation', 'svg'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M5 19l7-14 7 14"/><line x1="8" y1="13" x2="16" y2="13"/></svg>'],
                'warning'    => ['label'=>'Warning',    'svg'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>'],
                'info'       => ['label'=>'Info',       'svg'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>'],
            ];
            foreach ($filterLabels as $v => $fl): ?>
            <a href="<?= BASE_URL ?>index.php?page=alerts&severity=<?= $v ?>"
               class="a-ftab <?= $filterSeverity===$v?'a-ftab-active':'' ?>">
                <?= $fl['svg'] ?> <?= $fl['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="a-list">
        <?php if (empty($alerts)): ?>
        <div class="a-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2" width="20" height="20"><polyline points="20 6 9 17 4 12"/></svg>
            No alerts in this category.
        </div>
        <?php endif; ?>
        <?php foreach ($alerts as $alert):
            $cfg    = $severityConfig[$alert['severity']] ?? $severityConfig['info'];
            $isRead = (bool)$alert['is_read'];
        ?>
        <div class="a-item <?= $isRead?'a-read':'a-unread' ?>"
             data-id="<?= (int)$alert['id'] ?>"
             data-title="<?= htmlspecialchars($alert['title']) ?>"
             data-message="<?= htmlspecialchars($alert['message']) ?>"
             data-location="<?= htmlspecialchars($alert['location']??'') ?>"
             data-severity="<?= htmlspecialchars($alert['severity']) ?>"
             data-created="<?= htmlspecialchars($alert['created_at']) ?>"
             onclick="viewAlert(<?= (int)$alert['id'] ?>)">
            <?php if (!$isRead): ?><div class="a-dot" style="background:<?= $cfg['color'] ?>"></div><?php endif; ?>
            <div class="a-icon" style="background:<?= $cfg['bg'] ?>;border:1px solid <?= $cfg['border'] ?>;">
                <?= alertSvgIcon($cfg['iconType'], $cfg['color'], 22) ?>
            </div>
            <div class="a-body">
                <h4><?= htmlspecialchars($alert['title']) ?></h4>
                <p><?= htmlspecialchars($alert['message']) ?></p>
                <div class="a-meta">
                    <span class="badge <?= $cfg['badge'] ?>"><?= $cfg['label'] ?></span>
                    <?php if (!empty($alert['location'])): ?>
                    <span class="a-meta-txt">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= htmlspecialchars($alert['location']) ?>
                    </span>
                    <?php endif; ?>
                    <span class="a-meta-txt">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?= alertTimeAgo($alert['created_at']) ?>
                    </span>
                </div>
            </div>
            <button type="button" class="a-view-btn" onclick="event.stopPropagation(); viewAlert(<?= (int)$alert['id'] ?>);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($isAdmin): ?>
    <div class="a-broadcast-row">
        <button class="btn-broadcast-small" id="openCreateModal" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Create Broadcast
        </button>
    </div>
    <?php endif; ?>
</div>

</div></main></div>

<?php if ($isAdmin): ?>
<div class="m-overlay" id="sensorEditorModal" style="display:none;" role="dialog" aria-modal="true">
    <div class="m-box m-box-wide">
        <div class="m-head">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span style="margin-left:8px;">Edit Sensor Data</span>
            <button class="m-close" data-close="sensorEditorModal" type="button" style="margin-left:auto;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="padding:20px;">
            <div style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
                <p style="color:#7a94b8;font-size:13px;margin:0;">Update sensor readings and status</p>
                <button type="button" class="btn-reset-snr" onclick="resetSensors()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" style="vertical-align:middle;margin-right:4px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Reset All
                </button>
            </div>
            <div id="sensorEditRows">
                <?php foreach ($sensors as $key => $s): ?>
                <div class="se-row">
                    <div class="se-row-head">
                        <span class="se-row-icon"><?= sensorSvgIcon($key) ?></span>
                        <span class="se-row-lbl"><?= htmlspecialchars($s['label'] ?? $key) ?></span>
                        <button type="button" class="btn-se-save" onclick="saveSensor('<?= htmlspecialchars($key,ENT_QUOTES) ?>')">Save</button>
                    </div>
                    <div class="se-fields">
                        <div class="se-field">
                            <label>Value</label>
                            <input type="text" id="sr-value-<?= $key ?>" value="<?= htmlspecialchars($s['value'] ?? '') ?>" placeholder="e.g. 38.4">
                        </div>
                        <div class="se-field">
                            <label>Unit</label>
                            <input type="text" id="sr-unit-<?= $key ?>" value="<?= htmlspecialchars($s['unit'] ?? '') ?>" placeholder="e.g. °C">
                        </div>
                        <div class="se-field se-field-wide">
                            <label>Trend / Note</label>
                            <input type="text" id="sr-trend-<?= $key ?>" value="<?= htmlspecialchars($s['trend'] ?? '') ?>" placeholder="e.g. Rising 2.1° from last hour">
                        </div>
                        <div class="se-field">
                            <label>Status</label>
                            <select id="sr-status-<?= $key ?>">
                                <option value="ok"       <?= ($s['status']??'ok')==='ok'       ? 'selected':'' ?>>Normal</option>
                                <option value="warn"     <?= ($s['status']??'')==='warn'        ? 'selected':'' ?>>Warning</option>
                                <option value="critical" <?= ($s['status']??'')==='critical'    ? 'selected':'' ?>>Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="se-feedback" id="srf-<?= $key ?>"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<div class="m-overlay" id="createAlertModal" style="display:none;" role="dialog" aria-modal="true">
    <div class="m-box">
        <div class="m-head">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.36 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.11 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16z"/></svg>
            <span style="margin-left:8px;">Create New Alert</span>
            <button class="m-close" data-close="createAlertModal" type="button" style="margin-left:auto;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>index.php?page=alerts" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken,ENT_QUOTES,'UTF-8') ?>">
            <input type="hidden" name="action" value="create_alert">
            <div class="f-row">
                <label>Alert Title <span class="req">*</span></label>
                <input type="text" name="title" placeholder="e.g. Typhoon Warning – Barangay 7" required>
            </div>
            <div class="f-row">
                <label>Location</label>
                <input type="text" name="location" placeholder="e.g. District 4, Barangay 12">
            </div>
            <div class="f-row">
                <label>Severity <span class="req">*</span></label>
                <div class="sev-grid">
                    <?php foreach ($severityConfig as $k => $cfg): ?>
                    <label class="sev-opt">
                        <input type="radio" name="severity" value="<?= $k ?>" data-color="<?= $cfg['color'] ?>" <?= $k==='warning'?'checked':'' ?>>
                        <span class="sev-btn"><?= alertSvgIcon($cfg['iconType'], 'currentColor', 13) ?> <?= $cfg['label'] ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="f-row">
                <label>Message <span class="req">*</span></label>
                <textarea name="message" rows="4" placeholder="Describe the situation and what evacuees should do…" required></textarea>
            </div>
            <div class="m-foot">
                <button type="button" class="btn-mcancel" data-close="createAlertModal">Cancel</button>
                <button type="submit" class="btn-broadcast">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Broadcast Alert
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="m-overlay" id="alertDetailModal" style="display:none;" role="dialog" aria-modal="true">
    <div class="m-box">
        <div class="m-head" id="detailHeader">
            <span id="detailIcon" style="font-size:22px;display:flex;align-items:center;"></span>
            <span id="detailTitle" style="flex:1;margin-left:10px;font-weight:700;"></span>
            <button class="m-close" data-close="alertDetailModal" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="padding:20px;">
            <div id="detailBadges" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;"></div>
            <p id="detailMessage" style="line-height:1.7;color:#c7d5e8;font-size:15px;margin:0;"></p>
            <?php if ($isAdmin): ?>
            <form method="POST" action="<?= BASE_URL ?>index.php?page=alerts" id="deleteAlertForm" style="margin-top:18px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken,ENT_QUOTES,'UTF-8') ?>">
                <input type="hidden" name="action" value="delete_alert">
                <input type="hidden" name="alert_id" id="deleteAlertId">
                <button type="submit" class="btn-del-alert" onclick="return confirm('Delete this alert? This cannot be undone.')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    Delete Alert
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>index.php?page=alerts" id="markReadForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken,ENT_QUOTES,'UTF-8') ?>">
    <input type="hidden" name="action" value="mark_read">
    <input type="hidden" name="alert_id" id="markReadId">
</form>


