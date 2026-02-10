
document.addEventListener('DOMContentLoaded', () => {

    /* ── Navbar scroll effect ─────────────────── */
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 48);
        }, { passive: true });
    }

    /* ── Mobile hamburger ─────────────────────── */
    const hamburger = document.getElementById('navHamburger');
    const mobile    = document.getElementById('navMobile');
    if (hamburger && mobile) {
        hamburger.addEventListener('click', () => mobile.classList.toggle('open'));
        // Close when a link inside is clicked
        mobile.querySelectorAll('a').forEach(a =>
            a.addEventListener('click', () => mobile.classList.remove('open'))
        );
    }

    /* ── Smooth scroll for hash links ─────────── */
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.getElementById(a.getAttribute('href').slice(1));
            if (!target) return;
            e.preventDefault();
            const offset = navbar ? navbar.offsetHeight : 0;
            window.scrollTo({ top: target.getBoundingClientRect().top + scrollY - offset, behavior: 'smooth' });
        });
    });

    /* ── Reveal on scroll (IntersectionObserver) ─ */
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -32px 0px' });

    document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
});