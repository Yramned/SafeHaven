/**
 * SafeHaven - Evacuation Result JS
 * Extracted from result.php view into proper MVC JS file.
 */
// Read request data from sessionStorage
const requestData = {
    priority: sessionStorage.getItem('evac_priority') || 'Unaccompanied Minor',
    familyCount: parseInt(sessionStorage.getItem('evac_family_count') || '1'),
    specialNeeds: JSON.parse(sessionStorage.getItem('evac_special_needs') || '["Wheelchair"]')
};

// Update family count display
document.addEventListener('DOMContentLoaded', () => {
    const familyEl = document.querySelector('.summary-value[data-family]');
    if (familyEl && requestData.familyCount) {
        const plural = requestData.familyCount === 1 ? 'person' : 'people';
        familyEl.textContent = `${requestData.familyCount} ${plural}`;
    }

    // Update special needs badges
    const needsEl = document.querySelector('.summary-value[data-needs]');
    if (needsEl && requestData.specialNeeds) {
        needsEl.innerHTML = requestData.specialNeeds.length > 0
            ? requestData.specialNeeds.map(n => `<span class="badge badge-special">${n}</span>`).join(' ')
            : '<span class="badge badge-special">None</span>';
    }

    // Update priority display if it exists
    const priorityEl = document.querySelector('.summary-value[data-priority]');
    if (priorityEl && requestData.priority) {
        priorityEl.innerHTML = `<span class="badge badge-special">${requestData.priority}</span>`;
    }
});

function selectCenter(centerName) {
    alert('Selecting ' + centerName + '...');
    setTimeout(() => {
        window.location.href = 'evacuation-request.php';
    }, 500);
}
