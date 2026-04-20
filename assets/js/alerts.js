/**
 * SafeHaven – Alerts Page JS
 * Works with index.php?page= routing (no .htaccess required).
 * Handles: modals, alert detail view, sensor updates, mark-read, severity styling.
 */

(function () {
    var BASE = window.SAFEHAVEN_BASE || '';

    // ── Helpers ────────────────────────────────────────────────────────────
    function openM(id)  { var m = document.getElementById(id); if (m) m.style.display = 'flex'; }
    function closeM(id) { var m = document.getElementById(id); if (m) m.style.display = 'none'; }

    function timeAgo(dt) {
        var d = Math.floor((Date.now() - new Date(dt).getTime()) / 1000);
        if (d < 60)    return d + 's ago';
        if (d < 3600)  return Math.floor(d / 60) + 'm ago';
        if (d < 86400) return Math.floor(d / 3600) + 'h ago';
        return Math.floor(d / 86400) + 'd ago';
    }

    var SEV_COLORS = {
        critical:   '#ff4d4d',
        evacuation: '#ff7070',
        warning:    '#ffcc44',
        info:       '#5eb0ff',
    };
    var SEV_BG = {
        critical:   'rgba(255,77,77,0.14)',
        evacuation: 'rgba(139,0,0,0.14)',
        warning:    'rgba(255,204,68,0.14)',
        info:       'rgba(94,176,255,0.14)',
    };
    var SEV_LABELS = {
        critical: 'Critical', evacuation: 'Evacuation', warning: 'Warning', info: 'Info'
    };

    // ── Auto-hide flash ────────────────────────────────────────────────────
    var flash = document.querySelector('.sh-flash');
    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity 0.5s';
            flash.style.opacity = '0';
            setTimeout(function () { flash.style.display = 'none'; }, 500);
        }, 4000);
    }

    // ── Severity radio styling (create alert form) ─────────────────────────
    document.querySelectorAll('.sev-opt input[type=radio]').forEach(function (r) {
        var btn = r.nextElementSibling;
        var color = r.dataset.color || '#888';
        var on  = function () { btn.style.cssText = 'background:' + color + '22;border-color:' + color + ';color:#fff;'; };
        var off = function () { btn.style.cssText = ''; };
        r.checked ? on() : off();
        r.addEventListener('change', function () {
            document.querySelectorAll('.sev-opt input').forEach(function (x) { x.nextElementSibling.style.cssText = ''; });
            on();
        });
    });

    // ── Modal close (data-close attribute) ────────────────────────────────
    document.querySelectorAll('[data-close]').forEach(function (b) {
        b.addEventListener('click', function () { closeM(b.dataset.close); });
    });
    document.querySelectorAll('.m-overlay').forEach(function (o) {
        o.addEventListener('click', function (e) { if (e.target === o) o.style.display = 'none'; });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.m-overlay').forEach(function (m) { m.style.display = 'none'; });
        }
    });

    // ── Open Create Alert modal ────────────────────────────────────────────
    var openCreate = document.getElementById('openCreateModal');
    if (openCreate) {
        openCreate.addEventListener('click', function () { openM('createAlertModal'); });
    }

    // ── Open Sensor Editor modal ───────────────────────────────────────────
    var openSensor = document.getElementById('openSensorModal');
    if (openSensor) {
        openSensor.addEventListener('click', function () { openM('sensorEditorModal'); });
    }

    // ── Refresh sensor cards in DOM after save/reset ────────────────────────
    function refreshSensorCards(sensors) {
        var sc = { ok: 'status-ok', warn: 'status-warn', critical: 'status-critical' };
        var bc = { ok: 'badge-green', warn: 'badge-orange', critical: 'badge-red' };
        var bl = { ok: 'Normal', warn: 'Warning', critical: 'Critical' };
        sensors.forEach(function (s) {
            var card = document.querySelector('.sensor-card[data-key="' + s.sensor_key + '"]');
            if (!card) return;
            card.className = 'sensor-card ' + (sc[s.status] || 'status-ok');
            var badge = card.querySelector('.badge');
            if (badge) {
                badge.className = 'badge ' + (bc[s.status] || 'badge-green');
                badge.textContent = bl[s.status] || 'Normal';
            }
            var val = card.querySelector('.sc-val');
            if (val) val.innerHTML = (s.value || '—') + '<span class="sc-unit">' + (s.unit || '') + '</span>';
            var trend = card.querySelector('.sc-trend');
            if (trend) trend.textContent = s.trend || '';
        });
    }

    function updateEditorFields(sensors) {
        sensors.forEach(function (s) {
            var k = s.sensor_key;
            var set = function (id, v) { var el = document.getElementById(id); if (el) el.value = v || ''; };
            set('sr-value-' + k, s.value);
            set('sr-unit-' + k, s.unit);
            set('sr-trend-' + k, s.trend);
            set('sr-status-' + k, s.status);
        });
    }

    // ── Save single sensor (called from onclick in PHP) ────────────────────
    window.saveSensor = function (key) {
        var g = function (id) { var el = document.getElementById(id); return el ? el.value : ''; };
        var body = {
            sensor_key: key,
            value:  g('sr-value-'  + key),
            unit:   g('sr-unit-'   + key),
            trend:  g('sr-trend-'  + key),
            status: g('sr-status-' + key),
        };
        var fb = document.getElementById('srf-' + key);
        if (fb) { fb.textContent = 'Saving…'; fb.className = 'se-feedback'; }

        fetch(BASE + 'index.php?page=sensor-update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
            credentials: 'same-origin',
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (fb) {
                fb.textContent = data.success ? '✅ Saved!' : '❌ ' + (data.message || 'Failed');
                fb.className = 'se-feedback ' + (data.success ? 'ok' : 'err');
                setTimeout(function () { fb.textContent = ''; }, 3000);
            }
            if (data.success && data.sensors) refreshSensorCards(data.sensors);
        })
        .catch(function () {
            if (fb) { fb.textContent = '❌ Network error'; fb.className = 'se-feedback err'; }
        });
    };

    // ── Reset all sensors (called from onclick in PHP) ─────────────────────
    window.resetSensors = function () {
        if (!confirm('Reset all sensor values to factory defaults?')) return;
        fetch(BASE + 'index.php?page=sensor-reset', {
            method: 'POST',
            credentials: 'same-origin',
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success && data.sensors) {
                refreshSensorCards(data.sensors);
                updateEditorFields(data.sensors);
                showToast('Sensors reset to defaults.', 'success');
            } else {
                showToast('Reset failed: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(function () { showToast('Reset failed (network error).', 'error'); });
    };

    // ── Alert detail modal ─────────────────────────────────────────────────
    var dHeader = document.getElementById('detailHeader');
    var dIcon   = document.getElementById('detailIcon');
    var dTitle  = document.getElementById('detailTitle');
    var dBadges = document.getElementById('detailBadges');
    var dMsg    = document.getElementById('detailMessage');
    var delId   = document.getElementById('deleteAlertId');
    var mrForm  = document.getElementById('markReadForm');
    var mrId    = document.getElementById('markReadId');
    var urCnt   = document.getElementById('countUnread');

    // viewAlert called from onclick="viewAlert(id)" in PHP
    window.viewAlert = function (id) {
        var item = document.querySelector('.a-item[data-id="' + id + '"]');
        if (!item) return;
        var d = item.dataset;
        var sev = d.severity || 'info';
        var color  = SEV_COLORS[sev]  || '#5eb0ff';
        var bg     = SEV_BG[sev]      || 'rgba(94,176,255,0.14)';
        var label  = SEV_LABELS[sev]  || 'Info';
        var timeStr = d.created ? timeAgo(d.created) : '';

        if (dHeader) {
            dHeader.style.background = bg;
            dHeader.style.borderBottom = '2px solid ' + color + '55';
        }
        if (dIcon) dIcon.innerHTML = '';
        if (dTitle) { dTitle.textContent = d.title || ''; dTitle.style.color = color; }
        if (dMsg)   dMsg.textContent = d.message || '';
        if (dBadges) {
            var html = '<span class="badge" style="background:' + color + '22;color:' + color + ';border:1px solid ' + color + '55;">' + label + '</span>';
            if (d.location) html += '<span class="badge" style="background:rgba(255,255,255,.08);color:#9bb0d0;">📍 ' + d.location + '</span>';
            if (timeStr)    html += '<span class="badge" style="background:rgba(255,255,255,.08);color:#9bb0d0;">🕐 ' + timeStr + '</span>';
            dBadges.innerHTML = html;
        }
        if (delId) delId.value = id;
        openM('alertDetailModal');

        // Mark as read via fetch if unread
        if (item.classList.contains('a-unread') && mrForm && mrId) {
            mrId.value = id;
            fetch(mrForm.action, {
                method: 'POST',
                body: new FormData(mrForm),
                credentials: 'same-origin',
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    item.classList.replace('a-unread', 'a-read');
                    item.style.opacity = '.65';
                    var dot = item.querySelector('.a-dot');
                    if (dot) dot.remove();
                    if (urCnt) {
                        var v = parseInt(urCnt.textContent, 10);
                        if (v > 0) urCnt.textContent = v - 1;
                    }
                }
            })
            .catch(function () {});
        }
    };

    // Also wire up click on .a-item rows (fallback for items not using onclick)
    document.querySelectorAll('.a-item').forEach(function (item) {
        if (!item.getAttribute('onclick')) {
            item.addEventListener('click', function () {
                window.viewAlert(item.dataset.id);
            });
        }
        var btn = item.querySelector('.a-view-btn');
        if (btn && !btn.getAttribute('onclick')) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                window.viewAlert(item.dataset.id);
            });
        }
    });

    // ── Toast notification (replaces alert()) ─────────────────────────────
    function showToast(msg, type) {
        var existing = document.getElementById('sh-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        toast.id = 'sh-toast';
        var bg   = type === 'success' ? 'rgba(39,174,96,0.92)' : 'rgba(231,76,60,0.92)';
        toast.style.cssText = [
            'position:fixed','bottom:24px','right:24px','z-index:9999',
            'background:' + bg,'color:#fff','padding:12px 20px',
            'border-radius:10px','font-size:0.88rem','font-weight:500',
            'box-shadow:0 4px 16px rgba(0,0,0,0.3)','max-width:320px',
            'transition:opacity 0.3s','pointer-events:none'
        ].join(';');
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(function() { if (toast.parentNode) toast.remove(); }, 400);
        }, 3200);
    }

})();
