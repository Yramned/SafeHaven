/**
 * SafeHaven – js/auth.js
 * Password visibility toggle, password-length hint, phone mask, submit loading state.
 */
document.addEventListener('DOMContentLoaded', () => {

    /* ── Password eye toggles ───────────────── */
    document.querySelectorAll('.pw-toggle').forEach(btn => {
        const targetId = btn.dataset.target;
        const input    = document.getElementById(targetId);
        if (!input) return;

        btn.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';

            // Swap icon
            btn.innerHTML = isHidden
                ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                     <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                     <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                     <line x1="1" y1="1" x2="23" y2="23"/>
                   </svg>`
                : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                     <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                     <circle cx="12" cy="12" r="3"/>
                   </svg>`;
            input.focus();
        });
    });

    /* ── Live password length hint ──────────── */
    const pwInput = document.getElementById('regPassword');
    const pwHint  = document.getElementById('pwHint');
    if (pwInput && pwHint) {
        pwInput.addEventListener('input', () => {
            const rem = 6 - pwInput.value.length;
            if (pwInput.value.length === 0) {
                pwHint.textContent = 'Minimum 6 characters';
                pwHint.style.color = '';
            } else if (rem > 0) {
                pwHint.textContent = `${rem} more character${rem > 1 ? 's' : ''} needed`;
                pwHint.style.color = 'var(--red)';
            } else {
                pwHint.textContent = '✓ Password length is good';
                pwHint.style.color = 'var(--green)';
            }
        });
    }

    /* ── Phone input – strip non-allowed chars ─ */
    const phoneInput = document.querySelector('input[name="phone_number"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9+\-() ]/g, '');
        });
    }

    /* ── Form loading state on submit ───────── */
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('.btn-auth');
            if (btn) { btn.disabled = true; btn.textContent = 'Processing…'; }
        });
    });

    /* ── Auto-focus first input ─────────────── */
    const first = document.querySelector('.auth-card input');
    if (first) setTimeout(() => first.focus(), 180);
});