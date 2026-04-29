<?php
/**
 * ChubbyCMS - Media API
 */
require_once '../../includes/config.php';
require_once '../../core/Auth.php';
require_once '../../core/Media.php';

use Core\Auth;
use Core\Media;

Auth::initSession();

if (!Auth::isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$mediaEngine = new Media(MEDIA_PATH);
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'list':
        echo json_encode(['media' => $mediaEngine->listFiles()]);
        break;

    case 'upload':
        if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['error' => 'Invalid CSRF token']);
            break;
        }
        if (isset($_FILES['file'])) {
            echo json_encode($mediaEngine->upload($_FILES['file']));
        } else {
            echo json_encode(['error' => 'No file uploaded']);
        }
        break;

    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!Auth::verifyCSRFToken($data['csrf_token'] ?? '')) {
            echo json_encode(['error' => 'Invalid CSRF token']);
            break;
        }
        $success = $mediaEngine->delete($data['filename'] ?? '');
        echo json_encode(['success' => $success]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
