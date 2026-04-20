<?php
/**
 * SafeHaven - Main Configuration File
 * Works on XAMPP (Windows/Mac/Linux) and HelioHost.
 */

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (
    $host === 'localhost' ||
    str_starts_with($host, '127.') ||
    str_starts_with($host, '192.168.') ||
    str_ends_with($host, '.local') ||
    strpos($host, ':') !== false
);
define('IS_LOCAL', $isLocal);

if ($isLocal) {
    $docRoot   = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
    $indexFile = str_replace('\\', '/', realpath(dirname(__DIR__) . '/index.php'));
    $indexDir  = str_replace('\\', '/', dirname($indexFile));
    $subPath   = ltrim(str_replace($docRoot, '', $indexDir), '/');
    $baseUrl   = 'http://' . $host . '/' . ($subPath ? $subPath . '/' : '');
    define('BASE_URL', $baseUrl);
} else {
    define('BASE_URL', 'https://safehaven.helioho.st/');
}

define('SITE_NAME',        'SafeHaven');
define('SITE_DESCRIPTION', 'Emergency Evacuation System');
define('CONTACT_PHONE',   '+63 947 7153 075');
define('CONTACT_EMAIL',   'safehaven@support.com');
define('CONTACT_ADDRESS', 'Cebu City, Philippines');
define('CONTACT_WEBSITE', 'www.safehaven.com');
define('PHILSMS_TOKEN',   '1921|mmCNh0q3Dbpi7pUb9pvlteVWMLoRsDlgbawgNtBAf861eeb3');

define('ROOT_PATH',       dirname(__DIR__) . '/');
define('CONFIG_PATH',     ROOT_PATH . 'config/');
define('CONTROLLER_PATH', ROOT_PATH . 'controllers/');
define('MODEL_PATH',      ROOT_PATH . 'models/');
define('VIEW_PATH',       ROOT_PATH . 'views/');
define('STORAGE_PATH',    ROOT_PATH . 'storage/');
define('SERVICES_PATH',   ROOT_PATH . 'services/');
define('ASSET_PATH',      BASE_URL  . 'assets/');
define('CSS_PATH',        ASSET_PATH . 'css/');
define('JS_PATH',         ASSET_PATH . 'js/');

if ($isLocal) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'storage/error.log');

    // Global exception/error handler for production – prevents raw PHP errors leaking
    set_exception_handler(function(Throwable $e) {
        error_log('[SafeHaven] Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) {
            $isAjax = (
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            );
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'An unexpected server error occurred. Please try again.']);
            } else {
                http_response_code(500);
                echo '<!DOCTYPE html><html><head><title>Server Error – SafeHaven</title>
                <meta name="viewport" content="width=device-width,initial-scale=1">
                <style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:system-ui,sans-serif;background:#0a1628;color:#eef2f7;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;text-align:center}h1{font-size:3rem;color:#e74c3c;margin-bottom:8px}p{color:#8fa8c2;margin:8px 0 24px}a{color:#5dade2;text-decoration:none;padding:10px 24px;border:1px solid #5dade2;border-radius:8px;font-weight:600}</style></head>
                <body><div><h1>500</h1><h2 style="color:#fff;margin-bottom:12px">Server Error</h2><p>An unexpected error occurred. Please try again later.</p><a href="' . (defined('BASE_URL') ? htmlspecialchars(BASE_URL) : '/') . '">Go to Home</a></div></body></html>';
            }
        }
        exit;
    });

    set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline) {
        if (!(error_reporting() & $errno)) return false;
        error_log("[SafeHaven] PHP Error [{$errno}]: {$errstr} in {$errfile}:{$errline}");
        return false; // continue with normal error handling
    });
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('Asia/Manila');

define('USERS_FILE',    STORAGE_PATH . 'users.json');
define('MESSAGES_FILE', STORAGE_PATH . 'messages.json');
define('CAPACITY_FILE', STORAGE_PATH . 'capacity_data.json');

if (!is_dir(STORAGE_PATH)) { @mkdir(STORAGE_PATH, 0755, true); }
