<?php
/**
 * ChubbyCMS - Article Editor API
 * Handles CRUD operations for articles stored as files
 */
require_once '../includes/config.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        listArticles();
        break;
    case 'get':
        getArticle();
        break;
    case 'save':
        saveArticle();
        break;
    case 'delete':
        deleteArticle();
        break;
    case 'collections':
        getCollections();
        break;
    default:
        echo json_encode(['error' => 'invalid action']);
}

function listArticles() {
    $articles = [];
    $files = glob(NOTES_PATH . '/*.json');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if ($data) {
            $articles[] = [
                'filename' => basename($file, '.json'),
                'title' => $data['title'] ?? 'без названия',
                'collection' => $data['collection'] ?? '',
                'tab' => $data['tab'] ?? '',
                'updated' => filemtime($file)
            ];
        }
    }
    
    // Sort by updated time (newest first)
    usort($articles, function($a, $b) {
        return $b['updated'] - $a['updated'];
    });
    
    echo json_encode(['articles' => $articles]);
}

function getArticle() {
    $filename = $_GET['file'] ?? '';
    
    // Security: prevent directory traversal
    $filename = basename($filename);
    $filepath = NOTES_PATH . '/' . $filename . '.json';
    
    if (!file_exists($filepath)) {
        echo json_encode(['error' => 'article not found']);
        return;
    }
    
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    
    if ($data) {
        echo json_encode(['article' => $data]);
    } else {
        echo json_encode(['error' => 'invalid article format']);
    }
}

function saveArticle() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'method not allowed']);
        return;
    }
    
    // Verify CSRF token
    $input = json_decode(file_get_contents('php://input'), true);
    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'invalid csrf token']);
        return;
    }
    
    $title = sanitize($input['title'] ?? 'без названия');
    $blocks = $input['blocks'] ?? [];
    $collection = sanitize($input['collection'] ?? '');
    $tab = sanitize($input['tab'] ?? '');
    $filename = $input['filename'] ?? '';
    
    // Generate filename from title if new article
    if (empty($filename)) {
        $filename = mb_strtolower(preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ\s]/u', '', $title));
        $filename = preg_replace('/\s+/', '-', $filename);
        $filename = substr($filename, 0, 50);
        
        // Check if file exists, add timestamp if so
        $baseFilename = $filename;
        $counter = 1;
        while (file_exists(NOTES_PATH . '/' . $filename . '.json')) {
            $filename = $baseFilename . '-' . $counter;
            $counter++;
        }
    } else {
        $filename = basename($filename);
    }
    
    $articleData = [
        'title' => $title,
        'blocks' => $blocks,
        'collection' => $collection,
        'tab' => $tab,
        'updated' => time()
    ];
    
    $filepath = NOTES_PATH . '/' . $filename . '.json';
    
    if (file_put_contents($filepath, json_encode($articleData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'filename' => $filename]);
    } else {
        echo json_encode(['error' => 'failed to save article']);
    }
}

function deleteArticle() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'invalid csrf token']);
        return;
    }
    
    $filename = basename($input['filename'] ?? '');
    $filepath = NOTES_PATH . '/' . $filename . '.json';
    
    if (file_exists($filepath) && unlink($filepath)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'failed to delete article']);
    }
}

function getCollections() {
    $collections = [];
    $files = glob(NOTES_PATH . '/*.json');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if ($data) {
            $collection = $data['collection'] ?? '';
            $tab = $data['tab'] ?? '';
            
            if (!empty($collection) && !in_array($collection, $collections)) {
                $collections[$collection] = [];
            }
            
            if (!empty($collection) && !empty($tab) && !in_array($tab, $collections[$collection])) {
                $collections[$collection][] = $tab;
            }
        }
    }
    
    echo json_encode(['collections' => $collections]);
}
?>
