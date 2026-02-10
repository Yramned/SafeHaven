// Approve Request
function approveRequest(requestId) {

    if (!confirm('Approve this request?')) {
        return;
    }

    const formData = new FormData();
    formData.append('request_id', requestId);

    fetch('capacity.php?action=approve', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(() => {
        showToast('Failed to approve request', 'error');
    });
}


// Deny Request
function denyRequest(requestId) {

    if (!confirm('Deny this request?')) {
        return;
    }

    const formData = new FormData();
    formData.append('request_id', requestId);

    fetch('capacity.php?action=deny', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(() => {
        showToast('Failed to deny request', 'error');
    });
}


// Toast Notification
function showToast(message, type = 'success') {

    const toast = document.getElementById('toast');
    const messageElement = document.getElementById('toastMessage');

    messageElement.textContent = message;
    toast.className = `toast show ${type}`;

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
