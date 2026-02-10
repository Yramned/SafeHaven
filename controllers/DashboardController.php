<?php
/**
 * SafeHaven - Dashboard Controller
 */

class DashboardController {
    
    public static function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        $pageTitle = 'Dashboard - SafeHaven';
        $activePage = 'dashboard';
        $extraCss = ['assets/css/Dashboard.css'];
        $extraJs = [];
        
        require_once VIEW_PATH . 'dashboard/index.php';
    }
    
    public static function profile() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        require_once MODEL_PATH . 'UserModel.php';
        
        $user = UserModel::getById($_SESSION['user_id']);
        
        if (!$user) {
            session_destroy();
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        // Prepare variables for the view
        $successMessage = $_SESSION['success_message'] ?? '';
        $errorMessages = $_SESSION['error_messages'] ?? [];
        
        // Clear session messages
        unset($_SESSION['success_message']);
        unset($_SESSION['error_messages']);
        
        $pageTitle = 'Profile - SafeHaven';
        $activePage = 'profile';
        $extraCss = ['assets/css/Profile.css'];
        $extraJs = [];
        
        require_once VIEW_PATH . 'dashboard/profile.php';
    }
    
    public static function updateProfile() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        }
        
        require_once MODEL_PATH . 'UserModel.php';
        
        $errors = [];
        
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required';
        } else {
            $existingUser = UserModel::getByEmail($email);
            if ($existingUser && $existingUser['id'] != $_SESSION['user_id']) {
                $errors[] = 'Email already in use';
            }
        }
        
        if (empty($phone)) {
            $errors[] = 'Phone number is required';
        }
        
        if (empty($address)) {
            $errors[] = 'Address is required';
        }
        
        if (!empty($newPassword) && strlen($newPassword) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if (!empty($errors)) {
            $_SESSION['error_messages'] = $errors;
            header('Location: ' . BASE_URL . 'index.php?page=profile');
            exit;
        }
        
        $updateData = [
            'full_name' => $name,
            'email' => $email,
            'phone_number' => $phone,
            'address' => $address
        ];
        
        if (!empty($newPassword)) {
            $updateData['password'] = $newPassword;
        }
        
        if (UserModel::update($_SESSION['user_id'], $updateData)) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['user_address'] = $address;
            
            $message = 'Profile updated successfully!';
            if (!empty($newPassword)) {
                $message .= ' Password changed.';
            }
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_messages'] = ['Failed to update profile'];
        }
        
        header('Location: ' . BASE_URL . 'index.php?page=profile');
        exit;
    }
}