<?php
/**
 * SafeHaven - Main Configuration File
 * Auto-detects environment (localhost vs HelioHost)
 */

// Detect environment
$isLocal = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'localhost:') !== false
);

// Environment Constants
define('IS_LOCAL', $isLocal);

// Base URL Configuration
if ($isLocal) {
    // For localhost - adjust this if your project is in a subfolder
    define('BASE_URL', 'http://localhost/SafeHaven_Merged/');
} else {
    // For HelioHost - your project is in the root
    define('BASE_URL', 'https://safeheaven.helioho.st/');
}

// Site Configuration
define('SITE_NAME', 'SafeHaven');
define('SITE_DESCRIPTION', 'Emergency Evacuation System');

// Contact Information
define('CONTACT_PHONE', '+63 947 7153 075');
define('CONTACT_EMAIL', 'safehaven@support.com');
define('CONTACT_ADDRESS', 'Cebu City, Philippines');
define('CONTACT_WEBSITE', 'www.safehaven.com');

// Path Constants
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('CONTROLLER_PATH', ROOT_PATH . 'controllers/');
define('MODEL_PATH', ROOT_PATH . 'models/');
define('VIEW_PATH', ROOT_PATH . 'views/');
define('STORAGE_PATH', ROOT_PATH . 'storage/');
define('ASSET_PATH', BASE_URL . 'assets/');
define('CSS_PATH', ASSET_PATH . 'css/');
define('JS_PATH', ASSET_PATH . 'js/');

// Error Reporting
if ($isLocal) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'storage/error.log');
}

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Manila');

// JSON File Paths
define('USERS_FILE', STORAGE_PATH . 'users.json');
define('MESSAGES_FILE', STORAGE_PATH . 'messages.json');
define('CAPACITY_FILE', STORAGE_PATH . 'capacity_data.json');

// Initialize storage directory
if (!is_dir(STORAGE_PATH)) {
    @mkdir(STORAGE_PATH, 0755, true);
}