<?php
/**
 * ChubbyCMS - Article Editor
 */
require_once '../includes/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChubbyCMS - Редактор</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .editor-container {
            display: flex;
            gap: 20px;
            padding: 20px;
        }
        .editor-main {
            flex: 1;
            min-width: 0;
        }
        .editor-sidebar {
            width: 250px;
            flex-shrink: 0;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 16px;
            color: var(--text-dim);
        }
        .back-link:hover {
            color: var(--accent);
        }
        .nested-blocks {
            margin-left: 20px;
            border-left: 2px solid var(--border);
            padding-left: 10px;
            margin-top: 8px;
        }
        .img-little-content {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }
        .img-little-text-area {
            flex: 1;
            min-height: 60px;
        }
        .add-nested-btn {
            background: none;
            border: 1px dashed var(--border);
            color: var(--text-dim);
            font-family: var(--font);
            font-size: 10px;
            padding: 4px 8px;
            cursor: pointer;
            margin-top: 8px;
        }
        .add-nested-btn:hover {
            border-color: #777;
            color: var(--text);
        }
    </style>
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
            <div class="editor-container" style="width: 100%;">
                <div class="editor-main">
                    <a href="index.php" class="back-link">← назад к админке</a>
                    
                    <div class="cms-section-title">// редактор статей</div>
                    
                    <div class="editor-meta">
                        <input type="text" id="editor-title" placeholder="название статьи" class="cms-input" style="flex: 1;">
                    </div>
                    
                    <div class="editor-collection-row" style="margin-top: 8px;">
                        <label class="cms-label">подборка:</label>
                        <input type="text" id="editor-collection" placeholder="название подборки" class="cms-input" style="flex: 1; max-width: 200px;">
                        <label class="cms-label" style="margin-left: 12px;">вкладка:</label>
                        <input type="text" id="editor-tab" placeholder="название вкладки" class="cms-input" style="flex: 1; max-width: 150px;">
                    </div>
                    
                    <div class="editor-toolbar" style="margin-top: 16px;">
                        <button class="tool-btn" data-block="h3">[h3]</button>
                        <button class="tool-btn" data-block="text">[¶]</button>
                        <button class="tool-btn" data-block="tab">[tab]</button>
                        <button class="tool-btn" data-block="ul">[ul]</button>
                        <button class="tool-btn" data-block="img">[img]</button>
                        <button class="tool-btn" data-block="img_little">[img_s]</button>
                        <button class="tool-btn" data-block="hr">[—]</button>
                        <button class="tool-btn" data-block="link">[link]</button>
                        <button class="tool-btn" data-block="group">[grp]</button>
                    </div>
                    
                    <div class="block-constructor" id="block-constructor" style="margin-top: 16px;">
                        <div class="blocks-container" id="blocks-container"></div>
                    </div>
                    
                    <div class="editor-actions" style="margin-top: 16px;">
                        <button class="cms-btn cms-btn-save" id="save-article-btn">сохранить</button>
                        <button class="cms-btn cms-btn-preview" id="preview-btn">предпросмотр</button>
                        <button class="cms-btn cms-btn-delete hidden" id="delete-article-btn">удалить</button>
                    </div>
                    
                    <div class="save-notice hidden" id="save-notice">
                        ✓ статья сохранена
                    </div>
                </div>
                
                <div class="editor-sidebar">
                    <div class="cms-section-title">// статьи</div>
                    <div class="cms-articles-list" id="cms-articles-list" style="max-height: 400px; overflow-y: auto;"></div>
                    
                    <button class="cms-btn cms-btn-new" id="new-article-btn" style="margin-top: 12px; width: 100%;">+ новая статья</button>
                    
                    <div class="cms-section-title" style="margin-top: 20px;">// медиа</div>
                    <div class="media-grid" id="media-grid" style="grid-template-columns: repeat(2, 1fr);"></div>
                    <a href="media.php" style="font-size: 10px; color: var(--text-dim); margin-top: 8px; display: block;">управление медиа →</a>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <div class="left"></div>
            <div class="right">design by defenders08</div>
        </div>
    </div>
    
    <!-- Block Picker Modal -->
    <div id="block-picker" class="login-modal hidden">
        <div class="login-box" style="width: 400px;">
            <div class="login-title">выберите блок:</div>
            <div class="picker-grid" style="grid-template-columns: repeat(3, 1fr); margin-top: 12px;">
                <button class="picker-btn" data-type="text">¶ параграф</button>
                <button class="picker-btn" data-type="h3">H заголовок</button>
                <button class="picker-btn" data-type="tab">⌐ цитата</button>
                <button class="picker-btn" data-type="ul">≡ список</button>
                <button class="picker-btn" data-type="img">▣ картинка</button>
                <button class="picker-btn" data-type="img_little">▤ картинка+текст</button>
                <button class="picker-btn" data-type="hr">— разделитель</button>
                <button class="picker-btn" data-type="link">⊕ ссылка</button>
                <button class="picker-btn" data-type="group">▦ группа</button>
            </div>
            <button class="cms-btn" onclick="closeBlockPicker()" style="margin-top: 12px; width: 100%;">отмена</button>
        </div>
    </div>
    
    <!-- Media Picker Modal -->
    <div id="media-picker" class="login-modal hidden">
        <div class="login-box" style="width: 500px; max-height: 80vh; overflow-y: auto;">
            <div class="login-title">выберите медиа:</div>
            <div class="media-grid" id="media-picker-grid" style="margin-top: 12px;"></div>
            <button class="cms-btn" onclick="closeMediaPicker()" style="margin-top: 12px; width: 100%;">отмена</button>
        </div>
    </div>
    
    <script src="editor.js"></script>
</body>
</html>
