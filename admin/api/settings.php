<?php
/**
 * ChubbyCMS - Settings API
 */
require_once '../../includes/config.php';
require_once '../../core/Auth.php';

use Core\Auth;

Auth::initSession();

if (!Auth::isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'get':
        echo json_encode(['settings' => $config]);
        break;

    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!Auth::verifyCSRFToken($data['csrf_token'] ?? '')) {
            echo json_encode(['error' => 'Invalid CSRF token']);
            break;
        }

        // Filter sensitive data and merge
        $newSettings = $data['settings'] ?? [];
        unset($newSettings['password_hash']);
        unset($newSettings['2fa_secret']);

        $config = array_merge($config, $newSettings);
        saveConfig($config);
        echo json_encode(['success' => true]);
        break;

    case 'security':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!Auth::verifyCSRFToken($data['csrf_token'] ?? '')) {
            echo json_encode(['error' => 'Invalid CSRF token']);
            break;
        }

        if (isset($data['new_password']) && !empty($data['new_password'])) {
            $config['password_hash'] = password_hash($data['new_password'], PASSWORD_DEFAULT);
        }

        if (isset($data['2fa_secret'])) {
            $config['2fa_secret'] = $data['2fa_secret'];
        }

        saveConfig($config);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
