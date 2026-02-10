<?php
// Load user data
$usersFile = STORAGE_PATH . 'users.json';
$users = file_exists($usersFile) ? (json_decode(file_get_contents($usersFile), true) ?: []) : [];

$currentUser = null;
$userIndex = null;
foreach ($users as $key => $u) {
    if ($u['id'] == $_SESSION['user_id']) {
        $currentUser = $u;
        $userIndex = $key;
        break;
    }
}

if (!$currentUser) {
    header('Location: index.php?page=dashboard');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $errors = [];
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => strtolower(trim($_POST['email'] ?? '')),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'new_password' => trim($_POST['new_password'] ?? '')
    ];
    
    if (empty($data['name']) || strlen($data['name']) < 2) $errors[] = 'Name must be at least 2 characters';
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($data['phone'])) $errors[] = 'Phone number is required';
    if (empty($data['address'])) $errors[] = 'Address is required';
    if (!empty($data['new_password']) && strlen($data['new_password']) < 6) $errors[] = 'Password must be at least 6 characters';
    
    if (empty($errors)) {
        $users[$userIndex]['full_name'] = $data['name'];
        $users[$userIndex]['email'] = $data['email'];
        $users[$userIndex]['phone_number'] = $data['phone'];
        $users[$userIndex]['address'] = $data['address'];
        
        if (!empty($data['new_password'])) {
            $users[$userIndex]['password'] = password_hash($data['new_password'], PASSWORD_BCRYPT);
        }
        
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['user_email'] = $data['email'];
        $_SESSION['user_phone'] = $data['phone'];
        $_SESSION['user_address'] = $data['address'];
        
        $currentUser = $users[$userIndex];
        $_SESSION['success_message'] = 'Profile updated successfully!';
        header('Location: index.php?page=profile');
        exit;
    } else {
        $_SESSION['error_messages'] = $errors;
    }
}

$successMessage = $_SESSION['success_message'] ?? null;
$errorMessages = $_SESSION['error_messages'] ?? [];
unset($_SESSION['success_message'], $_SESSION['error_messages']);

$user = [
    'id' => $currentUser['id'],
    'name' => $currentUser['full_name'],
    'email' => $currentUser['email'],
    'phone' => $currentUser['phone_number'] ?? '',
    'address' => $currentUser['address'] ?? '',
    'role' => ucfirst($currentUser['role'] ?? 'evacuee'),
    'joined_date' => $currentUser['created_at'] ?? date('Y-m-d')
];

$pageTitle = 'My Profile - SafeHaven';
$activePage = 'profile';
$extraCss = ['assets/css/Profile.css'];
$extraJs = ['assets/js/Profile.js'];

require_once VIEW_PATH . 'shared/dashboard-header.php';
?>

<div class="profile-page">
<main class="profile-main">
<div class="profile-container">

<!-- Page Header -->
<div class="profile-page-head">
    <div class="profile-page-head-left">
        <div class="profile-page-head-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>
        <div>
            <h2>My Profile</h2>
            <p>Manage your account information</p>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if ($successMessage): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($successMessage); ?>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessages)): ?>
    <div class="alert alert-error">
        <?php foreach ($errorMessages as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Profile Content Grid -->
<div class="profile-grid">

    <!-- Left Column: Profile Card -->
    <div class="profile-card">
        <div class="profile-avatar-section">
            <div class="profile-avatar">
                <div class="avatar-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
            </div>
            <button class="btn-change-avatar" onclick="alert('Avatar upload feature coming soon!')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
                Change Photo
            </button>
        </div>
        
        <div class="profile-info">
            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
            <p class="profile-role"><?php echo htmlspecialchars($user['role']); ?></p>
            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        
        <div class="profile-stats">
            <div class="stat-item">
                <div class="stat-label">Member Since</div>
                <div class="stat-value"><?php echo date('M Y', strtotime($user['joined_date'])); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Account Status</div>
                <div class="stat-value">
                    <span class="status-badge status-active">Active</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Edit Form -->
    <div class="profile-forms">
        
        <!-- Personal Information Form -->
        <div class="form-section">
            <div class="form-section-header">
                <h3>Personal Information</h3>
                <button type="button" class="btn-edit" id="editBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit
                </button>
            </div>
            
            <form id="profileForm" method="POST" action="index.php?page=profile">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address (Barangay)</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" readonly required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password (optional)</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password" readonly>
                        <span class="field-hint">Minimum 6 characters</span>
                    </div>
                </div>
                
                <div class="form-actions" id="formActions" style="display: none;">
                    <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>

    </div>

</div>

</div><!-- /profile-container -->
</main><!-- /profile-main -->
</div><!-- /profile-page -->

<script>
// Profile Edit Functionality
(function() {
    let isEditing = false;
    let originalValues = {};
    
    function storeOriginalValues() {
        originalValues = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value
        };
    }
    
    function toggleEdit() {
        if (!isEditing) {
            storeOriginalValues();
            
            const fields = ['name', 'email', 'phone', 'address', 'new_password'];
            fields.forEach(function(id) {
                const input = document.getElementById(id);
                if (input) {
                    input.removeAttribute('readonly');
                    input.classList.add('editing');
                }
            });
            
            document.getElementById('formActions').style.display = 'flex';
            document.getElementById('editBtn').style.display = 'none';
            
            isEditing = true;
        }
    }
    
    function cancelEdit() {
        document.getElementById('name').value = originalValues.name;
        document.getElementById('email').value = originalValues.email;
        document.getElementById('phone').value = originalValues.phone;
        document.getElementById('address').value = originalValues.address;
        document.getElementById('new_password').value = '';
        
        const fields = ['name', 'email', 'phone', 'address', 'new_password'];
        fields.forEach(function(id) {
            const input = document.getElementById(id);
            if (input) {
                input.setAttribute('readonly', 'readonly');
                input.classList.remove('editing');
            }
        });
        
        document.getElementById('formActions').style.display = 'none';
        document.getElementById('editBtn').style.display = 'flex';
        
        isEditing = false;
    }
    
    function setupAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const editBtn = document.getElementById('editBtn');
        if (editBtn) {
            editBtn.addEventListener('click', toggleEdit);
        }
        
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', cancelEdit);
        }
        
        setupAlerts();
    });
})();
</script>

<?php require_once VIEW_PATH . 'shared/footer.php'; ?>
