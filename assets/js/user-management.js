/**
 * SafeHaven - User Management Page JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('User Management page loaded');
    
    // User search functionality
    const searchBox = document.querySelector('.user-search-box');
    if (searchBox) {
        searchBox.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Add any user management specific JavaScript here
});
