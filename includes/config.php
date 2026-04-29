<?php
/**
 * ChubbyCMS - Config
 * Simple configuration file
 */

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('NOTES_PATH', ROOT_PATH . '/content/notes');
define('MEDIA_PATH', ROOT_PATH . '/content/uploads');
define('CONFIG_FILE', ROOT_PATH . '/content/settings.json');

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
        '2fa_secret' => '123456',
        'site_title' => 'defenders08',
        'theme' => 'dark',
        'nav' => [
            'header' => [],
            'footer' => []
        ]
    ];
    saveConfig($default);
    return $default;
}

function saveConfig($config) {
    file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Initialize config
$config = loadConfig();

// Sanitize input
function sanitize($input) {
    if (is_array($input)) return $input;
    return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
}
?>
