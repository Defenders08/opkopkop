/**
 * ChubbyCMS - Editor JavaScript
 */

let currentFilename = '';
let blocks = [];
let csrfToken = '';
let mediaPickerCallback = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadCSRFToken();
    loadArticles();
    loadMedia();
    setupEventListeners();
});

// Load CSRF token from config page
async function loadCSRFToken() {
    try {
        const response = await fetch('../index.php');
        const html = await response.text();
        // Token will be set when making requests
        csrfToken = generateCSRF();
    } catch (e) {
        csrfToken = generateCSRF();
    }
}

function generateCSRF() {
    return Math.random().toString(36).substring(2) + Date.now().toString(36);
}

// Setup event listeners
function setupEventListeners() {
    // Toolbar buttons
    document.querySelectorAll('.tool-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            addBlock(btn.dataset.block);
        });
    });
    
    // New article
    document.getElementById('new-article-btn').addEventListener('click', newArticle);
    
    // Save article
    document.getElementById('save-article-btn').addEventListener('click', saveArticle);
    
    // Preview
    document.getElementById('preview-btn').addEventListener('click', previewArticle);
    
    // Delete
    document.getElementById('delete-article-btn').addEventListener('click', deleteArticle);
}

// Add block to editor
function addBlock(type, container = null, isNested = false) {
    const blockId = 'block_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    
    const block = {
        id: blockId,
        type: type,
        content: '',
        blocks: [] // For nested blocks (img_little, group, tab)
    };
    
    if (container) {
        container.push(block);
    } else {
        blocks.push(block);
    }
    
    renderBlock(block, isNested);
}

// Render a single block in the editor
function renderBlock(block, isNested = false) {
    const container = document.getElementById('blocks-container');
    const blockEl = document.createElement('div');
    blockEl.className = 'article-block';
    blockEl.dataset.id = block.id;
    blockEl.dataset.type = block.type;
    
    let contentHtml = '';
    
    switch (block.type) {
        case 'h3':
            contentHtml = `
                <div class="block-header">
                    <span class="block-type-label">H3</span>
                    <div class="block-controls">
                        <button class="block-move-up">↑</button>
                        <button class="block-move-down">↓</button>
                        <button class="block-delete">✕</button>
                    </div>
                </div>
                <div class="block-body">
                    <input type="text" class="cms-input block-content" placeholder="заголовок" value="${escapeHtml(block.content)}">
                </div>
            `;
            break;
            
        case 'text':
            contentHtml = `
                <div class="block-header">
                    <span class="block-type-label">TEXT</span>
                    <div class="block-controls">
                        <button class="block-move-up">↑</button>
                        <button class="block-move-down">↓</button>
                        <button class="block-delete">✕</button>
                    </div>
                </div>
                <div class="block-body">
                    <textarea class="cms-input block-content" placeholder="текст..." rows="3">${escapeHtml(block.content)}</textarea>
                </div>
            `;
            break;
            
        case 'tab':
            contentHtml = `
                <div class="block-header">
                    <span class="block-type-label">TAB</span>
                    <div class="block-controls">
                        <button class="block-move-up">↑</button>
                        <button class="block-move-down">↓</button>
                        <button class="block-delete">✕</button>
                    </div>
                </div>
                <div class="block-body">
                    <textarea class="cms-input block-content" placeholder="цитата..." rows="2">${escapeHtml(block.content)}</textarea>
                    <div class="nested-blocks" data-nested="true"></div>
                    <button class="add-nested-btn" onclick="openBlockPickerForNested('${block.id}')">+ добавить блок внутрь</button>
                </div>
            `;
            break;
            
        case 'ul':
            contentHtml = `
                <div class="block-header">
                    <span class="block-type-label">UL</span>
                    <div class="block-controls">
                        <button class="block-move-up">↑</button>
                        <button class="block-move-down">↓</button>
                        <button class="block-delete">✕</button>
                    </div>
                </div>
                <div class="block-body">
                    <textarea class="cms-input block-list" placeholder="элементы списка (каждый с новой строки)" rows="3">${escapeHtml(Array.isArray(block.content) ? block.content.join('\n') : block.content)}</textarea>
                </div>
            `;
            break;
            
        case 'img':
            contentHtml = `
                <div class="block-header">
                    <span class="block-type-label">IMG</span>
                    <div class="block-controls">
                        <button class="block-move-up">↑</button>
                        <button class="block-move-down">↓</button>
                        <button class="block-delete">✕</button>
                    </div>
                </div>
                <div class="block-body block-img">
                    <div class="img-preview-container">
                        ${block.content ? `<img src="${escapeHtml(block.content)}" class="img-preview">` : ''}
                    </div>
                    <button class="img-upload-btn" onclick="openMediaPicker('${block.id}')">выбрать медиа</button>
                    <input type="text" class="cms-input block-content" placeholder="или вставь URL" value="${escapeHtml(block.content)}" style="margin-top: 8px;">
                </div>
            `;
            break;
            
        case 'img_little':
            contentHtml = `
                <div class="block-header">
                    <span class="block-type-label">IMG_LITTLE</span>
                    <div class="block-controls">
                        <button class="block-move-up">↑</button>
                        <button class="block-move-down">↓</button>
                        <button class="block-delete">✕</button>
                    </div>
                </div>
                <div class="block-body block-img-little">
                    <div class="img-little-content">
                        <div style="flex-shrink: 0;">
                            ${block.image ? `<img src="${escapeHtml(block.image)}" class="img-preview" style="width: 120px; height: 90px;">` : ''}
                            <button class="img-upload-btn" onclick="openMediaPicker('${block.id}', 'image')" style="margin-top: 4px; font-size: 9px;">выбрать</button>
                        </div>
                        <div class="img-little-text-area" style="flex: 1;">
                            <textarea class="cms-input block-text" placeholder="текст справа..." rows="2">${escapeHtml(block.text || '')}</textarea>
                            <div class="nested-blocks" data-nested="true"></div>
                            <button class="add-nested-btn" onclick="openBlockPickerForNested('${block.id}')">+ добавить блок внутрь</button>
                        </div>
                    </div>
                </div>
            `;
            break;
            
        case 'hr':
            contentHtml = `
                <div class="block-header">
                    <span class="block-type-label">HR</span>
                    <div class="block-controls">
                        <button class="block-move-up">↑</button>
                        <button class="block-move-down">↓</button>
                        <button class="block-delete">✕</button>
                    </div>
                </div>
                <div class="block-body" style="padding: 8px; color: var(--text-dim); font-size: 11px;">
                    — разделитель —
                </div>
            `;
            break;
            
        case 'link':
            contentHtml = `
                <div class="block-header">
                    <span class="block-type-label">LINK</span>
                    <div class="block-controls">
                        <button class="block-move-up">↑</button>
                        <button class="block-move-down">↓</button>
                        <button class="block-delete">✕</button>
                    </div>
                </div>
                <div class="block-body">
                    <input type="text" class="cms-input block-url" placeholder="URL" value="${escapeHtml(block.url || '')}" style="margin-bottom: 8px;">
                    <input type="text" class="cms-input block-label" placeholder="текст ссылки" value="${escapeHtml(block.label || block.content)}">
                </div>
            `;
            break;
            
        case 'group':
            contentHtml = `
                <div class="block-header">
                    <span class="block-type-label">GROUP</span>
                    <div class="block-controls">
                        <button class="block-move-up">↑</button>
                        <button class="block-move-down">↓</button>
                        <button class="block-delete">✕</button>
                    </div>
                </div>
                <div class="block-body">
                    <div class="nested-blocks" data-nested="true"></div>
                    <button class="add-nested-btn" onclick="openBlockPickerForNested('${block.id}')">+ добавить блок в группу</button>
                </div>
            `;
            break;
    }
    
    blockEl.innerHTML = contentHtml;
    
    // Add event listeners for controls
    blockEl.querySelector('.block-delete').addEventListener('click', () => {
        deleteBlock(block.id);
    });
    
    blockEl.querySelector('.block-move-up').addEventListener('click', () => {
        moveBlock(block.id, -1);
    });
    
    blockEl.querySelector('.block-move-down').addEventListener('click', () => {
        moveBlock(block.id, 1);
    });
    
    if (isNested) {
        container.appendChild(blockEl);
    } else {
        container.appendChild(blockEl);
    }
}

// Delete block
function deleteBlock(blockId) {
    const blockEl = document.querySelector(`[data-id="${blockId}"]`);
    if (blockEl) {
        blockEl.remove();
        removeBlockFromData(blockId);
    }
}

// Move block up or down
function moveBlock(blockId, direction) {
    const blockEl = document.querySelector(`[data-id="${blockId}"]`);
    if (!blockEl) return;
    
    const container = blockEl.parentElement;
    const siblings = Array.from(container.children);
    const index = siblings.indexOf(blockEl);
    
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= siblings.length) return;
    
    if (direction === -1 && index > 0) {
        container.insertBefore(blockEl, siblings[index - 1]);
    } else if (direction === 1 && index < siblings.length - 1) {
        container.insertBefore(siblings[index + 1], blockEl);
    }
    
    updateBlocksFromDOM();
}

// Remove block from data array
function removeBlockFromData(blockId, arr = null) {
    const array = arr || blocks;
    const index = array.findIndex(b => b.id === blockId);
    if (index !== -1) {
        array.splice(index, 1);
    }
}

// Update blocks data from DOM
function updateBlocksFromDOM() {
    blocks = collectBlocksFromContainer(document.getElementById('blocks-container'));
}

// Collect blocks from a container
function collectBlocksFromContainer(container) {
    const collected = [];
    
    container.querySelectorAll(':scope > .article-block').forEach(blockEl => {
        const block = {
            id: blockEl.dataset.id,
            type: blockEl.dataset.type,
        };
        
        // Get content based on type
        switch (block.type) {
            case 'h3':
            case 'text':
            case 'tab':
                block.content = blockEl.querySelector('.block-content')?.value || '';
                break;
            case 'ul':
                const listText = blockEl.querySelector('.block-list')?.value || '';
                block.content = listText.split('\n').filter(l => l.trim());
                break;
            case 'img':
                block.content = blockEl.querySelector('.block-content')?.value || '';
                break;
            case 'img_little':
                block.image = blockEl.querySelector('.img-preview')?.src || '';
                block.text = blockEl.querySelector('.block-text')?.value || '';
                // Collect nested blocks
                const nestedContainer = blockEl.querySelector('[data-nested="true"]');
                if (nestedContainer) {
                    block.blocks = collectBlocksFromContainer(nestedContainer);
                }
                break;
            case 'link':
                block.url = blockEl.querySelector('.block-url')?.value || '';
                block.label = blockEl.querySelector('.block-label')?.value || '';
                block.content = block.label;
                break;
            case 'group':
            case 'tab':
                const nested = blockEl.querySelector('[data-nested="true"]');
                if (nested) {
                    block.blocks = collectBlocksFromContainer(nested);
                }
                break;
        }
        
        collected.push(block);
    });
    
    return collected;
}

// Open block picker for nested blocks
function openBlockPickerForNested(blockId) {
    const blockEl = document.querySelector(`[data-id="${blockId}"]`);
    const nestedContainer = blockEl.querySelector('[data-nested="true"]');
    
    const picker = document.getElementById('block-picker');
    picker.classList.remove('hidden');
    picker.dataset.targetBlock = blockId;
    picker.dataset.nestedContainer = nestedContainer ? 'true' : 'false';
    
    // Setup picker button clicks
    document.querySelectorAll('#block-picker .picker-btn').forEach(btn => {
        btn.onclick = () => {
            if (nestedContainer) {
                addBlock(btn.dataset.type, [], true);
                // Re-render to attach to correct container
                const lastBlock = blocks[blocks.length - 1];
                nestedContainer.appendChild(blockEl.querySelector('.article-block:last-child'));
                updateBlocksFromDOM();
            }
            picker.classList.add('hidden');
        };
    });
}

// Close block picker
function closeBlockPicker() {
    document.getElementById('block-picker').classList.add('hidden');
}

// Open media picker
function openMediaPicker(blockId, field = 'content') {
    const picker = document.getElementById('media-picker');
    picker.classList.remove('hidden');
    
    mediaPickerCallback = (url) => {
        const blockEl = document.querySelector(`[data-id="${blockId}"]`);
        
        if (blockEl.dataset.type === 'img') {
            blockEl.querySelector('.block-content').value = url;
            const previewContainer = blockEl.querySelector('.img-preview-container');
            previewContainer.innerHTML = `<img src="${escapeHtml(url)}" class="img-preview">`;
        } else if (blockEl.dataset.type === 'img_little') {
            const imgPreview = blockEl.querySelector('.img-preview');
            if (imgPreview) {
                imgPreview.src = url;
                imgPreview.style.width = '120px';
                imgPreview.style.height = '90px';
            } else {
                const previewDiv = document.createElement('div');
                previewDiv.innerHTML = `<img src="${escapeHtml(url)}" class="img-preview" style="width: 120px; height: 90px;">`;
                blockEl.querySelector('.img-upload-btn').before(previewDiv);
            }
        }
        
        picker.classList.add('hidden');
    };
    
    loadMediaPickerGrid();
}

// Close media picker
function closeMediaPicker() {
    document.getElementById('media-picker').classList.add('hidden');
}

// Load media into picker
async function loadMediaPickerGrid() {
    try {
        const response = await fetch('api_media.php?action=list');
        const data = await response.json();
        
        const grid = document.getElementById('media-picker-grid');
        grid.innerHTML = '';
        
        data.media.forEach(item => {
            const el = document.createElement('div');
            el.className = 'media-item';
            el.innerHTML = `
                <img src="${escapeHtml(item.url)}" alt="">
                <button class="media-item-del" onclick="event.stopPropagation()">✕</button>
            `;
            el.addEventListener('click', () => {
                if (mediaPickerCallback) {
                    mediaPickerCallback(item.url);
                }
            });
            grid.appendChild(el);
        });
    } catch (e) {
        console.error('Failed to load media:', e);
    }
}

// New article
function newArticle() {
    currentFilename = '';
    blocks = [];
    document.getElementById('editor-title').value = '';
    document.getElementById('editor-collection').value = '';
    document.getElementById('editor-tab').value = '';
    document.getElementById('blocks-container').innerHTML = '';
    document.getElementById('delete-article-btn').classList.add('hidden');
    document.getElementById('save-notice').classList.add('hidden');
}

// Save article
async function saveArticle() {
    updateBlocksFromDOM();
    
    const title = document.getElementById('editor-title').value || 'без названия';
    const collection = document.getElementById('editor-collection').value;
    const tab = document.getElementById('editor-tab').value;
    
    const data = {
        csrf_token: csrfToken,
        title: title,
        blocks: blocks,
        collection: collection,
        tab: tab
    };
    
    if (currentFilename) {
        data.filename = currentFilename;
    }
    
    try {
        const response = await fetch('api_articles.php?action=save', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentFilename = result.filename;
            document.getElementById('delete-article-btn').classList.remove('hidden');
            document.getElementById('save-notice').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('save-notice').classList.add('hidden');
            }, 3000);
            loadArticles();
        } else {
            alert('ошибка: ' + result.error);
        }
    } catch (e) {
        alert('ошибка сохранения: ' + e.message);
    }
}

// Preview article
function previewArticle() {
    updateBlocksFromDOM();
    window.open('../index.php?article=' + (currentFilename || 'preview'), '_blank');
}

// Delete article
async function deleteArticle() {
    if (!currentFilename) return;
    
    if (!confirm('удалить эту статью?')) return;
    
    try {
        const response = await fetch('api_articles.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                csrf_token: csrfToken,
                filename: currentFilename
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            newArticle();
            loadArticles();
        } else {
            alert('ошибка: ' + result.error);
        }
    } catch (e) {
        alert('ошибка удаления: ' + e.message);
    }
}

// Load articles list
async function loadArticles() {
    try {
        const response = await fetch('api_articles.php?action=list');
        const data = await response.json();
        
        const list = document.getElementById('cms-articles-list');
        list.innerHTML = '';
        
        data.articles.forEach(article => {
            const el = document.createElement('div');
            el.className = 'cms-article-item';
            el.innerHTML = `
                <span class="cms-article-name" onclick="loadArticle('${escapeHtml(article.filename)}')">${escapeHtml(article.title)}</span>
                <button class="del-btn" onclick="loadArticle('${escapeHtml(article.filename)}')">✎</button>
            `;
            list.appendChild(el);
        });
    } catch (e) {
        console.error('Failed to load articles:', e);
    }
}

// Load specific article
async function loadArticle(filename) {
    try {
        const response = await fetch('api_articles.php?action=get&file=' + encodeURIComponent(filename));
        const data = await response.json();
        
        if (data.article) {
            currentFilename = filename;
            document.getElementById('editor-title').value = data.article.title || '';
            document.getElementById('editor-collection').value = data.article.collection || '';
            document.getElementById('editor-tab').value = data.article.tab || '';
            
            blocks = data.article.blocks || [];
            document.getElementById('blocks-container').innerHTML = '';
            
            blocks.forEach(block => {
                renderBlock(block);
            });
            
            document.getElementById('delete-article-btn').classList.remove('hidden');
        }
    } catch (e) {
        console.error('Failed to load article:', e);
    }
}

// Load media sidebar
async function loadMedia() {
    try {
        const response = await fetch('api_media.php?action=list');
        const data = await response.json();
        
        const grid = document.getElementById('media-grid');
        grid.innerHTML = '';
        
        data.media.slice(0, 6).forEach(item => {
            const el = document.createElement('div');
            el.className = 'media-item';
            el.innerHTML = `<img src="${escapeHtml(item.url)}" alt="">`;
            el.addEventListener('click', () => {
                // Insert into current block if any
                const lastBlock = blocks[blocks.length - 1];
                if (lastBlock && lastBlock.type === 'img') {
                    lastBlock.content = item.url;
                    renderBlock(lastBlock);
                }
            });
            grid.appendChild(el);
        });
    } catch (e) {
        console.error('Failed to load media:', e);
    }
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
