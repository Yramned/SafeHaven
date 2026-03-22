<?php
/**
 * SafeHaven - Profile Controller
 * Handles all profile-related actions (show, update, family numbers)
 */

require_once MODEL_PATH . 'UserModel.php';

class ProfileController {

    public static function show() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }

        // Auto-migrate family_numbers column
        UserModel::ensureSchema();

        $currentUser = UserModel::getById($_SESSION['user_id']);
        if (!$currentUser) {
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        }

        $successMessage = $_SESSION['success_message'] ?? null;
        $errorMessages  = $_SESSION['error_messages']  ?? [];
        unset($_SESSION['success_message'], $_SESSION['error_messages']);

        $familyNumbers = [];
        if (!empty($currentUser['family_numbers'])) {
            $decoded = json_decode($currentUser['family_numbers'], true);
            if (is_array($decoded)) $familyNumbers = $decoded;
        }

        $user = [
            'id'             => $currentUser['id'],
            'name'           => $currentUser['full_name']    ?? '',
            'email'          => $currentUser['email']        ?? '',
            'phone'          => $currentUser['phone_number'] ?? '',
            'address'        => $currentUser['address']      ?? '',
            'role'           => ucfirst($currentUser['role'] ?? 'evacuee'),
            'joined_date'    => $currentUser['created_at']   ?? date('Y-m-d'),
            'family_numbers' => $familyNumbers,
        ];

        $pageTitle  = 'My Profile – SafeHaven';
        $activePage = 'profile';
        $extraCss   = ['assets/css/Profile.css'];
        $extraJs    = ['assets/js/Profile.js'];

        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/profile.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }

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

        // Family numbers – sent as JSON string from JS
        $familyNumbersRaw = trim($_POST['family_numbers'] ?? '[]');
        $familyNumbers = json_decode($familyNumbersRaw, true);
        if (!is_array($familyNumbers)) $familyNumbers = [];
        $familyNumbers = array_values(array_filter(array_map('trim', $familyNumbers)));

        if (strlen($name)  < 2)                             $errors[] = 'Name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'A valid email address is required.';
        if (empty($phone))                                  $errors[] = 'Phone number is required.';
        if (empty($address))                                $errors[] = 'Address is required.';
        if ($newPass !== '' && strlen($newPass) < 6)        $errors[] = 'New password must be at least 6 characters.';

        if (empty($errors)) {
            $updateData = [
                'full_name'      => $name,
                'email'          => $email,
                'phone_number'   => $phone,
                'address'        => $address,
                'family_numbers' => $familyNumbers,
            ];
            if ($newPass !== '') {
                $updateData['password'] = $newPass;
            }

            UserModel::update($_SESSION['user_id'], $updateData);

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
