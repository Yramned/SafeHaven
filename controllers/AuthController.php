<?php
/**
 * SafeHaven - Authentication Controller
 * Owns all auth page rendering and POST handling.
 */

require_once MODEL_PATH . 'UserModel.php';

class AuthController {

    public static function showLoginForm() {
        // Pull flash messages from session then clear them
        $error   = $_SESSION['error']   ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        $pageTitle  = 'Login - SafeHaven';
        $activePage = 'login';
        $extraCss   = ['assets/css/Auth.css'];
        $extraJs    = ['assets/js/Auth.js'];

        require_once VIEW_PATH . 'shared/header.php';
        require_once VIEW_PATH . 'auth/login.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }

    public static function showRegisterForm() {
        // Pull flash data then clear
        $errors  = $_SESSION['register_errors'] ?? [];
        $oldData = $_SESSION['register_old']    ?? [];
        unset($_SESSION['register_errors'], $_SESSION['register_old']);

        $pageTitle  = 'Register - SafeHaven';
        $activePage = 'register';
        $extraCss   = ['assets/css/Auth.css'];
        $extraJs    = ['assets/js/Auth.js'];

        require_once VIEW_PATH . 'shared/header.php';
        require_once VIEW_PATH . 'auth/register.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }

    public static function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        try {
            $user = UserModel::authenticate($email, $password);
        } catch (Exception $e) {
            error_log('[AuthController] Login error: ' . $e->getMessage());
            $_SESSION['error'] = 'A server error occurred. Please try again later.';
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }

        if ($user) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['user_name']    = $user['full_name'];
            $_SESSION['user_email']   = $user['email'];
            $_SESSION['user_role']    = $user['role'];
            $_SESSION['user_phone']   = $user['phone_number'];
            $_SESSION['user_address'] = $user['address'];
            session_regenerate_id(true);
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
        } else {
            $_SESSION['error'] = 'Invalid email or password.';
            header('Location: ' . BASE_URL . 'index.php?page=login');
        }
        exit;
    }

    public static function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'index.php?page=register');
            exit;
        }

        $errors  = [];
        $fullName = trim($_POST['full_name']        ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $phone    = trim($_POST['phone_number']     ?? '');
        $address  = trim($_POST['address']          ?? '');
        $password = $_POST['password']              ?? '';
        $confirm  = $_POST['confirm_password']      ?? '';
        $role     = $_POST['role']                  ?? 'evacuee';

        if (strlen($fullName) < 2)                              $errors[] = 'Full name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'A valid email address is required.';
        elseif (UserModel::getByEmail($email))                  $errors[] = 'Email is already registered.';
        if (empty($phone))                                      $errors[] = 'Phone number is required.';
        if (empty($address))                                    $errors[] = 'Address is required.';
        if (strlen($password) < 6)                             $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm)                             $errors[] = 'Passwords do not match.';
        if (!in_array($role, ['evacuee', 'admin']))             $errors[] = 'Invalid role selected.';

        if (!empty($errors)) {
            $_SESSION['register_errors'] = $errors;
            $_SESSION['register_old']    = $_POST;
            header('Location: ' . BASE_URL . 'index.php?page=register');
            exit;
        }

        // Ensure family_numbers column exists (safe auto-migration)
        UserModel::ensureSchema();

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
            error_log('[AuthController] Register error: ' . $e->getMessage());
            $user = false;
        }

        if ($user) {
            $_SESSION['success'] = 'Registration successful! Please login.';
            header('Location: ' . BASE_URL . 'index.php?page=login');
        } else {
            $_SESSION['error'] = 'Registration failed. Please try again.';
            header('Location: ' . BASE_URL . 'index.php?page=register');
        }
        exit;
    }

    public static function logout() {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}
