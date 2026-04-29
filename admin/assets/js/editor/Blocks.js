/**
 * ChubbyCMS - Block Definitions
 */

export const ParagraphBlock = {
    render: (container, block, update) => {
        const textarea = document.createElement('textarea');
        textarea.className = 'cms-input block-textarea';
        textarea.placeholder = 'Введите текст...';
        textarea.value = block.content;
        textarea.rows = 3;
        textarea.oninput = () => update({ content: textarea.value });
        container.appendChild(textarea);
    }
};

export const ColumnsBlock = {
    render: (container, block, update, editor) => {
        const columnsContainer = document.createElement('div');
        columnsContainer.className = 'editor-columns-setup';
        columnsContainer.style.display = 'flex';
        columnsContainer.style.gap = '10px';

        if (!block.columns || block.columns.length === 0) {
            block.columns = [{ blocks: [], width: '1' }, { blocks: [], width: '1' }];
        }

        block.columns.forEach((col, idx) => {
            const colDiv = document.createElement('div');
            colDiv.className = 'editor-column-preview';
            colDiv.style.flex = col.width;
            colDiv.style.border = '1px dashed var(--border)';
            colDiv.style.padding = '10px';
            colDiv.style.minHeight = '100px';
            colDiv.innerHTML = `<div class="col-label">Колонка ${idx + 1}</div>`;

            const nestedContainer = document.createElement('div');
            nestedContainer.className = 'nested-blocks-container';
            colDiv.appendChild(nestedContainer);

            // Recursively render blocks in this column
            editor.renderBlocks(col.blocks, nestedContainer);

            const addBtn = document.createElement('button');
            addBtn.className = 'cms-btn-tiny';
            addBtn.textContent = '+ блок';
            addBtn.onclick = () => {
                const type = prompt('Тип блока (paragraph, heading, image):', 'paragraph');
                if (type) {
                    const blockId = 'b' + Math.random().toString(36).substr(2, 9);
                    col.blocks.push({ id: blockId, type: type, content: '', settings: {}, blocks: [] });
                    editor.render();
                    update({ columns: block.columns });
                }
            };

            colDiv.appendChild(addBtn);
            columnsContainer.appendChild(colDiv);
        });

        container.appendChild(columnsContainer);
    }
};

export const ContainerBlock = {
    render: (container, block, update, editor) => {
        const groupDiv = document.createElement('div');
        groupDiv.className = 'group-children';
        groupDiv.style.minHeight = '50px';
        groupDiv.style.border = '1px dashed var(--border)';
        groupDiv.style.padding = '10px';

        if (!block.blocks) block.blocks = [];

        const nestedContainer = document.createElement('div');
        groupDiv.appendChild(nestedContainer);

        editor.renderBlocks(block.blocks, nestedContainer);

        const addBtn = document.createElement('button');
        addBtn.className = 'cms-btn-tiny';
        addBtn.textContent = '+ блок';
        addBtn.onclick = () => {
            const type = prompt('Тип блока (paragraph, heading, image):', 'paragraph');
            if (type) {
                const blockId = 'b' + Math.random().toString(36).substr(2, 9);
                block.blocks.push({ id: blockId, type: type, content: '', settings: {}, blocks: [] });
                editor.render();
                update({ blocks: block.blocks });
            }
        };

        groupDiv.appendChild(addBtn);
        container.appendChild(groupDiv);
    }
};

export const HeadingBlock = {
    render: (container, block, update) => {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'cms-input block-h3';
        input.placeholder = 'Заголовок';
        input.value = block.content;
        input.oninput = () => update({ content: input.value });

        const settings = document.createElement('div');
        settings.className = 'block-settings-inline';
        settings.innerHTML = `
            <select class="cms-select">
                <option value="1" ${block.settings.level == 1 ? 'selected' : ''}>H1</option>
                <option value="2" ${block.settings.level == 2 ? 'selected' : ''}>H2</option>
                <option value="3" ${block.settings.level == 3 || !block.settings.level ? 'selected' : ''}>H3</option>
            </select>
        `;
        settings.querySelector('select').onchange = (e) => update({ settings: { ...block.settings, level: e.target.value } });

        container.appendChild(settings);
        container.appendChild(input);
    }
};

export const ImageBlock = {
    render: (container, block, update) => {
        const preview = document.createElement('div');
        preview.className = 'img-preview-container';
        preview.innerHTML = block.content ? `<img src="${block.content}" class="img-preview">` : '';

        const btn = document.createElement('button');
        btn.className = 'img-upload-btn';
        btn.textContent = 'выбрать медиа';
        btn.onclick = () => window.openMediaPicker((url) => {
            update({ content: url });
            preview.innerHTML = `<img src="${url}" class="img-preview">`;
        });

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'cms-input';
        input.placeholder = 'или вставьте URL';
        input.value = block.content;
        input.oninput = () => {
            update({ content: input.value });
            preview.innerHTML = input.value ? `<img src="${input.value}" class="img-preview">` : '';
        };

        container.appendChild(preview);
        container.appendChild(btn);
        container.appendChild(input);
    }
};

export const ListBlock = {
    render: (container, block, update) => {
        const textarea = document.createElement('textarea');
        textarea.className = 'cms-input block-textarea';
        textarea.placeholder = 'Элементы списка (каждый с новой строки)';
        textarea.value = Array.isArray(block.content) ? block.content.join('\n') : block.content;
        textarea.oninput = () => update({ content: textarea.value.split('\n') });
        container.appendChild(textarea);
    }
};

export const QuoteBlock = {
    render: (container, block, update) => {
        const textarea = document.createElement('textarea');
        textarea.className = 'cms-input block-textarea';
        textarea.placeholder = 'Цитата...';
        textarea.value = block.content;
        textarea.oninput = () => update({ content: textarea.value });
        container.appendChild(textarea);
    }
};
