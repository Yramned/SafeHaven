<?php
/**
 * SafeHaven - User Management View
 * This is a VIEW file - variables are passed from UserManagementController
 */

// Load user data
$storageDir = STORAGE_PATH;
$usersFile  = $storageDir . 'users.json';

if (!is_dir($storageDir)) { @mkdir($storageDir, 0777, true); }

if (!file_exists($usersFile) || filesize($usersFile) == 0) {
    $initialUsers = [
        ['id'=>1,'full_name'=>'Maria Santos','email'=>'maria@example.com','phone_number'=>'+63 917 123 4567','address'=>'Brgy. Sta. Ana','role'=>'evacuee','created_at'=>'2026-01-28 09:00:00'],
        ['id'=>2,'full_name'=>'Juan dela Cruz','email'=>'juan@example.com','phone_number'=>'+63 918 234 5678','address'=>'Brgy. Poblacion','role'=>'center_manager','created_at'=>'2026-01-29 10:30:00'],
        ['id'=>3,'full_name'=>'Ana Reyes','email'=>'ana@example.com','phone_number'=>'+63 919 345 6789','address'=>'Brgy. Silang','role'=>'evacuee','created_at'=>'2026-01-30 14:15:00']
    ];
    file_put_contents($usersFile, json_encode($initialUsers, JSON_PRETTY_PRINT));
}

$users = json_decode(file_get_contents($usersFile), true) ?: [];

// Calculate stats
$totalUsers = count($users);
$evacuees   = count(array_filter($users, fn($u) => ($u['role']??'') === 'evacuee'));

$pageTitle = 'User Management - SafeHaven';
$activePage = 'user-management';
$extraCss = ['assets/css/UserManagement.css'];
$extraJs = ['assets/js/UserManagement.js'];


// Helper function to get initials from full name
if (!function_exists('initials')) {
    function initials($name) {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

require_once VIEW_PATH . 'shared/dashboard-header.php';
?>

<div class="um-page">
    <main class="um-main">
        <div class="um-container">

            <!-- Header -->
            <div class="um-head">
                <div>
                    <h2>User Management</h2>
                    <p>Total Registered: <?= $totalUsers ?></p>
                </div>
                <button class="btn-add-user" onclick="toggleModal(true)">+ Add New User</button>
            </div>

            <!-- Stats -->
            <div class="um-stats">
                <div class="um-stat"><b>Evacuees:</b> <?= $evacuees ?></div>
                <div class="um-stat"><b>Active Staff:</b> <?= ($totalUsers - $evacuees) ?></div>
            </div>

            <!-- Table -->
            <div class="um-table-wrap">
                <table class="um-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="um-avatar">
                                    <?= initials($u['full_name']) ?>
                                </div>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </td>
                            <td>
                                <span class="badge badge-blue">
                                    <?= ucfirst(str_replace('_', ' ', $u['role'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($u['phone_number'] ?? 'N/A') ?></td>
                            <td>
                                <button onclick="alert('Edit logic triggered')" style="cursor:pointer">✏️</button>
                                <button onclick="this.closest('tr').remove()" style="cursor:pointer">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="modalOverlay" style="display:none;">
    <div class="modal-box">
        <h3>Add New User</h3>

        <form onsubmit="event.preventDefault(); alert('User saved!'); toggleModal(false);">
            <label>Full Name</label>
            <input type="text" placeholder="e.g. Juan Dela Cruz" required>

            <label>Email</label>
            <input type="email" placeholder="email@example.com" required>

            <label>Role</label>
            <select required>
                <option value="evacuee">Evacuee</option>
                <option value="center_manager">Center Manager</option>
            </select>

            <div style="margin-top:20px; display:flex; gap:10px;">
                <button type="submit" class="btn-add-user">Save User</button>
                <button type="button"
                        onclick="toggleModal(false)"
                        style="background:#7f8c8d; color:white; border:none; padding:10px; border-radius:8px;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Script -->
<script>
function toggleModal(show) {
    document.getElementById('modalOverlay').style.display = show ? 'flex' : 'none';
}
</script>

<?php require_once VIEW_PATH . 'shared/footer.php'; ?>
