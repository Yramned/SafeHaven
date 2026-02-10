<?php
/**
 * SafeHaven - Authentication Controller
 */

require_once MODEL_PATH . 'UserModel.php';

class AuthController {
    
    public static function showLoginForm() {
        $pageTitle = 'Login - SafeHaven';
        $activePage = 'login';
        $extraCss = ['assets/css/Auth.css'];
        $extraJs = ['assets/js/Auth.js'];
        
        require_once VIEW_PATH . 'auth/login.php';
    }
    
    public static function showRegisterForm() {
        $pageTitle = 'Register - SafeHaven';
        $activePage = 'register';
        $extraCss = ['assets/css/Auth.css'];
        $extraJs = ['assets/js/Auth.js'];
        
        require_once VIEW_PATH . 'auth/register.php';
    }
    
    public static function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $user = UserModel::authenticate($email, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_phone'] = $user['phone_number'];
            $_SESSION['user_address'] = $user['address'];
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        } else {
            $_SESSION['error'] = 'Invalid email or password';
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
    }
    
    public static function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'index.php?page=register');
            exit;
        }
        
        $errors = [];
        
        // Validate input
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone_number'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'evacuee'; // Get role from form
        
        if (empty($fullName) || strlen($fullName) < 2) {
            $errors[] = 'Full name must be at least 2 characters';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required';
        } elseif (UserModel::getByEmail($email)) {
            $errors[] = 'Email already registered';
        }
        
        if (empty($phone)) {
            $errors[] = 'Phone number is required';
        }
        
        if (empty($address)) {
            $errors[] = 'Address is required';
        }
        
        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // Validate role
        if (!in_array($role, ['evacuee', 'admin'])) {
            $errors[] = 'Invalid role selected';
        }
        
        if (!empty($errors)) {
            $_SESSION['register_errors'] = $errors;
            $_SESSION['register_old'] = $_POST;
            header('Location: ' . BASE_URL . 'index.php?page=register');
            exit;
        }
        
        // Create user
        $userData = [
            'full_name' => $fullName,
            'email' => $email,
            'phone_number' => $phone,
            'address' => $address,
            'password' => $password,
            'role' => $role // Save the selected role
        ];
        
        $user = UserModel::create($userData);
        
        if ($user) {
            $_SESSION['success'] = 'Registration successful! Please login.';
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        } else {
            $_SESSION['error'] = 'Registration failed. Please try again.';
            header('Location: ' . BASE_URL . 'index.php?page=register');
            exit;
        }
    }
    
    public static function logout() {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}