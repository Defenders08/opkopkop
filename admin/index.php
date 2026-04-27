<?php
/**
 * ChubbyCMS - Admin Index
 * Main admin panel
 */
require_once '../includes/config.php';
requireLogin();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChubbyCMS - Админка</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="body">
        <div class="header">
            <div class="left">
                <a href="../index.php" class="site-title">defenders08</a>
            </div>
            <div class="right">
                <a href="?logout=1">выйти</a>
            </div>
        </div>
        
        <div class="content" style="margin-top: 40px;">
            <div class="markdown">
                <h1>// ChubbyCMS Админ Панель</h1>
                <p>добро пожаловать в панель управления.</p>
                <br>
                <ul>
                    <li><a href="editor.php">редактор статей</a></li>
                    <li><a href="media.php">управление медиа</a></li>
                    <li><a href="settings.php">настройки</a></li>
                    <li><a href="../index.php" target="_blank">просмотр сайта</a></li>
                </ul>
                <br>
                <hr>
                <p style="font-size: 11px; color: var(--text-dim);">
                    по умолчанию:<br>
                    пароль: admin123<br>
                    2FA код: 123456
                </p>
            </div>
        </div>
        
        <div class="footer">
            <div class="left"></div>
            <div class="right">design by defenders08</div>
        </div>
    </div>
</body>
</html>
