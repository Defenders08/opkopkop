<?php
/**
 * ChubbyCMS - Media Management Module
 */

namespace Core;

class Media {
    private $uploadPath;
    private $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm',
        'audio/mpeg',
        'application/pdf'
    ];

    public function __construct($uploadPath) {
        $this->uploadPath = rtrim($uploadPath, '/');
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    public function listFiles() {
        $files = [];
        foreach (scandir($this->uploadPath) as $file) {
            if ($file === '.' || $file === '..') continue;

            $filepath = $this->uploadPath . '/' . $file;
            if (is_file($filepath)) {
                $files[] = [
                    'name' => $file,
                    'url' => '/content/uploads/' . $file,
                    'size' => filesize($filepath),
                    'type' => mime_content_type($filepath),
                    'date' => filemtime($filepath)
                ];
            }
        }

        usort($files, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        return $files;
    }

    public function upload($fileInfo) {
        if (!isset($fileInfo['error']) || is_array($fileInfo['error'])) {
            return ['error' => 'invalid upload'];
        }

        if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'upload error: ' . $fileInfo['error']];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($fileInfo['tmp_name']);

        if (!in_array($mime, $this->allowedTypes)) {
            return ['error' => 'unsupported file type: ' . $mime];
        }

        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileInfo['name']);
        $name = time() . '_' . $name;

        $destination = $this->uploadPath . '/' . $name;

        if (move_uploaded_file($fileInfo['tmp_name'], $destination)) {
            return [
                'success' => true,
                'name' => $name,
                'url' => '/content/uploads/' . $name
            ];
        }

        return ['error' => 'failed to move uploaded file'];
    }

    public function delete($filename) {
        $filepath = $this->uploadPath . '/' . basename($filename);
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}
