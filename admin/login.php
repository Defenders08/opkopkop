<?php
/**
 * ChubbyCMS - Login Page with 2FA (F2A)
 * Press F2 to open login modal
 */
require_once '../includes/config.php';

// If already logged in, redirect to admin
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $step = $_POST['step'] ?? '1';
    
    if ($step === '1') {
        // Step 1: Verify password
        if (!empty($code) && password_verify($code, $config['password_hash'])) {
            // Generate temporary token for 2FA
            $_SESSION['temp_auth'] = bin2hex(random_bytes(16));
            $_SESSION['temp_auth_time'] = time();
            echo json_encode(['success' => true, 'step' => '2', 'token' => $_SESSION['temp_auth']]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'неверный пароль']);
            exit;
        }
    } elseif ($step === '2') {
        // Step 2: Verify 2FA code (simplified TOTP-like check)
        $tempAuth = $_SESSION['temp_auth'] ?? '';
        $providedToken = $_POST['token'] ?? '';
        $twoFACode = $_POST['2fa_code'] ?? '';
        
        // Check if temp auth is valid and not expired (5 min)
        if (empty($tempAuth) || $providedToken !== $tempAuth || 
            (time() - ($_SESSION['temp_auth_time'] ?? 0)) > 300) {
            echo json_encode(['success' => false, 'error' => 'сессия истекла']);
            exit;
        }
        
        // For simplicity, using a fixed 2FA secret (in production, use Google Authenticator compatible TOTP)
        // Here we simulate 2FA with a simple code stored in config
        $secret2fa = $config['2fa_secret'] ?? '123456';
        
        if ($twoFACode === $secret2fa) {
            // Login successful
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            unset($_SESSION['temp_auth']);
            unset($_SESSION['temp_auth_time']);
            echo json_encode(['success' => true, 'redirect' => 'index.php']);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'неверный 2FA код']);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChubbyCMS - Вход</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: var(--bg2);
            border: 1px solid var(--border);
            padding: 40px;
            width: 320px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .login-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--accent);
        }
        .login-step {
            font-size: 11px;
            color: var(--text-dim);
        }
        .cms-input {
            width: 100%;
            text-align: center;
            letter-spacing: 0.2em;
        }
        .cms-btn {
            width: 100%;
            text-align: center;
            justify-content: center;
        }
        .error-msg {
            color: #c47575;
            font-size: 11px;
            text-align: center;
        }
        .hidden { display: none; }
        .back-btn {
            background: none;
            border: none;
            color: var(--text-dim);
            font-family: var(--font);
            font-size: 10px;
            cursor: pointer;
            padding: 4px;
        }
        .back-btn:hover { color: var(--accent); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-title">// ChubbyCMS</div>
        
        <!-- Step 1: Password -->
        <div id="step-1">
            <div class="login-step">введи пароль</div>
            <input type="password" id="password-input" class="cms-input" placeholder="••••••" autocomplete="current-password" maxlength="64">
            <div class="error-msg hidden" id="error-1"></div>
            <button class="cms-btn cms-btn-save" style="margin-top: 12px;" onclick="submitPassword()">войти</button>
        </div>
        
        <!-- Step 2: 2FA Code -->
        <div id="step-2" class="hidden">
            <div class="login-step">введи 2FA код</div>
            <input type="text" id="2fa-input" class="cms-input" placeholder="123456" autocomplete="one-time-code" maxlength="6" pattern="[0-9]*" inputmode="numeric">
            <div class="error-msg hidden" id="error-2"></div>
            <button class="cms-btn cms-btn-save" style="margin-top: 12px;" onclick="submit2FA()">подтвердить</button>
            <button class="back-btn" onclick="goBack()" style="width: 100%; margin-top: 8px;">← назад</button>
        </div>
        
        <div style="font-size: 9px; color: var(--text-dim); text-align: center; margin-top: 16px;">
            нажми F2 на главной для входа
        </div>
    </div>

    <script>
        let tempToken = '';
        
        async function submitPassword() {
            const password = document.getElementById('password-input').value;
            const errorEl = document.getElementById('error-1');
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `code=${encodeURIComponent(password)}&step=1`
                });
                const data = await response.json();
                
                if (data.success) {
                    tempToken = data.token;
                    document.getElementById('step-1').classList.add('hidden');
                    document.getElementById('step-2').classList.remove('hidden');
                    document.getElementById('2fa-input').focus();
                } else {
                    errorEl.textContent = data.error;
                    errorEl.classList.remove('hidden');
                }
            } catch (e) {
                errorEl.textContent = 'ошибка соединения';
                errorEl.classList.remove('hidden');
            }
        }
        
        async function submit2FA() {
            const code = document.getElementById('2fa-input').value;
            const errorEl = document.getElementById('error-2');
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `token=${encodeURIComponent(tempToken)}&2fa_code=${encodeURIComponent(code)}&step=2`
                });
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    errorEl.textContent = data.error;
                    errorEl.classList.remove('hidden');
                }
            } catch (e) {
                errorEl.textContent = 'ошибка соединения';
                errorEl.classList.remove('hidden');
            }
        }
        
        function goBack() {
            document.getElementById('step-2').classList.add('hidden');
            document.getElementById('step-1').classList.remove('hidden');
            document.getElementById('password-input').value = '';
            document.getElementById('error-1').classList.add('hidden');
            tempToken = '';
        }
        
        // Allow Enter key to submit
        document.getElementById('password-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') submitPassword();
        });
        document.getElementById('2fa-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') submit2FA();
        });
    </script>
</body>
</html>
