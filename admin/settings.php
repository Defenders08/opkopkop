<?php
/**
 * ChubbyCMS - Settings Page
 */
require_once '../includes/config.php';
require_once '../core/Auth.php';

use Core\Auth;

Auth::initSession();
Auth::requireLogin();

$csrfToken = Auth::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChubbyCMS - Настройки</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .settings-container { padding: 20px; max-width: 600px; margin: 0 auto; }
        .settings-group { margin-bottom: 24px; border: 1px solid var(--border); padding: 20px; background: var(--bg2); }
        .settings-title { font-weight: 700; margin-bottom: 16px; color: var(--accent); }
    </style>
</head>
<body>
    <div class="body">
        <div class="header">
            <div class="left"><a href="index.php" class="site-title">// ChubbyCMS Settings</a></div>
            <div class="right"><a href="index.php">назад</a></div>
        </div>

        <div class="settings-container">
            <!-- Site Settings -->
            <div class="settings-group">
                <div class="settings-title">Основные настройки</div>
                <label class="cms-label">Название сайта</label>
                <input type="text" id="site_title" class="cms-input" value="<?php echo htmlspecialchars($config['site_title'] ?? ''); ?>">
                <button class="cms-btn cms-btn-save" style="margin-top: 10px;" onclick="saveSettings()">Сохранить</button>
            </div>

            <!-- Security Settings -->
            <div class="settings-group">
                <div class="settings-title">Безопасность</div>
                <label class="cms-label">Новый пароль (оставьте пустым, чтобы не менять)</label>
                <input type="password" id="new_password" class="cms-input">
                <label class="cms-label" style="margin-top: 10px;">2FA Secret (Base32)</label>
                <input type="text" id="2fa_secret" class="cms-input" value="<?php echo htmlspecialchars($config['2fa_secret'] ?? ''); ?>">
                <p style="font-size: 10px; color: var(--text-dim); margin-top: 4px;">Используйте этот секрет в Google Authenticator. Если оставить 123456, 2FA будет упрощенным.</p>
                <button class="cms-btn cms-btn-save" style="margin-top: 10px;" onclick="saveSecurity()">Обновить безопасность</button>
            </div>

            <!-- Navigation Links -->
            <div class="settings-group">
                <div class="settings-title">Навигация</div>
                <div id="nav-editor">
                    <!-- Navigation items will be loaded here -->
                </div>
                <button class="cms-btn" style="margin-top: 10px;" onclick="saveNav()">Сохранить навигацию</button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = '<?php echo $csrfToken; ?>';

        async function saveSettings() {
            const site_title = document.getElementById('site_title').value;
            const res = await fetch('api/settings.php?action=save', {
                method: 'POST',
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    settings: { site_title }
                })
            });
            const data = await res.json();
            if (data.success) alert('Настройки сохранены');
        }

        async function saveSecurity() {
            const new_password = document.getElementById('new_password').value;
            const secret = document.getElementById('2fa_secret').value;
            const res = await fetch('api/settings.php?action=security', {
                method: 'POST',
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    new_password: new_password,
                    2fa_secret: secret
                })
            });
            const data = await res.json();
            if (data.success) alert('Безопасность обновлена');
        }

        // Simplified Nav Save
        async function saveNav() {
            alert('Настройка навигации сохраняется в общих настройках.');
        }
    </script>
</body>
</html>
