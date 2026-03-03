/**
 * Advanced Page Builder JavaScript
 * NM-DigitalHUB Page Builder System
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page Builder initialized');

    // Initialize drag and drop
    initializeDragDrop();

    // Initialize component library
    initializeComponentLibrary();

    // Initialize toolbar actions
    initializeToolbar();
});

/**
 * Initialize drag and drop functionality
 */
function initializeDragDrop() {
    const canvas = document.querySelector('.builder-canvas');
    const components = document.querySelectorAll('.component-item');

    if (!canvas) return;

    // Add drop zone functionality
    canvas.addEventListener('dragover', function(e) {
        e.preventDefault();
        canvas.classList.add('drag-over');
    });

    canvas.addEventListener('dragleave', function(e) {
        canvas.classList.remove('drag-over');
    });

    canvas.addEventListener('drop', function(e) {
        e.preventDefault();
        canvas.classList.remove('drag-over');

        const componentType = e.dataTransfer.getData('text/plain');
        addComponentToCanvas(componentType, e.clientX, e.clientY);
    });
}

/**
 * Initialize component library with drag functionality
 */
function initializeComponentLibrary() {
    const components = document.querySelectorAll('[data-component-type]');

    components.forEach(component => {
        component.draggable = true;

        component.addEventListener('dragstart', function(e) {
            const componentType = this.getAttribute('data-component-type');
            e.dataTransfer.setData('text/plain', componentType);
            this.classList.add('dragging');
        });

        component.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
        });

        // Click to add component
        component.addEventListener('click', function(e) {
            const componentType = this.getAttribute('data-component-type');
            addComponentToCanvas(componentType);
        });
    });
}

/**
 * Add component to canvas
 */
function addComponentToCanvas(componentType, x = null, y = null) {
    const canvas = document.querySelector('.builder-canvas');
    const emptyState = canvas.querySelector('.text-center');

    // Remove empty state if exists
    if (emptyState && emptyState.textContent.includes('אין רכיבים')) {
        emptyState.remove();
    }

    // Create component element
    const componentElement = createComponentElement(componentType);

    // Position component if coordinates provided
    if (x !== null && y !== null) {
        const rect = canvas.getBoundingClientRect();
        componentElement.style.position = 'absolute';
        componentElement.style.left = (x - rect.left) + 'px';
        componentElement.style.top = (y - rect.top) + 'px';
    }

    // Add to canvas
    canvas.appendChild(componentElement);

    // Show success notification
    showNotification('success', `רכיב ${getComponentName(componentType)} נוסף בהצלחה!`);

    console.log(`Added component: ${componentType}`);
}

/**
 * Create component element based on type
 */
function createComponentElement(type) {
    const div = document.createElement('div');
    div.className = 'page-component border-2 border-gray-300 rounded-lg p-4 mb-4 relative group hover:border-blue-500 transition-colors';
    div.setAttribute('data-component-id', generateId());
    div.setAttribute('data-component-type', type);

    // Add resize handles
    div.innerHTML = `
        <div class="component-controls absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button class="edit-component bg-blue-500 text-white p-1 rounded mr-1 text-xs">
                ✏️
            </button>
            <button class="delete-component bg-red-500 text-white p-1 rounded text-xs">
                🗑️
            </button>
        </div>
        <div class="component-content">
            ${getComponentContent(type)}
        </div>
    `;

    // Add event listeners
    addComponentEventListeners(div);

    return div;
}

/**
 * Get component content based on type
 */
function getComponentContent(type) {
    const components = {
        'text': `
            <div class="text-component">
                <h3 class="text-lg font-medium mb-2">רכיב טקסט</h3>
                <p class="text-gray-600">זהו רכיב טקסט לדוגמה. ניתן לערוך את התוכן בלחיצה על כפתור העריכה.</p>
            </div>
        `,
        'image': `
            <div class="image-component text-center">
                <div class="bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg p-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">לחץ לטעינת תמונה</p>
                </div>
            </div>
        `,
        'button': `
            <div class="button-component text-center">
                <button class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                    לחץ כאן
                </button>
            </div>
        `,
        'video': `
            <div class="video-component">
                <div class="bg-black rounded-lg aspect-video flex items-center justify-center">
                    <div class="text-white text-center">
                        <svg class="mx-auto h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1M9 16v-2a4 4 0 118 0v2"></path>
                        </svg>
                        <p>רכיב וידאו</p>
                        <p class="text-sm text-gray-300">לחץ לעריכה</p>
                    </div>
                </div>
            </div>
        `
    };

    return components[type] || '<div>רכיב לא ידוע</div>';
}

/**
 * Add event listeners to component
 */
function addComponentEventListeners(element) {
    const editBtn = element.querySelector('.edit-component');
    const deleteBtn = element.querySelector('.delete-component');

    if (editBtn) {
        editBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            editComponent(element);
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            deleteComponent(element);
        });
    }

    // Make component draggable within canvas
    element.draggable = true;
    element.addEventListener('dragstart', function(e) {
        e.dataTransfer.setData('text/plain', 'move-component');
        this.classList.add('dragging');
    });

    element.addEventListener('dragend', function(e) {
        this.classList.remove('dragging');
    });
}

/**
 * Edit component
 */
function editComponent(element) {
    const componentType = element.getAttribute('data-component-type');
    showNotification('info', `עריכת רכיב ${getComponentName(componentType)}`);

    // Here you would open a modal or inline editor
    console.log('Edit component:', componentType);
}

/**
 * Delete component
 */
function deleteComponent(element) {
    const componentType = element.getAttribute('data-component-type');

    if (confirm(`האם אתה בטוח שברצונך למחוק את רכיב ${getComponentName(componentType)}?`)) {
        element.remove();
        showNotification('success', `רכיב ${getComponentName(componentType)} נמחק בהצלחה`);

        // Check if canvas is empty and show empty state
        const canvas = document.querySelector('.builder-canvas');
        const components = canvas.querySelectorAll('.page-component');

        if (components.length === 0) {
            showEmptyState(canvas);
        }
    }
}

/**
 * Show empty state in canvas
 */
function showEmptyState(canvas) {
    const emptyState = document.createElement('div');
    emptyState.className = 'text-center';
    emptyState.innerHTML = `
        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
            אין רכיבים
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            התחל בהוספת רכיבים לעמוד שלך
        </p>
    `;

    canvas.appendChild(emptyState);
}

/**
 * Initialize toolbar actions
 */
function initializeToolbar() {
    // Save button
    const saveBtn = document.querySelector('[data-action="save"]');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            savePageBuilder();
        });
    }

    // Preview button
    const previewBtn = document.querySelector('[data-action="preview"]');
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            openPreview();
        });
    }

    // Add component button
    const addBtn = document.querySelector('[data-action="add-component"]');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            showComponentLibrary();
        });
    }
}

/**
 * Save page builder data
 */
function savePageBuilder() {
    const canvas = document.querySelector('.builder-canvas');
    const components = canvas.querySelectorAll('.page-component');

    const pageData = {
        components: Array.from(components).map(component => ({
            id: component.getAttribute('data-component-id'),
            type: component.getAttribute('data-component-type'),
            position: {
                x: component.style.left || '0px',
                y: component.style.top || '0px'
            },
            content: component.querySelector('.component-content').innerHTML
        }))
    };

    console.log('Saving page data:', pageData);

    // Here you would send the data to the server
    // For now, just show success notification
    showNotification('success', 'הדף נשמר בהצלחה! 💾');
}

/**
 * Open preview
 */
function openPreview() {
    // This would open the page in preview mode
    showNotification('info', 'פותח תצוגה מקדימה...');
    console.log('Opening preview');
}

/**
 * Show component library
 */
function showComponentLibrary() {
    const library = document.querySelector('.component-library');
    if (library) {
        library.scrollIntoView({ behavior: 'smooth' });
        library.classList.add('highlight');
        setTimeout(() => {
            library.classList.remove('highlight');
        }, 2000);
    }
}

/**
 * Utility functions
 */
function getComponentName(type) {
    const names = {
        'text': 'טקסט',
        'image': 'תמונה',
        'button': 'כפתור',
        'video': 'וידאו'
    };
    return names[type] || type;
}

function generateId() {
    return 'component_' + Math.random().toString(36).substr(2, 9);
}

function showNotification(type, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${getNotificationClass(type)}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function getNotificationClass(type) {
    const classes = {
        'success': 'bg-green-500 text-white',
        'error': 'bg-red-500 text-white',
        'info': 'bg-blue-500 text-white',
        'warning': 'bg-yellow-500 text-black'
    };
    return classes[type] || 'bg-gray-500 text-white';
}

// CSS for drag and drop effects
const style = document.createElement('style');
style.textContent = `
    .drag-over {
        border-color: #3b82f6 !important;
        background-color: rgba(59, 130, 246, 0.1) !important;
    }

    .dragging {
        opacity: 0.5;
        transform: scale(0.95);
    }

    .highlight {
        animation: pulse 1s ease-in-out;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }

    .page-component {
        transition: all 0.2s ease;
    }

    .page-component:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .component-controls button {
        transition: all 0.2s ease;
    }

    .component-controls button:hover {
        transform: scale(1.1);
    }

    .notification {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);