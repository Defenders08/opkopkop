/**
 * ChubbyCMS - Core Block Editor
 * Vanilla JS ES6+ Modular Editor
 */

export class Editor {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.blocks = [];
        this.blockRegistry = {};
        this.onUpdate = null;

        this.setupDragAndDrop();
    }

    registerBlock(type, config) {
        this.blockRegistry[type] = config;
    }

    addBlock(type, data = {}, index = null) {
        const blockId = 'b' + Math.random().toString(36).substr(2, 9);
        const block = {
            id: blockId,
            type: type,
            content: data.content || '',
            settings: data.settings || {},
            blocks: data.blocks || [] // For nested blocks
        };

        if (index !== null) {
            this.blocks.splice(index, 0, block);
        } else {
            this.blocks.push(block);
        }

        this.render();
        if (this.onUpdate) this.onUpdate(this.blocks);
        return blockId;
    }

    deleteBlock(id) {
        this.deleteBlockFromArray(id, this.blocks);
    }

    deleteBlockFromArray(id, array) {
        const index = array.findIndex(b => b.id === id);
        if (index !== -1) {
            array.splice(index, 1);
        } else {
            // Search in nested blocks
            array.forEach(b => {
                if (b.blocks) this.deleteBlockFromArray(id, b.blocks);
                if (b.columns) b.columns.forEach(col => this.deleteBlockFromArray(id, col.blocks));
            });
        }
        this.render();
        if (this.onUpdate) this.onUpdate(this.blocks);
    }

    moveBlock(id, direction) {
        this.moveBlockInArray(id, this.blocks, direction);
    }

    moveBlockInArray(id, array, direction) {
        const index = array.findIndex(b => b.id === id);
        if (index !== -1) {
            const newIndex = index + direction;
            if (newIndex >= 0 && newIndex < array.length) {
                const [removed] = array.splice(index, 1);
                array.splice(newIndex, 0, removed);
            }
        } else {
            // Search in nested blocks
            array.forEach(b => {
                if (b.blocks) this.moveBlockInArray(id, b.blocks, direction);
                if (b.columns) b.columns.forEach(col => this.moveBlockInArray(id, col.blocks, direction));
            });
        }
        this.render();
        if (this.onUpdate) this.onUpdate(this.blocks);
    }

    setBlocks(blocks) {
        this.blocks = blocks;
        this.render();
    }

    getBlocks() {
        return this.blocks;
    }

    render() {
        this.container.innerHTML = '';
        this.renderBlocks(this.blocks, this.container);
    }

    renderBlocks(blocks, container) {
        blocks.forEach((block) => {
            const blockEl = this.createBlockElement(block, blocks);
            container.appendChild(blockEl);
        });
    }

    createBlockElement(block, parentArray) {
        const config = this.blockRegistry[block.type];
        if (!config) return document.createElement('div');

        const wrapper = document.createElement('div');
        wrapper.className = 'article-block';
        wrapper.dataset.id = block.id;
        wrapper.draggable = true;

        const header = document.createElement('div');
        header.className = 'block-header';
        header.innerHTML = `
            <span class="block-type-label">${block.type.toUpperCase()}</span>
            <div class="block-controls">
                <button class="block-ctrl-btn move-up">↑</button>
                <button class="block-ctrl-btn move-down">↓</button>
                <button class="block-ctrl-btn remove">✕</button>
            </div>
        `;

        const body = document.createElement('div');
        body.className = 'block-body';

        // Render the block's specific UI
        config.render(body, block, (newData) => {
            Object.assign(block, newData);
            if (this.onUpdate) this.onUpdate(this.blocks);
        }, this); // Pass editor instance for recursive rendering

        wrapper.appendChild(header);
        wrapper.appendChild(body);

        // Event listeners
        header.querySelector('.move-up').onclick = (e) => {
            e.stopPropagation();
            this.moveBlockInArray(block.id, parentArray, -1);
        };
        header.querySelector('.move-down').onclick = (e) => {
            e.stopPropagation();
            this.moveBlockInArray(block.id, parentArray, 1);
        };
        header.querySelector('.remove').onclick = (e) => {
            e.stopPropagation();
            this.deleteBlockFromArray(block.id, parentArray);
        };

        wrapper.ondragstart = (e) => {
            e.dataTransfer.setData('blockId', block.id);
            wrapper.classList.add('dragging');
        };

        wrapper.ondragend = () => {
            wrapper.classList.remove('dragging');
        };

        return wrapper;
    }

    setupDragAndDrop() {
        this.container.ondragover = (e) => {
            e.preventDefault();
            const draggingEl = document.querySelector('.dragging');
            const afterElement = this.getDragAfterElement(this.container, e.clientY);
            if (afterElement == null) {
                this.container.appendChild(draggingEl);
            } else {
                this.container.insertBefore(draggingEl, afterElement);
            }
        };

        this.container.ondrop = (e) => {
            e.preventDefault();
            // Reorder this.blocks based on DOM
            const newOrder = [];
            this.container.querySelectorAll('.article-block').forEach(el => {
                const id = el.dataset.id;
                const block = this.blocks.find(b => b.id === id);
                if (block) newOrder.push(block);
            });
            this.blocks = newOrder;
            if (this.onUpdate) this.onUpdate(this.blocks);
        };
    }

    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.article-block:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
}
