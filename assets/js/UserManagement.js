/**
 * SafeHaven - User Management JS
 * Note: All interactive logic is embedded directly in the user-management.php view
 * for better MVC cohesion with the DB-backed AJAX endpoints.
 * This file is kept for compatibility with the asset loader.
 */
// Intentionally empty - see views/pages/user-management.php for the implementation.

(function(){
    var BASE = window.SAFEHAVEN_BASE || '';

    // ── Helpers ──────────────────────────────────────────────────────────────
    function flash(msg, isError) {
        var el = document.getElementById('umFlash');
        el.textContent = msg;
        el.className   = 'um-flash ' + (isError ? 'um-flash-error' : 'um-flash-success');
        el.style.display = 'flex';
        el.scrollIntoView({behavior:'smooth',block:'center'});
        setTimeout(function(){ el.style.display='none'; }, 4000);
    }
    function initials(name) {
        var parts = (name||'').trim().split(' ');
        return parts.length >= 2
            ? (parts[0][0]||'') + (parts[1][0]||'')
            : (name||'').substring(0,2).toUpperCase();
    }
    function openModal(id) { document.getElementById(id).classList.add('sh-open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('sh-open'); }

    function apiPost(page, data, cb) {
        fetch(BASE + 'index.php?page=' + page, {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify(data),
            credentials:'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(cb)
        .catch(function(){ flash('Network error – try again.', true); });
    }

    // ── Open Add modal ───────────────────────────────────────────────────────
    document.getElementById('openModalBtn').addEventListener('click', function(){
        document.getElementById('modalTitle').textContent = 'Add New User';
        document.getElementById('editUserId').value = '';
        document.getElementById('fieldName').value    = '';
        document.getElementById('fieldEmail').value   = '';
        document.getElementById('fieldPhone').value   = '';
        document.getElementById('fieldAddress').value = '';
        document.getElementById('fieldRole').value    = 'evacuee';
        document.getElementById('fieldPassword').value= '';
        document.getElementById('pwdNote').textContent = '(required for new users)';
        document.getElementById('modalError').style.display = 'none';
        openModal('modalOverlay');
    });

    // ── Close modals ─────────────────────────────────────────────────────────
    ['closeModalBtn','cancelModalBtn'].forEach(function(id){
        document.getElementById(id).addEventListener('click', function(){ closeModal('modalOverlay'); });
    });
    document.getElementById('cancelDeleteBtn').addEventListener('click', function(){ closeModal('deleteModal'); });
    document.querySelectorAll('.sh-modal-overlay').forEach(function(m){
        m.addEventListener('click', function(e){ if(e.target===m) m.classList.remove('sh-open'); });
    });

    // ── Save user (add or edit) ───────────────────────────────────────────────
    document.getElementById('saveUserBtn').addEventListener('click', function(){
        var editId   = document.getElementById('editUserId').value;
        var name     = document.getElementById('fieldName').value.trim();
        var email    = document.getElementById('fieldEmail').value.trim();
        var phone    = document.getElementById('fieldPhone').value.trim();
        var address  = document.getElementById('fieldAddress').value.trim();
        var role     = document.getElementById('fieldRole').value;
        var password = document.getElementById('fieldPassword').value;
        var errEl    = document.getElementById('modalError');

        errEl.style.display = 'none';
        if (!name || !email) {
            errEl.textContent = 'Name and email are required.';
            errEl.style.display = 'block';
            return;
        }
        if (!editId && !password) {
            errEl.textContent = 'Password is required for new users.';
            errEl.style.display = 'block';
            return;
        }

        var payload = {full_name:name,email:email,phone_number:phone||'N/A',address:address||'N/A',role:role};
        if (editId) payload.id = parseInt(editId);
        if (password) payload.password = password;

        var route = editId ? 'user-edit' : 'user-add';
        var btn   = document.getElementById('saveUserBtn');
        btn.disabled = true; btn.textContent = '…';

        apiPost(route, payload, function(d){
            btn.disabled = false; btn.textContent = 'Save User';
            if (!d.success) {
                errEl.textContent    = d.message;
                errEl.style.display = 'block';
                return;
            }
            closeModal('modalOverlay');
            flash(d.message, false);
            var u = d.user;
            if (editId) {
                // Update row in-place
                var row = document.getElementById('user-row-' + u.id);
                if (row) {
                    row.querySelector('span').textContent = u.full_name;
                    var badge = row.querySelectorAll('td')[2].querySelector('.badge');
                    badge.textContent = u.role.charAt(0).toUpperCase()+u.role.slice(1);
                    badge.className   = 'badge badge-' + (u.role==='admin'?'red':'blue');
                    row.querySelectorAll('td')[1].textContent = u.email;
                    row.querySelectorAll('td')[3].textContent = u.phone_number||'N/A';
                    row.querySelector('.um-avatar').textContent = initials(u.full_name).toUpperCase();
                }
            } else {
                // Add new row
                var tbody = document.getElementById('usersTableBody');
                var tr    = document.createElement('tr');
                tr.id     = 'user-row-' + u.id;
                tr.innerHTML =
                    '<td><div style="display:flex;align-items:center;gap:10px;">' +
                        '<div class="um-avatar">' + initials(u.full_name).toUpperCase() + '</div>' +
                        '<span>' + u.full_name + '</span></div></td>' +
                    '<td>' + u.email + '</td>' +
                    '<td><span class="badge badge-' + (u.role==='admin'?'red':'blue') + '">' + u.role.charAt(0).toUpperCase()+u.role.slice(1) + '</span></td>' +
                    '<td>' + (u.phone_number||'N/A') + '</td>' +
                    '<td>' + new Date().toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'}) + '</td>' +
                    '<td><button class="btn-row-edit" data-id="'+u.id+'" title="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
                        '<button class="btn-row-delete" data-id="'+u.id+'" title="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg></button></td>';
                tbody.appendChild(tr);
                bindRowButtons(tr);
                // Update stats
                var total = parseInt(document.getElementById('totalCount').textContent)||0;
                document.getElementById('totalCount').textContent = total + 1;
            }
        });
    });

    // ── Edit and Delete row buttons ───────────────────────────────────────────
    function bindRowButtons(context) {
        context.querySelectorAll('.btn-row-edit').forEach(function(btn){
            btn.addEventListener('click', function(){
                var uid = parseInt(btn.dataset.id);
                // Fetch user data
                fetch(BASE + 'index.php?page=user-get&id=' + uid, {credentials:'same-origin'})
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (!d.success) { flash(d.message, true); return; }
                    var u = d.user;
                    document.getElementById('modalTitle').textContent = 'Edit User';
                    document.getElementById('editUserId').value   = u.id;
                    document.getElementById('fieldName').value    = u.full_name;
                    document.getElementById('fieldEmail').value   = u.email;
                    document.getElementById('fieldPhone').value   = u.phone_number||'';
                    document.getElementById('fieldAddress').value = u.address||'';
                    document.getElementById('fieldRole').value    = u.role;
                    document.getElementById('fieldPassword').value= '';
                    document.getElementById('pwdNote').textContent = '(leave blank to keep current)';
                    document.getElementById('modalError').style.display = 'none';
                    openModal('modalOverlay');
                })
                .catch(function(){ flash('Could not load user data.', true); });
            });
        });

        context.querySelectorAll('.btn-row-delete').forEach(function(btn){
            btn.addEventListener('click', function(){
                document.getElementById('deleteUserId').value = btn.dataset.id;
                openModal('deleteModal');
            });
        });
    }

    // Bind existing rows
    bindRowButtons(document.getElementById('usersTable'));

    // ── Confirm delete ────────────────────────────────────────────────────────
    document.getElementById('confirmDeleteBtn').addEventListener('click', function(){
        var uid = parseInt(document.getElementById('deleteUserId').value);
        if (!uid) return;
        var btn = this;
        btn.disabled = true; btn.textContent = '…';

        apiPost('user-delete', {id:uid}, function(d){
            btn.disabled = false; btn.textContent = 'Yes, Delete';
            closeModal('deleteModal');
            if (!d.success) { flash(d.message, true); return; }
            var row = document.getElementById('user-row-' + uid);
            if (row) {
                row.style.transition = 'opacity .3s';
                row.style.opacity    = '0';
                setTimeout(function(){ row.remove(); }, 300);
            }
            flash('User deleted.', false);
            var total = parseInt(document.getElementById('totalCount').textContent)||0;
            if (total > 0) document.getElementById('totalCount').textContent = total - 1;
        });
    });

})();