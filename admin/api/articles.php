<?php
/**
 * ChubbyCMS - Articles API
 */
require_once '../../includes/config.php';
require_once '../../core/Auth.php';
require_once '../../core/Content.php';

use Core\Auth;
use Core\Content;

Auth::initSession();

if (!Auth::isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$contentEngine = new Content(NOTES_PATH);
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'list':
        echo json_encode(['articles' => $contentEngine->getArticles()]);
        break;

    case 'get':
        $path = $_GET['path'] ?? '';
        $file = $_GET['file'] ?? '';
        $filepath = NOTES_PATH . '/' . trim($path, '/') . '/' . basename($file, '.md') . '.md';
        echo json_encode(['article' => $contentEngine->readArticle($filepath)]);
        break;

    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!Auth::verifyCSRFToken($data['csrf_token'] ?? '')) {
            echo json_encode(['error' => 'Invalid CSRF token']);
            break;
        }

        $path = sanitize($data['path'] ?? '');
        $filename = sanitize($data['filename'] ?? '');
        if (empty($filename)) {
            $filename = mb_strtolower(preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ\s]/u', '', $data['title'] ?? 'untitled'));
            $filename = preg_replace('/\s+/', '-', $filename) . '.md';
        } else {
            $filename = basename($filename, '.md') . '.md';
        }

        // Sanitize article data before saving
        $articleData = [
            'title' => sanitize($data['title'] ?? 'Untitled'),
            'path' => $path,
            'blocks' => $data['blocks'] ?? [], // Should be sanitized if containing HTML, but visual editor blocks are mostly JSON
            'updated' => time()
        ];

        $success = $contentEngine->saveArticle($path, $filename, $articleData);
        echo json_encode(['success' => $success, 'filename' => $filename]);
        break;

    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!Auth::verifyCSRFToken($data['csrf_token'] ?? '')) {
            echo json_encode(['error' => 'Invalid CSRF token']);
            break;
        }
        $success = $contentEngine->deleteArticle($data['path'] ?? '', $data['filename'] ?? '');
        echo json_encode(['success' => $success]);
        break;

    case 'categories':
        echo json_encode(['categories' => $contentEngine->getCategories()]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
