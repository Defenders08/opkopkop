<?php
/**
 * ChubbyCMS - Secure Login Page
 */
require_once '../includes/config.php';
require_once '../core/Auth.php';

use Core\Auth;

Auth::initSession();

if (Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    header('Content-Type: application/json');

    if ($action === 'login') {
        $password = $_POST['password'] ?? '';
        $login_result = Auth::login($password, $config);
        if ($login_result === true) {
            echo json_encode(['success' => true, 'step' => 2]);
        } elseif ($login_result === 'too_many_attempts') {
            echo json_encode(['success' => false, 'error' => 'Слишком много попыток. Попробуйте через 5 минут.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Неверный пароль']);
        }
        exit;
    } elseif ($action === 'verify_2fa') {
        $code = $_POST['code'] ?? '';
        if (Auth::verify2FA($code, $config)) {
            echo json_encode(['success' => true, 'redirect' => 'index.php']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Неверный или истекший код']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChubbyCMS - Вход</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-container { background: var(--bg2); border: 1px solid var(--border); padding: 40px; width: 320px; display: flex; flex-direction: column; gap: 16px; }
        .error-msg { color: #c47575; font-size: 11px; text-align: center; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-title">// ChubbyCMS Login</div>
        
        <div id="step-1">
            <div class="login-subtitle">введите пароль</div>
            <input type="password" id="password" class="cms-input" autofocus>
            <div id="error-1" class="error-msg hidden"></div>
            <button class="cms-btn cms-btn-save" style="width: 100%; margin-top: 10px;" onclick="doLogin()">продолжить</button>
        </div>

        <div id="step-2" class="hidden">
            <div class="login-subtitle">введите 2FA код</div>
            <input type="text" id="twofa-code" class="cms-input" placeholder="000000" maxlength="6">
            <div id="error-2" class="error-msg hidden"></div>
            <button class="cms-btn cms-btn-save" style="width: 100%; margin-top: 10px;" onclick="doVerify()">войти</button>
        </div>
    </div>

    <script>
        async function doLogin() {
            const password = document.getElementById('password').value;
            const res = await fetch('login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=login&password=${encodeURIComponent(password)}`
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('step-1').classList.add('hidden');
                document.getElementById('step-2').classList.remove('hidden');
                document.getElementById('twofa-code').focus();
            } else {
                const err = document.getElementById('error-1');
                err.textContent = data.error;
                err.classList.remove('hidden');
            }
        }

        async function doVerify() {
            const code = document.getElementById('twofa-code').value;
            const res = await fetch('login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=verify_2fa&code=${encodeURIComponent(code)}`
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                const err = document.getElementById('error-2');
                err.textContent = data.error;
                err.classList.remove('hidden');
            }
        }

        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                if (!document.getElementById('step-1').classList.contains('hidden')) doLogin();
                else if (!document.getElementById('step-2').classList.contains('hidden')) doVerify();
            }
        });
    </script>
</body>
</html>
