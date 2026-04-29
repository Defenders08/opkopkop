<?php
/**
 * ChubbyCMS - Delete Article
 */
require_once '../includes/config.php';
require_once '../core/Auth.php';
require_once '../core/Content.php';

use Core\Auth;
use Core\Content;

Auth::initSession();
if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $path = $_POST['path'] ?? '';
    $filename = $_POST['filename'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!Auth::verifyCSRFToken($csrfToken)) {
        die('Invalid CSRF token');
    }

    if (empty($filename)) {
        die('Article ID missing');
    }

    $contentEngine = new Content(NOTES_PATH);
    if ($contentEngine->deleteArticle($path, $filename)) {
        header('Location: index.php?status=deleted');
    } else {
        die('Failed to delete article. It may not exist or permission denied.');
    }
    exit;
}

header('Location: index.php');
exit;
