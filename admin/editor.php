<?php
/**
 * ChubbyCMS - Article Editor
 */
require_once '../includes/config.php';
require_once '../core/Auth.php';

use Core\Auth;

Auth::initSession();
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChubbyCMS - Редактор</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .editor-container { display: flex; gap: 20px; padding: 20px; }
        .editor-main { flex: 1; min-width: 0; }
        .editor-sidebar { width: 250px; flex-shrink: 0; }
        .block-constructor { margin-top: 16px; border: 1px solid var(--border); padding: 10px; min-height: 400px; background: var(--bg); }
        .editor-toolbar { position: sticky; top: 0; z-index: 10; background: var(--bg); padding: 10px 0; border-bottom: 1px solid var(--border); display: flex; gap: 5px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="body">
        <div class="header">
            <div class="left"><a href="index.php" class="site-title">// ChubbyCMS</a></div>
            <div class="right">
                <button class="cms-btn cms-btn-save" id="save-btn">Сохранить</button>
                <a href="?logout=1">Выйти</a>
            </div>
        </div>

        <div class="editor-container">
            <div class="editor-main">
                <div class="editor-meta" style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <input type="text" id="article-title" class="cms-input" placeholder="Заголовок статьи" style="flex: 2;">
                    <input type="text" id="article-path" class="cms-input" placeholder="категория/подкатегория" style="flex: 1;">
                </div>

                <div class="editor-toolbar">
                    <button class="tool-btn" data-type="paragraph">¶ Текст</button>
                    <button class="tool-btn" data-type="heading">H Заголовок</button>
                    <button class="tool-btn" data-type="image">▣ Картинка</button>
                    <button class="tool-btn" data-type="list">≡ Список</button>
                    <button class="tool-btn" data-type="quote">⌐ Цитата</button>
                    <button class="tool-btn" data-type="columns">▦ Колонки</button>
                    <button class="tool-btn" data-type="container">◇ Контейнер</button>
                </div>

                <div id="editor-container" class="block-constructor"></div>
            </div>

            <div class="editor-sidebar">
                <div class="cms-section-title">// Статьи</div>
                <div id="articles-list" class="cms-articles-list"></div>
                <button class="cms-btn cms-btn-new" id="new-article" style="width: 100%; margin-top: 10px;">+ Новая статья</button>
            </div>
        </div>
    </div>

    <!-- Media Picker Modal -->
    <div id="media-picker" class="login-modal hidden">
        <div class="login-box" style="width: 600px; max-height: 80vh; overflow-y: auto;">
            <div class="login-title">Выберите медиа</div>
            <div id="media-grid" class="media-grid" style="margin-top: 10px;"></div>
            <button class="cms-btn" style="width: 100%; margin-top: 10px;" onclick="closeMediaPicker()">Отмена</button>
        </div>
    </div>

    <!-- Block Picker Modal -->
    <div id="block-picker" class="login-modal hidden">
        <div class="login-box" style="width: 400px;">
            <div class="login-title">Добавить блок</div>
            <div class="picker-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                <button class="cms-btn" onclick="selectBlockType('paragraph')">Текст</button>
                <button class="cms-btn" onclick="selectBlockType('heading')">Заголовок</button>
                <button class="cms-btn" onclick="selectBlockType('image')">Картинка</button>
                <button class="cms-btn" onclick="selectBlockType('list')">Список</button>
                <button class="cms-btn" onclick="selectBlockType('quote')">Цитата</button>
                <button class="cms-btn" onclick="selectBlockType('columns')">Колонки</button>
                <button class="cms-btn" onclick="selectBlockType('container')">Контейнер</button>
            </div>
            <button class="cms-btn" style="width: 100%; margin-top: 20px;" onclick="closeBlockPicker()">Отмена</button>
        </div>
    </div>

    <script type="module">
        import { Editor } from './assets/js/editor/Editor.js';
        import { ParagraphBlock, HeadingBlock, ImageBlock, ListBlock, QuoteBlock, ColumnsBlock, ContainerBlock } from './assets/js/editor/Blocks.js';

        const editor = new Editor('editor-container');
        editor.registerBlock('paragraph', ParagraphBlock);
        editor.registerBlock('heading', HeadingBlock);
        editor.registerBlock('image', ImageBlock);
        editor.registerBlock('list', ListBlock);
        editor.registerBlock('quote', QuoteBlock);
        editor.registerBlock('columns', ColumnsBlock);
        editor.registerBlock('container', ContainerBlock);

        const csrfToken = '<?php echo Auth::generateCSRFToken(); ?>';
        let currentArticle = null;

        // Toolbar
        document.querySelectorAll('.tool-btn').forEach(btn => {
            btn.onclick = () => editor.addBlock(btn.dataset.type);
        });

        // Save
        document.getElementById('save-btn').onclick = async () => {
            const title = document.getElementById('article-title').value;
            const path = document.getElementById('article-path').value;
            const blocks = editor.getBlocks();

            const res = await fetch('api/articles.php?action=save', {
                method: 'POST',
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    title, path, blocks,
                    filename: currentArticle ? currentArticle.filename : null
                })
            });
            const result = await res.json();
            if (result.success) {
                alert('Сохранено!');
                loadArticles();
            } else {
                alert('Ошибка: ' + result.error);
            }
        };

        // Load Articles
        async function loadArticles() {
            const res = await fetch('api/articles.php?action=list');
            const data = await res.json();
            const list = document.getElementById('articles-list');
            list.innerHTML = '';
            data.articles.forEach(art => {
                const item = document.createElement('div');
                item.className = 'cms-article-item';
                item.innerHTML = `<span class="cms-article-name">${art.path}/${art.filename}</span>`;
                item.onclick = () => loadArticle(art);
                list.appendChild(item);
            });
        }

        async function loadArticle(art) {
            currentArticle = art;
            document.getElementById('article-title').value = art.title;
            document.getElementById('article-path').value = art.path;
            editor.setBlocks(art.blocks || []);
        }

        document.getElementById('new-article').onclick = () => {
            currentArticle = null;
            document.getElementById('article-title').value = '';
            document.getElementById('article-path').value = '';
            editor.setBlocks([]);
        };

        // Media Picker
        let mediaCallback = null;
        window.openMediaPicker = (callback) => {
            mediaCallback = callback;
            document.getElementById('media-picker').classList.remove('hidden');
            loadMedia();
        };

        window.closeMediaPicker = () => {
            document.getElementById('media-picker').classList.add('hidden');
        };

        // Block Picker
        let blockCallback = null;
        window.openBlockPicker = (callback) => {
            blockCallback = callback;
            document.getElementById('block-picker').classList.remove('hidden');
        };

        window.closeBlockPicker = () => {
            document.getElementById('block-picker').classList.add('hidden');
        };

        window.selectBlockType = (type) => {
            if (blockCallback) blockCallback(type);
            closeBlockPicker();
        };

        async function loadMedia() {
            const res = await fetch('api/media.php?action=list');
            const data = await res.json();
            const grid = document.getElementById('media-grid');
            grid.innerHTML = '';
            data.media.forEach(m => {
                const img = document.createElement('img');
                img.src = m.url;
                img.className = 'media-item';
                img.onclick = () => {
                    if (mediaCallback) mediaCallback(m.url);
                    closeMediaPicker();
                };
                grid.appendChild(img);
            });
        }

        loadArticles();
    </script>
</body>
</html>
