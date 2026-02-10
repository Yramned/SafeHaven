/**
 * SafeHaven – Profile.js
 * Simplified Profile Management JavaScript
 */

let isEditing = false;
let originalValues = {};

/**
 * Store original form values
 */
function storeOriginalValues() {
    originalValues = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value
    };
}

/**
 * Toggle edit mode
 */
function toggleEdit() {
    console.log('Toggle edit clicked, isEditing:', isEditing);
    
    if (!isEditing) {
        // Store current values before editing
        storeOriginalValues();
        
        // Enable editing
        const fields = ['name', 'email', 'phone', 'address', 'new_password'];
        fields.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.removeAttribute('readonly');
                input.classList.add('editing');
                console.log('Enabled field:', id);
            } else {
                console.error('Field not found:', id);
            }
        });
        
        // Show action buttons
        document.getElementById('formActions').style.display = 'flex';
        document.getElementById('editBtn').style.display = 'none';
        
        isEditing = true;
        console.log('Edit mode enabled');
    }
}

/**
 * Cancel editing and restore original values
 */
function cancelEdit() {
    console.log('Cancel edit clicked');
    
    // Restore original values
    document.getElementById('name').value = originalValues.name;
    document.getElementById('email').value = originalValues.email;
    document.getElementById('phone').value = originalValues.phone;
    document.getElementById('address').value = originalValues.address;
    document.getElementById('new_password').value = '';
    
    // Disable editing
    const fields = ['name', 'email', 'phone', 'address', 'new_password'];
    fields.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.setAttribute('readonly', 'readonly');
            input.classList.remove('editing');
        }
    });
    
    // Hide action buttons
    document.getElementById('formActions').style.display = 'none';
    document.getElementById('editBtn').style.display = 'flex';
    
    isEditing = false;
    console.log('Edit mode cancelled');
}

/**
 * Auto-hide alert messages after 5 seconds
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile.js loaded');
    
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Make functions available globally for onclick handlers
window.toggleEdit = toggleEdit;
window.cancelEdit = cancelEdit;