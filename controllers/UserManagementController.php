<?php
/**
 * SafeHaven - User Management Controller
 * Fully DB-backed: list, add, edit, delete via AJAX + page render.
 */

require_once MODEL_PATH . 'UserModel.php';

class UserManagementController {

    /** Ensure admin access */
    private static function requireAdmin() {
        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        }
    }

    /** Show page */
    public static function index() {
        self::requireAdmin();

        // Load all users from DB
        try {
            $users = UserModel::getAll();
        } catch (Exception $e) {
            error_log('[UserManagementController] index: ' . $e->getMessage());
            $users = [];
        }

        $totalUsers = count($users);
        $evacuees   = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'evacuee'));
        $admins     = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'admin'));

        $pageTitle  = 'User Management - SafeHaven';
        $activePage = 'user-management';
        $extraCss   = ['assets/css/safehaven-system.css','assets/css/UserManagement.css'];
        $extraJs    = ['assets/js/UserManagement.js'];

        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/user-management.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }

    /** AJAX: Add a new user */
    public static function addUser() {
        header('Content-Type: application/json');
        self::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $fullName = trim($input['full_name']    ?? '');
        $email    = strtolower(trim($input['email'] ?? ''));
        $phone    = trim($input['phone_number'] ?? '09000000000');
        $address  = trim($input['address']      ?? 'N/A');
        $role     = $input['role']              ?? 'evacuee';
        $password = $input['password']          ?? 'password123';

        $errors = [];
        if (strlen($fullName) < 2)                      $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Valid email is required.';
        try {
            if (UserModel::getByEmail($email))           $errors[] = 'Email already exists.';
        } catch (Exception $e) {
            error_log('[UserManagement] addUser email check: ' . $e->getMessage());
        }
        if (!in_array($role, ['evacuee', 'admin']))      $errors[] = 'Invalid role.';
        if (strlen($password) < 6)                       $errors[] = 'Password must be at least 6 characters.';

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit;
        }

        try {
            $user = UserModel::create([
                'full_name'    => $fullName,
                'email'        => $email,
                'phone_number' => $phone,
                'address'      => $address,
                'password'     => $password,
                'role'         => $role,
            ]);
        } catch (Exception $e) {
            error_log('[UserManagement] addUser create: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error while creating user.']);
            exit;
        }

        if ($user) {
            echo json_encode([
                'success' => true,
                'message' => 'User added successfully.',
                'user'    => [
                    'id'           => $user['id'],
                    'full_name'    => $user['full_name'],
                    'email'        => $user['email'],
                    'phone_number' => $user['phone_number'],
                    'role'         => $user['role'],
                    'created_at'   => $user['created_at'],
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create user.']);
        }
        exit;
    }

    /** AJAX: Edit a user */
    public static function editUser() {
        header('Content-Type: application/json');
        self::requireAdmin();

        $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $userId = (int)($input['id'] ?? 0);

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit;
        }

        $existing = UserModel::getById($userId);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $updateData = [];

        if (!empty($input['full_name'])) {
            $fullName = trim($input['full_name']);
            if (strlen($fullName) >= 2) $updateData['full_name'] = $fullName;
        }
        if (!empty($input['email'])) {
            $email = strtolower(trim($input['email']));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Check unique, but allow same email (own record)
                $exists = UserModel::getByEmail($email);
                if (!$exists || (int)$exists['id'] === $userId) {
                    $updateData['email'] = $email;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Email already in use.']);
                    exit;
                }
            }
        }
        if (!empty($input['phone_number']))
            $updateData['phone_number'] = trim($input['phone_number']);
        if (!empty($input['address']))
            $updateData['address'] = trim($input['address']);
        if (!empty($input['role']) && in_array($input['role'], ['evacuee', 'admin']))
            $updateData['role'] = $input['role'];
        if (!empty($input['password']) && strlen($input['password']) >= 6)
            $updateData['password'] = $input['password'];

        // Prevent demoting yourself if you're the only admin
        if (isset($updateData['role']) && $updateData['role'] !== 'admin' && (int)$userId === (int)$_SESSION['user_id']) {
            $adminCount = UserModel::countByRole('admin');
            if ($adminCount <= 1) {
                echo json_encode(['success' => false, 'message' => 'Cannot demote the only admin.']);
                exit;
            }
        }

        if (empty($updateData)) {
            echo json_encode(['success' => false, 'message' => 'No valid fields to update.']);
            exit;
        }

        try {
            $ok = UserModel::update($userId, $updateData);
            if ($ok) {
                $updated = UserModel::getById($userId);
                // Refresh session if editing yourself
                if ((int)$userId === (int)$_SESSION['user_id']) {
                    $_SESSION['user_name']  = $updated['full_name'];
                    $_SESSION['user_email'] = $updated['email'];
                    $_SESSION['user_role']  = $updated['role'];
                }
                echo json_encode([
                    'success' => true,
                    'message' => 'User updated successfully.',
                    'user'    => [
                        'id'           => $updated['id'],
                        'full_name'    => $updated['full_name'],
                        'email'        => $updated['email'],
                        'phone_number' => $updated['phone_number'],
                        'role'         => $updated['role'],
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user.']);
            }
        } catch (Exception $e) {
            error_log('[UserManagement] editUser: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error while updating user.']);
        }
        exit;
    }

    /** AJAX: Delete a user */
    public static function deleteUser() {
        header('Content-Type: application/json');
        self::requireAdmin();

        $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $userId = (int)($input['id'] ?? 0);

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit;
        }

        // Prevent self-delete
        if ((int)$userId === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
            exit;
        }

        try {
            // Prevent deleting the last admin
            $user = UserModel::getById($userId);
            if ($user && $user['role'] === 'admin') {
                $adminCount = UserModel::countByRole('admin');
                if ($adminCount <= 1) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete the last admin account.']);
                    exit;
                }
            }

            $ok = UserModel::delete($userId);
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'User deleted.' : 'Failed to delete user.',
                'id'      => $userId,
            ]);
        } catch (Exception $e) {
            error_log('[UserManagement] deleteUser: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error while deleting user.']);
        }
        exit;
    }

    /** AJAX: Get single user data for edit modal */
    public static function getUser() {
        header('Content-Type: application/json');
        self::requireAdmin();

        $userId = (int)($_GET['id'] ?? 0);
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }

        $user = UserModel::getById($userId);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        // Don't expose password hash
        unset($user['password']);
        echo json_encode(['success' => true, 'user' => $user]);
        exit;
    }
}
