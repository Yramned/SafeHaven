/**
 * SafeHaven - Capacity Management JS
 * Extracted from capacity.php view into proper MVC JS file.
 */
/* ── Utility: POST + handle response ─────────────── */
function capPost(url, data, onOk) {
  fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) })
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.success) { onOk(d); } else { showToast('Error: '+(d.message||'failed'), true); } })
    .catch(function(){ showToast('Network error — try again.', true); });
}

function disableBtns(id, prefixes) {
  prefixes.forEach(function(p){
    var el = document.getElementById(p+id);
    if (el) el.querySelectorAll('button').forEach(function(b){ b.disabled=true; b.style.opacity='0.5'; });
  });
}
function fadeRemove(id, prefixes) {
  prefixes.forEach(function(p){
    var el = document.getElementById(p+id);
    if (!el) return;
    el.style.transition='opacity .35s,transform .35s';
    el.style.opacity='0'; el.style.transform='translateX(16px)';
    setTimeout(function(){ el.remove(); }, 360);
  });
}

/* ── Save center occupancy ───────────────────────── */
function saveOccupancy(cid, maxCap) {
  var input = document.getElementById('occ-input-'+cid);
  var btn   = document.getElementById('occ-btn-'+cid);
  var val   = parseInt(input.value, 10);
  if (isNaN(val)||val<0)   { showToast('Enter a number ≥ 0.', true); return; }
  if (val>maxCap)          { showToast('Cannot exceed max capacity ('+maxCap+').', true); return; }

  btn.disabled=true; btn.textContent='…';
  capPost('index.php?page=capacity-update', {center_id:cid, occupancy:val}, function(d){
    btn.disabled=false; btn.textContent='Save';
    var pct    = maxCap>0 ? Math.min(100,Math.round(val/maxCap*100)) : 0;
    var status = val>=maxCap?'full':(val>=maxCap*0.8?'limited':'accepting');
    var scMap  = {full:'cap-badge-red',limited:'cap-badge-yellow',accepting:'cap-badge-green'};

    var curEl = document.getElementById('occ-cur-'+cid);
    var stEl  = document.getElementById('center-status-'+cid);

    if (curEl) curEl.textContent = val;
    if (stEl)  { stEl.textContent=status.charAt(0).toUpperCase()+status.slice(1); stEl.className='cap-badge '+(scMap[status]||'cap-badge-blue'); }

    showToast('Saved: '+val+' / '+maxCap+' people', false);
  });
}

/* ── Save family count ───────────────────────────── */
function saveFamilyCount(rid) {
  var input = document.getElementById('fam-input-'+rid);
  var btn   = input ? input.nextElementSibling : null;
  var nv    = parseInt(input.value, 10);
  var ov    = parseInt(input.getAttribute('data-orig'), 10);
  if (isNaN(nv)||nv<1) { showToast('Must be at least 1.', true); return; }
  if (nv===ov)          { showToast('No change to save.', false); return; }

  if (btn) { btn.disabled=true; btn.textContent='…'; }
  capPost('index.php?page=capacity-request-family', {request_id:rid, family_members:nv}, function(d){
    if (btn) { btn.disabled=false; btn.textContent='Save'; }
    var disp = document.getElementById('fam-display-'+rid);
    if (disp) disp.textContent = nv;
    input.setAttribute('data-orig', nv);
    var diff = d.diff !== undefined ? d.diff : (nv-ov);
    showToast('Updated '+ov+'→'+nv+' people'+(diff!==0?' (occupancy '+(diff>0?'+':'')+diff+')':''), false);
    setTimeout(function(){ location.reload(); }, 1800);
  });
}

/* ── Approve / Deny ──────────────────────────────── */
function handleRequest(rid, action) {
  disableBtns(rid, ['preq-row-','preq-card-','hreq-row-','hreq-card-']);
  capPost('index.php?page=capacity-request-action', {request_id:rid, action:action}, function(d){
    showToast('Request '+(action==='approved'?'approved':'denied'), false);

    // Update status badge in history table
    var stEl = document.getElementById('hreq-status-'+rid);
    if (stEl) {
      stEl.textContent = action.charAt(0).toUpperCase()+action.slice(1);
      stEl.className   = 'cap-badge '+(action==='approved'?'cap-badge-green':'cap-badge-red');
    }
    // Remove from pending section
    fadeRemove(rid, ['preq-row-','preq-card-']);
    setTimeout(function(){ location.reload(); }, 1500);
  });
}

/* ── Delete ──────────────────────────────────────── */
var _delId = null;
function confirmDelete(rid) {
  _delId = rid;
  document.getElementById('deleteModal').classList.add('sh-open');
}
function closeDeleteModal() {
  _delId = null;
  document.getElementById('deleteModal').classList.remove('sh-open');
}
document.getElementById('doDeleteBtn').addEventListener('click', function(){
  if (!_delId) return;
  var rid = _delId;
  closeDeleteModal();
  capPost('index.php?page=capacity-request-delete', {request_id:rid}, function(d){
    showToast('Request #'+rid+' deleted.', false);
    fadeRemove(rid, ['preq-row-','preq-card-','hreq-row-','hreq-card-']);
    setTimeout(function(){ location.reload(); }, 1400);
  });
});
document.getElementById('deleteModal').addEventListener('click', function(e){ if(e.target===this) closeDeleteModal(); });

/* ── Toast ───────────────────────────────────────── */
function showToast(msg, isErr) {
  var t=document.getElementById('capToast'), m=document.getElementById('capToastMsg');
  if(!t||!m) return;
  m.textContent=msg;
  t.className='sh-toast sh-toast-show'+(isErr?' sh-toast-err':'');
  clearTimeout(t._t);
  t._t=setTimeout(function(){ t.className='sh-toast'; }, 4000);
}

/* ─────────────────────────────────────────────────────
   CENTER MANAGEMENT (Add / Edit / Delete)
─────────────────────────────────────────────────────── */
const BASE = window.SAFEHAVEN_BASE || window.BASE_URL || '';

function openAddCenter() {
    document.getElementById('ac_name').value = '';
    document.getElementById('ac_barangay').value = '';
    document.getElementById('ac_address').value = '';
    document.getElementById('ac_capacity').value = '';
    document.getElementById('ac_contact').value = '';
    document.getElementById('ac_facilities').value = '';
    document.getElementById('addCenterError').style.display = 'none';
    document.getElementById('addCenterModal').classList.add('sh-open');
}
function closeAddCenter() { document.getElementById('addCenterModal').classList.remove('sh-open'); }

async function submitAddCenter() {
    const name     = document.getElementById('ac_name').value.trim();
    const capacity = parseInt(document.getElementById('ac_capacity').value) || 0;
    const errEl    = document.getElementById('addCenterError');

    if (!name || capacity < 1) {
        errEl.textContent = 'Name and capacity (> 0) are required.';
        errEl.style.display = 'block';
        return;
    }
    errEl.style.display = 'none';

    const payload = {
        name,
        barangay:       document.getElementById('ac_barangay').value.trim(),
        address:        document.getElementById('ac_address').value.trim(),
        capacity,
        contact_number: document.getElementById('ac_contact').value.trim(),
        facilities:     document.getElementById('ac_facilities').value.trim(),
    };

    try {
        const res  = await fetch('index.php?page=center-add', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data = await res.json();

        if (data.success) {
            showToast('Center added successfully!');
            closeAddCenter();
            setTimeout(() => location.reload(), 800);
        } else {
            errEl.textContent = data.message || 'Failed to add center.';
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = 'block';
    }
}

let _editCenterId = null;
function openEditCenter(centerId, centerData) {
    _editCenterId = centerId;
    const d = typeof centerData === 'string' ? JSON.parse(centerData) : centerData;
    document.getElementById('ec_center_id').value = centerId;
    document.getElementById('ec_name').value       = d.name           || '';
    document.getElementById('ec_barangay').value   = d.barangay       || '';
    document.getElementById('ec_address').value    = d.address        || '';
    document.getElementById('ec_capacity').value   = d.capacity       || '';
    document.getElementById('ec_contact').value    = d.contact_number || '';
    document.getElementById('ec_facilities').value = d.facilities     || '';
    document.getElementById('editCenterError').style.display = 'none';
    document.getElementById('editCenterModal').classList.add('sh-open');
}
function closeEditCenter() { document.getElementById('editCenterModal').classList.remove('sh-open'); }

async function submitEditCenter() {
    const centerId = _editCenterId;
    const errEl    = document.getElementById('editCenterError');

    const payload = {
        center_id:      centerId,
        name:           document.getElementById('ec_name').value.trim(),
        barangay:       document.getElementById('ec_barangay').value.trim(),
        address:        document.getElementById('ec_address').value.trim(),
        capacity:       parseInt(document.getElementById('ec_capacity').value) || 0,
        contact_number: document.getElementById('ec_contact').value.trim(),
        facilities:     document.getElementById('ec_facilities').value.trim(),
    };

    if (!payload.name || payload.capacity < 1) {
        errEl.textContent = 'Name and capacity are required.';
        errEl.style.display = 'block';
        return;
    }

    try {
        const res  = await fetch('index.php?page=center-edit', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data = await res.json();

        if (data.success) {
            showToast('Center updated!');
            closeEditCenter();
            setTimeout(() => location.reload(), 800);
        } else {
            errEl.textContent = data.message || 'Failed to update.';
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = 'block';
    }
}

let _deleteCenterId = null;
function confirmDeleteCenter(centerId, name) {
    _deleteCenterId = centerId;
    document.getElementById('deleteCenterMsg').textContent = `Permanently delete "${name}"? This cannot be undone.`;
    document.getElementById('deleteCenterModal').classList.add('sh-open');
    document.getElementById('doDeleteCenterBtn').onclick = async () => {
        try {
            const res  = await fetch('index.php?page=center-delete', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({center_id:centerId}) });
            const data = await res.json();
            if (data.success) {
                showToast('Center deleted.');
                closeDeleteCenterModal();
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.message || 'Delete failed.', true);
                closeDeleteCenterModal();
            }
        } catch (e) {
            showToast('Network error. Please try again.', true);
            closeDeleteCenterModal();
        }
    };
}
function closeDeleteCenterModal() { document.getElementById('deleteCenterModal').classList.remove('sh-open'); }

/* ── Edit Beds Modal ─────────────────────────────── */
function openBedsModal(centerId, name, currentOcc, maxCap) {
    document.getElementById('bm_center_id').value = centerId;
    document.getElementById('bedsModalName').textContent = name;
    document.getElementById('bm_occupancy').value = currentOcc;
    document.getElementById('bm_capacity').value  = maxCap;
    updateBedsPreview();
    document.getElementById('bedsModalError').style.display = 'none';
    document.getElementById('bedsModal').classList.add('sh-open');
}
function closeBedsModal() { document.getElementById('bedsModal').classList.remove('sh-open'); }

function updateBedsPreview() {
    var occ  = parseInt(document.getElementById('bm_occupancy').value) || 0;
    var cap  = parseInt(document.getElementById('bm_capacity').value)  || 0;
    var free = Math.max(0, cap - occ);
    document.getElementById('bmPreviewFree').textContent  = free;
    document.getElementById('bmPreviewTotal').textContent = cap;
}

document.addEventListener('DOMContentLoaded', function() {
    var occEl = document.getElementById('bm_occupancy');
    var capEl = document.getElementById('bm_capacity');
    if (occEl) occEl.addEventListener('input', updateBedsPreview);
    if (capEl) capEl.addEventListener('input', updateBedsPreview);
});

async function submitBeds() {
    var centerId = parseInt(document.getElementById('bm_center_id').value);
    var newOcc   = parseInt(document.getElementById('bm_occupancy').value);
    var newCap   = parseInt(document.getElementById('bm_capacity').value);
    var errBox   = document.getElementById('bedsModalError');
    errBox.style.display = 'none';

    if (!centerId || isNaN(newOcc) || isNaN(newCap) || newCap < 1) {
        errBox.textContent = 'Please enter valid values.';
        errBox.style.display = 'block';
        return;
    }
    if (newOcc > newCap) {
        errBox.textContent = 'Occupancy cannot exceed total bed capacity.';
        errBox.style.display = 'block';
        return;
    }

    var BASE = window.SAFEHAVEN_BASE || '';
    try {
        var editRes  = await fetch(BASE + 'index.php?page=center-edit', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ center_id: centerId, capacity: newCap }),
            credentials: 'same-origin'
        });
        var editData = await editRes.json();
        if (!editData.success) {
            errBox.textContent = 'Capacity update failed: ' + (editData.message || 'Error');
            errBox.style.display = 'block';
            return;
        }

        var occRes  = await fetch(BASE + 'index.php?page=capacity-update', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ center_id: centerId, occupancy: newOcc }),
            credentials: 'same-origin'
        });
        var occData = await occRes.json();
        if (!occData.success) {
            errBox.textContent = 'Occupancy update failed: ' + (occData.message || 'Error');
            errBox.style.display = 'block';
            return;
        }

        var card = document.getElementById('center-row-' + centerId);
        if (card) {
            var free   = Math.max(0, newCap - newOcc);
            var occPct = newCap > 0 ? Math.round((newOcc / newCap) * 100) : 0;
            var curEl  = document.getElementById('occ-cur-' + centerId);
            if (curEl) curEl.textContent = newOcc;
            var bigCapEl = card.querySelector('.ec-big-cap');
            if (bigCapEl) bigCapEl.textContent = newCap;
            var availEl = card.querySelector('.ec-big-avail');
            if (availEl) availEl.textContent = free;
            var occInput = document.getElementById('occ-input-' + centerId);
            if (occInput) { occInput.value = newOcc; occInput.max = newCap; }
            var st     = occPct >= 100 ? 'full' : (occPct >= 75 ? 'limited' : 'accepting');
            var stCls  = { accepting: 'cap-badge-green', limited: 'cap-badge-yellow', full: 'cap-badge-red' }[st] || 'cap-badge-blue';
            var stBadge = document.getElementById('center-status-' + centerId);
            if (stBadge) { stBadge.textContent = st.charAt(0).toUpperCase() + st.slice(1); stBadge.className = 'cap-badge ' + stCls; }
        }
        closeBedsModal();
        showToast('Beds updated successfully!');
    } catch (e) {
        errBox.textContent = 'Network error. Please try again.';
        errBox.style.display = 'block';
    }
}
