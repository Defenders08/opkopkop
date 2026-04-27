<?php
/**
 * ChubbyCMS - Settings Page
 */
require_once '../includes/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChubbyCMS - Настройки</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="body">
        <div class="header">
            <div class="left">
                <a href="index.php" class="site-title">defenders08</a>
            </div>
            <div class="right">
                <a href="../index.php" target="_blank">просмотр</a>
                <a href="?logout=1">выйти</a>
            </div>
        </div>
        
        <div class="content" style="margin-top: 20px;">
            <div class="markdown" style="width: 100%;">
                <a href="index.php" style="display: inline-block; margin-bottom: 16px; color: var(--text-dim);">← назад к админке</a>
                
                <h1>// настройки</h1>
                
                <div class="cms-section-title" style="margin-top: 20px;">// безопасность</div>
                
                <div class="settings-group" style="margin-top: 12px;">
                    <label class="cms-label">текущий пароль</label>
                    <input type="password" id="current-password" class="cms-input" placeholder="текущий пароль">
                </div>
                
                <div class="settings-group" style="margin-top: 12px;">
                    <label class="cms-label">новый пароль</label>
                    <input type="password" id="new-password" class="cms-input" placeholder="новый пароль">
                </div>
                
                <div class="settings-group" style="margin-top: 12px;">
                    <label class="cms-label">повторить пароль</label>
                    <input type="password" id="confirm-password" class="cms-input" placeholder="повторить пароль">
                </div>
                
                <button class="cms-btn cms-btn-save" id="change-password-btn" style="margin-top: 12px;">изменить пароль</button>
                
                <div class="cms-section-title" style="margin-top: 24px;">// 2FA (двухфакторная аутентификация)</div>
                
                <div class="settings-group" style="margin-top: 12px;">
                    <label class="cms-label">2FA код (для входа после пароля)</label>
                    <input type="text" id="2fa-secret" class="cms-input" placeholder="123456" maxlength="32">
                    <p style="font-size: 10px; color: var(--text-dim); margin-top: 8px;">
                        текущий код: <strong id="current-2fa">******</strong>
                    </p>
                </div>
                
                <button class="cms-btn cms-btn-save" id="change-2fa-btn" style="margin-top: 12px;">изменить 2FA код</button>
                
                <div class="cms-section-title" style="margin-top: 24px;">// данные</div>
                
                <button class="cms-btn" id="export-btn" style="margin-top: 12px;">экспорт всех данных (JSON)</button>
                
                <div style="margin-top: 8px;">
                    <button class="cms-btn cms-btn-delete" onclick="window.location.href='?logout=1'">выйти из CMS</button>
                </div>
                
                <div id="message" class="save-notice hidden" style="margin-top: 16px;"></div>
            </div>
        </div>
        
        <div class="footer">
            <div class="left"></div>
            <div class="right">design by defenders08</div>
        </div>
    </div>
    
    <script>
        // Load current settings
        loadSettings();
        
        async function loadSettings() {
            try {
                const response = await fetch('api_settings.php');
                const data = await response.json();
                
                document.getElementById('current-2fa').textContent = data.has_2fa ? 'установлен' : 'не установлен';
            } catch (e) {
                console.error('Failed to load settings:', e);
            }
        }
        
        // Change password
        document.getElementById('change-password-btn').addEventListener('click', async () => {
            const current = document.getElementById('current-password').value;
            const newPass = document.getElementById('new-password').value;
            const confirm = document.getElementById('confirm-password').value;
            
            if (!current || !newPass || !confirm) {
                showMessage('заполни все поля', 'error');
                return;
            }
            
            if (newPass !== confirm) {
                showMessage('пароли не совпадают', 'error');
                return;
            }
            
            if (newPass.length < 6) {
                showMessage('пароль должен быть не менее 6 символов', 'error');
                return;
            }
            
            try {
                const response = await fetch('api_settings.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        csrf_token: generateCSRF(),
                        action: 'change_password',
                        current_password: current,
                        new_password: newPass,
                        confirm_password: confirm
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('пароль изменён', 'success');
                    document.getElementById('current-password').value = '';
                    document.getElementById('new-password').value = '';
                    document.getElementById('confirm-password').value = '';
                } else {
                    showMessage(result.error || 'ошибка', 'error');
                }
            } catch (e) {
                showMessage('ошибка: ' + e.message, 'error');
            }
        });
        
        // Change 2FA
        document.getElementById('change-2fa-btn').addEventListener('click', async () => {
            const secret = document.getElementById('2fa-secret').value;
            
            if (!secret || secret.length < 4) {
                showMessage('2FA код должен быть не менее 4 символов', 'error');
                return;
            }
            
            try {
                const response = await fetch('api_settings.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        csrf_token: generateCSRF(),
                        action: 'change_2fa',
                        '2fa_secret': secret
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('2FA код изменён', 'success');
                    document.getElementById('2fa-secret').value = '';
                    loadSettings();
                } else {
                    showMessage(result.error || 'ошибка', 'error');
                }
            } catch (e) {
                showMessage('ошибка: ' + e.message, 'error');
            }
        });
        
        // Export data
        document.getElementById('export-btn').addEventListener('click', async () => {
            try {
                const articlesResponse = await fetch('api_articles.php?action=list');
                const articlesData = await articlesResponse.json();
                
                const mediaResponse = await fetch('api_media.php?action=list');
                const mediaData = await mediaResponse.json();
                
                const settingsResponse = await fetch('api_settings.php');
                const settingsData = await settingsResponse.json();
                
                const exportData = {
                    exported_at: new Date().toISOString(),
                    articles: articlesData.articles || [],
                    media: mediaData.media || [],
                    settings: settingsData
                };
                
                const blob = new Blob([JSON.stringify(exportData, null, 2)], {type: 'application/json'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'chubbycms-export-' + Date.now() + '.json';
                a.click();
                URL.revokeObjectURL(url);
                
                showMessage('данные экспортированы', 'success');
            } catch (e) {
                showMessage('ошибка экспорта: ' + e.message, 'error');
            }
        });
        
        function showMessage(text, type) {
            const msg = document.getElementById('message');
            msg.textContent = text;
            msg.className = 'save-notice ' + (type === 'error' ? 'error' : '');
            msg.classList.remove('hidden');
            msg.style.color = type === 'error' ? '#c47575' : '#a0a060';
            setTimeout(() => msg.classList.add('hidden'), 3000);
        }
        
        function generateCSRF() {
            return Math.random().toString(36).substring(2) + Date.now().toString(36);
        }
    </script>
</body>
</html>
