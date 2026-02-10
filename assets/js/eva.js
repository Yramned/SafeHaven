// Display current date and time
function updateTime() {
    const now = new Date();
    document.getElementById("lastUpdate").textContent =
        now.toLocaleString();
}

updateTime();
setInterval(updateTime, 60000);