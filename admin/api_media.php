<?php
/**
 * ChubbyCMS - Media API
 * Handles file uploads and management
 */
require_once '../includes/config.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        listMedia();
        break;
    case 'upload':
        uploadMedia();
        break;
    case 'delete':
        deleteMedia();
        break;
    default:
        echo json_encode(['error' => 'invalid action']);
}

function listMedia() {
    $media = [];
    $files = scandir(MEDIA_PATH);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filepath = MEDIA_PATH . '/' . $file;
        if (is_file($filepath)) {
            $media[] = [
                'filename' => $file,
                'url' => '/media/' . $file,
                'size' => filesize($filepath),
                'type' => mime_content_type($filepath),
                'created' => filectime($filepath)
            ];
        }
    }
    
    // Sort by created time (newest first)
    usort($media, function($a, $b) {
        return $b['created'] - $a['created'];
    });
    
    echo json_encode(['media' => $media]);
}

function uploadMedia() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'method not allowed']);
        return;
    }
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'invalid csrf token']);
        return;
    }
    
    $uploaded = [];
    
    if (isset($_FILES['files'])) {
        $files = $_FILES['files'];
        $count = is_array($files['name']) ? count($files['name']) : 1;
        
        for ($i = 0; $i < $count; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }
            
            // Security: validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            
            // Allow images, videos, audio, and documents
            $allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                'video/mp4', 'video/webm', 'video/ogg',
                'audio/mpeg', 'audio/ogg', 'audio/wav',
                'application/pdf'
            ];
            
            if (!in_array($mimeType, $allowedTypes)) {
                continue;
            }
            
            // Sanitize filename
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
            $name = time() . '_' . $name;
            
            // Prevent overwriting
            $baseName = pathinfo($name, PATHINFO_FILENAME);
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $counter = 1;
            while (file_exists(MEDIA_PATH . '/' . $name)) {
                $name = $baseName . '_' . $counter . '.' . $extension;
                $counter++;
            }
            
            $destination = MEDIA_PATH . '/' . $name;
            
            if (move_uploaded_file($tmpName, $destination)) {
                $uploaded[] = [
                    'filename' => $name,
                    'url' => '/media/' . $name
                ];
            }
        }
    }
    
    echo json_encode(['success' => true, 'uploaded' => $uploaded]);
}

function deleteMedia() {
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
    $filepath = MEDIA_PATH . '/' . $filename;
    
    if (file_exists($filepath) && unlink($filepath)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'failed to delete file']);
    }
}
?>
