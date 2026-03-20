/**
 * SafeHaven - Dashboard Header JS
 * Mobile nav toggle for the dashboard header.
 */

function toggleHeaderMenu() {
    document.getElementById('headerNav').classList.toggle('open');
}

document.addEventListener('click', function (e) {
    var nav    = document.getElementById('headerNav');
    var toggle = document.querySelector('.mobile-toggle');
    if (nav && toggle && !nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.classList.remove('open');
    }
});
