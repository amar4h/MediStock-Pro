import Alpine from 'alpinejs';

// Import Alpine components
import { invoiceScanner } from './alpine/invoice-scanner';
import { posBilling } from './alpine/pos-billing';
import { purchaseForm } from './alpine/purchase-form';

// ============================================================
// Global Alpine Stores
// ============================================================

/**
 * Notification Store
 * Manages toast notifications across the application
 */
Alpine.store('notification', {
    items: [],
    _counter: 0,

    /**
     * Show a toast notification
     * @param {string} message - The notification message
     * @param {string} type - success | error | warning | info
     * @param {number} duration - Auto-dismiss after ms (0 = manual dismiss)
     */
    show(message, type = 'success', duration = 4000) {
        const id = ++this._counter;
        const notification = { id, message, type, visible: true };

        this.items.push(notification);

        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(id);
            }, duration);
        }

        return id;
    },

    success(message, duration = 4000) {
        return this.show(message, 'success', duration);
    },

    error(message, duration = 6000) {
        return this.show(message, 'error', duration);
    },

    warning(message, duration = 5000) {
        return this.show(message, 'warning', duration);
    },

    info(message, duration = 4000) {
        return this.show(message, 'info', duration);
    },

    dismiss(id) {
        const index = this.items.findIndex(n => n.id === id);
        if (index !== -1) {
            this.items[index].visible = false;
            setTimeout(() => {
                this.items = this.items.filter(n => n.id !== id);
            }, 300);
        }
    },

    dismissAll() {
        this.items.forEach(n => n.visible = false);
        setTimeout(() => {
            this.items = [];
        }, 300);
    }
});

/**
 * Sidebar Store
 * Manages mobile sidebar toggle state
 */
Alpine.store('sidebar', {
    open: false,

    toggle() {
        this.open = !this.open;
    },

    close() {
        this.open = false;
    },

    init() {
        // Close sidebar on window resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                this.open = false;
            }
        });
    }
});

// ============================================================
// Register Alpine Components
// ============================================================

Alpine.data('invoiceScanner', invoiceScanner);
Alpine.data('posBilling', posBilling);
Alpine.data('purchaseForm', purchaseForm);

// ============================================================
// Global Alpine Component: Dropdown Search
// ============================================================

Alpine.data('searchDropdown', (config = {}) => ({
    query: '',
    open: false,
    items: config.items || [],
    filteredItems: [],
    selectedItem: config.selected || null,
    placeholder: config.placeholder || 'Search...',
    valueField: config.valueField || 'id',
    labelField: config.labelField || 'name',
    highlightedIndex: -1,

    init() {
        this.filteredItems = this.items.slice(0, 20);

        this.$watch('query', (value) => {
            if (value.length === 0) {
                this.filteredItems = this.items.slice(0, 20);
            } else {
                const q = value.toLowerCase();
                this.filteredItems = this.items.filter(item =>
                    item[this.labelField].toLowerCase().includes(q)
                ).slice(0, 20);
            }
            this.highlightedIndex = -1;
        });
    },

    select(item) {
        this.selectedItem = item;
        this.query = item[this.labelField];
        this.open = false;
        this.$dispatch('item-selected', { item });
    },

    clear() {
        this.selectedItem = null;
        this.query = '';
        this.open = false;
        this.$dispatch('item-cleared');
    },

    handleKeydown(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.highlightedIndex = Math.min(this.highlightedIndex + 1, this.filteredItems.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
        } else if (e.key === 'Enter' && this.highlightedIndex >= 0) {
            e.preventDefault();
            this.select(this.filteredItems[this.highlightedIndex]);
        } else if (e.key === 'Escape') {
            this.open = false;
        }
    }
}));

// ============================================================
// Global Alpine Component: Confirmation Dialog
// ============================================================

Alpine.data('confirmDialog', () => ({
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'danger',
    _resolve: null,

    open(options = {}) {
        this.title = options.title || 'Are you sure?';
        this.message = options.message || 'This action cannot be undone.';
        this.confirmText = options.confirmText || 'Confirm';
        this.cancelText = options.cancelText || 'Cancel';
        this.variant = options.variant || 'danger';
        this.show = true;

        return new Promise((resolve) => {
            this._resolve = resolve;
        });
    },

    confirm() {
        this.show = false;
        if (this._resolve) this._resolve(true);
    },

    cancel() {
        this.show = false;
        if (this._resolve) this._resolve(false);
    }
}));

// ============================================================
// Initialize Alpine
// ============================================================

window.Alpine = Alpine;
Alpine.start();
