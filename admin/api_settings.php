<?php
/**
 * ChubbyCMS - Settings API
 */
require_once '../includes/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'invalid csrf token']);
        return;
    }
    
    // Change password
    if (isset($input['action']) && $input['action'] === 'change_password') {
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';
        
        if (!password_verify($currentPassword, $config['password_hash'])) {
            echo json_encode(['error' => 'неверный текущий пароль']);
            return;
        }
        
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['error' => 'пароли не совпадают']);
            return;
        }
        
        if (strlen($newPassword) < 6) {
            echo json_encode(['error' => 'пароль должен быть не менее 6 символов']);
            return;
        }
        
        $config['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        saveConfig($config);
        
        echo json_encode(['success' => true]);
        return;
    }
    
    // Change 2FA secret
    if (isset($input['action']) && $input['action'] === 'change_2fa') {
        $newSecret = $input['2fa_secret'] ?? '';
        
        if (strlen($newSecret) < 4) {
            echo json_encode(['error' => '2FA код должен быть не менее 4 символов']);
            return;
        }
        
        $config['2fa_secret'] = $newSecret;
        saveConfig($config);
        
        echo json_encode(['success' => true]);
        return;
    }
}

// Get current settings
echo json_encode([
    'site_title' => $config['site_title'] ?? 'defenders08',
    'theme' => $config['theme'] ?? 'dark',
    'has_2fa' => isset($config['2fa_secret'])
]);
?>
