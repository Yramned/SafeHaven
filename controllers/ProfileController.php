<?php
/**
 * SafeHaven - Profile Controller
 * Handles all profile-related actions (show, update)
 */

require_once MODEL_PATH . 'UserModel.php';

class ProfileController {

    /**
     * Show the profile page
     */
    public static function show() {
        // Must be logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }

        // Load user from DB
        $currentUser = UserModel::getById($_SESSION['user_id']);
        if (!$currentUser) {
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        }

        $successMessage = $_SESSION['success_message'] ?? null;
        $errorMessages  = $_SESSION['error_messages']  ?? [];
        unset($_SESSION['success_message'], $_SESSION['error_messages']);

        $user = [
            'id'          => $currentUser['id'],
            'name'        => $currentUser['full_name']    ?? '',
            'email'       => $currentUser['email']        ?? '',
            'phone'       => $currentUser['phone_number'] ?? '',
            'address'     => $currentUser['address']      ?? '',
            'role'        => ucfirst($currentUser['role'] ?? 'evacuee'),
            'joined_date' => $currentUser['created_at']   ?? date('Y-m-d'),
        ];

        $pageTitle  = 'My Profile – SafeHaven';
        $activePage = 'profile';
        $extraCss   = ['assets/css/Profile.css'];
        $extraJs    = ['assets/js/Profile.js'];

        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/profile.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }

    /**
     * Handle profile update form submission
     */
    public static function update() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'index.php?page=profile');
            exit;
        }

        $errors  = [];
        $name    = trim($_POST['name']         ?? '');
        $email   = strtolower(trim($_POST['email']    ?? ''));
        $phone   = trim($_POST['phone']        ?? '');
        $address = trim($_POST['address']      ?? '');
        $newPass = trim($_POST['new_password'] ?? '');

        if (strlen($name)  < 2)                             $errors[] = 'Name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'A valid email address is required.';
        if (empty($phone))                                  $errors[] = 'Phone number is required.';
        if (empty($address))                                $errors[] = 'Address is required.';
        if ($newPass !== '' && strlen($newPass) < 6)        $errors[] = 'New password must be at least 6 characters.';

        if (empty($errors)) {
            $updateData = [
                'full_name'    => $name,
                'email'        => $email,
                'phone_number' => $phone,
                'address'      => $address,
            ];
            if ($newPass !== '') {
                $updateData['password'] = $newPass; // UserModel hashes it
            }

            UserModel::update($_SESSION['user_id'], $updateData);

            // Refresh session
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;

            $_SESSION['success_message'] = 'Profile updated successfully!';
        } else {
            $_SESSION['error_messages'] = $errors;
        }

        header('Location: ' . BASE_URL . 'index.php?page=profile');
        exit;
    }
}
