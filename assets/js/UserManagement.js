function toggleModal(show) {
    const modal = document.getElementById('modalOverlay');
    if (!modal) return;

    modal.classList.toggle('open', show);
}

window.addEventListener('click', (event) => {
    const modal = document.getElementById('modalOverlay');
    if (event.target === modal) toggleModal(false);
});
