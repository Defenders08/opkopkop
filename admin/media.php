<?php
/**
 * ChubbyCMS - Media Manager
 */
require_once '../includes/config.php';
requireLogin();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    header('Content-Type: application/json');
    
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'invalid csrf token']);
        exit;
    }
    
    $uploaded = [];
    $files = $_FILES['files'];
    $count = is_array($files['name']) ? count($files['name']) : 1;
    
    for ($i = 0; $i < $count; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        
        if ($error !== UPLOAD_ERR_OK) continue;
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            'video/mp4', 'video/webm', 'video/ogg',
            'audio/mpeg', 'audio/ogg', 'audio/wav',
            'application/pdf'
        ];
        
        if (!in_array($mimeType, $allowedTypes)) continue;
        
        // Sanitize filename
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $name = time() . '_' . $name;
        
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $counter = 1;
        while (file_exists(MEDIA_PATH . '/' . $name)) {
            $name = $baseName . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        $destination = MEDIA_PATH . '/' . $name;
        
        if (move_uploaded_file($tmpName, $destination)) {
            $uploaded[] = ['filename' => $name, 'url' => '/media/' . $name];
        }
    }
    
    echo json_encode(['success' => true, 'uploaded' => $uploaded]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChubbyCMS - Медиа</title>
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
                
                <h1>// управление медиа</h1>
                
                <div class="media-upload-zone" id="upload-zone" style="margin-top: 16px;">
                    <input type="file" id="file-input" multiple accept="image/*,video/*,audio/*,.pdf" style="display:none">
                    <div class="upload-hint">
                        перетащи файлы сюда<br>
                        <span>или кликни для выбора</span>
                    </div>
                </div>
                
                <div class="media-grid" id="media-grid" style="margin-top: 20px; grid-template-columns: repeat(4, 1fr);"></div>
            </div>
        </div>
        
        <div class="footer">
            <div class="left"></div>
            <div class="right">design by defenders08</div>
        </div>
    </div>
    
    <script>
        const uploadZone = document.getElementById('upload-zone');
        const fileInput = document.getElementById('file-input');
        const mediaGrid = document.getElementById('media-grid');
        
        // Click to upload
        uploadZone.addEventListener('click', () => fileInput.click());
        
        // File input change
        fileInput.addEventListener('change', handleFiles);
        
        // Drag and drop
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            uploadFiles(files);
        });
        
        function handleFiles() {
            uploadFiles(fileInput.files);
        }
        
        async function uploadFiles(files) {
            if (!files.length) return;
            
            const formData = new FormData();
            for (let i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            formData.append('csrf_token', generateCSRF());
            
            try {
                const response = await fetch('media.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    loadMedia();
                } else {
                    alert('ошибка загрузки: ' + (result.error || 'неизвестная'));
                }
            } catch (e) {
                alert('ошибка: ' + e.message);
            }
        }
        
        async function loadMedia() {
            try {
                const response = await fetch('api_media.php?action=list');
                const data = await response.json();
                
                mediaGrid.innerHTML = '';
                
                data.media.forEach(item => {
                    const el = document.createElement('div');
                    el.className = 'media-item';
                    
                    let preview = '';
                    if (item.type.startsWith('image/')) {
                        preview = `<img src="${escapeHtml(item.url)}" alt="">`;
                    } else if (item.type.startsWith('video/')) {
                        preview = `<video src="${escapeHtml(item.url)}" style="width:100%;height:100%;object-fit:cover;"></video>`;
                    } else {
                        preview = `<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:24px;">📄</div>`;
                    }
                    
                    el.innerHTML = `
                        ${preview}
                        <div class="media-item-name">${escapeHtml(item.filename)}</div>
                        <button class="media-item-del" onclick="deleteMedia('${escapeHtml(item.filename)}', event)">✕</button>
                    `;
                    
                    el.addEventListener('click', (e) => {
                        if (!e.target.classList.contains('media-item-del')) {
                            // Copy URL to clipboard
                            navigator.clipboard.writeText(window.location.origin + item.url);
                        }
                    });
                    
                    mediaGrid.appendChild(el);
                });
            } catch (e) {
                console.error('Failed to load media:', e);
            }
        }
        
        async function deleteMedia(filename, event) {
            event.stopPropagation();
            
            if (!confirm('удалить этот файл?')) return;
            
            try {
                const response = await fetch('api_media.php?action=delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        csrf_token: generateCSRF(),
                        filename: filename
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadMedia();
                } else {
                    alert('ошибка: ' + result.error);
                }
            } catch (e) {
                alert('ошибка: ' + e.message);
            }
        }
        
        function generateCSRF() {
            return Math.random().toString(36).substring(2) + Date.now().toString(36);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Initial load
        loadMedia();
    </script>
</body>
</html>
