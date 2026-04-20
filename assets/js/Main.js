/**
 * SafeHaven – Main.js
 * Public pages: navbar scroll, hamburger, smooth scroll, reveal animations.
 */

document.addEventListener('DOMContentLoaded', () => {

    /* ── Navbar scroll effect ──────────────────────────── */
    var navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            navbar.classList.toggle('scrolled', window.scrollY > 48);
        }, { passive: true });
    }

    /* ── Mobile hamburger ──────────────────────────────── */
    var hamburger = document.getElementById('navHamburger');
    var mobile    = document.getElementById('navMobile');
    if (hamburger && mobile) {
        hamburger.addEventListener('click', function() {
            var isOpen = mobile.classList.toggle('open');
            hamburger.classList.toggle('open', isOpen);
            hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Close when any link inside is clicked
        mobile.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', function() {
                mobile.classList.remove('open');
                hamburger.classList.remove('open');
                hamburger.setAttribute('aria-expanded', 'false');
            });
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!navbar.contains(e.target)) {
                mobile.classList.remove('open');
                hamburger.classList.remove('open');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ── Smooth scroll for hash links ──────────────────── */
    document.querySelectorAll('a[href^="#"]').forEach(function(a) {
        a.addEventListener('click', function(e) {
            var id     = a.getAttribute('href').slice(1);
            var target = document.getElementById(id);
            if (!target) return;
            e.preventDefault();
            var offset = navbar ? navbar.offsetHeight : 0;
            window.scrollTo({
                top: target.getBoundingClientRect().top + window.scrollY - offset,
                behavior: 'smooth'
            });
        });
    });

    /* ── Reveal on scroll (IntersectionObserver) ─────────── */
    if ('IntersectionObserver' in window) {
        var revealObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -28px 0px' });

        document.querySelectorAll('.reveal').forEach(function(el) {
            revealObserver.observe(el);
        });
    } else {
        // Fallback: show all reveal elements
        document.querySelectorAll('.reveal').forEach(function(el) {
            el.classList.add('visible');
        });
    }

});
