<?php
/**
 * ChubbyCMS - Config
 * Simple configuration file
 */

// Security: Set secure session settings
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Strict');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paths
define('BASE_PATH', __DIR__);
define('NOTES_PATH', BASE_PATH . '/notes');
define('MEDIA_PATH', BASE_PATH . '/media');
define('CONFIG_FILE', BASE_PATH . '/includes/config.json');

// Ensure directories exist
if (!is_dir(NOTES_PATH)) mkdir(NOTES_PATH, 0755, true);
if (!is_dir(MEDIA_PATH)) mkdir(MEDIA_PATH, 0755, true);

// Load or create config
function loadConfig() {
    if (file_exists(CONFIG_FILE)) {
        return json_decode(file_get_contents(CONFIG_FILE), true);
    }
    // Default config
    $default = [
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'site_title' => 'defenders08',
        'theme' => 'dark'
    ];
    saveConfig($default);
    return $default;
}

function saveConfig($config) {
    file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
}

// Initialize config
$config = loadConfig();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
