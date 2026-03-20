// Display current date and time
function updateTime() {
    const now = new Date();
    document.getElementById("lastUpdate").textContent =
        now.toLocaleString();
}

updateTime();
setInterval(updateTime, 60000);
// Update last-updated timestamp
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('lastUpdate');
    if (el) el.textContent = new Date().toLocaleString();
});
