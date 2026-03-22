/**
 * SafeHaven – Profile JS
 * Handles edit toggle + family SMS number management
 */

// ── State ────────────────────────────────────────────────────────────────────
let familyNumbers = [];

document.addEventListener('DOMContentLoaded', () => {
    // Read initial family numbers from the hidden input
    try {
        const raw = document.getElementById('family_numbers_input')?.value || '[]';
        familyNumbers = JSON.parse(raw);
        if (!Array.isArray(familyNumbers)) familyNumbers = [];
    } catch (e) { familyNumbers = []; }
});

// ── Edit toggle ──────────────────────────────────────────────────────────────
function toggleEdit() {
    const inputs   = document.querySelectorAll('#profileForm input:not([type="hidden"])');
    const actions  = document.getElementById('formActions');
    const editBtn  = document.getElementById('editBtn');
    const addBtn   = document.getElementById('addFamilyBtn');
    const emptyMsg = document.getElementById('familyEmptyState');

    inputs.forEach(i => i.removeAttribute('readonly'));
    actions.style.display  = 'flex';
    editBtn.style.display  = 'none';
    if (addBtn)   addBtn.style.display   = 'flex';
    if (emptyMsg) emptyMsg.style.display = 'none';

    // Show remove buttons on existing rows
    document.querySelectorAll('.btn-remove-family').forEach(b => b.style.display = 'flex');
    document.querySelectorAll('.family-number-input').forEach(i => i.removeAttribute('readonly'));
}

function cancelEdit() {
    location.reload();
}

// ── Family number management ─────────────────────────────────────────────────
function addFamilyNumber() {
    const list = document.getElementById('familyNumbersList');
    const idx  = familyNumbers.length;
    familyNumbers.push('');

    const row = document.createElement('div');
    row.className    = 'family-number-row';
    row.dataset.index = idx;
    row.innerHTML = `
        <div class="family-number-icon">👤</div>
        <input type="tel" class="family-number-input"
               placeholder="e.g. 09171234567"
               data-index="${idx}"
               oninput="updateFamilyNumber(${idx}, this.value)">
        <button type="button" class="btn-remove-family" onclick="removeFamilyNumber(${idx})" style="display:flex;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>`;
    list.appendChild(row);
    row.querySelector('input').focus();
    syncHiddenInput();
}

function updateFamilyNumber(idx, value) {
    familyNumbers[idx] = value.trim();
    syncHiddenInput();
}

function removeFamilyNumber(idx) {
    // Mark as empty; we filter on save
    familyNumbers[idx] = '';
    const row = document.querySelector(`.family-number-row[data-index="${idx}"]`);
    if (row) {
        row.style.opacity   = '0';
        row.style.transform = 'translateX(-10px)';
        row.style.transition = 'all .25s ease';
        setTimeout(() => row.remove(), 260);
    }
    syncHiddenInput();
}

function syncHiddenInput() {
    const cleaned = familyNumbers.filter(n => n && n.trim() !== '');
    document.getElementById('family_numbers_input').value = JSON.stringify(cleaned);
}

// Sync any existing inputs that were typed into
document.addEventListener('input', e => {
    if (e.target.classList.contains('family-number-input')) {
        const idx = parseInt(e.target.dataset.index, 10);
        updateFamilyNumber(idx, e.target.value);
    }
});
